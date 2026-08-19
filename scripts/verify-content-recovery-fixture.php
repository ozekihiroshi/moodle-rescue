<?php
// Verifies a restored file through Moodle's File API.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$marker = getenv('S3_TEST_FILE_MARKER') ?: 'secure-s3-content-recovery-marker-v1';
$file = get_file_storage()->get_file(
    context_system::instance()->id,
    'tool_secure_s3_storage',
    'recoverytest',
    0,
    '/',
    'secure-s3-content-recovery-marker.txt'
);
if (!$file || !hash_equals($marker, $file->get_content())) {
    throw new RuntimeException('The restored File API marker did not match.');
}

echo json_encode([
    'contentFileApiRestoreGate' => true,
    'contenthash' => $file->get_contenthash(),
    'filesize' => $file->get_filesize(),
], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
