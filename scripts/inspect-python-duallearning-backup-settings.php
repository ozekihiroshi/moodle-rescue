<?php
// Read-only backup-plan inspection for the isolated trial course.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/backup/util/includes/backup_includes.php';
$course = $DB->get_record('course', ['shortname' => 'DUAL-PY-11-PATH-JA'], '*', MUST_EXIST);
$controller = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE,
    backup::INTERACTIVE_YES, backup::MODE_GENERAL, get_admin()->id);
foreach ($controller->get_plan()->get_settings() as $setting) {
    if (str_contains($setting->get_name(), '558') || str_contains($setting->get_name(), 'lti')) {
        echo $setting->get_name() . ' = ' . $setting->get_value() . PHP_EOL;
    }
}
$controller->destroy();
