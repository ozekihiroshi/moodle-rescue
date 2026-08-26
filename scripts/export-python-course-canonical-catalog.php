<?php
// Export stable, translatable content from the canonical Python sample course.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$canonicalversion = getenv('PYTHON_CANONICAL_VERSION') ?: '1.0.0';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$modinfo = get_fast_modinfo($course);

function catalogue_key(string $type, string $name): string {
    return $type . '.' . substr(sha1($name), 0, 12);
}

function text_field(?string $value, int $format = FORMAT_HTML): array {
    return ['text' => $value ?? '', 'format' => $format];
}

function latest_question_for_entry(int $entryid): stdClass {
    global $DB;
    $sql = "SELECT q.*
              FROM {question_versions} qv
              JOIN {question} q ON q.id = qv.questionid
             WHERE qv.questionbankentryid = :entryid
          ORDER BY qv.version DESC";
    $records = $DB->get_records_sql($sql, ['entryid' => $entryid], 0, 1);
    if (!$records) {
        throw new RuntimeException("No question version found for bank entry {$entryid}");
    }
    return reset($records);
}

$catalogue = [
    'schema_version' => 1,
    'catalogue_kind' => 'canonical_course_content',
    'canonical' => [
        'shortname' => $course->shortname,
        'version' => $canonicalversion,
        'language' => 'en',
    ],
    'course' => [
        'fullname' => $course->fullname,
        'summary' => text_field($course->summary, (int) $course->summaryformat),
    ],
    'sections' => [],
    'activities' => [],
    'announcements' => [],
];

foreach ($modinfo->get_section_info_all() as $section) {
    if (!$section) {
        continue;
    }
    if (empty($section->component)) {
        $key = sprintf('section.%02d', (int) $section->section);
        $selector = ['section' => (int) $section->section, 'component' => ''];
    } else if ($section->component === 'mod_subsection') {
        $itemname = $DB->get_field('subsection', 'name', ['id' => $section->itemid], MUST_EXIST);
        $key = catalogue_key('section.delegated', $itemname);
        $selector = ['component' => 'mod_subsection', 'item_name' => $itemname];
    } else {
        throw new RuntimeException("Unsupported delegated section component: {$section->component}");
    }
    if (isset($catalogue['sections'][$key])) {
        throw new RuntimeException("Duplicate section catalogue key: {$key}");
    }
    $catalogue['sections'][$key] = [
        'selector' => $selector,
        'name' => $section->name ?? '',
        'summary' => text_field($section->summary ?? '', (int) ($section->summaryformat ?? FORMAT_HTML)),
    ];
}

$cms = $modinfo->get_cms();
ksort($cms, SORT_NUMERIC);
foreach ($cms as $cm) {
    if (!in_array($cm->modname, ['page', 'assign', 'quiz', 'lti', 'subsection'], true)) {
        continue;
    }
    $instance = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);
    $key = catalogue_key('activity.' . $cm->modname, $cm->name);
    if (isset($catalogue['activities'][$key])) {
        throw new RuntimeException("Duplicate catalogue key for {$cm->modname}: {$cm->name}");
    }
    $activity = [
        'selector' => ['modname' => $cm->modname, 'name' => $cm->name],
        'name' => $instance->name,
        'intro' => text_field($instance->intro ?? '', (int) ($instance->introformat ?? FORMAT_HTML)),
        'visible' => (bool) $cm->visible,
    ];
    if ($cm->modname === 'page') {
        $activity['content'] = text_field($instance->content, (int) $instance->contentformat);
    } else if ($cm->modname === 'quiz') {
        $activity['feedback_bands'] = [];
        foreach ($DB->get_records('quiz_feedback', ['quizid' => $instance->id], 'mingrade DESC') as $feedback) {
            $activity['feedback_bands'][] = [
                'mingrade' => (float) $feedback->mingrade,
                'text' => text_field($feedback->feedbacktext, (int) $feedback->feedbacktextformat),
            ];
        }
        $activity['questions'] = [];
        foreach ($DB->get_records('quiz_slots', ['quizid' => $instance->id], 'slot ASC') as $slot) {
            $entryid = (int) $DB->get_field('question_references', 'questionbankentryid', [
                'component' => 'mod_quiz',
                'questionarea' => 'slot',
                'itemid' => $slot->id,
            ], MUST_EXIST);
            $question = latest_question_for_entry($entryid);
            $questionkey = sprintf('question.%02d', (int) $slot->slot);
            $questiondata = [
                'selector' => [
                    'slot' => (int) $slot->slot,
                    'name' => str_replace($shortname, '{canonical_shortname}', $question->name),
                ],
                'name' => str_replace($shortname, '{canonical_shortname}', $question->name),
                'questiontext' => text_field($question->questiontext, (int) $question->questiontextformat),
                'generalfeedback' => text_field($question->generalfeedback, (int) $question->generalfeedbackformat),
                'answers' => [],
            ];
            foreach ($DB->get_records('question_answers', ['question' => $question->id], 'id ASC') as $answer) {
                $questiondata['answers'][] = [
                    'text' => text_field($answer->answer, (int) $answer->answerformat),
                    'fraction' => (float) $answer->fraction,
                    'feedback' => text_field($answer->feedback, (int) $answer->feedbackformat),
                ];
            }
            $activity['questions'][$questionkey] = $questiondata;
        }
    }
    $catalogue['activities'][$key] = $activity;
}

$sql = "SELECT p.id, p.subject, p.message, p.messageformat
          FROM {forum_posts} p
          JOIN {forum_discussions} d ON d.id = p.discussion
          JOIN {forum} f ON f.id = d.forum
         WHERE f.course = :courseid
           AND " . $DB->sql_like('p.message', ':marker') . "
      ORDER BY p.created ASC, p.id ASC";
foreach ($DB->get_records_sql($sql, [
    'courseid' => $course->id,
    'marker' => '%PYAI-V%-ANNOUNCEMENT%',
]) as $index => $post) {
    $catalogue['announcements'][sprintf('announcement.%02d', count($catalogue['announcements']) + 1)] = [
        'selector' => ['subject' => $post->subject],
        'subject' => $post->subject,
        'message' => text_field($post->message, (int) $post->messageformat),
    ];
}

echo json_encode(
    $catalogue,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
) . PHP_EOL;
