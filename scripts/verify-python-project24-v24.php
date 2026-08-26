<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $topic = $ja ? '2.4 実践プロジェクト：CSV図書記録管理' : '2.4 Applied project: CSV library record manager';
    $pagename = $ja ? '2.4 課題仕様と完成条件' : '2.4 Project brief and completion criteria';
    $ltiname = $ja ? 'Python Lab 2.4：CSV図書記録管理' : 'Python Lab 2.4: CSV library record manager';
    $assignname = $ja ? 'プロジェクト2.4：CSV図書記録管理' : 'Project 2.4: CSV library record manager';
    $path = $ja ? '/ja/P2_csv_library_manager.ipynb' : '/P2_csv_library_manager.ipynb';
    $sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topic], '*', MUST_EXIST);
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id], '*', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $assign = $DB->get_record('assign', ['course' => $course->id, 'name' => $assignname], '*', MUST_EXIST);
    foreach (['PYAI-V24-PROJECT24-LIBRARY', 'data/books.csv', 'B005', 'B003', 'B001', 'B004', 'ValueError', 'KeyError', 'ALL TESTS PASSED', 'library_manager.py'] as $token) {
        if (!str_contains($page->content, $token) || !str_contains($assign->intro, $token)) throw new RuntimeException("$shortname missing $token");
    }
    foreach (['parse_read', 'run_project', 'PROGRAM INCOMPLETE', $ja ? '右クリック' : 'Right-click'] as $flowtoken) {
        if (!str_contains($page->content, $flowtoken) || !str_contains($assign->intro, $flowtoken)) throw new RuntimeException("$shortname missing learner-flow token $flowtoken");
    }
    foreach (['find_book(books, book_id)', 'add_book(books, book_id, title)', 'save_books(books, path)', 'B001,Python Foundations,false', $ja ? '更新依頼を別ファイルやキーボードからは' : 'not another file'] as $contracttoken) {
        if (!str_contains($page->content, $contracttoken) || !str_contains($assign->intro, $contracttoken)) throw new RuntimeException("$shortname missing public-contract token $contracttoken");
    }
    if (str_contains($page->content, '\\nTOTAL BOOKS') || str_contains($assign->intro, '\\nTOTAL BOOKS')) throw new RuntimeException("$shortname contains literal newline escapes");
    foreach (['PYAI-V25-PROJECT24-LEARNER-BRIEF', 'id,title,read', 'B004,"Writing, Presenting, and Learning",true', $ja ? 'この課題で行うこと' : 'What you will do', $ja ? '完成済みの' : 'completed', $ja ? '編集または上書きしてはいけません' : 'Do not edit or overwrite it'] as $briefToken) {
        if (!str_contains($page->content, $briefToken) || !str_contains($assign->intro, $briefToken)) throw new RuntimeException("$shortname missing learner-brief token $briefToken");
    }
    $beforeHeader = $ja ? '<th>ID</th><th>更新前</th><th>操作</th><th>更新後</th>' : '<th>ID</th><th>Before</th><th>Operation</th><th>After</th>';
    $functionHeader = $ja ? '<th>関数</th><th>引数と役割</th><th>戻り値・状態変化・例外</th>' : '<th>Function</th><th>Inputs and responsibility</th><th>Return, mutation, and exceptions</th>';
    if (!str_contains($page->content, $beforeHeader) || !str_contains($page->content, $functionHeader)) throw new RuntimeException("$shortname table columns are not separated");
    foreach ([$ja ? 'Pythonプログラムを一から作成しません' : 'do not create the program from an empty file', 'projects/library-manager/library_manager.py', $ja ? '編集するファイルは' : 'Edit only'] as $starterToken) {
        if (!str_contains($page->content, $starterToken) || !str_contains($assign->intro, $starterToken)) throw new RuntimeException("$shortname missing starter instruction $starterToken");
    }
    $whitespaceRule = $ja ? 'IDと書名は、検証・検索・保存の前に前後の空白を' : 'IDs and titles are stripped';
    if (substr_count($page->content, $whitespaceRule) !== 1 || substr_count($assign->intro, $whitespaceRule) !== 1) throw new RuntimeException("$shortname whitespace rule must appear exactly once");
    if (!str_ends_with($lti->toolurl, $path)) throw new RuntimeException("$shortname LTI path");
    $modinfo = get_fast_modinfo($course);
    $names = [];
    foreach (array_filter(array_map('intval', explode(',', (string)$section->sequence))) as $cmid) $names[] = $modinfo->get_cm($cmid)->name;
    if ($names !== [$pagename, $ltiname, $assignname]) throw new RuntimeException("$shortname activity order: " . implode(' | ', $names));
    $fileenabled = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => 'enabled']);
    $allowed = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => 'allowedfiletypes']);
    $maxfiles = $DB->get_field('assign_plugin_config', 'value', ['assignment' => $assign->id, 'plugin' => 'file', 'subtype' => 'assignsubmission', 'name' => 'maxfilesubmissions']);
    if ($fileenabled !== '1' || $allowed !== '.py' || $maxfiles !== '1' || (float)$assign->grade !== 100.0) throw new RuntimeException("$shortname assignment policy");
    $results[] = ['shortname' => $shortname, 'topic' => $topic, 'activities' => $names, 'lti_path' => $path, 'submission' => ['maxfiles' => 1, 'allowed' => '.py']];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
