<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$user = $DB->get_record('user', ['id' => 2], '*', MUST_EXIST);
$result = ['userid' => 2, 'username' => $user->username, 'courses' => []];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $lti = $DB->get_record_select('lti', 'course = :course AND name LIKE :name', [
        'course' => $course->id, 'name' => '%1.7%'], '*', MUST_EXIST);
    $lticm = get_coursemodule_from_instance('lti', $lti->id, $course->id, false, MUST_EXIST);
    $assigncm = $DB->get_record('course_modules', [
        'course' => $course->id, 'idnumber' => 'pyai-project-1-weekly-support'], '*', MUST_EXIST);
    $context = context_module::instance($assigncm->id);
    $result['courses'][] = [
        'shortname' => $shortname,
        'lti_cmid' => (int)$lticm->id,
        'enrolled' => is_enrolled(context_course::instance($course->id), $user, '', true),
        'can_submit' => has_capability('mod/assign:submit', $context, $user),
    ];
}
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
