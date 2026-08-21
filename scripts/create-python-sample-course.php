<?php
// Create the English Python/data-analysis sample course through Moodle APIs.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->libdir . '/resourcelib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/question/editlib.php';

$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = 1;

use core_question\local\bank\question_version_status;

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$fullname = getenv('PYTHON_COURSE_FULLNAME') ?: 'Python for Data: Foundations in the AI Era';

$existingcourse = $DB->get_record('course', ['shortname' => $shortname]);
if ($existingcourse && ($existingcourse->fullname !== $fullname
        || $DB->count_records('course_modules', ['course' => $existingcourse->id]) > 1)) {
    throw new moodle_exception('shortnametaken', 'error', '', $shortname);
}

\core\session\manager::set_user(get_admin());

function h(string $text): string {
    return s($text);
}

function codeblock(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>'
        . h($code) . '</code></pre>';
}

function lesson_html(array $lesson): string {
    $objectives = '';
    foreach ($lesson['objectives'] as $objective) {
        $objectives .= '<li>' . h($objective) . '</li>';
    }
    $practice = '';
    foreach ($lesson['practice'] as $item) {
        $practice .= '<li>' . h($item) . '</li>';
    }
    return '<div class="python-sample-lesson">'
        . '<p><strong>Estimated study time:</strong> ' . h($lesson['time']) . '</p>'
        . '<h3>Learning objectives</h3><ul>' . $objectives . '</ul>'
        . '<h3>Core idea</h3>' . $lesson['body']
        . '<h3>Worked example</h3>' . codeblock($lesson['code'])
        . '<p>' . $lesson['explanation'] . '</p>'
        . '<h3>Practice: type, run, change, explain</h3><ol>' . $practice . '</ol>'
        . '<div style="border-left:5px solid #5b79a5;padding:.7em 1em;background:#eef4fb">'
        . '<strong>AI checkpoint:</strong> If you use AI, ask for a hint or explanation first. '
        . 'Run its suggestion, test it with a different value, change at least one part, and be ready to explain it.'
        . '</div></div>';
}

function add_page_activity(stdClass $course, int $section, string $name, string $content, bool $visible = true): stdClass {
    global $DB;
    $moduleinfo = (object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST),
        'modulename' => 'page',
        'section' => $section,
        'name' => $name,
        'intro' => '<p>Read, run the examples, and complete the practice before moving on.</p>',
        'introformat' => FORMAT_HTML,
        'content' => $content,
        'contentformat' => FORMAT_HTML,
        'display' => RESOURCELIB_DISPLAY_OPEN,
        'printintro' => 0,
        'printlastmodified' => 0,
        'visible' => $visible ? 1 : 0,
        'visibleoncoursepage' => $visible ? 1 : 0,
        'groupmode' => 0,
        'groupingid' => 0,
        'completion' => 0,
        'showdescription' => 0,
    ];
    return add_moduleinfo($moduleinfo, $course);
}

function add_assignment_activity(stdClass $course, int $section, string $name, string $intro): stdClass {
    global $DB;
    $moduleinfo = (object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST),
        'modulename' => 'assign',
        'section' => $section,
        'name' => $name,
        'intro' => $intro,
        'introformat' => FORMAT_HTML,
        'alwaysshowdescription' => 1,
        'submissiondrafts' => 0,
        'requiresubmissionstatement' => 0,
        'sendnotifications' => 0,
        'sendlatenotifications' => 0,
        'sendstudentnotifications' => 1,
        'duedate' => 0,
        'cutoffdate' => 0,
        'gradingduedate' => 0,
        'allowsubmissionsfromdate' => 0,
        'grade' => 100,
        'attemptreopenmethod' => 'manual',
        'maxattempts' => -1,
        'teamsubmission' => 0,
        'requireallteammemberssubmit' => 0,
        'blindmarking' => 0,
        'markingworkflow' => 0,
        'markingallocation' => 0,
        'assignsubmission_onlinetext_enabled' => 1,
        'assignsubmission_file_enabled' => 1,
        'assignsubmission_file_maxfiles' => 5,
        'assignsubmission_file_maxsizebytes' => 0,
        'assignfeedback_comments_enabled' => 1,
        'visible' => 1,
        'visibleoncoursepage' => 1,
        'groupmode' => 0,
        'groupingid' => 0,
        'completion' => 0,
        'showdescription' => 1,
    ];
    return add_moduleinfo($moduleinfo, $course);
}

