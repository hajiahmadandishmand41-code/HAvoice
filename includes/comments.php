<?php
/**
 * HAvoice — لایه‌ی داده‌ی «نظرات عمومی» (MySQL/MariaDB)
 *
 * معماری — سازگار با هاستِ اشتراکیِ InfinityFree:
 *   • PHP + MySQL/MariaDB از طریق mysqli (افزونه‌ی استانداردِ هاستِ اشتراکی؛
 *     بدون Composer، بدون Node، بدون Redis/کرون‌جاب).
 *   • تمامِ کوئری‌ها Prepared Statementاند (bind_param) — هیچ ورودیِ
 *     کاربری در SQL درج نمی‌شود.
 *   • جدول در نخستین اتصالِ موفق با CREATE TABLE IF NOT EXISTS ساخته می‌شود
 *     (روی InfinityFree دسترسیِ shell نیست؛ همین «migration» خودکار است).
 *     نسخه‌ی قابلِ درون‌ریزی در phpMyAdmin هم در sql/001-create-comments.sql است.
 *   • اگر دیتابیس پیکربندی/در دسترس نباشد، سایتِ اصلی سالم می‌ماند:
 *     همه‌ی توابع به‌آرامی «خالی» برمی‌گردند و صفحه‌ی نظرات پیامِ مناسب می‌دهد.
 *
 * نمایشِ امن: تابعِ e() (htmlspecialchars با ENT_QUOTES) هنگامِ رندر؛
 * ایمیلِ نویسنده هرگز نمایش داده نمی‌شود — فقط برای تماسِ مدیر است.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  اتصال                                                             */
/* ------------------------------------------------------------------ */

/**
 * آیا دیتابیس پیکربندی شده است؟
 * (ثابت‌ها را می‌توان پیش از config در بوت‌استرپِ تست تعریف کرد.)
 */
function comments_configured(): bool
{
    return defined('HA_DB_HOST') && HA_DB_HOST !== ''
        && defined('HA_DB_NAME') && HA_DB_NAME !== ''
        && defined('HA_DB_USER') && HA_DB_USER !== '';
}

/**
 * اتصالِ تنبل (یک‌بار در هر درخواست).
 *
 * @return mysqli|null در هر خرابی null — هرگز استثنا به بیرون نشت نمی‌کند.
 */
function comments_db(): ?mysqli
{
    static $db = null;
    static $state = null; // null=تلاش نشده | true=متصل | false=شکست

    if ($state !== null) {
        return $state ? $db : null;
    }
    $state = false;

    if (!comments_configured() || !function_exists('mysqli_connect')) {
        return null;
    }

    try {
        mysqli_report(MYSQLI_REPORT_OFF); // خطاها به‌صورت نتیجه برگردند، نه استثنا
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
        if (!comments_ensure_table($db)) {
            @mysqli_close($db);
            $db = null;
            return null;
        }
        $state = true;
        return $db;
    } catch (Throwable $e) {
        if (isset($db) && $db instanceof mysqli) {
            @mysqli_close($db);
        }
        $db = null;
        return null;
    }
}

/* ------------------------------------------------------------------ */
/*  جدول                                                              */
/* ------------------------------------------------------------------ */

/** DDL — MySQL 5.6+/MariaDB سازگار (InfinityFree). */
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
    PRIMARY KEY (id),
    KEY idx_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

/** ساختِ جدول در صورتِ نبود (یک‌بار در هر درخواست، ارزان است). */
function comments_ensure_table(mysqli $db): bool
{
    if (@mysqli_query($db, comments_table_sql()) === false) {
        return false;
    }
    return true;
}

/* ------------------------------------------------------------------ */
/*  نوشتن                                                             */
/* ------------------------------------------------------------------ */

/**
 * ثبتِ نظرِ جدید (با وضعیتِ pending — نمایش پس از تأیید مدیر).
 *
 * @return array{ok:bool, error?:string, id?:int}
 */
