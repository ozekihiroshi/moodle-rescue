<?php
// Lab registration adapter. Does not create courses, users or activities.
define('CLI_SCRIPT', true);
if (!function_exists('posix_geteuid') || posix_geteuid() !== 33) {
    fwrite(STDERR, "Run as www-data.\n"); exit(1);
}
require is_file('/var/www/html/public/config.php') ? '/var/www/html/public/config.php' : '/var/www/html/config.php';
require_once $CFG->dirroot . '/mod/lti/locallib.php';
\core\session\manager::set_user(get_admin());
set_exception_handler(static function(Throwable $error): void {
    fwrite(STDERR, 'Registration failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
});
$kind = getenv('LAB_KIND');
$name = getenv('LAB_TOOL_NAME');
$base = rtrim(getenv('LAB_URL'), '/');
$parts = parse_url($base);
if (!in_array($kind, ['java', 'python'], true) || !$name || !$parts || empty($parts['host']) ||
        !in_array($parts['scheme'] ?? '', ['http', 'https'], true) ||
        isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) ||
        !empty($parts['path']) || (($parts['scheme'] ?? '') === 'http' && !in_array($parts['host'], ['localhost','127.0.0.1'], true))) {
    throw new RuntimeException('Specify java/python, a tool name and an HTTPS origin (HTTP loopback allowed).');
}
$transaction = $DB->start_delegated_transaction();
$types = $DB->get_records('lti_types', ['name'=>$name, 'course'=>get_site()->id]);
if (count($types) > 1) { throw new RuntimeException('Ambiguous tool name; resolve duplicates before linking.'); }
$type = $types ? reset($types) : null;
$login = $base . '/hub/lti13/oauth_login';
$callback = $base . '/hub/lti13/oauth_callback';
if (!$type) {
    if (getenv('LAB_APPLY') !== 'yes') { throw new RuntimeException('Tool not found. Review the URL/name and rerun with --apply to register it.'); }
    $type = (object)['name'=>$name, 'course'=>get_site()->id, 'state'=>LTI_TOOL_STATE_CONFIGURED,
        'coursevisible'=>LTI_COURSEVISIBLE_ACTIVITYCHOOSER, 'ltiversion'=>LTI_VERSION_1P3];
    $id = lti_add_type($type, (object)[
        'lti_typename'=>$name, 'lti_toolurl'=>$base.'/hub/', 'lti_description'=>ucfirst($kind).' Lab',
        'lti_ltiversion'=>LTI_VERSION_1P3, 'lti_keytype'=>LTI_JWK_KEYSET, 'lti_publickeyset'=>'',
        'lti_initiatelogin'=>$login, 'lti_redirectionuris'=>$callback,
        'lti_customparameters'=>'', 'lti_coursevisible'=>LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
        'lti_launchcontainer'=>LTI_LAUNCH_CONTAINER_WINDOW, 'lti_contentitem'=>0,
        'lti_sendname'=>LTI_SETTING_NEVER, 'lti_sendemailaddr'=>LTI_SETTING_NEVER,
        'lti_acceptgrades'=>LTI_SETTING_NEVER, 'lti_forcessl'=>$parts['scheme']==='https' ? 1 : 0,
    ]);
    $type = $DB->get_record('lti_types', ['id'=>$id], '*', MUST_EXIST);
}
$config = lti_get_type_config($type->id);
if ($type->ltiversion !== LTI_VERSION_1P3 || ($config['initiatelogin'] ?? '') !== $login ||
        trim($config['redirectionuris'] ?? '') !== $callback) {
    throw new RuntimeException('Existing registration differs. Refusing to overwrite it. Use a distinct tool name or inspect Moodle.');
}
$transaction->allow_commit();
echo json_encode([
    'schema_version'=>1, 'kind'=>$kind, 'tool_type_id'=>(int)$type->id,
    'platform'=>['issuer'=>$CFG->wwwroot, 'authorize_url'=>$CFG->wwwroot.'/mod/lti/auth.php',
        'jwks_url'=>$CFG->wwwroot.'/mod/lti/certs.php', 'client_id'=>$type->clientid, 'deployment_id'=>(string)$type->id],
    'tool'=>['base_url'=>$base, 'login_url'=>$login, 'callback_url'=>$callback,
        'target_url'=>$base.'/hub/user-redirect/'.($kind==='java' ? 'ide/' : 'lab/')],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
