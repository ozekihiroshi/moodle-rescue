<?php
// Read-only migration inventory. No lesson text or credentials are emitted.
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
$items = [];
foreach ($DB->get_records('lessonmark', null, 'id ASC') as $item) {
    $cm = get_coursemodule_from_instance('lessonmark', $item->id, $item->course, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $files = [];
    foreach (get_file_storage()->get_area_files($context->id, 'mod_lessonmark', 'content', false,
            'filepath, filename', false) as $file) {
        $files[] = [$file->get_filepath(), $file->get_filename(), $file->get_contenthash(),
            hash('sha256', $file->get_content())];
    }
    $items[] = ['id' => $item->id, 'courseid' => $item->course, 'cmid' => $cm->id,
        'recordsha256' => hash('sha256', json_encode($item)),
        'sourcesha256' => hash('sha256', $item->markdownsource), 'files' => $files];
}
if (!$items) {
    throw new RuntimeException('No LessonMark material exists; refusing empty migration verification.');
}
echo json_encode(['courses' => $DB->count_records('course'),
    'coursemodules' => $DB->count_records('course_modules'), 'lessonmark' => $items],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
