<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$result = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $parentname = $ja ? '第1章 — プログラミングの基礎と基本データ' : 'Chapter 1 — Programming Foundations and Scalar Values';
    $topicname = $ja ? '1.3 基本データ型・型変換・算術' : '1.3 Basic scalar types, conversion, and arithmetic';
    $conditionname = $ja ? '1.4 条件による判断' : '1.4 Decisions with conditions';
    $loopname = $ja ? '1.5 ループによる繰り返し' : '1.5 Repetition with loops';
    $projectname = $ja ? '1.6 実践プロジェクト：週間サポート報告' : '1.6 Applied project: Weekly support report';
    $pagename = $ja ? 'レッスン1.3：基本データ型・型変換・算術' : 'Lesson 1.3: Basic scalar types, conversion, and arithmetic';
    $quizname = $ja ? '理解度チェック：1.3 基本データ型・型変換・算術' : 'Knowledge check: 1.3 Basic scalar types, conversion, and arithmetic';
    $ltiname = $ja ? 'Python Lab 1.3：基本データ型・型変換・算術' : 'Python Lab 1.3: Basic scalar types, conversion, and arithmetic';

    $parent = null;
    foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
        if ($section && empty($section->component) && $section->name === $parentname) {
            $parent = $section;
            break;
        }
    }
    if (!$parent) {
        throw new RuntimeException("{$shortname}: parent chapter missing");
    }
    $names = [];
    foreach (get_fast_modinfo($course)->sections[$parent->section] ?? [] as $cmid) {
        $cm = get_fast_modinfo($course)->get_cm($cmid);
        if ($cm->modname === 'subsection') {
            $names[] = $cm->name;
        }
    }
    $expectedtail = [$topicname, $conditionname, $loopname, $projectname];
    if (array_slice($names, 2, 4) !== $expectedtail) {
        throw new RuntimeException("{$shortname}: Chapter 1 order is " . implode(' / ', $names));
    }

    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
    $delegated = $DB->get_record('course_sections', [
        'course' => $course->id,
        'component' => 'mod_subsection',
        'itemid' => $subsection->id,
    ], '*', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    if (substr_count($page->content, 'PYAI-V14-LESSON13-FLOW') !== 1) {
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
    $expectedpath = $ja ? '/ja/03_basic_scalar_types.ipynb' : '/03_basic_scalar_types.ipynb';
    if (!str_ends_with($lti->toolurl, $expectedpath)) {
        throw new RuntimeException("{$shortname}: unexpected LTI URL {$lti->toolurl}");
    }
    $activitynames = [];
    foreach (array_filter(array_map('intval', explode(',', (string) $delegated->sequence))) as $cmid) {
        $activitynames[] = get_fast_modinfo($course)->get_cm($cmid)->name;
    }
    if ($activitynames !== [$pagename, $ltiname, $quizname]) {
        throw new RuntimeException("{$shortname}: unexpected topic activities " . implode(' / ', $activitynames));
    }
    $result[] = [
        'courseid' => (int) $course->id,
        'shortname' => $shortname,
        'chapter_order' => $expectedtail,
        'activities' => $activitynames,
        'quiz_slots' => $slots,
        'sumgrades' => (float) $quiz->sumgrades,
        'lti_path' => $expectedpath,
    ];
}
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
