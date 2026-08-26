<?php
// Apply reviewed adaptation segments to a separately generated Moodle course.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/forum/lib.php';

$canonicalfile = getenv('PYTHON_CANONICAL_CATALOGUE') ?: '/tmp/python-canonical.json';
$adaptationfile = getenv('PYTHON_ADAPTATION_SEGMENTS') ?: '/tmp/python-adaptation.json';
$allowpending = getenv('PYTHON_ADAPTATION_ALLOW_PENDING') === '1';
$publish = getenv('PYTHON_ADAPTATION_PUBLISH') === '1';

foreach ([$canonicalfile, $adaptationfile] as $path) {
    if (!is_readable($path)) {
        throw new RuntimeException("Required adaptation file is not readable: {$path}");
    }
}

$canonicalraw = file_get_contents($canonicalfile);
$canonical = json_decode($canonicalraw, true, 512, JSON_THROW_ON_ERROR);
$adaptation = json_decode(file_get_contents($adaptationfile), true, 512, JSON_THROW_ON_ERROR);

if (!hash_equals($adaptation['catalogue_sha256'], hash('sha256', $canonicalraw))) {
    throw new RuntimeException('Adaptation segments were prepared from a different canonical catalogue.');
}
if ($canonical['canonical']['shortname'] !== $adaptation['canonical_course']
        || $canonical['canonical']['version'] !== $adaptation['canonical_version']) {
    throw new RuntimeException('Canonical course relationship does not match the adaptation metadata.');
}

$pending = 0;
$targets = [];
foreach ($adaptation['segments'] as $segment) {
    if ($segment['status'] === 'pending') {
        $pending++;
        continue;
    }
    if (!in_array($segment['status'], ['adapted', 'reviewed'], true)) {
        throw new RuntimeException("Unsupported segment status: {$segment['status']}");
    }
    if (!is_string($segment['target']) || $segment['target'] === '') {
        throw new RuntimeException("Adapted segment has an empty target: {$segment['id']}");
    }
    $targets[$segment['id']] = $segment['target'];
}
if ($pending && !$allowpending) {
    throw new RuntimeException("{$pending} adaptation segments are still pending; refusing a release application.");
}
if ($publish && $pending) {
    throw new RuntimeException('A course with pending adaptation segments cannot be published.');
}

$shortname = $adaptation['adaptation_course'];
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
\core\session\manager::set_user(get_admin());

function json_pointer(array $parts): string {
    return '/' . implode('/', array_map(
        fn(string $part): string => str_replace(['~', '/'], ['~0', '~1'], $part),
        $parts
    ));
}

function target_for(array $targets, array $parts): ?string {
    return $targets[json_pointer($parts)] ?? null;
}

function assert_preserved_literals(string $source, string $target, string $segmentid): void {
    preg_match_all('/PYAI-[A-Za-z0-9:_-]+/', $source, $sourcemarkers);
    foreach (array_unique($sourcemarkers[0]) as $marker) {
        if (!str_contains($target, $marker)) {
            throw new RuntimeException("Adaptation removed marker {$marker} in {$segmentid}");
        }
    }
    preg_match_all('~<code>(.*?)</code>~s', $source, $sourcecode);
    preg_match_all('~<code>(.*?)</code>~s', $target, $targetcode);
    if ($sourcecode[1] !== $targetcode[1]) {
        throw new RuntimeException("Adaptation changed a code block or inline code in {$segmentid}");
    }
}

function latest_question_for_slot(stdClass $slot): stdClass {
    global $DB;
    $entryid = (int) $DB->get_field('question_references', 'questionbankentryid', [
        'component' => 'mod_quiz',
        'questionarea' => 'slot',
        'itemid' => $slot->id,
    ], MUST_EXIST);
    $sql = "SELECT q.*
              FROM {question_versions} qv
              JOIN {question} q ON q.id = qv.questionid
             WHERE qv.questionbankentryid = :entryid
          ORDER BY qv.version DESC";
    $records = $DB->get_records_sql($sql, ['entryid' => $entryid], 0, 1);
    if (!$records) {
        throw new RuntimeException("No question found for target quiz slot {$slot->id}");
    }
    return reset($records);
}

function target_activity(stdClass $course, string $cataloguekey, array $source): array {
    global $DB;
    $modname = $source['selector']['modname'];
    $moduleid = (int) $DB->get_field('modules', 'id', ['name' => $modname], MUST_EXIST);
    $cm = $DB->get_record('course_modules', [
        'course' => $course->id,
        'module' => $moduleid,
        'idnumber' => $cataloguekey,
    ]);
    if (!$cm) {
        $instances = $DB->get_records($modname, [
            'course' => $course->id,
            'name' => $source['selector']['name'],
        ]);
        if (count($instances) !== 1) {
            throw new RuntimeException(
                "Expected one target {$modname} named '{$source['selector']['name']}', found " . count($instances)
            );
        }
        $instance = reset($instances);
        $cm = get_coursemodule_from_instance($modname, $instance->id, $course->id, false, MUST_EXIST);
        $DB->set_field('course_modules', 'idnumber', $cataloguekey, ['id' => $cm->id]);
    }
    $instance = $DB->get_record($modname, ['id' => $cm->instance], '*', MUST_EXIST);
    return [$cm, $instance];
}

