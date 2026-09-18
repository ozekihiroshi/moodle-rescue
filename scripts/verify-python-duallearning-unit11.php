<?php
// Verify the isolated Python 1.1 course without changing Moodle records.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';
require_once $CFG->dirroot . '/mod/lti/locallib.php';

if ($CFG->wwwroot !== 'http://localhost:8083') {
    throw new RuntimeException('Local verification only.');
}
$course = $DB->get_record('course', ['shortname' => 'DUAL-PY-11-PATH-JA'], '*', MUST_EXIST);
if ($course->format !== 'duallearning' || !$course->enablecompletion || !$course->visible
        || (course_get_format($course)->get_format_options()['learningmode'] ?? null) !== 'path') {
    throw new RuntimeException('Course format, mode, visibility or completion is incorrect.');
}
$sections = $DB->get_records('course_sections', ['course' => $course->id], 'section');
$unit = array_values(array_filter($sections, static fn(stdClass $s): bool => (int)$s->section === 1));
if (count($unit) !== 1 || !$unit[0]->visible || !str_contains($unit[0]->summary, '自分で進める順番')) {
    throw new RuntimeException('The authored unit is missing or hidden.');
}
$expected = [
    ['lessonmark', '01-prepare.md'],
    ['lessonmark', '02-lesson.md'],
    ['lti', null],
    ['lessonmark', '03-review.md'],
];
$modinfo = get_fast_modinfo($course);
$found = [];
$rendered = [];
$labcmid = null;
$renderer = new \mod_lessonmark\local\moodle_markdown_renderer();
foreach ($unit[0]->sequence ? explode(',', $unit[0]->sequence) : [] as $cmid) {
    $cm = $modinfo->get_cm((int)$cmid);
    $found[] = $cm->modname;
    $position = count($found) - 1;
    if (!isset($expected[$position]) || $cm->modname !== $expected[$position][0]) {
        throw new RuntimeException('Unexpected activity order or module at position ' . $position);
    }
    if ($cm->modname === 'lessonmark') {
        $source = file_get_contents('/srv/python-duallearning-1-1/' . $expected[$position][1]);
        $record = $DB->get_record('lessonmark', ['id' => $cm->instance], '*', MUST_EXIST);
        if ($source !== $record->markdownsource || (int)$cm->completion !== COMPLETION_TRACKING_MANUAL) {
            throw new RuntimeException('Markdown source or completion setting differs: ' . $cm->name);
        }
        $document = $renderer->render($source, \context_module::instance($cm->id));
        $html = $document->get_content_html();
        if (str_contains($html, '[!ANSWER]') || !str_contains($html, '<h2')) {
            throw new RuntimeException('LessonMark did not render the Markdown as expected: ' . $cm->name);
        }
        if (str_contains($source, '[!ANSWER]') && !str_contains($html, 'mod_lessonmark-selfcheck__answer')) {
            throw new RuntimeException('The model-answer disclosure was not rendered.');
        }
        $rendered[] = ['cmid' => (int)$cm->id, 'name' => $cm->name, 'html_bytes' => strlen($html),
            'answer_disclosure' => str_contains($html, 'mod_lessonmark-selfcheck__answer')];
    } else {
        $labcmid = (int)$cm->id;
        $lti = $DB->get_record('lti', ['id' => $cm->instance], '*', MUST_EXIST);
        if (!str_ends_with($lti->toolurl, '/ja/01_programs_values_output.ipynb')
                || (int)$cm->completion !== COMPLETION_TRACKING_NONE) {
            throw new RuntimeException('The existing Japanese 1.1 Lab launch was not reused.');
        }
    }
}
if (count($found) !== count($expected)) {
    throw new RuntimeException('The unit activity count is incorrect.');
}
$enrolments = array_column($DB->get_records('enrol', ['courseid' => $course->id]), 'status', 'enrol');
if (isset($enrolments['guest']) && (int)$enrolments['guest'] === ENROL_INSTANCE_ENABLED) {
    throw new RuntimeException('Guest enrolment must remain disabled in this trial.');
}
echo json_encode(['courseid' => (int)$course->id, 'sectionid' => (int)$unit[0]->id,
    'mode' => 'path', 'activities' => $rendered, 'lab_cmid' => $labcmid,
    'status' => 'verified'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
