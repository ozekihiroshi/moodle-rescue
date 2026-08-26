<?php
// Make the Project 1.7 assignment a file-only submission target.
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';

\core\session\manager::set_user(get_admin());

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);

$sql = "SELECT a.*
          FROM {assign} a
          JOIN {course_modules} cm ON cm.instance = a.id
          JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
         WHERE a.course = :courseid
           AND cm.idnumber = :idnumber";
$assign = $DB->get_record_sql($sql, [
    'courseid' => $course->id,
    'idnumber' => 'pyai-project-1-weekly-support',
], MUST_EXIST);

$assign->submissiondrafts = 0;
$assign->timemodified = time();
$DB->update_record('assign', $assign);

function v32_config(int $assignment, string $plugin, string $name, string $value): void {
    global $DB;
    $where = [
        'assignment' => $assignment,
        'plugin' => $plugin,
        'subtype' => 'assignsubmission',
        'name' => $name,
    ];
    if ($record = $DB->get_record('assign_plugin_config', $where)) {
        $record->value = $value;
        $DB->update_record('assign_plugin_config', $record);
        return;
    }
    $DB->insert_record('assign_plugin_config', (object)($where + ['value' => $value]));
}

v32_config((int)$assign->id, 'file', 'enabled', '1');
v32_config((int)$assign->id, 'file', 'maxfilesubmissions', '1');
v32_config((int)$assign->id, 'file', 'filetypeslist', '.py');
v32_config((int)$assign->id, 'onlinetext', 'enabled', '0');

rebuild_course_cache($course->id, true);

echo json_encode([
    'shortname' => $shortname,
    'assignment_id' => (int)$assign->id,
    'submissiondrafts' => (int)$assign->submissiondrafts,
    'file_enabled' => 1,
    'onlinetext_enabled' => 0,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