$applied = 0;
$coursefullname = target_for($targets, ['course', 'fullname']);
if ($coursefullname !== null) {
    $course->fullname = $coursefullname;
    $applied++;
}
$coursesummaryid = json_pointer(['course', 'summary', 'text']);
$coursesummary = $targets[$coursesummaryid] ?? null;
if ($coursesummary !== null) {
    assert_preserved_literals($canonical['course']['summary']['text'], $coursesummary, $coursesummaryid);
    $course->summary = $coursesummary;
    $applied++;
}
$course->visible = $publish ? 1 : 0;
$course->timemodified = time();
$DB->update_record('course', $course);

$activityinstances = [];
foreach ($canonical['activities'] as $key => $source) {
    [$cm, $instance] = target_activity($course, $key, $source);
    $activityinstances[$key] = [$cm, $instance];
}

foreach ($canonical['sections'] as $key => $source) {
    if (($source['selector']['component'] ?? '') === '') {
        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'section' => $source['selector']['section'],
        ], '*', MUST_EXIST);
        if (!empty($section->component)) {
            throw new RuntimeException("Expected a top-level target section for {$key}");
        }
    } else {
        $hash = substr($key, strrpos($key, '.') + 1);
        $activitykey = 'activity.subsection.' . $hash;
        if (!isset($activityinstances[$activitykey])) {
            throw new RuntimeException("Target subsection activity was not found for {$key}");
        }
        [, $subsection] = $activityinstances[$activitykey];
        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'component' => 'mod_subsection',
            'itemid' => $subsection->id,
        ], '*', MUST_EXIST);
    }
    $name = target_for($targets, ['sections', $key, 'name']);
    if ($name !== null) {
        $section->name = $name;
        $applied++;
    }
    $summaryid = json_pointer(['sections', $key, 'summary', 'text']);
    $summary = $targets[$summaryid] ?? null;
    if ($summary !== null) {
        assert_preserved_literals($source['summary']['text'], $summary, $summaryid);
        $section->summary = $summary;
        $applied++;
    }
    $DB->update_record('course_sections', $section);
}

foreach ($canonical['activities'] as $key => $source) {
    [$cm, $instance] = $activityinstances[$key];
    $name = target_for($targets, ['activities', $key, 'name']);
    if ($name !== null) {
        $instance->name = $name;
        $applied++;
    }
    foreach (['intro', 'content'] as $field) {
        if (!isset($source[$field])) {
            continue;
        }
        $segmentid = json_pointer(['activities', $key, $field, 'text']);
        $value = $targets[$segmentid] ?? null;
        if ($value !== null) {
            assert_preserved_literals($source[$field]['text'], $value, $segmentid);
            $instance->{$field} = $value;
            $applied++;
        }
    }
    $DB->update_record($source['selector']['modname'], $instance);

    if ($source['selector']['modname'] !== 'quiz') {
        continue;
    }
    $feedbacks = array_values($DB->get_records('quiz_feedback', ['quizid' => $instance->id], 'mingrade DESC'));
    if (count($feedbacks) !== count($source['feedback_bands'])) {
        throw new RuntimeException("Quiz feedback-band count differs for {$key}");
    }
    foreach ($feedbacks as $index => $feedback) {
        $segmentid = json_pointer(['activities', $key, 'feedback_bands', (string) $index, 'text', 'text']);
        $value = $targets[$segmentid] ?? null;
        if ($value !== null) {
            assert_preserved_literals($source['feedback_bands'][$index]['text']['text'], $value, $segmentid);
            $feedback->feedbacktext = $value;
            $DB->update_record('quiz_feedback', $feedback);
            $applied++;
        }
    }

    $slots = array_values($DB->get_records('quiz_slots', ['quizid' => $instance->id], 'slot ASC'));
    if (count($slots) !== count($source['questions'])) {
        throw new RuntimeException("Quiz question count differs for {$key}");
    }
    foreach ($slots as $slotindex => $slot) {
        $questionkey = sprintf('question.%02d', $slotindex + 1);
        $sourcequestion = $source['questions'][$questionkey];
        $question = latest_question_for_slot($slot);
        $updates = [
            'name' => ['name'],
            'questiontext' => ['questiontext', 'text'],
            'generalfeedback' => ['generalfeedback', 'text'],
        ];
        foreach ($updates as $field => $suffix) {
            $parts = ['activities', $key, 'questions', $questionkey, ...$suffix];
            $segmentid = json_pointer($parts);
            $value = $targets[$segmentid] ?? null;
            if ($value === null) {
                continue;
            }
            $sourcevalue = $field === 'name'
                ? $sourcequestion['name']
                : $sourcequestion[$field]['text'];
            $value = str_replace('{canonical_shortname}', $shortname, $value);
            assert_preserved_literals($sourcevalue, $value, $segmentid);
            $question->{$field} = $value;
            $applied++;
        }
        $DB->update_record('question', $question);

        $answers = array_values($DB->get_records('question_answers', ['question' => $question->id], 'id ASC'));
        if (count($answers) !== count($sourcequestion['answers'])) {
            throw new RuntimeException("Question answer count differs for {$key} {$questionkey}");
        }
        foreach ($answers as $answerindex => $answer) {
            if (abs((float) $answer->fraction - (float) $sourcequestion['answers'][$answerindex]['fraction']) > 0.000001) {
                throw new RuntimeException("Correct-answer fraction differs for {$key} {$questionkey}");
            }
            foreach (['text' => 'answer', 'feedback' => 'feedback'] as $segmentfield => $dbfield) {
                $segmentid = json_pointer([
                    'activities', $key, 'questions', $questionkey, 'answers',
                    (string) $answerindex, $segmentfield, 'text',
                ]);
                $value = $targets[$segmentid] ?? null;
                if ($value === null) {
                    continue;
                }
                $sourcevalue = $sourcequestion['answers'][$answerindex][$segmentfield]['text'];
                assert_preserved_literals($sourcevalue, $value, $segmentid);
                $answer->{$dbfield} = $value;
                $applied++;
            }
            $DB->update_record('question_answers', $answer);
        }
    }
}

