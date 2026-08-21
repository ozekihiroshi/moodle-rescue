<?php
// Upgrade PYAI-INTRO with applied examples, projects, announcements, and a scale-up pathway.

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

function v2_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>'
        . s($code) . '</code></pre>';
}

function v2_add_page(stdClass $course, int $section, string $name, string $content, bool $visible = true): stdClass {
    global $DB;
    if ($page = $DB->get_record('page', ['course' => $course->id, 'name' => $name])) {
        return $page;
    }
    return add_moduleinfo((object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST),
        'modulename' => 'page', 'section' => $section, 'name' => $name,
        'intro' => '<p>Read the task, run the example, and explain your decisions.</p>',
        'introformat' => FORMAT_HTML, 'content' => $content, 'contentformat' => FORMAT_HTML,
        'display' => RESOURCELIB_DISPLAY_OPEN, 'printintro' => 0, 'printlastmodified' => 0,
        'visible' => $visible ? 1 : 0, 'visibleoncoursepage' => $visible ? 1 : 0,
        'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 0,
    ], $course);
}

function v2_add_assignment(stdClass $course, int $section, string $name, string $intro): stdClass {
    global $DB;
    if ($assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $name])) {
        return $assign;
    }
    return add_moduleinfo((object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST),
        'modulename' => 'assign', 'section' => $section, 'name' => $name,
        'intro' => $intro, 'introformat' => FORMAT_HTML, 'alwaysshowdescription' => 1,
        'submissiondrafts' => 0, 'requiresubmissionstatement' => 0,
        'sendnotifications' => 0, 'sendlatenotifications' => 0, 'sendstudentnotifications' => 1,
        'duedate' => 0, 'cutoffdate' => 0, 'gradingduedate' => 0, 'allowsubmissionsfromdate' => 0,
        'grade' => 100, 'attemptreopenmethod' => 'manual', 'maxattempts' => -1,
        'teamsubmission' => 0, 'requireallteammemberssubmit' => 0, 'blindmarking' => 0,
        'markingworkflow' => 0, 'markingallocation' => 0,
        'assignsubmission_onlinetext_enabled' => 1, 'assignsubmission_file_enabled' => 1,
        'assignsubmission_file_maxfiles' => 5, 'assignsubmission_file_maxsizebytes' => 0,
        'assignfeedback_comments_enabled' => 1, 'visible' => 1, 'visibleoncoursepage' => 1,
        'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 1,
    ], $course);
}

