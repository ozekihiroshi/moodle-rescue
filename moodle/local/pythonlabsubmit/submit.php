<?php
// Server-to-server endpoint for submitting a Python Lab artifact to mod_assign.

define('NO_MOODLE_COOKIES', true);
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

function local_pythonlabsubmit_response(int $status, array $body): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    local_pythonlabsubmit_response(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$secret = trim((string)getenv('PYTHON_LAB_SUBMIT_SECRET'));
if (strlen($secret) < 32 || str_starts_with($secret, 'CHANGE_ME')) {
    local_pythonlabsubmit_response(503, ['ok' => false, 'error' => 'service_not_configured']);
}

$rawbody = file_get_contents('php://input');
if ($rawbody === false || strlen($rawbody) > 400000) {
    local_pythonlabsubmit_response(413, ['ok' => false, 'error' => 'request_too_large']);
}

$timestamp = $_SERVER['HTTP_X_PYTHON_LAB_TIMESTAMP'] ?? '';
$nonce = $_SERVER['HTTP_X_PYTHON_LAB_NONCE'] ?? '';
$signature = $_SERVER['HTTP_X_PYTHON_LAB_SIGNATURE'] ?? '';
if (!ctype_digit($timestamp) || abs(time() - (int)$timestamp) > 120 ||
        !preg_match('/^[a-f0-9]{32,64}$/', $nonce)) {
    local_pythonlabsubmit_response(401, ['ok' => false, 'error' => 'invalid_request_proof']);
}

$expected = 'sha256=' . hash_hmac('sha256', $timestamp . "\n" . $nonce . "\n" . $rawbody, $secret);
if (!hash_equals($expected, $signature)) {
    local_pythonlabsubmit_response(401, ['ok' => false, 'error' => 'invalid_signature']);
}

$DB->delete_records_select('local_pythonlabsubmit_nonce', 'expiresat < :now', ['now' => time()]);
try {
    $DB->insert_record('local_pythonlabsubmit_nonce', (object)[
        'nonce' => $nonce,
        'expiresat' => time() + 300,
    ]);
} catch (dml_write_exception $exception) {
    local_pythonlabsubmit_response(409, ['ok' => false, 'error' => 'replayed_request']);
}

$data = json_decode($rawbody);
if (!is_object($data) || json_last_error() !== JSON_ERROR_NONE) {
    local_pythonlabsubmit_response(400, ['ok' => false, 'error' => 'invalid_json']);
}

$userid = clean_param($data->userid ?? 0, PARAM_INT);
$coursekey = clean_param($data->course_shortname ?? '', PARAM_ALPHANUMEXT);
$project = clean_param($data->project ?? '', PARAM_ALPHANUMEXT);
$filename = clean_param($data->filename ?? '', PARAM_FILE);
$encoded = $data->content_base64 ?? '';
$claimedhash = clean_param($data->sha256 ?? '', PARAM_ALPHANUM);

$allowedcourses = array_filter(array_map('trim', explode(',', (string)getenv('PYTHON_LAB_SUBMIT_COURSES'))));
if (!$allowedcourses) {
    $allowedcourses = ['PYAI-INTRO', 'PYAI-INTRO-JA'];
}
if (!in_array($coursekey, $allowedcourses, true) || $project !== 'weekly-support' ||
        $filename !== 'weekly_support.py') {
    local_pythonlabsubmit_response(400, ['ok' => false, 'error' => 'unsupported_submission_target']);
}

$content = base64_decode($encoded, true);
if ($content === false || strlen($content) === 0 || strlen($content) > 262144 ||
        str_contains($content, "\0") || !hash_equals(hash('sha256', $content), $claimedhash)) {
    local_pythonlabsubmit_response(400, ['ok' => false, 'error' => 'invalid_artifact']);
}

$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0, 'suspended' => 0]);
$course = $DB->get_record('course', ['shortname' => $coursekey]);
if (!$user || !$course) {
    local_pythonlabsubmit_response(404, ['ok' => false, 'error' => 'user_or_course_not_found']);
}

$sql = "SELECT cm.*
          FROM {course_modules} cm
          JOIN {modules} m ON m.id = cm.module
         WHERE cm.course = :courseid
           AND cm.idnumber = :idnumber
           AND m.name = 'assign'";
$cmrecord = $DB->get_record_sql($sql, [
    'courseid' => $course->id,
    'idnumber' => 'pyai-project-1-weekly-support',
]);
if (!$cmrecord) {
    local_pythonlabsubmit_response(404, ['ok' => false, 'error' => 'assignment_not_configured']);
}

$cm = get_coursemodule_from_id('assign', $cmrecord->id, $course->id, false, MUST_EXIST);
$context = context_module::instance($cm->id);
if (!is_enrolled(context_course::instance($course->id), $user, '', true) ||
        !has_capability('mod/assign:submit', $context, $user)) {
    local_pythonlabsubmit_response(403, ['ok' => false, 'error' => 'not_allowed_to_submit']);
}

\core\session\manager::set_user($user);
$assignment = new assign($context, $cm, $course);
if (!$assignment->submissions_open($userid)) {
    local_pythonlabsubmit_response(409, ['ok' => false, 'error' => 'submissions_not_open']);
}

$draftitemid = file_get_unused_draft_itemid();
$filerecord = [
    'contextid' => context_user::instance($userid)->id,
    'component' => 'user',
    'filearea' => 'draft',
    'itemid' => $draftitemid,
    'filepath' => '/',
    'filename' => $filename,
];
get_file_storage()->create_file_from_string($filerecord, $content);

$notices = [];
try {
    $saved = $assignment->save_submission((object)[
        'userid' => $userid,
        'files_filemanager' => $draftitemid,
    ], $notices);
} catch (Throwable $exception) {
    error_log('local_pythonlabsubmit: ' . get_class($exception) . ': ' . $exception->getMessage());
    local_pythonlabsubmit_response(409, [
        'ok' => false,
        'error' => 'submission_rejected',
        'message' => 'Moodle rejected the submission.',
    ]);
}
if (!$saved) {
    local_pythonlabsubmit_response(409, [
        'ok' => false,
        'error' => 'submission_not_saved',
        'notices' => array_values(array_map('strip_tags', $notices)),
    ]);
}

$submission = $assignment->get_user_submission($userid, false);
local_pythonlabsubmit_response(200, [
    'ok' => true,
    'course_shortname' => $course->shortname,
    'assignment_cmid' => (int)$cm->id,
    'submission_id' => (int)$submission->id,
    'status' => $submission->status,
    'filename' => $filename,
    'sha256' => hash('sha256', $content),
    'submitted_at' => time(),
]);