function create_multichoice_question(int $categoryid, int $contextid, array $data): stdClass {
    $question = (object) [
        'qtype' => 'multichoice',
        'category' => $categoryid . ',' . $contextid,
    ];
    $answers = [];
    $feedback = [];
    $fractions = [];
    foreach ($data['answers'] as $index => $answer) {
        $answers[] = ['text' => $answer, 'format' => FORMAT_PLAIN];
        $feedback[] = [
            'text' => $index === $data['correct'] ? 'Correct.' : 'Review the example and trace it one step at a time.',
            'format' => FORMAT_HTML,
        ];
        $fractions[] = $index === $data['correct'] ? 1.0 : 0.0;
    }
    $form = (object) [
        'name' => $data['name'],
        'category' => $categoryid . ',' . $contextid,
        'questiontext' => ['text' => '<p>' . h($data['question']) . '</p>', 'format' => FORMAT_HTML],
        'generalfeedback' => ['text' => '<p>' . h($data['explanation']) . '</p>', 'format' => FORMAT_HTML],
        'defaultmark' => 1,
        'penalty' => 0.3333333,
        'status' => question_version_status::QUESTION_STATUS_READY,
        'idnumber' => null,
        'single' => 1,
        'shuffleanswers' => 1,
        'answernumbering' => 'abc',
        'showstandardinstruction' => 1,
        'correctfeedback' => ['text' => 'Correct. Now explain why.', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => 'Partly correct.', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => 'Not yet. Run or trace the code and try again.', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0,
        'answer' => $answers,
        'fraction' => $fractions,
        'feedback' => $feedback,
        'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function add_quiz_activity(stdClass $course, int $section, string $name, array $questions, int $categoryid, int $contextid): stdClass {
    global $DB;
    $moduleinfo = (object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST),
        'modulename' => 'quiz',
        'section' => $section,
        'name' => $name,
        'intro' => '<p>Three questions. You may retry. After submitting, explain each answer before continuing.</p>',
        'introformat' => FORMAT_HTML,
        'timeopen' => 0,
        'timeclose' => 0,
        'timelimit' => 0,
        'overduehandling' => 'autosubmit',
        'graceperiod' => 0,
        'preferredbehaviour' => 'deferredfeedback',
        'attempts' => 0,
        'attemptonlast' => 0,
        'grademethod' => QUIZ_GRADEHIGHEST,
        'decimalpoints' => 0,
        'questiondecimalpoints' => -1,
        'questionsperpage' => 3,
        'navmethod' => QUIZ_NAVMETHOD_FREE,
        'shuffleanswers' => 1,
        'grade' => 100,
        'reviewattempt' => 69888,
        'reviewcorrectness' => 4352,
        'reviewmarks' => 4352,
        'reviewspecificfeedback' => 4352,
        'reviewgeneralfeedback' => 4352,
        'reviewrightanswer' => 4352,
        'reviewoverallfeedback' => 4352,
        'password' => '',
        'quizpassword' => '',
        'subnet' => '',
        'browsersecurity' => '-',
        'delay1' => 0,
        'delay2' => 0,
        'completionattemptsexhausted' => 0,
        'completionminattempts' => 0,
        'visible' => 1,
        'visibleoncoursepage' => 1,
        'groupmode' => 0,
        'groupingid' => 0,
        'completion' => 0,
        'showdescription' => 1,
    ];
    $created = add_moduleinfo($moduleinfo, $course);
    $quiz = $DB->get_record('quiz', ['id' => $created->instance], '*', MUST_EXIST);
    foreach ($questions as $data) {
        $question = create_multichoice_question($categoryid, $contextid, $data);
        quiz_add_quiz_question($question->id, $quiz, 0, 1);
    }
    $quizsettings = \mod_quiz\quiz_settings::create($quiz->id);
    $quizsettings->get_grade_calculator()->recompute_quiz_sumgrades();
    return $created;
}

if ($existingcourse) {
    $course = $existingcourse;
} else {
    $course = create_course((object) [
    'fullname' => $fullname,
    'shortname' => $shortname,
    'category' => 1,
    'format' => 'topics',
    'visible' => 1,
    'enablecompletion' => 0,
    'summary' => '<p>A practical, low-bandwidth introduction to Python and data analysis. '
        . 'Learners build programming foundations, analyse CSV data, create a chart, and explain evidence. '
        . 'Responsible AI assistance is permitted; verification and understanding are required.</p>',
    'summaryformat' => FORMAT_HTML,
    'startdate' => usergetmidnight(time()),
    ]);
}

$sections = [
    0 => ['Welcome and course guide', 'Start here. Understand the outcome, study method, and responsible-AI policy.'],
    1 => ['1. Programs, values, and output', 'Run a first program and learn how Python evaluates expressions.'],
    2 => ['2. Variables, types, input, and calculations', 'Store values and build a small calculation.'],
    3 => ['3. Decisions with conditions', 'Make a program choose using if, elif, and else.'],
    4 => ['4. Repetition with loops', 'Process several values without repeating code by hand.'],
    5 => ['5. Lists and dictionaries', 'Represent collections and labelled records.'],
    6 => ['6. Functions, errors, and testing', 'Organise reusable logic and debug systematically.'],
    7 => ['Foundation project', 'Combine core Python skills in a practical program.'],
    8 => ['7. Tables, CSV, and pandas', 'Move from individual values to tabular data.'],
    9 => ['8. Inspecting and selecting data', 'Ask focused questions of a DataFrame.'],
    10 => ['9. Cleaning data', 'Find missing, invalid, and inconsistent values.'],
    11 => ['10. Grouping and summary statistics', 'Compare groups and calculate useful summaries.'],
    12 => ['11. Visualisation and evidence', 'Choose a chart and explain what it does and does not show.'],
    13 => ['Data analysis project', 'Analyse a supplied dataset and communicate a supported conclusion.'],
    14 => ['Final project and reflection', 'Complete an end-to-end analysis and reflect on your learning and AI use.'],
];
for ($sectionnumber = 1; $sectionnumber <= 14; $sectionnumber++) {
    course_create_sections_if_missing($course, $sectionnumber);
}
foreach ($sections as $number => [$name, $summary]) {
    $record = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $number], '*', MUST_EXIST);
    $record->name = $name;
    $record->summary = '<p>' . h($summary) . '</p>';
    $record->summaryformat = FORMAT_HTML;
    $DB->update_record('course_sections', $record);
}

