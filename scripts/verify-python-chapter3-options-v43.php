<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';
$errors = [];

function v43check(bool $condition, string $message): void {
    global $errors;
    if (!$condition) {
        $errors[] = $message;
    }
}

$names = $ja ? [
    'guide' => '第3章中間実践：三つから一つを選ぶ',
    'a' => '3.5A 中間実践課題：学校給食の追加配送',
    'b' => '3.5B 中間実践課題：公共バスの改善調査',
    'c' => '3.5C 中間実践課題：地域給水設備の点検',
    'bpage' => '3.5B 課題仕様と完成条件',
    'cpage' => '3.5C 課題仕様と完成条件',
    'blti' => 'Python Lab 3.5B：公共バスの改善調査',
    'clti' => 'Python Lab 3.5C：地域給水設備の点検',
    'bassign' => '3.5B提出：公共バスの改善調査',
    'cassign' => '3.5C提出：地域給水設備の点検',
] : [
    'guide' => 'Chapter 3 midterm: Choose one practical project',
    'a' => '3.5A Midterm practical project: School meal delivery',
    'b' => '3.5B Midterm practical project: Public bus service review',
    'c' => '3.5C Midterm practical project: Community water-point inspection',
    'bpage' => '3.5B Project brief and completion criteria',
    'cpage' => '3.5C Project brief and completion criteria',
    'blti' => 'Python Lab 3.5B: Public bus service review',
    'clti' => 'Python Lab 3.5C: Community water-point inspection',
    'bassign' => 'Submit 3.5B: Public bus service review',
    'cassign' => 'Submit 3.5C: Community water-point inspection',
];

$modinfo = get_fast_modinfo($course);
$parent = $modinfo->get_section_info(3, MUST_EXIST);
$parentsequence = array_values(array_filter(array_map('intval', explode(',', (string)$parent->sequence))));
$expectedtail = [];

$guide = $DB->get_record('page', ['course' => $course->id, 'name' => $names['guide']]);
v43check((bool)$guide, 'choice guide page missing');
if ($guide) {
    $cm = get_coursemodule_from_instance('page', $guide->id, $course->id, false, MUST_EXIST);
    $expectedtail[] = (int)$cm->id;
    v43check((bool)$cm->visible, 'choice guide is hidden');
    v43check(str_contains($guide->content, '3.5A') && str_contains($guide->content, '3.5B') && str_contains($guide->content, '3.5C'), 'choice guide does not list A/B/C');
}

$subsections = [];
foreach (['a', 'b', 'c'] as $key) {
    $sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $names[$key]]);
    v43check((bool)$sub, "{$key} subsection missing");
    if ($sub) {
        $cm = get_coursemodule_from_instance('subsection', $sub->id, $course->id, false, MUST_EXIST);
        $expectedtail[] = (int)$cm->id;
        $subsections[$key] = $sub;
        v43check((bool)$cm->visible, "{$key} subsection hidden");
    }
}

foreach (['b', 'c'] as $key) {
    if (!isset($subsections[$key])) {
        continue;
    }
    $delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsections[$key]->id], '*', MUST_EXIST);
    $sequence = array_values(array_filter(array_map('intval', explode(',', (string)$delegated->sequence))));
    v43check(count($sequence) === 3, "{$key} must contain exactly page, LTI, assignment");

    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $names[$key . 'page']]);
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $names[$key . 'lti']]);
    $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $names[$key . 'assign']]);
    v43check((bool)$page && (bool)$lti && (bool)$assign, "{$key} activity missing");
    if (!$page || !$lti || !$assign) {
        continue;
    }
    $pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
    $lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
    $assigncm = get_coursemodule_from_instance('assign', $assign->id, $course->id, false, MUST_EXIST);
    v43check($sequence === [(int)$pagecm->id, (int)$lticm->id, (int)$assigncm->id], "{$key} activity order incorrect");
    v43check(str_contains($page->content, 'Stage 1') || str_contains($page->content, '第1段階'), "{$key} Stage 1 missing from brief");
    v43check(str_contains($page->content, 'Stage 2') || str_contains($page->content, '第2段階'), "{$key} Stage 2 missing from brief");
    $expectedpath = $ja ? '/ja/P3' . strtoupper($key) . '_' : '/P3' . strtoupper($key) . '_';
    v43check(str_contains($lti->toolurl, $expectedpath), "{$key} notebook path incorrect: {$lti->toolurl}");
    $fileenabled = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => 'enabled']);
    $maxfiles = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => 'maxfilesubmissions']);
    $types = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => 'allowedfiletypes']);
    $online = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'plugin' => 'onlinetext', 'subtype' => 'assignsubmission', 'name' => 'enabled']);
    v43check($fileenabled === '1' && $maxfiles === '2' && $types === '.py' && $online === '0', "{$key} submission contract incorrect");
}

if (count($expectedtail) === 4) {
    v43check(array_slice($parentsequence, -4) === $expectedtail, 'Chapter 3 tail is not guide, A, B, C');
}

if ($errors) {
    fwrite(STDERR, json_encode(['status' => 'error', 'shortname' => $shortname, 'errors' => $errors], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
    exit(1);
}

echo json_encode(['status' => 'ok', 'shortname' => $shortname, 'tail' => $expectedtail, 'marker' => 'PYAI-V43-CHAPTER3-OPTIONS-VERIFIED'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
