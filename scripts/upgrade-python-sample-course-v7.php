<?php
// Clarify mastery-check purpose and require all checks for course completion.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->libdir . '/completionlib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/completion/criteria/completion_criteria_activity.php';
require_once $CFG->dirroot . '/completion/completion_aggregation.php';
require_once $CFG->libdir . '/gradelib.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
\core\session\manager::set_user(get_admin());

$topics = [
    'Knowledge check: Lesson 1: Your first Python program' => 'programs, values, and output',
    'Knowledge check: Lesson 2: Variables, types, input, and calculations' => 'variables, types, input, and calculations',
    'Knowledge check: Lesson 3: Decisions with conditions' => 'conditions, Boolean results, and boundaries',
    'Knowledge check: Lesson 4: Repetition with loops' => 'loops, counters, and accumulators',
    'Knowledge check: Lesson 5: Lists and dictionaries' => 'lists, dictionaries, and records',
    'Knowledge check: Lesson 6: Functions, errors, and testing' => 'functions, errors, and testing',
    'Knowledge check: Lesson 7: Tables, CSV, and pandas' => 'tables, CSV files, and pandas',
    'Knowledge check: Lesson 8: Inspecting and selecting data' => 'filtering, Boolean logic, and schema checks',
    'Knowledge check: Lesson 9: Cleaning data' => 'data cleaning and audit trails',
    'Knowledge check: Lesson 10: Grouping and summary statistics' => 'grouping and summary statistics',
    'Knowledge check: Lesson 11: Visualisation and evidence' => 'visualisation, evidence, and interpretation',
    'Applied check: Scaling up safely' => 'chunked processing, validation, and data provenance',
];

function v7_feedback_bands(int $quizid): void {
    global $DB;

    $DB->delete_records('quiz_feedback', ['quizid' => $quizid]);
    $bands = [
        [100, 101, '<div><span aria-hidden="true" style="font-size:2rem">&#127881;</span><h3>Mastered - 100%!</h3><p>You checked every idea successfully. Explain one difficult answer in your own words to make the learning stick.</p></div>'],
        [90, 100, '<div><span aria-hidden="true" style="font-size:2rem">&#127942;</span><h3>Completed - congratulations!</h3><p>This learning check is complete. Review any remaining feedback; you may try again for 100%.</p></div>'],
        [80, 90, '<div><span aria-hidden="true" style="font-size:2rem">&#128640;</span><h3>Not complete yet - you are close.</h3><p>Review the missed ideas, practise them in Python Lab, and try again. A score of 90% completes this check.</p></div>'],
        [60, 80, '<div><span aria-hidden="true" style="font-size:2rem">&#127793;</span><h3>Not complete yet - keep building.</h3><p>Use the feedback to choose two ideas to practise in Python Lab, then make another attempt.</p></div>'],
        [0, 60, '<div><span aria-hidden="true" style="font-size:2rem">&#128269;</span><h3>Not complete yet - you found what to learn next.</h3><p>This result is guidance, not a penalty. Review the explanations, trace small examples, and try again.</p></div>'],
    ];
    foreach ($bands as [$min, $max, $html]) {
        $DB->insert_record('quiz_feedback', (object) [
            'quizid' => $quizid,
            'feedbacktext' => $html,
            'feedbacktextformat' => FORMAT_HTML,
            'mingrade' => $min,
            'maxgrade' => $max,
        ]);
    }
}