$context = context_course::instance($course->id);
$questioncategory = (object) [
    'name' => 'Python course checks',
    'contextid' => $context->id,
    'info' => 'Question bank for the reproducible Python sample course.',
    'infoformat' => FORMAT_HTML,
    'stamp' => make_unique_id_code(),
    'parent' => 0,
    'sortorder' => 999,
    'idnumber' => null,
];
$questioncategory->id = $DB->insert_record('question_categories', $questioncategory);

$welcome = <<<'HTML'
<h2>Welcome</h2>
<p>This course is for learners who can use files and a web browser but may never have programmed before. In about 30 hours you will progress from a first Python expression to a small, evidence-based data analysis.</p>
<h3>By the end, you can</h3>
<ul><li>read, run, test, and modify basic Python;</li><li>use conditions, loops, collections, and functions;</li><li>load, inspect, clean, and summarise CSV data with pandas;</li><li>make an appropriate chart with matplotlib;</li><li>state a conclusion and a limitation in your own words;</li><li>use AI assistance without surrendering verification or judgment.</li></ul>
<h3>Study cycle</h3>
<p><strong>Read → Type → Run → Change → Explain.</strong> Do not only read code. Type it, predict the result, run it, change one input, and explain the new result. A local Python installation is enough. Internet access and an AI account are optional.</p>
<h3>Assessment</h3>
<p>Section quizzes check understanding. The foundation assignment checks core Python. The data-analysis assignment checks a guided workflow. The final project asks you to select a question, analyse data, create a chart, and explain the evidence.</p>
HTML;
add_page_activity($course, 0, 'Start here: course guide', $welcome);

$aipolicy = <<<'HTML'
<h2>Responsible AI use</h2>
<p>AI tools are allowed for learners and teachers. The goal is better learning, fewer avoidable errors, and less wasted effort—not copying an answer that nobody has checked.</p>
<h3>The required workflow</h3>
<p><strong>Ask → Read → Run → Check → Modify → Explain.</strong></p>
<ul><li><strong>Good uses:</strong> ask for a simpler explanation, interpret an error, request a hint, review code you wrote, generate an extra practice problem, or check whether an interpretation missed something.</li><li><strong>Do not:</strong> submit unrun code, use code you cannot explain, accept an analysis without comparing it with the data, or present AI-written explanation as your own work.</li></ul>
<h3>AI use declaration for every project</h3>
<ol><li>Did you use an AI tool? Yes or No.</li><li>What did you ask it to help with?</li><li>What did you test, change, or verify yourself?</li><li>What did you learn from using it?</li></ol>
<p>Using AI is not a reason to lose marks. Failing to verify or explain the submitted work is.</p>
HTML;
add_page_activity($course, 0, 'Responsible AI: Ask, Read, Run, Check, Modify, Explain', $aipolicy);

