<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $topicname = $ja ? '2.2 関数・エラー・テスト' : '2.2 Functions, errors, and testing';
    $pagename = $ja ? 'レッスン2.2：関数・エラー・テスト' : 'Lesson 2.2: Functions, errors, and testing';
    $ltiname = $ja ? 'Python Lab 2.2：関数・エラー・テスト' : 'Python Lab 2.2: Functions, errors, and testing';
    $quizname = $ja ? '理解度チェック：2.2 関数・エラー・テスト' : 'Knowledge check: 2.2 Functions, errors, and testing';
    $required = $ja
        ? ['def', '仮引数', '実引数', 'return', 'None', 'ローカル変数', '既定値', 'キーワード引数', 'docstring', '型ヒント', 'KeyError', 'ValueError', '文法エラー', '実行時エラー', '論理エラー', 'トレースバック', 'try', 'except', 'assert', '境界値', 'summarise_centres()']
        : ['def', 'parameters', 'arguments', 'return', 'None', 'local', 'defaults', 'keyword argument', 'docstring', 'type hints', 'KeyError', 'ValueError', 'syntax error', 'runtime error', 'logic error', 'traceback', 'try', 'except', 'assert', 'boundary', 'summarise_centres()'];

    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
    $delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    if (substr_count($page->content, 'PYAI-V20-LESSON22-FLOW') !== 1) {
        throw new RuntimeException("{$shortname}: v20 marker missing or duplicated");
    }
    foreach ($required as $needle) {
        if (!str_contains($page->content, $needle)) {
            throw new RuntimeException("{$shortname}: required content missing: {$needle}");
        }
    }
    foreach (['Naledi', 'ナレディ', 'AI checkpoint', 'AI利用の確認', 'Teacher guide', '教師用ガイド'] as $forbidden) {
        if (str_contains($page->content, $forbidden)) {
            throw new RuntimeException("{$shortname}: forbidden content {$forbidden}");
        }
    }

    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname], '*', MUST_EXIST);
    $slots = (int) $DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
    if ($slots !== 10 || abs((float) $quiz->sumgrades - 100.0) > 0.001 ||
            (int) $quiz->attempts !== 0 || $quiz->grademethod != QUIZ_GRADEHIGHEST) {
        throw new RuntimeException("{$shortname}: quiz settings mismatch slots={$slots}, sumgrades={$quiz->sumgrades}, attempts={$quiz->attempts}, grademethod={$quiz->grademethod}");
    }

    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $expectedpath = $ja ? '/ja/06_functions_errors_testing.ipynb' : '/06_functions_errors_testing.ipynb';
    if (!str_ends_with($lti->toolurl, $expectedpath)) {
        throw new RuntimeException("{$shortname}: unexpected LTI URL {$lti->toolurl}");
    }
    $activities = [];
    $modinfo = get_fast_modinfo($course);
    foreach (array_filter(array_map('intval', explode(',', (string) $delegated->sequence))) as $cmid) {
        $activities[] = $modinfo->get_cm($cmid)->name;
    }
    if ($activities !== [$pagename, $ltiname, $quizname]) {
        throw new RuntimeException("{$shortname}: unexpected activity order " . implode(' / ', $activities));
    }
    $results[] = [
        'courseid' => (int) $course->id,
        'shortname' => $shortname,
        'topic' => $topicname,
        'activities' => $activities,
        'required_content_checks' => count($required),
        'quiz_slots' => $slots,
        'sumgrades' => (float) $quiz->sumgrades,
        'unlimited_attempts' => (int) $quiz->attempts === 0,
        'highest_grade' => $quiz->grademethod == QUIZ_GRADEHIGHEST,
        'lti_path' => $expectedpath,
    ];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
