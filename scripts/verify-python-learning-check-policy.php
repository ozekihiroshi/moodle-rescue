<?php
// Verify the course-level learning-check explanation and completion policy.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->libdir . '/completionlib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->dirroot . '/completion/completion_aggregation.php';
require_once $CFG->libdir . '/gradelib.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$guide = $DB->get_record('page', ['course' => $course->id, 'name' => 'Start here: course guide'], '*', MUST_EXIST);

$errors = [];
if ((int) $course->enablecompletion !== 1) {
    $errors[] = 'Course completion is not enabled.';
}
foreach ([
    'PYAI-V7-LEARNING-CHECK-POLICY',
    'All 12 learning checks are required for course completion',
    'they are not one-time exams',
    'A lower score does not mean that you have failed the course',
] as $text) {
    if (!str_contains($guide->content, $text)) {
        $errors[] = "Course guide is missing policy text: {$text}";
    }
}

$quizcmids = [];
foreach ($DB->get_records('quiz', ['course' => $course->id], 'id') as $quiz) {
    $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
    $gradeitem = grade_item::fetch([
        'courseid' => $course->id,
        'itemtype' => 'mod',
        'itemmodule' => 'quiz',
        'iteminstance' => $quiz->id,
        'itemnumber' => 0,
    ]);
    if (!str_contains($quiz->intro, '<strong>Required learning check:</strong>')
            || str_contains($quiz->intro, 'This is a learning check, not a one-time test.')) {
        $errors[] = "Quiz '{$quiz->name}' does not have the concise description.";
    }
    if ((int) $quiz->attempts !== 0 || (int) $quiz->grademethod !== (int) QUIZ_GRADEHIGHEST) {
        $errors[] = "Quiz '{$quiz->name}' is not unlimited with highest grade retained.";
    }
    if ((int) $cm->completion !== COMPLETION_TRACKING_AUTOMATIC
            || (int) $cm->completionpassgrade !== 1
            || (int) $cm->completiongradeitemnumber !== 0) {
        $errors[] = "Quiz '{$quiz->name}' is not completed by passing grade.";
    }
    if (!$gradeitem || abs((float) $gradeitem->gradepass - 90.0) > 0.001) {
        $errors[] = "Quiz '{$quiz->name}' does not have a 90% pass grade.";
    }
    $feedbacks = $DB->get_records('quiz_feedback', ['quizid' => $quiz->id], 'mingrade DESC');
    if (count($feedbacks) !== 5) {
        $errors[] = "Quiz '{$quiz->name}' does not have five feedback bands.";
    } else {
        foreach ($feedbacks as $feedback) {
            if ((float) $feedback->mingrade >= 90.0) {
                if (!str_contains($feedback->feedbacktext, (float) $feedback->mingrade >= 100.0 ? 'Mastered' : 'Completed')) {
                    $errors[] = "Quiz '{$quiz->name}' has incorrect completion feedback.";
                }
            } elseif (!str_contains($feedback->feedbacktext, 'Not complete yet')) {
                $errors[] = "Quiz '{$quiz->name}' labels a below-90 result incorrectly.";
            }
        }
    }
    $quizcmids[] = (int) $cm->id;
}

sort($quizcmids);
if (count($quizcmids) !== 12) {
    $errors[] = 'Expected exactly 12 learning checks.';
}
$criteriacmids = array_map('intval', $DB->get_fieldset_select(
    'course_completion_criteria',
    'moduleinstance',
    'course = :course AND criteriatype = :type',
    ['course' => $course->id, 'type' => COMPLETION_CRITERIA_TYPE_ACTIVITY]
));
sort($criteriacmids);
if ($criteriacmids !== $quizcmids) {
    $errors[] = 'Course-completion activity criteria are not exactly the 12 learning checks.';
}

foreach ([null, COMPLETION_CRITERIA_TYPE_ACTIVITY] as $criteriatype) {
    $params = ['course' => $course->id, 'criteriatype' => $criteriatype];
    $aggregation = completion_aggregation::fetch($params);
    if (!$aggregation || (int) $aggregation->method !== COMPLETION_AGGREGATION_ALL) {
        $label = $criteriatype === null ? 'overall' : 'activity';
        $errors[] = "The {$label} completion aggregation does not require all criteria.";
    }
}

if ($errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

echo json_encode([
    'status' => 'ok',
    'course_id' => (int) $course->id,
    'guide_page_id' => (int) $guide->id,
    'required_learning_checks' => count($quizcmids),
    'pass_grade' => 90,
    'completion_requires_all_checks' => true,
    'quiz_cmids' => $quizcmids,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
