<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $topic = $ja ? '1.7 小プロジェクト：週間サポート報告' : '1.7 Mini-project: Weekly support report';
    $pageName = $ja ? '1.7 課題仕様と完成条件' : '1.7 Project brief and completion contract';
    $ltiName = $ja ? 'Python Labプロジェクト1.7：週間サポート報告' : 'Python Lab project 1.7: Weekly support report';
    $assignName = $ja ? 'プロジェクト1.7：学習センター週間サポート報告' : 'Project 1.7: Weekly learning-centre support report';
    $path = $ja ? '/ja/P1_weekly_support_report.ipynb' : '/P1_weekly_support_report.ipynb';
    $pageRequired = $ja
        ? ['weekly_support.py', 'check_weekly_support.py', '関数、リスト、辞書', 'input()', 'WEEKLY SUPPORT REPORT', 'RESULT: INVALID', 'NO REQUESTS', 'ALL TESTS PASSED']
        : ['weekly_support.py', 'check_weekly_support.py', 'Functions, lists, dictionaries', 'input()', 'WEEKLY SUPPORT REPORT', 'RESULT: INVALID', 'NO REQUESTS', 'ALL TESTS PASSED'];
    $assignRequired = $ja
        ? ['weekly_support.py', '8項目', 'ALL TESTS PASSED', '提出しないもの']
        : ['weekly_support.py', 'eight automatic checks', 'ALL TESTS PASSED', 'Do not submit'];

    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topic], '*', MUST_EXIST);
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pageName], '*', MUST_EXIST);
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiName], '*', MUST_EXIST);
    $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignName], '*', MUST_EXIST);

    if (substr_count($page->content, 'PYAI-V29-PROJECT17-SCRIPT-CHECK') !== 1) throw new RuntimeException("$shortname page marker");
    if (substr_count($assign->intro, 'PYAI-V29-PROJECT17-SUBMISSION') !== 1) throw new RuntimeException("$shortname assignment marker");
    foreach ($pageRequired as $needle) if (!str_contains($page->content, $needle)) throw new RuntimeException("$shortname page missing $needle");
    foreach ($assignRequired as $needle) if (!str_contains($assign->intro, $needle)) throw new RuntimeException("$shortname assignment missing $needle");
    foreach (['AI use', 'AI利用', 'Naledi', 'ナレディ', 'Teacher guide', '教師用ガイド', 'model answer', '模範解答'] as $forbidden) {
        if (stripos($page->content . $assign->intro . $lti->intro, $forbidden) !== false) throw new RuntimeException("$shortname forbidden $forbidden");
    }
    if (!str_ends_with($lti->toolurl, $path)) throw new RuntimeException("$shortname LTI path");
    if (!str_contains($lti->intro, 'weekly_support.py')) throw new RuntimeException("$shortname LTI brief");

    $modinfo = get_fast_modinfo($course);
    $activities = [];
    foreach (array_filter(array_map('intval', explode(',', (string)$section->sequence))) as $cmid) $activities[] = $modinfo->get_cm($cmid)->name;
    if ($activities !== [$pageName, $ltiName, $assignName]) throw new RuntimeException("$shortname activity order");

    $configs = [];
    foreach ($DB->get_records('assign_plugin_config', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission']) as $config) $configs[$config->name] = $config->value;
    if (($configs['enabled'] ?? '') !== '1' || ($configs['maxfilesubmissions'] ?? '') !== '1' || ($configs['accepted_types'] ?? '') !== '.py') {
        throw new RuntimeException("$shortname submission config");
    }
    $results[] = ['shortname' => $shortname, 'topic' => $topic, 'activities' => $activities, 'lti_path' => $path, 'accepted_types' => '.py'];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
