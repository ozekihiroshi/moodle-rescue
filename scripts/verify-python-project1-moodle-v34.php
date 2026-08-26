<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $pageName = $ja ? '1.7 課題仕様と完成条件' : '1.7 Project brief and completion contract';
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pageName], '*', MUST_EXIST);
    $content = $page->content;
    $checks = [
        'full_run_path' => str_contains($content, 'python projects/weekly-support/weekly_support.py'),
        'full_checker_path' => str_contains($content, 'python projects/weekly-support/check_weekly_support.py'),
        'no_bare_checker_command' => !str_contains($content, '<code>python check_weekly_support.py</code>'),
        'implementation_contract' => $ja
            ? str_contains($content, '実装上の約束')
            : str_contains($content, 'Implementation contract'),
        'automation_scope' => $ja
            ? str_contains($content, '集計、解決率の計算、対応状況の判定、最繁忙日の特定を自動化')
            : str_contains($content, 'automates the totals, resolution-rate calculation, status decision'),
        'sample_table_columns' => $ja
            ? str_contains($content, '<th>曜日</th><th>問い合わせ件数</th><th>解決件数</th>')
            : str_contains($content, '<th>Day</th><th>Received</th><th>Resolved</th>'),
        'eight_checks_documented' => str_contains($content, 'ALL TESTS PASSED'),
        'submission_documented' => str_contains($content, 'submit_weekly_support.py'),
    ];
    $results[] = [
        'shortname' => $shortname,
        'pageid' => (int)$page->id,
        'checks' => $checks,
        'verified' => !in_array(false, $checks, true),
    ];
}
$verified = !array_filter($results, static fn($result) => !$result['verified']);
echo json_encode(['courses' => $results, 'verified' => $verified], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
exit($verified ? 0 : 1);
