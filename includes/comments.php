<?php
/**
 * HAvoice — نظرات عمومی (MySQL)
 *
 * Production: PDO مشترک (includes/db.php)
 * Fallback: mysqli اگر PDO در دسترس نباشد
 * بدون DB: توابع خالی برمی‌گردند؛ سایت اصلی سالم می‌ماند.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

function comments_configured(): bool
{
    return defined('HA_DB_HOST') && HA_DB_HOST !== ''
        && defined('HA_DB_NAME') && HA_DB_NAME !== ''
        && defined('HA_DB_USER') && HA_DB_USER !== '';
}

/**
 * @return PDO|mysqli|null
 */
function comments_db()
{
    static $db = null;
    static $state = null;

    if ($state !== null) {
        return $state ? $db : null;
    }
    $state = false;

    if (!comments_configured()) {
        return null;
    }

    if (function_exists('db')) {
        $pdo = db();
        if ($pdo instanceof PDO) {
            $db = $pdo;
            $state = true;
            return $db;
        }
    }

    if (!function_exists('mysqli_connect')) {
        return null;
    }

    try {
        mysqli_report(MYSQLI_REPORT_OFF);
        $db = @mysqli_connect(
            (string) HA_DB_HOST,
            (string) HA_DB_USER,
            (string) HA_DB_PASS,
            (string) HA_DB_NAME,
            (int) (defined('HA_DB_PORT') ? HA_DB_PORT : 3306)
        );
        if (!$db) {
            return null;
        }
        mysqli_set_charset($db, 'utf8mb4');
        if (@mysqli_query($db, comments_table_sql()) === false) {
            @mysqli_close($db);
            $db = null;
            return null;
        }
        $state = true;
        return $db;
    } catch (Throwable $e) {
        $db = null;
        return null;
    }
}

function comments_table_sql(): string
{
    return "CREATE TABLE IF NOT EXISTS ha_comments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(60) NOT NULL,
    email VARCHAR(190) NOT NULL DEFAULT '',
    body TEXT NOT NULL,
    status ENUM('pending','approved') NOT NULL DEFAULT 'pending',
    ip VARCHAR(45) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

/** @return array{ok:bool, error?:string, id?:int} */
function comment_add(string $name, string $email, string $body, string $ip): array
{
    $db = comments_db();
    if ($db === null) {
        return ['ok' => false, 'error' => 'db'];
    }
    $created = date('Y-m-d H:i:s');
    if ($db instanceof PDO) {
        try {
            $st = $db->prepare("INSERT INTO ha_comments (name, email, body, status, ip, created_at) VALUES (?,?,?,'pending',?,?)");
            $ok = $st->execute([$name, $email, $body, $ip, $created]);
            return $ok ? ['ok' => true, 'id' => (int) $db->lastInsertId()] : ['ok' => false, 'error' => 'db'];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'db'];
        }
    }
    $st = @mysqli_prepare($db, "INSERT INTO ha_comments (name, email, body, status, ip, created_at) VALUES (?,?,?,'pending',?,?)");
    if ($st === false) {
        return ['ok' => false, 'error' => 'db'];
    }
    mysqli_stmt_bind_param($st, 'sssss', $name, $email, $body, $ip, $created);
    $ok = @mysqli_stmt_execute($st);
    $id = (int) mysqli_insert_id($db);
    @mysqli_stmt_close($st);
    return $ok ? ['ok' => true, 'id' => $id] : ['ok' => false, 'error' => 'db'];
}

