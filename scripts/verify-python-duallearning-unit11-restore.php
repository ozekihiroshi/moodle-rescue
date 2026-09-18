<?php
// Restore the distribution artifact into a NEW hidden local course and inspect it.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/backup/util/includes/restore_includes.php';
require_once $CFG->dirroot . '/course/lib.php';

if ($CFG->wwwroot !== 'http://localhost:8083') {
    throw new RuntimeException('Local restore verification only.');
}
$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = 1;
$archive = $argv[1] ?? '';
if (!is_file($archive) || !is_readable($archive)) {
    throw new RuntimeException('Readable backup path required.');
}
$shortname = $argv[2] ?? 'DUAL-PY-11-RESTORE-JA';
if (!preg_match('/^DUAL-PY-11-RESTORE-JA(?:-[A-Z0-9]+)?$/', $shortname)) {
    throw new RuntimeException('Invalid test-course shortname.');
}
if ($DB->record_exists('course', ['shortname' => $shortname])) {
    throw new RuntimeException('Restore target already exists; do not overwrite it.');
}
$admin = get_admin();
\core\session\manager::set_user($admin);
$tempname = restore_controller::get_tempdir_name(36, $admin->id);
$temppath = make_backup_temp_directory($tempname);
if (!get_file_packer('application/vnd.moodle.backup')->extract_to_pathname($archive, $temppath)) {
    throw new RuntimeException('Backup extraction failed.');
}
$category = $DB->get_record('course_categories', ['idnumber' => 'dual-learning-pilot'], '*', MUST_EXIST);
$targetid = restore_dbops::create_new_course('【復元検証】Python 1.1', $shortname, $category->id);
$controller = new restore_controller($tempname, $targetid, backup::INTERACTIVE_NO,
    backup::MODE_GENERAL, $admin->id, backup::TARGET_NEW_COURSE);
if (!$controller->execute_precheck()) {
    throw new RuntimeException('Restore precheck failed: ' . json_encode($controller->get_precheck_results()));
}
$controller->execute_plan();
$controller->destroy();
update_course((object)['id' => $targetid, 'visible' => 0, 'shortname' => $shortname]);

$course = get_course($targetid);
if ($course->format !== 'duallearning' ||
        (course_get_format($course)->get_format_options()['learningmode'] ?? null) !== 'path') {
    throw new RuntimeException('Restored format or learning mode differs.');
}
$sections = $DB->get_records('course_sections', ['course' => $targetid], 'section');
$unit = array_values(array_filter($sections, static fn($s) => (int)$s->section === 1));
if (count($unit) !== 1) {
    throw new RuntimeException('Expected one restored unit.');
}
$modinfo = get_fast_modinfo($course);
$files = ['01-prepare.md', '02-lesson.md', '03-review.md'];
$found = [];
foreach (explode(',', $unit[0]->sequence) as $cmid) {
    if ($cmid === '') {
        continue;
    }
    $cm = $modinfo->get_cm((int)$cmid);
    if ($cm->modname !== 'lessonmark' || count($found) >= count($files)) {
        throw new RuntimeException('Unexpected restored activity.');
    }
    $lesson = $DB->get_record('lessonmark', ['id' => $cm->instance], '*', MUST_EXIST);
    $expected = file_get_contents('/srv/python-duallearning-1-1/' . $files[count($found)]);
    if ($lesson->markdownsource !== $expected ||
            (int)$cm->completion !== COMPLETION_TRACKING_MANUAL) {
        throw new RuntimeException('Restored Markdown or completion differs.');
    }
    $found[] = $cm->name;
}
if (count($found) !== 3 || $course->visible) {
    throw new RuntimeException('Restored course is incomplete or visible.');
}
echo json_encode(['status' => 'restored_hidden_verified', 'courseid' => $targetid,
    'mode' => 'path', 'activities' => $found, 'lti' => 0],
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
