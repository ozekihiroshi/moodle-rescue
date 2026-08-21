<?php
// Create two local learners for shared-PC LTI workspace-isolation testing.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/user/lib.php';
require_once $CFG->dirroot . '/lib/enrollib.php';

$password = getenv('MOODLE_ADMIN_PASSWORD');
if (!$password) {
    throw new moodle_exception('MOODLE_ADMIN_PASSWORD is required for local smoke users.');
}

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
$manual = enrol_get_plugin('manual');
$instance = null;
foreach (enrol_get_instances($course->id, true) as $candidate) {
    if ($candidate->enrol === 'manual') {
        $instance = $candidate;
        break;
    }
}
if (!$manual || !$instance) {
    throw new moodle_exception('The sample course requires an enabled manual enrolment instance.');
}

$users = [
    ['username' => 'lti_smoke_a', 'firstname' => 'LTI', 'lastname' => 'Smoke A'],
    ['username' => 'lti_smoke_b', 'firstname' => 'LTI', 'lastname' => 'Smoke B'],
];
$ids = [];

foreach ($users as $definition) {
    $user = $DB->get_record('user', ['username' => $definition['username'], 'mnethostid' => $CFG->mnet_localhost_id]);
    if (!$user) {
        $user = (object) [
            'auth' => 'manual',
            'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id,
            'username' => $definition['username'],
            'password' => $password,
            'firstname' => $definition['firstname'],
            'lastname' => $definition['lastname'],
            'email' => $definition['username'] . '@example.invalid',
        ];
        $user->id = user_create_user($user, false, false);
    } else {
        update_internal_user_password($user, $password, false);
    }
    $manual->enrol_user($instance, $user->id, $studentrole->id);
    $failurereason = null;
    if (!authenticate_user_login($definition['username'], $password, false, $failurereason)) {
        throw new moodle_exception('Local smoke user authentication failed: ' . $definition['username']);
    }
    $ids[$definition['username']] = (int) $user->id;
}

echo json_encode(['status' => 'ok', 'users' => $ids], JSON_PRETTY_PRINT) . PHP_EOL;
