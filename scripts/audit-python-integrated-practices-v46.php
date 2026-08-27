<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$modinfo = get_fast_modinfo($course);
$result = ['shortname' => $shortname, 'lessons' => []];

for ($chapter = 1; $chapter <= 6; $chapter++) {
    $section = $modinfo->get_section_info($chapter, MUST_EXIST);
    foreach ($modinfo->get_section_info_all() as $candidate) {
        if ($candidate->component !== 'mod_subsection' || !$candidate->itemid) {
            continue;
        }
        $subsection = $DB->get_record('subsection', ['id' => $candidate->itemid, 'course' => $course->id]);
        if (!$subsection) continue;
        $subcm = get_coursemodule_from_instance('subsection', $subsection->id, $course->id, false, MUST_EXIST);
        if ((int)$subcm->section !== (int)$section->id) continue;
        if (preg_match('/(?:project|プロジェクト|課題)/iu', $subsection->name)) continue;

        $sequence = array_values(array_filter(array_map('intval', explode(',', (string)$candidate->sequence))));
        foreach ($sequence as $cmid) {
            $cm = $modinfo->get_cm($cmid);
            if ($cm->modname !== 'page' || !$cm->visible) continue;
            $page = $DB->get_record('page', ['id' => $cm->instance], '*', MUST_EXIST);
            $result['lessons'][] = [
                'chapter' => $chapter,
                'subsection' => $subsection->name,
                'page' => $page->name,
                'pageid' => (int)$page->id,
                'cmid' => (int)$cm->id,
                'format' => (int)$page->contentformat,
                'content' => $page->content,
            ];
            break;
        }
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
