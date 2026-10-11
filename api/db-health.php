<?php
/**
 * HAvoice — production DB health probe.
 *
 * This endpoint intentionally returns only non-sensitive health metadata:
 * no hostname, username, schema contents, or credentials.
 */

declare(strict_types=1);

define('HA_ROOT', dirname(__DIR__));

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
header_remove('X-Powered-By');
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
    $seeded = false;
    $tableCount = 0;
    try {
        $st = $pdo->query("SELECT meta_value FROM ha_schema_meta WHERE meta_key = 'version' LIMIT 1");
        $schema = $st ? (string) $st->fetchColumn() : null;

        $stSeed = $pdo->query("SELECT meta_value FROM ha_schema_meta WHERE meta_key = 'seeded_at' LIMIT 1");
        $seeded = $stSeed && (string) $stSeed->fetchColumn() !== '';

        $tableCount = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND LEFT(table_name, 3) = 'ha_'"
        )->fetchColumn();
    } catch (Throwable $ignored) {
        /* Keep health response non-sensitive; missing metadata yields a failed check below. */
    }

    $ok = ($ping === 1)
        && ($schema === (defined('HA_DB_SCHEMA_VERSION') ? (string) HA_DB_SCHEMA_VERSION : $schema))
        && ($tableCount >= 27);

    http_response_code($ok ? 200 : 503);
    echo json_encode([
        'ok' => $ok,
        'database_configured' => true,
        'database_connected' => true,
        'pdo_mysql_loaded' => true,
        'schema_version' => $schema,
        'schema_version_expected' => defined('HA_DB_SCHEMA_VERSION') ? HA_DB_SCHEMA_VERSION : null,
        'application_tables_present' => $tableCount,
        'content_seeded' => $seeded,
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
