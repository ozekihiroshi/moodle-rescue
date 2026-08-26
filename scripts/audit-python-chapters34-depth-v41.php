<?php
// Compare the instructional depth of the completed Chapter 3 and draft Chapter 4.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$modinfo = get_fast_modinfo($course);
$result = [];

foreach ([3, 4] as $number) {
    $section = $modinfo->get_section_info($number, MUST_EXIST);
    $chapter = ['number' => $number, 'name' => $section->name, 'topics' => []];
    foreach ($modinfo->sections[$section->section] ?? [] as $subcmid) {
        $subcm = $modinfo->get_cm($subcmid);
        if ($subcm->modname !== 'subsection') {
            continue;
        }
        $sub = $DB->get_record('subsection', ['id' => $subcm->instance], '*', MUST_EXIST);
        $delegated = $DB->get_record('course_sections', [
            'course' => $course->id,
            'component' => 'mod_subsection',
            'itemid' => $sub->id,
        ], '*', MUST_EXIST);
        $topic = ['name' => $subcm->name, 'activities' => []];
        foreach ($modinfo->sections[$delegated->section] ?? [] as $cmid) {
            $cm = $modinfo->get_cm($cmid);
            $entry = ['cmid' => (int)$cm->id, 'module' => $cm->modname, 'name' => $cm->name];
            if ($cm->modname === 'page') {
                $page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
                $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($page->content))));
                $entry += [
                    'characters' => mb_strlen($plain),
                    'h2' => substr_count($page->content, '<h2>'),
                    'h3' => substr_count($page->content, '<h3>'),
                    'code_blocks' => substr_count($page->content, '<pre'),
                    'tables' => substr_count($page->content, '<table'),
                    'ordered_lists' => substr_count($page->content, '<ol'),
                    'unordered_lists' => substr_count($page->content, '<ul'),
                ];
            } elseif ($cm->modname === 'quiz') {
                $entry['questions'] = $DB->count_records('quiz_slots', ['quizid' => $cm->instance]);
            }
            $topic['activities'][] = $entry;
        }
        $chapter['topics'][] = $topic;
    }
    $result[] = $chapter;
}

echo json_encode(['course_id'=>(int)$course->id,'shortname'=>$course->shortname,'chapters'=>$result],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