$lessons = [
    1 => [
        'name' => 'Lesson 1: Your first Python program', 'time' => '1.5 hours',
        'objectives' => ['Run a Python statement', 'Distinguish text, numbers, and expressions', 'Predict simple output'],
        'body' => '<p>A program is a sequence of instructions. <code>print()</code> displays a value. Text is written inside quotes; numbers are not. Python evaluates an expression before printing it.</p>',
        'code' => "print(\"Hello, Python!\")\nprint(7 + 5)\nprint(\"7 + 5\")",
        'explanation' => 'The second line prints 12 because Python calculates the expression. The third prints the characters 7 + 5 because they are text.',
        'practice' => ['Predict all three outputs, then run the code.', 'Change the greeting and both numbers.', 'Add one line that multiplies two numbers. Explain the difference between code and output.'],
    ],
    2 => [
        'name' => 'Lesson 2: Variables, types, input, and calculations', 'time' => '2 hours',
        'objectives' => ['Store and update values', 'Use int, float, and str', 'Convert input before calculating'],
        'body' => '<p>A variable gives a value a useful name. <code>input()</code> always returns text, so numerical input must be converted. Use meaningful names and include units when they matter.</p>',
        'code' => "name = input(\"Your name: \")\nhours = float(input(\"Hours studied: \"))\ndays = int(input(\"Number of days: \"))\naverage = hours / days\nprint(name, \"studied\", average, \"hours per day\")",
        'explanation' => 'float accepts a decimal value. int accepts a whole number. The variable names make the calculation readable.',
        'practice' => ['Run with 10 hours and 5 days.', 'Test a decimal such as 7.5 hours.', 'Modify the program to calculate weekly cost from price and quantity.'],
    ],
    3 => [
        'name' => 'Lesson 3: Decisions with conditions', 'time' => '2 hours',
        'objectives' => ['Compare values', 'Write if/elif/else logic', 'Test boundary cases'],
        'body' => '<p>Conditions choose which block runs. Indentation is part of Python syntax. Boundary values—such as exactly 50—deserve deliberate tests.</p>',
        'code' => "score = 68\nif score >= 70:\n    grade = \"A\"\nelif score >= 50:\n    grade = \"Pass\"\nelse:\n    grade = \"Not yet\"\nprint(grade)",
        'explanation' => 'Python checks from top to bottom and stops at the first true condition. With 68, the second condition is true.',
        'practice' => ['Predict results for 49, 50, 69, and 70.', 'Add validation for scores below 0 or above 100.', 'Explain why the order of the conditions matters.'],
    ],
    4 => [
        'name' => 'Lesson 4: Repetition with loops', 'time' => '2 hours',
        'objectives' => ['Iterate through a collection', 'Maintain a running total', 'Recognise a loop boundary'],
        'body' => '<p>A <code>for</code> loop performs the same pattern for each item. An accumulator such as <code>total</code> stores a result that grows during the loop.</p>',
        'code' => "scores = [62, 74, 81, 55]\ntotal = 0\nfor score in scores:\n    total = total + score\naverage = total / len(scores)\nprint(average)",
        'explanation' => 'The loop visits every score once. len(scores) is 4, so the final total is divided by 4.',
        'practice' => ['Trace total after every loop iteration.', 'Add another score and predict the new average.', 'Count how many scores are at least 60.'],
    ],
    5 => [
        'name' => 'Lesson 5: Lists and dictionaries', 'time' => '2 hours',
        'objectives' => ['Access and update a list', 'Represent labelled data with a dictionary', 'Loop through records'],
        'body' => '<p>Lists hold ordered values. Dictionaries map keys to values. A list of dictionaries can represent table-like records before you learn pandas.</p>',
        'code' => "students = [\n    {\"name\": \"Amina\", \"score\": 78},\n    {\"name\": \"Kabelo\", \"score\": 64},\n]\nfor student in students:\n    print(student[\"name\"], student[\"score\"])",
        'explanation' => 'Each student is one dictionary. The loop gives each record a temporary name and retrieves values by key.',
        'practice' => ['Add a third student.', 'Print only students scoring at least 70.', 'Calculate the average without pandas.'],
    ],
    6 => [
        'name' => 'Lesson 6: Functions, errors, and testing', 'time' => '2.5 hours',
        'objectives' => ['Define and call a function', 'Read a traceback from the last line upward', 'Test normal, boundary, and invalid inputs'],
        'body' => '<p>A function names a reusable operation. Parameters are inputs and <code>return</code> sends a result back. Debugging is an evidence process: reproduce the problem, read the error, isolate the cause, make one change, and retest.</p>',
        'code' => "def mean(values):\n    if len(values) == 0:\n        return None\n    return sum(values) / len(values)\n\nprint(mean([4, 7, 10]))\nprint(mean([]))",
        'explanation' => 'The empty-list check prevents division by zero. Returning None makes the missing result explicit.',
        'practice' => ['Test one value, decimals, and an empty list.', 'Write a function that returns the highest score.', 'Introduce a small error, read the traceback, and record how you fixed it.'],
    ],
    8 => [
        'name' => 'Lesson 7: Tables, CSV, and pandas', 'time' => '2 hours',
        'objectives' => ['Describe rows, columns, and headers', 'Load CSV data into a DataFrame', 'Inspect shape, columns, and sample rows'],
        'body' => '<p>CSV is plain text for tabular data. pandas loads it into a DataFrame. Begin every analysis by inspecting—not assuming—the structure.</p>',
        'code' => "from io import StringIO\nimport pandas as pd\n\ncsv = StringIO(\"student,score\\nAmina,78\\nKabelo,64\\nNaledi,85\")\ndf = pd.read_csv(csv)\nprint(df.head())\nprint(df.shape)\nprint(df.dtypes)",
        'explanation' => 'head shows sample rows, shape reports rows and columns, and dtypes shows how pandas interpreted each column.',
        'practice' => ['Predict the shape before running.', 'Add an attendance column and one row.', 'Explain why inspecting dtypes matters before calculation.'],
    ],
    9 => [
        'name' => 'Lesson 8: Inspecting and selecting data', 'time' => '2 hours',
        'objectives' => ['Select columns and filter rows', 'Combine conditions correctly', 'Keep the analysis question visible'],
        'body' => '<p>Select only the data needed for the question. Boolean conditions create True/False masks that filter rows. Parentheses make compound conditions clear.</p>',
        'code' => "high_attendance = df[(df[\"score\"] >= 70) & (df[\"attendance\"] >= 80)]\nprint(high_attendance[[\"student\", \"score\"]])",
        'explanation' => 'The result contains only rows meeting both conditions and only the two requested columns.',
        'practice' => ['Change both thresholds and compare the result.', 'Filter for either a high score or high attendance.', 'Write the question your filter answers in one sentence.'],
    ],
    10 => [
        'name' => 'Lesson 9: Cleaning data', 'time' => '2.5 hours',
        'objectives' => ['Detect missing and invalid values', 'Convert data types safely', 'Document a cleaning decision'],
        'body' => '<p>Real data may contain blanks, spelling differences, impossible values, or numbers stored as text. Never silently delete a problem. Count it, decide how to handle it, and record the decision.</p>',
        'code' => "df[\"score\"] = pd.to_numeric(df[\"score\"], errors=\"coerce\")\nprint(df.isna().sum())\nclean = df[df[\"score\"].between(0, 100)].copy()\nclean[\"district\"] = clean[\"district\"].str.strip().str.title()",
        'explanation' => 'Invalid score text becomes missing, impossible scores are excluded, and district labels are normalised. Each choice should be reported.',
        'practice' => ['Create one blank and one impossible score.', 'Compare row counts before and after cleaning.', 'Write a two-sentence cleaning log.'],
    ],
    11 => [
        'name' => 'Lesson 10: Grouping and summary statistics', 'time' => '2 hours',
        'objectives' => ['Calculate count, mean, median, minimum, and maximum', 'Group records by a category', 'Avoid conclusions unsupported by small groups'],
        'body' => '<p>Summary statistics compress many rows. The mean uses every value and can be pulled by extremes; the median is the middle value. Always report group size alongside a group average.</p>',
        'code' => "summary = clean.groupby(\"district\")[\"score\"].agg([\"count\", \"mean\", \"median\", \"min\", \"max\"])\nprint(summary.sort_values(\"mean\", ascending=False))",
        'explanation' => 'Grouping compares districts, while count warns you when an apparent difference is based on very few records.',
        'practice' => ['Explain a case where median is more useful than mean.', 'Group by a different categorical column.', 'Identify one comparison you should not make from the available data.'],
    ],
    12 => [
        'name' => 'Lesson 11: Visualisation and evidence', 'time' => '2 hours',
        'objectives' => ['Choose a bar chart, line chart, or histogram for a purpose', 'Label a chart clearly', 'Separate observation from explanation'],
        'body' => '<p>Use bars to compare categories, lines for ordered change such as time, and histograms for a distribution. A chart supports a claim only when the axes, units, filters, and limitations are clear.</p>',
        'code' => "import matplotlib.pyplot as plt\nby_district = clean.groupby(\"district\")[\"score\"].mean().sort_values()\nby_district.plot(kind=\"barh\", color=\"#356a9a\")\nplt.xlabel(\"Mean score (0-100)\")\nplt.ylabel(\"District\")\nplt.title(\"Mean score by district\")\nplt.tight_layout()\nplt.show()",
        'explanation' => 'A horizontal bar chart supports category comparison. The title and axis label state the measure and unit.',
        'practice' => ['Explain why a line chart is not ideal here.', 'Add group counts to your analysis notes.', 'Write one observation and one limitation without claiming causation.'],
    ],
];

