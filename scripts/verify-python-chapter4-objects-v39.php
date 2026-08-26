<?php
// Verify the inserted objects/classes chapter and shifted later chapters.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';
$modinfo = get_fast_modinfo($course);

$chapternames = [];
$normal = [];
foreach ($modinfo->get_section_info_all() as $section) {
    if ($section && $section->section > 0 && empty($section->component)) {
        $chapternames[] = $section->name;
        $normal[(int)$section->section] = $section;
    }
}
$expectedchapters = $ja
    ? ['第1章 — プログラミングの基礎と基本データ','第2章 — データ構造・関数・ファイル処理','第3章 — 表形式データの分析','第4章 — オブジェクトとクラス','第5章 — 根拠を伝える','第6章 — より大きなデータへの拡張']
    : ['Chapter 1 — Programming Foundations and Scalar Values','Chapter 2 — Data Structures, Functions, and File Processing','Chapter 3 — Analysing Tabular Data','Chapter 4 — Objects and Classes','Chapter 5 — Communicating Evidence','Chapter 6 — Scaling Up'];
if ($chapternames !== $expectedchapters) {
    throw new RuntimeException('Top-level chapter order mismatch: ' . json_encode($chapternames, JSON_UNESCAPED_UNICODE));
}

$topics = $ja
    ? [
        ['4.1 レコードと関数からオブジェクトへ','page','レッスン4.1：レコードと関数からオブジェクトへ','lti','Python Lab 4.1：オブジェクトとクラス','quiz','理解度チェック：4.1 オブジェクトとクラス','/ja/13_objects_classes.ipynb'],
        ['4.2 状態・メソッド・正しいオブジェクト','page','レッスン4.2：状態・メソッド・正しいオブジェクト','lti','Python Lab 4.2：状態と検証','quiz','理解度チェック：4.2 状態と検証','/ja/14_object_state_validation.ipynb'],
        ['4.3 複数オブジェクト・合成・責任分担','page','レッスン4.3：複数オブジェクト・合成・責任分担','lti','Python Lab 4.3：合成と責任分担','quiz','理解度チェック：4.3 合成と責任分担','/ja/15_composition_responsibility.ipynb'],
        ['4.4 オブジェクトの保存とテスト','page','レッスン4.4：オブジェクトの保存とテスト','lti','Python Lab 4.4：保存とテスト','quiz','理解度チェック：4.4 保存とテスト','/ja/16_object_persistence_testing.ipynb'],
        ['4.5 応用プロジェクト：共用機材貸出窓口','page','4.5 課題仕様と完成条件','lti','Python Lab 4.5：共用機材貸出窓口','assign','提出課題4.5：共用機材貸出窓口','/ja/P4_equipment_lending.ipynb'],
    ]
    : [
        ['4.1 From records and functions to objects','page','Lesson 4.1: From records and functions to objects','lti','Python Lab 4.1: Objects and classes','quiz','Knowledge check: 4.1 Objects and classes','/13_objects_classes.ipynb'],
        ['4.2 State, methods, and valid objects','page','Lesson 4.2: State, methods, and valid objects','lti','Python Lab 4.2: State and validation','quiz','Knowledge check: 4.2 State and validation','/14_object_state_validation.ipynb'],
        ['4.3 Collections, composition, and responsibility','page','Lesson 4.3: Collections, composition, and responsibility','lti','Python Lab 4.3: Composition and responsibility','quiz','Knowledge check: 4.3 Composition and responsibility','/15_composition_responsibility.ipynb'],
        ['4.4 Persistence and testing class-based programs','page','Lesson 4.4: Persistence and testing class-based programs','lti','Python Lab 4.4: Persistence and testing','quiz','Knowledge check: 4.4 Persistence and testing','/16_object_persistence_testing.ipynb'],
        ['4.5 Applied project: Community equipment lending desk','page','4.5 Project brief and completion contract','lti','Python Lab 4.5: Community equipment lending desk','assign','Assignment 4.5: Community equipment lending desk','/P4_equipment_lending.ipynb'],
    ];

