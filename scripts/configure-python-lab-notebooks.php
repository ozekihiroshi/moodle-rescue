<?php
// Add one deep-linked Python Lab activity for every lesson and major project.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/lti/locallib.php';

$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = 1;

\core\session\manager::set_user(get_admin());

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$toolname = getenv('PYTHON_LAB_TOOL_NAME') ?: 'Python Lab';
$toolbase = rtrim(getenv('PYTHON_LAB_PUBLIC_URL') ?: 'http://localhost:8086', '/');

$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$type = $DB->get_record('lti_types', ['name' => $toolname, 'course' => SITEID], '*', MUST_EXIST);
if ($type->ltiversion !== LTI_VERSION_1P3 || $type->state != LTI_TOOL_STATE_CONFIGURED) {
    throw new moodle_exception('Python Lab must be configured as an active LTI 1.3 site tool first.');
}

$items = [
    [1, 'Python Lab 01: Programs, values, and output', '01_programs_values_output.ipynb', 'page', 'Lesson 1: Your first Python program'],
    [2, 'Python Lab 02: Variables, types, and calculations', '02_variables_types_calculations.ipynb', 'page', 'Lesson 2: Variables, types, input, and calculations'],
    [3, 'Python Lab 03: Conditions and boundaries', '03_conditions_boundaries.ipynb', 'page', 'Lesson 3: Decisions with conditions'],
    [4, 'Python Lab 04: Loops and accumulators', '04_loops_accumulators.ipynb', 'page', 'Lesson 4: Repetition with loops'],
    [4, 'Python Lab project: Weekly support report', 'P1_weekly_support_report.ipynb', 'assign', 'Mini-project: Weekly learning-centre support report'],
    [5, 'Python Lab 05: Lists, dictionaries, and records', '05_lists_dictionaries_records.ipynb', 'page', 'Lesson 5: Lists and dictionaries'],
    [6, 'Python Lab 06: Functions, errors, and testing', '06_functions_errors_testing.ipynb', 'page', 'Lesson 6: Functions, errors, and testing'],
    [7, 'Python Lab project: Monthly centre performance report', 'P2_monthly_centre_report.ipynb', 'assign', 'Foundation project: Monthly learning-centre performance report'],
    [8, 'Python Lab 07: Tables, CSV, and pandas', '07_tables_csv_pandas.ipynb', 'page', 'Lesson 7: Tables, CSV, and pandas'],
    [9, 'Python Lab 08: Filtering and Boolean logic', '08_filtering_boolean_logic.ipynb', 'page', 'Lesson 8: Inspecting and selecting data'],
    [10, 'Python Lab 09: Cleaning with an audit trail', '09_cleaning_audit_trail.ipynb', 'page', 'Lesson 9: Cleaning data'],
    [11, 'Python Lab 10: Grouping and statistics', '10_grouping_statistics.ipynb', 'page', 'Lesson 10: Grouping and summary statistics'],
    [12, 'Python Lab 11: Visualisation and evidence', '11_visualisation_evidence.ipynb', 'page', 'Lesson 11: Visualisation and evidence'],
    [13, 'Python Lab project: Learning-centre analysis', 'P3_learning_centres_analysis.ipynb', 'assign', 'Data analysis project: Learning centres'],
    [14, 'Python Lab project: Question to evidence', 'P4_final_question_to_evidence.ipynb', 'assign', 'Final project: From question to evidence'],
    [15, 'Python Lab 12: Scaling, chunks, and validation', '12_scaling_chunks_validation.ipynb', 'page', 'Lesson 12: Processing larger CSV files in chunks'],
    [16, 'Python Lab project: Scale-up capstone', 'P5_scaleup_capstone.ipynb', 'assign', 'Scale-up capstone: Operations evidence'],
];

function notebook_tool_url(string $base, string $filename): string {
    return $base . '/hub/user-redirect/lab/tree/' . rawurlencode($filename);
}

function activity_cm(int $courseid, string $modname, string $name): ?stdClass {
    global $DB;
    $instance = $DB->get_record($modname, ['course' => $courseid, 'name' => $name]);
    if (!$instance) {
        return null;
    }
    return get_coursemodule_from_instance($modname, $instance->id, $courseid, false, MUST_EXIST);
}

