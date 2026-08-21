<?php
// Verify the Python Lab LTI 1.3 registration and course activity.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/lti/locallib.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$toolname = getenv('PYTHON_LAB_TOOL_NAME') ?: 'Python Lab';
$activityname = getenv('PYTHON_LAB_ACTIVITY_NAME') ?: 'Python Lab — Run and save your code';
$toolbase = rtrim(getenv('PYTHON_LAB_PUBLIC_URL') ?: 'http://localhost:8086', '/');
$activitytoolurl = $toolbase . '/hub/user-redirect/lab/tree/00_start_here.ipynb';

$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$type = $DB->get_record('lti_types', ['name' => $toolname, 'course' => SITEID], '*', MUST_EXIST);
$activity = $DB->get_record('lti', ['course' => $course->id, 'name' => $activityname], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('lti', $activity->id, $course->id, false, MUST_EXIST);
$config = lti_get_type_config($type->id);

$expected = [
    'version' => LTI_VERSION_1P3,
    'toolurl' => $toolbase . '/hub/',
    'initiatelogin' => $toolbase . '/hub/lti13/oauth_login',
    'redirectionuris' => $toolbase . '/hub/lti13/oauth_callback',
];

if ($type->ltiversion !== $expected['version'] || $type->state != LTI_TOOL_STATE_CONFIGURED) {
    throw new moodle_exception('Python Lab tool is not an active LTI 1.3 registration.');
}
if (empty($type->clientid)) {
    throw new moodle_exception('Python Lab tool has no client ID.');
}
if (($config['toolurl'] ?? '') !== $expected['toolurl']
        || ($config['initiatelogin'] ?? '') !== $expected['initiatelogin']
        || ($config['redirectionuris'] ?? '') !== $expected['redirectionuris']) {
    throw new moodle_exception('Python Lab endpoint configuration does not match the local deployment.');
}
if ((int) $activity->typeid !== (int) $type->id || !$cm->visible || $activity->grade != 0) {
    throw new moodle_exception('Python Lab course activity is not configured as expected.');
}
if ($activity->toolurl !== $activitytoolurl) {
    throw new moodle_exception('Python Lab course activity does not open the start notebook.');
}

echo json_encode([
    'status' => 'ok',
    'client_id' => $type->clientid,
    'course_module_id' => (int) $cm->id,
    'launch_url' => $CFG->wwwroot . '/mod/lti/view.php?id=' . $cm->id,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
