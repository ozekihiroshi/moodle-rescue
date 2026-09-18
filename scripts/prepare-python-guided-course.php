<?php
// Isolated, user-free full-course copy; never overwrite either existing variant.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->libdir . '/externallib.php';
require_once $CFG->dirroot . '/course/externallib.php';
require_once $CFG->libdir . '/enrollib.php';
if ($CFG->wwwroot !== 'http://localhost:8083') { throw new RuntimeException('Local only'); }
\core\session\manager::set_user(get_admin());
$shortname = 'PYAI-INTRO-JA-DUAL-GUIDED';
if ($DB->record_exists('course', ['shortname' => $shortname])) { throw new RuntimeException('Already exists; do not overwrite'); }
$source = $DB->get_record('course', ['shortname' => 'PYAI-INTRO-JA-DUAL-PATH'], '*', MUST_EXIST);
$result = core_course_external::duplicate_course($source->id,
    '【作成中】Python入門 — 教師伴走・Markdown対応版', $shortname, $source->category, 0,
    [['name' => 'users', 'value' => 0]]);
$course = get_course($result['id']);
update_course((object)['id' => $course->id, 'learningmode' => 'guided', 'visible' => 0,
    'showgrades' => 1, 'enddate' => 0]);
$map = []; $targetkeys = [];
$key = fn($cm) => $cm->sectionnum . '|' . $cm->modname . '|' . $cm->name;
foreach (get_fast_modinfo($course)->get_cms() as $cm) {
    if (isset($targetkeys[$key($cm)])) { throw new RuntimeException('Ambiguous restored activity'); }
    $targetkeys[$key($cm)] = $cm->id;
}
foreach (get_fast_modinfo($source)->get_cms() as $cm) {
    if (!isset($targetkeys[$key($cm)])) { throw new RuntimeException('Missing restored activity: ' . $cm->name); }
    $map[$cm->id] = $targetkeys[$key($cm)];
}
// Markdown links are not necessarily rewritten by Moodle's HTML restore decoder.
$rewrite = function($text) use ($map, $source, $course) {
    $text = preg_replace_callback('~(/mod/[a-z]+/(?:view|launch)\.php\?id=)(\d+)~',
        fn($m) => $m[1] . ($map[(int)$m[2]] ?? $m[2]), $text);
    return preg_replace('~(/course/view\.php\?id=)' . $source->id . '(?!\d)~', '${1}' . $course->id, $text);
};
foreach (get_fast_modinfo($course)->get_cms() as $cm) {
    if (!in_array($cm->modname, ['lessonmark', 'page', 'assign', 'lti', 'forum', 'quiz'])) { continue; }
    $record = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);
    foreach (['markdownsource', 'content', 'intro'] as $field) {
        if (isset($record->$field)) { $record->$field = $rewrite($record->$field); }
    }
    $DB->update_record($cm->modname, $record);
}
foreach ($DB->get_records('course_sections', ['course' => $course->id]) as $section) {
    $DB->set_field('course_sections', 'summary', $rewrite($section->summary), ['id' => $section->id]);
}
$manual = enrol_get_plugin('manual'); $manualinstance = null;
foreach (enrol_get_instances($course->id, false) as $instance) {
    if ($instance->enrol === 'manual') { $manualinstance = $instance; }
    else { enrol_get_plugin($instance->enrol)->update_status($instance, ENROL_INSTANCE_DISABLED); }
}
if (!$manualinstance) { $manualinstance = $DB->get_record('enrol', ['id' => $manual->add_instance($course)], '*', MUST_EXIST); }
foreach (['dual_3a_guided' => 'student', 'dual_3a_teacher' => 'editingteacher'] as $username => $rolename) {
    $user = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id], '*', MUST_EXIST);
    $manual->enrol_user($manualinstance, $user->id, $DB->get_field('role', 'id', ['shortname' => $rolename], MUST_EXIST));
}
rebuild_course_cache($course->id, true);
$report = ['courseid' => (int)$course->id, 'sourcecourse' => (int)$source->id, 'map' => $map];
$path = make_temp_directory('python-guided-full') . '/mapping.json';
file_put_contents($path, json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo json_encode(['courseid' => $course->id, 'mapping' => $path, 'users_copied' => false]) . PHP_EOL;
