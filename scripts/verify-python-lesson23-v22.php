<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once $CFG->libdir . '/gradelib.php';

$results = [];
foreach (['PYAI-INTRO', 'PYAI-INTRO-JA'] as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $ja = $shortname === 'PYAI-INTRO-JA';
    $chaptername = $ja ? '第2章 — データ構造・関数・ファイル処理' : 'Chapter 2 — Data Structures, Functions, and File Processing';
    $topic = $ja ? '2.3 ファイル・CSVの読み書き' : '2.3 File and CSV input/output';
    $project = $ja ? '2.4 実践プロジェクト：学習センター月次実績報告' : '2.4 Applied project: Monthly centre performance report';
    $pagename = $ja ? 'レッスン2.3：ファイル・CSVの読み書き' : 'Lesson 2.3: File and CSV input/output';
    $ltiname = $ja ? 'Python Lab 2.3：ファイル・CSVの読み書き' : 'Python Lab 2.3: File and CSV input/output';
    $quizname = $ja ? '理解度チェック：2.3 ファイル・CSVの読み書き' : 'Knowledge check: 2.3 File and CSV input/output';
    $projectlti = $ja ? 'Python Lab 2.4：学習センター月次実績報告' : 'Python Lab 2.4: Monthly centre performance report';
    $projectassign = $ja ? 'プロジェクト2.4：学習センター月次実績報告' : 'Project 2.4: Monthly learning-centre performance report';

    $parent = null;
    foreach (get_fast_modinfo($course)->get_section_info_all() as $candidate) {
        if ($candidate && empty($candidate->component) && $candidate->name === $chaptername) $parent = $candidate;
    }
    if (!$parent) throw new RuntimeException("$shortname chapter missing");
    $sub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $topic], '*', MUST_EXIST);
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id], '*', MUST_EXIST);
    $projectsub = $DB->get_record('subsection', ['course' => $course->id, 'name' => $project], '*', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pagename], '*', MUST_EXIST);
    if (substr_count($page->content, 'PYAI-V22-LESSON23-FLOW') !== 1) throw new RuntimeException("$shortname page marker");
    $required = $ja
        ? ['Path.cwd()', '__file__', 'FileNotFoundError', 'with', 'newline', 'split', 'csv', 'DictReader', '文字列', 'bool', 'ValueError', '重複ID', 'DictWriter', 'writeheader', '再読込', '元データ']
        : ['Path.cwd()', '__file__', 'FileNotFoundError', 'with', 'newline', 'split', 'csv', 'DictReader', 'strings', 'bool', 'ValueError', 'duplicate ID', 'DictWriter', 'writeheader', 'Reload', 'source'];
    foreach ($required as $needle) if (!str_contains($page->content, $needle)) throw new RuntimeException("$shortname page missing $needle");
    foreach (['pandas', 'DataFrame', 'Naledi', 'ナレディ', 'AI checkpoint', '教師用ガイド'] as $forbidden) if (str_contains($page->content, $forbidden)) throw new RuntimeException("$shortname forbidden $forbidden");

    $lti = $DB->get_record('lti', ['course' => $course->id, 'name' => $ltiname], '*', MUST_EXIST);
    $expectedpath = $ja ? '/ja/07_files_csv.ipynb' : '/07_files_csv.ipynb';
    if (!str_ends_with($lti->toolurl, $expectedpath)) throw new RuntimeException("$shortname LTI path");
    $quiz = $DB->get_record('quiz', ['course' => $course->id, 'name' => $quizname], '*', MUST_EXIST);
    $slots = (int)$DB->count_records('quiz_slots', ['quizid' => $quiz->id]);
    if ($slots !== 10 || abs((float)$quiz->sumgrades - 100.0) > 0.001 || (int)$quiz->attempts !== 0 || $quiz->grademethod != QUIZ_GRADEHIGHEST) throw new RuntimeException("$shortname quiz contract");
    $gradeitem = grade_item::fetch(['courseid' => $course->id, 'itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id, 'itemnumber' => 0]);
    if (!$gradeitem || abs((float)$gradeitem->gradepass - 90.0) > 0.001 || (int)$DB->count_records('quiz_feedback', ['quizid' => $quiz->id]) !== 5) throw new RuntimeException("$shortname mastery policy");
    $DB->get_record('lti', ['course' => $course->id, 'name' => $projectlti], '*', MUST_EXIST);
    $DB->get_record('assign', ['course' => $course->id, 'name' => $projectassign], '*', MUST_EXIST);

    $modinfo = get_fast_modinfo($course);
    $activitynames = [];
    foreach (array_filter(array_map('intval', explode(',', (string)$section->sequence))) as $cmid) $activitynames[] = $modinfo->get_cm($cmid)->name;
    if ($activitynames !== [$pagename, $ltiname, $quizname]) throw new RuntimeException("$shortname activity order");
    $parentids = array_filter(array_map('intval', explode(',', (string)$DB->get_field('course_sections', 'sequence', ['id' => $parent->id], MUST_EXIST))));
    $subcm = get_coursemodule_from_instance('subsection', $sub->id, $course->id, false, MUST_EXIST);
    $projectcm = get_coursemodule_from_instance('subsection', $projectsub->id, $course->id, false, MUST_EXIST);
    $subposition = array_search((int)$subcm->id, $parentids, true);
    $projectposition = array_search((int)$projectcm->id, $parentids, true);
    if ($subposition === false || $projectposition === false || $subposition >= $projectposition) {
        throw new RuntimeException("$shortname subsection order: 2.3=" . var_export($subposition, true) . ", 2.4=" . var_export($projectposition, true) . ", sequence=" . implode(',', $parentids));
    }

    $results[] = ['shortname' => $shortname, 'chapter' => $chaptername, 'topic' => $topic, 'activities' => $activitynames, 'quiz_slots' => $slots, 'lti_path' => $expectedpath, 'project_after' => $project];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
