<?php
// Create an isolated, user-free working copy. Never mutate the published course.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->libdir . '/externallib.php';
require_once $CFG->dirroot . '/course/externallib.php';
if ($CFG->wwwroot !== 'http://localhost:8083' || get_config('format_duallearning', 'version') < 2026091803) {
    throw new RuntimeException('Local alpha5 or newer required.');
}
$shortname = 'PYAI-INTRO-JA-DUAL-PATH';
if ($DB->record_exists('course', ['shortname' => $shortname])) {
    throw new RuntimeException('Working copy exists; inspect it instead of replacing it.');
}
\core\session\manager::set_user(get_admin());
$source = $DB->get_record('course', ['shortname' => 'PYAI-INTRO-JA'], '*', MUST_EXIST);
$result = core_course_external::duplicate_course($source->id,
    '【作成中】Python入門 — 自学自習・Markdown対応版', $shortname, $source->category, 0,
    [['name' => 'users', 'value' => 0]]);
$id = (int)$result['id'];
update_course((object)['id' => $id, 'format' => 'duallearning', 'learningmode' => 'path',
    'visible' => 0, 'enablecompletion' => 1]);
$course = get_course($id);
foreach (enrol_get_instances($id, false) as $instance) {
    if ($instance->enrol !== 'manual') {
        enrol_get_plugin($instance->enrol)->update_status($instance, ENROL_INSTANCE_DISABLED);
    }
}
$dir = make_temp_directory('python-self-paced-migration');
$manifest = ['source_course' => (int)$source->id, 'courseid' => $id, 'shortname' => $shortname,
    'users_included' => false, 'sections' => [], 'activities' => []];
$mi = get_fast_modinfo($course);
foreach ($mi->get_section_info_all() as $section) {
    $manifest['sections'][] = ['id' => (int)$section->id, 'number' => (int)$section->section,
        'name' => $section->name, 'component' => $section->component, 'itemid' => $section->itemid,
        'sequence' => $section->sequence, 'summary' => $section->summary];
}
$cms = $mi->get_cms();
$mi->sort_cm_array($cms);
foreach ($cms as $cm) {
    $record = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);
    $entry = ['id' => (int)$cm->id, 'instance' => (int)$cm->instance, 'module' => $cm->modname,
        'section' => (int)$cm->sectionnum, 'name' => $cm->name, 'completion' => (int)$cm->completion];
    if ($cm->modname === 'page') {
        $entry['html'] = $record->content;
        $entry['format'] = (int)$record->contentformat;
    }
    if ($cm->modname === 'lti') {
        $entry['toolurl'] = $record->toolurl;
    }
    $manifest['activities'][] = $entry;
}
file_put_contents($dir . '/working-copy.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo json_encode(['courseid' => $id, 'shortname' => $shortname,
    'manifest' => $dir . '/working-copy.json', 'visible' => false]) . PHP_EOL;
