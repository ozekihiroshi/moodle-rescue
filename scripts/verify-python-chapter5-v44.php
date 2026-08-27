<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';
$errors = [];

function v44verify(bool $condition, string $message): void {
    global $errors;
    if (!$condition) $errors[] = $message;
}

$expectedchapter = $ja ? '第5章 — 根拠を伝える' : 'Chapter 5 — Communicating Evidence';
$lessons = $ja ? [
    '51' => ['topic' => '5.1 問いから図へ', 'page' => 'レッスン5.1：問いから図へ', 'lti' => 'Python Lab 5.1：問いから図へ', 'quiz' => '理解度チェック：5.1 問いから図へ', 'path' => '/ja/17_question_to_chart.ipynb', 'heading' => '5.1.1', 'summary' => 'まとめ', 'next' => '次のレッスン'],
    '52' => ['topic' => '5.2 誤解を生まない比較', 'page' => 'レッスン5.2：誤解を生まない比較', 'lti' => 'Python Lab 5.2：誤解を生まない比較', 'quiz' => '理解度チェック：5.2 誤解を生まない比較', 'path' => '/ja/18_honest_comparisons.ipynb', 'heading' => '5.2.1', 'summary' => 'まとめ', 'next' => '次のレッスン'],
    '53' => ['topic' => '5.3 図から根拠文へ', 'page' => 'レッスン5.3：図から根拠文へ', 'lti' => 'Python Lab 5.3：図から根拠文へ', 'quiz' => '理解度チェック：5.3 図から根拠文へ', 'path' => '/ja/19_evidence_statements.ipynb', 'heading' => '5.3.1', 'summary' => 'まとめ', 'next' => '章末プロジェクト'],
] : [
    '51' => ['topic' => '5.1 From a question to a chart', 'page' => 'Lesson 5.1: From a question to a chart', 'lti' => 'Python Lab 5.1: From a question to a chart', 'quiz' => 'Knowledge check: 5.1 From a question to a chart', 'path' => '/17_question_to_chart.ipynb', 'heading' => '5.1.1', 'summary' => 'Summary', 'next' => 'Next lesson'],
    '52' => ['topic' => '5.2 Honest comparisons', 'page' => 'Lesson 5.2: Honest comparisons', 'lti' => 'Python Lab 5.2: Honest comparisons', 'quiz' => 'Knowledge check: 5.2 Honest comparisons', 'path' => '/18_honest_comparisons.ipynb', 'heading' => '5.2.1', 'summary' => 'Summary', 'next' => 'Next lesson'],
    '53' => ['topic' => '5.3 From chart to evidence statement', 'page' => 'Lesson 5.3: From chart to evidence statement', 'lti' => 'Python Lab 5.3: From chart to evidence statement', 'quiz' => 'Knowledge check: 5.3 From chart to evidence statement', 'path' => '/19_evidence_statements.ipynb', 'heading' => '5.3.1', 'summary' => 'Summary', 'next' => 'Chapter project'],
];
$project = $ja ? [
    'topic' => '5.4 応用プロジェクト：診療所の待ち時間',
    'page' => '5.4 課題仕様と完成条件',
    'lti' => 'Python Lab 5.4：診療所の待ち時間',
    'assign' => '提出課題5.4：診療所の待ち時間',
    'path' => '/ja/P5_clinic_wait_evidence.ipynb',
] : [
    'topic' => '5.4 Applied project: Clinic waiting-time evidence',
    'page' => '5.4 Project brief and completion criteria',
    'lti' => 'Python Lab 5.4: Clinic waiting-time evidence',
    'assign' => 'Assignment 5.4: Clinic waiting-time evidence',
    'path' => '/P5_clinic_wait_evidence.ipynb',
];

$modinfo = get_fast_modinfo($course);
$chapter = $modinfo->get_section_info(5, MUST_EXIST);
v44verify($chapter->name === $expectedchapter, 'Chapter 5 name mismatch');
$parentsequence = array_values(array_filter(array_map('intval', explode(',', (string)$chapter->sequence))));
$expectedsubcms = [];

