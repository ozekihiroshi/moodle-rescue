<?php
// Read-only local working-copy activity settings, excluding credentials.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
if ($CFG->wwwroot !== 'http://localhost:8083') {
    throw new RuntimeException('Local only');
}
$shortname = match ($argv[1] ?? '') {
    '--english-guided' => 'PYAI-INTRO-EN-DUAL-GUIDED',
    '--english' => 'PYAI-INTRO-EN-DUAL-PATH',
    default => 'PYAI-INTRO-JA-DUAL-PATH',
};
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$mi = get_fast_modinfo($course);
$cms = $mi->get_cms();
$mi->sort_cm_array($cms);
$rows = [];
foreach ($cms as $cm) {
    if (!$cm->visible || !in_array($cm->modname, ['lti', 'quiz', 'assign'])) {
        continue;
    }
    $r = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);
    $row = ['cmid' => $cm->id, 'name' => $cm->name, 'module' => $cm->modname,
        'completion' => $cm->completion, 'completiongradeitemnumber' => $cm->completiongradeitemnumber];
    if ($cm->modname === 'lti') {
        $row['toolurl'] = $r->toolurl;
    } else if ($cm->modname === 'quiz') {
        foreach (['attempts', 'grademethod', 'grade', 'completionpass', 'completionminattempts'] as $field) {
            $row[$field] = $r->$field ?? null;
        }
        $row['gradepass'] = $DB->get_field('grade_items', 'gradepass',
            ['courseid' => $course->id, 'itemmodule' => 'quiz', 'iteminstance' => $r->id, 'itemnumber' => 0]);
    } else {
        $row['completionsubmit'] = $r->completionsubmit;
    }
    $rows[] = $row;
}
echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
