<?php
// Configure Python Lab as an LTI 1.3 site tool and add it to the sample course.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/lti/locallib.php';

$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = 1;

\core\session\manager::set_user(get_admin());

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$toolname = getenv('PYTHON_LAB_TOOL_NAME') ?: 'Python Lab';
$activityname = getenv('PYTHON_LAB_ACTIVITY_NAME') ?: 'Python Lab — Run and save your code';
$toolbase = rtrim(getenv('PYTHON_LAB_PUBLIC_URL') ?: 'http://localhost:8086', '/');
$toolurl = $toolbase . '/hub/';
$activitytoolurl = $toolbase . '/hub/user-redirect/lab/tree/00_start_here.ipynb';
$loginurl = $toolbase . '/hub/lti13/oauth_login';
$callbackurl = $toolbase . '/hub/lti13/oauth_callback';

$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$sitecourse = get_site();

$types = $DB->get_records('lti_types', ['name' => $toolname, 'course' => $sitecourse->id]);
if (count($types) > 1) {
    throw new moodle_exception('Multiple Python Lab LTI tool types exist; refusing an ambiguous update.');
}

$type = $types ? reset($types) : (object) [];
$type->name = $toolname;
$type->state = LTI_TOOL_STATE_CONFIGURED;
$type->course = $sitecourse->id;
$type->coursevisible = LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
$type->ltiversion = LTI_VERSION_1P3;

$config = (object) [
    'lti_typename' => $toolname,
    'lti_toolurl' => $toolurl,
    'lti_description' => 'Server-saved JupyterLab workspace for the Python for Data sample course.',
    'lti_ltiversion' => LTI_VERSION_1P3,
    'lti_clientid' => $type->clientid ?? null,
    'lti_keytype' => LTI_JWK_KEYSET,
    'lti_publickeyset' => '',
    'lti_initiatelogin' => $loginurl,
    'lti_redirectionuris' => $callbackurl,
    'lti_customparameters' => '',
    'lti_coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
    'lti_launchcontainer' => LTI_LAUNCH_CONTAINER_WINDOW,
    'lti_contentitem' => 0,
    'lti_sendname' => LTI_SETTING_NEVER,
    'lti_sendemailaddr' => LTI_SETTING_NEVER,
    'lti_acceptgrades' => LTI_SETTING_NEVER,
    'lti_forcessl' => 0,
];

if (!empty($type->id)) {
    lti_update_type($type, $config, false);
    $typeid = (int) $type->id;
} else {
    $typeid = (int) lti_add_type($type, $config);
}

$type = $DB->get_record('lti_types', ['id' => $typeid], '*', MUST_EXIST);

$existingactivities = $DB->get_records('lti', ['course' => $course->id, 'name' => $activityname]);
if (count($existingactivities) > 1) {
    throw new moodle_exception('Multiple Python Lab activities exist; refusing an ambiguous update.');
}

if ($existingactivities) {
    $activity = reset($existingactivities);
    $activity->typeid = $typeid;
    $activity->toolurl = $activitytoolurl;
    $activity->launchcontainer = LTI_LAUNCH_CONTAINER_WINDOW;
    $activity->instructorchoicesendname = LTI_SETTING_NEVER;
    $activity->instructorchoicesendemailaddr = LTI_SETTING_NEVER;
    $activity->instructorchoiceacceptgrades = LTI_SETTING_NEVER;
    $activity->grade = 0;
    $activity->timemodified = time();
    $DB->update_record('lti', $activity);
    $cm = get_coursemodule_from_instance('lti', $activity->id, $course->id, false, MUST_EXIST);
    $cmid = (int) $cm->id;
} else {
    $moduleinfo = (object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'lti'], MUST_EXIST),
        'modulename' => 'lti',
        'section' => 0,
        'name' => $activityname,
        'intro' => '<p>Open your server-saved Python workspace. Use the same Moodle account each time.</p>',
        'introformat' => FORMAT_HTML,
        'typeid' => $typeid,
        'toolurl' => $activitytoolurl,
        'launchcontainer' => LTI_LAUNCH_CONTAINER_WINDOW,
        'instructorchoicesendname' => LTI_SETTING_NEVER,
        'instructorchoicesendemailaddr' => LTI_SETTING_NEVER,
        'instructorchoiceacceptgrades' => LTI_SETTING_NEVER,
        'grade' => 0,
        'visible' => 1,
        'visibleoncoursepage' => 1,
        'groupmode' => 0,
        'groupingid' => 0,
        'completion' => 0,
        'showdescription' => 1,
    ];
    $created = add_moduleinfo($moduleinfo, $course);
    $cmid = (int) $created->coursemodule;
}

rebuild_course_cache($course->id, true);

echo json_encode([
    'tool_type_id' => $typeid,
    'client_id' => $type->clientid,
    'course_id' => (int) $course->id,
    'course_module_id' => $cmid,
    'issuer' => $CFG->wwwroot,
    'authorize_url' => $CFG->wwwroot . '/mod/lti/auth.php',
    'jwks_url' => $CFG->wwwroot . '/mod/lti/certs.php',
    'login_url' => $loginurl,
    'callback_url' => $callbackurl,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
