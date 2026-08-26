<?php
// Repair stale legacy section references for Python Lab LTI activities.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ltimoduleid = $DB->get_field('modules', 'id', ['name' => 'lti'], MUST_EXIST);
$sections = $DB->get_records('course_sections', ['course' => $course->id]);
$repairs = [];

foreach ($DB->get_records('course_modules', ['course' => $course->id, 'module' => $ltimoduleid]) as $cm) {
    $containing = [];
    foreach ($sections as $section) {
        $sequence = array_values(array_filter(array_map('intval', explode(',', (string) $section->sequence))));
        if (in_array((int) $cm->id, $sequence, true)) {
            $containing[] = $section;
        }
    }
    $delegated = array_values(array_filter($containing, fn(stdClass $section): bool => $section->component === 'mod_subsection'));
    if (count($delegated) > 1) {
        throw new moodle_exception("LTI course module {$cm->id} appears in multiple delegated subsections.");
    }
    if (!$delegated) {
        continue;
    }
    $target = reset($delegated);
    foreach ($containing as $section) {
        if ((int) $section->id === (int) $target->id) {
            continue;
        }
        $sequence = array_values(array_filter(
            array_map('intval', explode(',', (string) $section->sequence)),
            fn(int $id): bool => $id > 0 && $id !== (int) $cm->id
        ));
        $section->sequence = implode(',', $sequence);
        $DB->update_record('course_sections', $section);
        $sections[$section->id] = $section;
    }
    if ((int) $cm->section !== (int) $target->id || count($containing) > 1) {
        $DB->set_field('course_modules', 'section', $target->id, ['id' => $cm->id]);
        $repairs[] = [
            'cmid' => (int) $cm->id,
            'from_section' => (int) $cm->section,
            'to_section' => (int) $target->id,
            'stale_sequences_removed' => max(0, count($containing) - 1),
        ];
    }
}

rebuild_course_cache($course->id, true);
echo json_encode([
    'repaired' => true,
    'courseid' => (int) $course->id,
    'shortname' => $shortname,
    'lti_repairs' => $repairs,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