$guide = $DB->get_record('page', ['course' => $course->id, 'name' => 'Start here: course guide'], '*', MUST_EXIST);
$marker = 'PYAI-V7-LEARNING-CHECK-POLICY';
$policy = '<h3>How learning checks work</h3>'
    . '<div style="border-left:5px solid #356a9a;padding:.8em 1em;background:#eef4fb">'
    . '<p><strong>All 12 learning checks are required for course completion, but they are not one-time exams.</strong></p>'
    . '<p>You may try each check as many times as needed, and your highest score is kept. A score of 90% completes the check.</p>'
    . '<p>A lower score does not mean that you have failed the course. It identifies what to practise next. Read the feedback, practise the missed ideas in Python Lab, and try again.</p>'
    . '<p>After reaching 90%, the check is complete. You may continue towards 100% to strengthen your understanding.</p>'
    . '</div>'
    . '<h3>Projects and evidence</h3>'
    . '<p>The foundation assignment checks core Python. The data-analysis assignment checks a guided workflow. The final project asks you to select a question, analyse data, create a chart, and explain the evidence.</p>'
    . '<p style="display:none">' . $marker . '</p>';

if (str_contains($guide->content, '<h3>Assessment</h3>')) {
    $guide->content = preg_replace(
        '~<h3>Assessment</h3>.*$~s',
        $policy,
        $guide->content,
        1
    );
} elseif (!str_contains($guide->content, $marker)) {
    $guide->content .= $policy;
}
$guide->timemodified = time();
$DB->update_record('page', $guide);

$course->enablecompletion = 1;
$course->timemodified = time();
$DB->update_record('course', $course);

$quizcmids = [];
foreach ($topics as $quizname => $topic) {
    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
    $gradeitem = grade_item::fetch([
        'courseid' => $course->id,
        'itemtype' => 'mod',
        'itemmodule' => 'quiz',
        'iteminstance' => $quiz->id,
        'itemnumber' => 0,
    ]);
    if (!$gradeitem) {
        throw new moodle_exception("Grade item not found for '{$quizname}'");
    }

    $quiz->intro = '<p><strong>Required learning check:</strong> Check your understanding of '
        . s($topic)
        . '. Review the feedback and try again when needed.</p>';
    $quiz->introformat = FORMAT_HTML;
    $quiz->attempts = 0;
    $quiz->grademethod = QUIZ_GRADEHIGHEST;
    $quiz->timemodified = time();
    $DB->update_record('quiz', $quiz);

    $gradeitem->gradepass = 90;
    $gradeitem->grademax = 100;
    $gradeitem->update();
    v7_feedback_bands($quiz->id);

    $DB->set_field('course_modules', 'completion', COMPLETION_TRACKING_AUTOMATIC, ['id' => $cm->id]);
    $DB->set_field('course_modules', 'completiongradeitemnumber', 0, ['id' => $cm->id]);
    $DB->set_field('course_modules', 'completionpassgrade', 1, ['id' => $cm->id]);
    $DB->set_field('course_modules', 'completionview', 0, ['id' => $cm->id]);
    $DB->set_field('course_modules', 'completionexpected', 0, ['id' => $cm->id]);
    \course_modinfo::purge_course_module_cache($course->id, $cm->id);
    $quizcmids[] = (int) $cm->id;
}

// This sample course uses the 12 learning checks as its complete set of course-completion criteria.
$course = $DB->get_record('course', ['id' => $course->id], '*', MUST_EXIST);
$completion = new completion_info($course);
$completion->clear_criteria(false);
$criteriadata = (object) [
    'id' => $course->id,
    'criteria_activity' => array_fill_keys($quizcmids, 1),
];
$criterion = new completion_criteria_activity();
$criterion->update_config($criteriadata);

foreach ([null, COMPLETION_CRITERIA_TYPE_ACTIVITY] as $criteriatype) {
    $aggregation = new completion_aggregation([
        'course' => $course->id,
        'criteriatype' => $criteriatype,
    ]);
    $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
    $aggregation->save();
}

rebuild_course_cache($course->id, true);

echo json_encode([
    'upgraded' => true,
    'version' => 7,
    'course_id' => (int) $course->id,
    'guide_page_id' => (int) $guide->id,
    'required_learning_checks' => count($quizcmids),
    'pass_grade' => 90,
    'unlimited_attempts' => true,
    'highest_grade_kept' => true,
    'quiz_cmids' => $quizcmids,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