$quizdata = [
    1 => [
        ['name' => 'Expression or text', 'question' => 'What does print("3 + 4") display?', 'answers' => ['7', '3 + 4', 'An error', '34'], 'correct' => 1, 'explanation' => 'Quoted characters are text, so Python displays 3 + 4.'],
        ['name' => 'Numeric expression', 'question' => 'What does print(3 * 4) display?', 'answers' => ['3 * 4', '7', '12', '34'], 'correct' => 2, 'explanation' => 'Python evaluates multiplication before printing.'],
        ['name' => 'Learning cycle', 'question' => 'Which action best checks understanding after code runs?', 'answers' => ['Copy it unchanged', 'Change an input and explain the new result', 'Delete all comments', 'Memorise the screen'], 'correct' => 1, 'explanation' => 'Changing and explaining tests whether you understand the relationship between code and result.'],
    ],
    2 => [
        ['name' => 'Input type', 'question' => 'What type does input() return?', 'answers' => ['Always int', 'Always float', 'Text (str)', 'A list'], 'correct' => 2, 'explanation' => 'input returns text; convert it before numerical calculation.'],
        ['name' => 'Useful conversion', 'question' => 'Which expression accepts the input 7.5 as a number?', 'answers' => ['int(input())', 'float(input())', 'str(float)', 'print(input)'], 'correct' => 1, 'explanation' => 'float converts decimal text to a numerical value.'],
        ['name' => 'Variable update', 'question' => 'After x = 5 and x = x + 2, what is x?', 'answers' => ['2', '5', '7', 'An error'], 'correct' => 2, 'explanation' => 'The right side uses the old value 5, then the result 7 is stored in x.'],
    ],
    3 => [
        ['name' => 'Boundary condition', 'question' => 'With if score >= 50, which score first passes?', 'answers' => ['49', '50', '51 only', '0'], 'correct' => 1, 'explanation' => 'The >= operator includes the boundary value 50.'],
        ['name' => 'First true branch', 'question' => 'If score is 80, what happens in an if/elif/else chain?', 'answers' => ['Every true branch runs', 'Only the first true branch runs', 'Only else runs', 'No branch runs'], 'correct' => 1, 'explanation' => 'An if/elif/else chain stops at the first true condition.'],
        ['name' => 'Good condition test', 'question' => 'Which test set is best for a pass boundary of 50?', 'answers' => ['0 only', '50 only', '49, 50, and 51', '100 only'], 'correct' => 2, 'explanation' => 'Testing just below, at, and just above the boundary exposes comparison mistakes.'],
    ],
    4 => [
        ['name' => 'Loop total', 'question' => 'Starting total at 0, what is total after looping over [2, 3, 5] and adding each value?', 'answers' => ['3', '5', '10', '15'], 'correct' => 2, 'explanation' => '0 + 2 + 3 + 5 equals 10.'],
        ['name' => 'List length', 'question' => 'What does len([4, 7, 9]) return?', 'answers' => ['2', '3', '4', '20'], 'correct' => 1, 'explanation' => 'The list contains three items.'],
        ['name' => 'Loop purpose', 'question' => 'Why use a loop for many scores?', 'answers' => ['To avoid processing values', 'To repeat one pattern consistently', 'To turn numbers into text', 'To hide errors'], 'correct' => 1, 'explanation' => 'A loop applies the same processing pattern to each item.'],
    ],
    5 => [
        ['name' => 'Dictionary lookup', 'question' => 'For student = {"name": "Amina", "score": 78}, what is student["score"]?', 'answers' => ['Amina', 'score', '78', 'student'], 'correct' => 2, 'explanation' => 'The key score maps to the value 78.'],
        ['name' => 'List indexing', 'question' => 'What is [10, 20, 30][0]?', 'answers' => ['0', '10', '20', '30'], 'correct' => 1, 'explanation' => 'Python list indexes begin at zero.'],
        ['name' => 'Record structure', 'question' => 'Which structure best represents several named student records before pandas?', 'answers' => ['A list of dictionaries', 'One long string only', 'A single number', 'A Boolean'], 'correct' => 0, 'explanation' => 'A list holds records, and each dictionary labels fields with keys.'],
    ],
    6 => [
        ['name' => 'Function result', 'question' => 'What keyword sends a result back from a function?', 'answers' => ['print', 'return', 'input', 'for'], 'correct' => 1, 'explanation' => 'return gives the calculated value to the caller.'],
        ['name' => 'Traceback reading', 'question' => 'Where should you usually begin reading a Python traceback?', 'answers' => ['At the last line', 'At a random line', 'Only at the file name', 'Ignore it'], 'correct' => 0, 'explanation' => 'The last line states the exception type and immediate message.'],
        ['name' => 'Testing empty input', 'question' => 'Why test mean([])?', 'answers' => ['It is a normal large dataset', 'It checks an edge case that can cause division by zero', 'It changes a list to text', 'It guarantees every answer'], 'correct' => 1, 'explanation' => 'An empty input is an important boundary that needs explicit handling.'],
    ],
    8 => [
        ['name' => 'DataFrame shape', 'question' => 'What does a DataFrame shape of (12, 4) mean?', 'answers' => ['12 columns and 4 files', '12 rows and 4 columns', '16 rows', '4 rows and 12 types'], 'correct' => 1, 'explanation' => 'shape reports (number of rows, number of columns).'],
        ['name' => 'First inspection', 'question' => 'What is a good first action after pd.read_csv?', 'answers' => ['Assume all types are correct', 'Inspect head, shape, columns, and dtypes', 'Delete missing rows immediately', 'Build a complex chart'], 'correct' => 1, 'explanation' => 'Inspection reveals structure and possible quality issues before analysis.'],
        ['name' => 'CSV meaning', 'question' => 'In a typical CSV table, what does the header row contain?', 'answers' => ['Column names', 'Only totals', 'Python functions', 'Chart colours'], 'correct' => 0, 'explanation' => 'The header normally names the fields represented by columns.'],
    ],
    10 => [
        ['name' => 'Coerce invalid values', 'question' => 'What does pd.to_numeric(..., errors="coerce") do to invalid numeric text?', 'answers' => ['Makes it zero automatically', 'Turns it into a missing value', 'Deletes the entire file', 'Sorts it'], 'correct' => 1, 'explanation' => 'Coercion converts invalid numeric text to NaN so it can be detected and handled.'],
        ['name' => 'Cleaning record', 'question' => 'Why keep a cleaning log?', 'answers' => ['To hide removed values', 'To make decisions transparent and reproducible', 'To avoid checking counts', 'To replace the source data'], 'correct' => 1, 'explanation' => 'A cleaning log explains what changed, how much, and why.'],
        ['name' => 'Range check', 'question' => 'Which expression checks scores are from 0 through 100 inclusive?', 'answers' => ['score > 0 only', 'score.between(0, 100)', 'score == 100', 'score.isna()'], 'correct' => 1, 'explanation' => 'between includes both endpoints by default.'],
    ],
    11 => [
        ['name' => 'Group count', 'question' => 'Why report count beside each group mean?', 'answers' => ['Count changes labels', 'A mean based on very few records may be unstable', 'It forces all means equal', 'It removes missing values'], 'correct' => 1, 'explanation' => 'Group size is necessary context for judging a comparison.'],
        ['name' => 'Median meaning', 'question' => 'What is the median of [2, 4, 100]?', 'answers' => ['2', '4', '35.3', '100'], 'correct' => 1, 'explanation' => 'After ordering, the middle value is 4.'],
        ['name' => 'Mean sensitivity', 'question' => 'Which statistic is usually more affected by an extreme value?', 'answers' => ['Mean', 'Median', 'Count', 'Column name'], 'correct' => 0, 'explanation' => 'The mean uses the magnitude of every value and is pulled by extremes.'],
    ],
    12 => [
        ['name' => 'Category chart', 'question' => 'Which chart is usually suitable for comparing mean scores across districts?', 'answers' => ['Bar chart', 'Unlabelled line chart', 'Map without values', 'No chart can compare categories'], 'correct' => 0, 'explanation' => 'Bars provide a clear comparison among discrete categories.'],
        ['name' => 'Time trend chart', 'question' => 'Which chart is usually suitable for monthly values in chronological order?', 'answers' => ['Line chart', 'Randomly ordered bars only', 'One text label', 'A dictionary'], 'correct' => 0, 'explanation' => 'A line chart highlights ordered change through time.'],
        ['name' => 'Evidence language', 'question' => 'Which statement is most responsible?', 'answers' => ['The chart proves district causes scores', 'In this dataset, District A has a higher mean; group sizes and other factors should be checked', 'The chart explains everything', 'The smallest bar is an error'], 'correct' => 1, 'explanation' => 'It states the observation while acknowledging limitations and avoiding unsupported causation.'],
    ],
];

