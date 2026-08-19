<?php
// Creates a deterministic non-empty Moodle file-pool object for the recovery gate.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$marker = getenv('S3_TEST_FILE_MARKER') ?: 'secure-s3-content-recovery-marker-v1';
$filename = 'secure-s3-content-recovery-marker.txt';
$context = context_system::instance();
$filestorage = get_file_storage();
$existing = $filestorage->get_file(
    $context->id,
    'tool_secure_s3_storage',
    'recoverytest',
    0,
    '/',
    $filename
);
if ($existing) {
    $existing->delete();
}
$file = $filestorage->create_file_from_string([
    'contextid' => $context->id,
    'component' => 'tool_secure_s3_storage',
    'filearea' => 'recoverytest',
    'itemid' => 0,
    'filepath' => '/',
    'filename' => $filename,
], $marker);

echo json_encode([
    'contenthash' => $file->get_contenthash(),
    'filesize' => $file->get_filesize(),
    'marker' => $marker,
], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
