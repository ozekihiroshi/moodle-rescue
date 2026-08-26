<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
$course = $DB->get_record('course', ['shortname' => 'PYAI-INTRO-JA'], '*', MUST_EXIST);
$assigncm = $DB->get_record('course_modules', [
    'course' => $course->id, 'idnumber' => 'pyai-project-1-weekly-support'], '*', MUST_EXIST);
$context = context_module::instance($assigncm->id);
$coursecontext = context_course::instance($course->id);
$result = [];
foreach ([3, 4, 5, 6] as $userid) {
    if (!$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0])) continue;
    $result[] = [
        'id' => $userid, 'username' => $user->username,
        'enrolled' => is_enrolled($coursecontext, $user, '', true),
        'can_submit' => has_capability('mod/assign:submit', $context, $user),
    ];
}
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