function v2_save_question(int $categoryid, int $contextid, array $data): stdClass {
    $question = (object) ['qtype' => 'multichoice', 'category' => $categoryid . ',' . $contextid];
    $answers = $feedback = $fractions = [];
    foreach ($data['answers'] as $index => $answer) {
        $answers[] = ['text' => $answer, 'format' => FORMAT_PLAIN];
        $feedback[] = ['text' => $index === $data['correct'] ? 'Correct.' : 'Trace the scenario with the given values.', 'format' => FORMAT_HTML];
        $fractions[] = $index === $data['correct'] ? 1.0 : 0.0;
    }
    $form = (object) [
        'name' => $data['name'], 'category' => $categoryid . ',' . $contextid,
        'questiontext' => ['text' => '<p>' . s($data['question']) . '</p>', 'format' => FORMAT_HTML],
        'generalfeedback' => ['text' => '<p>' . s($data['explanation']) . '</p>', 'format' => FORMAT_HTML],
        'defaultmark' => 1, 'penalty' => 0.3333333,
        'status' => question_version_status::QUESTION_STATUS_READY, 'idnumber' => null,
        'single' => 1, 'shuffleanswers' => 1, 'answernumbering' => 'abc', 'showstandardinstruction' => 1,
        'correctfeedback' => ['text' => 'Correct. Explain the calculation or decision.', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => 'Partly correct.', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => 'Not yet. Substitute the values and trace the code.', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0, 'answer' => $answers, 'fraction' => $fractions,
        'feedback' => $feedback, 'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v2_add_quiz(stdClass $course, int $section, string $name, array $questions, int $categoryid, int $contextid): stdClass {
    global $DB;
    if ($quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $name])) {
        return $quiz;
    }
    $created = add_moduleinfo((object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST),
        'modulename' => 'quiz', 'section' => $section, 'name' => $name,
        'intro' => '<p>Apply the technique to realistic data-processing decisions.</p>', 'introformat' => FORMAT_HTML,
        'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0, 'overduehandling' => 'autosubmit',
        'graceperiod' => 0, 'preferredbehaviour' => 'deferredfeedback', 'attempts' => 0,
        'attemptonlast' => 0, 'grademethod' => QUIZ_GRADEHIGHEST, 'decimalpoints' => 0,
        'questiondecimalpoints' => -1, 'questionsperpage' => 3, 'navmethod' => QUIZ_NAVMETHOD_FREE,
        'shuffleanswers' => 1, 'grade' => 100, 'reviewattempt' => 69888,
        'reviewcorrectness' => 4352, 'reviewmarks' => 4352, 'reviewspecificfeedback' => 4352,
        'reviewgeneralfeedback' => 4352, 'reviewrightanswer' => 4352, 'reviewoverallfeedback' => 4352,
        'password' => '', 'quizpassword' => '', 'subnet' => '', 'browsersecurity' => '-',
        'delay1' => 0, 'delay2' => 0, 'completionattemptsexhausted' => 0, 'completionminattempts' => 0,
        'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0, 'groupingid' => 0,
        'completion' => 0, 'showdescription' => 1,
    ], $course);
    $quiz = $DB->get_record('quiz', ['id' => $created->instance], '*', MUST_EXIST);
    foreach ($questions as $data) {
        $question = v2_save_question($categoryid, $contextid, $data);
        quiz_add_quiz_question($question->id, $quiz, 0, 1);
    }
    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
    return $quiz;
}

// Post a real announcement, not merely an empty Announcements forum.
$announcementmarker = 'PYAI-V2-ANNOUNCEMENT';
$newsforum = $DB->get_record('forum', ['course' => $course->id, 'type' => 'news']);
if ($newsforum && !$DB->record_exists_select('forum_posts', $DB->sql_like('message', ':marker'), ['marker' => '%' . $announcementmarker . '%'])) {
    forum_add_discussion((object) [
        'course' => $course->id, 'forum' => $newsforum->id, 'name' => 'Welcome: our path from Python basics to larger datasets',
        'message' => '<p><strong>Welcome.</strong> This course builds one connected set of skills: values become records, records become tables, and tables become evidence for a practical decision.</p>'
            . '<p>After conditions and loops you will build a mobile-data budget planner. Later you will analyse CSV data, clean quality problems, compare groups, create charts, and finally process a dataset that is too large to treat as a classroom toy.</p>'
            . '<p>For every task, use the cycle <strong>Read → Type → Run → Change → Explain</strong>. AI assistance is allowed, but you must test, verify, modify, and explain the result.</p>'
            . '<p style="display:none">' . $announcementmarker . '</p>',
        'messageformat' => FORMAT_HTML, 'messagetrust' => 0, 'mailnow' => 0, 'groupid' => -1, 'itemid' => 0,
    ], null, null, get_admin()->id);
}

