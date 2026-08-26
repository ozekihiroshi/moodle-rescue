<?php
// Add the missing Lesson 8 learning check and announce notebook integration.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';

$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = 1;

require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';
require_once $CFG->dirroot . '/mod/forum/lib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/question/editlib.php';
require_once $CFG->libdir . '/gradelib.php';

use core_question\local\bank\question_version_status;

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
\core\session\manager::set_user(get_admin());
function v5_announcement_posts(int $courseid, string $marker): array {
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


function v5_question(int $categoryid, int $contextid, string $prefix, array $data): stdClass {
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
        'incorrectfeedback' => ['text' => '<p>This is a useful mistake. Read the option feedback, run the connected notebook example, and try again.</p>', 'format' => FORMAT_HTML],
        'shownumcorrect' => 0, 'answer' => $answers, 'fraction' => $fractions,
        'feedback' => $feedback, 'hint' => [],
    ];
    return question_bank::get_qtype('multichoice')->save_question($question, $form);
}

function v5_feedback_bands(int $quizid): void {
    global $DB;
    $DB->delete_records('quiz_feedback', ['quizid' => $quizid]);
    $bands = [
        [100, 101, '<div><span aria-hidden="true" style="font-size:2rem">&#127881;</span><h3>Excellent — 100%!</h3><p>You checked every idea successfully. Explain one difficult question, then change its values and solve it again.</p></div>'],
        [90, 100, '<div><span aria-hidden="true" style="font-size:2rem">&#127942;</span><h3>Congratulations — you passed!</h3><p>Review any remaining feedback and try again for 100%. You are very close.</p></div>'],
        [80, 90, '<div><span aria-hidden="true" style="font-size:2rem">&#128640;</span><h3>Almost there — good progress.</h3><p>Run the connected example, review what you missed, and retry. The goal is at least 90%.</p></div>'],
        [60, 80, '<div><span aria-hidden="true" style="font-size:2rem">&#127793;</span><h3>Your knowledge is growing.</h3><p>Use the feedback to choose two ideas to practise in Python Lab, then try again.</p></div>'],
        [0, 60, '<div><span aria-hidden="true" style="font-size:2rem">&#128269;</span><h3>You found what to study next.</h3><p>There is no penalty for trying. Trace small values, run the notebook, and make another attempt.</p></div>'],
    ];
    foreach ($bands as [$min, $max, $html]) {
        $DB->insert_record('quiz_feedback', (object) [
            'quizid' => $quizid, 'feedbacktext' => $html,
            'feedbacktextformat' => FORMAT_HTML, 'mingrade' => $min, 'maxgrade' => $max,
        ]);
    }
}

