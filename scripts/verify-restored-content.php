<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$emptyhash = sha1('');
$record = $DB->get_record_sql(
    "SELECT pathnamehash, contenthash, filesize, component, filearea
       FROM {files}
      WHERE filesize > 0 AND contenthash <> :emptyhash
   ORDER BY id ASC",
    ['emptyhash' => $emptyhash],
    IGNORE_MULTIPLE
);
if (!$record) {
    throw new RuntimeException('The restored database contains no non-empty File API object.');
}

$file = get_file_storage()->get_file_by_hash($record->pathnamehash);
if (!$file || $file->is_directory()) {
    throw new RuntimeException('The representative restored File API object is unavailable.');
}

$handle = $file->get_content_file_handle();
if (!is_resource($handle)) {
    throw new RuntimeException('Unable to open the representative restored File API object.');
}

$hash = hash_init('sha1');
$bytes = 0;
try {
    while (!feof($handle)) {
        $chunk = fread($handle, 1048576);
        if ($chunk === false) {
            throw new RuntimeException('Unable to read the representative restored File API object.');
        }
        $bytes += strlen($chunk);
        hash_update($hash, $chunk);
    }
} finally {
    fclose($handle);
}

$actualhash = hash_final($hash);
if ($bytes !== (int)$record->filesize || !hash_equals($record->contenthash, $actualhash)) {
    throw new RuntimeException('The representative restored File API object failed verification.');
}

echo json_encode([
    'restoredFileApiVerified' => true,
    'contenthash' => $actualhash,
    'bytes' => $bytes,
    'component' => $record->component,
    'filearea' => $record->filearea,
], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