$examples = [
    'Lesson 1: Your first Python program' => [
        'task' => 'A shop records 18 notebooks sold at 2.50 each. Display a clear sales total.',
        'answer' => "quantity = 18\nunit_price = 2.50\ntotal = quantity * unit_price\nprint(\"Sales total:\", total)",
        'path' => 'The same expression will later be applied to thousands of sales rows. First learn to verify one row correctly.',
    ],
    'Lesson 2: Variables, types, input, and calculations' => [
        'task' => 'Ask for monthly income and three expenses, then display the remaining amount.',
        'answer' => "income = float(input(\"Income: \"))\nrent = float(input(\"Rent: \"))\nfood = float(input(\"Food: \"))\ndata = float(input(\"Mobile data: \"))\nremaining = income - rent - food - data\nprint(\"Remaining:\", remaining)",
        'path' => 'Column names in a large table play the same role as meaningful variable names. Units and types must remain clear.',
    ],
    'Lesson 3: Decisions with conditions' => [
        'task' => 'Classify stock as reorder (below 10), watch (10–19), or sufficient (20 or more). Test 9, 10, 19, and 20.',
        'answer' => "stock = 19\nif stock < 10:\n    status = \"reorder\"\nelif stock < 20:\n    status = \"watch\"\nelse:\n    status = \"sufficient\"\nprint(status)",
        'path' => 'Data cleaning and filtering are large collections of decisions. Boundary tests prevent thousands of rows being misclassified.',
    ],
    'Lesson 4: Repetition with loops' => [
        'task' => 'Given daily water readings [120, -1, 135, 128], ignore the invalid negative marker and calculate the valid total and count.',
        'answer' => "readings = [120, -1, 135, 128]\ntotal = 0\ncount = 0\nfor reading in readings:\n    if reading >= 0:\n        total += reading\n        count += 1\nprint(total, count)",
        'path' => 'A loop is the first model of processing many records. pandas later performs similar work efficiently over entire columns.',
    ],
    'Lesson 5: Lists and dictionaries' => [
        'task' => 'Represent three inventory items with name, quantity, and unit price; print the value of each item.',
        'answer' => "items = [\n    {\"name\": \"Notebook\", \"qty\": 12, \"price\": 2.5},\n    {\"name\": \"Pen\", \"qty\": 40, \"price\": 0.8},\n    {\"name\": \"Folder\", \"qty\": 7, \"price\": 1.4},\n]\nfor item in items:\n    print(item[\"name\"], item[\"qty\"] * item[\"price\"])",
        'path' => 'A dictionary resembles one labelled row; a list of dictionaries resembles a table. This is the bridge to DataFrames.',
    ],
    'Lesson 6: Functions, errors, and testing' => [
        'task' => 'Write a function that calculates cost per learner and returns None when learner count is zero.',
        'answer' => "def cost_per_learner(total_cost, learners):\n    if learners <= 0:\n        return None\n    return total_cost / learners\n\nprint(cost_per_learner(1200, 30))\nprint(cost_per_learner(1200, 0))",
        'path' => 'Reusable, tested functions make cleaning rules consistent when the dataset grows or is refreshed next month.',
    ],
    'Lesson 7: Tables, CSV, and pandas' => [
        'task' => 'A file reports shape (250000, 8). State what this means and write code to inspect five rows and all column types.',
        'answer' => "df = pd.read_csv(\"records.csv\")\nprint(df.shape)       # 250,000 rows and 8 columns\nprint(df.head())\nprint(df.dtypes)",
        'path' => 'Inspection must scale. With 250,000 rows you inspect samples, schema, counts, and summaries rather than reading every row.',
    ],
    'Lesson 8: Inspecting and selecting data' => [
        'task' => 'Select centre, district, and completion_rate for centres with at least 100 learners and completion_rate below 0.70.',
        'answer' => "priority = df[(df[\"learners\"] >= 100) & (df[\"completion_rate\"] < 0.70)]\nprint(priority[[\"centre\", \"district\", \"completion_rate\"]])",
        'path' => 'Early filtering and selecting only needed columns reduces memory use and keeps a large analysis focused.',
    ],
    'Lesson 9: Cleaning data' => [
        'task' => 'Convert an attendance column to numeric, count invalid values, and keep only values from 0 to 100.',
        'answer' => "df[\"attendance\"] = pd.to_numeric(df[\"attendance\"], errors=\"coerce\")\ninvalid_count = df[\"attendance\"].isna().sum()\nclean = df[df[\"attendance\"].between(0, 100)].copy()\nprint(\"Invalid or missing:\", invalid_count)",
        'path' => 'A 0.1% error rate means 1,000 questionable rows in a million-row dataset. Report rates and counts, not just examples.',
    ],
    'Lesson 10: Grouping and summary statistics' => [
        'task' => 'Compare districts using count, mean completion rate, and total learners. Sort by total learners.',
        'answer' => "summary = clean.groupby(\"district\").agg(\n    centres=(\"centre\", \"count\"),\n    mean_completion=(\"completion_rate\", \"mean\"),\n    total_learners=(\"learners\", \"sum\"),\n+).sort_values(\"total_learners\", ascending=False)\nprint(summary)",
        'path' => 'Group-by aggregation turns millions of detailed rows into a decision-sized table, while counts preserve context.',
    ],
    'Lesson 11: Visualisation and evidence' => [
        'task' => 'Create a monthly line chart from a table with month and usage_kwh columns, with title, axes, and units.',
        'answer' => "monthly = df.groupby(\"month\")[\"usage_kwh\"].sum()\nmonthly.plot(kind=\"line\", marker=\"o\")\nplt.title(\"Monthly electricity use\")\nplt.xlabel(\"Month\")\nplt.ylabel(\"Electricity use (kWh)\")\nplt.tight_layout()\nplt.show()",
        'path' => 'Large raw data should usually be aggregated before plotting. A chart with millions of marks is slower and often less informative.',
    ],
];

