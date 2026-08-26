<?php
// Reframe PYAI-INTRO as one learner's connected learning-centre data journey.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->libdir . '/resourcelib.php';
require_once $CFG->dirroot . '/mod/forum/lib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/question/editlib.php';

use core_question\local\bank\question_version_status;

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
\core\session\manager::set_user(get_admin());
function v3_announcement_posts(int $courseid, string $marker): array {
    global $DB;
    $sql = "SELECT p.*
              FROM {forum_posts} p
              JOIN {forum_discussions} d ON d.id = p.discussion
              JOIN {forum} f ON f.id = d.forum
             WHERE f.course = :courseid
               AND " . $DB->sql_like('p.message', ':marker');
    return array_values($DB->get_records_sql($sql, [
        'courseid' => $courseid, 'marker' => '%' . $marker . '%',
    ]));
}


function v3_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>'
        . s($code) . '</code></pre>';
}

function v3_add_page(stdClass $course, int $section, string $name, string $content, bool $visible = true): stdClass {
    global $DB;
    if ($page = $DB->get_record('page', ['course' => $course->id, 'name' => $name])) {
        return $page;
    }
    return add_moduleinfo((object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST),
        'modulename' => 'page', 'section' => $section, 'name' => $name,
        'intro' => '<p>Follow Naledi as the reporting workflow grows one step at a time.</p>',
        'introformat' => FORMAT_HTML, 'content' => $content, 'contentformat' => FORMAT_HTML,
        'display' => RESOURCELIB_DISPLAY_OPEN, 'printintro' => 0, 'printlastmodified' => 0,
        'visible' => $visible ? 1 : 0, 'visibleoncoursepage' => $visible ? 1 : 0,
        'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 0,
    ], $course);
}

function v3_quiz_has_question_named(int $quizid, string $name): bool {
    global $DB;
    $sql = "SELECT 1
              FROM {quiz_slots} qs
              JOIN {question_references} qr
                ON qr.component = 'mod_quiz' AND qr.questionarea = 'slot' AND qr.itemid = qs.id
              JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid
              JOIN {question} q ON q.id = qv.questionid
             WHERE qs.quizid = :quizid AND q.name = :name";
    return $DB->record_exists_sql($sql, ['quizid' => $quizid, 'name' => $name]);
}

