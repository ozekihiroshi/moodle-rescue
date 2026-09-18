<?php
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
if ($CFG->wwwroot !== 'http://localhost:8083') { throw new RuntimeException('Local only'); }
$course = $DB->get_record('course', ['shortname' => 'PYAI-INTRO-JA-DUAL-PATH'], '*', MUST_EXIST);
$rows = [];
foreach (get_fast_modinfo($course)->get_cms() as $cm) {
    if ($cm->modname !== 'assign') { continue; }
    $r = $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);
    $rows[] = ['cmid' => $cm->id, 'name' => $cm->name, 'visible' => $cm->visible,
        'visibleold' => $DB->get_field('course_modules', 'visibleold', ['id' => $cm->id]),
        'intro' => strip_tags($r->intro), 'submissiondrafts' => $r->submissiondrafts,
        'plugins' => $DB->get_records('assign_plugin_config', ['assignment' => $r->id, 'subtype' => 'assignsubmission'], '', 'id,plugin,name,value')];
}
echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
