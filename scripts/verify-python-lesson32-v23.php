<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $topicname = $ja ? '3.2 データの選択・抽出とブール論理' : '3.2 Data selection, filtering, and Boolean logic';
    $pagename = $ja ? 'レッスン3.2：データの選択・抽出とブール論理' : 'Lesson 3.2: Data selection, filtering, and Boolean logic';
    $ltiname = $ja ? 'Python Lab 3.2：データの選択・抽出とブール論理' : 'Python Lab 3.2: Data selection, filtering, and Boolean logic';
    $quizname = $ja ? '理解度チェック：3.2 データの選択・抽出とブール論理' : 'Knowledge check: 3.2 Data selection, filtering, and Boolean logic';
    $required = $ja
        ? ['表示列', '行条件', '必要列', '差集合', 'Series', 'DataFrame', 'loc[', 'iloc[', 'ブールSeries', 'mask.sum()', 'and', '&amp;', '|', '~', 'ド・モルガン', 'isin()', 'between()', 'inclusive', 'notna()', 'isna()', 'sort_values()', 'indexラベル', 'まだ値を直しません']
        : ['displayed columns', 'row conditions', 'required names', 'missing columns', 'Series', 'DataFrame', 'loc[', 'iloc[', 'Boolean Series', 'mask.sum()', 'and', '&amp;', '|', '~', 'De Morgan', 'isin()', 'between()', 'inclusive', 'notna()', 'isna()', 'sort_values()', 'index label', 'does not correct'];

    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
    $delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    if (substr_count($page->content, 'PYAI-V23-LESSON32-FLOW') !== 1) {
        throw new RuntimeException("{$shortname}: v23 marker missing or duplicated");
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
    $slots = (int)$DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
    if ($slots !== 10 || abs((float)$quiz->sumgrades - 100.0) > 0.001 ||
            (int)$quiz->attempts !== 0 || $quiz->grademethod != QUIZ_GRADEHIGHEST) {
        throw new RuntimeException("{$shortname}: quiz settings mismatch slots={$slots}, sumgrades={$quiz->sumgrades}, attempts={$quiz->attempts}, grademethod={$quiz->grademethod}");
    }
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $expectedpath = $ja ? '/ja/08_filtering_boolean_logic.ipynb' : '/08_filtering_boolean_logic.ipynb';
    if (!str_ends_with($lti->toolurl, $expectedpath)) {
        throw new RuntimeException("{$shortname}: unexpected LTI URL {$lti->toolurl}");
    }
    $activities = [];
    $modinfo = get_fast_modinfo($course);
    foreach (array_filter(array_map('intval', explode(',', (string)$delegated->sequence))) as $cmid) {
        $activities[] = $modinfo->get_cm($cmid)->name;
    }
    if ($activities !== [$pagename, $ltiname, $quizname]) {
        throw new RuntimeException("{$shortname}: unexpected activity order " . implode(' / ', $activities));
    }
    $results[] = [
        'courseid' => (int)$course->id,
        'shortname' => $shortname,
        'topic' => $topicname,
        'activities' => $activities,
        'required_content_checks' => count($required),
        'quiz_slots' => $slots,
        'sumgrades' => (float)$quiz->sumgrades,
        'unlimited_attempts' => (int)$quiz->attempts === 0,
        'highest_grade' => $quiz->grademethod == QUIZ_GRADEHIGHEST,
        'lti_path' => $expectedpath,
    ];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
