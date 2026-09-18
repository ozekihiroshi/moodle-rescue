<?php
// Simulate progression, roll it back, and never create a submission.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->libdir . '/completionlib.php';
if ($CFG->wwwroot !== 'http://localhost:8083') {
    throw new RuntimeException('Local only');
}
$shortname = ($argv[1] ?? '') === '--guided' ? 'PYAI-INTRO-JA-DUAL-GUIDED' : 'PYAI-INTRO-JA-DUAL-PATH';
$english = in_array($argv[1] ?? '', ['--english', '--english-guided']);
if ($english) { $shortname = ($argv[1] ?? '') === '--english-guided' ? 'PYAI-INTRO-EN-DUAL-GUIDED' : 'PYAI-INTRO-EN-DUAL-PATH'; }
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$student = $DB->get_record('user', ['username' => 'dual_3a_guided'], '*', MUST_EXIST);
\core\session\manager::set_user($student);
$mi = get_fast_modinfo($course, $student->id);
$choice = $DB->get_record('course_modules', ['course' => $course->id,
    'idnumber' => $english ? 'python-en-path-page-1057' : 'python-path-page-650'], '*', MUST_EXIST);
if ((int)$choice->completion !== COMPLETION_TRACKING_MANUAL) {
    throw new RuntimeException('Shared confirmation must be manual');
}
$options = [];
foreach ($mi->get_cms() as $cm) {
    $section = $mi->get_section_info($cm->sectionnum);
    if ($cm->uservisible && preg_match('/^3\.5[ABC]/', $section->name ?? '')) {
        if ($cm->completion != COMPLETION_TRACKING_NONE) {
            throw new RuntimeException('Optional work still tracked: ' . $cm->id);
        }
        if ($cm->modname === 'assign') {
            $options[] = $cm->id;
        }
    }
}
if (count($options) !== 3) {
    throw new RuntimeException('Expected three visible submission activities');
}
$transaction = $DB->start_delegated_transaction();
try {
    foreach ($mi->get_cms() as $cm) {
        if (!$cm->uservisible || !$cm->completion) {
            continue;
        }
        $row = $DB->get_record('course_modules_completion', ['coursemoduleid' => $cm->id, 'userid' => $student->id]);
        $data = (object)['coursemoduleid' => $cm->id, 'userid' => $student->id,
            'completionstate' => $cm->id == $choice->id ? COMPLETION_INCOMPLETE : COMPLETION_COMPLETE_PASS,
            'viewed' => 1, 'timemodified' => time()];
        if ($row) {
            $data->id = $row->id;
            $DB->update_record('course_modules_completion', $data);
        } else {
            $DB->insert_record('course_modules_completion', $data);
        }
    }
    \cache::make('core', 'completion')->purge();
    $before = \format_duallearning\local\overview::build($course, $student->id)['next'];
    $expected = (new moodle_url('/mod/lessonmark/view.php', ['id' => $choice->id]))->out(false);
    if ($before['url'] !== $expected) {
        throw new RuntimeException('Shared confirmation is not next');
    }
    $DB->set_field('course_modules_completion', 'completionstate', COMPLETION_COMPLETE,
        ['coursemoduleid' => $choice->id, 'userid' => $student->id]);
    \cache::make('core', 'completion')->purge();
    $overview = \format_duallearning\local\overview::build($course, $student->id);
    $after = $overview['next'];
    if ($after !== null || !$overview['finished']) {
        throw new RuntimeException('Optional work still blocks completion');
    }
    $continuation = null;
    foreach ($mi->get_cms() as $cm) {
        if ($cm->uservisible && $cm->idnumber === ($english ? 'python-en-path-page-1071' : 'python-path-page-664')) {
            $DB->set_field('course_modules_completion', 'completionstate', COMPLETION_INCOMPLETE,
                ['coursemoduleid' => $cm->id, 'userid' => $student->id]);
            \cache::make('core', 'completion')->purge();
            $continuation = \format_duallearning\local\overview::build($course, $student->id)['next'];
            if ($continuation['url'] !== $cm->url->out(false)) {
                throw new RuntimeException('Chapter 3 does not continue to chapter 4');
            }
        }
    }
    $transaction->rollback(new RuntimeException('ROLLBACK_TEST'));
} catch (RuntimeException $e) {
    if ($e->getMessage() !== 'ROLLBACK_TEST') {
        throw $e;
    }
} finally {
    \cache::make('core', 'completion')->purge();
}
echo json_encode(['status' => 'PASS', 'shared_confirmation' => $choice->id,
    'submission_activities_preserved' => $options, 'before' => $before, 'after' => $after,
    'continuation' => $continuation, 'test_state_rolled_back' => true], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
