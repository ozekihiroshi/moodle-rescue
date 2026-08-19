<?php
// Downloads one exact Secure S3 Storage recovery set through the AWS SDK
// default credential provider chain. This script intentionally does not load
// Moodle or accept static AWS credentials.

declare(strict_types=1);

use Aws\S3\S3Client;

const DATABASE_DESTINATION = '/database-artifacts';
const CONTENT_DESTINATION = '/content-recovery';
const MAX_MANIFEST_BYTES = 65536;

/**
 * Returns one required environment variable.
 */
function required_environment(string $name): string {
    $value = getenv($name);
    if ($value === false || trim($value) === '') {
        throw new RuntimeException($name . ' is required.');
    }
    return trim($value);
}

/**
 * Creates an empty, private destination directory.
 */
function prepare_destination(string $path): string {
    if (is_link($path)) {
        throw new RuntimeException('Recovery destination must not be a symlink.');
    }
    if (!is_dir($path) && !mkdir($path, 0750, true) && !is_dir($path)) {
        throw new RuntimeException('Unable to create recovery destination.');
    }
    $canonical = realpath($path);
    if ($canonical !== $path || is_link($path) || !is_writable($path)) {
        throw new RuntimeException('Recovery destination boundary verification failed.');
    }
    $entries = array_values(array_diff(scandir($path) ?: [], ['.', '..']));
    if ($entries !== []) {
        throw new RuntimeException('Recovery destination must be empty.');
    }
    chmod($path, 0750);
    return $canonical;
}

/**
 * Reads and decodes a bounded JSON manifest from S3.
 *
 * @return array<string, mixed>
 */
function read_manifest(S3Client $client, string $bucket, string $key): array {
    $result = $client->getObject(['Bucket' => $bucket, 'Key' => $key]);
    $length = (int)($result['ContentLength'] ?? 0);
    if ($length < 2 || $length > MAX_MANIFEST_BYTES) {
        throw new RuntimeException('Remote recovery manifest size is invalid.');
    }
    $json = (string)$result['Body'];
    if (strlen($json) !== $length) {
        throw new RuntimeException('Remote recovery manifest was truncated.');
    }
    $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($decoded) || array_is_list($decoded)) {
        throw new RuntimeException('Remote recovery manifest is invalid.');
    }
    return $decoded;
}

/**
 * Downloads one object atomically and verifies its size and digests.
 */
function download_verified(
    S3Client $client,
    string $bucket,
    string $key,
    string $destination,
    int $expectedbytes,
    ?string $expectedsha256 = null,
    ?string $expectedsha1 = null,
    ?string $expectedformat = null,
): void {
    if ($expectedbytes < 0 || is_link(dirname($destination))) {
        throw new RuntimeException('Invalid recovery object destination.');
    }
    $temporary = dirname($destination) . '/.' . basename($destination) . '.partial';
    if (file_exists($temporary) || is_link($temporary) || file_exists($destination)) {
        throw new RuntimeException('Recovery object destination is not empty.');
    }

    if ($expectedformat !== null) {
        $head = $client->headObject(['Bucket' => $bucket, 'Key' => $key]);
        $metadata = array_change_key_case((array)($head['Metadata'] ?? []), CASE_LOWER);
        if (
            (int)($head['ContentLength'] ?? -1) !== $expectedbytes ||
            !isset($metadata['sha256'], $metadata['format']) ||
            !preg_match('/^[0-9a-f]{64}$/D', (string)$metadata['sha256']) ||
            !hash_equals($expectedformat, (string)$metadata['format'])
        ) {
            throw new RuntimeException('Remote content object metadata is invalid.');
        }
        $expectedsha256 = (string)$metadata['sha256'];
    }

    try {
        $client->getObject(['Bucket' => $bucket, 'Key' => $key, 'SaveAs' => $temporary]);
        clearstatcache(true, $temporary);
        $stat = lstat($temporary);
        if (
            $stat === false || is_link($temporary) || !is_file($temporary) ||
            (int)$stat['nlink'] !== 1 || (int)$stat['size'] !== $expectedbytes
        ) {
            throw new RuntimeException('Downloaded recovery object boundary or size is invalid.');
        }
        if ($expectedsha256 !== null) {
            $actualsha256 = hash_file('sha256', $temporary);
            if ($actualsha256 === false || !hash_equals($expectedsha256, $actualsha256)) {
                throw new RuntimeException('Downloaded recovery object SHA-256 is invalid.');
            }
        }
        if ($expectedsha1 !== null) {
            $actualsha1 = hash_file('sha1', $temporary);
            if ($actualsha1 === false || !hash_equals($expectedsha1, $actualsha1)) {
                throw new RuntimeException('Downloaded recovery object SHA-1 is invalid.');
            }
        }
        chmod($temporary, 0640);
        if (!rename($temporary, $destination)) {
            throw new RuntimeException('Unable to publish downloaded recovery object.');
        }
    } finally {
        if (is_file($temporary) && !is_link($temporary)) {
            unlink($temporary);
        }
    }
}

