<?php
// This file is part of the local Moodle Rescue integration environment.

define('CLI_SCRIPT', true);

require '/var/www/html/config.php';

$sourcecourseid = (int) (getenv('S3_TEST_SOURCE_COURSE_ID') ?: 0);
$marker = getenv('S3_TEST_CONTENT_MARKER') ?: 'secure-s3-integration-marker-v1';

$sql = "SELECT c.id, c.shortname, c.fullname, p.id AS pageid, p.name, p.content
          FROM {course} c
          JOIN {course_modules} cm ON cm.course = c.id
          JOIN {modules} m ON m.id = cm.module AND m.name = :modname
          JOIN {page} p ON p.id = cm.instance
         WHERE c.id <> :siteid
           AND c.id <> :sourcecourseid
           AND " . $DB->sql_like('p.content', ':marker') . "
      ORDER BY c.id DESC";

$record = $DB->get_record_sql($sql, [
    'modname' => 'page',
    'siteid' => SITEID,
    'sourcecourseid' => $sourcecourseid,
    'marker' => '%' . $DB->sql_like_escape($marker) . '%',
], IGNORE_MULTIPLE);

if (!$record) {
    fwrite(STDERR, "Restored course containing the verification marker was not found.\n");
    exit(1);
}

echo json_encode([
    'restoredcourseid' => $record->id,
    'shortname' => $record->shortname,
    'fullname' => $record->fullname,
    'pageid' => $record->pageid,
    'pagename' => $record->name,
    'markerverified' => true,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
