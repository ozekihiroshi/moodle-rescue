<?php
// Runtime-only Moodle configuration for the moodle-rescue containers.

unset($CFG);
global $CFG;
$CFG = new stdClass();

$required = static function(string $name): string {
    $value = getenv($name);
    if ($value === false || $value === '') {
        throw new RuntimeException('Required container environment is missing: ' . $name);
    }

    return $value;
};

$enabled = static function(string $name): bool {
    $value = getenv($name);
    if ($value === false) {
        return false;
    }

    return filter_var($value, FILTER_VALIDATE_BOOL);
};

$CFG->dbtype = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost = $required('MOODLE_DB_HOST');
$CFG->dbname = $required('MOODLE_DB_NAME');
$CFG->dbuser = $required('MOODLE_DB_USER');
$CFG->dbpass = $required('MOODLE_DB_PASSWORD');
$CFG->prefix = 'mdl_';
$CFG->dboptions = [
    'dbpersist' => false,
    'dbsocket' => false,
    'dbport' => '',
    'dbhandlesoptions' => false,
    'dbcollation' => 'utf8mb4_unicode_ci',
];

$CFG->wwwroot = $required('MOODLE_WWWROOT');
$CFG->dataroot = '/var/moodledata';
$CFG->admin = 'admin';
$CFG->directorypermissions = 02770;
$CFG->reverseproxy = $enabled('MOODLE_REVERSE_PROXY');
$CFG->sslproxy = $enabled('MOODLE_SSL_PROXY');
$CFG->cookiehttponly = true;
$CFG->preventexecpath = true;

// The SDK is built into the image; credentials remain runtime-only.
$CFG->tool_secure_s3_storage_awssdkautoload = '/opt/moodle-aws-sdk/vendor/autoload.php';

// Development-only S3 endpoint override. The plugin must not expose this as a
// Moodle setting. Production leaves this environment variable unset.
$endpoint = getenv('TOOL_SECURE_S3_STORAGE_S3_ENDPOINT');
if ($endpoint !== false && $endpoint !== '') {
    $CFG->tool_secure_s3_storage_s3endpoint = $endpoint;
    $CFG->tool_secure_s3_storage_pathstyle = true;
}

require_once(__DIR__ . '/lib/setup.php');