function v3_save_question(int $categoryid, int $contextid, array $data): stdClass {
    $question = (object) ['qtype' => 'multichoice', 'category' => $categoryid . ',' . $contextid];
    $answers = $feedback = $fractions = [];
    foreach ($data['answers'] as $index => $answer) {
        $answers[] = ['text' => $answer, 'format' => FORMAT_PLAIN];
        $feedback[] = ['text' => $index === $data['correct'] ? 'Correct.' : 'Use the same method as Naledi, but substitute the new values.', 'format' => FORMAT_HTML];
        $fractions[] = $index === $data['correct'] ? 1.0 : 0.0;
    }
    $form = (object) [
        'name' => $data['name'], 'category' => $categoryid . ',' . $contextid,
        'questiontext' => ['text' => '<p>' . s($data['question']) . '</p>', 'format' => FORMAT_HTML],
        'generalfeedback' => ['text' => '<p>' . s($data['explanation']) . '</p>', 'format' => FORMAT_HTML],
        'defaultmark' => 1, 'penalty' => 0.3333333,
        'status' => question_version_status::QUESTION_STATUS_READY, 'idnumber' => null,
        'single' => 1, 'shuffleanswers' => 1, 'answernumbering' => 'abc', 'showstandardinstruction' => 1,
        'correctfeedback' => ['text' => 'Correct. Explain how it connects to the worked example.', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => 'Partly correct.', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => 'Not yet. Trace the values and test the boundary.', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0, 'answer' => $answers, 'fraction' => $fractions,
        'feedback' => $feedback, 'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

$persona = '<h2>Meet Naledi</h2><p>Naledi supports several community learning centres. She can use files and a browser but has never programmed. Her laptop has limited memory and the internet is not always available.</p>'
    . '<p>Every month she receives registration, attendance, completion, teaching-hour, and material-cost records. At first she calculates reports by hand. During this course she will turn that repeated work into a tested Python workflow.</p>'
    . '<h3>The connected journey</h3><ol><li>Calculate one centre correctly.</li><li>Apply decisions and process several weeks.</li><li>Represent several centres and reuse calculations.</li><li>Load the same records from CSV.</li><li>Clean, group, and visualise them.</li><li>Process several years in chunks.</li><li>Transfer the method to a licensed open dataset.</li></ol>'
    . '<p><strong>Your role:</strong> work alongside Naledi. The worked example shows one step. The transfer challenge asks you to use the same idea on a different operational measure.</p>';
v3_add_page($course, 0, 'Meet Naledi: One reporting task that grows with the course', $persona);

$journey = [
    'Lesson 1: Your first Python program' => [
        'story' => 'Naledi starts by displaying one centre’s monthly registrations and attendance.',
        'task' => 'Store 40 registered learners and 34 attendees, then print a readable two-line report.',
        'code' => "registered = 40\nattended = 34\nprint(\"Registered:\", registered)\nprint(\"Attended:\", attended)",
        'transfer' => 'A course scheduled 24 training hours and delivered 20. Display both values with clear labels.',
        'answer' => 'scheduled = 24; delivered = 20; print both values with labels. Accept separate lines or one readable line.',
        'scale' => 'Correct labels for one record become correct column meanings when the file contains a million records.',
    ],
    'Lesson 2: Variables, types, input, and calculations' => [
        'story' => 'The coordinator now needs a percentage rather than two separate counts.',
        'task' => 'Calculate attendance rate for 34 attendees from 40 registered learners and display 85.0%.',
        'code' => "registered = 40\nattended = 34\nattendance_rate = attended / registered * 100\nprint(\"Attendance rate:\", round(attendance_rate, 1), \"%\")",
        'transfer' => 'A centre has 29 completions from 36 registered learners. Calculate its completion percentage to one decimal place.',
        'answer' => '29 / 36 * 100 gives about 80.6%. The learner should identify registered as the denominator.',
        'scale' => 'Clear numerators, denominators, units, and types prevent a wrong formula from being repeated across every row.',
    ],
    'Lesson 3: Decisions with conditions' => [
        'story' => 'Naledi must flag a centre for support when attendance is below 75%, and watch it from 75% through 84.9%.',
        'task' => 'Classify an attendance rate of 72.5 as support, 80 as watch, and 88 as on track.',
        'code' => "rate = 72.5\nif rate < 75:\n    status = \"support\"\nelif rate < 85:\n    status = \"watch\"\nelse:\n    status = \"on track\"\nprint(status)",
        'transfer' => 'Materials below 10 units require reorder; 10–19 require monitoring; 20 or more are sufficient. Test 9, 10, 19, and 20.',
        'answer' => 'Use if stock < 10, elif stock < 20, else. The four boundary tests should produce reorder, monitor, monitor, sufficient.',
        'scale' => 'A boundary error at 75% can misclassify thousands of centre-month records, so test below, at, and above every threshold.',
    ],
    'Lesson 4: Repetition with loops' => [
        'story' => 'One monthly figure is built from several weekly attendance counts.',
        'task' => 'Sum [31, 33, 30, 34] and calculate the weekly mean.',
        'code' => "weekly_attendance = [31, 33, 30, 34]\ntotal = 0\nfor attended in weekly_attendance:\n    total += attended\nmean = total / len(weekly_attendance)\nprint(\"Total:\", total)\nprint(\"Weekly mean:\", mean)",
        'transfer' => 'Loop through weekly material costs [82.5, 74.0, 91.5, 80.0] and report total and highest weekly cost.',
        'answer' => 'The total is 328.0 and the maximum is 91.5. A loop-based maximum or max() is acceptable if explained.',
        'scale' => 'The loop establishes the idea of processing repeated records; later pandas and chunks perform it efficiently at scale.',
    ],
    'Lesson 5: Lists and dictionaries' => [
        'story' => 'Naledi combines several labelled centre records instead of keeping unrelated variables.',
        'task' => 'Represent two centres with name, registered, attended, and completed fields; print each attendance rate.',
        'code' => "centres = [\n    {\"name\": \"Gaborone\", \"registered\": 40, \"attended\": 34, \"completed\": 30},\n    {\"name\": \"Maun\", \"registered\": 35, \"attended\": 29, \"completed\": 25},\n]\nfor centre in centres:\n    rate = centre[\"attended\"] / centre[\"registered\"] * 100\n    print(centre[\"name\"], round(rate, 1))",
        'transfer' => 'Add a third centre containing course name, scheduled hours, and delivered hours; print delivery percentage.',
        'answer' => 'Use one dictionary with labelled keys, append it to the list, then calculate delivered / scheduled * 100.',
        'scale' => 'One dictionary resembles one labelled row; a list of dictionaries is the conceptual bridge to a DataFrame.',
    ],
    'Lesson 6: Functions, errors, and testing' => [
        'story' => 'The same percentage formula must be correct everywhere, including when the denominator is zero.',
        'task' => 'Create percentage(part, whole) that returns None when whole is zero or negative.',
        'code' => "def percentage(part, whole):\n    if whole <= 0:\n        return None\n    return part / whole * 100\n\nprint(percentage(34, 40))\nprint(percentage(0, 0))",
        'transfer' => 'Create cost_per_completion(material_cost, completed) with the same invalid-denominator protection. Test 450/30 and 450/0.',
        'answer' => 'The valid result is 15.0 and the zero-completion result should be None or another explicitly documented invalid result.',
        'scale' => 'A reusable function applies the same validated rule to new months and larger files without copying fragile logic.',
    ],
    'Lesson 7: Tables, CSV, and pandas' => [
        'story' => 'The centre records now arrive as a monthly CSV rather than being typed into the programme.',
        'task' => 'Load learning-centres-practice.csv and inspect head, shape, columns, and dtypes before calculating anything.',
        'code' => "import pandas as pd\n\ndf = pd.read_csv(\"learning-centres-practice.csv\")\nprint(df.head())\nprint(df.shape)\nprint(df.columns.tolist())\nprint(df.dtypes)",
        'transfer' => 'Select centre_name, registered, and completed, then calculate a completion_rate column.',
        'answer' => 'Use a column list for selection and df["completed"] / df["registered"] * 100 for the new column.',
        'scale' => 'Schema inspection replaces manual reading. The same four checks work whether there are 24 or 24 million rows.',
    ],
    'Lesson 8: Inspecting and selecting data' => [
        'story' => 'Naledi needs a focused support list, not every column and row.',
        'task' => 'Select centres with at least 30 registered learners and attendance rate below 80%.',
        'code' => "df[\"attendance_rate\"] = df[\"attended\"] / df[\"registered\"] * 100\npriority = df[(df[\"registered\"] >= 30) & (df[\"attendance_rate\"] < 80)]\nprint(priority[[\"month\", \"centre_name\", \"attendance_rate\"]])",
        'transfer' => 'Create a list of centre-months with at least 24 scheduled training hours but fewer than 20 delivered hours.',
        'answer' => 'Combine both Boolean conditions with &, wrap each in parentheses, and select only identifying and hour columns.',
        'scale' => 'Filtering early and selecting only useful columns reduces memory while keeping the question explicit.',
    ],
    'Lesson 9: Cleaning data' => [
        'story' => 'The practice CSV contains a blank attendance value, district spelling differences, and one impossible completion count.',
        'task' => 'Normalise district, convert counts numerically, and flag rows where completed exceeds attended.',
        'code' => "df[\"district\"] = df[\"district\"].str.strip().str.title()\nfor column in [\"registered\", \"attended\", \"completed\"]:\n    df[column] = pd.to_numeric(df[column], errors=\"coerce\")\ninvalid = df[df[\"completed\"] > df[\"attended\"]]\nprint(df.isna().sum())\nprint(invalid)",
        'transfer' => 'Flag negative material cost, delivered hours above scheduled hours, and duplicate centre-month-course rows. Report each count.',
        'answer' => 'Use comparisons for impossible ranges and duplicated(subset=[...], keep=False). Do not silently discard the rows.',
        'scale' => 'A small error percentage can represent thousands of records. Always report both counts and rates with a cleaning log.',
    ],
    'Lesson 10: Grouping and summary statistics' => [
        'story' => 'Individual rows are condensed into a district-level table for a management meeting.',
        'task' => 'Group by district and report number of centre-months, total registered, total completed, and mean attendance rate.',
        'code' => "summary = df.groupby(\"district\").agg(\n    centre_months=(\"centre_id\", \"count\"),\n    registered=(\"registered\", \"sum\"),\n    completed=(\"completed\", \"sum\"),\n    mean_attendance=(\"attendance_rate\", \"mean\"),\n)\nprint(summary)",
        'transfer' => 'Group by course and calculate total training hours, total material cost, and cost per completion.',
        'answer' => 'Aggregate hours, cost, and completions by course, then divide each course total cost by its total completions.',
        'scale' => 'Group-by converts detailed operations into a decision-sized table; group counts protect against misleading comparisons.',
    ],
    'Lesson 11: Visualisation and evidence' => [
        'story' => 'Naledi must explain whether attendance is changing over time without claiming an unsupported cause.',
        'task' => 'Aggregate monthly attendance rate and create a labelled line chart.',
        'code' => "monthly = df.groupby(\"month\").agg(attended=(\"attended\", \"sum\"), registered=(\"registered\", \"sum\"))\nmonthly[\"attendance_rate\"] = monthly[\"attended\"] / monthly[\"registered\"] * 100\nmonthly[\"attendance_rate\"].plot(kind=\"line\", marker=\"o\")\nplt.title(\"Monthly attendance rate\")\nplt.xlabel(\"Month\")\nplt.ylabel(\"Attendance rate (%)\")\nplt.tight_layout()\nplt.show()",
        'transfer' => 'Create a bar chart comparing completion rate by course. Write one observation and one limitation.',
        'answer' => 'Aggregate totals before calculating each course rate. A limitation could be differing course difficulty or small group size.',
        'scale' => 'Plot aggregates rather than millions of raw marks. The chart should answer the operational question, not display every record.',
    ],
];

$teacheranswers = '<h2>Teacher answers: Connected transfer challenges</h2><p>These are reference approaches, not exact-match requirements. Ask learners to run, modify, and explain their solution.</p>';
foreach ($journey as $pagename => $item) {
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    $marker = 'PYAI-V3-JOURNEY:' . sha1($pagename);
    if (strpos($page->content, $marker) === false) {
        $start = strrpos($page->content, '<section style="margin-top:2em');
        $prefix = $start === false ? $page->content : substr($page->content, 0, $start);
        $page->content = $prefix
            . '<section style="margin-top:2em;border-top:3px solid #356a9a;padding-top:1em">'
            . '<p><strong>Naledi’s next task:</strong> ' . s($item['story']) . '</p>'
            . '<h3>Connected worked example</h3><p>' . s($item['task']) . '</p>' . v3_code($item['code'])
            . '<details><summary><strong>Why this answer works</strong></summary><p>Run it first, change one input, and connect every output to the named operational measure.</p></details>'
            . '<h3>Transfer challenge</h3><p>' . s($item['transfer']) . '</p><p><em>The teacher has a reference answer. Your code may differ if it is correct and explainable.</em></p>'
            . '<div style="border-left:5px solid #7b5aa6;padding:.7em 1em;background:#f6f0fb"><strong>Path to larger data:</strong> ' . s($item['scale']) . '</div>'
            . '<span style="display:none">' . $marker . '</span></section>';
        $page->timemodified = time();
        $DB->update_record('page', $page);
    }
    $teacheranswers .= '<h3>' . s($pagename) . '</h3><p><strong>Challenge:</strong> ' . s($item['transfer']) . '</p><p><strong>Reference:</strong> ' . s($item['answer']) . '</p>';
}
v3_add_page($course, 14, 'Teacher answers: Connected transfer challenges (hidden)', $teacheranswers, false);

$datasetpage = '<h2>Learning-centre dataset progression</h2><p>The course uses fictional operational data first so every learner can work offline with the same schema and known answers. It contains no personal data.</p>'
    . '<h3>Small practice file</h3><p><code>learning-centres-practice.csv</code> contains 24 centre-month records and intentional quality issues: a blank value, district label variation, and a completion count greater than attendance.</p>'
    . v3_code("month,centre_id,centre_name,district,course,registered,attended,completed\n2026-01,C001,Gaborone Learning Centre,South,Python Foundations,32,28,24\n2026-01,C002,Molepolole Learning Centre,Central,Python Foundations,27,21,18\n2026-01,C003,Maun Learning Centre,North,Digital Skills,35,29,25")
    . '<h3>Scale-up file</h3><p>Use the supplied deterministic generator to create 10,000 rows for a first run, then 250,000 or more when the computer permits:</p>'
    . v3_code("python generate-learning-centre-data.py --rows 10000 --output learning-centres-10000.csv\npython generate-learning-centre-data.py --rows 250000 --output learning-centres-large.csv")
    . '<p>The same seed produces the same data and intentional errors, allowing teachers to reproduce results and learners to reconcile counts.</p>';
v3_add_page($course, 8, 'Dataset pack: From 24 rows to 250,000 fictional records', $datasetpage);

$opendata = '<h2>Extension: Transfer the workflow to open data</h2><p>After the fictional capstone works, repeat the analysis with an openly licensed dataset. “Publicly accessible” does not automatically mean reusable.</p>'
    . '<h3>Selection checklist</h3><ol><li>The licence explicitly permits the intended reuse and redistribution, such as CC0 or CC BY.</li><li>The source and publisher are identifiable.</li><li>The dataset contains no unnecessary personal or sensitive information.</li><li>The schema and file version can be recorded.</li><li>A local copy can be retained for an offline class.</li></ol>'
    . '<h3>Required provenance note</h3><ul><li>Dataset title and publisher</li><li>Direct source URL</li><li>Licence and attribution text</li><li>Download date and file checksum</li><li>Any filtering, renaming, or derived columns</li></ul>'
    . '<p>First reproduce one known total or published statistic. Then ask a modest new question. Do not change the method and the dataset simultaneously without a small validation fixture.</p>';
v3_add_page($course, 16, 'Open-data extension: Licence, provenance, privacy, and validation', $opendata);

// Replace the first practical project with the connected learning-centre milestone.
$mini = $DB->get_record('assign', ['course' => $course->id, 'name' => 'Mini-project: Mobile data budget planner']);
if ($mini) {
    $mini->name = 'Mini-project: Weekly learning-centre support report';
    $mini->intro = '<h2>Milestone project</h2><p>Use variables, conditions, and loops to process four weekly attendance counts. Calculate total and mean attendance, then classify the month as support (below 75%), watch (75–84.9%), or on track (85% or higher). Test below, at, and above both boundaries.</p>'
        . '<h3>Submit</h3><ul><li>Runnable Python code</li><li>Normal and boundary test results</li><li>A short operational recommendation</li><li>AI use declaration</li></ul>'
        . '<h3>Rubric</h3><ul><li>Correct variables and calculations: 25</li><li>Loop processes all weeks: 20</li><li>Conditions and boundaries: 25</li><li>Tests and useful output: 20</li><li>Explanation and AI declaration: 10</li></ul>';
    $mini->timemodified = time();
    $DB->update_record('assign', $mini);
}
$minianswer = $DB->get_record('page', ['course' => $course->id, 'name' => 'Teacher model answer: Mobile data budget planner (hidden)']);
if ($minianswer) {
    $minianswer->name = 'Teacher model answer: Weekly learning-centre support report (hidden)';
    $minianswer->content = '<h2>Teacher model</h2>' . v3_code("registered = 40\nweekly_attendance = [31, 33, 30, 34]\ntotal = sum(weekly_attendance)\nmean = total / len(weekly_attendance)\nrate = mean / registered * 100\nif rate < 75:\n    status = \"support\"\nelif rate < 85:\n    status = \"watch\"\nelse:\n    status = \"on track\"\nprint(round(rate, 1), status)")
        . '<p>Accept an explicit loop instead of sum(). Require tests such as 74.9, 75, 84.9, and 85. Ask the learner to change registered or one weekly value and predict the direction before running.</p>';
    $minianswer->timemodified = time();
    $DB->update_record('page', $minianswer);
}

$foundation = $DB->get_record('assign', ['course' => $course->id, 'name' => 'Foundation project: Score report']);
if ($foundation) {
    $foundation->name = 'Foundation project: Monthly learning-centre performance report';
    $foundation->intro = '<h2>Foundation project</h2><p>Represent at least three learning centres with registered, attended, completed, and material-cost values. Write reusable functions for attendance percentage, completion percentage, and cost per completion. Validate zero denominators and impossible counts. Print a readable report and flag centres needing support.</p><h3>Submit</h3><ul><li>Runnable code</li><li>Normal, boundary, and invalid-data tests</li><li>150–250 word explanation</li><li>AI use declaration</li></ul>';
    $foundation->timemodified = time();
    $DB->update_record('assign', $foundation);
}

$context = context_course::instance($course->id);
$category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => 'Python course checks'], '*', MUST_EXIST);
$transferquestions = [
    'Knowledge check: Lesson 1: Your first Python program' => ['labels', 'A report must display scheduled hours 24 and delivered hours 20. Which output is clearest?', ['24 20', 'Scheduled: 24, Delivered: 20', 'hours', '44 without labels'], 1, 'Labels preserve the meaning of each value.'],
    'Knowledge check: Lesson 2: Variables, types, input, and calculations' => ['completion rate', 'A centre registered 36 learners and 29 completed. What is the completion rate to one decimal place?', ['80.6%', '124.1%', '7%', '65.0%'], 0, '29 / 36 × 100 is about 80.6%.'],
    'Knowledge check: Lesson 3: Decisions with conditions' => ['material boundary', 'Reorder is below 10 and monitor is 10 through 19. What status applies at stock = 10?', ['reorder', 'monitor', 'sufficient', 'invalid'], 1, 'Ten is not below ten, so it enters the monitor range.'],
    'Knowledge check: Lesson 4: Repetition with loops' => ['cost loop', 'What is the total of weekly costs [82.5, 74.0, 91.5, 80.0]?', ['318.0', '328.0', '337.0', '91.5'], 1, 'Adding all four weekly values gives 328.0.'],
    'Knowledge check: Lesson 5: Lists and dictionaries' => ['record field', 'Which dictionary key should be the denominator for delivered / scheduled * 100?', ['delivered', 'scheduled', 'name', 'course'], 1, 'Scheduled hours represent the whole against which delivery is measured.'],
    'Knowledge check: Lesson 6: Functions, errors, and testing' => ['zero completion', 'What should cost_per_completion(450, 0) do?', ['Divide normally', 'Return an explicit invalid/missing result before division', 'Return 450', 'Hide the row'], 1, 'Zero completion requires an explicit boundary decision before division.'],
    'Knowledge check: Lesson 7: Tables, CSV, and pandas' => ['inspect first', 'After loading a new centre CSV, what should Naledi do before calculating rates?', ['Inspect sample rows, shape, columns, and dtypes', 'Assume every type is correct', 'Delete blanks immediately', 'Create the final chart'], 0, 'Inspection establishes the actual schema and quality before analysis.'],
    'Knowledge check: Lesson 9: Cleaning data' => ['impossible completion', 'Which row is logically impossible?', ['registered 40, attended 34, completed 30', 'registered 40, attended 34, completed 39', 'registered 40, attended 40, completed 40', 'registered 40, attended 20, completed 15'], 1, 'Completions should not exceed attendance under this data definition.'],
    'Knowledge check: Lesson 10: Grouping and summary statistics' => ['course cost', 'To calculate cost per completion by course, what should be divided?', ['Mean row cost by row count', 'Total course cost by total course completions', 'Total completions by total cost', 'Course count by centre count'], 1, 'Aggregate compatible totals first, then calculate the rate.'],
    'Knowledge check: Lesson 11: Visualisation and evidence' => ['course comparison', 'Which chart best compares completion rate across four course categories?', ['Bar chart', 'Line chart implying time order', 'Millions of raw points', 'Unlabelled pie chart'], 0, 'A bar chart clearly compares a small number of categories.'],
];
foreach ($transferquestions as $quizname => [$suffix, $prompt, $answers, $correct, $explanation]) {
    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname], '*', MUST_EXIST);
    $questionname = $shortname . ' transfer: ' . $suffix;
    if (v3_quiz_has_question_named((int) $quiz->id, $questionname)) {
        continue;
    }
    $question = v3_save_question($category->id, $context->id, [
        'name' => $questionname, 'question' => $prompt, 'answers' => $answers,
        'correct' => $correct, 'explanation' => $explanation,
    ]);
    quiz_add_quiz_question($question->id, $quiz, 0, 1);
    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
}

