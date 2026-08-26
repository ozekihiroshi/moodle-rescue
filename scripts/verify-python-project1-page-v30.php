<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $topicName = $ja ? '1.7 小プロジェクト：週間サポート報告' : '1.7 Mini-project: Weekly support report';
    $pageName = $ja ? '1.7 課題仕様と完成条件' : '1.7 Project brief and completion contract';
    $ltiName = $ja ? 'Python Labプロジェクト1.7：週間サポート報告' : 'Python Lab project 1.7: Weekly support report';
    $assignName = $ja ? 'プロジェクト1.7：学習センター週間サポート報告' : 'Project 1.7: Weekly learning-centre support report';
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pageName], '*', MUST_EXIST);
    if (substr_count($page->content, 'PYAI-V30-PROJECT17-READABLE-BRIEF') !== 1) throw new RuntimeException("$shortname marker");
    if (str_contains($page->content, '\\n')) throw new RuntimeException("$shortname contains visible escaped newline");

    $ordered = $ja
        ? ['この課題ですること', '課題の状況', '入力方法と動作確認用のサンプル日報', '作成するプログラム', '期待される出力', '問い合わせ0件と不正データ', '使用するPython', '考えるためのヒント', '確認手順', '提出物と完成条件']
        : ['What you will do', 'Situation', 'Input and sample daily records', 'Program requirements', 'Expected output', 'Zero requests and invalid data', 'Python in scope', 'Hints', 'Checking', 'Submission and completion'];
    $last = -1;
    foreach ($ordered as $heading) {
        $position = strpos($page->content, $heading);
        if ($position === false || $position <= $last) throw new RuntimeException("$shortname heading order: $heading");
        $last = $position;
    }

    $required = ['weekly_support.py', 'check_weekly_support.py', 'Monday received:', 'Monday resolved:', 'WEEKLY SUPPORT REPORT', 'TOTAL RECEIVED: 75', 'TOTAL RESOLVED: 67', 'UNRESOLVED: 8', 'RESOLUTION RATE: 89.3%', 'STATUS: REVIEW', 'BUSIEST DAY: Thursday', 'RESULT: INVALID', 'N/A', 'NO REQUESTS', 'NONE', '80%', '90%', 'ALL TESTS PASSED'];
    foreach ($required as $needle) if (!str_contains($page->content, $needle)) throw new RuntimeException("$shortname missing $needle");
    $sampleValues = [['Monday', '12', '10'], ['Tuesday', '18', '16'], ['Wednesday', '15', '15'], ['Thursday', '20', '17'], ['Friday', '10', '9']];
    foreach ($sampleValues as [$day, $received, $resolved]) {
        if (!str_contains($page->content, "<td>$day</td><td>$received</td><td>$resolved</td>")) throw new RuntimeException("$shortname sample row $day");
    }
    if (substr_count($page->content, '<table class="generaltable">') !== 2 && $ja) throw new RuntimeException("$shortname table count");
    if (substr_count($page->content, '<table class="generaltable">') !== 1 && !$ja) throw new RuntimeException("$shortname table count");

    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicName], '*', MUST_EXIST);
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
    $modinfo = get_fast_modinfo($course);
    $activities = [];
    foreach (array_filter(array_map('intval', explode(',', (string)$section->sequence))) as $cmid) $activities[] = $modinfo->get_cm($cmid)->name;
    if ($activities !== [$pageName, $ltiName, $assignName]) throw new RuntimeException("$shortname activity order");

    $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignName], '*', MUST_EXIST);
    $configs = [];
    foreach ($DB->get_records('assign_plugin_config', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission']) as $config) $configs[$config->name] = $config->value;
    if (($configs['enabled'] ?? '') !== '1' || ($configs['maxfilesubmissions'] ?? '') !== '1' || ($configs['accepted_types'] ?? '') !== '.py') throw new RuntimeException("$shortname submission config");

    $results[] = ['shortname' => $shortname, 'pageid' => (int)$page->id, 'ordered_sections' => count($ordered), 'sample_rows' => 5, 'escaped_newlines' => false, 'activities' => $activities, 'accepted_types' => '.py'];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
