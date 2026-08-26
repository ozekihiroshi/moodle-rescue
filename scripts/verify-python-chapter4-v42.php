<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';

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

$report = [];
$allquestionnames = [];
foreach ($names as $lesson => $name) {
    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $name], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
    $slots = $DB->get_records('quiz_slots', ['quizid' => $quiz->id], 'slot ASC');
    $namesinquiz = [];
    foreach ($slots as $slot) {
        $reference = $DB->get_record('question_references', [
            'component' => 'mod_quiz',
            'questionarea' => 'slot',
            'itemid' => $slot->id,
        ], '*', MUST_EXIST);
        $entry = $DB->get_record('question_bank_entries', ['id' => $reference->questionbankentryid], '*', MUST_EXIST);
        $version = $DB->get_record_sql('SELECT * FROM {question_versions} WHERE questionbankentryid = ? ORDER BY version DESC', [$entry->id], MUST_EXIST);
        $question = $DB->get_record('question', ['id' => $version->questionid], '*', MUST_EXIST);
        $namesinquiz[] = $question->name;
        $allquestionnames[] = $question->name;
    }
    $gradepass = (float)$DB->get_field('grade_items', 'gradepass', [
        'courseid' => $course->id,
        'itemmodule' => 'quiz',
        'iteminstance' => $quiz->id,
    ]);
    $oldprefix = $ja ? '旧版（履歴保存）：' : 'Previous version (history preserved): ';
    $old = $DB->get_record('quiz', ['course' => $course->id, 'name' => $oldprefix . $name], '*', MUST_EXIST);
    $oldcm = get_coursemodule_from_instance('quiz', $old->id, $course->id, false, MUST_EXIST);
    $report[] = [
        'lesson' => $lesson,
        'quizid' => (int)$quiz->id,
        'visible' => (int)$cm->visible,
        'slots' => count($slots),
        'sumgrades' => (float)$quiz->sumgrades,
        'gradepass' => $gradepass,
        'attempts' => (int)$DB->count_records('quiz_attempts', ['quiz' => $quiz->id]),
        'questionnames' => $namesinquiz,
        'oldquizid' => (int)$old->id,
        'oldvisible' => (int)$oldcm->visible,
        'oldattempts' => (int)$DB->count_records('quiz_attempts', ['quiz' => $old->id]),
    ];
}

echo json_encode([
    'status' => 'ok',
    'shortname' => $shortname,
    'quizzes' => $report,
    'uniquequestionnames' => count(array_unique($allquestionnames)),
    'totalquestionnames' => count($allquestionnames),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
