<?php
// Allow existing local-only reviewer accounts to inspect the isolated unit.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->libdir . '/enrollib.php';

if ($CFG->wwwroot !== 'http://localhost:8083') {
    throw new RuntimeException('Local evaluation only.');
}
$course = $DB->get_record('course', ['shortname' => 'DUAL-PY-11-PATH-JA'], '*', MUST_EXIST);
$manual = enrol_get_plugin('manual');
$instance = null;
foreach (enrol_get_instances($course->id, false) as $candidate) {
    if ($candidate->enrol === 'manual') {
        $instance = $candidate;
        break;
    }
}
if (!$instance) {
    throw new RuntimeException('Manual enrolment is not configured.');
}
$result = [];
foreach (['dual_3a_path' => 'student', 'dual_3a_teacher' => 'editingteacher'] as $username => $rolename) {
    $user = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id], '*', MUST_EXIST);
    $role = $DB->get_record('role', ['shortname' => $rolename], '*', MUST_EXIST);
    if (!$DB->record_exists('user_enrolments', ['enrolid' => $instance->id, 'userid' => $user->id])) {
        $manual->enrol_user($instance, $user->id, $role->id);
    }
    $result[$username] = (int)$user->id;
}
echo json_encode(['courseid' => (int)$course->id, 'reviewers' => $result],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