foreach ($examples as $pagename => $example) {
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    $marker = 'PYAI-V2-EXAMPLE:' . sha1($pagename);
    if (strpos($page->content, $marker) !== false) {
        continue;
    }
    $page->content .= '<section style="margin-top:2em;border-top:2px solid #d8dce1;padding-top:1em">'
        . '<h3>Applied example problem</h3><p>' . s($example['task']) . '</p>'
        . '<details><summary><strong>Show the model answer</strong></summary>' . v2_code($example['answer'])
        . '<p>Run it, change one value, and explain the changed result. Equivalent correct solutions are welcome.</p></details>'
        . '<div style="margin-top:1em;border-left:5px solid #7b5aa6;padding:.7em 1em;background:#f6f0fb">'
        . '<strong>Path to larger data:</strong> ' . s($example['path']) . '</div>'
        . '<span style="display:none">' . $marker . '</span></section>';
    $page->timemodified = time();
    $DB->update_record('page', $page);
}

$context = context_course::instance($course->id);
$category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => 'Python course checks'], '*', MUST_EXIST);
$appliedquestions = [
    'Knowledge check: Lesson 1: Your first Python program' => ['Output for one sale', 'A programme uses quantity = 12, price = 2.5, and print(quantity * price). What is displayed?', ['14.5', '30', '122.5', 'An error'], 1, 'Twelve items at 2.5 each total 30.'],
    'Knowledge check: Lesson 2: Variables, types, input, and calculations' => ['Remaining budget', 'Income is 500 and expenses are 200, 120, and 30. What remaining amount should the programme report?', ['150', '250', '350', '850'], 0, '500 - 200 - 120 - 30 = 150.'],
    'Knowledge check: Lesson 3: Decisions with conditions' => ['Reorder boundary', 'Stock below 10 must be reordered. Which stock level should first NOT be classified as reorder?', ['9', '10', '0', '-1'], 1, 'The rule is below 10, so 10 is outside the reorder branch.'],
    'Knowledge check: Lesson 4: Repetition with loops' => ['Valid reading total', 'A loop adds only non-negative readings from [20, -1, 25, 30]. What total should it produce?', ['74', '75', '76', '3'], 1, 'The -1 marker is excluded: 20 + 25 + 30 = 75.'],
    'Knowledge check: Lesson 5: Lists and dictionaries' => ['Inventory record', 'Which expression calculates the value of item = {"qty": 8, "price": 1.5}?', ['item["qty"] + item["price"]', 'item["qty"] * item["price"]', 'item[0] * item[1]', 'len(item)'], 1, 'Quantity multiplied by unit price gives inventory value.'],
    'Knowledge check: Lesson 6: Functions, errors, and testing' => ['Safe unit cost', 'What should a cost_per_person function do when people is zero?', ['Divide anyway', 'Return a clear missing/invalid result before division', 'Change people to one silently', 'Delete the function'], 1, 'Checking the boundary prevents division by zero and makes the decision explicit.'],
    'Knowledge check: Lesson 7: Tables, CSV, and pandas' => ['Large table shape', 'A DataFrame has shape (1000000, 12). How many records and columns are present?', ['12 records and 1,000,000 columns', '1,000,000 records and 12 columns', '1,000,012 records', 'The shape is invalid'], 1, 'DataFrame shape is reported as rows, then columns.'],
    'Knowledge check: Lesson 9: Cleaning data' => ['Error rate at scale', 'A million-row file has 1,000 invalid rows. What percentage is invalid?', ['0.001%', '0.1%', '1%', '10%'], 1, '1,000 / 1,000,000 × 100 = 0.1%.'],
    'Knowledge check: Lesson 10: Grouping and summary statistics' => ['Group-size caution', 'District A has mean 80 from 2 records; District B has mean 76 from 2,000 records. What is the best response?', ['Declare A superior immediately', 'Report both means and counts and avoid a strong conclusion from A', 'Ignore B', 'Change A to 76'], 1, 'A tiny group can produce an unstable mean, so count is essential context.'],
    'Knowledge check: Lesson 11: Visualisation and evidence' => ['Plot after aggregation', 'You have 20 million transaction rows and need monthly totals. What should you usually plot?', ['All 20 million raw points', 'The 12 monthly aggregated totals', 'Only the first row', 'Unlabelled random samples'], 1, 'Aggregate to the level of the question before plotting.'],
];

