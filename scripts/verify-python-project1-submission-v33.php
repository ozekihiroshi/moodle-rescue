<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $page = $DB->get_record_select('page', 'course = :course AND content LIKE :marker', [
        'course' => $course->id,
        'marker' => '%PYAI-V33-PROJECT17-SAVE-SUBMIT%',
    ], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $checks = [
        'ctrl_s' => str_contains($page->content, '<kbd>Ctrl</kbd> + <kbd>S</kbd>'),
        'checker' => str_contains($page->content, 'check_weekly_support.py'),
        'all_tests_passed' => str_contains($page->content, 'ALL TESTS PASSED'),
        'direct_submit' => str_contains($page->content, 'submit_weekly_support.py'),
        'manual_submit' => $ja
            ? str_contains($page->content, 'Moodleの標準提出画面')
            : str_contains($page->content, "Moodle's standard submission page"),
        'single_deliverable' => substr_count($page->content, 'weekly_support.py') >= 5,
        'old_appendix_removed' => !str_contains($page->content, 'PYAI-V31-DIRECT-SUBMIT'),
    ];
    $results[] = [
        'shortname' => $shortname,
        'pageid' => (int)$page->id,
        'checks' => $checks,
        'verified' => !in_array(false, $checks, true),
    ];
}

$verified = !array_filter($results, static fn($result) => !$result['verified']);
echo json_encode(['courses' => $results, 'verified' => $verified], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
exit($verified ? 0 : 1);
