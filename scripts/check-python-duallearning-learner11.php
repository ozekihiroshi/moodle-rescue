<?php
// Enrol the existing disposable pilot learner and inspect the self-paced next action.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->libdir . '/enrollib.php';

if ($CFG->wwwroot !== 'http://localhost:8083') {
    throw new RuntimeException('Local evaluation only.');
}
$course = $DB->get_record('course', ['shortname' => 'DUAL-PY-11-PATH-JA'], '*', MUST_EXIST);
$user = $DB->get_record('user', ['username' => 'dual_3a_path', 'mnethostid' => $CFG->mnet_localhost_id], '*', MUST_EXIST);
$role = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
$manual = enrol_get_plugin('manual');
$instance = null;
foreach (enrol_get_instances($course->id, false) as $candidate) {
    if ($candidate->enrol === 'manual') {
        $instance = $candidate;
        break;
    }
}
if (!$instance) {
    throw new RuntimeException('The test course has no manual enrolment method.');
}
if (!$DB->record_exists('user_enrolments', ['enrolid' => $instance->id, 'userid' => $user->id])) {
    $manual->enrol_user($instance, $user->id, $role->id);
}
\core\session\manager::set_user($user);
$overview = \format_duallearning\local\overview::build($course, $user->id);
if (empty($overview['next']) || !str_contains($overview['next']['name'], '始める前に')) {
    throw new RuntimeException('The learner is not directed to the first preparation activity.');
}
$labshortcut = array_values(array_filter($overview['shortcuts'], static fn(array $item): bool =>
    str_contains($item['name'], 'Python Lab 1.1')));
if (!str_contains($overview['progress'], '0 / 3') || count($labshortcut) !== 1) {
    throw new RuntimeException('Expected initial progress and one Lab shortcut.');
}
echo json_encode(['courseid' => (int)$course->id, 'learnerid' => (int)$user->id,
    'next' => $overview['next'], 'progress' => $overview['progress'],
    'shortcuts' => $overview['shortcuts'], 'status' => 'verified'],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
