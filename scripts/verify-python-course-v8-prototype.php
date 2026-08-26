<?php
// Verify the Chapter 0 and Chapter 1 Lesson 1 v2 prototype.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->libdir . '/completionlib.php';
require_once $CFG->dirroot . '/completion/criteria/completion_criteria_activity.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$language = $shortname === 'PYAI-INTRO-JA' ? 'ja' : 'en';
$errors = [];

$expected = $language === 'ja' ? [
    'section0' => '第0章 — PythonとPython Labを始める',
    'chapter1' => '第1章 — プログラミングの基礎と基本データ',
    'topic' => '1.1 プログラム・値・式・出力',
    'guide' => 'はじめに：Python学習の全体地図',
    'labguide' => 'Python Labの使い方：Notebook・Console・スクリプト',
    'lesson' => 'レッスン1：プログラム・値・式・出力',
    'quiz' => '理解度チェック：レッスン1 プログラム・値・式・出力',
    'lti' => 'Python Lab 01：プログラム・値・式・出力',
    'startlti' => 'Python Lab 00：Notebookを始める',
] : [
    'section0' => 'Chapter 0 — Starting Python and Python Lab',
    'chapter1' => 'Chapter 1 — Programming Foundations and Scalar Values',
    'topic' => '1.1 Programs, values, expressions, and output',
    'guide' => 'Start here: the Python learning map',
    'labguide' => 'Using Python Lab: Notebook, Console, and scripts',
    'lesson' => 'Lesson 1: Programs, values, expressions, and output',
    'quiz' => 'Knowledge check: Lesson 1: Programs, values, expressions, and output',
    'lti' => 'Python Lab 01: Programs, values, expressions, and output',
    'startlti' => 'Python Lab 00: Getting started with Notebooks',
];

$teachername = $language === 'ja'
    ? '教師用ガイド（学習者には非表示）'
    : 'Teacher guide (hidden from students)';

$section0 = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0], '*', MUST_EXIST);
if ($section0->name !== $expected['section0']) {
    $errors[] = "Chapter 0 name mismatch: {$section0->name}";
}

$normalnames = $DB->get_fieldset_select('course_sections', 'name', 'course = :course AND component IS NULL AND section > 0', ['course' => $course->id]);
if (!in_array($expected['chapter1'], $normalnames, true)) {
    $errors[] = 'Chapter 1 name not found.';
}
$subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $expected['topic']]);
if (!$subsection) {
    $errors[] = 'Lesson 1 subsection not found.';
}

foreach (['guide', 'labguide', 'lesson'] as $key) {
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $expected[$key]]);
    if (!$page || !str_contains($page->content, 'PYAI-V8-')) {
        $errors[] = "Missing or unmarked page: {$expected[$key]}";
    }
}
$lessonpage = $DB->get_record('page', ['course' => $course->id, 'name' => $expected['lesson']], '*', MUST_EXIST);
if (!str_contains($lessonpage->content, 'PYAI-V8-LESSON1-REFINED')) {
    $errors[] = 'Lesson 1 does not contain the refined scope marker.';
}
$labpage = $DB->get_record('page', ['course' => $course->id, 'name' => $expected['labguide']], '*', MUST_EXIST);
if (!str_contains($labpage->content, 'PYAI-V8-CHAPTER0-EVIDENCE')) {
    $errors[] = 'Chapter 0 does not contain the evidence-complete extension.';
}
if (!str_contains($lessonpage->content, 'PYAI-V8-LESSON1-EVIDENCE')) {
    $errors[] = 'Lesson 1 does not contain the wrong-result diagnosis extension.';
}
$guidepage = $DB->get_record('page', ['course' => $course->id, 'name' => $expected['guide']], '*', MUST_EXIST);
$repeatedaipolicy = $language === 'ja'
    ? 'AI支援は利用できますが'
    : 'AI assistance is permitted';
if (str_contains($guidepage->content, $repeatedaipolicy)) {
    $errors[] = 'Repeated AI-use instruction remains in the Chapter 0 guide.';
}
$teacherpage = $DB->get_record('page', ['course' => $course->id, 'name' => $teachername], '*', MUST_EXIST);
if (!str_contains($teacherpage->content, 'PYAI-V8-TEACHER-CH0-L1')) {
    $errors[] = 'Chapter 0 and Lesson 1 teacher support is missing.';
}
$teachercm = get_coursemodule_from_instance('page', $teacherpage->id, $course->id, false, MUST_EXIST);
if ($teachercm->visible) {
    $errors[] = 'Teacher guide is visible to learners.';
}
$lessonplain = html_entity_decode(strip_tags($lessonpage->content));
foreach (['type(', '//', '%', '**', 'input('] as $deferredsyntax) {
    if (str_contains($lessonplain, $deferredsyntax)) {
        $errors[] = "Lesson 1 prematurely teaches deferred syntax: {$deferredsyntax}";
    }
}