foreach ($lessons as $section => $lesson) {
    add_page_activity($course, $section, $lesson['name'], lesson_html($lesson));
    if (isset($quizdata[$section])) {
        add_quiz_activity($course, $section, 'Knowledge check: ' . $lesson['name'], $quizdata[$section], $questioncategory->id, $context->id);
    }
}

$declaration = '<h3>Required AI use declaration</h3><ol><li>Did you use an AI tool? Yes or No.</li><li>What did you ask it to help with?</li><li>What did you run, test, change, or verify yourself?</li><li>What did you learn?</li></ol>';

$foundationassignment = <<<'HTML'
<h2>Foundation project: Score report</h2>
<p>Write a Python program that accepts or stores at least five scores, validates that every score is from 0 to 100, and reports count, mean, minimum, maximum, and the number at or above a pass mark of 50. Put at least one calculation in a function.</p>
<h3>Submit</h3><ul><li>Your <code>.py</code> file or complete code</li><li>Output from a normal test and a boundary/invalid-input test</li><li>A 100–200 word explanation of your design and one debugging decision</li></ul>
<h3>Rubric (100)</h3><ul><li>Correct input/data and validation: 20</li><li>Correct calculations and conditions: 30</li><li>Useful function and readable names: 20</li><li>Tests and evidence: 15</li><li>Explanation and AI declaration: 15</li></ul>
HTML;
add_assignment_activity($course, 7, 'Foundation project: Score report', $foundationassignment . $declaration);