$quizname = 'Knowledge check: Lesson 8: Inspecting and selecting data';
$quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname]);
if (!$quiz) {
    $created = add_moduleinfo((object) [
        'module' => $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST),
        'modulename' => 'quiz', 'section' => 9, 'name' => $quizname,
        'intro' => '<div style="border-left:5px solid #356a9a;padding:.8em 1em;background:#eef4fb">'
            . '<h3>This is a learning check, not a one-time test.</h3>'
            . '<p>You may try as many times as needed. Submit all ten questions to see your score and explanations.</p>'
            . '<p><strong>90% passes. Aim for 100%.</strong> Use a wrong answer to choose what to practise in Python Lab.</p></div>',
        'introformat' => FORMAT_HTML, 'timeopen' => 0, 'timeclose' => 0, 'timelimit' => 0,
        'overduehandling' => 'autosubmit', 'graceperiod' => 0,
        'preferredbehaviour' => 'deferredfeedback', 'attempts' => 0, 'attemptonlast' => 0,
        'grademethod' => QUIZ_GRADEHIGHEST, 'decimalpoints' => 0, 'questiondecimalpoints' => 0,
        'questionsperpage' => 5, 'navmethod' => QUIZ_NAVMETHOD_FREE, 'shuffleanswers' => 1,
        'grade' => 100, 'reviewattempt' => 0x11100, 'reviewcorrectness' => 0x01100,
        'reviewmarks' => 0x01100, 'reviewspecificfeedback' => 0x01100,
        'reviewgeneralfeedback' => 0x01100, 'reviewrightanswer' => 0x01100,
        'reviewoverallfeedback' => 0x01100, 'password' => '', 'quizpassword' => '',
        'subnet' => '', 'browsersecurity' => '-', 'delay1' => 0, 'delay2' => 0,
        'completionattemptsexhausted' => 0, 'completionminattempts' => 0,
        'visible' => 1, 'visibleoncoursepage' => 1, 'groupmode' => 0,
        'groupingid' => 0, 'completion' => 0, 'showdescription' => 1,
    ], $course);
    $quiz = $DB->get_record('quiz', ['id' => $created->instance], '*', MUST_EXIST);

    $context = context_course::instance($course->id);
    $category = $DB->get_record('question_categories', [
        'contextid' => $context->id, 'name' => 'Python course checks',
    ], '*', MUST_EXIST);
    $questions = [
        ['id' => 'L8-01', 'prompt' => 'Which operator combines two pandas Series conditions when both must be true?', 'choices' => [['&', 'Correct: pandas uses element-wise & for AND.'], ['and', 'Python and expects one truth value and cannot combine whole Series.'], ['|', '| means element-wise OR.'], ['=', '= assigns rather than combines conditions.']], 'correct' => 0, 'explanation' => 'Use &, with each pandas condition in parentheses, when both conditions must hold.'],
        ['id' => 'L8-02', 'prompt' => 'Which operator keeps rows matching at least one of two pandas conditions?', 'choices' => [['&', '& requires both conditions.'], ['|', 'Correct: | means element-wise OR.'], ['not', 'not does not combine two Series.'], ['+', 'Addition does not express the selection rule.']], 'correct' => 1, 'explanation' => 'OR broadens a filter because either condition may be true.'],
        ['id' => 'L8-03', 'prompt' => 'Why are parentheses needed in df[(df["registered"] >= 30) & (df["attendance_rate"] < 80)]?', 'choices' => [['They make each comparison a complete Boolean Series before combination', 'Correct.'], ['They turn numbers into text', 'Parentheses group operations; they do not convert types.'], ['They delete missing rows', 'Filtering and missing-value handling are separate decisions.'], ['They sort the result', 'No sorting operation appears.']], 'correct' => 0, 'explanation' => 'Parentheses make operator precedence and each condition explicit.'],
        ['id' => 'L8-04', 'prompt' => 'registered >= 30 is true, but attendance_rate < 80 is false. What is their AND result?', 'choices' => [['True', 'AND needs both conditions true.'], ['False', 'Correct.'], ['Missing', 'Both Boolean values are known.'], ['80', 'The result is Boolean, not the threshold.']], 'correct' => 1, 'explanation' => 'AND is true only when both input conditions are true.'],
        ['id' => 'L8-05', 'prompt' => 'Which expression selects only centre_name and attendance_rate columns?', 'choices' => [['df[["centre_name", "attendance_rate"]]', 'Correct.'], ['df["centre_name", "attendance_rate"]', 'A list of column names is required inside the brackets.'], ['df.rows(2)', 'DataFrame has no rows method for this selection.'], ['df.mean()', 'mean calculates rather than selects these columns.']], 'correct' => 0, 'explanation' => 'A list inside double brackets selects multiple named columns.'],
        ['id' => 'L8-06', 'prompt' => 'What does df["course"].isin(["Python Foundations", "Data Basics"]) produce?', 'choices' => [['A Boolean Series marking rows in either category', 'Correct.'], ['One combined text value', 'isin tests membership row by row.'], ['A sorted DataFrame', 'Membership testing does not sort.'], ['Only the column names', 'It evaluates column values.']], 'correct' => 0, 'explanation' => 'isin expresses membership in a permitted set of values.'],
        ['id' => 'L8-07', 'prompt' => 'A filter returns no rows. What is the best first response?', 'choices' => [['Check each condition count and the actual value ranges', 'Correct.'], ['Invent rows that match', 'That corrupts the evidence.'], ['Remove all conditions permanently', 'First diagnose which condition excludes the rows.'], ['Assume pandas is broken', 'Inspecting the Boolean masks provides direct evidence.']], 'correct' => 0, 'explanation' => 'Diagnose an empty result by checking conditions separately and inspecting source ranges.'],
        ['id' => 'L8-08', 'prompt' => 'The analysis requires delivered_hours, but that column is absent. What should happen?', 'choices' => [['Stop clearly or handle the documented schema difference', 'Correct.'], ['Silently substitute training_hours', 'Different fields can have different meanings.'], ['Create random delivered hours', 'Invented values invalidate the analysis.'], ['Ignore every later error', 'Failing clearly prevents misleading output.']], 'correct' => 0, 'explanation' => 'Validate required columns and preserve the meaning of each field.'],
        ['id' => 'L8-09', 'prompt' => 'What does expected_columns - set(df.columns) identify?', 'choices' => [['Required columns missing from the DataFrame', 'Correct.'], ['Rows with negative numbers', 'Set difference compares labels, not row values.'], ['Duplicate records', 'Duplicate detection needs duplicated().'], ['The mean of every column', 'Sets do not calculate means.']], 'correct' => 0, 'explanation' => 'Set difference is a compact schema validation technique.'],
        ['id' => 'L8-10', 'prompt' => 'Why filter rows and select needed columns early when data becomes large?', 'choices' => [['It reduces memory and keeps the analysis question explicit', 'Correct.'], ['It automatically proves causation', 'Selection does not establish cause.'], ['It repairs every invalid value', 'Cleaning still requires explicit checks.'], ['It guarantees no relevant row was excluded', 'The selection rule must still be justified and tested.']], 'correct' => 0, 'explanation' => 'Early, justified reduction improves efficiency without replacing validation.'],
    ];
    foreach ($questions as $data) {
        $question = v5_question($category->id, $context->id, $shortname . ' mastery: ', $data);
        quiz_add_quiz_question($question->id, $quiz, 0, 10);
    }
}