foreach ($appliedquestions as $quizname => [$suffix, $prompt, $answers, $correct, $explanation]) {
    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname], '*', MUST_EXIST);
    $questionname = $shortname . ' applied: ' . $suffix;
    if ($DB->record_exists('question', ['name' => $questionname])) {
        continue;
    }
    $question = v2_save_question($category->id, $context->id, [
        'name' => $questionname, 'question' => $prompt, 'answers' => $answers,
        'correct' => $correct, 'explanation' => $explanation,
    ]);
    quiz_add_quiz_question($question->id, $quiz, 0, 1);
    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
}

$aideclaration = '<h3>Required AI use declaration</h3><ol><li>Did you use an AI tool?</li><li>What help did you request?</li><li>What did you run, test, change, or verify?</li><li>What did you learn?</li></ol>';
$miniproject = '<h2>Mini-project: Mobile data budget planner</h2>'
    . '<p>Build a useful command-line programme for a person choosing a mobile-data package. Store at least three packages with name, price, and included gigabytes. Ask for expected weekly use and a monthly budget. Use a loop to compare packages and conditions to mark each as suitable, over budget, or too small.</p>'
    . '<h3>Required evidence</h3><ul><li>Readable Python code</li><li>Tests at a package limit and budget limit</li><li>A sample recommendation</li><li>100–150 words explaining one design decision</li></ul>'
    . '<h3>Rubric</h3><ul><li>Variables and conversions: 20</li><li>Correct conditions and boundaries: 25</li><li>Loop processes every package: 25</li><li>Useful output and tests: 20</li><li>Explanation and AI declaration: 10</li></ul>' . $aideclaration;
v2_add_assignment($course, 4, 'Mini-project: Mobile data budget planner', $miniproject);

$minianswer = '<h2>Teacher model: Mobile data budget planner</h2>'
    . v2_code("packages = [\n    {\"name\": \"Basic\", \"gb\": 5, \"price\": 8},\n    {\"name\": \"Standard\", \"gb\": 12, \"price\": 15},\n    {\"name\": \"Plus\", \"gb\": 25, \"price\": 26},\n]\nweekly_use = float(input(\"Expected GB per week: \"))\nbudget = float(input(\"Monthly budget: \"))\nmonthly_use = weekly_use * 4\n\nfor package in packages:\n    if package[\"gb\"] < monthly_use:\n        status = \"too small\"\n    elif package[\"price\"] > budget:\n        status = \"over budget\"\n    else:\n        status = \"suitable\"\n    print(package[\"name\"], status)")
    . '<h3>Teacher notes</h3><p>Accept functions, tuples, or parallel lists when the learner can explain them. Test equality at both boundaries. Common errors are comparing weekly use with monthly allowance, converting after calculation, or placing the print statement outside the loop.</p>';