/**
 * Requires the exact manifest field set.
 *
 * @param array<string, mixed> $manifest
 * @param list<string> $expected
 */
function require_fields(array $manifest, array $expected): void {
    $keys = array_keys($manifest);
    sort($keys, SORT_STRING);
    sort($expected, SORT_STRING);
    if ($keys !== $expected) {
        throw new RuntimeException('Recovery manifest field set is invalid.');
    }
}

/**
 * Downloads and verifies every object referenced by one content inventory.
 *
 * @return array{count: int, bytes: int}
 */
function download_inventory_objects(
    S3Client $client,
    string $bucket,
    string $prefix,
    string $inventorypath,
    string $objectdirectory,
    string $recoverysetid,
    int $expectedcount,
    int $expectedbytes,
): array {
    $handle = gzopen($inventorypath, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Unable to open downloaded content inventory.');
    }

    $count = 0;
    $bytes = 0;
    $previous = '';
    try {
        $headerline = gzgets($handle, 1048577);
        $header = is_string($headerline)
            ? json_decode(trim($headerline), true, 8, JSON_THROW_ON_ERROR)
            : null;
        if (
            !is_array($header) || array_keys($header) !== ['schema', 'recoverysetid'] ||
            $header['schema'] !== 'tool_secure_s3_storage.content-inventory/v1' ||
            $header['recoverysetid'] !== $recoverysetid
        ) {
            throw new RuntimeException('Downloaded content inventory header is invalid.');
        }

        while (!gzeof($handle)) {
            $line = gzgets($handle, 1048577);
            if ($line === false) {
                if (gzeof($handle)) {
                    break;
                }
                throw new RuntimeException('Unable to read downloaded content inventory.');
            }
            if (!str_ends_with($line, "\n")) {
                throw new RuntimeException('Downloaded content inventory line is truncated.');
            }
            $entry = json_decode(trim($line), true, 8, JSON_THROW_ON_ERROR);
            if (
                !is_array($entry) || array_keys($entry) !== ['contenthash', 'filesize'] ||
                !is_string($entry['contenthash']) ||
                !preg_match('/^[0-9a-f]{40}$/D', $entry['contenthash']) ||
                !is_int($entry['filesize']) || $entry['filesize'] < 1 ||
                ($previous !== '' && strcmp($previous, $entry['contenthash']) >= 0)
            ) {
                throw new RuntimeException('Downloaded content inventory entry is invalid.');
            }

            $hash = $entry['contenthash'];
            $first = substr($hash, 0, 2);
            $second = substr($hash, 2, 2);
            $directory = $objectdirectory . '/' . $first . '/' . $second;
            if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to create content object directory.');
            }
            chmod(dirname($directory), 0750);
            chmod($directory, 0750);
            download_verified(
                $client,
                $bucket,
                $prefix . 'content/v1/objects/' . $first . '/' . $second . '/' . $hash,
                $directory . '/' . $hash,
                $entry['filesize'],
                null,
                $hash,
                'moodle-filedir-sha1'
            );
            $count++;
            $bytes += $entry['filesize'];
            $previous = $hash;
        }
    } finally {
        gzclose($handle);
    }

    if ($count !== $expectedcount || $bytes !== $expectedbytes) {
        throw new RuntimeException('Downloaded content inventory totals are invalid.');
    }
    return ['count' => $count, 'bytes' => $bytes];
}

