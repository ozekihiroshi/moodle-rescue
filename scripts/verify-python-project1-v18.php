<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $topicname = $ja ? '1.7 実践プロジェクト：週間サポート報告' : '1.7 Applied project: Weekly support report';
    $ltiname = $ja ? 'Python Labプロジェクト1.7：週間サポート報告' : 'Python Lab project 1.7: Weekly support report';
    $assignname = $ja ? 'プロジェクト1.7：学習センター週間サポート報告' : 'Project 1.7: Weekly learning-centre support report';
    $required = $ja
        ? ['1.1〜1.6', '業務ルール', '初期データの自己確認', '必須テスト', '提出物', '採点基準（100点）', '75%', '85%', '空データ']
        : ['1.1-1.6', 'Operational rules', 'Self-check for the initial data', 'Required tests', 'Submit', 'Marking criteria (100 points)', '75%', '85%', 'empty'];

    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
    $delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
    $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname], '*', MUST_EXIST);
    if (substr_count($assign->intro, 'PYAI-V18-PROJECT17') !== 1 || (float) $assign->grade != 100.0) {
        throw new RuntimeException("{$shortname}: assignment marker or grade mismatch");
    }
    foreach ($required as $needle) {
        if (!str_contains($assign->intro, $needle)) {
            throw new RuntimeException("{$shortname}: required assignment content missing: {$needle}");
        }
    }
    foreach (['Naledi', 'ナレディ', 'AI use', 'AI利用', 'Teacher guide', '教師用ガイド', 'model answer', '模範解答'] as $forbidden) {
        if (str_contains($assign->intro, $forbidden)) {
            throw new RuntimeException("{$shortname}: forbidden assignment content {$forbidden}");
        }
    }

    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $expectedpath = $ja ? '/ja/P1_weekly_support_report.ipynb' : '/P1_weekly_support_report.ipynb';
    if (!str_ends_with($lti->toolurl, $expectedpath)) {
        throw new RuntimeException("{$shortname}: unexpected LTI URL {$lti->toolurl}");
    }
    $activities = [];
    foreach (array_filter(array_map('intval', explode(',', (string) $delegated->sequence))) as $cmid) {
        $activities[] = get_fast_modinfo($course)->get_cm($cmid)->name;
    }
    if ($activities !== [$ltiname, $assignname]) {
        throw new RuntimeException("{$shortname}: unexpected project activity order " . implode(' / ', $activities));
    }

    $fileenabled = $DB->get_record('assign_plugin_config', [
        'assignment' => $assign->id,
        'plugin' => 'file',
        'subtype' => 'assignsubmission',
        'name' => 'enabled',
    ]);
    if (!$fileenabled || $fileenabled->value != '1') {
        throw new RuntimeException("{$shortname}: file submission is not enabled");
    }
    $results[] = [
        'courseid' => (int) $course->id,
        'shortname' => $shortname,
        'topic' => $topicname,
        'activities' => $activities,
        'required_content_checks' => count($required),
        'assignment_grade' => (float) $assign->grade,
        'file_submission' => true,
        'lti_path' => $expectedpath,
    ];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
