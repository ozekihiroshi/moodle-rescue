<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$result = [];
foreach ([181,185,189,289] as $cmid) {
    $cm = get_coursemodule_from_id('page', $cmid, 0, false, MUST_EXIST);
    $page = $DB->get_record('page', ['id'=>$cm->instance], '*', MUST_EXIST);
    $result[] = ['cmid'=>(int)$cmid, 'name'=>$page->name, 'content'=>$page->content];
}
echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), PHP_EOL;

