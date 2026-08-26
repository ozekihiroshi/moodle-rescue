<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$ja = $shortname === 'PYAI-INTRO-JA';
$name = $ja ? 'レッスン4.1：レコードと関数からオブジェクトへ'
    : 'Lesson 4.1: From records and functions to objects';
$page = $DB->get_record('page', ['course' => $course->id, 'name' => $name], '*', MUST_EXIST);
$required = ['PYAI-V40-LESSON41-COMPARISON', 'item = {', 'def loan_item', 'class EquipmentItem',
    'def __init__', 'self.borrower_id', 'def loan_to', '==', 'is'];
foreach ($required as $value) {
    if (!str_contains($page->content, $value)) {
        throw new RuntimeException("Missing 4.1 comparison content: {$value}");
    }
}
if (substr_count($page->content, '<h2>') < 8 || substr_count($page->content, '<pre ') < 2) {
    throw new RuntimeException('4.1 hierarchy or worked-code count is incomplete');
}
echo json_encode(['status'=>'ok','course_id'=>(int)$course->id,'shortname'=>$course->shortname,
    'page_id'=>(int)$page->id,'h2_count'=>substr_count($page->content,'<h2>'),
    'code_blocks'=>substr_count($page->content,'<pre '),'marker'=>'PYAI-V40-LESSON41-COMPARISON'],
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
