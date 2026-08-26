<?php
// This file is part of Moodle Rescue.
//
// Moodle Rescue is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Create a course backup suitable for public distribution.
 *
 * Unlike Moodle's standard admin/cli/backup.php defaults, this command
 * explicitly excludes users and all user-dependent data.
 *
 * Run this file inside the Moodle container after copying it below the
 * Moodle root, for example:
 *
 * php /var/www/html/admin/cli/backup-course-for-distribution.php \
 *   --courseid=12 --destination=/tmp/release
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

[$options, $unrecognized] = cli_get_params([
    'courseid' => false,
    'destination' => '',
    'help' => false,
], [
    'h' => 'help',
]);

if ($unrecognized) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognized)));
}

if ($options['help'] || !$options['courseid']) {
    echo "Create a user-free Moodle course backup for public distribution.\n\n";
    echo "Options:\n";
    echo "--courseid=INTEGER       Course ID to back up.\n";
    echo "--destination=PATH       Writable destination directory.\n";
    echo "-h, --help               Show this help.\n";
    exit($options['help'] ? 0 : 1);
}

$destination = rtrim($options['destination'], '/');
if ($destination === '' || !is_dir($destination) || !is_writable($destination)) {
    cli_error('Destination directory does not exist or is not writable.');
}

$course = $DB->get_record('course', ['id' => (int)$options['courseid']], '*', MUST_EXIST);
$admin = get_admin();
if (!$admin) {
    cli_error('No administrator account was found.');
}

cli_heading('Creating user-free distribution backup');
$controller = new backup_controller(
    backup::TYPE_1COURSE,
    $course->id,
    backup::FORMAT_MOODLE,
    backup::INTERACTIVE_YES,
    backup::MODE_GENERAL,
    $admin->id
);

$plan = $controller->get_plan();
$plan->get_setting('users')->set_value(0);

// These settings are user-dependent. Moodle normally disables them when
// users is false; setting each available item explicitly documents and
// enforces the distribution policy across supported Moodle versions.
foreach (['role_assignments', 'comments', 'badges', 'userscompletion', 'logs', 'grade_histories'] as $name) {
    if ($plan->setting_exists($name)) {
        $setting = $plan->get_setting($name);
        if ($setting->get_status() === base_setting::NOT_LOCKED) {
            $setting->set_value(0);
        }
    }
}

$filename = backup_plan_dbops::get_default_backup_filename(
    $controller->get_format(),
    $controller->get_type(),
    $controller->get_id(),
    0,
    0
);
$plan->get_setting('filename')->set_value($filename);

$controller->finish_ui();
$controller->execute_plan();
$results = $controller->get_results();
$file = $results['backup_destination'] ?? null;

if (!$file || !$file->copy_content_to($destination . '/' . $filename)) {
    $controller->destroy();
    cli_error('Could not copy the completed backup to the destination.');
}

$file->delete();
$controller->destroy();
mtrace('Wrote ' . $destination . '/' . $filename);
mtrace('Backup completed with users excluded.');