function comment_add(string $name, string $email, string $body, string $ip): array
{
    $db = comments_db();
    if ($db === null) {
        return ['ok' => false, 'error' => 'db'];
    }

    $sql = "INSERT INTO ha_comments (name, email, body, status, ip, created_at)
            VALUES (?, ?, ?, 'pending', ?, ?)";
    $st = @mysqli_prepare($db, $sql);
    if ($st === false) {
        return ['ok' => false, 'error' => 'db'];
    }

    $created = date('Y-m-d H:i:s');
    mysqli_stmt_bind_param($st, 'sssss', $name, $email, $body, $ip, $created);
    $ok = @mysqli_stmt_execute($st);
    $id = (int) mysqli_insert_id($db);
    @mysqli_stmt_close($st);

    if (!$ok) {
        return ['ok' => false, 'error' => 'db'];
    }
    return ['ok' => true, 'id' => $id];
}

/* ------------------------------------------------------------------ */
/*  خواندن                                                            */
/* ------------------------------------------------------------------ */

/**
 * نظراتِ تأییدشده برای نمایشِ عمومی — تازه‌ترین اول.
 *
 * @return array<int, array{id:string,name:string,body:string,created_at:string}>
 */
function comments_approved(int $limit = 10, int $offset = 0): array
{
    $db = comments_db();
    if ($db === null) {
        return [];
    }
    $limit  = max(1, min(50, $limit));
    $offset = max(0, $offset);

    $sql = "SELECT id, name, body, created_at
            FROM ha_comments
            WHERE status = 'approved'
            ORDER BY created_at DESC, id DESC
            LIMIT ? OFFSET ?";
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

/** شمارِ نظراتِ تأییدشده (برای متنِ «نظرِ دیگران» و صفحه‌بندی). */
function comments_count_approved(): int
{
    $db = comments_db();
    if ($db === null) {
        return 0;
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

/* ------------------------------------------------------------------ */
/*  بخشِ مدیریت                                                       */
/* ------------------------------------------------------------------ */

/** شمارِ نظرات به تفکیکِ وضعیت — برای داشبورد و برچسبِ سایدبار. */
function comments_admin_counts(): array
{
    $db = comments_db();
    if ($db === null) {
        return ['pending' => 0, 'approved' => 0, 'all' => 0];
    }
    $st = @mysqli_prepare($db, "SELECT status, COUNT(*) AS c FROM ha_comments GROUP BY status");
    if ($st === false) {
        return ['pending' => 0, 'approved' => 0, 'all' => 0];
    }
    if (!@mysqli_stmt_execute($st)) {
        @mysqli_stmt_close($st);
        return ['pending' => 0, 'approved' => 0, 'all' => 0];
    }
    $out = ['pending' => 0, 'approved' => 0, 'all' => 0];
    $result = mysqli_stmt_get_result($st);
    if ($result !== false) {
        while (($row = mysqli_fetch_assoc($result)) !== null) {
            $status = (string) ($row['status'] ?? '');
            if (isset($out[$status])) {
                $out[$status] = (int) ($row['c'] ?? 0);
            }
        }
    }
    @mysqli_stmt_close($st);
    $out['all'] = $out['pending'] + $out['approved'];
    return $out;
}

/**
 * فهرستِ نظرات برای پنل (هر دو وضعیت).
 *
 * @param string $status '' | 'pending' | 'approved'
 */
function comments_admin_list(string $status = '', int $limit = 100): array
{
    $db = comments_db();
    if ($db === null) {
        return [];
    }
    $limit = max(1, min(300, $limit));

    $sql = "SELECT id, name, email, body, status, ip, created_at
            FROM ha_comments";
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
    if ($params === []) {
        mysqli_stmt_bind_param($st, 'i', $limit);
    } else {
        mysqli_stmt_bind_param($st, $types, ...$params);
    }
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

/** تأیید/پنهان‌کردن نظر. */
function comment_set_status(int $id, string $status): bool
{
    if ($status !== 'pending' && $status !== 'approved') {
        return false;
    }
    $db = comments_db();
    if ($db === null) {
        return false;
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

/** حذفِ دائمیِ نظر. */
function comment_delete(int $id): bool
{
    $db = comments_db();
    if ($db === null) {
        return false;
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

/** یک نظر با شناسه (برای تیترهای پیامِ پنل). */
function comment_find(int $id): ?array
{
    $db = comments_db();
    if ($db === null) {
        return null;
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
