<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $topicname = $ja ? '3.3 データのクリーニングと監査記録' : '3.3 Data cleaning and audit records';
    $pagename = $ja ? 'レッスン3.3：データのクリーニングと監査記録' : 'Lesson 3.3: Data cleaning and audit records';
    $ltiname = $ja ? 'Python Lab 3.3：データのクリーニングと監査記録' : 'Python Lab 3.3: Data cleaning and audit records';
    $quizname = $ja ? '理解度チェック：3.3 データのクリーニングと監査記録' : 'Knowledge check: 3.3 Data cleaning and audit records';
    $required = $ja
        ? ['元データ', 'raw.copy()', '修正前', '型変換失敗', 'pd.to_numeric', 'errors=&quot;coerce&quot;', '欠損は0ではない', 'isna()', '表記', 'district_raw', 'str.strip()', 'str.title()', '項目間', 'registered &gt;= attended &gt;= completed', 'notna()', '業務キー', 'duplicated', 'keep=False', '検出と処置', 'analysis_ready', '監査記録', '0件', 'assert', '件数']
        : ['source', 'raw.copy()', 'before correction', 'conversion failure', 'pd.to_numeric', 'errors=&quot;coerce&quot;', 'Missing does not mean zero', 'isna()', 'Normalise labels', 'district_raw', 'str.strip()', 'str.title()', 'cross-field', 'registered &gt;= attended &gt;= completed', 'notna()', 'business key', 'duplicated', 'keep=False', 'Separate detection from action', 'analysis_ready', 'audit record', 'zero count', 'assert', 'count'];

    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
    $delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    if (substr_count($page->content, 'PYAI-V24-LESSON33-FLOW') !== 1) {
        throw new RuntimeException("{$shortname}: v24 marker missing or duplicated");
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
        throw new RuntimeException("{$shortname}: quiz settings mismatch");
    }
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $expectedpath = $ja ? '/ja/09_cleaning_audit_trail.ipynb' : '/09_cleaning_audit_trail.ipynb';
    if (!str_ends_with($lti->toolurl, $expectedpath)) {
        throw new RuntimeException("{$shortname}: unexpected LTI URL {$lti->toolurl}");
    }
    $activities = [];
    $modinfo = get_fast_modinfo($course);
    foreach (array_filter(array_map('intval', explode(',', (string)$delegated->sequence))) as $cmid) {
        $activities[] = $modinfo->get_cm($cmid)->name;
    }
    if (array_slice($activities, 0, 3) !== [$pagename, $ltiname, $quizname] || count($activities) !== 4) {
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