if ((int) $DB->count_records('quiz_slots', ['quizid' => $quiz->id]) !== 10) {
    throw new RuntimeException('Lesson 8 learning check must contain exactly 10 questions.');
}
$DB->set_field('quiz_slots', 'maxmark', 10, ['quizid' => $quiz->id]);
$quiz->attempts = 0;
$quiz->grademethod = QUIZ_GRADEHIGHEST;
$quiz->grade = 100;
$quiz->questionsperpage = 5;
$quiz->preferredbehaviour = 'deferredfeedback';
$quiz->timemodified = time();
$DB->update_record('quiz', $quiz);
// add_moduleinfo() invalidates the course cache, but the in-process modinfo
// instance can still predate the newly created quiz. Refresh it before the
// grade calculator resolves the quiz instance back to its course module.
rebuild_course_cache($course->id, true);
\mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
$gradeitem = \grade_item::fetch([
    'courseid' => $course->id, 'itemtype' => 'mod', 'itemmodule' => 'quiz',
    'iteminstance' => $quiz->id, 'outcomeid' => null,
]);
if (!$gradeitem) {
    throw new RuntimeException('Lesson 8 quiz grade item not found.');
}
$gradeitem->gradepass = 90;
$gradeitem->grademax = 100;
$gradeitem->update();
v5_feedback_bands($quiz->id);

$oldmarker = 'PYAI-V5-PYTHON-LAB-NOTEBOOKS';
$marker = 'PYAI-V5-ANNOUNCEMENT';
foreach (v5_announcement_posts($course->id, $oldmarker) as $post) {
    $post->message = str_replace($oldmarker, $marker, $post->message);
    $DB->update_record('forum_posts', $post);
}
$newsforum = forum_get_course_forum($course->id, 'news');
if (!v5_announcement_posts($course->id, $marker)) {
    forum_add_discussion((object) [
        'course' => $course->id, 'forum' => $newsforum->id,
        'name' => 'Python Lab notebooks now connect each lesson to hands-on practice',
        'message' => '<p>Each lesson now has a connected Python Lab notebook between the worked example and its learning check. Predict the result, run the code, change one value, explain what changed, and save before returning to Moodle.</p><p>Project sections also contain notebook templates. Your live work is saved in your own server workspace; use the same Moodle account each time.</p><p style="display:none">' . $marker . '</p>',
        'messageformat' => FORMAT_HTML, 'messagetrust' => 0, 'mailnow' => 0,
        'groupid' => -1, 'itemid' => 0,
    ], null, null, get_admin()->id);
}

rebuild_course_cache($course->id, true);
echo json_encode([
    'upgraded' => true,
    'version' => 5,
    'courseid' => (int) $course->id,
    'lesson8quizid' => (int) $quiz->id,
    'questions' => 10,
    'gradepass' => 90,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
