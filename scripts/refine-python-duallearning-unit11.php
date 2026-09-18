<?php
// One-time local refinement: avoid repeating Moodle's activity title inside Markdown.
define('CLI_SCRIPT', true);
require '/var/www/html/public/config.php';

if ($CFG->wwwroot !== 'http://localhost:8083') {
    throw new RuntimeException('Local evaluation only.');
}
$course = $DB->get_record('course', ['shortname' => 'DUAL-PY-11-PATH-JA'], '*', MUST_EXIST);
$expectedold = [
    'prepare' => ['cmidnumber' => 'dual-python-11-prepare',
        'hash' => 'd62195c47d9e39f079468fd05e186cac275525b9f38f8e75a3a7b6222a4cdbd6',
        'file' => '01-prepare.md'],
    'lesson' => ['cmidnumber' => 'dual-python-11-lesson',
        'hash' => 'e957ccb6426c2990f3e2e1c0744f24aa4344b83f1eb79f1d74690c1a4b0065cb',
        'file' => '02-lesson.md'],
    'review' => ['cmidnumber' => 'dual-python-11-review',
        'hash' => '8e8db87535f1873528762dc6236857d904342e5c44f71562ad7ac926e95f8954',
        'file' => '03-review.md'],
];
$updates = [];
foreach ($expectedold as $key => $entry) {
    $cm = $DB->get_record('course_modules', [
        'course' => $course->id, 'idnumber' => $entry['cmidnumber'],
    ], '*', MUST_EXIST);
    $lesson = $DB->get_record('lessonmark', ['id' => $cm->instance], '*', MUST_EXIST);
    $new = file_get_contents('/srv/python-duallearning-1-1/' . $entry['file']);
    if ($new === false || !mb_check_encoding($new, 'UTF-8')) {
        throw new RuntimeException('Invalid Markdown: ' . $entry['file']);
    }
    $oldhash = hash('sha256', $lesson->markdownsource);
    if ($oldhash !== $entry['hash'] && $lesson->markdownsource !== $new) {
        throw new RuntimeException('Existing learner content differs; refusing to overwrite ' . $key);
    }
    $updates[] = ['id' => (int)$lesson->id, 'name' => $key, 'text' => $new];
}
foreach ($updates as $update) {
    $DB->set_field('lessonmark', 'markdownsource', $update['text'], ['id' => $update['id']]);
}
rebuild_course_cache($course->id, true);
echo json_encode(['courseid' => (int)$course->id, 'updated' => array_column($updates, 'name')],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