$dataset = <<<'HTML'
<h2>Practice dataset</h2>
<p>Copy the following text into a file named <code>learning_centres.csv</code>. It is small enough to inspect manually and intentionally contains two quality issues.</p>
<pre><code>centre,district,learners,completion_rate,hours_per_week
North 1,north,42,0.81,6
North 2, North ,35,0.74,5
Central 1,Central,58,0.88,7
Central 2,Central,51,,6
South 1,South,29,0.69,4
South 2,South,33,1.20,5
West 1,West,24,0.76,5
West 2,West,27,0.79,6</code></pre>
<p><strong>Data dictionary:</strong> learners is the number enrolled; completion_rate should be between 0 and 1; hours_per_week is scheduled study time. The data is fictional and contains no personal information.</p>
HTML;
add_page_activity($course, 13, 'Dataset: Learning centres (fictional CSV)', $dataset);

$midassignment = <<<'HTML'
<h2>Guided data analysis</h2>
<p>Use the supplied learning-centres CSV. Load and inspect it, normalise district labels, identify the missing completion rate and invalid value above 1, and explain how you handle each. Then calculate learner count and mean valid completion rate by district.</p>
<h3>Submit</h3><ul><li>A notebook or <code>.py</code> file</li><li>A cleaning log with before/after row or missing-value counts</li><li>One labelled chart</li><li>150–250 words: one finding, supporting numbers, and one limitation</li></ul>
<h3>Rubric (100)</h3><ul><li>Inspection and reproducible cleaning: 25</li><li>Correct grouping and calculations: 25</li><li>Appropriate, labelled chart: 20</li><li>Evidence-based interpretation and limitation: 20</li><li>Readable work and AI declaration: 10</li></ul>
HTML;
add_assignment_activity($course, 13, 'Data analysis project: Learning centres', $midassignment . $declaration);

