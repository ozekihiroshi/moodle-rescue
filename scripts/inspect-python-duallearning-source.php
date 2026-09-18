<?php
// Read-only inspection of the published Python lessons for a Dual Learning pilot.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';

if ($CFG->wwwroot !== 'http://localhost:8083') {
    throw new RuntimeException('This inspection is restricted to the local development site.');
}

$result = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $entry = ['courseid' => (int)$course->id, 'shortname' => $shortname, 'activities' => []];
    foreach (get_fast_modinfo($course)->get_cms() as $cm) {
        if (!preg_match('/(?:1[.．]1|1[－-]1|first Python program|初めてのPython|プログラム・値・式)/iu', $cm->name)) {
            continue;
        }
        $activity = [
            'cmid' => (int)$cm->id,
            'section' => (int)$cm->sectionnum,
            'modname' => $cm->modname,
            'name' => $cm->name,
        ];
        if ($cm->modname === 'lessonmark') {
            $record = $DB->get_record('lessonmark', ['id' => $cm->instance], '*', MUST_EXIST);
            $activity['markdownsource'] = $record->markdownsource;
        } else if ($cm->modname === 'page') {
            $record = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
            $activity['content'] = $record->content;
        } else if ($cm->modname === 'lti') {
            $record = $DB->get_record('lti', ['id' => $cm->instance], '*', MUST_EXIST);
            $activity['toolurl'] = $record->toolurl;
            $activity['typeid'] = (int)$record->typeid;
        }
        $entry['activities'][] = $activity;
    }
    $result[] = $entry;
}
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
