<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$expectedhash = '40d267e662c7f48608d0dcfc319878fa907a09df902fd0b10cf5815c36989bf0';
$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $topic = $ja ? '4.2 ガイド付きプロジェクト：学習センター分析' : '4.2 Guided project: Learning-centre analysis';
    $pagename = $ja ? '4.2 データセットとプロジェクト手順' : '4.2 Dataset and project brief';
    $ltiname = $ja ? 'Python Lab 4.2：学習センター分析' : 'Python Lab 4.2: Learning-centre analysis';
    $assignname = $ja ? '提出課題4.2：学習センター分析' : 'Assignment 4.2: Learning-centre analysis';

    $pagechecks = $ja
        ? ['learning-centres-practice.csv', '/home/jovyan/work/data/learning-centres-practice.csv', '24行×10列', '今回答える具体的な問い', 'Python Foundations', 'Digital Skills', 'パーセントポイント', '回答する5項目']
        : ['learning-centres-practice.csv', '/home/jovyan/work/data/learning-centres-practice.csv', '24 rows and 10 columns', 'Concrete question', 'Python Foundations', 'Digital Skills', 'percentage-point difference', 'Five report prompts'];
    $assignchecks = $ja
        ? ['(24, 10)', '三つの品質検査', '照合', '0〜100%軸', '300〜500字', '評価（100点）']
        : ['(24, 10)', 'Three quality checks', 'reconciliation', '0–100% axis', '150–250 words', 'Rubric (100)'];

    $sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topic], '*', MUST_EXIST);
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id], '*', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname], '*', MUST_EXIST);
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);

    if (substr_count($page->content, 'PYAI-V28-PROJECT42-CONCRETE') !== 1) throw new RuntimeException("$shortname marker");
    if (!str_contains($page->content, '@@PLUGINFILE@@/learning-centres-practice.csv?forcedownload=1')) throw new RuntimeException("$shortname download link");
    foreach ($pagechecks as $needle) if (!str_contains($page->content, $needle)) throw new RuntimeException("$shortname page missing $needle");
    foreach ($assignchecks as $needle) if (!str_contains($assign->intro, $needle)) throw new RuntimeException("$shortname assignment missing $needle");

    $cm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $file = get_file_storage()->get_file($context->id, 'mod_page', 'content', 0, '/', 'learning-centres-practice.csv');
    if (!$file) throw new RuntimeException("$shortname attached CSV missing");
    if ((int)$file->get_filesize() !== 2071) throw new RuntimeException("$shortname attached CSV size");
    if (hash('sha256', $file->get_content()) !== $expectedhash) throw new RuntimeException("$shortname attached CSV hash");

    $path = $ja ? '/ja/P3_learning_centres_analysis.ipynb' : '/P3_learning_centres_analysis.ipynb';
    if (!str_ends_with($lti->toolurl, $path)) throw new RuntimeException("$shortname LTI path");
    $names = [];
    $modinfo = get_fast_modinfo($course);
    foreach (array_filter(array_map('intval', explode(',', (string)$section->sequence))) as $cmid) $names[] = $modinfo->get_cm($cmid)->name;
    if ($names !== [$pagename, $ltiname, $assignname]) throw new RuntimeException("$shortname activity order");

    $results[] = [
        'shortname' => $shortname,
        'pageid' => (int)$page->id,
        'activities' => $names,
        'csv_bytes' => $file->get_filesize(),
        'csv_sha256' => $expectedhash,
        'lti_path' => $path,
    ];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