foreach ($lessons as $key => $names) {
    $sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $names['topic']]);
    v44verify((bool)$sub, "$key subsection missing");
    if (!$sub) continue;
    $subcm = get_coursemodule_from_instance('subsection', $sub->id, $course->id, false, MUST_EXIST);
    $expectedsubcms[] = (int)$subcm->id;
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id], '*', MUST_EXIST);
    $sequence = array_values(array_filter(array_map('intval', explode(',', (string)$section->sequence))));
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $names['page']]);
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $names['lti']]);
    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $names['quiz']]);
    v44verify((bool)$page && (bool)$lti && (bool)$quiz, "$key visible activity missing");
    if (!$page || !$lti || !$quiz) continue;
    $pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
    $lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
    $quizcm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
    v44verify($sequence === [(int)$pagecm->id, (int)$lticm->id, (int)$quizcm->id], "$key activity order is not page, Lab, quiz");
    v44verify($page->contentformat == FORMAT_MARKDOWN, "$key page is not Markdown");
    v44verify(str_contains($page->content, $names['heading']) && str_contains($page->content, $names['summary']) && str_contains($page->content, $names['next']), "$key textbook structure missing");
    v44verify(str_contains($lti->toolurl, $names['path']), "$key LTI path incorrect");
    v44verify((int)$DB->count_records('quiz_slots', ['quizid' => $quiz->id]) === 10, "$key quiz does not have ten questions");
    v44verify((int)$quiz->attempts === 0 && (int)$quiz->grademethod === (int)QUIZ_GRADEHIGHEST,
        "$key retry policy incorrect (attempts=" . (int)$quiz->attempts . ", grademethod=" . (int)$quiz->grademethod . ", expected=" . (int)QUIZ_GRADEHIGHEST . ")");
    $gradeitem = $DB->get_record('grade_items', ['courseid' => $course->id, 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id]);
    v44verify($gradeitem && (float)$gradeitem->gradepass === 90.0, "$key pass mark is not 90");
}

$sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $project['topic']]);
v44verify((bool)$sub, '5.4 subsection missing');
if ($sub) {
    $subcm = get_coursemodule_from_instance('subsection', $sub->id, $course->id, false, MUST_EXIST);
    $expectedsubcms[] = (int)$subcm->id;
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id], '*', MUST_EXIST);
    $sequence = array_values(array_filter(array_map('intval', explode(',', (string)$section->sequence))));
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $project['page']]);
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $project['lti']]);
    $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $project['assign']]);
    v44verify((bool)$page && (bool)$lti && (bool)$assign, '5.4 visible activity missing');
    if ($page && $lti && $assign) {
        $pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
        $lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
        $assigncm = get_coursemodule_from_instance('assign', $assign->id, $course->id, false, MUST_EXIST);
        v44verify($sequence === [(int)$pagecm->id, (int)$lticm->id, (int)$assigncm->id], '5.4 activity order incorrect');
        v44verify(str_contains($page->content, 'SOURCE RECORDS: 36') && str_contains($page->content, '48.1 MINUTES') && str_contains($page->content, '32.4%'), '5.4 public checkpoints missing');
        v44verify(str_contains($lti->toolurl, $project['path']), '5.4 LTI path incorrect');
        $fileenabled = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => 'enabled']);
        $maxfiles = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => 'maxfilesubmissions']);
        $types = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => 'allowedfiletypes']);
        v44verify($fileenabled === '1' && $maxfiles === '2' && $types === '.py,.png', '5.4 submission contract incorrect');
    }
}

v44verify(array_slice($parentsequence, -4) === $expectedsubcms, 'Chapter 5 subsection order is not 5.1, 5.2, 5.3, 5.4');

if ($errors) {
    fwrite(STDERR, json_encode(['status' => 'error', 'shortname' => $shortname, 'errors' => $errors], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
    exit(1);
}
echo json_encode(['status' => 'ok', 'shortname' => $shortname, 'subsections' => $expectedsubcms, 'marker' => 'PYAI-V44-CHAPTER5-VERIFIED'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