function comments_approved(int $limit = 10, int $offset = 0): array
{
    $db = comments_db();
    if ($db === null) {
        return [];
    }
    $limit = max(1, min(50, $limit));
    $offset = max(0, $offset);
    $sql = "SELECT id, name, body, created_at FROM ha_comments WHERE status = 'approved' ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?";
    if ($db instanceof PDO) {
        try {
            $st = $db->prepare($sql);
            $st->bindValue(1, $limit, PDO::PARAM_INT);
            $st->bindValue(2, $offset, PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
    $st = @mysqli_prepare($db, $sql);
    if ($st === false) {
        return [];
    }
    mysqli_stmt_bind_param($st, 'ii', $limit, $offset);
    if (!@mysqli_stmt_execute($st)) {
        @mysqli_stmt_close($st);
        return [];
    }
    $rows = [];
    $result = mysqli_stmt_get_result($st);
    if ($result !== false) {
        while (($row = mysqli_fetch_assoc($result)) !== null) {
            $rows[] = $row;
        }
    }
    @mysqli_stmt_close($st);
    return $rows;
}

function comments_count_approved(): int
{
    $db = comments_db();
    if ($db === null) {
        return 0;
    }
    if ($db instanceof PDO) {
        try {
            return (int) $db->query("SELECT COUNT(*) FROM ha_comments WHERE status = 'approved'")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
    $st = @mysqli_prepare($db, "SELECT COUNT(*) FROM ha_comments WHERE status = 'approved'");
    if ($st === false) {
        return 0;
    }
    if (!@mysqli_stmt_execute($st)) {
        @mysqli_stmt_close($st);
        return 0;
    }
    mysqli_stmt_bind_result($st, $count);
    $ok = mysqli_stmt_fetch($st);
    @mysqli_stmt_close($st);
    return $ok ? (int) $count : 0;
}

function comments_admin_counts(): array
{
    $db = comments_db();
    $out = ['pending' => 0, 'approved' => 0, 'all' => 0];
    if ($db === null) {
        return $out;
    }
    if ($db instanceof PDO) {
        try {
            $rows = $db->query("SELECT status, COUNT(*) AS c FROM ha_comments GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $s = (string) ($row['status'] ?? '');
                if (isset($out[$s])) {
                    $out[$s] = (int) ($row['c'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            return $out;
        }
        $out['all'] = $out['pending'] + $out['approved'];
        return $out;
    }
    $st = @mysqli_prepare($db, "SELECT status, COUNT(*) AS c FROM ha_comments GROUP BY status");
    if ($st === false || !@mysqli_stmt_execute($st)) {
        return $out;
    }
    $result = mysqli_stmt_get_result($st);
    if ($result !== false) {
        while (($row = mysqli_fetch_assoc($result)) !== null) {
            $s = (string) ($row['status'] ?? '');
            if (isset($out[$s])) {
                $out[$s] = (int) ($row['c'] ?? 0);
            }
        }
    }
    @mysqli_stmt_close($st);
    $out['all'] = $out['pending'] + $out['approved'];
    return $out;
}

function comments_admin_list(string $status = '', int $limit = 100): array
{
    $db = comments_db();
    if ($db === null) {
        return [];
    }
    $limit = max(1, min(300, $limit));
    if ($db instanceof PDO) {
        try {
            if ($status === 'pending' || $status === 'approved') {
                $st = $db->prepare("SELECT id, name, email, body, status, ip, created_at FROM ha_comments WHERE status = ? ORDER BY created_at DESC, id DESC LIMIT ?");
                $st->bindValue(1, $status);
                $st->bindValue(2, $limit, PDO::PARAM_INT);
            } else {
                $st = $db->prepare("SELECT id, name, email, body, status, ip, created_at FROM ha_comments ORDER BY created_at DESC, id DESC LIMIT ?");
                $st->bindValue(1, $limit, PDO::PARAM_INT);
            }
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
    $sql = "SELECT id, name, email, body, status, ip, created_at FROM ha_comments";
    $types = '';
    $params = [];
    if ($status === 'pending' || $status === 'approved') {
        $sql .= " WHERE status = ?";
        $types = 's';
        $params[] = $status;
    }
    $sql .= " ORDER BY created_at DESC, id DESC LIMIT ?";
    $types .= 'i';
    $params[] = $limit;
    $st = @mysqli_prepare($db, $sql);
    if ($st === false) {
        return [];
    }
    mysqli_stmt_bind_param($st, $types, ...$params);
    if (!@mysqli_stmt_execute($st)) {
        @mysqli_stmt_close($st);
        return [];
    }
    $rows = [];
    $result = mysqli_stmt_get_result($st);
    if ($result !== false) {
        while (($row = mysqli_fetch_assoc($result)) !== null) {
            $rows[] = $row;
        }
    }
    @mysqli_stmt_close($st);
    return $rows;
}

function comment_set_status(int $id, string $status): bool
{
    if ($status !== 'pending' && $status !== 'approved') {
        return false;
    }
    $db = comments_db();
    if ($db === null) {
        return false;
    }
    if ($db instanceof PDO) {
        try {
            $st = $db->prepare("UPDATE ha_comments SET status = ?, updated_at = ? WHERE id = ?");
            return $st->execute([$status, date('Y-m-d H:i:s'), $id]);
        } catch (Throwable $e) {
            return false;
        }
    }
    $st = @mysqli_prepare($db, "UPDATE ha_comments SET status = ? WHERE id = ?");
    if ($st === false) {
        return false;
    }
    mysqli_stmt_bind_param($st, 'si', $status, $id);
    $ok = @mysqli_stmt_execute($st);
    @mysqli_stmt_close($st);
    return $ok;
}

function comment_delete(int $id): bool
{
    $db = comments_db();
    if ($db === null) {
        return false;
    }
    if ($db instanceof PDO) {
        try {
            $st = $db->prepare("DELETE FROM ha_comments WHERE id = ?");
            return $st->execute([$id]);
        } catch (Throwable $e) {
            return false;
        }
    }
    $st = @mysqli_prepare($db, "DELETE FROM ha_comments WHERE id = ?");
    if ($st === false) {
        return false;
    }
    mysqli_stmt_bind_param($st, 'i', $id);
    $ok = @mysqli_stmt_execute($st);
    @mysqli_stmt_close($st);
    return $ok;
}

function comment_find(int $id): ?array
{
    $db = comments_db();
    if ($db === null) {
        return null;
    }
    if ($db instanceof PDO) {
        try {
            $st = $db->prepare("SELECT id, name, email, body, status, created_at FROM ha_comments WHERE id = ?");
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
    $st = @mysqli_prepare($db, "SELECT id, name, email, body, status, created_at FROM ha_comments WHERE id = ?");
    if ($st === false) {
        return null;
    }
    mysqli_stmt_bind_param($st, 'i', $id);
    if (!@mysqli_stmt_execute($st)) {
        @mysqli_stmt_close($st);
        return null;
    }
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st)) ?: null;
    @mysqli_stmt_close($st);
    return is_array($row) ? $row : null;
}
