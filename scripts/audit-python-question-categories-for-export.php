<?php
// Read-only audit for Moodle 5 question category export readiness.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$result = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $coursecontext = context_course::instance($course->id);
    $entries = $DB->get_records_sql(
        "SELECT qbe.*
           FROM {question_bank_entries} qbe
           JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
          WHERE qc.contextid = :contextid",
        ['contextid' => $coursecontext->id]
    );
    $counts = ['zero_references' => 0, 'one_quiz_context' => 0, 'multiple_quiz_contexts' => 0];
    foreach ($entries as $entry) {
        $contexts = $DB->get_fieldset_sql(
            "SELECT DISTINCT qr.usingcontextid
               FROM {question_references} qr
              WHERE qr.questionbankentryid = :entryid
                AND qr.component = :component
                AND qr.questionarea = :questionarea",
            ['entryid' => $entry->id, 'component' => 'mod_quiz', 'questionarea' => 'slot']
        );
        if (count($contexts) === 0) {
            $counts['zero_references']++;
        } else if (count($contexts) === 1) {
            $counts['one_quiz_context']++;
        } else {
            $counts['multiple_quiz_contexts']++;
        }
    }
    $result[] = [
        'shortname' => $shortname,
        'course_context_entries' => count($entries),
    ] + $counts;
}

echo json_encode(['status' => 'ok', 'courses' => $result], JSON_PRETTY_PRINT) . PHP_EOL;
