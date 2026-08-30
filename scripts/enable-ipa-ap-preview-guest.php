<?php
// Explicitly enable password-free guest access for an IPA AP public preview course.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->libdir . '/clilib.php';

[$options, $unrecognized] = cli_get_params([
    'courseid' => 0,
    'shortname' => 'IPA-AP-WRITTEN-JA-PREVIEW',
    'help' => false,
], [
    'h' => 'help',
]);
if ($unrecognized) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognized)));
}
if ($options['help']) {
    echo "Enable password-free guest access for an IPA AP public preview course.\n\n";
    echo "Options:\n";
    echo "--courseid=INTEGER       Target an exact restored course.\n";
    echo "--shortname=SHORTNAME    Target by shortname when courseid is omitted.\n";
    echo "-h, --help               Show this help.\n";
    exit(0);
}

$courseid = (int) $options['courseid'];
$course = $courseid > 0
    ? $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST)
    : $DB->get_record('course', ['shortname' => $options['shortname']], '*', MUST_EXIST);
if (!str_contains($course->fullname, '公開体験版（問1・問2）')) {
    cli_error('The selected course is not the IPA AP public preview.');
}

$guestplugin = enrol_get_plugin('guest');
if (!$guestplugin) {
    cli_error('The Moodle guest enrolment plugin is unavailable.');
}
$guestinstance = null;
foreach (enrol_get_instances($course->id, false) as $instance) {
    if ($instance->enrol === 'guest') {
        $guestinstance = $instance;
        break;
    }
}
if ($guestinstance) {
    $guestplugin->update_status($guestinstance, ENROL_INSTANCE_ENABLED);
    $DB->set_field('enrol', 'password', '', ['id' => $guestinstance->id]);
} else {
    $guestplugin->add_instance($course, [
        'status' => ENROL_INSTANCE_ENABLED,
        'password' => '',
    ]);
}

rebuild_course_cache($course->id, true);
mtrace('Enabled password-free guest access for course ' . $course->id . ': ' . $course->fullname);
