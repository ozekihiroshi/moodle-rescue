<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/question/editlib.php';

use core_question\local\bank\question_version_status;

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';
$names = $ja ? [
    '41' => '理解度チェック：4.1 オブジェクトとクラス',
    '42' => '理解度チェック：4.2 状態と検証',
    '43' => '理解度チェック：4.3 合成と責任分担',
    '44' => '理解度チェック：4.4 保存とテスト',
] : [
    '41' => 'Knowledge check: 4.1 Objects and classes',
    '42' => 'Knowledge check: 4.2 State and validation',
    '43' => 'Knowledge check: 4.3 Composition and responsibility',
    '44' => 'Knowledge check: 4.4 Persistence and testing',
];
$context = context_course::instance($course->id);
$category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => 'Python course checks']);
if (!$category) {
    $categories = $DB->get_records('question_categories', ['contextid' => $context->id], 'id ASC');
    $category = reset($categories);
}

function v42_add_question(int $categoryid, int $contextid, string $prefix, array $data, bool $ja): stdClass {
    $question = (object)['qtype' => 'multichoice', 'category' => $categoryid . ',' . $contextid];
    $answers = $feedback = $fractions = [];
    foreach ($data['c'] as $index => $choice) {
        $correct = $index === $data['ok'];
        $answers[] = ['text' => $choice, 'format' => FORMAT_PLAIN];
        $feedback[] = ['text' => $correct ? ($ja ? '<p>正解です。</p>' : '<p>Correct.</p>') : ($ja ? '<p>コードの対象と状態を追って、もう一度試してください。</p>' : '<p>Trace the receiver and state, then try again.</p>'), 'format' => FORMAT_HTML];
        $fractions[] = $correct ? 1.0 : 0.0;
    }
    $form = (object)[
        'name' => $prefix . $data['id'],
        'category' => $categoryid . ',' . $contextid,
        'questiontext' => ['text' => '<div style="white-space:pre-wrap">' . s($data['p']) . '</div>', 'format' => FORMAT_HTML],
        'generalfeedback' => ['text' => '<p><strong>' . ($ja ? '確認ポイント：' : 'Learning point:') . '</strong> ' . s($data['why']) . '</p>', 'format' => FORMAT_HTML],
        'defaultmark' => 10, 'penalty' => .3333333,
        'status' => question_version_status::QUESTION_STATUS_READY, 'idnumber' => null,
        'single' => 1, 'shuffleanswers' => 1, 'answernumbering' => 'abc', 'showstandardinstruction' => 1,
        'correctfeedback' => ['text' => $ja ? '<p>正解です。次の問題でもコードから根拠を確認しましょう。</p>' : '<p>Correct. Keep using the code as evidence.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $ja ? '<p>解説を読み、状態を一行ずつ追って再挑戦してください。</p>' : '<p>Read the explanation, trace state line by line, and retry.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0, 'answer' => $answers, 'fraction' => $fractions, 'feedback' => $feedback, 'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v42_add_quiz(stdClass $course, int $sectionnumber, string $name, bool $ja): stdClass {
    global $DB;
    $intro = ($ja
        ? '<p>学んだ内容を定着させる確認です。何度でも受験でき、最高点を保持します。90%以上で合格です。解説を読み、100%を目指してください。</p>'
        : '<p>This is a learning check. Retry as needed; the highest score is kept. 90% passes. Read the explanations and aim for 100%.</p>')
        . '<p><small>PYAI-V42-CHAPTER4-QUESTIONS</small></p>';
    return add_moduleinfo((object)[
        'module' => $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST),
        'modulename' => 'quiz', 'section' => $sectionnumber, 'name' => $name,
        'intro' => $intro, 'introformat' => FORMAT_HTML,
        'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0,
        'overduehandling' => 'autosubmit', 'graceperiod' => 0, 'preferredbehaviour' => 'deferredfeedback',
        'attempts' => 0, 'attemptonlast' => 0, 'grademethod' => QUIZ_GRADEHIGHEST,
        'decimalpoints' => 0, 'questiondecimalpoints' => -1, 'questionsperpage' => 10,
        'navmethod' => QUIZ_NAVMETHOD_FREE, 'shuffleanswers' => 1, 'grade' => 100,
        'reviewattempt' => 69888, 'reviewcorrectness' => 4352, 'reviewmarks' => 4352,
        'reviewspecificfeedback' => 4352, 'reviewgeneralfeedback' => 4352,
        'reviewrightanswer' => 4352, 'reviewoverallfeedback' => 4352,
        'password' => '', 'quizpassword' => '', 'subnet' => '', 'browsersecurity' => '-',
        'delay1' => 0, 'delay2' => 0, 'visible' => 1, 'visibleoncoursepage' => 1,
        'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 1,
    ], $course);
}

$results = [];
foreach ($names as $lesson => $name) {
    $existing = $DB->get_records('quiz', ['course' => $course->id, 'name' => $name], 'id ASC');
    $current = null;
    foreach ($existing as $candidate) {
        if (str_contains((string)$candidate->intro, 'PYAI-V42-CHAPTER4-QUESTIONS')) {
            $current = $candidate;
            break;
        }
    }
    if ($current) {
        $cm = get_coursemodule_from_instance('quiz', $current->id, $course->id, false, MUST_EXIST);
        set_coursemodule_visible($cm->id, 1);
        $results[] = ['lesson' => $lesson, 'quizid' => (int)$current->id, 'status' => 'already-current'];
        continue;
    }
    $old = reset($existing);
    if (!$old) {
        throw new RuntimeException('Previous quiz not found: ' . $name);
    }
    $oldcm = get_coursemodule_from_instance('quiz', $old->id, $course->id, false, MUST_EXIST);
    $section = $DB->get_record('course_sections', ['id' => $oldcm->section], '*', MUST_EXIST);

    // Preserve every old attempt and slot. Only rename and hide the old activity.
    $old->name = ($ja ? '旧版（履歴保存）：' : 'Previous version (history preserved): ') . $name;
    $old->timemodified = time();
    $DB->update_record('quiz', $old);
    set_coursemodule_visible($oldcm->id, 0);

    $created = v42_add_quiz($course, (int)$section->section, $name, $ja);
    $quiz = $DB->get_record('quiz', ['id' => $created->instance], '*', MUST_EXIST);
    $path = '/workspace/sample-content/introduction-to-python/chapter4-questions-v42/' . ($ja ? 'ja' : 'en') . '/' . $lesson . '.json';
    $questions = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (count($questions) !== 10) {
        throw new RuntimeException('Expected 10 questions: ' . $path);
    }
    foreach ($questions as $data) {
        $saved = v42_add_question((int)$category->id, (int)$context->id, $shortname . ' v42 ' . $lesson . ': ', $data, $ja);
        quiz_add_quiz_question($saved->id, $quiz, 0, 10);
    }
    $DB->set_field('quiz_slots', 'maxmark', 10, ['quizid' => $quiz->id]);
    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
    $gradeitem = $DB->get_record('grade_items', ['courseid' => $course->id, 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id]);
    if ($gradeitem) {
        $gradeitem->gradepass = 90;
        $gradeitem->timemodified = time();
        $DB->update_record('grade_items', $gradeitem);
    }
    $results[] = [
        'lesson' => $lesson, 'quizid' => (int)$quiz->id, 'questions' => 10,
        'preservedquizid' => (int)$old->id,
        'preservedattempts' => (int)$DB->count_records('quiz_attempts', ['quiz' => $old->id]),
        'status' => 'created',
    ];
}
rebuild_course_cache($course->id, true);
echo json_encode(['status' => 'ok', 'shortname' => $shortname, 'results' => $results, 'marker' => 'PYAI-V42-CHAPTER4-QUESTIONS-SAFE'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
