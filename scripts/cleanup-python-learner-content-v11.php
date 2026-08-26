<?php
define('CLI_SCRIPT', true);

require '/var/www/html/config.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . '/course/modlib.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$language = $shortname === 'PYAI-INTRO-JA' ? 'ja' : 'en';
\core\session\manager::set_user(get_admin());

$deletepagenames = [
    'Meet Naledi: One reporting task that grows with the course',
    'ナレディの紹介：コースとともに発展する一つの報告業務',
    'Teacher guide (hidden from students)',
    '教師用ガイド（学習者には非表示）',
    'Teacher guide: Misconception-driven learning checks (hidden)',
    '教師用ガイド：誤解から学ぶ理解度チェック（非表示）',
];

$pagesdeleted = 0;
foreach ($deletepagenames as $deletepagename) {
    if ($deletepage = $DB->get_record('page', ['course' => $course->id, 'name' => $deletepagename])) {
        $cm = get_coursemodule_from_instance('page', $deletepage->id, $course->id, false, MUST_EXIST);
        course_delete_module($cm->id);
        $pagesdeleted++;
    }
}

$referencesneutralised = 0;
foreach ($DB->get_records('page', ['course' => $course->id]) as $page) {
    $before = [$page->name, $page->intro, $page->content];
    $replacement = $language === 'ja' ? '担当者' : 'the analyst';
    foreach (['Naledi', 'ナレディ'] as $narrativename) {
        $page->name = str_replace($narrativename, $replacement, $page->name);
        $page->intro = str_replace($narrativename, $replacement, $page->intro);
        $page->content = str_replace($narrativename, $replacement, $page->content);
    }
    if ($before !== [$page->name, $page->intro, $page->content]) {
        $page->timemodified = time();
        $DB->update_record('page', $page);
        $referencesneutralised++;
    }
}

rebuild_course_cache($course->id, true);
echo json_encode([
    'courseid' => (int) $course->id,
    'shortname' => $shortname,
    'pages_deleted' => $pagesdeleted,
    'pages_neutralised' => $referencesneutralised,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
