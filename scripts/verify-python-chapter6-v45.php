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

$expectedchapter = $ja ? '第6章 — 信頼できる分析を大規模データへ拡張する' : 'Chapter 6 — Scaling Reliable Analysis';
$lessons = $ja ? [
    '61'=>['topic'=>'6.1 読み込む前に調べる','page'=>'レッスン6.1：読み込む前に調べる','lti'=>'Python Lab 6.1：読み込む前に調べる','quiz'=>'理解度チェック：6.1 読み込む前に調べる','path'=>'/ja/20_inspect_before_loading.ipynb','heading'=>'6.1.1','summary'=>'まとめ','next'=>'次のレッスン'],
    '62'=>['topic'=>'6.2 チャンクを越えて正しく集計する','page'=>'レッスン6.2：チャンクを越えて正しく集計する','lti'=>'Python Lab 6.2：チャンクを越えた集計','quiz'=>'理解度チェック：6.2 チャンクを越えて正しく集計する','path'=>'/ja/21_chunked_aggregation.ipynb','heading'=>'6.2.1','summary'=>'まとめ','next'=>'次のレッスン'],
    '63'=>['topic'=>'6.3 照合して再現可能にする','page'=>'レッスン6.3：照合して再現可能にする','lti'=>'Python Lab 6.3：照合と再現','quiz'=>'理解度チェック：6.3 照合して再現可能にする','path'=>'/ja/22_reconcile_reproduce.ipynb','heading'=>'6.3.1','summary'=>'まとめ','next'=>'章末プロジェクト'],
] : [
    '61'=>['topic'=>'6.1 Inspect before loading','page'=>'Lesson 6.1: Inspect before loading','lti'=>'Python Lab 6.1: Inspect before loading','quiz'=>'Knowledge check: 6.1 Inspect before loading','path'=>'/20_inspect_before_loading.ipynb','heading'=>'6.1.1','summary'=>'Summary','next'=>'Next lesson'],
    '62'=>['topic'=>'6.2 Aggregate correctly across chunks','page'=>'Lesson 6.2: Aggregate correctly across chunks','lti'=>'Python Lab 6.2: Chunked aggregation','quiz'=>'Knowledge check: 6.2 Aggregate correctly across chunks','path'=>'/21_chunked_aggregation.ipynb','heading'=>'6.2.1','summary'=>'Summary','next'=>'Next lesson'],
    '63'=>['topic'=>'6.3 Reconcile and reproduce','page'=>'Lesson 6.3: Reconcile and reproduce','lti'=>'Python Lab 6.3: Reconcile and reproduce','quiz'=>'Knowledge check: 6.3 Reconcile and reproduce','path'=>'/22_reconcile_reproduce.ipynb','heading'=>'6.3.1','summary'=>'Summary','next'=>'Chapter project'],
];
$project = $ja ? [
    'topic'=>'6.4 総合プロジェクト：診療所医薬品在庫切れ対応','page'=>'6.4 課題仕様と完成条件','lti'=>'Python Lab 6.4：診療所医薬品在庫切れ対応','assign'=>'提出課題6.4：診療所医薬品在庫切れ対応','path'=>'/ja/P6_clinic_stock_scaleup.ipynb',
] : [
    'topic'=>'6.4 Capstone project: Clinic medicine stock-out response','page'=>'6.4 Project brief and completion criteria','lti'=>'Python Lab 6.4: Clinic medicine stock-out response','assign'=>'Assignment 6.4: Clinic medicine stock-out response','path'=>'/P6_clinic_stock_scaleup.ipynb',
];
$modinfo = get_fast_modinfo($course);
$chapter = $modinfo->get_section_info(6, MUST_EXIST);
v44verify($chapter->name === $expectedchapter, 'Chapter 6 name mismatch');
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
v44verify((bool)$sub, '6.4 subsection missing');
if ($sub) {
    $subcm = get_coursemodule_from_instance('subsection', $sub->id, $course->id, false, MUST_EXIST);
    $expectedsubcms[] = (int)$subcm->id;
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id], '*', MUST_EXIST);
    $sequence = array_values(array_filter(array_map('intval', explode(',', (string)$section->sequence))));
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $project['page']]);
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $project['lti']]);
    $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $project['assign']]);
    v44verify((bool)$page && (bool)$lti && (bool)$assign, '6.4 visible activity missing');
    if ($page && $lti && $assign) {
        $pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
        $lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
        $assigncm = get_coursemodule_from_instance('assign', $assign->id, $course->id, false, MUST_EXIST);
        v44verify($sequence === [(int)$pagecm->id, (int)$lticm->id, (int)$assigncm->id], '6.4 activity order incorrect');
        v44verify(str_contains($page->content, 'SOURCE RECORDS: 120000') && str_contains($page->content, 'ANALYSIS RECORDS: 119977') && str_contains($page->content, 'PATIENTS TURNED AWAY: 367492'), '6.4 public checkpoints missing');
        v44verify(str_contains($lti->toolurl, $project['path']), '6.4 LTI path incorrect');
        $fileenabled = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => 'enabled']);
        $maxfiles = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => 'maxfilesubmissions']);
        $types = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => 'allowedfiletypes']);
        v44verify($fileenabled === '1' && $maxfiles === '3' && $types === '.py,.csv,.png', '6.4 submission contract incorrect');
    }
}

v44verify(array_slice($parentsequence, -4) === $expectedsubcms, 'Chapter 6 subsection order is not 6.1, 6.2, 6.3, 6.4');

if ($errors) {
    fwrite(STDERR, json_encode(['status' => 'error', 'shortname' => $shortname, 'errors' => $errors], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
    exit(1);
}
echo json_encode(['status' => 'ok', 'shortname' => $shortname, 'subsections' => $expectedsubcms, 'marker' => 'PYAI-V45-CHAPTER6-VERIFIED'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
