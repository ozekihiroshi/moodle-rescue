<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$pageNames = [
    'PYAI-INTRO' => [
        'Lesson 3.1: Tabular data, CSV, and pandas',
        'Lesson 3.2: Data selection, filtering, and Boolean logic',
        'Lesson 3.3: Data cleaning and audit records',
        'Lesson 3.4: Grouping and summary statistics',
    ],
    'PYAI-INTRO-JA' => [
        'レッスン3.1：表形式データ・CSV・pandas',
        'レッスン3.2：データの選択・抽出とブール論理',
        'レッスン3.3：データのクリーニングと監査記録',
        'レッスン3.4：グループ化と要約統計',
    ],
];

$export = [];
foreach ($pageNames as $shortname => $names) {
    $course = $DB->get_record('course', ['shortname' => $shortname], 'id,shortname', MUST_EXIST);
    foreach ($names as $name) {
        $page = $DB->get_record('page', ['course' => $course->id, 'name' => $name], '*', MUST_EXIST);
        $export[] = [
            'course' => $shortname,
            'page_id' => (int)$page->id,
            'name' => $page->name,
            'content' => $page->content,
            'timemodified' => (int)$page->timemodified,
        ];
    }
}

echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
