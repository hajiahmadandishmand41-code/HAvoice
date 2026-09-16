<?php
/**
 * HAvoice — Migration / Seed CLI
 *
 * Usage (on host with PHP + MySQL):
 *   php tools/db-migrate.php              # schema only
 *   php tools/db-migrate.php --seed       # schema + seed real content
 *   php tools/db-migrate.php --seed --force
 *   php tools/db-migrate.php --status
 *
 * Credentials: config/config.local.php (never commit secrets).
 */

declare(strict_types=1);

$root = dirname(__DIR__);
define('HA_ROOT', $root);

require $root . '/config/config.php';
require $root . '/includes/helpers.php';
require $root . '/includes/db.php';
require $root . '/includes/repository.php';

$args = array_slice($argv, 1);
$doSeed = in_array('--seed', $args, true);
$force  = in_array('--force', $args, true);
$status = in_array('--status', $args, true);

echo "HAvoice DB migrate\n";
echo "  configured: " . (db_configured() ? 'yes' : 'NO') . "\n";

if (!db_configured()) {
    echo "ERROR: Set HA_DB_* in config/config.local.php (see config.local.php.example).\n";
    exit(1);
}

$pdo = db();
if ($pdo === null) {
    echo "ERROR: Connection or schema failed. Check host/user/pass/name and pdo_mysql.\n";
    exit(1);
}
echo "  connected: yes\n";
echo "  schema:   " . HA_DB_SCHEMA_VERSION . "\n";

$tables = [
    'ha_users', 'ha_categories', 'ha_courses', 'ha_stages', 'ha_lessons',
    'ha_exercises', 'ha_media', 'ha_books', 'ha_articles', 'ha_tips',
    'ha_research', 'ha_comments', 'ha_settings', 'ha_contact_messages',
    'ha_progress',
];
foreach ($tables as $t) {
    try {
        $c = (int) $pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
        echo "  {$t}: {$c}\n";
    } catch (Throwable $e) {
        echo "  {$t}: MISSING\n";
    }
}

if ($status) {
    exit(0);
}

if ($doSeed) {
    echo "\nSeeding real content (no fake media)…\n";
    $res = repo_seed($force);
    if (!$res['ok']) {
        echo "SEED FAIL: " . ($res['error'] ?? '?') . "\n";
        exit(1);
    }
    foreach ($res['counts'] as $k => $n) {
        echo "  seeded {$k}: {$n}\n";
    }
    echo "SEED OK\n";
}

echo "DONE\n";
exit(0);
