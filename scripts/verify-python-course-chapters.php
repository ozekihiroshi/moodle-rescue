<?php
// Verify the chapter/subsection hierarchy without relying on site-specific IDs.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$modinfo = get_fast_modinfo($course);

$expected = [
    'Chapter 1 — Python Programming Foundations' => [
        '1.1 Programs, values, and output' => 3,
        '1.2 Variables, types, input, and calculations' => 3,
        '1.3 Decisions with conditions' => 3,
        '1.4 Repetition with loops' => 3,
        '1.5 Applied project: Weekly support report' => 3,
    ],
    'Chapter 2 — Data Structures and Reliable Programs' => [
        '2.1 Lists, dictionaries, and records' => 3,
        '2.2 Functions, errors, and testing' => 3,
        '2.3 Applied project: Monthly centre performance report' => 2,
    ],
    'Chapter 3 — Analysing Tabular Data' => [
        '3.1 Tables, CSV, and pandas' => 4,
        '3.2 Filtering and Boolean logic' => 3,
        '3.3 Cleaning data with an audit trail' => 4,
        '3.4 Grouping and summary statistics' => 3,
    ],
    'Chapter 4 — Communicating Evidence' => [
        '4.1 Visualisation and evidence' => 3,
        '4.2 Guided project: Learning-centre analysis' => 3,
        '4.3 Final project: From question to evidence' => 5,
    ],
    'Chapter 5 — Scaling Up' => [
        '5.1 Processing larger CSV files safely' => 3,
        '5.2 Scale-up capstone project' => 5,
    ],
];

$topsections = [];
foreach ($modinfo->get_section_info_all() as $section) {
    if ($section && $section->section > 0 && empty($section->component)) {
        $topsections[$section->name] = $section;
    }
}
if (array_keys($topsections) !== array_keys($expected)) {
    throw new moodle_exception('Top-level chapter names or order do not match the expected hierarchy.');
}

$activityids = [];
$subsectionreport = [];
foreach ($expected as $chaptername => $topics) {
    $parent = $topsections[$chaptername];
    $parentcmids = $modinfo->sections[$parent->section] ?? [];
    $actualtopicnames = [];
    foreach ($parentcmids as $cmid) {
        $cm = $modinfo->get_cm($cmid);
        if ($cm->modname !== 'subsection') {
            throw new moodle_exception("Unexpected activity '{$cm->name}' at chapter level '{$chaptername}'.");
        }
        $actualtopicnames[] = $cm->name;
    }
    if ($actualtopicnames !== array_keys($topics)) {
        throw new moodle_exception("Subsection names or order do not match in '{$chaptername}'.");
    }

    foreach ($topics as $topicname => $expectedcount) {
        $records = $DB->get_records('subsection', ['course' => $course->id, 'name' => $topicname]);
        if (count($records) !== 1) {
            throw new moodle_exception("Expected one subsection named '{$topicname}'.");
        }
        $instance = reset($records);
        $subsectioncm = get_coursemodule_from_instance('subsection', $instance->id, $course->id, false, MUST_EXIST);
        if ((int) $subsectioncm->section !== (int) $parent->id) {
            throw new moodle_exception("Subsection '{$topicname}' is not inside '{$chaptername}'.");
        }
        $delegated = $DB->get_record('course_sections', [
            'course' => $course->id,
            'component' => 'mod_subsection',
            'itemid' => $instance->id,
        ], '*', MUST_EXIST);
        $cmids = $modinfo->sections[$delegated->section] ?? [];
        if (count($cmids) !== $expectedcount) {
            throw new moodle_exception("Subsection '{$topicname}' has " . count($cmids) . " activities; expected {$expectedcount}.");
        }
        foreach ($cmids as $cmid) {
            if (isset($activityids[$cmid])) {
                throw new moodle_exception("Activity {$cmid} appears in multiple subsections.");
            }
            $activityids[$cmid] = $modinfo->get_cm($cmid)->name;
        }
        $subsectionreport[] = [
            'name' => $topicname,
            'cmid' => (int) $subsectioncm->id,
            'activity_count' => count($cmids),
        ];
    }
}

$counts = ['page' => 0, 'quiz' => 0, 'assign' => 0, 'lti' => 0];
foreach ($activityids as $cmid => $name) {
    $modname = $modinfo->get_cm($cmid)->modname;
    if (isset($counts[$modname])) {
        $counts[$modname]++;
    }
}
$expectedcounts = ['page' => 22, 'quiz' => 12, 'assign' => 5, 'lti' => 17];
if ($counts !== $expectedcounts) {
    throw new moodle_exception('Grouped activity counts do not match: ' . json_encode($counts));
}

echo json_encode([
    'status' => 'ok',
    'course_id' => (int) $course->id,
    'chapter_count' => count($topsections),
    'subsection_count' => count($subsectionreport),
    'grouped_activity_count' => count($activityids),
    'activity_counts' => $counts,
    'activity_ids' => array_map('intval', array_keys($activityids)),
    'subsections' => $subsectionreport,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