function position_before(int $courseid, int $sectionnumber, int $cmid, ?int $beforecmid): void {
    global $DB;
    $section = $DB->get_record('course_sections', [
        'course' => $courseid,
        'section' => $sectionnumber,
    ], '*', MUST_EXIST);
    $sequence = array_values(array_filter(
        array_map('intval', explode(',', (string) $section->sequence)),
        fn(int $id): bool => $id > 0 && $id !== $cmid
    ));
    $position = $beforecmid ? array_search($beforecmid, $sequence, true) : false;
    if ($position === false) {
        $sequence[] = $cmid;
    } else {
        array_splice($sequence, $position, 0, [$cmid]);
    }
    $section->sequence = implode(',', $sequence);
    $DB->update_record('course_sections', $section);
    $DB->set_field('course_modules', 'section', $section->id, ['id' => $cmid]);
}

$newfoundationname = 'Python Lab project: Monthly centre performance report';
if (!$DB->record_exists('lti', ['course' => $course->id, 'name' => $newfoundationname])) {
    if ($legacy = $DB->get_record('lti', ['course' => $course->id, 'name' => 'Python Lab project: Foundation score report'])) {
        $legacy->name = $newfoundationname;
        $DB->update_record('lti', $legacy);
    }
}
$results = [];
foreach ($items as [$section, $name, $filename, $anchormod, $anchorname]) {
    $toolurl = notebook_tool_url($toolbase, $filename);
    $activities = $DB->get_records('lti', ['course' => $course->id, 'name' => $name]);
    if (count($activities) > 1) {
        throw new moodle_exception("Multiple activities named {$name}; refusing an ambiguous update.");
    }
    if ($activities) {
        $activity = reset($activities);
        $activity->typeid = $type->id;
        $activity->toolurl = $toolurl;
        $activity->launchcontainer = LTI_LAUNCH_CONTAINER_WINDOW;
        $activity->instructorchoicesendname = LTI_SETTING_NEVER;
        $activity->instructorchoicesendemailaddr = LTI_SETTING_NEVER;
        $activity->instructorchoiceacceptgrades = LTI_SETTING_NEVER;
        $activity->grade = 0;
        $activity->timemodified = time();
        $DB->update_record('lti', $activity);
        $cm = get_coursemodule_from_instance('lti', $activity->id, $course->id, false, MUST_EXIST);
    } else {
        $created = add_moduleinfo((object) [
            'module' => $DB->get_field('modules', 'id', ['name' => 'lti'], MUST_EXIST),
            'modulename' => 'lti',
            'section' => $section,
            'name' => $name,
            'intro' => '<p>Open the notebook for this topic. Predict before running, change at least one value, save your work, and return to Moodle for the learning check.</p>'
                . '<p><strong>Notebook:</strong> <code>' . s($filename) . '</code></p>',
            'introformat' => FORMAT_HTML,
            'typeid' => $type->id,
            'toolurl' => $toolurl,
            'launchcontainer' => LTI_LAUNCH_CONTAINER_WINDOW,
            'instructorchoicesendname' => LTI_SETTING_NEVER,
            'instructorchoicesendemailaddr' => LTI_SETTING_NEVER,
            'instructorchoiceacceptgrades' => LTI_SETTING_NEVER,
            'grade' => 0,
            'visible' => 1,
            'visibleoncoursepage' => 1,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => 0,
            'showdescription' => 1,
        ], $course);
        $cm = get_coursemodule_from_id('lti', $created->coursemodule, $course->id, false, MUST_EXIST);
    }

    $anchor = activity_cm($course->id, $anchormod, $anchorname);
    if ($anchormod === 'page') {
        $quiz = activity_cm($course->id, 'quiz', 'Knowledge check: ' . $anchorname);
        if ($section === 15) {
            $quiz = activity_cm($course->id, 'quiz', 'Applied check: Scaling up safely');
        }
        $beforecmid = $quiz ? (int) $quiz->id : null;
    } else {
        $beforecmid = $anchor ? (int) $anchor->id : null;
    }
    position_before($course->id, $section, (int) $cm->id, $beforecmid);

    $results[] = [
        'cmid' => (int) $cm->id,
        'section' => $section,
        'name' => $name,
        'notebook' => $filename,
    ];
}

rebuild_course_cache($course->id, true);
echo json_encode([
    'status' => 'ok',
    'course_id' => (int) $course->id,
    'activities' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
