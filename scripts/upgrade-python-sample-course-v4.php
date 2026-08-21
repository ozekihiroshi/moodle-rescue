<?php
// Turn the course quizzes into retryable, misconception-driven learning checks.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->libdir . '/resourcelib.php';
require_once $CFG->dirroot . '/mod/forum/lib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/question/editlib.php';
require_once $CFG->libdir . '/gradelib.php';

use core_question\local\bank\question_version_status;

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
\core\session\manager::set_user(get_admin());

function v4_code(string $code): string {
    return '<pre style="background:#f4f5f7;border:1px solid #d8dce1;padding:1em;overflow:auto"><code>'
        . s($code) . '</code></pre>';
}

function v4_add_page(stdClass $course, int $section, string $name, string $content, bool $visible = true): stdClass {
    global $DB;
    if ($page = $DB->get_record('page', ['course' => $course->id, 'name' => $name])) {
        return $page;
    }
    return add_moduleinfo((object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST),
        'modulename' => 'page', 'section' => $section, 'name' => $name,
        'intro' => '<p>Use this reference while practising and reviewing feedback.</p>',
        'introformat' => FORMAT_HTML, 'content' => $content, 'contentformat' => FORMAT_HTML,
        'display' => RESOURCELIB_DISPLAY_OPEN, 'printintro' => 0, 'printlastmodified' => 0,
        'visible' => $visible ? 1 : 0, 'visibleoncoursepage' => $visible ? 1 : 0,
        'groupmode' => 0, 'groupingid' => 0, 'completion' => 0, 'showdescription' => 0,
    ], $course);
}

function v4_question(string $id, string $prompt, array $choices, int $correct, string $explanation): array {
    return compact('id', 'prompt', 'choices', 'correct', 'explanation');
}