v2_add_page($course, 4, 'Teacher model answer: Mobile data budget planner (hidden)', $minianswer, false);

foreach ([15, 16] as $sectionnumber) {
    course_create_sections_if_missing($course, $sectionnumber);
}
$newsections = [
    15 => ['12. Scaling up: larger CSV datasets', 'Read selected columns, control data types, aggregate in chunks, and validate totals.'],
    16 => ['Scale-up capstone project', 'Use a substantial real-world dataset to produce reproducible evidence for a practical decision.'],
];
foreach ($newsections as $number => [$name, $summary]) {
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $number], '*', MUST_EXIST);
    $section->name = $name;
    $section->summary = '<p>' . s($summary) . '</p>';
    $section->summaryformat = FORMAT_HTML;
    $DB->update_record('course_sections', $section);
}

$scalinglesson = '<h2>When the file no longer feels small</h2><p><strong>Estimated study time:</strong> 3 hours</p>'
    . '<h3>Learning objectives</h3><ul><li>Estimate whether a file fits in memory</li><li>Read only needed columns with explicit types</li><li>Aggregate a CSV in chunks</li><li>Reconcile chunk results with independent totals</li></ul>'
    . '<p>“Big data” is relative to the available computer. The professional habit is to reduce data early, preserve an audit trail, and validate the final totals. Start with file size, columns, types, row count, missingness, and the question you need to answer.</p>'
    . v2_code("import pandas as pd\n\ntotals = {}\nrow_count = 0\nfor chunk in pd.read_csv(\n    \"transactions.csv\",\n    usecols=[\"district\", \"amount\"],\n    dtype={\"district\": \"category\", \"amount\": \"float64\"},\n    chunksize=100_000,\n):\n    row_count += len(chunk)\n    part = chunk.groupby(\"district\", observed=True)[\"amount\"].sum()\n    for district, amount in part.items():\n        totals[district] = totals.get(district, 0) + amount\n\nprint(\"Rows processed:\", row_count)\nprint(totals)")
    . '<h3>Validation checklist</h3><ol><li>Compare processed row count with the source expectation.</li><li>Count rejected or missing amounts.</li><li>Compare the grand total with a second calculation on a small known fixture.</li><li>Record file name, date, filters, and cleaning decisions.</li></ol>'
    . '<h3>Privacy and proportionality</h3><p>Do not collect personal data merely because storage is available. Remove identifiers not needed for the question, restrict access, and prefer aggregated reporting where possible.</p>'
    . '<div style="border-left:5px solid #7b5aa6;padding:.7em 1em;background:#f6f0fb"><strong>Connection:</strong> variables hold running totals; conditions validate rows; loops process chunks; dictionaries merge group results; functions make checks reusable; pandas performs column and group operations; charts communicate the final aggregate.</div>';
v2_add_page($course, 15, 'Lesson 12: Processing larger CSV files in chunks', $scalinglesson);

$scalequestions = [
    ['name' => $shortname . ' scale: chunksize', 'question' => 'Why use chunksize=100000 when reading a very large CSV?', 'answers' => ['To load manageable blocks instead of the whole file', 'To delete every 100,000th row', 'To create 100,000 columns', 'To disable validation'], 'correct' => 0, 'explanation' => 'Chunking bounds memory use while allowing incremental aggregation.'],
    ['name' => $shortname . ' scale: usecols', 'question' => 'The analysis needs district and amount from a 60-column file. What is the most useful early reduction?', 'answers' => ['Read all columns repeatedly', 'Use usecols to read district and amount', 'Convert every field to long text', 'Plot before reading'], 'correct' => 1, 'explanation' => 'Reading only necessary columns reduces memory and clarifies the analysis boundary.'],
    ['name' => $shortname . ' scale: reconcile', 'question' => 'After chunk aggregation, which check most directly guards against skipped data?', 'answers' => ['Change the chart colour', 'Compare processed row count and totals with independent expectations', 'Rename the Python file', 'Remove the cleaning log'], 'correct' => 1, 'explanation' => 'Reconciliation provides evidence that all expected data was processed consistently.'],
];
v2_add_quiz($course, 15, 'Applied check: Scaling up safely', $scalequestions, $category->id, $context->id);

