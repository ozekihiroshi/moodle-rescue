<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';
\core\session\manager::set_user(get_admin());

$names = [
    'PYAI-INTRO' => 'Lesson 3.4: Grouping and summary statistics',
    'PYAI-INTRO-JA' => 'レッスン3.4：グループ化と要約統計',
];

foreach ($names as $shortname => $pageName) {
    $course = $DB->get_record('course', ['shortname' => $shortname], 'id,shortname', MUST_EXIST);
    $page = $DB->get_record('page', ['course' => $course->id, 'name' => $pageName], '*', MUST_EXIST);
    $rankingText = strpos($page->content, '>3.4.4 ');
    $proportionText = strpos($page->content, '>3.4.5 ');
    $transferText = strpos($page->content, '>3.4.7 ');
    if ($rankingText === false || $proportionText === false || $transferText === false) {
        throw new RuntimeException("$shortname cannot locate 3.4 blocks");
    }
    if ($rankingText > $proportionText) {
        $rankingStart = strrpos(substr($page->content, 0, $rankingText), '<h3 style=');
        $transferStart = strrpos(substr($page->content, 0, $transferText), '<h3 style=');
        $rankingBlock = substr($page->content, $rankingStart, $transferStart - $rankingStart);
        $page->content = substr_replace($page->content, '', $rankingStart, $transferStart - $rankingStart);
        $proportionText = strpos($page->content, '>3.4.5 ');
        $proportionStart = strrpos(substr($page->content, 0, $proportionText), '<h3 style=');
        $page->content = substr_replace($page->content, $rankingBlock, $proportionStart, 0);
        $page->timemodified = time();
        $DB->update_record('page', $page);
    }
    echo json_encode(['course' => $shortname, 'page' => $pageName, 'reordered' => $rankingText > $proportionText], JSON_UNESCAPED_UNICODE), PHP_EOL;
    rebuild_course_cache($course->id, true);
}
