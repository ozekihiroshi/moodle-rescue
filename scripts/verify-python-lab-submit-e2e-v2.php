<?php
define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$shortname = getenv('PYTHON_COURSE_SHORTNAME') ?: 'PYAI-INTRO-JA';
$userid = (int)(getenv('PYTHON_LAB_TEST_USERID') ?: 3);
$course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
$cm = $DB->get_record('course_modules', [
    'course' => $course->id,
    'idnumber' => 'pyai-project-1-weekly-support',
], '*', MUST_EXIST);
$submission = $DB->get_record('assign_submission', [
    'assignment' => $cm->instance,
    'userid' => $userid,
    'latest' => 1,
]);

$result = [
    'course' => $shortname,
    'userid' => $userid,
    'exists' => (bool)$submission,
];
if ($submission) {
    $context = context_module::instance($cm->id);
    $files = get_file_storage()->get_area_files(
        $context->id,
        'assignsubmission_file',
        'submission_files',
        $submission->id,
        'id',
        false
    );
    $result += [
        'submission_id' => (int)$submission->id,
        'status' => $submission->status,
        'files' => array_values(array_map(static fn($file) => [
            'filename' => $file->get_filename(),
            'sha256' => hash('sha256', $file->get_content()),
            'bytes' => $file->get_filesize(),
        ], $files)),
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
exit(!empty($result['files']) ? 0 : 1);
