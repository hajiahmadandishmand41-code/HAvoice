<?php
/**
 * HAvoice — production DB health probe.
 *
 * This endpoint intentionally returns only non-sensitive health metadata:
 * no hostname, username, schema contents, or credentials.
 */

declare(strict_types=1);

define('HA_ROOT', dirname(__DIR__));

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

try {
    require HA_ROOT . '/config/config.php';
    require HA_ROOT . '/includes/db.php';

    $configured = function_exists('db_configured') && db_configured();
    $pdo = $configured && function_exists('db') ? db() : null;

    if (!$pdo instanceof PDO) {
        http_response_code(503);
        echo json_encode([
            'ok' => false,
            'database_configured' => $configured,
            'database_connected' => false,
            'pdo_mysql_loaded' => extension_loaded('pdo_mysql'),
            'schema_version_expected' => defined('HA_DB_SCHEMA_VERSION') ? HA_DB_SCHEMA_VERSION : null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $ping = (int) $pdo->query('SELECT 1')->fetchColumn();
    $schema = null;
    try {
        $st = $pdo->query("SELECT meta_value FROM ha_schema_meta WHERE meta_key = 'version' LIMIT 1");
        $schema = $st ? (string) $st->fetchColumn() : null;
    } catch (Throwable $ignored) {
        $schema = null;
    }

    $ok = ($ping === 1) && ($schema === (defined('HA_DB_SCHEMA_VERSION') ? (string) HA_DB_SCHEMA_VERSION : $schema));

    http_response_code($ok ? 200 : 503);
    echo json_encode([
        'ok' => $ok,
        'database_configured' => true,
        'database_connected' => true,
        'pdo_mysql_loaded' => true,
        'schema_version' => $schema,
        'schema_version_expected' => defined('HA_DB_SCHEMA_VERSION') ? HA_DB_SCHEMA_VERSION : null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'database_configured' => false,
        'database_connected' => false,
        'pdo_mysql_loaded' => extension_loaded('pdo_mysql'),
        'schema_version_expected' => defined('HA_DB_SCHEMA_VERSION') ? HA_DB_SCHEMA_VERSION : null,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