foreach (['lti', 'startlti'] as $key) {
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $expected[$key]]);
    if (!$lti) {
        $errors[] = "Missing LTI activity: {$expected[$key]}";
        continue;
    }
    $filename = $key === 'startlti' ? '00_start_here.ipynb' : '01_programs_values_output.ipynb';
    $expectedpath = '/hub/user-redirect/lab/tree/' . ($language === 'ja' ? 'ja/' : '') . $filename;
    if (!str_ends_with($lti->toolurl, $expectedpath)) {
        $errors[] = "Wrong Notebook path for {$expected[$key]}: {$lti->toolurl}";
    }
}

$quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $expected['quiz']]);
if (!$quiz) {
    $errors[] = 'v2 Lesson 1 quiz not found.';
} else {
    $newcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
    $slotcount = (int) $DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
    if ($slotcount !== 10) {
        $errors[] = "v2 Lesson 1 quiz has {$slotcount} slots instead of 10.";
    }
    $sql = "SELECT q.*
              FROM {quiz_slots} qs
              JOIN {question_references} qr
                ON qr.component = 'mod_quiz' AND qr.questionarea = 'slot' AND qr.itemid = qs.id
              JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid
              JOIN {question} q ON q.id = qv.questionid
             WHERE qs.quizid = :quizid";
    $questions = $DB->get_records_sql($sql, ['quizid' => $quiz->id]);
    if (count($questions) !== 10) {
        $errors[] = 'Could not resolve all 10 v2 Lesson 1 questions.';
    }
    foreach ($questions as $question) {
        $search = strtolower($question->name . ' ' . strip_tags($question->questiontext) . ' ' . strip_tags($question->generalfeedback));
        if (str_contains($search, 'naledi') || preg_match('/\bai\b/', $search)) {
            $errors[] = "Narrative or AI-policy content remains in question {$question->name}.";
        }
        if (!str_contains($question->name, 'L1R-')) {
            $errors[] = "An earlier broad Lesson 1 question remains active: {$question->name}.";
        }
    }
    $criterionexists = $DB->record_exists('course_completion_criteria', [
        'course' => $course->id,
        'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY,
        'moduleinstance' => $newcm->id,
    ]);
    if (!$criterionexists) {
        $errors[] = 'Course-completion criterion does not point to the v2 Lesson 1 quiz.';
    }
}

$activequizcount = (int) $DB->count_records_sql(
    "SELECT COUNT(1)
       FROM {course_modules} cm
       JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
      WHERE cm.course = :course AND cm.visible = 1",
    ['course' => $course->id]
);
$activequestioncount = (int) $DB->count_records_sql(
    "SELECT COUNT(1)
       FROM {quiz_slots} qs
       JOIN {quiz} q ON q.id = qs.quizid
       JOIN {course_modules} cm ON cm.instance = q.id
       JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
      WHERE q.course = :course AND cm.visible = 1",
    ['course' => $course->id]
);
if ($activequizcount !== 12 || $activequestioncount !== 120) {
    $errors[] = "Expected 12 active quizzes and 120 active questions; found {$activequizcount} and {$activequestioncount}.";
}

$archives = $DB->get_records_select('quiz', 'course = :course AND (' . $DB->sql_like('name', ':archive') . ' OR ' . $DB->sql_like('name', ':jaarchive') . ')', [
    'course' => $course->id,
    'archive' => '%archive%',
    'jaarchive' => '%受験履歴%',
]);
foreach ($archives as $archive) {
    $archivecm = get_coursemodule_from_instance('quiz', $archive->id, $course->id, false, MUST_EXIST);
    $attempts = (int) $DB->count_records('quiz_attempts', ['quiz' => $archive->id]);
    if ($archivecm->visible || $attempts < 1) {
        $errors[] = "Attempt archive {$archive->name} is visible or has no preserved attempt.";
    }
}

$narrativevisible = (int) $DB->count_records_sql(
    "SELECT COUNT(1)
       FROM {page} p
       JOIN {course_modules} cm ON cm.instance = p.id
       JOIN {modules} m ON m.id = cm.module AND m.name = 'page'
      WHERE p.course = :course AND cm.visible = 1
        AND (" . $DB->sql_like('p.name', ':naledi') . ' OR ' . $DB->sql_like('p.name', ':janame') . ')',
    ['course' => $course->id, 'naledi' => '%Naledi%', 'janame' => '%ナレディ%']
);
if ($narrativevisible !== 0) {
    $errors[] = 'A Naledi narrative page remains visible.';
}

if ($errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

echo json_encode([
    'verified' => true,
    'courseid' => (int) $course->id,
    'shortname' => $shortname,
    'chapter0' => $expected['section0'],
    'chapter1_lesson1' => $expected['lesson'],
    'active_quizzes' => $activequizcount,
    'active_questions' => $activequestioncount,
    'attempt_archives' => count($archives),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
