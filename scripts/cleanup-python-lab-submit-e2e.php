<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/assign/locallib.php';

\core\session\manager::set_user(get_admin());
$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO-JA';
$userid = (int)(getenv('PYTHON_LAB_TEST_USERID') ?: 3);
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$cm = $DB->get_record('course_modules', [
    'course' => $course->id,
    'idnumber' => 'pyai-project-1-weekly-support',
], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
$assign = new assign($context, $cm, $course);
$removed = $assign->remove_submission($userid);

echo json_encode([
    'course' => $shortname,
    'userid' => $userid,
    'submission_content_removed' => $removed,
], JSON_UNESCAPED_SLASHES), PHP_EOL;
