<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$cmids = [35,37,39,41,43,45,181,183,185,187,189,191,267,271,275,279,285,289];
$result = [];
foreach ($cmids as $cmid) {
    $cm = get_coursemodule_from_id('page', $cmid, 0, false, MUST_EXIST);
    $page = $DB->get_record('page', ['id'=>$cm->instance], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id'=>$cm->course], 'id,shortname', MUST_EXIST);
    $result[] = [
        'shortname'=>$course->shortname,
        'cmid'=>(int)$cmid,
        'pageid'=>(int)$page->id,
        'name'=>$page->name,
        'content'=>$page->content,
        'contentformat'=>(int)$page->contentformat,
        'timemodified'=>(int)$page->timemodified,
    ];
}
echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), PHP_EOL;

