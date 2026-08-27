<?php
// Verify restored English and Japanese Python course distributions in an isolated Moodle.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$courses = $DB->get_records_select(
    'course',
    'id <> :siteid AND (shortname LIKE :en OR shortname LIKE :ja)',
    ['siteid' => SITEID, 'en' => 'PYAI-INTRO%', 'ja' => 'PYAI-INTRO-JA%'],
    'id ASC'
);
$result = [];
foreach ($courses as $course) {
    $modinfo = get_fast_modinfo($course);
    $modulecounts = [];
    $visible = 0;
    $quizrefs = 0;
    $wrongcontexts = 0;
    foreach ($modinfo->get_cms() as $cm) {
        $modulecounts[$cm->modname] = ($modulecounts[$cm->modname] ?? 0) + 1;
        if ($cm->visible && $cm->visibleoncoursepage) {
            $visible++;
        }
        if ($cm->modname === 'quiz') {
            $context = context_module::instance($cm->id);
            $references = $DB->get_records('question_references', [
                'usingcontextid' => $context->id,
                'component' => 'mod_quiz',
                'questionarea' => 'slot',
            ]);
            $quizrefs += count($references);
            foreach ($references as $reference) {
                $entry = $DB->get_record('question_bank_entries', [
                    'id' => $reference->questionbankentryid,
                ], '*', MUST_EXIST);
                $category = $DB->get_record('question_categories', [
                    'id' => $entry->questioncategoryid,
                ], '*', MUST_EXIST);
                if ((int)$category->contextid !== (int)$context->id) {
                    $wrongcontexts++;
                }
            }
        }
    }
    ksort($modulecounts);
    $result[] = [
        'course_id' => (int)$course->id,
        'shortname' => $course->shortname,
        'fullname' => $course->fullname,
        'sections' => count($modinfo->get_section_info_all()),
        'visible_activities' => $visible,
        'module_counts' => $modulecounts,
        'quiz_question_references' => $quizrefs,
        'wrong_question_contexts' => $wrongcontexts,
        'enrolments' => $DB->count_records_sql(
            'SELECT COUNT(1) FROM {user_enrolments} ue JOIN {enrol} e ON e.id = ue.enrolid WHERE e.courseid = :courseid',
            ['courseid' => $course->id]
        ),
        'quiz_attempts' => $DB->count_records_sql(
            'SELECT COUNT(1) FROM {quiz_attempts} qa JOIN {quiz} q ON q.id = qa.quiz WHERE q.course = :courseid',
            ['courseid' => $course->id]
        ),
        'submissions' => $DB->count_records_sql(
            'SELECT COUNT(1) FROM {assign_submission} s JOIN {assign} a ON a.id = s.assignment WHERE a.course = :courseid AND s.userid <> 0',
            ['courseid' => $course->id]
        ),
    ];
}

echo json_encode(['status' => 'ok', 'courses' => $result],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
