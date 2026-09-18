<?php
// Read-only regression probe. Never modifies completion, courses, or activities.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
if ($CFG->wwwroot !== 'http://localhost:8083') {
    throw new RuntimeException('Local inspection only.');
}
$course = $DB->get_record('course', ['shortname' => 'PYAI-INTRO-JA'], '*', MUST_EXIST);
$modinfo = get_fast_modinfo($course);
$sections = $modinfo->get_section_info_all();
$delegated = [];
foreach ($sections as $section) {
    if ($section->component === 'mod_subsection') {
        $delegated[(int)$section->itemid] = (int)$section->section;
    }
}
$expected = [];
$visit = function (int $sectionnum) use (&$visit, &$expected, $modinfo, $delegated): void {
    foreach ($modinfo->sections[$sectionnum] ?? [] as $cmid) {
        $cm = $modinfo->cms[$cmid];
        if ($cm->modname === 'subsection') {
            $visit($delegated[(int)$cm->instance]);
        } else if ($cm->modname === 'page') {
            $expected[(int)$cm->id] = $cm->name;
        }
    }
};
// Chapter 1's displayed sequence, following its subsection activities in place.
$visit(1);
$actual = [];
foreach ($modinfo->get_cms() as $cm) {
    if (isset($expected[(int)$cm->id])) {
        $actual[(int)$cm->id] = $cm->name;
    }
}
// Exercise the installed plugin's selection method on a projected completion
// state: the first two lessons are done. No learner records are written.
$completed = array_slice(array_keys($expected), 0, 2);
$targets = [];
foreach ($actual as $id => $name) {
    $targets[] = ['name' => $name, 'url' => '/mod/page/view.php?id=' . $id,
        'finished' => in_array($id, $completed, true)];
}
$selector = new ReflectionMethod(\format_duallearning\local\overview::class, 'first_incomplete');
$selected = $selector->invoke(null, $targets);
$wanted = array_values($expected)[2];
$pass = $selected['name'] === $wanted;
echo json_encode([
    'plugin_version' => get_config('format_duallearning', 'version'),
    'source_course' => (int)$course->id,
    'scope' => 'Chapter 1; page activities; projected completion; no database writes',
    'displayed_order' => array_values($expected),
    'get_cms_order_used_by_collect_items' => array_values($actual),
    'expected_next_after_1_2' => $wanted,
    'selected_by_current_algorithm' => $selected['name'],
    'result' => $pass ? 'PASS' : 'FAIL',
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
exit($pass ? 0 : 1);
