<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $parentname = $ja ? '第1章 — プログラミングの基礎と基本データ' : 'Chapter 1 — Programming Foundations and Scalar Values';
    $sectionnames = $ja ? [
        '1.1 プログラム・値・式・出力',
        '1.2 変数・代入・プログラムの状態',
        '1.3 基本データ型・型変換・算術',
        '1.4 文字列・入力・書式付き出力',
        '1.5 条件による判断',
        '1.6 ループによる繰り返し',
        '1.7 実践プロジェクト：週間サポート報告',
    ] : [
        '1.1 Programs, values, expressions, and output',
        '1.2 Variables, assignment, and program state',
        '1.3 Basic scalar types, conversion, and arithmetic',
        '1.4 Strings, input, and formatted output',
        '1.5 Decisions with conditions',
        '1.6 Repetition with loops',
        '1.7 Applied project: Weekly support report',
    ];
    $pagename = $ja ? 'レッスン1.4：文字列・入力・書式付き出力' : 'Lesson 1.4: Strings, input, and formatted output';
    $ltiname = $ja ? 'Python Lab 1.4：文字列・入力・書式付き出力' : 'Python Lab 1.4: Strings, input, and formatted output';
    $quizname = $ja ? '理解度チェック：1.4 文字列・入力・書式付き出力' : 'Knowledge check: 1.4 Strings, input, and formatted output';

    $parent = null;
    foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
        if ($section && empty($section->component) && $section->name === $parentname) {
            $parent = $section;
            break;
        }
    }
    if (!$parent) {
        throw new RuntimeException("{$shortname}: Chapter 1 missing");
    }
    $actualsections = [];
    foreach (get_fast_modinfo($course)->sections[$parent->section] ?? [] as $cmid) {
        $cm = get_fast_modinfo($course)->get_cm($cmid);
        if ($cm->modname === 'subsection') {
            $actualsections[] = $cm->name;
        }
    }
    if ($actualsections !== $sectionnames) {
        throw new RuntimeException("{$shortname}: Chapter 1 order is " . implode(' / ', $actualsections));
    }

    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $sectionnames[3]], '*', MUST_EXIST);
    $delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    if (substr_count($page->content, 'PYAI-V15-LESSON14-FLOW') !== 1) {
        throw new RuntimeException("{$shortname}: page marker missing or duplicated");
    }
    foreach (['Naledi', 'ナレディ', 'AI checkpoint', 'AI利用の確認'] as $forbidden) {
        if (str_contains($page->content, $forbidden)) {
            throw new RuntimeException("{$shortname}: forbidden content {$forbidden}");
        }
    }
    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname], '*', MUST_EXIST);
    $slots = (int) $DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
    if ($slots !== 10 || (float) $quiz->sumgrades !== 100.0) {
        throw new RuntimeException("{$shortname}: quiz slots={$slots}, sumgrades={$quiz->sumgrades}");
    }
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $expectedpath = $ja ? '/ja/04_strings_input_formatting.ipynb' : '/04_strings_input_formatting.ipynb';
    if (!str_ends_with($lti->toolurl, $expectedpath)) {
        throw new RuntimeException("{$shortname}: unexpected LTI URL {$lti->toolurl}");
    }
    $activitynames = [];
    foreach (array_filter(array_map('intval', explode(',', (string) $delegated->sequence))) as $cmid) {
        $activitynames[] = get_fast_modinfo($course)->get_cm($cmid)->name;
    }
    if ($activitynames !== [$pagename, $ltiname, $quizname]) {
        throw new RuntimeException("{$shortname}: unexpected 1.4 activities " . implode(' / ', $activitynames));
    }

    $numbered = $ja ? [
        'レッスン1.1：', 'Python Lab 1.1：', '理解度チェック：1.1 ',
        'レッスン1.2：', 'Python Lab 1.2：', '理解度チェック：1.2 ',
        'レッスン1.5：', 'Python Lab 1.5：', '理解度チェック：1.5 ',
        'レッスン1.6：', 'Python Lab 1.6：', '理解度チェック：1.6 ',
        'Python Labプロジェクト1.7：', 'プロジェクト1.7：',
    ] : [
        'Lesson 1.1:', 'Python Lab 1.1:', 'Knowledge check: 1.1 ',
        'Lesson 1.2:', 'Python Lab 1.2:', 'Knowledge check: 1.2 ',
        'Lesson 1.5:', 'Python Lab 1.5:', 'Knowledge check: 1.5 ',
        'Lesson 1.6:', 'Python Lab 1.6:', 'Knowledge check: 1.6 ',
        'Python Lab project 1.7:', 'Project 1.7:',
    ];
    foreach ($numbered as $prefix) {
        $found = false;
        foreach (['page', 'lti', 'quiz', 'assign'] as $table) {
            if ($DB->record_exists_select($table, 'course = ? AND ' . $DB->sql_like('name', '?'), [$course->id, $prefix . '%'])) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new RuntimeException("{$shortname}: numbered activity missing: {$prefix}");
        }
    }
    $results[] = [
        'courseid' => (int) $course->id,
        'shortname' => $shortname,
        'chapter1_sections' => $actualsections,
        'lesson_1_4_activities' => $activitynames,
        'quiz_slots' => $slots,
        'sumgrades' => (float) $quiz->sumgrades,
        'lti_path' => $expectedpath,
        'chapter1_numbering_checks' => count($numbered),
    ];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
