<?php
// Reconnect every existing Python Lab activity in one restored course.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/mod/lti/locallib.php';

\core\session\manager::set_user(get_admin());

$shortname = trim((string) getenv('PYTHON_COURSE_SHORTNAME'));
$toolname = trim((string) (getenv('PYTHON_LAB_TOOL_NAME') ?: 'Python Lab'));
$toolbase = rtrim(trim((string) getenv('PYTHON_LAB_PUBLIC_URL')), '/');

if ($shortname === '') {
    throw new coding_exception('PYTHON_COURSE_SHORTNAME is required.');
}
if (!str_starts_with($toolbase, 'https://')) {
    throw new coding_exception('PYTHON_LAB_PUBLIC_URL must be an HTTPS URL.');
}

$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$type = $DB->get_record('lti_types', ['name' => $toolname, 'course' => SITEID], '*', MUST_EXIST);
if ($type->ltiversion !== LTI_VERSION_1P3 || (int) $type->state !== LTI_TOOL_STATE_CONFIGURED) {
    throw new coding_exception('Python Lab must be an active LTI 1.3 site tool first.');
}

$updated = [];
foreach ($DB->get_records('lti', ['course' => $course->id], 'id') as $activity) {
    $oldurl = trim((string) $activity->toolurl);
    $path = (string) parse_url($oldurl, PHP_URL_PATH);
    $islabname = str_starts_with((string) $activity->name, 'Python Lab');
    $islaburl = str_starts_with($path, '/hub/');
    if (!$islabname && !$islaburl) {
        continue;
    }
    if (!$islaburl) {
        throw new coding_exception(
            'Python Lab activity has an unexpected tool URL: ' . $activity->name
        );
    }

    $newurl = $toolbase . $path;
    $query = parse_url($oldurl, PHP_URL_QUERY);
    if (is_string($query) && $query !== '') {
        $newurl .= '?' . $query;
    }

    $activity->typeid = $type->id;
    $activity->toolurl = $newurl;
    $activity->launchcontainer = LTI_LAUNCH_CONTAINER_WINDOW;
    $activity->instructorchoicesendname = LTI_SETTING_NEVER;
    $activity->instructorchoicesendemailaddr = LTI_SETTING_NEVER;
    $activity->instructorchoiceacceptgrades = LTI_SETTING_NEVER;
    $activity->grade = 0;
    $activity->timemodified = time();
    $DB->update_record('lti', $activity);

    $updated[] = [
        'id' => (int) $activity->id,
        'name' => (string) $activity->name,
        'toolurl' => $newurl,
    ];
}

if (!$updated) {
    throw new coding_exception('No Python Lab activities were found in the course.');
}

rebuild_course_cache($course->id, true);

echo json_encode([
    'status' => 'ok',
    'course_id' => (int) $course->id,
    'course_shortname' => $course->shortname,
    'tool_type_id' => (int) $type->id,
    'activity_count' => count($updated),
    'activities' => $updated,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
