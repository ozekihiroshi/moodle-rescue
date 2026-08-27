<?php
// Verify the release form of Chapter 4 after historical quizzes are removed.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$result = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $lessons = [];
    foreach ([28, 29, 30, 31] as $sectionnumber) {
        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'section' => $sectionnumber,
        ], '*', MUST_EXIST);
        $cmids = array_values(array_filter(explode(',', (string)$section->sequence), 'strlen'));
        $quizzes = [];
        foreach ($cmids as $cmid) {
            $cm = $DB->get_record('course_modules', ['id' => (int)$cmid], '*', MUST_EXIST);
            $module = $DB->get_record('modules', ['id' => $cm->module], 'id,name', MUST_EXIST);
            if ($module->name === 'quiz') {
                $quizzes[] = $cm;
            }
        }
        if (count($quizzes) !== 1) {
            throw new RuntimeException("{$shortname} section {$sectionnumber}: expected exactly one quiz.");
        }
        $cm = reset($quizzes);
        if (!(bool)$cm->visible || (bool)$cm->deletioninprogress) {
            throw new RuntimeException("{$shortname} CM {$cm->id}: current quiz is not visible and live.");
        }
        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = context_module::instance($cm->id);
        $references = $DB->get_records('question_references', [
            'usingcontextid' => $context->id,
            'component' => 'mod_quiz',
            'questionarea' => 'slot',
        ]);
        if (count($references) !== 10) {
            throw new RuntimeException("{$shortname} CM {$cm->id}: expected 10 question references.");
        }
        foreach ($references as $reference) {
            $entry = $DB->get_record('question_bank_entries', [
                'id' => $reference->questionbankentryid,
            ], '*', MUST_EXIST);
            $category = $DB->get_record('question_categories', [
                'id' => $entry->questioncategoryid,
            ], '*', MUST_EXIST);
            if ((int)$category->contextid !== (int)$context->id) {
                throw new RuntimeException("{$shortname} entry {$entry->id}: wrong question context.");
            }
        }
        $lessons[] = [
            'section' => $sectionnumber,
            'cmid' => (int)$cm->id,
            'quizid' => (int)$quiz->id,
            'name' => $quiz->name,
            'question_references' => count($references),
        ];
    }
    $result[] = ['shortname' => $shortname, 'lessons' => $lessons];
}

echo json_encode([
    'status' => 'ok',
    'courses' => $result,
    'marker' => 'PYAI-V47-CHAPTER4-DISTRIBUTION-VERIFIED',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
