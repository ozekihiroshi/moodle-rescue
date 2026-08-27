<?php
// Read-only ownership audit for course-context question bank entries.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$result = ['status' => 'ok', 'mutation' => false, 'courses' => []];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $coursecontext = context_course::instance($course->id);
    $entries = $DB->get_records_sql(
        "SELECT qbe.id
           FROM {question_bank_entries} qbe
           JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
          WHERE qc.contextid = :contextid",
        ['contextid' => $coursecontext->id]
    );

    $counts = [
        'entries_total' => count($entries),
        'zero_references' => 0,
        'visible_live_quiz' => 0,
        'hidden_live_quiz' => 0,
        'deletion_in_progress_quiz' => 0,
        'missing_or_other_context' => 0,
        'multiple_contexts' => 0,
    ];
    $owners = [];

    foreach ($entries as $entry) {
        $references = $DB->get_records_sql(
            "SELECT DISTINCT qr.usingcontextid
               FROM {question_references} qr
              WHERE qr.questionbankentryid = :entryid
                AND qr.component = :component
                AND qr.questionarea = :questionarea",
            [
                'entryid' => $entry->id,
                'component' => 'mod_quiz',
                'questionarea' => 'slot',
            ]
        );
        if (!$references) {
            $counts['zero_references']++;
            continue;
        }
        if (count($references) > 1) {
            $counts['multiple_contexts']++;
        }

        foreach ($references as $reference) {
            $context = $DB->get_record('context', ['id' => $reference->usingcontextid]);
            if (!$context || (int)$context->contextlevel !== CONTEXT_MODULE) {
                $counts['missing_or_other_context']++;
                continue;
            }
            $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
            if (!$cm || (int)$cm->course !== (int)$course->id) {
                $counts['missing_or_other_context']++;
                continue;
            }
            $module = $DB->get_record('modules', ['id' => $cm->module], 'id,name');
            $name = '';
            if ($module && $module->name === 'quiz') {
                $quiz = $DB->get_record('quiz', ['id' => $cm->instance], 'id,name');
                $name = $quiz ? $quiz->name : '';
            }
            $ownerkey = $cm->id . '|' . $name;
            if (!isset($owners[$ownerkey])) {
                $owners[$ownerkey] = [
                    'cmid' => (int)$cm->id,
                    'name' => $name,
                    'visible' => (bool)$cm->visible,
                    'deletion_in_progress' => (bool)$cm->deletioninprogress,
                    'entry_references' => 0,
                ];
            }
            $owners[$ownerkey]['entry_references']++;

            if ($cm->deletioninprogress) {
                $counts['deletion_in_progress_quiz']++;
            } else if ($cm->visible) {
                $counts['visible_live_quiz']++;
            } else {
                $counts['hidden_live_quiz']++;
            }
        }
    }

    usort($owners, static fn($a, $b) => $a['cmid'] <=> $b['cmid']);
    $result['courses'][] = [
        'course_id' => (int)$course->id,
        'shortname' => $shortname,
        'counts' => $counts,
        'non_visible_or_deleting_owners' => array_values(array_filter(
            $owners,
            static fn($owner) => !$owner['visible'] || $owner['deletion_in_progress']
        )),
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
