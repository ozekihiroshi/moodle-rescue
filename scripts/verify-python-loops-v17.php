<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $topicname = $ja ? '1.6 ループによる繰り返し' : '1.6 Repetition with loops';
    $pagename = $ja ? 'レッスン1.6：ループによる繰り返し' : 'Lesson 1.6: Repetition with loops';
    $ltiname = $ja ? 'Python Lab 1.6：ループと累積処理' : 'Python Lab 1.6: Loops and accumulators';
    $quizname = $ja ? '理解度チェック：1.6 ループによる繰り返し' : 'Knowledge check: 1.6 Repetition with loops';
    $required = $ja
        ? ['for文は', 'range()', 'アキュムレータ', 'カウンタ', '最大値', 'enumerate()', 'continue', 'while文', '空データ', '例題から応用へ']
        : ['for takes one value', 'range()', 'accumulator', 'counter', 'maximum', 'enumerate()', 'continue', 'while repeats', 'empty iterable', 'guided example to transfer'];

    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
    $delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    if (substr_count($page->content, 'PYAI-V17-LOOPS-FLOW') !== 1) {
        throw new RuntimeException("{$shortname}: v17 page marker missing or duplicated");
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
    if ($slots != 10 || abs((float) $quiz->sumgrades - 100.0) > 0.001 || $quiz->attempts != 0 || $quiz->grademethod != QUIZ_GRADEHIGHEST) {
        throw new RuntimeException("{$shortname}: quiz settings mismatch slots={$slots}, sumgrades={$quiz->sumgrades}, attempts={$quiz->attempts}, grademethod={$quiz->grademethod}");
    }
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $expectedpath = $ja ? '/ja/04_loops_accumulators.ipynb' : '/04_loops_accumulators.ipynb';
    if (!str_ends_with($lti->toolurl, $expectedpath)) {
        throw new RuntimeException("{$shortname}: unexpected LTI URL {$lti->toolurl}");
    }
    $activities = [];
    foreach (array_filter(array_map('intval', explode(',', (string) $delegated->sequence))) as $cmid) {
        $activities[] = get_fast_modinfo($course)->get_cm($cmid)->name;
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
        'unlimited_attempts' => $quiz->attempts == 0,
        'highest_grade' => $quiz->grademethod == QUIZ_GRADEHIGHEST,
        'lti_path' => $expectedpath,
    ];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
