<?php
// This file is part of the local Moodle Rescue integration environment.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->libdir . '/resourcelib.php';

$shortname = getenv('S3_TEST_COURSE_SHORTNAME') ?: 'S3INT-CLI';
$fullname = getenv('S3_TEST_COURSE_FULLNAME') ?: 'Secure S3 Integration Test (CLI)';
$marker = getenv('S3_TEST_CONTENT_MARKER') ?: 'secure-s3-integration-marker-v1';

if ($DB->record_exists('course', ['shortname' => $shortname])) {
    throw new moodle_exception('shortnametaken', 'error', '', $shortname);
}

\core\session\manager::set_user(get_admin());

$coursedata = (object) [
    'fullname' => $fullname,
    'shortname' => $shortname,
    'category' => 1,
    'format' => 'topics',
    'visible' => 1,
    'summary' => '<p>CLI-created course for Secure S3 backup and restore testing.</p>',
    'summaryformat' => FORMAT_HTML,
    'startdate' => usergetmidnight(time()),
];

$course = create_course($coursedata);
course_create_sections_if_missing($course, 1);

$moduleinfo = (object) [
    'module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST),
    'modulename' => 'page',
    'section' => 1,
    'name' => 'Secure S3 verification page',
    'intro' => '<p>This page must survive backup, transfer, download, and restore.</p>',
    'introformat' => FORMAT_HTML,
    'content' => '<h2>Secure S3 integration verification</h2><p>' . s($marker) . '</p>',
    'contentformat' => FORMAT_HTML,
    'display' => RESOURCELIB_DISPLAY_OPEN,
    'printintro' => 1,
    'printlastmodified' => 1,
    'visible' => 1,
    'visibleoncoursepage' => 1,
    'groupmode' => 0,
    'groupingid' => 0,
    'completion' => 0,
    'showdescription' => 1,
];

$moduleinfo = add_moduleinfo($moduleinfo, $course);

echo json_encode([
    'courseid' => $course->id,
    'shortname' => $course->shortname,
    'fullname' => $course->fullname,
    'cmid' => $moduleinfo->coursemodule,
    'marker' => $marker,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
