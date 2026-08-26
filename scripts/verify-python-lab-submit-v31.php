<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$result = ['plugin' => false, 'nonce_table' => false, 'courses' => []];
$result['plugin'] = (int)get_config('local_pythonlabsubmit', 'version') === 2026082400;
$manager = $DB->get_manager();
$result['nonce_table'] = $manager->table_exists(new xmldb_table('local_pythonlabsubmit_nonce'));
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $page = $DB->get_record_select('page', 'course = :course AND content LIKE :marker', [
        'course' => $course->id, 'marker' => '%PYAI-V31-DIRECT-SUBMIT%'], '*', MUST_EXIST);
    $sql = "SELECT cm.id, a.id AS assignid, a.submissiondrafts
              FROM {course_modules} cm
              JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
              JOIN {assign} a ON a.id = cm.instance
             WHERE cm.course = :course AND cm.idnumber = :idnumber";
    $assignment = $DB->get_record_sql($sql, [
        'course' => $course->id, 'idnumber' => 'pyai-project-1-weekly-support'], MUST_EXIST);
    $filetypes = $DB->get_field('assign_plugin_config', 'value', [
        'assignment' => $assignment->assignid, 'plugin' => 'file',
        'subtype' => 'assignsubmission', 'name' => 'filetypeslist']);
    $result['courses'][] = [
        'shortname' => $shortname, 'assignment_cmid' => (int)$assignment->id,
        'immediate_submission' => (int)$assignment->submissiondrafts === 0,
        'filetypes' => $filetypes, 'pageid' => (int)$page->id,
    ];
}
$result['verified'] = $result['plugin'] && $result['nonce_table'] &&
    count($result['courses']) === 2 &&
    !array_filter($result['courses'], fn($item) => !$item['immediate_submission'] || $item['filetypes'] !== '.py');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
exit($result['verified'] ? 0 : 1);
