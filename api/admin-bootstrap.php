<?php
/**
 * TEMPORARY one-time HAvoice administrator bootstrap.
 * Remove this endpoint and its Vercel environment variables immediately after use.
 */
declare(strict_types=1);

define('HA_ROOT', dirname(__DIR__));
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
header_remove('X-Powered-By');
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function ha_bootstrap_raw_env(string $key): string
{
    $value = function_exists('getenv') ? getenv($key) : false;
    if ($value !== false && $value !== '') {
        return (string) $value;
    }
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? '';
    return is_string($value) ? $value : '';
}

function ha_bootstrap_reply(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$expectedToken = ha_bootstrap_raw_env('HA_ADMIN_BOOTSTRAP_TOKEN');
$providedToken = isset($_GET['token']) && is_string($_GET['token']) ? $_GET['token'] : '';
if ($expectedToken === '' || strlen($expectedToken) < 40 || $providedToken === ''
    || !hash_equals($expectedToken, $providedToken)) {
    ha_bootstrap_reply(404, ['ok' => false, 'error' => 'not_found']);
}

require HA_ROOT . '/config/config.php';
require HA_ROOT . '/includes/db.php';

$pdo = db();
if (!$pdo instanceof PDO) {
    ha_bootstrap_reply(503, [
        'ok' => false,
        'error' => 'database_unavailable',
        'pdo_mysql_loaded' => extension_loaded('pdo_mysql'),
    ]);
}

if (db_setting_get('admin_bootstrap_endpoint_used', '') === 'true') {
    ha_bootstrap_reply(410, ['ok' => false, 'error' => 'already_used']);
}

$email = strtolower(trim(ha_env('HA_ADMIN_BOOTSTRAP_EMAIL')));
$name = trim(ha_env('HA_ADMIN_BOOTSTRAP_NAME', 'مدیر سایت'));
$password = ha_env('HA_ADMIN_BOOTSTRAP_PASSWORD');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === ''
    || strlen($password) < 8 || strlen($password) > 72) {
    ha_bootstrap_reply(503, ['ok' => false, 'error' => 'bootstrap_configuration_invalid']);
}

$existing = db_user_find_email($email);
$action = 'created';
$userId = '';

if ($existing !== null) {
    $userId = (string) ($existing['id'] ?? '');
    if ($userId === '' || !db_user_update_password($userId, $password)
        || !db_user_set_role($userId, 'admin')) {
        ha_bootstrap_reply(500, ['ok' => false, 'error' => 'account_update_failed']);
    }
    $action = 'updated';
} else {
    $created = db_user_create($name, $email, $password, 'admin');
    if (empty($created['ok']) || empty($created['user']['id'])) {
        ha_bootstrap_reply(500, ['ok' => false, 'error' => 'account_create_failed']);
    }
    $userId = (string) $created['user']['id'];
}

$lock = [
    'done' => true,
    'admin_id' => $userId,
    'admin_at' => date('c'),
    'note' => 'Administrator provisioned through the temporary one-time production bootstrap.',
];
$settingsOk = db_settings_set([
    'auth_bootstrap' => json_encode($lock, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'admin_bootstrap_endpoint_used' => 'true',
]);
if (!$settingsOk) {
    ha_bootstrap_reply(500, ['ok' => false, 'error' => 'account_saved_but_lock_failed']);
}

ha_bootstrap_reply(200, [
    'ok' => true,
    'action' => $action,
    'email' => $email,
    'role' => 'admin',
    'password_set' => true,
    'one_time_lock' => true,
]);
