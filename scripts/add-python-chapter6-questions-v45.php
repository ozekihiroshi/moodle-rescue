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
$context = context_course::instance($course->id);
$category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => 'Python course checks']);
if (!$category) {
    $categories = $DB->get_records('question_categories', ['contextid' => $context->id], 'id ASC');
    $category = reset($categories);
}
if (!$category) throw new RuntimeException('Question category not found');

$lessons = $ja ? [
    '61' => ['topic'=>'6.1 読み込む前に調べる','page'=>'レッスン6.1：読み込む前に調べる','lti'=>'Python Lab 6.1：読み込む前に調べる','quiz'=>'理解度チェック：6.1 読み込む前に調べる','oldquiz'=>'実践理解度チェック：安全なスケールアップ'],
    '62' => ['topic'=>'6.2 チャンクを越えて正しく集計する','page'=>'レッスン6.2：チャンクを越えて正しく集計する','lti'=>'Python Lab 6.2：チャンクを越えた集計','quiz'=>'理解度チェック：6.2 チャンクを越えて正しく集計する','oldquiz'=>null],
    '63' => ['topic'=>'6.3 照合して再現可能にする','page'=>'レッスン6.3：照合して再現可能にする','lti'=>'Python Lab 6.3：照合と再現','quiz'=>'理解度チェック：6.3 照合して再現可能にする','oldquiz'=>null],
] : [
    '61' => ['topic'=>'6.1 Inspect before loading','page'=>'Lesson 6.1: Inspect before loading','lti'=>'Python Lab 6.1: Inspect before loading','quiz'=>'Knowledge check: 6.1 Inspect before loading','oldquiz'=>'Applied check: Scaling up safely'],
    '62' => ['topic'=>'6.2 Aggregate correctly across chunks','page'=>'Lesson 6.2: Aggregate correctly across chunks','lti'=>'Python Lab 6.2: Chunked aggregation','quiz'=>'Knowledge check: 6.2 Aggregate correctly across chunks','oldquiz'=>null],
    '63' => ['topic'=>'6.3 Reconcile and reproduce','page'=>'Lesson 6.3: Reconcile and reproduce','lti'=>'Python Lab 6.3: Reconcile and reproduce','quiz'=>'Knowledge check: 6.3 Reconcile and reproduce','oldquiz'=>null],
];
function v44q_save(int $categoryid, int $contextid, string $prefix, array $data, bool $ja): stdClass {
    $question = (object)['qtype' => 'multichoice', 'category' => $categoryid . ',' . $contextid];
    $answers = $feedback = $fractions = [];
    foreach ($data['c'] as $index => $choice) {
        $correct = $index === $data['ok'];
        $answers[] = ['text' => $choice, 'format' => FORMAT_PLAIN];
        $feedback[] = [
            'text' => $correct
                ? ($ja ? '<p>正解です。</p>' : '<p>Correct.</p>')
                : ($ja ? '<p>問い、粒度、指標、図の意味を対応させ、解説を読んで再挑戦してください。</p>' : '<p>Match question, grain, metric, and chart meaning; read the explanation and retry.</p>'),
            'format' => FORMAT_HTML,
        ];
        $fractions[] = $correct ? 1.0 : 0.0;
    }
    $form = (object)[
        'name' => $prefix . $data['id'],
        'category' => $categoryid . ',' . $contextid,
        'questiontext' => ['text' => '<div style="white-space:pre-wrap">' . s($data['p']) . '</div>', 'format' => FORMAT_HTML],
        'generalfeedback' => ['text' => '<p><strong>' . ($ja ? '確認点：' : 'Learning point:') . '</strong> ' . s($data['why']) . '</p>', 'format' => FORMAT_HTML],
        'defaultmark' => 10, 'penalty' => .3333333,
        'status' => question_version_status::QUESTION_STATUS_READY,
        'idnumber' => null, 'single' => 1, 'shuffleanswers' => 1,
        'answernumbering' => 'abc', 'showstandardinstruction' => 1,
        'correctfeedback' => ['text' => $ja ? '<p>正解です。コードと表から同じ考え方を確認してください。</p>' : '<p>Correct. Confirm the same idea in the code and plot table.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => $ja ? '<p>解説を読み、対応する例を確認してから再挑戦してください。</p>' : '<p>Read the explanation, revisit the corresponding example, and retry.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0, 'answer' => $answers, 'fraction' => $fractions,
        'feedback' => $feedback, 'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v44q_create_quiz(stdClass $course, int $sectionnumber, string $name, bool $ja): stdClass {
    global $DB;
    $intro = ($ja
        ? '<p>これは学んだ考え方を定着させる確認です。何度でも再挑戦でき、最高得点を記録します。90%以上で合格、100%を目指してください。</p>'
        : '<p>This is a learning check. Retry as needed; the highest score is kept. 90% passes. Read the explanations and aim for 100%.</p>')
        . '<p><small>PYAI-V45-CHAPTER6-QUESTIONS</small></p>';
    return add_moduleinfo((object)[
        'module' => $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST),
        'modulename' => 'quiz', 'section' => $sectionnumber, 'name' => $name,
        'intro' => $intro, 'introformat' => FORMAT_HTML,
        'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0,
        'overduehandling' => 'autosubmit', 'graceperiod' => 0,
        'preferredbehaviour' => 'deferredfeedback', 'attempts' => 0,
        'attemptonlast' => 0, 'grademethod' => QUIZ_GRADEHIGHEST,
        'decimalpoints' => 0, 'questiondecimalpoints' => -1,
        'questionsperpage' => 10, 'navmethod' => QUIZ_NAVMETHOD_FREE,
        'shuffleanswers' => 1, 'grade' => 100,
        'reviewattempt' => 69888, 'reviewcorrectness' => 4352,
        'reviewmarks' => 4352, 'reviewspecificfeedback' => 4352,
        'reviewgeneralfeedback' => 4352, 'reviewrightanswer' => 4352,
        'reviewoverallfeedback' => 4352, 'password' => '', 'quizpassword' => '',
        'subnet' => '', 'browsersecurity' => '-', 'delay1' => 0, 'delay2' => 0,
        'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0,
        'groupingid' => 0, 'completion' => 0, 'showdescription' => 1,
    ], $course);
}

$results = [];
foreach ($lessons as $lesson => $names) {
    $sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $names['topic']], '*', MUST_EXIST);
    $section = $DB->get_record('course_sections', [
        'course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id,
    ], '*', MUST_EXIST);
    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $names['quiz']]);
    if (!$quiz || !str_contains((string)$quiz->intro, 'PYAI-V45-CHAPTER6-QUESTIONS')) {
        if ($quiz) {
            $oldcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
            $quiz->name = ($ja ? '旧版（履歴保存）：' : 'Previous version (history preserved): ') . $names['quiz'];
            $quiz->timemodified = time();
            $DB->update_record('quiz', $quiz);
            set_coursemodule_visible($oldcm->id, 0);
        }
        if ($names['oldquiz']) {
            $old = $DB->get_record('quiz', ['course' => $course->id, 'name' => $names['oldquiz']]);
            if ($old) {
                $oldcm = get_coursemodule_from_instance('quiz', $old->id, $course->id, false, MUST_EXIST);
                $old->name = ($ja ? '旧版（履歴保存）：' : 'Previous version (history preserved): ') . $names['oldquiz'];
                $old->timemodified = time();
                $DB->update_record('quiz', $old);
                set_coursemodule_visible($oldcm->id, 0);
            }
        }
        $created = v44q_create_quiz($course, (int)$section->section, $names['quiz'], $ja);
        $quiz = $DB->get_record('quiz', ['id' => $created->instance], '*', MUST_EXIST);
        $path = '/workspace/sample-content/introduction-to-python/chapter6-questions-v45/' . ($ja ? 'ja' : 'en') . '/' . $lesson . '.json';
        $questions = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (count($questions) !== 10) throw new RuntimeException('Expected 10 questions: ' . $path);
        foreach ($questions as $data) {
            $saved = v44q_save((int)$category->id, (int)$context->id, $shortname . ' v45 ' . $lesson . ': ', $data, $ja);
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
    }
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $names['page']], '*', MUST_EXIST);
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $names['lti']], '*', MUST_EXIST);
    $pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
    $lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
    $quizcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
    $section->sequence = implode(',', [(int)$pagecm->id, (int)$lticm->id, (int)$quizcm->id]);
    $DB->update_record('course_sections', $section);
    foreach ([$pagecm->id, $lticm->id, $quizcm->id] as $cmid) {
        $DB->set_field('course_modules', 'section', $section->id, ['id' => $cmid]);
    }
    $results[] = ['lesson' => $lesson, 'quizid' => (int)$quiz->id, 'questions' => 10];
}

rebuild_course_cache($course->id, true);
echo json_encode(['status' => 'ok', 'shortname' => $shortname, 'results' => $results, 'marker' => 'PYAI-V45-CHAPTER6-QUESTIONS'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