try {
    $autoload = '/var/www/html/public/admin/tool/secure_s3_storage/vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Secure S3 Storage AWS SDK autoloader is unavailable.');
    }
    require $autoload;

    $region = strtolower(required_environment('AWS_REGION'));
    $bucket = strtolower(required_environment('S3_BUCKET'));
    $prefix = required_environment('S3_PREFIX');
    $recoverysetid = required_environment('AWS_RECOVERY_SET_ID');

    if (!preg_match('/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/D', $region)) {
        throw new RuntimeException('AWS_REGION is invalid.');
    }
    if (
        !preg_match('/^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$/D', $bucket) ||
        str_contains($bucket, '..') || filter_var($bucket, FILTER_VALIDATE_IP) !== false
    ) {
        throw new RuntimeException('S3_BUCKET is invalid.');
    }
    $prefix = rtrim($prefix, '/') . '/';
    if (
        strlen($prefix) > 181 || str_starts_with($prefix, '/') ||
        preg_match('/[\x00-\x1f\x7f\\]/', $prefix) || str_contains($prefix, '//')
    ) {
        throw new RuntimeException('S3_PREFIX is invalid.');
    }
    foreach (explode('/', rtrim($prefix, '/')) as $segment) {
        if ($segment === '' || $segment === '.' || $segment === '..') {
            throw new RuntimeException('S3_PREFIX is invalid.');
        }
    }
    if (!preg_match('/^(\d{4})(\d{2})(\d{2})T\d{6}Z-([0-9a-f]{32})$/D', $recoverysetid, $matches)) {
        throw new RuntimeException('AWS_RECOVERY_SET_ID is invalid.');
    }
    if (!checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])) {
        throw new RuntimeException('AWS_RECOVERY_SET_ID date is invalid.');
    }

    $databasedestination = prepare_destination(DATABASE_DESTINATION);
    $contentdestination = prepare_destination(CONTENT_DESTINATION);
    $objectdestination = $contentdestination . '/objects';
    if (!mkdir($objectdestination, 0750) || realpath($objectdestination) !== $objectdestination) {
        throw new RuntimeException('Unable to create content object destination.');
    }

    $client = new S3Client(['version' => 'latest', 'region' => $region]);
    $datepath = $matches[1] . '/' . $matches[2] . '/' . $matches[3] . '/';
    $artifactid = $matches[4];

    $databasebase = $prefix . 'database/v2/' . $datepath . $artifactid . '/';
    $databasemanifest = read_manifest($client, $bucket, $databasebase . 'manifest.json');
    require_fields($databasemanifest, [
        'artifactid', 'bytes', 'compression', 'createdat', 'dbtype', 'encryption',
        'format', 'moodlerelease', 'moodleversion', 'payload', 'recoverysetid',
        'schema', 'sha256', 'type',
    ]);
    $databasepayload = 'moodle-db-' . $recoverysetid . '.xml.gz';
    if (
        $databasemanifest['schema'] !== 'tool_secure_s3_storage.artifact/v2' ||
        $databasemanifest['type'] !== 'database' ||
        $databasemanifest['artifactid'] !== $artifactid ||
        $databasemanifest['recoverysetid'] !== $recoverysetid ||
        $databasemanifest['payload'] !== $databasepayload ||
        !is_int($databasemanifest['bytes']) || $databasemanifest['bytes'] < 1 ||
        !is_string($databasemanifest['sha256']) ||
        !preg_match('/^[0-9a-f]{64}$/D', $databasemanifest['sha256'])
    ) {
        throw new RuntimeException('Remote database manifest is invalid.');
    }
    download_verified(
        $client,
        $bucket,
        $databasebase . $databasepayload,
        $databasedestination . '/' . $databasepayload,
        $databasemanifest['bytes'],
        $databasemanifest['sha256']
    );
    $databasemanifestpath = $databasedestination . '/' . $databasepayload . '.manifest.json';
    file_put_contents(
        $databasemanifestpath,
        json_encode($databasemanifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
        LOCK_EX
    );
    chmod($databasemanifestpath, 0640);

    $contentbase = $prefix . 'content/v1/recovery-sets/' . $datepath . $artifactid . '/';
    $contentmanifest = read_manifest($client, $bucket, $contentbase . 'manifest.json');
    require_fields($contentmanifest, [
        'compression', 'contentbytes', 'createdat', 'databaseartifactid',
        'hashalgorithm', 'inventory', 'inventorybytes', 'inventorysha256',
        'objectcount', 'recoverysetid', 'schema', 'type',
    ]);
    $inventory = 'moodle-content-' . $recoverysetid . '.jsonl.gz';
    if (
        $contentmanifest['schema'] !== 'tool_secure_s3_storage.content-recovery/v1' ||
        $contentmanifest['type'] !== 'content' ||
        $contentmanifest['databaseartifactid'] !== $artifactid ||
        $contentmanifest['recoverysetid'] !== $recoverysetid ||
        $contentmanifest['inventory'] !== $inventory ||
        !is_int($contentmanifest['inventorybytes']) || $contentmanifest['inventorybytes'] < 1 ||
        !is_string($contentmanifest['inventorysha256']) ||
        !preg_match('/^[0-9a-f]{64}$/D', $contentmanifest['inventorysha256']) ||
        !is_int($contentmanifest['objectcount']) || $contentmanifest['objectcount'] < 0 ||
        !is_int($contentmanifest['contentbytes']) || $contentmanifest['contentbytes'] < 0
    ) {
        throw new RuntimeException('Remote content manifest is invalid.');
    }
    $inventorypath = $contentdestination . '/' . $inventory;
    download_verified(
        $client,
        $bucket,
        $contentbase . 'inventory.jsonl.gz',
        $inventorypath,
        $contentmanifest['inventorybytes'],
        $contentmanifest['inventorysha256']
    );
    $totals = download_inventory_objects(
        $client,
        $bucket,
        $prefix,
        $inventorypath,
        $objectdestination,
        $recoverysetid,
        $contentmanifest['objectcount'],
        $contentmanifest['contentbytes']
    );
    $contentmanifestpath = $contentdestination . '/' . $inventory . '.manifest.json';
    file_put_contents(
        $contentmanifestpath,
        json_encode($contentmanifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
        LOCK_EX
    );
    chmod($contentmanifestpath, 0640);

    echo json_encode([
        'awsRecoveryDownloaded' => true,
        'recoverysetid' => $recoverysetid,
        'databasepayload' => $databasepayload,
        'contentobjects' => $totals['count'],
        'contentbytes' => $totals['bytes'],
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'AWS recovery fetch failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
