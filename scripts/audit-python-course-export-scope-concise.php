<?php
// Read-only concise inventory for deciding the distributable course scope.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$result = ['status' => 'ok', 'mutation' => false, 'courses' => []];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $modinfo = get_fast_modinfo($course);
    $sections = [];
    foreach ($modinfo->get_section_info_all() as $section) {
        $sections[(int)$section->section] = (bool)$section->visible;
    }

    $summary = [
        'sections_total' => count($sections),
        'sections_visible' => count(array_filter($sections)),
        'activities_total' => 0,
        'activities_visible_on_course_page' => 0,
        'activities_hidden' => 0,
        'activities_stealth_or_hidden_section' => 0,
        'quizzes_total' => 0,
        'quiz_question_references' => 0,
    ];
    $modulecounts = [];
    $nonvisible = [];
    $names = [];

    foreach ($modinfo->get_cms() as $cm) {
        $sectionvisible = $sections[$cm->sectionnum] ?? false;
        $visibleonpage = (bool)$cm->visible && (bool)$cm->visibleoncoursepage && $sectionvisible;
        $state = $visibleonpage ? 'visible' : ((bool)$cm->visible ? 'stealth_or_hidden_section' : 'hidden');
        $item = [
            'cmid' => (int)$cm->id,
            'module' => (string)$cm->modname,
            'section_number' => (int)$cm->sectionnum,
            'name' => (string)$cm->name,
            'state' => $state,
        ];

        $summary['activities_total']++;
        $modulecounts[$cm->modname] = ($modulecounts[$cm->modname] ?? 0) + 1;
        if ($visibleonpage) {
            $summary['activities_visible_on_course_page']++;
        } else if ($state === 'hidden') {
            $summary['activities_hidden']++;
        } else {
            $summary['activities_stealth_or_hidden_section']++;
        }

        if ($cm->modname === 'quiz') {
            $context = context_module::instance($cm->id);
            $refcount = $DB->count_records('question_references', [
                'usingcontextid' => $context->id,
                'component' => 'mod_quiz',
                'questionarea' => 'slot',
            ]);
            $item['question_reference_count'] = $refcount;
            $summary['quizzes_total']++;
            $summary['quiz_question_references'] += $refcount;
        }

        if (!$visibleonpage) {
            $nonvisible[] = $item;
        }
        $namekey = mb_strtolower(trim($cm->modname . '|' . $cm->name));
        $names[$namekey][] = $item;
    }

    $duplicategroups = array_values(array_filter($names, static fn($items) => count($items) > 1));
    ksort($modulecounts);
    $result['courses'][] = [
        'course_id' => (int)$course->id,
        'shortname' => $shortname,
        'summary' => $summary,
        'module_counts' => $modulecounts,
        'non_visible_activities' => $nonvisible,
        'same_name_activity_groups' => $duplicategroups,
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
