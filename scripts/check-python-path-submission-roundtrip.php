<?php
// Local dedicated test learner only. Never overwrite an existing submission.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/mod/assign/locallib.php';
require_once $CFG->libdir . '/filelib.php';
if ($CFG->wwwroot !== 'http://localhost:8083') { throw new RuntimeException('Local only'); }
$guided = ($argv[1] ?? '') === '--guided';
$english = in_array($argv[1] ?? '', ['--english', '--english-guided']);
$course = $DB->get_record('course', ['shortname' => $english ? (($argv[1] ?? '') === '--english-guided' ? 'PYAI-INTRO-EN-DUAL-GUIDED' : 'PYAI-INTRO-EN-DUAL-PATH') : ($guided ? 'PYAI-INTRO-JA-DUAL-GUIDED' : 'PYAI-INTRO-JA-DUAL-PATH')], '*', MUST_EXIST);
$map = $guided ? json_decode(file_get_contents($CFG->dataroot . '/temp/python-guided-full/mapping.json'), true)['map'] : [];
if ($english) {
    $keys = ['1.7'=>614,'2.4'=>631,'3.5A'=>654,'3.5B'=>658,'3.5C'=>662,'4.5'=>682,'5.4'=>699,'6.4'=>720];
    $mi = get_fast_modinfo($course);
    foreach ($mi->get_instances_of('assign') as $cm) {
        if (!$cm->visible) { continue; }
        preg_match('/^(\d+\.\d+[ABC]?)/', $mi->get_section_info($cm->sectionnum)->name, $match);
        if (!isset($keys[$match[1] ?? ''])) { throw new RuntimeException('Unexpected assignment'); }
        $map[$keys[$match[1]]] = $cm->id;
    }
    if (count($map) !== 8) { throw new RuntimeException('Expected eight assignments'); }
}
$student = $DB->get_record('user', ['username' => 'dual_3a_guided'], '*', MUST_EXIST);
$teacher = $DB->get_record('user', ['username' => 'dual_3a_teacher'], '*', MUST_EXIST);
$fs = get_file_storage();
$report = [];
foreach ([614, 631, 654, 658, 662, 682, 699, 720] as $fixtureid) {
    $id = $map[$fixtureid] ?? $fixtureid;
    \core\session\manager::set_user($student);
    $cm = get_coursemodule_from_id('assign', $id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($id);
    $assignment = new assign($context, $cm, $course);
    if (!has_capability('mod/assign:submit', $context) || !$cm->visible) {
        throw new RuntimeException('Student cannot submit: ' . $id);
    }
    $paths = glob('/tmp/python-path-submit-fixtures/' . $fixtureid . '/*');
    if (!$paths) { throw new RuntimeException('Missing fixtures'); }
    $existing = $assignment->get_user_submission($student->id, false);
    if (!$existing || $existing->status === ASSIGN_SUBMISSION_STATUS_NEW) {
        $draft = file_get_unused_draft_itemid();
        foreach ($paths as $path) {
            $fs->create_file_from_pathname(['contextid' => context_user::instance($student->id)->id,
                'component' => 'user', 'filearea' => 'draft', 'itemid' => $draft,
                'filepath' => '/', 'filename' => basename($path), 'userid' => $student->id,
                'author' => 'Local acceptance fixture'], $path);
        }
        $notices = [];
        if (!$assignment->save_submission((object)['files_filemanager' => $draft], $notices)) {
            throw new RuntimeException('Save failed: ' . implode('; ', $notices));
        }
    }
    $submission = $assignment->get_user_submission($student->id, false);
    if ($submission->status !== ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
        throw new RuntimeException('Not submitted: ' . $id);
    }
    \core\session\manager::set_user($teacher);
    if (!has_capability('mod/assign:grade', $context)) { throw new RuntimeException('Teacher cannot grade'); }
    $files = $fs->get_area_files($context->id, 'assignsubmission_file', 'submission_files', $submission->id, '', false);
    if (count($files) !== count($paths)) { throw new RuntimeException('File count mismatch'); }
    foreach ($files as $file) {
        $path = '/tmp/python-path-submit-fixtures/' . $fixtureid . '/' . $file->get_filename();
        if (!is_file($path) || hash('sha256', $file->get_content()) !== hash_file('sha256', $path)) {
            throw new RuntimeException('Existing submission differs; not overwritten: ' . $id);
        }
    }
    $report[] = ['cmid' => $id, 'submission' => $submission->id, 'status' => $submission->status,
        'files' => count($files), 'teacher_access' => true, 'hashes_match' => true];
}
echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
