<?php
// Export the live hierarchy before inserting the object-oriented programming chapter.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO';
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$modinfo = get_fast_modinfo($course);
$outline = [];

foreach ($modinfo->get_section_info_all() as $section) {
    if (!$section || $section->section === 0 || !empty($section->component)) {
        continue;
    }

    $chapter = [
        'section' => (int) $section->section,
        'section_id' => (int) $section->id,
        'name' => (string) $section->name,
        'subsections' => [],
    ];

    foreach ($modinfo->sections[$section->section] ?? [] as $cmid) {
        $cm = $modinfo->get_cm($cmid);
        if ($cm->modname !== 'subsection') {
            $chapter['subsections'][] = [
                'cmid' => (int) $cm->id,
                'name' => (string) $cm->name,
                'module' => (string) $cm->modname,
            ];
            continue;
        }

        $instance = $DB->get_record('subsection', ['id' => $cm->instance], '*', MUST_EXIST);
        $delegated = $DB->get_record('course_sections', [
            'course' => $course->id,
            'component' => 'mod_subsection',
            'itemid' => $instance->id,
        ], '*', MUST_EXIST);
        $activities = [];
        foreach ($modinfo->sections[$delegated->section] ?? [] as $activitycmid) {
            $activity = $modinfo->get_cm($activitycmid);
            $activities[] = [
                'cmid' => (int) $activity->id,
                'name' => (string) $activity->name,
                'module' => (string) $activity->modname,
                'instance' => (int) $activity->instance,
            ];
        }
        $chapter['subsections'][] = [
            'cmid' => (int) $cm->id,
            'instance' => (int) $instance->id,
            'delegated_section' => (int) $delegated->section,
            'name' => (string) $cm->name,
            'module' => 'subsection',
            'activities' => $activities,
        ];
    }
    $outline[] = $chapter;
}

echo json_encode([
    'course_id' => (int) $course->id,
    'shortname' => $course->shortname,
    'fullname' => $course->fullname,
    'chapters' => $outline,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
