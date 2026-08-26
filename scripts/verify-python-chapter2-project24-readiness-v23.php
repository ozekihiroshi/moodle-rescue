<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->libdir . '/gradelib.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $lessons = $ja ? [
        '2.1' => ['page' => 'レッスン2.1：リスト・辞書・レコード', 'lti' => 'Python Lab 2.1：リスト・辞書・レコード', 'quiz' => '理解度チェック：2.1 リスト・辞書・レコード', 'topic' => '2.1 リスト・辞書・レコード', 'path' => '/ja/05_lists_dictionaries_records.ipynb', 'required' => ['IDで一件', 'None', '重複', 'enumerate()', 'pop()', 'CRUD', '備品台帳']],
        '2.2' => ['page' => 'レッスン2.2：関数・エラー・テスト', 'lti' => 'Python Lab 2.2：関数・エラー・テスト', 'quiz' => '理解度チェック：2.2 関数・エラー・テスト', 'topic' => '2.2 関数・エラー・テスト', 'path' => '/ja/06_functions_errors_testing.ipynb', 'required' => ['None', '保存中の辞書', 'KeyError', 'ValueError', '状態の前後', '確認プログラム', '備品台帳']],
    ] : [
        '2.1' => ['page' => 'Lesson 2.1: Lists, dictionaries, and records', 'lti' => 'Python Lab 2.1: Lists, dictionaries, and records', 'quiz' => 'Knowledge check: 2.1 Lists, dictionaries, and records', 'topic' => '2.1 Lists, dictionaries, and records', 'path' => '/05_lists_dictionaries_records.ipynb', 'required' => ['Find one record', 'None', 'duplicate', 'enumerate()', 'pop()', 'CRUD', 'equipment register']],
        '2.2' => ['page' => 'Lesson 2.2: Functions, errors, and testing', 'lti' => 'Python Lab 2.2: Functions, errors, and testing', 'quiz' => 'Knowledge check: 2.2 Functions, errors, and testing', 'topic' => '2.2 Functions, errors, and testing', 'path' => '/06_functions_errors_testing.ipynb', 'required' => ['None', 'stored dictionary', 'KeyError', 'ValueError', 'state before and after', 'supplied checker', 'equipment-register']],
    ];
    foreach ($lessons as $lesson => $names) {
        $page = $DB->get_record('page', ['course' => $course->id, 'name' => $names['page']], '*', MUST_EXIST);
        $marker = "PYAI-V23-{$lesson}-PROJECT24-READY";
        if (substr_count($page->content, $marker) !== 1) throw new RuntimeException("$shortname $lesson marker");
        foreach ($names['required'] as $token) if (!str_contains($page->content, $token)) throw new RuntimeException("$shortname $lesson missing $token");
        foreach (['Naledi', 'ナレディ', 'AI checkpoint', 'Teacher guide', '教師用ガイド'] as $forbidden) if (str_contains($page->content, $forbidden)) throw new RuntimeException("$shortname $lesson forbidden $forbidden");
        $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $names['lti']], '*', MUST_EXIST);
        if (!str_ends_with($lti->toolurl, $names['path'])) throw new RuntimeException("$shortname $lesson LTI");
        $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $names['quiz']], '*', MUST_EXIST);
        $slots = (int)$DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
        $gradeitem = grade_item::fetch(['courseid' => $course->id, 'itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id, 'itemnumber' => 0]);
        if ($slots !== 10 || abs((float)$quiz->sumgrades - 100.0) > 0.001 || (int)$quiz->attempts !== 0 || $quiz->grademethod != QUIZ_GRADEHIGHEST || !$gradeitem || abs((float)$gradeitem->gradepass - 90.0) > 0.001 || (int)$DB->count_records('quiz_feedback', ['quizid' => $quiz->id]) !== 5) throw new RuntimeException("$shortname $lesson quiz policy");
        $sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $names['topic']], '*', MUST_EXIST);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id], '*', MUST_EXIST);
        $modinfo = get_fast_modinfo($course);
        $activitynames = [];
        foreach (array_filter(array_map('intval', explode(',', (string)$section->sequence))) as $cmid) $activitynames[] = $modinfo->get_cm($cmid)->name;
        if ($activitynames !== [$names['page'], $names['lti'], $names['quiz']]) throw new RuntimeException("$shortname $lesson activity order");
        $results[] = ['shortname' => $shortname, 'lesson' => $lesson, 'activities' => $activitynames, 'quiz_slots' => $slots, 'gradepass' => (float)$gradeitem->gradepass, 'lti_path' => $names['path']];
    }
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