$marker = 'PYAI-V3-ANNOUNCEMENT';
$newsforum = forum_get_course_forum($course->id, 'news');
if (!v3_announcement_posts($course->id, $marker)) {
    forum_add_discussion((object) [
        'course' => $course->id, 'forum' => $newsforum->id,
        'name' => 'Course story: Work alongside Naledi from one centre to open data',
        'message' => '<p>The course now follows Naledi, a learning-centre operations assistant. Each worked example advances one monthly reporting workflow. Each transfer challenge asks you to apply the same skill to another centre measure.</p><p>Use the fictional files first because they are offline, reproducible, and contain no personal data. After your workflow is validated, the final extension transfers it to a properly licensed open dataset.</p><p style="display:none">' . $marker . '</p>',
        'messageformat' => FORMAT_HTML, 'messagetrust' => 0, 'mailnow' => 0,
        'groupid' => -1, 'itemid' => 0,
    ], null, null, get_admin()->id);
}

$course->summary = '<p>Follow Naledi as one learning-centre reporting task grows from basic Python into a validated analysis of larger CSV files and, finally, a licensed open dataset. Worked examples, transfer challenges, practical milestones, and teacher-only answers form one connected learning journey. Responsible AI assistance is permitted; testing, verification, modification, and explanation are required.</p>';
$course->summaryformat = FORMAT_HTML;
$course->timemodified = time();
$DB->update_record('course', $course);
rebuild_course_cache($course->id, true);

echo json_encode([
    'upgraded' => true, 'version' => 3, 'courseid' => (int) $course->id,
    'shortname' => $course->shortname,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