$capstonebrief = '<h2>Scale-up capstone: Operations evidence</h2><p>Work with a teacher-approved public, organisational, or generated dataset containing at least 10,000 rows. If local hardware permits, use 100,000 or more. The learning goal is not a record-size contest; it is a reliable workflow that still works when the file grows.</p>'
    . '<h3>Choose one practical question</h3><ul><li>Which locations or periods need attention?</li><li>How does demand, completion, usage, or cost vary by group and time?</li><li>Where are data-quality problems concentrated?</li></ul>'
    . '<h3>Required workflow</h3><ol><li>Write the question and data dictionary.</li><li>Record size, row count, columns, types, missingness, and privacy decision.</li><li>Build and test the analysis on a small fixture.</li><li>Run the full data with selected columns/types or chunks where appropriate.</li><li>Reconcile row counts, rejected rows, and totals.</li><li>Create a decision-sized table and one chart.</li><li>State a finding, limitation, and recommended next action.</li></ol>';
v2_add_page($course, 16, 'Capstone guide: From large file to decision-sized evidence', $capstonebrief);

$capstoneassign = $capstonebrief . '<h3>Submit</h3><ul><li>Runnable code or notebook</li><li>Small test fixture</li><li>Cleaning and processing log</li><li>Summary table and labelled chart</li><li>300–500 word decision note</li></ul>'
    . '<h3>Rubric</h3><ul><li>Question, data understanding, and privacy: 15</li><li>Test fixture and reproducibility: 15</li><li>Efficient, correct processing: 25</li><li>Validation and reconciliation: 15</li><li>Table, chart, and interpretation: 20</li><li>Explanation and AI declaration: 10</li></ul>' . $aideclaration;
v2_add_assignment($course, 16, 'Scale-up capstone: Operations evidence', $capstoneassign);

$capstoneanswer = '<h2>Teacher reference: Scale-up capstone</h2><p>There is no single required dataset or exact solution. A strong submission demonstrates the following trace:</p>'
    . '<ol><li>A small fixture with a manually verifiable result</li><li>Explicit <code>usecols</code>, <code>dtype</code>, or justified chunk size</li><li>Counts for processed, missing, invalid, and excluded rows</li><li>Chunk totals merged correctly across repeated groups</li><li>A final aggregate small enough to inspect</li><li>A claim supported by numbers, with a limitation and no unsupported causation</li></ol>'
    . '<h3>Oral modification prompts</h3><ul><li>Change the chunk size. Why should the result remain the same?</li><li>Add one invalid row to the fixture. Where is it counted?</li><li>Change a filter threshold and predict the direction of change.</li><li>Explain one AI suggestion you rejected or modified.</li></ul>'
    . '<p>Do not reward sheer row count. Reward correctness, validation, proportional data use, and the learner’s ability to explain and modify the work.</p>';
v2_add_page($course, 16, 'Teacher reference: Scale-up capstone (hidden)', $capstoneanswer, false);

$course->summary = '<p>A practical, low-bandwidth course from Python foundations to validated analysis of larger CSV datasets. Learners build useful programmes, clean and aggregate data, create evidence-based charts, and complete a scale-up capstone. Responsible AI assistance is permitted; verification and explanation are required.</p>';
$course->summaryformat = FORMAT_HTML;
$course->timemodified = time();
$DB->update_record('course', $course);

rebuild_course_cache($course->id, true);

echo json_encode([
    'upgraded' => true, 'version' => 2, 'courseid' => (int) $course->id,
    'shortname' => $course->shortname,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
