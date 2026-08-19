<?php
// Creates deterministic invalid content recovery fixtures for the ZIP gate.

define('CLI_SCRIPT', true);
require '/var/www/html/config.php';

$directory = \tool_secure_s3_storage\local\database_backup_producer::get_builtin_directory();
if (!is_dir($directory) || is_link($directory)) {
    throw new RuntimeException('Built-in recovery directory is unavailable.');
}

$create = static function (
    string $compacttime,
    string $artifactid,
    bool $unknownfield,
    bool $corruptinventoryhash,
) use ($directory): void {
    $created = DateTimeImmutable::createFromFormat('!Ymd\THis\Z', $compacttime);
    if ($created === false) {
        throw new RuntimeException('Fixture timestamp is invalid.');
    }
    $recoverysetid = $compacttime . '-' . $artifactid;
    $inventory = 'moodle-content-' . $recoverysetid . '.jsonl.gz';
    $inventorypath = $directory . '/' . $inventory;
    $inventorydata = json_encode([
        'schema' => 'tool_secure_s3_storage.content-inventory/v1',
        'recoverysetid' => $recoverysetid,
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    $compressed = gzencode($inventorydata, 6);
    if ($compressed === false || file_put_contents($inventorypath, $compressed, LOCK_EX) !== strlen($compressed)) {
        throw new RuntimeException('Unable to write a content inventory fixture.');
    }

    $manifest = [
        'schema' => 'tool_secure_s3_storage.content-recovery/v1',
        'type' => 'content',
        'createdat' => $created->format('Y-m-d\TH:i:s\Z'),
        'recoverysetid' => $recoverysetid,
        'databaseartifactid' => $artifactid,
        'inventory' => $inventory,
        'inventorybytes' => strlen($compressed),
        'inventorysha256' => $corruptinventoryhash
            ? str_repeat('0', 64)
            : hash('sha256', $compressed),
        'objectcount' => 0,
        'contentbytes' => 0,
        'hashalgorithm' => 'sha1',
        'compression' => 'gzip',
    ];
    if ($unknownfield) {
        $manifest['unexpected'] = true;
    }
    $json = json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    $manifestpath = $inventorypath . '.manifest.json';
    if (file_put_contents($manifestpath, $json, LOCK_EX) !== strlen($json)) {
        throw new RuntimeException('Unable to write a content manifest fixture.');
    }
    chmod($inventorypath, 0600);
    chmod($manifestpath, 0600);
};

$create('20000101T000005Z', str_repeat('5', 32), true, false);
$create('20000101T000006Z', str_repeat('6', 32), false, true);

echo '{"contentNegativeFixtures":true}' . PHP_EOL;
