<?php
// Verify the reproducible Python sample course in the local Moodle environment.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->libdir . '/gradelib.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);

$counts = [];
foreach (['page', 'quiz', 'assign'] as $modname) {
    $counts[$modname] = (int) $DB->count_records_sql(
        "SELECT COUNT(1)
           FROM {course_modules} cm
           JOIN {modules} m ON m.id = cm.module
          WHERE cm.course = :courseid AND m.name = :modname",
        ['courseid' => $course->id, 'modname' => $modname]
    );
}

$questioncount = (int) $DB->count_records_sql(
    "SELECT COUNT(1)
       FROM {quiz_slots} qs
       JOIN {quiz} q ON q.id = qs.quizid
      WHERE q.course = :courseid",
    ['courseid' => $course->id]
);

$sectioncount = (int) $DB->count_records_select(
    'course_sections',
    'course = :courseid AND section > 0',
    ['courseid' => $course->id]
);

$announcementcount = (int) $DB->count_records_select(
    'forum_posts',
    $DB->sql_like('message', ':marker'),
    ['marker' => '%PYAI-V%-ANNOUNCEMENT%']
);

$examplecount = (int) $DB->count_records_select(
    'page',
    'course = :courseid AND ' . $DB->sql_like('content', ':marker'),
    ['courseid' => $course->id, 'marker' => '%PYAI-V3-JOURNEY:%']
);
$appliedquestioncount = (int) $DB->count_records_select(
    'question', $DB->sql_like('name', ':prefix'),
    ['prefix' => $shortname . ' applied:%']
);
$transferquestioncount = (int) $DB->count_records_select(
    'question', $DB->sql_like('name', ':prefix'),
    ['prefix' => $shortname . ' transfer:%']
);

$masteryquestioncount = (int) $DB->count_records_select(
    'question', $DB->sql_like('name', ':prefix'),
    ['prefix' => $shortname . ' mastery:%']
);

$configuredlearningchecks = 0;
foreach ($DB->get_records('quiz', ['course' => $course->id]) as $quiz) {
    $slotcount = (int) $DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
    $feedbackcount = (int) $DB->count_records('quiz_feedback', ['quizid' => $quiz->id]);
    $gradeitem = grade_item::fetch([
        'courseid' => $course->id,
        'itemtype' => 'mod',
        'itemmodule' => 'quiz',
        'iteminstance' => $quiz->id,
        'itemnumber' => 0,
    ]);
    if ($slotcount === 10
            && (int) $quiz->attempts === 0
            && (int) $quiz->grademethod === (int) QUIZ_GRADEHIGHEST
            && abs((float) $quiz->grade - 100.0) < 0.001
            && $feedbackcount === 5
            && $gradeitem
            && abs((float) $gradeitem->gradepass - 90.0) < 0.001
            && abs((float) $gradeitem->grademax - 100.0) < 0.001) {
        $configuredlearningchecks++;
    }
}


$expected = ['page' => 28, 'quiz' => 12, 'assign' => 5];
$errors = [];
foreach ($expected as $type => $minimum) {
    if ($counts[$type] < $minimum) {
        $errors[] = "Expected at least {$minimum} {$type} activities; found {$counts[$type]}.";
    }
}
if ($questioncount < 120) {
    $errors[] = "Expected at least 120 quiz questions; found {$questioncount}.";
}
if ($sectioncount < 16) {
    $errors[] = "Expected at least 16 course sections; found {$sectioncount}.";
}

if ($announcementcount < 4) {
    $errors[] = "Expected four real course announcements; found {$announcementcount}.";
}
if ($examplecount < 11) {
    $errors[] = "Expected 11 connected Naledi examples; found {$examplecount}.";
}
if ($appliedquestioncount < 10) {
    $errors[] = "Expected 10 applied quiz questions; found {$appliedquestioncount}.";
}
if ($transferquestioncount < 10) {
    $errors[] = "Expected 10 transfer quiz questions; found {$transferquestioncount}.";
}
if ($masteryquestioncount < 67) {
    $errors[] = "Expected 67 misconception-driven mastery questions; found {$masteryquestioncount}.";
}
if ($configuredlearningchecks < 12) {
    $errors[] = "Expected all 12 quizzes to be configured as mastery learning checks; found {$configuredlearningchecks}.";
}
if ($errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

echo json_encode([
    'verified' => true,
    'courseid' => (int) $course->id,
    'shortname' => $course->shortname,
    'fullname' => $course->fullname,
    'sections' => $sectioncount,
    'activities' => $counts,
    'quizquestions' => $questioncount,
    'announcements' => $announcementcount,
    'lessonexamples' => $examplecount,
    'appliedquestions' => $appliedquestioncount,
    'transferquestions' => $transferquestioncount,
    'masteryquestions' => $masteryquestioncount,
    'configuredlearningchecks' => $configuredlearningchecks,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
