<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $topicname = $ja ? '2.3 実践プロジェクト：学習センター月次実績報告' : '2.3 Applied project: Monthly centre performance report';
    $ltiname = $ja ? 'Python Lab 2.3：学習センター月次実績報告' : 'Python Lab 2.3: Monthly centre performance report';
    $assignname = $ja ? 'プロジェクト2.3：学習センター月次実績報告' : 'Project 2.3: Monthly learning-centre performance report';
    $required = $ja
        ? ['name', 'district', 'registered', 'attended', 'completed', 'material_cost', 'KeyError', 'completed &lt;= attended &lt;= registered', 'validate_centre(centre)', 'safe_percentage(part, whole)', 'safe_unit_cost(cost, completed)', 'centre_metrics(centre)', 'summarise_centres(centres)', 'None', '75%', '70%', '単純平均', '必須キー欠落', '負数', '評価基準（100点）', '再起動して全セル']
        : ['name', 'district', 'registered', 'attended', 'completed', 'material_cost', 'KeyError', 'completed &lt;= attended &lt;= registered', 'validate_centre(centre)', 'safe_percentage(part, whole)', 'safe_unit_cost(cost, completed)', 'centre_metrics(centre)', 'summarise_centres(centres)', 'None', '75%', '70%', 'unweighted average', 'missing required key', 'negative value', 'Assessment criteria (100 points)', 'Restart the kernel'];

    $subsection = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topicname], '*', MUST_EXIST);
    $delegated = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $subsection->id], '*', MUST_EXIST);
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname], '*', MUST_EXIST);

    if (substr_count($assign->intro, 'PYAI-V21-PROJECT23-BRIEF') !== 1) {
        throw new RuntimeException("{$shortname}: v21 assignment marker missing or duplicated");
    }
    foreach ($required as $needle) {
        if (!str_contains($assign->intro, $needle)) {
            throw new RuntimeException("{$shortname}: required assignment content missing: {$needle}");
        }
    }
    foreach (['Naledi', 'ナレディ', 'AI use declaration', 'AI利用申告', 'AI checkpoint', 'AI利用の確認', 'Teacher guide', '教師用ガイド', 'model answer', '模範解答'] as $forbidden) {
        if (str_contains($assign->intro, $forbidden)) {
            throw new RuntimeException("{$shortname}: forbidden assignment content {$forbidden}");
        }
    }
    if (abs((float)$assign->grade - 100.0) > 0.001) {
        throw new RuntimeException("{$shortname}: assignment grade is {$assign->grade}");
    }

    $fileenabled = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'subtype' => 'assignsubmission', 'plugin' => 'file', 'name' => 'enabled']);
    $textenabled = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'subtype' => 'assignsubmission', 'plugin' => 'onlinetext', 'name' => 'enabled']);
    if ((int)$fileenabled !== 1 || (int)$textenabled !== 1) {
        throw new RuntimeException("{$shortname}: expected file and online-text submissions");
    }

    $expectedpath = $ja ? '/ja/P2_monthly_centre_report.ipynb' : '/P2_monthly_centre_report.ipynb';
    if (!str_ends_with($lti->toolurl, $expectedpath)) {
        throw new RuntimeException("{$shortname}: unexpected LTI URL {$lti->toolurl}");
    }
    $activities = [];
    $modinfo = get_fast_modinfo($course);
    foreach (array_filter(array_map('intval', explode(',', (string)$delegated->sequence))) as $cmid) {
        $activities[] = $modinfo->get_cm($cmid)->name;
    }
    if ($activities !== [$ltiname, $assignname]) {
        throw new RuntimeException("{$shortname}: unexpected activity order " . implode(' / ', $activities));
    }
    $results[] = [
        'courseid' => (int)$course->id,
        'shortname' => $shortname,
        'topic' => $topicname,
        'activities' => $activities,
        'required_content_checks' => count($required),
        'grade' => (float)$assign->grade,
        'file_submission' => (int)$fileenabled === 1,
        'online_text' => (int)$textenabled === 1,
        'lti_path' => $expectedpath,
    ];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
