<?php
// Group the flat Python sample course into chapters and native Moodle subsections.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';

use core_courseformat\formatactions;

$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = 1;

\core\session\manager::set_user(get_admin());

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);

$chapters = [
    [
        'name' => 'Chapter 1 — Python Programming Foundations',
        'legacysection' => '1. Programs, values, and output',
        'summary' => '<p>Write small Python programs using values, variables, decisions, and repetition, then combine them in a practical weekly report.</p>',
        'topics' => [
            [
                'name' => '1.1 Programs, values, and output',
                'summary' => '<p>Run a first program and explain how Python represents and displays values.</p>',
                'activities' => [
                    ['page', 'Lesson 1: Your first Python program'],
                    ['lti', 'Python Lab 01: Programs, values, and output'],
                    ['quiz', 'Knowledge check: Lesson 1: Your first Python program'],
                ],
            ],
            [
                'name' => '1.2 Variables, types, input, and calculations',
                'summary' => '<p>Store information, perform calculations, and accept simple input.</p>',
                'activities' => [
                    ['page', 'Lesson 2: Variables, types, input, and calculations'],
                    ['lti', 'Python Lab 02: Variables, types, and calculations'],
                    ['quiz', 'Knowledge check: Lesson 2: Variables, types, input, and calculations'],
                ],
            ],
            [
                'name' => '1.3 Decisions with conditions',
                'summary' => '<p>Use Boolean expressions and boundary checks to make reliable decisions.</p>',
                'activities' => [
                    ['page', 'Lesson 3: Decisions with conditions'],
                    ['lti', 'Python Lab 03: Conditions and boundaries'],
                    ['quiz', 'Knowledge check: Lesson 3: Decisions with conditions'],
                ],
            ],
            [
                'name' => '1.4 Repetition with loops',
                'summary' => '<p>Repeat work safely and accumulate useful totals.</p>',
                'activities' => [
                    ['page', 'Lesson 4: Repetition with loops'],
                    ['lti', 'Python Lab 04: Loops and accumulators'],
                    ['quiz', 'Knowledge check: Lesson 4: Repetition with loops'],
                ],
            ],
            [
                'name' => '1.5 Applied project: Weekly support report',
                'summary' => '<p>Combine variables, conditions, and loops in a small learning-centre report.</p>',
                'activities' => [
                    ['lti', 'Python Lab project: Weekly support report'],
                    ['assign', 'Mini-project: Weekly learning-centre support report'],
                    ['page', 'Teacher model answer: Weekly learning-centre support report (hidden)'],
                ],
            ],
        ],
    ],
    [
        'name' => 'Chapter 2 — Data Structures and Reliable Programs',
        'legacysection' => '2. Variables, types, input, and calculations',
        'summary' => '<p>Organise records with Python data structures and build reusable, testable functions.</p>',
        'topics' => [
            [
                'name' => '2.1 Lists, dictionaries, and records',
                'summary' => '<p>Represent collections and structured learning-centre records.</p>',
                'activities' => [
                    ['page', 'Lesson 5: Lists and dictionaries'],
                    ['lti', 'Python Lab 05: Lists, dictionaries, and records'],
                    ['quiz', 'Knowledge check: Lesson 5: Lists and dictionaries'],
                ],
            ],
            [
                'name' => '2.2 Functions, errors, and testing',
                'summary' => '<p>Break a program into functions, read errors, and check behaviour.</p>',
                'activities' => [
                    ['page', 'Lesson 6: Functions, errors, and testing'],
                    ['lti', 'Python Lab 06: Functions, errors, and testing'],
                    ['quiz', 'Knowledge check: Lesson 6: Functions, errors, and testing'],
                ],
            ],
            [
                'name' => '2.3 Applied project: Monthly centre performance report',
                'summary' => '<p>Turn a collection of centre records into a checked monthly report.</p>',
                'activities' => [
                    ['lti', 'Python Lab project: Monthly centre performance report'],
                    ['assign', 'Foundation project: Monthly learning-centre performance report'],
                ],
            ],
        ],
    ],
    [
        'name' => 'Chapter 3 — Analysing Tabular Data',
        'legacysection' => '3. Decisions with conditions',
        'summary' => '<p>Read, select, clean, group, and summarise tabular data while keeping an audit trail.</p>',
        'topics' => [
            [
                'name' => '3.1 Tables, CSV, and pandas',
                'summary' => '<p>Move from Python records to CSV files and pandas DataFrames.</p>',
                'activities' => [
                    ['page', 'Lesson 7: Tables, CSV, and pandas'],
                    ['lti', 'Python Lab 07: Tables, CSV, and pandas'],
                    ['quiz', 'Knowledge check: Lesson 7: Tables, CSV, and pandas'],
                    ['page', 'Dataset pack: From 24 rows to 250,000 fictional records'],
                ],
            ],
            [
                'name' => '3.2 Filtering and Boolean logic',
                'summary' => '<p>Select the records that answer a question using sets and Boolean logic.</p>',
                'activities' => [
                    ['page', 'Lesson 8: Inspecting and selecting data'],
                    ['lti', 'Python Lab 08: Filtering and Boolean logic'],
                    ['quiz', 'Knowledge check: Lesson 8: Inspecting and selecting data'],
                ],
            ],
            [
                'name' => '3.3 Cleaning data with an audit trail',
                'summary' => '<p>Detect missing or invalid data and document each cleaning decision.</p>',
                'activities' => [
                    ['page', 'Lesson 9: Cleaning data'],
                    ['lti', 'Python Lab 09: Cleaning with an audit trail'],
                    ['quiz', 'Knowledge check: Lesson 9: Cleaning data'],
                    ['page', 'Analysis toolkit: Boolean logic, sets, and basic statistics'],
                ],
            ],
            [
                'name' => '3.4 Grouping and summary statistics',
                'summary' => '<p>Compare groups with totals, averages, medians, and other useful summaries.</p>',
                'activities' => [
                    ['page', 'Lesson 10: Grouping and summary statistics'],
                    ['lti', 'Python Lab 10: Grouping and statistics'],
                    ['quiz', 'Knowledge check: Lesson 10: Grouping and summary statistics'],
                ],
            ],
        ],
    ],
    [
        'name' => 'Chapter 4 — Communicating Evidence',
        'legacysection' => '4. Repetition with loops',
        'summary' => '<p>Create appropriate visual evidence, explain findings, and complete guided and independent analyses.</p>',
        'topics' => [
            [
                'name' => '4.1 Visualisation and evidence',
                'summary' => '<p>Choose a chart that supports the question and explain what the evidence shows.</p>',
                'activities' => [
                    ['page', 'Lesson 11: Visualisation and evidence'],
                    ['lti', 'Python Lab 11: Visualisation and evidence'],
                    ['quiz', 'Knowledge check: Lesson 11: Visualisation and evidence'],
                ],
            ],
            [
                'name' => '4.2 Guided project: Learning-centre analysis',
                'summary' => '<p>Complete a guided analysis from a fictional CSV dataset to an evidence-based conclusion.</p>',
                'activities' => [
                    ['page', 'Dataset: Learning centres (fictional CSV)'],
                    ['lti', 'Python Lab project: Learning-centre analysis'],
                    ['assign', 'Data analysis project: Learning centres'],
                ],
            ],
            [
                'name' => '4.3 Final project: From question to evidence',
                'summary' => '<p>Define a useful question, analyse the data, communicate the result, and reflect on the process.</p>',
                'activities' => [
                    ['lti', 'Python Lab project: Question to evidence'],
                    ['assign', 'Final project: From question to evidence'],
                    ['page', 'Reflection and next steps'],
                    ['page', 'Model answers and grading notes (hidden from students)'],
                    ['page', 'Teacher answers: Connected transfer challenges (hidden)'],
                ],
            ],
        ],
    ],
    [
        'name' => 'Chapter 5 — Scaling Up',
        'legacysection' => '5. Lists and dictionaries',
        'summary' => '<p>Apply the same careful workflow to larger files using chunking, validation, and data provenance.</p>',
        'topics' => [
            [
                'name' => '5.1 Processing larger CSV files safely',
                'summary' => '<p>Process larger datasets in chunks and validate that the result remains complete and correct.</p>',
                'activities' => [
                    ['page', 'Lesson 12: Processing larger CSV files in chunks'],
                    ['lti', 'Python Lab 12: Scaling, chunks, and validation'],
                    ['quiz', 'Applied check: Scaling up safely'],
                ],
            ],
            [
                'name' => '5.2 Scale-up capstone project',
                'summary' => '<p>Produce decision-sized evidence from a larger operational dataset.</p>',
                'activities' => [
                    ['page', 'Capstone guide: From large file to decision-sized evidence'],
                    ['lti', 'Python Lab project: Scale-up capstone'],
                    ['assign', 'Scale-up capstone: Operations evidence'],
                    ['page', 'Teacher reference: Scale-up capstone (hidden)'],
                    ['page', 'Open-data extension: Licence, provenance, privacy, and validation'],
                ],
            ],
        ],
    ],
];

