<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $topicname = $ja ? '1.5 条件による判断' : '1.5 Decisions with conditions';
    $pagename = $ja ? 'レッスン1.5：条件による判断' : 'Lesson 1.5: Decisions with conditions';
    $ltiname = $ja ? 'Python Lab 1.5：条件と境界値' : 'Python Lab 1.5: Conditions and boundaries';
    $quizname = $ja ? '理解度チェック：1.5 条件による判断' : 'Knowledge check: 1.5 Decisions with conditions';
    $required = $ja
        ? ['条件式はTrueまたはFalse', 'if文は条件がTrue', '独立したif文', 'and・or・not', '境界値', '妥当か確認', '短絡評価', '例題から応用へ']
        : ['condition produces the Boolean', 'if runs its indented block', 'Independent if statements', 'and, or, and not', 'boundary', 'Validate the value', 'Short-circuit evaluation', 'guided example to transfer'];

    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
    $delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    if (substr_count($page->content, 'PYAI-V16-CONDITIONS-FLOW') !== 1 || str_contains($page->content, 'PYAI-V10-CONDITIONS-FLOW')) {
        throw new RuntimeException("{$shortname}: v16 page marker mismatch");
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
        throw new RuntimeException("{$shortname}: quiz settings mismatch slots={$slots}, sumgrades={$quiz->sumgrades}, attempts={$quiz->attempts}, grademethod={$quiz->grademethod}, highest=" . QUIZ_GRADEHIGHEST);
    }
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $expectedpath = $ja ? '/ja/03_conditions_boundaries.ipynb' : '/03_conditions_boundaries.ipynb';
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
        'unlimited_attempts' => (int) $quiz->attempts === 0,
        'highest_grade' => $quiz->grademethod == QUIZ_GRADEHIGHEST,
        'lti_path' => $expectedpath,
    ];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
