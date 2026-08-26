<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $topic = $ja ? '4.2 ガイド付きプロジェクト：学習センター分析' : '4.2 Guided project: Learning-centre analysis';
    $pagename = $ja ? '4.2 データセットとプロジェクト手順' : '4.2 Dataset and project brief';
    $ltiname = $ja ? 'Python Lab 4.2：学習センター分析' : 'Python Lab 4.2: Learning-centre analysis';
    $assignname = $ja ? '提出課題4.2：学習センター分析' : 'Assignment 4.2: Learning-centre analysis';
    $pagechecks = $ja
        ? ['問い', '2026年1〜6月', '全体修了率', '一人修了当たり教材費', '24件', '10列', '個人情報を含みません', '欠損', '地区表記ゆれ', '項目間制約違反', '監査表', '0〜100%', '300〜500字']
        : ['Question', 'January–June 2026', 'overall completion', 'material cost per completion', '24 fictional rows', '10 columns', 'no personal data', 'missingness', 'label variation', 'cross-field violation', 'audit', 'zero-to-100%', '150–250 word'];
    $assignchecks = $ja
        ? ['問い・範囲・指標定義', '検査出力', 'クリーニング監査表', '検証済み集計表', 'ラベル付き主図', '数値・範囲・限界・次の問い', '評価（100点）', '集計と分母25', '検証15']
        : ['Question, scope, and measure definitions', 'Inspection output and cleaning audit', 'Validated summary table', 'labelled primary chart', 'numbers, scope, limitation, and next question', 'Rubric (100)', 'aggregation and denominators 25', 'validation 15'];
    $sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topic], '*', MUST_EXIST);
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id], '*', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname], '*', MUST_EXIST);
    if (substr_count($page->content, 'PYAI-V27-PROJECT42-FLOW') !== 1) throw new RuntimeException("$shortname marker");
    foreach ($pagechecks as $needle) if (!str_contains($page->content, $needle)) throw new RuntimeException("$shortname page missing $needle");
    foreach ($assignchecks as $needle) if (!str_contains($assign->intro, $needle)) throw new RuntimeException("$shortname assignment missing $needle");
    foreach (['AI use declaration', 'AI-use declaration', 'AI利用申告', 'Naledi', 'ナレディ', 'Model answer', '模範解答', 'Teacher guide', '教師用'] as $forbidden) {
        if (stripos($page->content . $assign->intro, $forbidden) !== false) throw new RuntimeException("$shortname forbidden $forbidden");
    }
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $path = $ja ? '/ja/P3_learning_centres_analysis.ipynb' : '/P3_learning_centres_analysis.ipynb';
    if (!str_ends_with($lti->toolurl, $path)) throw new RuntimeException("$shortname LTI");
    $names = []; $modinfo = get_fast_modinfo($course);
    foreach (array_filter(array_map('intval', explode(',', (string)$section->sequence))) as $cmid) $names[] = $modinfo->get_cm($cmid)->name;
    if ($names !== [$pagename, $ltiname, $assignname]) throw new RuntimeException("$shortname order");
    $results[] = ['courseid'=>(int)$course->id, 'shortname'=>$shortname, 'topic'=>$topic, 'activities'=>$names, 'page_checks'=>count($pagechecks), 'assignment_checks'=>count($assignchecks), 'lti_path'=>$path];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