$legacysections = [
    '6. Functions, errors, and testing',
    'Foundation project',
    '7. Tables, CSV, and pandas',
    '8. Inspecting and selecting data',
    '9. Cleaning data',
    '10. Grouping and summary statistics',
    '11. Visualisation and evidence',
    'Data analysis project',
    'Final project and reflection',
    '12. Scaling up: larger CSV datasets',
    'Scale-up capstone project',
];

function normal_section_by_name(stdClass $course, array $names): ?section_info {
    $matches = [];
    foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
        if (!$section || $section->section == 0 || !empty($section->component)) {
            continue;
        }
        if (in_array($section->name, $names, true)) {
            $matches[] = $section;
        }
    }
    if (count($matches) > 1) {
        throw new moodle_exception('Multiple matching top-level sections: ' . implode(', ', $names));
    }
    return $matches ? reset($matches) : null;
}

function cmid_by_name(stdClass $course, string $modname, string $name): int {
    $matches = [];
    foreach (get_fast_modinfo($course)->get_cms() as $cm) {
        if ($cm->modname === $modname && $cm->name === $name) {
            $matches[] = $cm;
        }
    }
    if (count($matches) !== 1) {
        throw new moodle_exception("Expected one {$modname} activity named '{$name}', found " . count($matches));
    }
    return (int) reset($matches)->id;
}

