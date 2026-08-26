<?php
// Add or remove one temporary manual enrolment for local submission E2E testing.
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO-JA';
$userid = (int)(getenv('PYTHON_LAB_TEST_USERID') ?: 2);
$action = getenv('PYTHON_LAB_TEST_ACTION') ?: 'inspect';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);
$manual = enrol_get_plugin('manual');
$instance = null;
foreach (enrol_get_instances($course->id, true) as $candidate) {
    if ($candidate->enrol === 'manual') {
        $instance = $candidate;
        break;
    }
}
if (!$manual || !$instance) {
    throw new RuntimeException('Enabled manual enrolment instance is required');
}

$before = is_enrolled($coursecontext, $user, '', true);
if ($action === 'enrol') {
    if ($before) {
        throw new RuntimeException('Refusing to replace a pre-existing enrolment');
    }
    $role = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
    $manual->enrol_user($instance, $userid, $role->id);
} elseif ($action === 'unenrol') {
    $manual->unenrol_user($instance, $userid);
} elseif ($action !== 'inspect') {
    throw new InvalidArgumentException('Unknown action');
}

echo json_encode([
    'action' => $action,
    'course' => $shortname,
    'userid' => $userid,
    'enrolled_before' => $before,
    'enrolled_after' => is_enrolled($coursecontext, $user, '', true),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
