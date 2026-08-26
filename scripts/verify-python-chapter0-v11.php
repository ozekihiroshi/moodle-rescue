<?php
define('CLI_SCRIPT', true);

require '/var/www/html/config.php';

$shortnames = ['PYAI-INTRO', 'PYAI-INTRO-JA'];
$result = [];
foreach ($shortnames as $shortname) {
    $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
    $pages = $DB->get_records('page', ['course' => $course->id]);
    $allhtml = '';
    $markers = ['PYAI-V11-CHAPTER0-GUIDE' => 0, 'PYAI-V11-CHAPTER0-LAB' => 0];
    $forbidden = ['Naledi', 'ナレディ', 'AI checkpoint', 'AI利用の確認', 'Teacher guide', '教師用ガイド'];
    $forbiddencounts = array_fill_keys($forbidden, 0);
    foreach ($pages as $page) {
        $html = (string) $page->name . (string) $page->intro . (string) $page->content;
        $allhtml .= $html;
        foreach ($markers as $marker => $count) {
            $markers[$marker] += substr_count($html, $marker);
        }
        foreach ($forbidden as $text) {
            $forbiddencounts[$text] += substr_count($html, $text);
        }
    }
    foreach ($markers as $marker => $count) {
        if ($count !== 1) {
            throw new RuntimeException("{$shortname}: expected one {$marker}, found {$count}");
        }
    }
    foreach ($forbiddencounts as $text => $count) {
        if ($count !== 0) {
            throw new RuntimeException("{$shortname}: forbidden text {$text} found {$count} time(s)");
        }
    }
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 0], '*', MUST_EXIST);
    $result[] = [
        'courseid' => (int) $course->id,
        'shortname' => $shortname,
        'section0' => $section->name,
        'pages' => count($pages),
        'markers' => $markers,
        'forbidden' => $forbiddencounts,
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