function subsection_for_topic(stdClass $course, section_info $parent, array $topic): array {
    global $DB;

    $records = $DB->get_records('subsection', ['course' => $course->id, 'name' => $topic['name']]);
    if (count($records) > 1) {
        throw new moodle_exception("Multiple subsections named '{$topic['name']}'");
    }

    if ($records) {
        $instance = reset($records);
        $cm = get_coursemodule_from_instance('subsection', $instance->id, $course->id, false, MUST_EXIST);
    } else {
        $moduleinfo = (object) [
            'module' => $DB->get_field('modules', 'id', ['name' => 'subsection'], MUST_EXIST),
            'modulename' => 'subsection',
            'section' => $parent->section,
            'name' => $topic['name'],
            'visible' => 1,
            'visibleoncoursepage' => 1,
            'groupmode' => 0,
            'groupingid' => 0,
            'completion' => 0,
        ];
        $created = add_moduleinfo($moduleinfo, $course);
        $instance = $DB->get_record('subsection', ['id' => $created->instance], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('subsection', $instance->id, $course->id, false, MUST_EXIST);
    }

    $delegated = $DB->get_record('course_sections', [
        'course' => $course->id,
        'component' => 'mod_subsection',
        'itemid' => $instance->id,
    ], '*', MUST_EXIST);
    course_update_section($course, $delegated, [
        'name' => $topic['name'],
        'summary' => $topic['summary'],
        'summaryformat' => FORMAT_HTML,
        'visible' => 1,
    ]);

    return [(int) $cm->id, (int) $delegated->id];
}

// Preflight every existing activity before changing course structure.
$activityids = [];
foreach ($chapters as $chapter) {
    $parent = normal_section_by_name($course, [$chapter['name'], $chapter['legacysection']]);
    if (!$parent) {
        throw new moodle_exception("Top-level section for '{$chapter['name']}' was not found");
    }
    foreach ($chapter['topics'] as $topic) {
        foreach ($topic['activities'] as [$modname, $name]) {
            $cmid = cmid_by_name($course, $modname, $name);
            if (isset($activityids[$cmid])) {
                throw new moodle_exception("Activity {$cmid} is mapped more than once");
            }
            $activityids[$cmid] = $name;
        }
    }
}

$actions = formatactions::cm($course);
$createdsubsections = [];

foreach ($chapters as $chapter) {
    $parent = normal_section_by_name($course, [$chapter['name'], $chapter['legacysection']]);
    course_update_section($course, $parent, [
        'name' => $chapter['name'],
        'summary' => $chapter['summary'],
        'summaryformat' => FORMAT_HTML,
        'visible' => 1,
    ]);

    foreach ($chapter['topics'] as $topic) {
        [$subsectioncmid, $delegatedsectionid] = subsection_for_topic($course, $parent, $topic);
        // Re-appending in definition order makes reruns normalise chapter ordering.
        $actions->move_end_section($subsectioncmid, $parent->id);
        foreach ($topic['activities'] as [$modname, $name]) {
            $actions->move_end_section(cmid_by_name($course, $modname, $name), $delegatedsectionid);
        }
        $createdsubsections[] = [
            'name' => $topic['name'],
            'cmid' => $subsectioncmid,
            'sectionid' => $delegatedsectionid,
        ];
    }
}

rebuild_course_cache($course->id, true);

// Delete only the known legacy sections, and only after every activity has moved out.
foreach ($legacysections as $legacyname) {
    $section = normal_section_by_name($course, [$legacyname]);
    if (!$section) {
        continue;
    }
    if (!empty(get_fast_modinfo($course)->sections[$section->section])) {
        throw new moodle_exception("Legacy section '{$legacyname}' is not empty; refusing to delete it");
    }
    // A non-empty legacy summary also makes Moodle treat the section as non-empty.
    course_update_section($course, $section, ['summary' => '', 'summaryformat' => FORMAT_HTML]);
    $section = get_fast_modinfo($course)->get_section_info_by_id($section->id);
    if (!course_delete_section($course, $section, false, false)) {
        throw new moodle_exception("Could not delete empty legacy section '{$legacyname}'");
    }
    rebuild_course_cache($course->id, true);
}

rebuild_course_cache($course->id, true);

echo json_encode([
    'status' => 'ok',
    'course_id' => (int) $course->id,
    'chapters' => array_column($chapters, 'name'),
    'subsections' => $createdsubsections,
    'preserved_activity_ids' => array_map('intval', array_keys($activityids)),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
