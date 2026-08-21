<?php
// Verify deep-linked Python Lab activities and their section ordering.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$toolbase = rtrim(getenv('PYTHON_LAB_PUBLIC_URL') ?: 'http://localhost:8086', '/');
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);

$expected = [
    1 => ['Python Lab 01: Programs, values, and output', '01_programs_values_output.ipynb'],
    2 => ['Python Lab 02: Variables, types, and calculations', '02_variables_types_calculations.ipynb'],
    3 => ['Python Lab 03: Conditions and boundaries', '03_conditions_boundaries.ipynb'],
    4 => ['Python Lab 04: Loops and accumulators', '04_loops_accumulators.ipynb'],
    5 => ['Python Lab project: Weekly support report', 'P1_weekly_support_report.ipynb'],
    6 => ['Python Lab 05: Lists, dictionaries, and records', '05_lists_dictionaries_records.ipynb'],
    7 => ['Python Lab 06: Functions, errors, and testing', '06_functions_errors_testing.ipynb'],
    8 => ['Python Lab project: Monthly centre performance report', 'P2_monthly_centre_report.ipynb'],
    9 => ['Python Lab 07: Tables, CSV, and pandas', '07_tables_csv_pandas.ipynb'],
    10 => ['Python Lab 08: Filtering and Boolean logic', '08_filtering_boolean_logic.ipynb'],
    11 => ['Python Lab 09: Cleaning with an audit trail', '09_cleaning_audit_trail.ipynb'],
    12 => ['Python Lab 10: Grouping and statistics', '10_grouping_statistics.ipynb'],
    13 => ['Python Lab 11: Visualisation and evidence', '11_visualisation_evidence.ipynb'],
    14 => ['Python Lab project: Learning-centre analysis', 'P3_learning_centres_analysis.ipynb'],
    15 => ['Python Lab project: Question to evidence', 'P4_final_question_to_evidence.ipynb'],
    16 => ['Python Lab 12: Scaling, chunks, and validation', '12_scaling_chunks_validation.ipynb'],
    17 => ['Python Lab project: Scale-up capstone', 'P5_scaleup_capstone.ipynb'],
];

$seen = [];
foreach ($expected as [$name, $filename]) {
    $activity = $DB->get_record('lti', ['course' => $course->id, 'name' => $name], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('lti', $activity->id, $course->id, false, MUST_EXIST);
    $want = $toolbase . '/hub/user-redirect/lab/tree/' . rawurlencode($filename);
    if ($activity->toolurl !== $want || !$cm->visible || $activity->grade != 0) {
        throw new RuntimeException("Invalid notebook activity: {$name}");
    }
    $section = $DB->get_record('course_sections', ['id' => $cm->section], '*', MUST_EXIST);
    if (!in_array((int) $cm->id, array_map('intval', explode(',', $section->sequence)), true)) {
        throw new RuntimeException("Notebook activity missing from section sequence: {$name}");
    }
    $seen[] = ['cmid' => (int) $cm->id, 'section' => (int) $section->section, 'notebook' => $filename];
}

echo json_encode([
    'status' => 'ok',
    'course_id' => (int) $course->id,
    'activity_count' => count($seen),
    'activities' => $seen,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