// Moodle creates the announcements forum lazily. Seed any missing canonical
// posts in this language course before applying the adapted text.
$newsforum = forum_get_course_forum($course->id, 'news');
foreach (array_values($canonical['announcements']) as $source) {
    if (!preg_match('/PYAI-[A-Za-z0-9:_-]*ANNOUNCEMENT[A-Za-z0-9:_-]*/', $source['message']['text'], $matches)) {
        throw new RuntimeException('Canonical announcement is missing its stable marker.');
    }
    $existsql = "SELECT 1
                   FROM {forum_posts} p
                   JOIN {forum_discussions} d ON d.id = p.discussion
                   JOIN {forum} f ON f.id = d.forum
                  WHERE f.course = :courseid
                    AND " . $DB->sql_like('p.message', ':marker');
    if (!$DB->record_exists_sql($existsql, ['courseid' => $course->id, 'marker' => '%' . $matches[0] . '%'])) {
        forum_add_discussion((object) [
            'course' => $course->id,
            'forum' => $newsforum->id,
            'name' => $source['subject'],
            'message' => $source['message']['text'],
            'messageformat' => $source['message']['format'],
            'messagetrust' => 0, 'mailnow' => 0, 'groupid' => -1, 'itemid' => 0,
        ], null, null, get_admin()->id);
    }
}
$sql = "SELECT p.*
          FROM {forum_posts} p
          JOIN {forum_discussions} d ON d.id = p.discussion
          JOIN {forum} f ON f.id = d.forum
         WHERE f.course = :courseid
           AND " . $DB->sql_like('p.message', ':marker') . "
      ORDER BY p.created ASC, p.id ASC";
$posts = array_values($DB->get_records_sql($sql, [
    'courseid' => $course->id,
    'marker' => '%PYAI-V%-ANNOUNCEMENT%',
]));
if (count($posts) !== count($canonical['announcements'])) {
    throw new RuntimeException('Target announcement count differs from canonical.');
}
foreach (array_values($canonical['announcements']) as $index => $source) {
    $key = sprintf('announcement.%02d', $index + 1);
    $post = $posts[$index];
    $subject = target_for($targets, ['announcements', $key, 'subject']);
    if ($subject !== null) {
        $post->subject = $subject;
        $applied++;
    }
    $messageid = json_pointer(['announcements', $key, 'message', 'text']);
    $message = $targets[$messageid] ?? null;
    if ($message !== null) {
        assert_preserved_literals($source['message']['text'], $message, $messageid);
        $post->message = $message;
        $applied++;
    }
    $DB->update_record('forum_posts', $post);
}

rebuild_course_cache($course->id, true);

echo json_encode([
    'status' => 'ok',
    'course_id' => (int) $course->id,
    'shortname' => $course->shortname,
    'fullname' => $course->fullname,
    'canonical_version' => $adaptation['canonical_version'],
    'adaptation_version' => $adaptation['adaptation_version'],
    'applied_fields' => $applied,
    'pending_segments' => $pending,
    'visible' => (bool) $course->visible,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
