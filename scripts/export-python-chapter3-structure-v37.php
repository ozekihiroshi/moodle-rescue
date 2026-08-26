<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$modinfo = get_fast_modinfo($course);
$result = ['course' => $shortname, 'activities' => []];

foreach ($modinfo->get_cms() as $cm) {
    if (!in_array($cm->modname, ['page', 'quiz', 'lti', 'assign'], true)) {
        continue;
    }
    if (!preg_match('/(?:Lesson|レッスン|Knowledge check|理解度チェック|Project|プロジェクト|Stage|段階|Submit|提出|Python Lab|中間)/u', $cm->name)) {
        continue;
    }
    $instance = $DB->get_record($cm->modname, ['id' => $cm->instance], '*', MUST_EXIST);
    $section = $modinfo->get_section_info($cm->sectionnum, MUST_EXIST);
    $entry = [
        'cmid' => (int)$cm->id,
        'modname' => $cm->modname,
        'name' => $cm->name,
        'section_number' => (int)$cm->sectionnum,
        'section_name' => $section->name ?? '',
    ];
    if ($cm->modname === 'page') {
        $entry['content'] = $instance->content;
    }
    if ($cm->modname === 'quiz') {
        $entry['slots'] = (int)$DB->count_records('quiz_slots', ['quizid' => $instance->id]);
    }
    $result['activities'][] = $entry;
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