$finalassignment = <<<'HTML'
<h2>Final project: From question to evidence</h2>
<p>Select a teacher-approved CSV dataset that contains no unnecessary personal or sensitive information. Define one answerable question, inspect and clean the data, calculate at least two relevant summaries, create one appropriate chart, and explain the result.</p>
<h3>Required submission</h3><ol><li>Your question and a brief data dictionary</li><li>Original data source or supplied file</li><li>Runnable notebook or Python script</li><li>A cleaning log</li><li>At least one table and one labelled chart</li><li>A 250–400 word report containing a finding, evidence, limitation, and next question</li><li>The AI use declaration</li></ol>
<h3>Rubric (100)</h3><ul><li>Clear, answerable question and data understanding: 15</li><li>Reproducible inspection and cleaning: 20</li><li>Correct analysis: 25</li><li>Appropriate visualisation: 15</li><li>Interpretation, limitation, and communication: 20</li><li>AI disclosure and ability to explain/modify the work: 5</li></ul>
<p>The teacher may ask you to change a filter, explain one line, predict a new result, or rerun the work with a small data change. This is a learning conversation, not a memory test.</p>
HTML;
add_assignment_activity($course, 14, 'Final project: From question to evidence', $finalassignment . $declaration);

$reflection = <<<'HTML'
<h2>Final reflection</h2>
<p>Write 150–250 words answering:</p><ol><li>What can you now do that you could not do before?</li><li>Which error or wrong assumption taught you the most?</li><li>When did AI assistance help, and when did you reject or change its suggestion?</li><li>What will you learn next?</li></ol>
<p>Before finishing, choose one function or analysis step from your project and explain it aloud to another person or in writing without copying your comments.</p>
HTML;
add_page_activity($course, 14, 'Reflection and next steps', $reflection);

$teacherguide = <<<'HTML'
<h2>Teacher guide</h2>
<p><strong>Suggested schedule:</strong> 6–8 weeks part-time or a one-week intensive programme, about 30 learner hours. Keep demonstrations short; reserve most time for typing, prediction, testing, and explanation.</p>
<h3>Facilitation pattern</h3><ol><li>Ask learners to predict before running.</li><li>Let pairs compare explanations, not merely answers.</li><li>Use quiz errors to choose the next demonstration.</li><li>For projects, ask for one live modification or explanation.</li><li>Never require an AI account. Pair or teacher-provided demonstrations are optional.</li></ol>
<h3>Common difficulties</h3><ul><li>Quotes around numbers and failure to convert input</li><li>Indentation and boundary conditions</li><li>Confusing a value with its list index or dictionary key</li><li>Changing several things while debugging</li><li>Assuming CSV types and silently dropping missing data</li><li>Claiming causation from a descriptive chart</li></ul>
<h3>Teacher AI checks</h3><p>Run all generated code, answer every generated quiz before publishing it, check for ambiguous alternatives, verify feedback and grading, and do not delegate final judgment of student work to AI.</p>
<h3>Accessibility and low bandwidth</h3><p>All essential data is embedded as text. Provide offline copies where necessary. Do not assess typing speed or internet availability. Describe the meaning of charts in words and allow equivalent tools when the analysis can be verified.</p>
HTML;
add_page_activity($course, 0, 'Teacher guide (hidden from students)', $teacherguide, false);

$answers = <<<'HTML'
<h2>Model-answer guidance</h2>
<p>Use these as discussion references, not exact-match requirements.</p>
<h3>Foundation project</h3><pre><code>def summarise(scores, pass_mark=50):
    valid = [s for s in scores if 0 &lt;= s &lt;= 100]
    if not valid:
        return None
    return {
        "count": len(valid),
        "mean": sum(valid) / len(valid),
        "minimum": min(valid),
        "maximum": max(valid),
        "passes": sum(s &gt;= pass_mark for s in valid),
    }</code></pre>
<p>Accept loop-based equivalents. Ask how invalid values are reported; silently ignoring them without explanation is weaker evidence.</p>
<h3>Learning-centres analysis</h3><p>A strong response normalises district with <code>str.strip().str.title()</code>, converts completion_rate numerically, reports one missing and one out-of-range value, excludes or repairs only with justification, groups valid rows, includes counts, labels the chart, and notes that this tiny fictional dataset cannot explain causes.</p>
<h3>Final project</h3><p>There is no single correct code listing. Grade the traceable path from question through cleaning and calculation to chart and claim. Reward a defensible limitation and transparent AI use. Ask the learner to modify a threshold or explain why a chart type fits.</p>
HTML;
add_page_activity($course, 14, 'Model answers and grading notes (hidden from students)', $answers, false);

rebuild_course_cache($course->id, true);

echo json_encode([
    'created' => true,
    'courseid' => (int) $course->id,
    'shortname' => $course->shortname,
    'fullname' => $course->fullname,
    'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
