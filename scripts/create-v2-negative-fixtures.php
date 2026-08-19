<?php
// Creates deterministic invalid v2 artifacts for the isolated release gate.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$directory = getenv('S3_TEST_DATABASE_ARTIFACT_DIRECTORY');
if ($directory === false || trim($directory) === '') {
    $directory = $CFG->dataroot . '/tool_secure_s3_storage/database';
}
$directory = rtrim($directory, '/');
if (!is_dir($directory) || is_link($directory) || !is_writable($directory)) {
    throw new RuntimeException('Built-in database artifact directory is unavailable.');
}
$readergid = getenv('S3_TEST_DATABASE_ARTIFACT_READER_GID');
if ($readergid === false || !preg_match('/^[0-9]+$/D', $readergid)) {
    throw new RuntimeException('Database artifact reader GID is invalid.');
}
$readergid = (int)$readergid;

$writefixture = static function (string $compacttime, string $artifactid, bool $malformed) use (
    $directory,
    $CFG,
    $readergid
): void {
    $payload = "moodle-db-{$compacttime}-{$artifactid}.xml.gz";
    $payloadpath = $directory . '/' . $payload;
    $contents = gzencode('<invalid-fixture/>', 6);
    if ($contents === false || file_put_contents($payloadpath, $contents, LOCK_EX) !== strlen($contents)) {
        throw new RuntimeException('Unable to write v2 negative payload fixture.');
    }
    if (!chgrp($payloadpath, $readergid) || !chmod($payloadpath, 0640)) {
        throw new RuntimeException('Unable to secure v2 negative payload fixture.');
    }

    $created = DateTimeImmutable::createFromFormat('!Ymd\THis\Z', $compacttime);
    if ($created === false) {
        throw new RuntimeException('Invalid fixture timestamp.');
    }
    $manifest = [
        'schema' => 'tool_secure_s3_storage.artifact/v2',
        'artifactid' => $artifactid,
        'type' => 'database',
        'createdat' => $created->format('Y-m-d\TH:i:s\Z'),
        'payload' => $payload,
        'bytes' => strlen($contents),
        'sha256' => $malformed ? hash('sha256', $contents) : str_repeat('0', 64),
        'format' => 'moodle-dtl-xml',
        'compression' => 'gzip',
        'encryption' => 'none',
        'recoverysetid' => $compacttime . '-' . $artifactid,
        'moodleversion' => (int)$CFG->version,
        'moodlerelease' => (string)$CFG->release,
        'dbtype' => (string)$CFG->dbtype,
    ];
    if ($malformed) {
        $manifest['unexpected'] = 'reject';
    }
    $json = json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    $manifestpath = $payloadpath . '.manifest.json';
    if (file_put_contents($manifestpath, $json, LOCK_EX) !== strlen($json)) {
        throw new RuntimeException('Unable to write v2 negative manifest fixture.');
    }
    if (!chgrp($manifestpath, $readergid) || !chmod($manifestpath, 0640)) {
        throw new RuntimeException('Unable to secure v2 negative manifest fixture.');
    }
};

$writefixture('20000101T000003Z', str_repeat('3', 32), true);
$writefixture('20000101T000004Z', str_repeat('4', 32), false);

echo "v2_negative_fixtures_created=yes\n";