function v4_save_question(int $categoryid, int $contextid, string $prefix, array $data): stdClass {
    $question = (object) ['qtype' => 'multichoice', 'category' => $categoryid . ',' . $contextid];
    $answers = $feedback = $fractions = [];
    foreach ($data['choices'] as $index => [$answer, $why]) {
        $answers[] = ['text' => $answer, 'format' => FORMAT_PLAIN];
        $feedback[] = ['text' => '<p>' . s($why) . '</p>', 'format' => FORMAT_HTML];
        $fractions[] = $index === $data['correct'] ? 1.0 : 0.0;
    }
    $form = (object) [
        'name' => $prefix . $data['id'], 'category' => $categoryid . ',' . $contextid,
        'questiontext' => ['text' => '<p>' . s($data['prompt']) . '</p>', 'format' => FORMAT_HTML],
        'generalfeedback' => ['text' => '<p><strong>Learning point:</strong> ' . s($data['explanation']) . '</p>', 'format' => FORMAT_HTML],
        'defaultmark' => 10, 'penalty' => 0.3333333,
        'status' => question_version_status::QUESTION_STATUS_READY, 'idnumber' => null,
        'single' => 1, 'shuffleanswers' => 1, 'answernumbering' => 'abc', 'showstandardinstruction' => 1,
        'correctfeedback' => ['text' => '<p>Correct. Explain the reason before moving on.</p>', 'format' => FORMAT_HTML],
        'partiallycorrectfeedback' => ['text' => '<p>Partly correct. Compare every condition.</p>', 'format' => FORMAT_HTML],
        'incorrectfeedback' => ['text' => '<p>This is a useful mistake. Read the option feedback, revisit the example, and try again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0, 'answer' => $answers, 'fraction' => $fractions,
        'feedback' => $feedback, 'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v4_feedback_bands(int $quizid): void {
    global $DB;
    $DB->delete_records('quiz_feedback', ['quizid' => $quizid]);
    $bands = [
        [100, 101, '<div><span aria-hidden="true" style="font-size:2rem">🏆</span><h3>Excellent—100%!</h3><p>You checked every idea successfully. Explain one difficult question to another learner or change its values and solve it again.</p></div>'],
        [90, 100, '<div><span aria-hidden="true" style="font-size:2rem">🎉</span><h3>Congratulations—you passed!</h3><p>Your understanding is strong. Review the remaining feedback and try again for 100%. You are very close.</p></div>'],
        [80, 90, '<div><span aria-hidden="true" style="font-size:2rem">🌟</span><h3>Almost there—good progress.</h3><p>You have most of the ideas. Review the questions you missed, test the related example, and retry. The goal is at least 90%, and 100% is within reach.</p></div>'],
        [60, 80, '<div><span aria-hidden="true" style="font-size:2rem">🌱</span><h3>Your knowledge is growing.</h3><p>This attempt is part of learning. Use the feedback to identify two concepts to revisit, run their examples, and try again.</p></div>'],
        [0, 60, '<div><span aria-hidden="true" style="font-size:2rem">🧭</span><h3>You have found what to study next.</h3><p>There is no penalty for trying. Return to the worked examples, trace small values by hand, and make another attempt when ready.</p></div>'],
    ];
    foreach ($bands as [$min, $max, $html]) {
        $DB->insert_record('quiz_feedback', (object) [
            'quizid' => $quizid, 'feedbacktext' => $html,
            'feedbacktextformat' => FORMAT_HTML, 'mingrade' => $min, 'maxgrade' => $max,
        ]);
    }
}

$learningguide = '<h2>Learning checks: Practise until the ideas are yours</h2>'
    . '<p>These activities are not one-time examinations. They help you find what you understand, notice a misconception, read an explanation, and try again.</p>'
    . '<ol><li>Attempt all ten questions without fear of mistakes.</li><li>Submit and read the feedback for every option you missed.</li><li>Return to the connected example and run or trace it.</li><li>Change one value or boundary.</li><li>Try the learning check again. Your highest score is kept.</li></ol>'
    . '<h3>Progress</h3><ul><li><strong>90% or more:</strong> passed—congratulations.</li><li><strong>100%:</strong> mastery target.</li><li><strong>Below 90%:</strong> useful evidence about what to revisit, not a failure.</li></ul>'
    . '<p>Some wrong answers are common mistakes on purpose: reversed division, text used as a number, an excluded boundary, AND confused with OR, mean confused with median, or group percentages averaged incorrectly.</p>';
v4_add_page($course, 0, 'How learning checks work: Retry, learn, and aim for 100%', $learningguide);

$mathpage = '<h2>Analysis toolkit: Boolean logic, sets, and basic statistics</h2>'
    . '<p>These ideas appear inside Python and pandas rather than as a separate mathematics examination.</p>'
    . '<h3>Boolean logic</h3>' . v4_code("priority = (registered >= 30) and (attendance_rate < 75)\nreview = missing_data or impossible_value\nvalid = not duplicate")
    . '<ul><li><strong>AND:</strong> both conditions must be true.</li><li><strong>OR:</strong> at least one condition must be true.</li><li><strong>NOT:</strong> reverses true and false.</li></ul>'
    . '<h3>Sets</h3>' . v4_code("expected = {\"Central\", \"North\", \"South\"}\nobserved = set(df[\"district\"].dropna())\nunexpected = observed - expected\nmissing = expected - observed")
    . '<p>Sets keep unique values. Intersection finds shared values; difference finds values present in one set but not the other.</p>'
    . '<h3>Basic statistics</h3><ul><li><strong>Count:</strong> how many observations.</li><li><strong>Mean:</strong> sum divided by count; sensitive to extreme values.</li><li><strong>Median:</strong> middle ordered value; often more resistant to extremes.</li><li><strong>Range:</strong> maximum minus minimum.</li><li><strong>Weighted rate:</strong> total attended divided by total registered—not usually the simple mean of centre percentages.</li></ul>'
    . v4_code("weighted_rate = df[\"attended\"].sum() / df[\"registered\"].sum() * 100")
    . '<p>A statistic summarises the observed data. It does not by itself establish a cause.</p>';
v4_add_page($course, 10, 'Analysis toolkit: Boolean logic, sets, and basic statistics', $mathpage);

$teacherpage = '<h2>Teacher guide: Misconception-driven learning checks</h2>'
    . '<p>The purpose is retrieval practice and feedback, not ranking learners. Permit unlimited attempts and keep the highest score. Ask learners to explain a changed example rather than memorise an option position.</p>'
    . '<h3>Deliberate distractors</h3><ul><li>Numerator and denominator reversed</li><li>Boundary excluded by &gt; instead of &gt;=</li><li>AND and OR confused</li><li>String concatenation mistaken for addition</li><li>Row percentages averaged instead of aggregating counts</li><li>Mean used without checking extreme values or group size</li><li>Set union confused with intersection</li><li>Correlation described as causation</li></ul>'
    . '<p>If a question has a high error rate, treat it as feedback about instruction. Demonstrate the misconception with small values, then let learners retry.</p>';
v4_add_page($course, 0, 'Teacher guide: Misconception-driven learning checks (hidden)', $teacherpage, false);

$banks = [
    'Knowledge check: Lesson 1: Your first Python program' => [
        v4_question('L1-06', 'Naledi runs print("Registered:", 40). What does Python display?', [['Registered: 40', 'Correct: print displays both arguments separated by a space.'], ['"Registered:", 40', 'Quotes mark source-code text; the quote characters are not displayed.'], ['Registered:40 without any separation', 'print inserts a separator between its arguments by default.'], ['An error', 'Text and a number can be separate print arguments.']], 0, 'print can display labelled values without converting the number to text first.'),
        v4_question('L1-07', 'Which line calculates rather than merely displays the characters 34 + 6?', [['print(34 + 6)', 'Correct: numbers outside quotes are evaluated.'], ['print("34 + 6")', 'Quotes make the expression literal text.'], ['print("40")', 'This displays text 40 but does not calculate it.'], ['34 + "6"', 'A number and text cannot be added directly.']], 0, 'Quoted values are text; unquoted numeric expressions are evaluated.'),
        v4_question('L1-08', 'A report prints 24 where Naledi expected 20. What is the best first debugging step?', [['Check the values and the exact print expression, then reproduce the result', 'Correct: debugging starts from observable evidence.'], ['Rewrite the entire programme', 'A large rewrite hides the original cause.'], ['Assume Python is wrong', 'The interpreter follows the given instructions consistently.'], ['Add unrelated code', 'Unrelated changes make diagnosis harder.']], 0, 'Reproduce, inspect, change one thing, and retest.'),
        v4_question('L1-09', 'Which output is most useful to a centre manager?', [['34', 'The value has no label or unit.'], ['Attendance', 'The measure is named but the value is missing.'], ['Attended learners: 34', 'Correct: the label preserves meaning.'], ['print(attended)', 'This is code, not the report output.']], 2, 'Useful output communicates both the measure and its value.'),
        v4_question('L1-10', 'Why should you predict output before running a small example?', [['To practise tracing the instructions and expose a misunderstanding', 'Correct: prediction makes the mental model testable.'], ['To avoid ever using Python', 'Prediction is followed by execution and comparison.'], ['Because output is always obvious', 'Unexpected results are precisely why prediction helps.'], ['To memorise punctuation only', 'The goal is understanding behaviour, not isolated punctuation.']], 0, 'Predict–run–compare turns execution into a learning check.'),
    ],
    'Knowledge check: Lesson 2: Variables, types, input, and calculations' => [
        v4_question('L2-06', 'input() returns "40". What must happen before dividing it by 50?', [['Convert it with int() or float()', 'Correct: input returns text.'], ['Put more quotes around it', 'More quotes still produce text.'], ['Use print() first', 'Printing does not change the value type.'], ['Multiply it by an empty string', 'String operations do not create a usable number.']], 0, 'Convert input text to an appropriate numerical type before calculation.'),
        v4_question('L2-07', 'Attendance is 34 from 40 registered. Which calculation gives the attendance percentage?', [['34 / 40 * 100', 'Correct: part divided by whole, then converted to percent.'], ['40 / 34 * 100', 'This reverses numerator and denominator and exceeds 100%.'], ['34 + 40 / 100', 'This mixes addition and division and does not represent a rate.'], ['34 / 100', 'The total registered count is the required denominator.']], 0, 'A percentage rate is part / whole × 100.'),
        v4_question('L2-08', 'What happens after x = 10 followed by x = x + 5?', [['x becomes 15', 'Correct: the old value is used, then replaced by the result.'], ['x remains 10', 'Assignment updates the stored value.'], ['x becomes the text "10 + 5"', 'No quotes are present, so numeric addition occurs.'], ['Python compares 10 and 5', 'The single equals sign assigns; it does not compare.']], 0, 'Assignment evaluates the right side before storing it on the left.'),
        v4_question('L2-09', 'Why include units in names or output such as material_cost and training_hours?', [['To prevent values with different meanings from being mixed', 'Correct: units and meaning are part of data quality.'], ['To make every name longer without benefit', 'Clear names reduce errors and explanation time.'], ['Because Python calculates units automatically', 'Python does not know physical or business units by itself.'], ['To convert text to numbers', 'Names do not perform type conversion.']], 0, 'Meaning and units must be explicit because Python only sees values.'),
        v4_question('L2-10', 'Which result should raise a question when calculating a completion rate?', [['82.5%', 'This can be a valid rate.'], ['0%', 'This may be valid if nobody completed.'], ['108%', 'Correct: under this definition, completions should not exceed the relevant whole.'], ['100%', 'This can be valid when all relevant learners completed.']], 2, 'Domain constraints help detect reversed formulas and invalid data.'),
    ],
    'Knowledge check: Lesson 3: Decisions with conditions' => [
        v4_question('L3-06', 'Support is rate < 75. What happens at exactly 75?', [['Support', 'The condition is strictly below 75, so equality is excluded.'], ['The next branch, such as watch', 'Correct: 75 is not below 75.'], ['Both branches', 'An if/elif chain runs only the first matching branch.'], ['An error', 'Comparing a number with 75 is valid.']], 1, 'Boundary symbols determine whether equality belongs to a category.'),
        v4_question('L3-07', 'Priority requires registered >= 30 AND rate < 75. Which centre qualifies?', [['registered 28, rate 70', 'The rate condition is true but registration condition is false.'], ['registered 35, rate 80', 'Registration is sufficient but rate is not below 75.'], ['registered 35, rate 70', 'Correct: both conditions are true.'], ['registered 20, rate 90', 'Neither condition is true.']], 2, 'AND requires every condition to be true.'),
        v4_question('L3-08', 'A row needs review if attendance is missing OR completed > attended. Which row needs no review?', [['attendance missing, counts otherwise valid', 'OR is true because one problem exists.'], ['attendance 30, completed 35', 'Completed exceeds attendance.'], ['attendance 30, completed 25', 'Correct: neither review condition is true.'], ['attendance missing, completed 50', 'Both problems make the OR expression true.']], 2, 'OR is false only when all its conditions are false.'),
        v4_question('L3-09', 'Which test set best checks thresholds at 75 and 85?', [['75 and 85 only', 'Exact boundaries matter, but nearby values are also needed.'], ['0 and 100 only', 'Extremes do not expose off-by-one boundary errors.'], ['74.9, 75, 84.9, 85', 'Correct: just below and exactly at each boundary.'], ['80 only', 'A middle value tests only one branch.']], 2, 'Test below, at, and above important boundaries.'),
        v4_question('L3-10', 'Why should impossible values be checked before ordinary classification?', [['Otherwise -5% might be labelled support as though it were valid', 'Correct: validity and business category are different decisions.'], ['Because every invalid value is automatically zero', 'Python does not make that domain decision.'], ['Because conditions cannot compare negative numbers', 'Python can compare them, but the result may be misleading.'], ['To make the programme longer', 'Validation protects meaning, not code length.']], 0, 'Validate the domain before assigning an operational category.'),
    ],
    'Knowledge check: Lesson 4: Repetition with loops' => [
        v4_question('L4-06', 'total starts at 0 and a loop adds [3, 4, 5]. What is total?', [['5', 'This is only the final item.'], ['7', 'This omits one item.'], ['12', 'Correct: 0 + 3 + 4 + 5.'], ['60', 'That would multiply rather than add.']], 2, 'An accumulator retains the growing result across iterations.'),
        v4_question('L4-07', 'Why must total usually be initialised before the loop?', [['So every iteration can update an existing value', 'Correct: the accumulator needs a known starting state.'], ['So the loop runs only once', 'Initialisation does not limit iteration count.'], ['To turn a list into text', 'The accumulator type depends on the intended operation.'], ['Because lists start at one', 'List indexing and accumulator initialisation are separate ideas.']], 0, 'Initialise state once before repeatedly updating it.'),
        v4_question('L4-08', 'A loop should count valid values in [20, -1, 25]. What count is correct if negatives are invalid?', [['3', 'This counts the invalid marker.'], ['2', 'Correct: 20 and 25 are valid.'], ['44', 'That is not a count.'], ['1', 'There are two non-negative values.']], 1, 'Filtering and counting are separate from summing.'),
        v4_question('L4-09', 'What is the common error if count += 1 is outside the loop?', [['It usually increments once rather than once per valid record', 'Correct: indentation controls repetition.'], ['It increments for every item automatically', 'Outside the loop it executes once.'], ['It changes count to text', 'Indentation does not change the type.'], ['It sorts the list', 'No sorting operation is present.']], 0, 'Indentation determines which operations repeat.'),
        v4_question('L4-10', 'Which approach is safest when a list might be empty before calculating total / len(values)?', [['Divide immediately', 'An empty list produces division by zero.'], ['Check len(values) before division', 'Correct: handle the boundary explicitly.'], ['Add a fake value silently', 'That changes the data.'], ['Convert the list to a string', 'That does not make the mean meaningful.']], 1, 'Empty collections need an explicit decision before division.'),
    ],
    'Knowledge check: Lesson 5: Lists and dictionaries' => [
        v4_question('L5-06', 'For centre = {"registered": 40, "attended": 34}, which expression retrieves 34?', [['centre["attended"]', 'Correct: dictionaries retrieve values by key.'], ['centre[1]', 'A dictionary is not accessed by list position here.'], ['centre.attended', 'Ordinary Python dictionaries do not use attribute syntax.'], ['centre[34]', '34 is the value, not the key.']], 0, 'Dictionary keys preserve field meaning.'),
        v4_question('L5-07', 'Which collection is best for the unique observed district labels?', [['A set', 'Correct: sets keep unique values.'], ['A repeated string', 'A string does not directly model unique categories.'], ['One integer', 'A single number cannot hold labels.'], ['A Boolean', 'A Boolean holds only true or false.']], 0, 'Sets are useful for unique categories and membership checks.'),
        v4_question('L5-08', 'expected = {Central, North, South}; observed = {Central, North, West}. What is observed - expected?', [['{Central, North}', 'Those values are shared, not unexpected.'], ['{South}', 'South is expected but missing from observed.'], ['{West}', 'Correct: West appears only in observed.'], ['{Central, North, South, West}', 'That is the union.']], 2, 'Set difference A - B keeps values found in A but not B.'),
        v4_question('L5-09', 'What does expected & observed represent for two Python sets?', [['Their shared values', 'Correct: & is set intersection.'], ['Every value from either set', 'That is union.'], ['Values only in expected', 'That is a set difference.'], ['A numeric multiplication', 'For sets, & performs intersection.']], 0, 'Intersection identifies values present in both sets.'),
        v4_question('L5-10', 'Why is a list of dictionaries a useful bridge to pandas?', [['Each dictionary resembles a labelled row and the list holds many rows', 'Correct.'], ['It removes all labels', 'Dictionary keys add labels.'], ['It guarantees every real dataset is clean', 'Structure does not guarantee quality.'], ['It can store only one centre', 'The list can hold many dictionaries.']], 0, 'The row-and-column mental model transfers naturally to a DataFrame.'),
    ],
    'Knowledge check: Lesson 6: Functions, errors, and testing' => [
        v4_question('L6-06', 'What is the difference between print(result) and return result inside a function?', [['return sends a value to the caller; print only displays it', 'Correct.'], ['They are always identical', 'Printed output cannot be reliably reused as the returned value.'], ['print stops the function and return does not', 'return ends the function path; print normally does not.'], ['return converts the result to text', 'return preserves the value type.']], 0, 'Return values can be tested, stored, and reused.'),
        v4_question('L6-07', 'percentage(part, whole) receives whole = 0. What is the important first action?', [['Handle the invalid denominator before division', 'Correct.'], ['Divide and hope Python estimates it', 'Division by zero raises an error.'], ['Return 100 automatically', 'That invents a result without justification.'], ['Change part to text', 'The denominator problem remains.']], 0, 'Guard conditions protect functions at domain boundaries.'),
        v4_question('L6-08', 'Where should you usually begin reading a traceback?', [['The last line describing the exception', 'Correct: it states the immediate error type and message.'], ['Only the first import line', 'The relevant error is usually described at the end.'], ['A random middle line', 'Tracebacks have a useful call sequence.'], ['Ignore it and ask for replacement code', 'The traceback is direct evidence.']], 0, 'Read the final error, then trace upward to your code location.'),
        v4_question('L6-09', 'Which tests best support cost_per_completion?', [['One normal value only', 'A normal case is necessary but insufficient.'], ['Normal, zero denominator, negative input, and a decimal cost', 'Correct: normal and boundary/invalid cases.'], ['Only a very large value', 'Size alone does not test domain boundaries.'], ['No tests if AI wrote it', 'AI-generated code still requires verification.']], 1, 'Tests should cover normal, boundary, and invalid inputs.'),
        v4_question('L6-10', 'Why change one thing at a time while debugging?', [['It helps connect the change to the observed result', 'Correct.'], ['Python allows only one edit per file', 'Python has no such restriction.'], ['It guarantees the first change is correct', 'It improves evidence but does not guarantee correctness.'], ['It prevents running tests', 'You should retest after each focused change.']], 0, 'Controlled changes make cause and effect easier to identify.'),
    ],
    'Knowledge check: Lesson 7: Tables, CSV, and pandas' => [
        v4_question('L7-06', 'df.shape is (250000, 9). What does this mean?', [['250,000 rows and 9 columns', 'Correct.'], ['9 rows and 250,000 columns', 'The order is rows, columns.'], ['250,009 total cells', 'Cells would be rows multiplied by columns.'], ['The file has 9 errors', 'Shape describes dimensions, not quality.']], 0, 'DataFrame shape is (rows, columns).'),
        v4_question('L7-07', 'Why inspect dtypes before calculating completion rates?', [['A numeric-looking column may have been read as text', 'Correct.'], ['dtypes automatically fixes every error', 'Inspection reports types; it does not decide all corrections.'], ['Rates require every column to be text', 'Rate inputs must be numerical.'], ['It changes the source CSV', 'Reading dtypes does not modify the source.']], 0, 'Type inspection catches invalid assumptions before calculation.'),
        v4_question('L7-08', 'Which command is most useful for seeing a small sample without printing 250,000 rows?', [['df.head()', 'Correct.'], ['print every row', 'That is slow and hard to inspect.'], ['df.shape only', 'Shape gives dimensions but not sample values.'], ['Delete most rows first', 'Do not destroy data merely to inspect it.']], 0, 'Use samples and summaries to inspect large tables.'),
        v4_question('L7-09', 'What should happen before trusting a newly calculated column?', [['Check several rows manually with known values', 'Correct.'], ['Assume vectorised code cannot be wrong', 'A fast operation can repeat a wrong formula quickly.'], ['Hide the source columns', 'Source fields help verify the result.'], ['Round everything to zero decimals first', 'Premature rounding can hide discrepancies.']], 0, 'Manually verified fixtures anchor confidence in column calculations.'),
        v4_question('L7-10', 'A required column is absent. What is the safest response?', [['Stop with a clear message or handle the schema difference explicitly', 'Correct.'], ['Silently use an unrelated column', 'That corrupts meaning.'], ['Invent zeros for every row without documenting it', 'That changes the data and hides the problem.'], ['Continue and ignore later errors', 'Failing clearly prevents misleading results.']], 0, 'Validate required schema before analysis.'),
    ],
    'Knowledge check: Lesson 9: Cleaning data' => [
        v4_question('L9-06', 'What does pd.to_numeric(values, errors="coerce") do to "unknown"?', [['Converts it to a missing value', 'Correct.'], ['Converts it to zero', 'Coercion uses NaN, not an assumed zero.'], ['Deletes the entire row immediately', 'Conversion does not automatically delete rows.'], ['Leaves it as valid numeric text', 'The invalid value cannot be parsed numerically.']], 0, 'Coercion exposes invalid numeric text as missing for explicit handling.'),
        v4_question('L9-07', 'Why count invalid rows before excluding them?', [['To preserve an audit trail and measure the problem', 'Correct.'], ['Because counting automatically repairs them', 'Counting diagnoses; it does not repair.'], ['To make the dataset larger', 'Counting does not add rows.'], ['Because missing values are always harmless', 'Missingness can bias results.']], 0, 'Cleaning decisions should be measurable and reproducible.'),
        v4_question('L9-08', 'A million rows contain 2,000 invalid records. What percentage is invalid?', [['0.02%', '2,000 / 1,000,000 is 0.002, or 0.2%.'], ['0.2%', 'Correct.'], ['2%', 'That would be 20,000 rows.'], ['20%', 'That would be 200,000 rows.']], 1, 'Rate = count / total × 100; report both rate and count.'),
        v4_question('L9-09', 'Which method best finds repeated centre-month-course records?', [['duplicated with those fields as the subset', 'Correct.'], ['mean()', 'Mean calculates an average.'], ['head()', 'A sample may miss duplicates elsewhere.'], ['sort_values() alone', 'Sorting can help inspect but does not identify duplicates by itself.']], 0, 'Define the business key before detecting duplicates.'),
        v4_question('L9-10', 'Why normalise " central " and "Central" before grouping?', [['Otherwise one district may be split into separate categories', 'Correct.'], ['To change all districts to numbers', 'Normalisation here standardises text labels.'], ['Because spaces are arithmetic operators', 'Whitespace affects text equality, not arithmetic.'], ['To remove the original source permanently', 'Keep raw data or a cleaning log.']], 0, 'Consistent category labels are necessary for correct grouping.'),
    ],
    'Knowledge check: Lesson 10: Grouping and summary statistics' => [
        v4_question('L10-06', 'What is the median of [20, 21, 22, 100]?', [['21', 'For an even count, use the mean of the two middle values.'], ['21.5', 'Correct: (21 + 22) / 2.'], ['40.75', 'That is the mean, pulled upward by 100.'], ['100', 'That is the maximum.']], 1, 'The median is based on ordered middle position and is less affected by the extreme value.'),
        v4_question('L10-07', 'Which statistic is usually more affected by one extreme material cost?', [['Mean', 'Correct.'], ['Median', 'Median is usually more resistant.'], ['Count', 'The number of records does not depend on value magnitude.'], ['Column name', 'A label is not a statistic.']], 0, 'The mean uses every magnitude, so extreme values can pull it strongly.'),
        v4_question('L10-08', 'Centre A has 9/10 attendance and B has 50/100. What is the combined attendance rate?', [['70%', 'This is the unweighted mean of 90% and 50%, which ignores group size.'], ['59/110 ≈ 53.6%', 'Correct: combine counts before dividing.'], ['90%', 'This ignores Centre B.'], ['50%', 'This ignores Centre A.']], 1, 'For a combined rate, sum numerators and denominators before division.'),
        v4_question('L10-09', 'Why report group count beside a group mean?', [['A mean from very few records may be unstable or misleading', 'Correct.'], ['Count forces all means to be equal', 'It provides context but does not change the mean.'], ['Count proves causation', 'No descriptive count proves a cause.'], ['It removes extreme values', 'Count alone does not remove anything.']], 0, 'Sample/group size is essential context for a summary.'),
        v4_question('L10-10', 'A set contains {Python, Data, Python}. How many unique values remain?', [['3', 'Sets remove the repeated Python value.'], ['2', 'Correct: Python and Data.'], ['1', 'There are two distinct labels.'], ['0', 'The set is not empty.']], 1, 'Sets retain unique values, which helps inspect categories.'),
    ],
    'Knowledge check: Lesson 11: Visualisation and evidence' => [
        v4_question('L11-06', 'Which chart best shows monthly attendance rate in chronological order?', [['Line chart', 'Correct.'], ['Randomly ordered pie slices', 'This hides time order.'], ['One bar without a month', 'It cannot show change over time.'], ['A list of Python errors', 'Errors are not a visual summary.']], 0, 'A line chart supports ordered change through time.'),
        v4_question('L11-07', 'Which chart best compares completion rates for four course categories?', [['Bar chart', 'Correct.'], ['Line chart implying continuous time', 'The categories have no natural time order.'], ['Twenty million raw points', 'Aggregate first to the decision level.'], ['An unlabelled axis', 'Labels are required regardless of chart type.']], 0, 'Bars clearly compare a small set of categories.'),
        v4_question('L11-08', 'A district has higher completion after a new programme. What can the chart alone establish?', [['An association in the observed data, not necessarily causation', 'Correct.'], ['The programme definitely caused every improvement', 'Other factors or selection may explain the change.'], ['No information at all', 'The chart can describe the observed pattern.'], ['The data is automatically error-free', 'Visualisation does not validate the source by itself.']], 0, 'Describe evidence precisely and separate association from cause.'),
        v4_question('L11-09', 'Why can a truncated vertical axis exaggerate a small difference?', [['It removes visual context for the magnitude', 'Correct.'], ['It changes every source value', 'The data may be unchanged while perception changes.'], ['It always makes the chart invalid', 'Sometimes truncation is defensible if clearly marked, but caution is needed.'], ['It converts means to medians', 'Axis limits do not change the statistic.']], 0, 'Axis choices influence perception and must be transparent.'),
        v4_question('L11-10', 'Before plotting 20 million transactions by month, what should usually happen?', [['Aggregate to monthly values and validate the totals', 'Correct.'], ['Plot every raw row to make the chart more accurate', 'Overplotting is slow and often less informative.'], ['Delete random months', 'That changes the question.'], ['Convert every number to a label', 'Charts require numerical values.']], 0, 'Reduce large data to the level of the question before plotting.'),
    ],
    'Applied check: Scaling up safely' => [
        v4_question('L12-04', 'Why specify usecols when a 60-column file needs only district and amount?', [['It reduces memory and clarifies the analysis boundary', 'Correct.'], ['It adds 58 empty columns', 'It selects rather than adds columns.'], ['It automatically proves correctness', 'Validation is still necessary.'], ['It encrypts the file', 'Column selection is not encryption.']], 0, 'Read only data needed for the question when practical.'),
        v4_question('L12-05', 'Why specify dtype for stable large-file processing?', [['To make interpretation and memory use more predictable', 'Correct.'], ['To repair every invalid value automatically', 'Explicit types can expose errors but do not decide every repair.'], ['To sort all rows', 'Types do not imply sorting.'], ['To remove the header', 'dtype controls column interpretation.']], 0, 'Explicit schema reduces surprises across chunks and file versions.'),
        v4_question('L12-06', 'Chunk 1 has district total 100 and chunk 2 has 60. What merged total is correct?', [['60', 'This overwrites the earlier chunk.'], ['100', 'This ignores the later chunk.'], ['160', 'Correct: group totals must accumulate across chunks.'], ['40', 'This subtracts without reason.']], 2, 'Chunk aggregation requires merging repeated groups across all chunks.'),
        v4_question('L12-07', 'Can you average two chunk means to get the overall mean?', [['Always', 'Only when chunk sizes are equal or weights are applied.'], ['Never under any circumstances', 'A weighted combination can be correct.'], ['Only with equal chunk sizes, or by weighting with counts/sums', 'Correct.'], ['Only after converting means to text', 'Text conversion is irrelevant.']], 2, 'Overall mean needs total sum and total count, or an equivalent weighted calculation.'),
        v4_question('L12-08', 'expected rows = 250,000 but processed rows = 240,000. What should happen?', [['Investigate before reporting results', 'Correct.'], ['Ignore the difference because the chart looks reasonable', 'Missing rows can bias every result.'], ['Multiply totals by 250,000', 'Scaling does not identify why rows are missing.'], ['Rename the output file', 'The reconciliation failure remains.']], 0, 'Row-count reconciliation detects skipped or rejected data.'),
        v4_question('L12-09', 'Why test chunk logic first on a tiny known fixture?', [['The expected totals can be calculated manually', 'Correct.'], ['Small data guarantees production data is clean', 'It validates logic, not all future inputs.'], ['Chunking works only on small data', 'Chunking is intended for larger data.'], ['It eliminates the need for full-run checks', 'Both fixture tests and full reconciliation are needed.']], 0, 'A known fixture makes processing logic independently verifiable.'),
        v4_question('L12-10', 'Which statement about larger data is most responsible?', [['More rows automatically make every conclusion true', 'Size does not remove bias, bad definitions, or quality problems.'], ['Efficiency, validity, provenance, and privacy all still matter', 'Correct.'], ['Personal identifiers should always be retained', 'Collect and retain only data necessary for the question.'], ['Reconciliation is optional when code runs without errors', 'Successful execution does not prove complete or correct processing.']], 1, 'Scale increases the importance of definitions, validation, provenance, and proportional data use.'),
    ],
];

// This reproducible development course intentionally carries no attempt history.
foreach ($DB->get_records('quiz', ['course' => $course->id]) as $quizwithattempts) {
    quiz_delete_all_attempts($quizwithattempts);
}

$context = context_course::instance($course->id);
$category = $DB->get_record('question_categories', ['contextid' => $context->id, 'name' => 'Python course checks'], '*', MUST_EXIST);

foreach ($banks as $quizname => $newquestions) {
    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname], '*', MUST_EXIST);
    $currentcount = (int) $DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
    $needed = 10 - $currentcount;
    if ($needed < 0) {
        throw new RuntimeException("Quiz {$quizname} has more than 10 questions.");
    }
    if ($needed > count($newquestions)) {
        throw new RuntimeException("Quiz {$quizname} needs {$needed} questions but the v4 bank has only " . count($newquestions) . '.');
    }
    foreach (array_slice($newquestions, 0, $needed) as $data) {
        $questionname = $shortname . ' mastery: ' . $data['id'];
        if ($DB->record_exists('question', ['name' => $questionname])) {
            throw new RuntimeException("Question exists but is not slotted: {$questionname}");
        }
        $question = v4_save_question($category->id, $context->id, $shortname . ' mastery: ', $data);
        quiz_add_quiz_question($question->id, $quiz, 0, 10);
    }

    $DB->set_field('quiz_slots', 'maxmark', 10, ['quizid' => $quiz->id]);
    $quiz->attempts = 0;
    $quiz->grademethod = QUIZ_GRADEHIGHEST;
    $quiz->grade = 100;
    $quiz->decimalpoints = 0;
    $quiz->questiondecimalpoints = 0;
    $quiz->questionsperpage = 5;
    $quiz->preferredbehaviour = 'deferredfeedback';
    $quiz->reviewcorrectness = 0x01100;
    $quiz->reviewmarks = 0x01100;
    $quiz->reviewspecificfeedback = 0x01100;
    $quiz->reviewgeneralfeedback = 0x01100;
    $quiz->reviewrightanswer = 0x01100;
    $quiz->reviewoverallfeedback = 0x01100;
    $quiz->intro = '<div style="border-left:5px solid #356a9a;padding:.8em 1em;background:#eef4fb">'
        . '<h3>This is a learning check, not a one-time test.</h3>'
        . '<p>You may try as many times as you need. Your highest score is kept. Submit all ten questions to see your score and detailed explanations.</p>'
        . '<p><strong>90% passes. Aim for 100%.</strong> A wrong answer identifies an idea to practise; it is not a penalty for learning.</p>'
        . '</div>';
    $quiz->introformat = FORMAT_HTML;
    $quiz->timemodified = time();
    $DB->update_record('quiz', $quiz);

    $settings = \mod_quiz\quiz_settings::create($quiz->id);
    $settings->get_grade_calculator()->recompute_quiz_sumgrades();
    $gradeitem = \grade_item::fetch([
        'courseid' => $course->id, 'itemtype' => 'mod', 'itemmodule' => 'quiz',
        'iteminstance' => $quiz->id, 'outcomeid' => null,
    ]);
    if (!$gradeitem) {
        throw new RuntimeException("Grade item not found for quiz {$quizname}");
    }
    $gradeitem->gradepass = 90;
    $gradeitem->grademax = 100;
    $gradeitem->update();
    v4_feedback_bands($quiz->id);
}