$chapter = $normal[4];
$chaptercmids = $modinfo->sections[$chapter->section] ?? [];
if (count($chaptercmids) !== 5) {
    throw new RuntimeException('Chapter 4 must contain five subsections');
}
$report = [];
foreach ($topics as $position => [$topicname,$type1,$name1,$type2,$name2,$type3,$name3,$path]) {
    $subcm = $modinfo->get_cm($chaptercmids[$position]);
    if ($subcm->modname !== 'subsection' || $subcm->name !== $topicname) {
        throw new RuntimeException("Subsection mismatch at position {$position}");
    }
    $sub = $DB->get_record('subsection', ['id' => $subcm->instance], '*', MUST_EXIST);
    $delegated = $DB->get_record('course_sections', [
        'course' => $course->id, 'component' => 'mod_subsection', 'itemid' => $sub->id,
    ], '*', MUST_EXIST);
    $cmids = $modinfo->sections[$delegated->section] ?? [];
    if (count($cmids) !== 3) {
        throw new RuntimeException("{$topicname} must contain three activities");
    }
    $expected = [[$type1,$name1],[$type2,$name2],[$type3,$name3]];
    foreach ($cmids as $index => $cmid) {
        $cm = $modinfo->get_cm($cmid);
        if ([$cm->modname,$cm->name] !== $expected[$index]) {
            throw new RuntimeException("Activity mismatch in {$topicname}");
        }
    }
    $pagecm = $modinfo->get_cm($cmids[0]);
    $page = $DB->get_record('page', ['id' => $pagecm->instance], '*', MUST_EXIST);
    if ($position < 4) {
        if (!str_contains($page->content, 'PYAI-V39-OBJECTS-CLASSES') || substr_count($page->content, '<h2>') < 8) {
            throw new RuntimeException("Lesson structure marker or h2 hierarchy missing: {$name1}");
        }
    } elseif (!str_contains($page->content, 'PYAI-V39-PROJECT45')) {
        throw new RuntimeException('Project page marker missing');
    }
    $lticm = $modinfo->get_cm($cmids[1]);
    $lti = $DB->get_record('lti', ['id' => $lticm->instance], '*', MUST_EXIST);
    if (!str_ends_with($lti->toolurl, $path)) {
        throw new RuntimeException("Unexpected LTI path: {$lti->toolurl}");
    }
    if ($position < 4) {
        $quizcm = $modinfo->get_cm($cmids[2]);
        $quiz = $DB->get_record('quiz', ['id' => $quizcm->instance], '*', MUST_EXIST);
        $slots = $DB->get_records('quiz_slots', ['quizid' => $quiz->id]);
        if (count($slots) !== 10 || (float)$quiz->grade !== 100.0 || (int)$quiz->attempts !== 0) {
            throw new RuntimeException("Quiz contract mismatch: {$name3}");
        }
    }
    $report[] = ['topic'=>$topicname,'subsection_cmid'=>(int)$subcm->id,'activity_cmids'=>array_map('intval',$cmids)];
}

$shifted = $ja
    ? ['5.1 可視化と根拠','5.2 ガイド付きプロジェクト：学習センター分析','5.3 最終プロジェクト：問いから根拠へ','6.1 大きなCSVファイルを安全に処理する','6.2 スケールアップ総合プロジェクト']
    : ['5.1 Visualisation and evidence','5.2 Guided project: Learning-centre analysis','5.3 Final project: From question to evidence','6.1 Processing larger CSV files safely','6.2 Scale-up capstone project'];
foreach ($shifted as $name) {
    if (!$DB->record_exists('subsection', ['course' => $course->id, 'name' => $name])) {
        throw new RuntimeException("Shifted subsection missing: {$name}");
    }
}

echo json_encode([
    'status'=>'ok','course_id'=>(int)$course->id,'shortname'=>$course->shortname,
    'chapter_count'=>count($chapternames),'chapter4_topics'=>$report,
    'quiz_count'=>4,'questions'=>40,'shifted_subsections'=>$shifted,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
