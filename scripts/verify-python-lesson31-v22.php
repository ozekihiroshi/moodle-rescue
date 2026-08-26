<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $topicname = $ja ? '3.1 表形式データ・CSV・pandas' : '3.1 Tabular data, CSV, and pandas';
    $pagename = $ja ? 'レッスン3.1：表形式データ・CSV・pandas' : 'Lesson 3.1: Tabular data, CSV, and pandas';
    $ltiname = $ja ? 'Python Lab 3.1：表形式データ・CSV・pandas' : 'Python Lab 3.1: Tabular data, CSV, and pandas';
    $quizname = $ja ? '理解度チェック：3.1 表形式データ・CSV・pandas' : 'Knowledge check: 3.1 Tabular data, CSV, and pandas';
    $datasetname = $ja ? 'データセットの発展：24行から25万件の架空レコードへ' : 'Dataset progression: 24 to 250,000 fictional records';
    $required = $ja
        ? ['一行を一観測', '一列を一変数', 'スキーマ', 'ヘッダー', '引用符', '文字コード', '相対パス', '現在の作業フォルダ', 'FileNotFoundError', 'read_csv', 'encoding', 'dtype', 'head()', 'shape', 'columns', 'dtypes', 'info()', 'isna().sum()', 'Series', 'DataFrame', 'assign()', 'index=False', 'UnicodeDecodeError']
        : ['one observation per row', 'one variable per column', 'schema', 'header', 'quoting', 'encoding', 'relative path', 'current working directory', 'FileNotFoundError', 'read_csv', 'encoding', 'dtype', 'head()', 'shape', 'columns', 'dtypes', 'info()', 'isna().sum()', 'Series', 'DataFrame', 'assign()', 'index=False', 'UnicodeDecodeError'];

    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
    $delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    $dataset = $DB->get_record('page', ['course' => $course->id, 'name' => $datasetname], '*', MUST_EXIST);
    if (substr_count($page->content, 'PYAI-V22-LESSON31-FLOW') !== 1 ||
            substr_count($dataset->content, 'PYAI-V22-DATASET-PROGRESSION') !== 1) {
        throw new RuntimeException("{$shortname}: v22 page or dataset marker missing or duplicated");
    }
    foreach ($required as $needle) {
        if (!str_contains($page->content, $needle)) {
            throw new RuntimeException("{$shortname}: required content missing: {$needle}");
        }
    }
    foreach (['Naledi', 'ナレディ', 'AI checkpoint', 'AI利用の確認', 'Teacher guide', '教師用ガイド'] as $forbidden) {
        if (str_contains($page->content . $dataset->content, $forbidden)) {
            throw new RuntimeException("{$shortname}: forbidden content {$forbidden}");
        }
    }
    foreach (['offline', 'オフライン', 'teachers', '教師'] as $removed) {
        if (str_contains($dataset->content, $removed)) {
            throw new RuntimeException("{$shortname}: obsolete dataset wording {$removed}");
        }
    }
    foreach (['24', '10,000', '250,000', 'learning-centres-practice.csv'] as $needle) {
        if (!str_contains($dataset->content, $needle)) {
            throw new RuntimeException("{$shortname}: dataset progression missing {$needle}");
        }
    }

    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname], '*', MUST_EXIST);
    $slots = (int)$DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
    if ($slots !== 10 || abs((float)$quiz->sumgrades - 100.0) > 0.001 ||
            (int)$quiz->attempts !== 0 || $quiz->grademethod != QUIZ_GRADEHIGHEST) {
        throw new RuntimeException("{$shortname}: quiz settings mismatch slots={$slots}, sumgrades={$quiz->sumgrades}, attempts={$quiz->attempts}, grademethod={$quiz->grademethod}");
    }
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $expectedpath = $ja ? '/ja/07_tables_csv_pandas.ipynb' : '/07_tables_csv_pandas.ipynb';
    if (!str_ends_with($lti->toolurl, $expectedpath)) {
        throw new RuntimeException("{$shortname}: unexpected LTI URL {$lti->toolurl}");
    }
    $activities = [];
    $modinfo = get_fast_modinfo($course);
    foreach (array_filter(array_map('intval', explode(',', (string)$delegated->sequence))) as $cmid) {
        $activities[] = $modinfo->get_cm($cmid)->name;
    }
    if ($activities !== [$pagename, $ltiname, $quizname, $datasetname]) {
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