$marker = 'PYAI-V4-ANNOUNCEMENT';
$newsforum = $DB->get_record('forum', ['course' => $course->id, 'type' => 'news']);
if ($newsforum && !$DB->record_exists_select('forum_posts', $DB->sql_like('message', ':marker'), ['marker' => '%' . $marker . '%'])) {
    forum_add_discussion((object) [
        'course' => $course->id, 'forum' => $newsforum->id,
        'name' => 'Learning checks: Try again, learn from feedback, and aim for 100%',
        'message' => '<p>Course quizzes are now learning checks. Each has ten questions and may be attempted without limit. Your highest score is recorded. A score of 90% passes, and 100% is the mastery target.</p><p>Wrong options represent common misunderstandings on purpose. Submit, read the explanation, run the related example, and try again. Progress is something to celebrate.</p><p style="display:none">' . $marker . '</p>',
        'messageformat' => FORMAT_HTML, 'messagetrust' => 0, 'mailnow' => 0,
        'groupid' => -1, 'itemid' => 0,
    ], null, null, get_admin()->id);
}

rebuild_course_cache($course->id, true);
echo json_encode([
    'upgraded' => true, 'version' => 4, 'courseid' => (int) $course->id,
    'shortname' => $course->shortname, 'quizzes' => count($banks),
    'questionsperquiz' => 10, 'gradepass' => 90,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
