<?php
/**
 * HAvoice — لایه PDO (MySQL/MariaDB)
 *
 * مسیر دادهٔ پویا:
 *   Admin → Validation → Handler → PDO → MySQL → Response → Frontend
 *
 * اگر DB پیکربندی/در دسترس نباشد:
 *   • محتوا از data/*.php + storage/admin/*.json (fallback سازگار)
 *   • سایت اصلی سالم می‌ماند
 *
 * بدون Composer. فقط PDO + pdo_mysql.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/** نسخهٔ schema که ensure_schema می‌سازد. */
define('HA_DB_SCHEMA_VERSION', '6');

/* ------------------------------------------------------------------ */
/*  پیکربندی و اتصال                                                  */
/* ------------------------------------------------------------------ */

function db_configured(): bool
{
    return defined('HA_DB_HOST') && HA_DB_HOST !== ''
        && defined('HA_DB_NAME') && HA_DB_NAME !== ''
        && defined('HA_DB_USER') && HA_DB_USER !== '';
}

/**
 * اتصال تنبل PDO. null اگر پیکربندی/افزونه/اتصال نباشد.
 */
function db(): ?PDO
{
    static $pdo = null;
    static $state = null; // null | true | false

    if ($state !== null) {
        return $state ? $pdo : null;
    }
    $state = false;

    if (!db_configured() || !class_exists('PDO') || !extension_loaded('pdo_mysql')) {
        return null;
    }

    $host = (string) HA_DB_HOST;
    $port = (int) (defined('HA_DB_PORT') ? HA_DB_PORT : 3306);
    $name = (string) HA_DB_NAME;
    $user = (string) HA_DB_USER;
    $pass = (string) (defined('HA_DB_PASS') ? HA_DB_PASS : '');

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
        if (!db_ensure_schema($pdo)) {
            $pdo = null;
            return null;
        }
        $state = true;
        return $pdo;
    } catch (Throwable $e) {
        $pdo = null;
        error_log('HAvoice DB: connection failed');
        if (defined('HA_DEBUG') && HA_DEBUG) {
            error_log('HAvoice DB: ' . $e->getMessage());
        }
        return null;
    }
}

/** آیا لایه DB فعال و آماده است؟ */
function db_ready(): bool
{
    return db() !== null;
}

/* ------------------------------------------------------------------ */
/*  Schema                                                            */
/* ------------------------------------------------------------------ */

function db_ensure_schema(PDO $pdo): bool
{
    static $done = false;
    if ($done) {
        return true;
    }

    try {
        $ver = null;
        try {
            $st = $pdo->query("SELECT meta_value FROM ha_schema_meta WHERE meta_key = 'version' LIMIT 1");
            $row = $st ? $st->fetch() : false;
            $ver = is_array($row) ? (string) ($row['meta_value'] ?? '') : null;
        } catch (Throwable $e) {
            $ver = null;
        }

        if ($ver === HA_DB_SCHEMA_VERSION) {
            $done = true;
            return true;
        }

        $sqlFile = HA_ROOT . '/sql/002-schema.sql';
        if (!is_file($sqlFile)) {
            return false;
        }
        $sql = (string) file_get_contents($sqlFile);
        // حذف کامنت‌های -- خطی برای اجرای ساده
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $parts = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($parts as $stmt) {
            if ($stmt === '' || strtoupper($stmt) === 'SET NAMES UTF8MB4') {
                if (stripos($stmt, 'SET NAMES') === 0) {
                    $pdo->exec($stmt);
                }
                continue;
            }
            if (stripos($stmt, 'SET FOREIGN_KEY') === 0) {
                $pdo->exec($stmt);
                continue;
            }
            $pdo->exec($stmt);
        }

        db_schema_upgrade($pdo);

        $pdo->prepare("INSERT INTO ha_schema_meta (meta_key, meta_value) VALUES ('version', ?)
                       ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)")
            ->execute([HA_DB_SCHEMA_VERSION]);

        $done = true;
        return true;
    } catch (Throwable $e) {
        error_log('HAvoice schema: upgrade failed');
        if (defined('HA_DEBUG') && HA_DEBUG) {
            error_log('HAvoice schema: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * مهاجرت نرم از schema قدیمی به v4 (ستون‌ها و FKهای تازه).
 * هر دستور جداگانه است تا نصب نیمه‌کاره InfinityFree نشکند.
 */
function db_schema_upgrade(PDO $pdo): void
{
    $try = static function (string $sql) use ($pdo): void {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            /* ستون یا قید از قبل هست */
        }
    };

    $try("CREATE TABLE IF NOT EXISTS ha_roles (
        role_key VARCHAR(20) NOT NULL,
        label VARCHAR(80) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (role_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $try("INSERT IGNORE INTO ha_roles (role_key, label, created_at) VALUES ('user','کاربر',NOW()), ('admin','مدیر',NOW())");
    $try("ALTER TABLE ha_users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'user'");
    $try("ALTER TABLE ha_users ADD CONSTRAINT fk_users_role FOREIGN KEY (role) REFERENCES ha_roles(role_key)");
    $try("ALTER TABLE ha_courses MODIFY category_slug VARCHAR(80) NULL DEFAULT NULL");
    $try("UPDATE ha_courses SET category_slug = NULL WHERE category_slug = ''");
    $try("ALTER TABLE ha_courses ADD CONSTRAINT fk_courses_category FOREIGN KEY (category_slug) REFERENCES ha_categories(slug) ON DELETE SET NULL ON UPDATE CASCADE");
    $try("ALTER TABLE ha_exercises ADD COLUMN course_id INT UNSIGNED NULL DEFAULT NULL");
    $try("ALTER TABLE ha_exercises ADD COLUMN lesson_id INT UNSIGNED NULL DEFAULT NULL");
    $try("ALTER TABLE ha_exercises ADD CONSTRAINT fk_exercises_course FOREIGN KEY (course_id) REFERENCES ha_courses(id) ON DELETE SET NULL");
    $try("ALTER TABLE ha_exercises ADD CONSTRAINT fk_exercises_lesson FOREIGN KEY (lesson_id) REFERENCES ha_lessons(id) ON DELETE SET NULL");
    $try("ALTER TABLE ha_media ADD COLUMN course_id INT UNSIGNED NULL DEFAULT NULL");
    $try("ALTER TABLE ha_media ADD COLUMN lesson_id INT UNSIGNED NULL DEFAULT NULL");
    $try("ALTER TABLE ha_media ADD CONSTRAINT fk_media_course FOREIGN KEY (course_id) REFERENCES ha_courses(id) ON DELETE SET NULL");
    $try("ALTER TABLE ha_media ADD CONSTRAINT fk_media_lesson FOREIGN KEY (lesson_id) REFERENCES ha_lessons(id) ON DELETE SET NULL");
    $try("ALTER TABLE ha_comments ADD COLUMN user_id VARCHAR(32) NULL DEFAULT NULL");
    $try("ALTER TABLE ha_comments ADD COLUMN kind ENUM('comment','experience') NOT NULL DEFAULT 'comment'");
    $try("ALTER TABLE ha_comments ADD CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE SET NULL");
    $try("ALTER TABLE ha_progress ADD CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE CASCADE");
    $try("ALTER TABLE ha_contact_messages ADD COLUMN status ENUM('unread','read') NOT NULL DEFAULT 'unread'");
    $try("ALTER TABLE ha_contact_messages ADD COLUMN read_at DATETIME NULL DEFAULT NULL");
    $try("ALTER TABLE ha_contact_messages ADD KEY idx_messages_status (status)");

    // v5: interactions
    $try("CREATE TABLE IF NOT EXISTS ha_content_reactions (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id VARCHAR(32) NOT NULL,
        content_type ENUM('article','video','audio','book','research','course','lesson','exercise','tip','category') NOT NULL,
        content_slug VARCHAR(120) NOT NULL,
        reaction_type ENUM('like','love','laugh','wow','sad') NOT NULL DEFAULT 'like',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_reaction_user_content (user_id, content_type, content_slug),
        KEY idx_reaction_content (content_type, content_slug),
        KEY idx_reaction_type (reaction_type),
        CONSTRAINT fk_reaction_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $try("CREATE TABLE IF NOT EXISTS ha_content_comments (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id VARCHAR(32) NOT NULL,
        content_type ENUM('article','video','audio','book','research','course','lesson','exercise','tip','category') NOT NULL,
        content_slug VARCHAR(120) NOT NULL,
        parent_id INT UNSIGNED NULL DEFAULT NULL,
        body TEXT NOT NULL,
        status ENUM('pending','approved') NOT NULL DEFAULT 'approved',
        likes_count INT UNSIGNED NOT NULL DEFAULT 0,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_cc_content (content_type, content_slug, status, created_at),
        KEY idx_cc_parent (parent_id),
        KEY idx_cc_user (user_id),
        CONSTRAINT fk_cc_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE CASCADE,
        CONSTRAINT fk_cc_parent FOREIGN KEY (parent_id) REFERENCES ha_content_comments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $try("CREATE TABLE IF NOT EXISTS ha_comment_likes (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id VARCHAR(32) NOT NULL,
        comment_id INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_comment_like (user_id, comment_id),
        KEY idx_cl_comment (comment_id),
        CONSTRAINT fk_cl_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE CASCADE,
        CONSTRAINT fk_cl_comment FOREIGN KEY (comment_id) REFERENCES ha_content_comments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // v6: virtual board + analytics
    $try("CREATE TABLE IF NOT EXISTS ha_board_posts (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id VARCHAR(32) NOT NULL,
        body TEXT NOT NULL,
        image VARCHAR(500) NOT NULL DEFAULT '',
        media_url VARCHAR(500) NOT NULL DEFAULT '',
        media_type ENUM('image','video','audio','link') NULL DEFAULT NULL,
        status ENUM('pending','approved','hidden') NOT NULL DEFAULT 'approved',
        likes_count INT UNSIGNED NOT NULL DEFAULT 0,
        reactions_count INT UNSIGNED NOT NULL DEFAULT 0,
        comments_count INT UNSIGNED NOT NULL DEFAULT 0,
        views_count INT UNSIGNED NOT NULL DEFAULT 0,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_board_status_created (status, created_at DESC),
        KEY idx_board_user (user_id, created_at DESC),
        KEY idx_board_created (created_at DESC),
        CONSTRAINT fk_board_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $try("CREATE TABLE IF NOT EXISTS ha_board_reactions (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id VARCHAR(32) NOT NULL,
        post_id INT UNSIGNED NOT NULL,
        reaction_type ENUM('like','love','laugh','wow','sad') NOT NULL DEFAULT 'like',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_board_reaction_user_post (user_id, post_id),
        KEY idx_board_reaction_post (post_id),
        KEY idx_board_reaction_type (reaction_type),
        CONSTRAINT fk_board_reaction_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE CASCADE,
        CONSTRAINT fk_board_reaction_post FOREIGN KEY (post_id) REFERENCES ha_board_posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $try("CREATE TABLE IF NOT EXISTS ha_board_comments (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id VARCHAR(32) NOT NULL,
        post_id INT UNSIGNED NOT NULL,
        parent_id INT UNSIGNED NULL DEFAULT NULL,
        body TEXT NOT NULL,
        status ENUM('pending','approved') NOT NULL DEFAULT 'approved',
        likes_count INT UNSIGNED NOT NULL DEFAULT 0,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_bc_post_status (post_id, status, created_at),
        KEY idx_bc_parent (parent_id),
        KEY idx_bc_user (user_id),
        CONSTRAINT fk_bc_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE CASCADE,
        CONSTRAINT fk_bc_post FOREIGN KEY (post_id) REFERENCES ha_board_posts(id) ON DELETE CASCADE,
        CONSTRAINT fk_bc_parent FOREIGN KEY (parent_id) REFERENCES ha_board_comments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $try("CREATE TABLE IF NOT EXISTS ha_board_comment_likes (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id VARCHAR(32) NOT NULL,
        comment_id INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_board_comment_like (user_id, comment_id),
        KEY idx_bcl_comment (comment_id),
        CONSTRAINT fk_bcl_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE CASCADE,
        CONSTRAINT fk_bcl_comment FOREIGN KEY (comment_id) REFERENCES ha_board_comments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $try("CREATE TABLE IF NOT EXISTS ha_site_visits (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id VARCHAR(32) NULL DEFAULT NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        route VARCHAR(80) NOT NULL DEFAULT '',
        slug VARCHAR(120) NOT NULL DEFAULT '',
        user_agent VARCHAR(500) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_visit_route (route, created_at),
        KEY idx_visit_created (created_at),
        KEY idx_visit_ip (ip, created_at),
        KEY idx_visit_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $try("CREATE TABLE IF NOT EXISTS ha_content_views (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        content_type VARCHAR(40) NOT NULL,
        content_slug VARCHAR(120) NOT NULL,
        views_count INT UNSIGNED NOT NULL DEFAULT 0,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_content_view (content_type, content_slug),
        KEY idx_cv_type (content_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function db_id_by_slug(string $table, string $slug): ?int
{
    $allowed = ['ha_courses' => 'slug', 'ha_lessons' => 'slug', 'ha_categories' => 'slug'];
    if (!isset($allowed[$table])) {
        return null;
    }
    $slug = slugify($slug);
    if ($slug === '') {
        return null;
    }
    $col = $allowed[$table];
    $r = db_one("SELECT id FROM {$table} WHERE {$col} = ? LIMIT 1", [$slug]);
    return $r ? (int) $r['id'] : null;
}

function db_nullable_slug(string $raw): ?string
{
    $s = slugify($raw);
    return $s === '' ? null : $s;
}

/* ------------------------------------------------------------------ */
/*  Helpers                                                           */
/* ------------------------------------------------------------------ */

function db_now(): string
{
    return date('Y-m-d H:i:s');
}

/**
 * آیا این جدول در دیتابیس وجود دارد؟ (برخلافِ repo_db_has که «پر بودنِ»
 * جدول را می‌سنجد، اینجا فقط وجودِ ساختار ملاک است.)
 *
 * برای جدول‌هایی که ممکن است خالی باشند ولی باید نوشته شوند — مثلِ
 * ha_contact_messages و ha_progress — به همین نیاز داریم.
 */
function db_table_exists(string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    $allowed = [
        'ha_schema_meta', 'ha_categories', 'ha_courses', 'ha_stages', 'ha_lessons', 'ha_articles',
        'ha_books', 'ha_media', 'ha_exercises', 'ha_tips', 'ha_research',
        'ha_users', 'ha_roles', 'ha_progress', 'ha_comments', 'ha_settings', 'ha_contact_messages',
        'ha_content_reactions', 'ha_content_comments', 'ha_comment_likes',
        'ha_board_posts', 'ha_board_reactions', 'ha_board_comments', 'ha_board_comment_likes',
        'ha_site_visits', 'ha_content_views',
    ];
    if (!in_array($table, $allowed, true) || !db_ready()) {
        return $cache[$table] = false;
    }
    try {
        $pdo = db();
        if ($pdo === null) {
            return $cache[$table] = false;
        }
        $st  = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
        $row = $st ? $st->fetch() : false;
        return $cache[$table] = is_array($row) && $row !== [];
    } catch (Throwable $e) {
        return $cache[$table] = false;
    }
}

function db_json_encode($value): string
{
    if ($value === null) {
        return '';
    }
    $j = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $j === false ? '' : $j;
}

function db_json_decode(?string $raw, $default = [])
{
    if ($raw === null || $raw === '') {
        return $default;
    }
    $d = json_decode($raw, true);
    return is_array($d) ? $d : $default;
}

function db_status(string $raw, string $default = 'published'): string
{
    $raw = strtolower(trim($raw));
    if ($raw === 'draft' || $raw === 'published') {
        return $raw;
    }
    return $default;
}

/**
 * اجرای SELECT با prepared statement.
 * @return list<array<string,mixed>>
 */
function db_all(string $sql, array $params = []): array
{
    $pdo = db();
    if ($pdo === null) {
        return [];
    }
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
        return is_array($rows) ? $rows : [];
    } catch (Throwable $e) {
        return [];
    }
}

/** @return array<string,mixed>|null */
function db_one(string $sql, array $params = []): ?array
{
    $rows = db_all($sql, $params);
    return $rows[0] ?? null;
}

function db_exec(string $sql, array $params = []): bool
{
    $pdo = db();
    if ($pdo === null) {
        return false;
    }
    try {
        $st = $pdo->prepare($sql);
        return $st->execute($params);
    } catch (Throwable $e) {
        return false;
    }
}

function db_last_id(): int
{
    $pdo = db();
    return $pdo ? (int) $pdo->lastInsertId() : 0;
}

/* ------------------------------------------------------------------ */
/*  Repository — Categories                                           */
/* ------------------------------------------------------------------ */

function db_categories_all(): array
{
    $rows = db_all('SELECT * FROM ha_categories ORDER BY sort_order ASC, id ASC');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'slug'        => (string) $r['slug'],
            'title'       => (string) $r['title'],
            'short'       => (string) ($r['short_title'] ?: $r['title']),
            'description' => (string) ($r['description'] ?? ''),
            'icon'        => (string) ($r['icon'] ?? ''),
            'color'       => (string) ($r['color'] ?? ''),
            'accent'      => (string) ($r['accent'] ?? ''),
            'status'      => (string) ($r['status'] ?? 'published'),
            'order'       => (int) ($r['sort_order'] ?? 0),
            'created_at'  => (string) ($r['created_at'] ?? ''),
            'updated_at'  => (string) ($r['updated_at'] ?? ''),
            '_db'         => true,
            '_id'         => (int) $r['id'],
        ];
    }
    return $out;
}

function db_category_upsert(array $item): bool
{
    $now = db_now();
    $slug = slugify((string) ($item['slug'] ?? ''));
    if ($slug === '') {
        return false;
    }
    $existing = db_one('SELECT id FROM ha_categories WHERE slug = ?', [$slug]);
    $params = [
        $slug,
        (string) ($item['title'] ?? ''),
        (string) ($item['short'] ?? $item['short_title'] ?? ''),
        (string) ($item['description'] ?? ''),
        (string) ($item['icon'] ?? ''),
        (string) ($item['color'] ?? ''),
        (string) ($item['accent'] ?? ''),
        db_status((string) ($item['status'] ?? 'published')),
        (int) ($item['order'] ?? $item['sort_order'] ?? 0),
        $now,
    ];
    if ($existing) {
        return db_exec(
            'UPDATE ha_categories SET title=?, short_title=?, description=?, icon=?, color=?, accent=?, status=?, sort_order=?, updated_at=? WHERE slug=?',
            [
                $params[1], $params[2], $params[3], $params[4], $params[5], $params[6],
                $params[7], $params[8], $now, $slug,
            ]
        );
    }
    return db_exec(
        'INSERT INTO ha_categories (slug, title, short_title, description, icon, color, accent, status, sort_order, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?)',
        array_merge([$slug], array_slice($params, 1, 8), [$now, $now])
    );
}

function db_category_delete(string $slug): bool
{
    $slug = slugify($slug);
    return $slug !== '' && db_exec('DELETE FROM ha_categories WHERE slug = ?', [$slug]);
}

/* ------------------------------------------------------------------ */
/*  Repository — Courses / Stages / Lessons                           */
/* ------------------------------------------------------------------ */

function db_courses_all(): array
{
    $courses = db_all('SELECT * FROM ha_courses ORDER BY sort_order ASC, id ASC');
    if ($courses === []) {
        return [];
    }
    $stages = db_all('SELECT * FROM ha_stages ORDER BY sort_order ASC, id ASC');
    $lessons = db_all('SELECT * FROM ha_lessons ORDER BY sort_order ASC, id ASC');

    $lessonsByStage = [];
    foreach ($lessons as $l) {
        $sid = (int) $l['stage_id'];
        $lessonsByStage[$sid][] = [
            'slug'         => (string) $l['slug'],
            'title'        => (string) $l['title'],
            'minutes'      => (int) $l['minutes'],
            'goal'         => (string) ($l['goal'] ?? ''),
            'blocks'       => db_json_decode($l['blocks_json'] ?? null, []),
            'drill'        => db_json_decode($l['drill_json'] ?? null, []),
            'prerequisite' => (string) ($l['prerequisite'] ?? ''),
            'refs'         => db_json_decode($l['refs_json'] ?? null, []),
            'status'       => (string) ($l['status'] ?? 'published'),
        ];
    }
    $stagesByCourse = [];
    foreach ($stages as $s) {
        $cid = (int) $s['course_id'];
        $stagesByCourse[$cid][] = [
            'id'         => (string) ($s['stage_key'] ?: ('stage-' . $s['id'])),
            'label'      => (string) ($s['label'] ?? ''),
            'title'      => (string) $s['title'],
            'summary'    => (string) ($s['summary'] ?? ''),
            'outcome'    => (string) ($s['outcome'] ?? ''),
            'duration'   => (string) ($s['duration'] ?? ''),
            'assessment' => db_json_decode($s['assessment_json'] ?? null, []),
            'lessons'    => $lessonsByStage[(int) $s['id']] ?? [],
        ];
    }

    $out = [];
    foreach ($courses as $c) {
        $id = (int) $c['id'];
        $out[] = [
            'slug'       => (string) $c['slug'],
            'title'      => (string) $c['title'],
            'category'   => (string) ($c['category_slug'] ?? ''),
            'level'      => (string) ($c['level'] ?? ''),
            'excerpt'    => (string) ($c['excerpt'] ?? ''),
            'intro'      => (string) ($c['intro'] ?? ''),
            'how_to'     => db_json_decode($c['how_to_json'] ?? null, []),
            'project'    => db_json_decode($c['project_json'] ?? null, []),
            'prereq'     => (string) ($c['prereq'] ?? ''),
            'featured'   => !empty($c['featured']),
            'status'     => (string) ($c['status'] ?? 'published'),
            'order'      => (int) ($c['sort_order'] ?? 0),
            'created_at' => (string) ($c['created_at'] ?? ''),
            'updated_at' => (string) ($c['updated_at'] ?? ''),
            'stages'     => $stagesByCourse[$id] ?? [],
            '_db'        => true,
            '_id'        => $id,
        ];
    }
    return $out;
}

/**
 * ذخیرهٔ کامل یک دوره + مراحل + درس‌ها (جایگزینی اتمیک stages/lessons).
 */
function db_course_save_full(array $course): bool
{
    $pdo = db();
    if ($pdo === null) {
        return false;
    }
    $slug = slugify((string) ($course['slug'] ?? ''));
    $orig = slugify((string) ($course['_original_slug'] ?? $slug));
    if ($slug === '') {
        return false;
    }
    $now = db_now();

    try {
        $pdo->beginTransaction();

        $existing = db_one('SELECT id, created_at FROM ha_courses WHERE slug = ?', [$orig !== '' ? $orig : $slug]);
        if (!$existing && $orig !== $slug) {
            $existing = db_one('SELECT id, created_at FROM ha_courses WHERE slug = ?', [$slug]);
        }

        $catSlug = db_nullable_slug((string) ($course['category'] ?? ''));
        if ($catSlug !== null && db_id_by_slug('ha_categories', $catSlug) === null) {
            $catSlug = null;
        }
        $fields = [
            $slug,
            (string) ($course['title'] ?? ''),
            $catSlug,
            (string) ($course['level'] ?? ''),
            (string) ($course['excerpt'] ?? ''),
            (string) ($course['intro'] ?? ''),
            db_json_encode($course['how_to'] ?? []),
            db_json_encode($course['project'] ?? []),
            (string) ($course['prereq'] ?? ''),
            !empty($course['featured']) ? 1 : 0,
            db_status((string) ($course['status'] ?? 'draft'), 'draft'),
            (int) ($course['order'] ?? 0),
            $now,
        ];

        if ($existing) {
            $cid = (int) $existing['id'];
            db_exec(
                'UPDATE ha_courses SET slug=?, title=?, category_slug=?, level=?, excerpt=?, intro=?, how_to_json=?, project_json=?, prereq=?, featured=?, status=?, sort_order=?, updated_at=? WHERE id=?',
                array_merge($fields, [$cid])
            );
            // حذف stages/lessons قبلی (CASCADE lessons)
            db_exec('DELETE FROM ha_stages WHERE course_id = ?', [$cid]);
        } else {
            db_exec(
                'INSERT INTO ha_courses (slug, title, category_slug, level, excerpt, intro, how_to_json, project_json, prereq, featured, status, sort_order, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                array_merge($fields, [$now])
            );
            $cid = db_last_id();
        }

        if ($cid <= 0) {
            $pdo->rollBack();
            return false;
        }

        foreach (array_values((array) ($course['stages'] ?? [])) as $si => $stage) {
            if (!is_array($stage)) {
                continue;
            }
            db_exec(
                'INSERT INTO ha_stages (course_id, stage_key, label, title, summary, outcome, duration, assessment_json, sort_order, status, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $cid,
                    (string) ($stage['id'] ?? ('stage-' . ($si + 1))),
                    (string) ($stage['label'] ?? ''),
                    (string) ($stage['title'] ?? ''),
                    (string) ($stage['summary'] ?? ''),
                    (string) ($stage['outcome'] ?? ''),
                    (string) ($stage['duration'] ?? ''),
                    db_json_encode($stage['assessment'] ?? []),
                    $si,
                    'published',
                    $now,
                    $now,
                ]
            );
            $sid = db_last_id();
            foreach (array_values((array) ($stage['lessons'] ?? [])) as $li => $lesson) {
                if (!is_array($lesson)) {
                    continue;
                }
                $lSlug = slugify((string) ($lesson['slug'] ?? ''));
                if ($lSlug === '') {
                    $lSlug = slugify((string) ($lesson['title'] ?? ('lesson-' . ($si + 1) . '-' . ($li + 1))));
                }
                if ($lSlug === '') {
                    continue;
                }
                db_exec(
                    'INSERT INTO ha_lessons (course_id, stage_id, slug, title, minutes, goal, blocks_json, drill_json, prerequisite, refs_json, sort_order, status, created_at, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    [
                        $cid,
                        $sid,
                        $lSlug,
                        (string) ($lesson['title'] ?? ''),
                        max(0, (int) ($lesson['minutes'] ?? 0)),
                        (string) ($lesson['goal'] ?? ''),
                        db_json_encode($lesson['blocks'] ?? []),
                        db_json_encode($lesson['drill'] ?? []),
                        slugify((string) ($lesson['prerequisite'] ?? '')),
                        db_json_encode($lesson['refs'] ?? []),
                        $li,
                        db_status((string) ($lesson['status'] ?? 'published')),
                        $now,
                        $now,
                    ]
                );
            }
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (defined('HA_DEBUG') && HA_DEBUG) {
            error_log('db_course_save_full: ' . $e->getMessage());
        }
        return false;
    }
}

function db_course_delete(string $slug): bool
{
    $slug = slugify($slug);
    return $slug !== '' && db_exec('DELETE FROM ha_courses WHERE slug = ?', [$slug]);
}

/* ------------------------------------------------------------------ */
/*  Repository — generic content rows (articles, books, media, …)     */
/* ------------------------------------------------------------------ */

function db_articles_all(): array
{
    $rows = db_all('SELECT * FROM ha_articles ORDER BY date_iso DESC, id DESC');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'slug'       => (string) $r['slug'],
            'title'      => (string) $r['title'],
            'excerpt'    => (string) ($r['excerpt'] ?? ''),
            'category'   => (string) ($r['category'] ?? ''),
            'field'      => (string) ($r['field_slug'] ?? ''),
            'tags'       => db_json_decode($r['tags_json'] ?? null, []),
            'date'       => (string) ($r['date_iso'] ?? ''),
            'date_fa'    => (string) ($r['date_fa'] ?? ''),
            'minutes'    => (int) ($r['minutes'] ?? 0),
            'blocks'     => db_json_decode($r['blocks_json'] ?? null, []),
            'featured'   => !empty($r['featured']),
            'status'     => (string) ($r['status'] ?? 'published'),
            'order'      => (int) ($r['sort_order'] ?? 0),
            'created_at' => (string) ($r['created_at'] ?? ''),
            'updated_at' => (string) ($r['updated_at'] ?? ''),
            '_db'        => true,
            '_id'        => (int) $r['id'],
        ];
    }
    return $out;
}

function db_article_upsert(array $item, string $origSlug = ''): bool
{
    $slug = slugify((string) ($item['slug'] ?? ''));
    if ($slug === '') {
        return false;
    }
    $orig = slugify($origSlug !== '' ? $origSlug : $slug);
    $now = db_now();
    $existing = db_one('SELECT id, created_at FROM ha_articles WHERE slug = ?', [$orig]);
    $vals = [
        $slug,
        (string) ($item['title'] ?? ''),
        (string) ($item['excerpt'] ?? ''),
        (string) ($item['category'] ?? ''),
        (string) ($item['field'] ?? ''),
        db_json_encode($item['tags'] ?? []),
        (string) ($item['date'] ?? ''),
        (string) ($item['date_fa'] ?? ''),
        (int) ($item['minutes'] ?? 0),
        db_json_encode($item['blocks'] ?? []),
        !empty($item['featured']) ? 1 : 0,
        db_status((string) ($item['status'] ?? 'published')),
        (int) ($item['order'] ?? 0),
        $now,
    ];
    if ($existing) {
        return db_exec(
            'UPDATE ha_articles SET slug=?, title=?, excerpt=?, category=?, field_slug=?, tags_json=?, date_iso=?, date_fa=?, minutes=?, blocks_json=?, featured=?, status=?, sort_order=?, updated_at=? WHERE id=?',
            array_merge($vals, [(int) $existing['id']])
        );
    }
    return db_exec(
        'INSERT INTO ha_articles (slug, title, excerpt, category, field_slug, tags_json, date_iso, date_fa, minutes, blocks_json, featured, status, sort_order, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        array_merge($vals, [$now])
    );
}

function db_article_delete(string $slug): bool
{
    $slug = slugify($slug);
    return $slug !== '' && db_exec('DELETE FROM ha_articles WHERE slug = ?', [$slug]);
}

function db_books_all(): array
{
    $rows = db_all('SELECT * FROM ha_books ORDER BY sort_order ASC, id DESC');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'slug'       => (string) $r['slug'],
            'title'      => (string) $r['title'],
            'author'     => (string) ($r['author'] ?? ''),
            'category'   => (string) ($r['category'] ?? ''),
            'field'      => (string) ($r['field_slug'] ?? ''),
            'excerpt'    => (string) ($r['excerpt'] ?? ''),
            'summary'    => (string) ($r['summary'] ?? ''),
            'minutes'    => (int) ($r['minutes'] ?? 0),
            'date_fa'    => (string) ($r['date_fa'] ?? ''),
            'tags'       => db_json_decode($r['tags_json'] ?? null, []),
            'lessons'    => db_json_decode($r['lessons_json'] ?? null, []),
            'cover'      => (string) ($r['cover'] ?? ''),
            'file'       => (string) ($r['file_url'] ?? ''),
            'featured'   => !empty($r['featured']),
            'status'     => (string) ($r['status'] ?? 'published'),
            'order'      => (int) ($r['sort_order'] ?? 0),
            'created_at' => (string) ($r['created_at'] ?? ''),
            'updated_at' => (string) ($r['updated_at'] ?? ''),
            '_db'        => true,
            '_id'        => (int) $r['id'],
        ];
    }
    return $out;
}

function db_book_upsert(array $item, string $origSlug = ''): bool
{
    $slug = slugify((string) ($item['slug'] ?? ''));
    if ($slug === '') {
        return false;
    }
    $orig = slugify($origSlug !== '' ? $origSlug : $slug);
    $now = db_now();
    $existing = db_one('SELECT id FROM ha_books WHERE slug = ?', [$orig]);
    $vals = [
        $slug,
        (string) ($item['title'] ?? ''),
        (string) ($item['author'] ?? ''),
        (string) ($item['category'] ?? ''),
        (string) ($item['field'] ?? ''),
        (string) ($item['excerpt'] ?? ''),
        (string) ($item['summary'] ?? ''),
        (int) ($item['minutes'] ?? 0),
        (string) ($item['date_fa'] ?? ''),
        db_json_encode($item['tags'] ?? []),
        db_json_encode($item['lessons'] ?? []),
        (string) ($item['cover'] ?? ''),
        (string) ($item['file'] ?? $item['file_url'] ?? ''),
        !empty($item['featured']) ? 1 : 0,
        db_status((string) ($item['status'] ?? 'published')),
        (int) ($item['order'] ?? 0),
        $now,
    ];
    if ($existing) {
        return db_exec(
            'UPDATE ha_books SET slug=?, title=?, author=?, category=?, field_slug=?, excerpt=?, summary=?, minutes=?, date_fa=?, tags_json=?, lessons_json=?, cover=?, file_url=?, featured=?, status=?, sort_order=?, updated_at=? WHERE id=?',
            array_merge($vals, [(int) $existing['id']])
        );
    }
    return db_exec(
        'INSERT INTO ha_books (slug, title, author, category, field_slug, excerpt, summary, minutes, date_fa, tags_json, lessons_json, cover, file_url, featured, status, sort_order, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        array_merge($vals, [$now])
    );
}

function db_book_delete(string $slug): bool
{
    $slug = slugify($slug);
    return $slug !== '' && db_exec('DELETE FROM ha_books WHERE slug = ?', [$slug]);
}

function db_media_all(): array
{
    $rows = db_all('SELECT * FROM ha_media ORDER BY sort_order ASC, id DESC');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'type'       => (string) $r['type'],
            'slug'       => (string) $r['slug'],
            'title'      => (string) $r['title'],
            'excerpt'    => (string) ($r['excerpt'] ?? ''),
            'url'        => (string) ($r['url'] ?? ''),
            'thumbnail'  => (string) ($r['thumbnail'] ?? ''),
            'category'   => (string) ($r['category'] ?? ''),
            'field'      => (string) ($r['field_slug'] ?? ''),
            'course'     => (string) ($r['course_slug'] ?? ''),
            'lesson'     => (string) ($r['lesson_slug'] ?? ''),
            'seconds'    => (int) ($r['seconds'] ?? 0),
            'date_fa'    => (string) ($r['date_fa'] ?? ''),
            'featured'   => !empty($r['featured']),
            'status'     => (string) ($r['status'] ?? 'draft'),
            'order'      => (int) ($r['sort_order'] ?? 0),
            'created_at' => (string) ($r['created_at'] ?? ''),
            'updated_at' => (string) ($r['updated_at'] ?? ''),
            '_db'        => true,
            '_id'        => (int) $r['id'],
        ];
    }
    return $out;
}

function db_media_upsert(array $item, string $origSlug = ''): bool
{
    $type = (($item['type'] ?? '') === 'audio') ? 'audio' : 'video';
    $slug = slugify((string) ($item['slug'] ?? ''));
    if ($slug === '') {
        return false;
    }
    $orig = slugify($origSlug !== '' ? $origSlug : $slug);
    $now = db_now();
    $existing = db_one('SELECT id FROM ha_media WHERE type = ? AND slug = ?', [$type, $orig]);
    $vals = [
        $type,
        $slug,
        (string) ($item['title'] ?? ''),
        (string) ($item['excerpt'] ?? ''),
        (string) ($item['url'] ?? ''),
        (string) ($item['thumbnail'] ?? ''),
        (string) ($item['category'] ?? ''),
        (string) ($item['field'] ?? ''),
        (string) ($item['course'] ?? ''),
        (string) ($item['lesson'] ?? ''),
        max(0, (int) ($item['seconds'] ?? 0)),
        (string) ($item['date_fa'] ?? ''),
        !empty($item['featured']) ? 1 : 0,
        db_status((string) ($item['status'] ?? 'draft'), 'draft'),
        (int) ($item['order'] ?? 0),
        $now,
    ];
    if ($existing) {
        $ok = db_exec(
            'UPDATE ha_media SET type=?, slug=?, title=?, excerpt=?, url=?, thumbnail=?, category=?, field_slug=?, course_slug=?, lesson_slug=?, seconds=?, date_fa=?, featured=?, status=?, sort_order=?, updated_at=? WHERE id=?',
            array_merge($vals, [(int) $existing['id']])
        );
        if ($ok) {
            db_exec(
                'UPDATE ha_media SET course_id = ?, lesson_id = ? WHERE id = ?',
                [
                    db_id_by_slug('ha_courses', (string) ($item['course'] ?? '')),
                    db_id_by_slug('ha_lessons', (string) ($item['lesson'] ?? '')),
                    (int) $existing['id'],
                ]
            );
        }
        return $ok;
    }
    $ok = db_exec(
        'INSERT INTO ha_media (type, slug, title, excerpt, url, thumbnail, category, field_slug, course_slug, lesson_slug, seconds, date_fa, featured, status, sort_order, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        array_merge($vals, [$now])
    );
    if ($ok) {
        $row = db_one('SELECT id FROM ha_media WHERE type = ? AND slug = ?', [$type, $slug]);
        if ($row) {
            db_exec(
                'UPDATE ha_media SET course_id = ?, lesson_id = ? WHERE id = ?',
                [
                    db_id_by_slug('ha_courses', (string) ($item['course'] ?? '')),
                    db_id_by_slug('ha_lessons', (string) ($item['lesson'] ?? '')),
                    (int) $row['id'],
                ]
            );
        }
    }
    return $ok;
}

function db_media_delete(string $type, string $slug): bool
{
    $type = $type === 'audio' ? 'audio' : 'video';
    $slug = slugify($slug);
    return $slug !== '' && db_exec('DELETE FROM ha_media WHERE type = ? AND slug = ?', [$type, $slug]);
}

function db_exercises_all(): array
{
    $rows = db_all('SELECT * FROM ha_exercises ORDER BY sort_order ASC, id ASC');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'         => (string) $r['ex_key'],
            'title'      => (string) $r['title'],
            'level'      => (string) ($r['level'] ?? ''),
            'focus'      => (string) ($r['focus'] ?? ''),
            'goal'       => (string) ($r['goal'] ?? ''),
            'tool'       => (string) ($r['tool'] ?? 'timer'),
            'seconds'    => (int) ($r['seconds'] ?? 0),
            'steps'      => db_json_decode($r['steps_json'] ?? null, []),
            'topics'     => db_json_decode($r['topics_json'] ?? null, []),
            'success'    => (string) ($r['success_text'] ?? ''),
            'note'       => (string) ($r['note_text'] ?? ''),
            'lesson'     => (string) ($r['lesson_slug'] ?? ''),
            'course'     => (string) ($r['course_slug'] ?? ''),
            'field'      => (string) ($r['field_slug'] ?? ''),
            'featured'   => !empty($r['featured']),
            'status'     => (string) ($r['status'] ?? 'published'),
            'order'      => (int) ($r['sort_order'] ?? 0),
            'created_at' => (string) ($r['created_at'] ?? ''),
            'updated_at' => (string) ($r['updated_at'] ?? ''),
            '_db'        => true,
            '_id'        => (int) $r['id'],
        ];
    }
    return $out;
}

function db_exercise_upsert(array $item, string $origKey = ''): bool
{
    $key = slugify((string) ($item['id'] ?? ''));
    if ($key === '') {
        return false;
    }
    $orig = slugify($origKey !== '' ? $origKey : $key);
    $now = db_now();
    $existing = db_one('SELECT id FROM ha_exercises WHERE ex_key = ?', [$orig]);
    $vals = [
        $key,
        (string) ($item['title'] ?? ''),
        (string) ($item['level'] ?? ''),
        (string) ($item['focus'] ?? ''),
        (string) ($item['goal'] ?? ''),
        (string) ($item['tool'] ?? 'timer'),
        max(0, (int) ($item['seconds'] ?? 0)),
        db_json_encode($item['steps'] ?? []),
        db_json_encode($item['topics'] ?? []),
        (string) ($item['success'] ?? ''),
        (string) ($item['note'] ?? ''),
        slugify((string) ($item['lesson'] ?? '')),
        slugify((string) ($item['course'] ?? '')),
        (string) ($item['field'] ?? ''),
        !empty($item['featured']) ? 1 : 0,
        db_status((string) ($item['status'] ?? 'published')),
        (int) ($item['order'] ?? 0),
        $now,
    ];
    if ($existing) {
        $ok = db_exec(
            'UPDATE ha_exercises SET ex_key=?, title=?, level=?, focus=?, goal=?, tool=?, seconds=?, steps_json=?, topics_json=?, success_text=?, note_text=?, lesson_slug=?, course_slug=?, field_slug=?, featured=?, status=?, sort_order=?, updated_at=? WHERE id=?',
            array_merge($vals, [(int) $existing['id']])
        );
    } else {
        $ok = db_exec(
            'INSERT INTO ha_exercises (ex_key, title, level, focus, goal, tool, seconds, steps_json, topics_json, success_text, note_text, lesson_slug, course_slug, field_slug, featured, status, sort_order, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            array_merge($vals, [$now])
        );
    }
    if ($ok) {
        db_exec(
            'UPDATE ha_exercises SET course_id = ?, lesson_id = ? WHERE ex_key = ?',
            [
                db_id_by_slug('ha_courses', (string) ($item['course'] ?? '')),
                db_id_by_slug('ha_lessons', (string) ($item['lesson'] ?? '')),
                $key,
            ]
        );
    }
    return $ok;
}

function db_exercise_delete(string $key): bool
{
    $key = slugify($key);
    return $key !== '' && db_exec('DELETE FROM ha_exercises WHERE ex_key = ?', [$key]);
}

function db_tips_all(): array
{
    $rows = db_all('SELECT * FROM ha_tips ORDER BY sort_order ASC, id ASC');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'         => (string) $r['tip_key'],
            'category'   => (string) ($r['category'] ?? ''),
            'text'       => (string) ($r['body_text'] ?? ''),
            'try'        => (string) ($r['try_text'] ?? ''),
            'field'      => (string) ($r['field_slug'] ?? ''),
            'status'     => (string) ($r['status'] ?? 'published'),
            'order'      => (int) ($r['sort_order'] ?? 0),
            'created_at' => (string) ($r['created_at'] ?? ''),
            'updated_at' => (string) ($r['updated_at'] ?? ''),
            '_db'        => true,
            '_id'        => (int) $r['id'],
        ];
    }
    return $out;
}

function db_tip_upsert(array $item, string $origKey = ''): bool
{
    $key = slugify((string) ($item['id'] ?? ''));
    if ($key === '') {
        return false;
    }
    $orig = slugify($origKey !== '' ? $origKey : $key);
    $now = db_now();
    $existing = db_one('SELECT id FROM ha_tips WHERE tip_key = ?', [$orig]);
    $vals = [
        $key,
        (string) ($item['category'] ?? ''),
        (string) ($item['text'] ?? ''),
        (string) ($item['try'] ?? ''),
        (string) ($item['field'] ?? ''),
        db_status((string) ($item['status'] ?? 'published')),
        (int) ($item['order'] ?? 0),
        $now,
    ];
    if ($existing) {
        return db_exec(
            'UPDATE ha_tips SET tip_key=?, category=?, body_text=?, try_text=?, field_slug=?, status=?, sort_order=?, updated_at=? WHERE id=?',
            array_merge($vals, [(int) $existing['id']])
        );
    }
    return db_exec(
        'INSERT INTO ha_tips (tip_key, category, body_text, try_text, field_slug, status, sort_order, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?)',
        array_merge($vals, [$now])
    );
}

function db_tip_delete(string $key): bool
{
    $key = slugify($key);
    return $key !== '' && db_exec('DELETE FROM ha_tips WHERE tip_key = ?', [$key]);
}

function db_research_all(): array
{
    $rows = db_all('SELECT * FROM ha_research ORDER BY sort_order ASC, id DESC');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'slug'       => (string) $r['slug'],
            'title'      => (string) $r['title'],
            'summary'    => (string) ($r['summary'] ?? ''),
            'category'   => (string) ($r['category'] ?? ''),
            'field'      => (string) ($r['field_slug'] ?? ''),
            'date_fa'    => (string) ($r['date_fa'] ?? ''),
            'blocks'     => db_json_decode($r['blocks_json'] ?? null, []),
            'featured'   => !empty($r['featured']),
            'status'     => (string) ($r['status'] ?? 'published'),
            'order'      => (int) ($r['sort_order'] ?? 0),
            'created_at' => (string) ($r['created_at'] ?? ''),
            'updated_at' => (string) ($r['updated_at'] ?? ''),
            '_db'        => true,
            '_id'        => (int) $r['id'],
        ];
    }
    return $out;
}

function db_research_upsert(array $item, string $origSlug = ''): bool
{
    $slug = slugify((string) ($item['slug'] ?? ''));
    if ($slug === '') {
        return false;
    }
    $orig = slugify($origSlug !== '' ? $origSlug : $slug);
    $now = db_now();
    $existing = db_one('SELECT id FROM ha_research WHERE slug = ?', [$orig]);
    $vals = [
        $slug,
        (string) ($item['title'] ?? ''),
        (string) ($item['summary'] ?? ''),
        (string) ($item['category'] ?? ''),
        (string) ($item['field'] ?? ''),
        (string) ($item['date_fa'] ?? ''),
        db_json_encode($item['blocks'] ?? []),
        !empty($item['featured']) ? 1 : 0,
        db_status((string) ($item['status'] ?? 'published')),
        (int) ($item['order'] ?? 0),
        $now,
    ];
    if ($existing) {
        return db_exec(
            'UPDATE ha_research SET slug=?, title=?, summary=?, category=?, field_slug=?, date_fa=?, blocks_json=?, featured=?, status=?, sort_order=?, updated_at=? WHERE id=?',
            array_merge($vals, [(int) $existing['id']])
        );
    }
    return db_exec(
        'INSERT INTO ha_research (slug, title, summary, category, field_slug, date_fa, blocks_json, featured, status, sort_order, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
        array_merge($vals, [$now])
    );
}

function db_research_delete(string $slug): bool
{
    $slug = slugify($slug);
    return $slug !== '' && db_exec('DELETE FROM ha_research WHERE slug = ?', [$slug]);
}

/* ------------------------------------------------------------------ */
/*  Users (DB-backed when available)                                  */
/* ------------------------------------------------------------------ */

function db_users_all(): array
{
    $rows = db_all('SELECT * FROM ha_users ORDER BY created_at ASC');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'         => (string) $r['id'],
            'name'       => (string) $r['name'],
            'email'      => (string) $r['email'],
            'pass_hash'  => (string) $r['pass_hash'],
            'role'       => (string) ($r['role'] ?? 'user'),
            'last_login' => !empty($r['last_login']) ? date('c', strtotime((string) $r['last_login'])) : null,
            'created_at' => !empty($r['created_at']) ? date('c', strtotime((string) $r['created_at'])) : '',
            'updated_at' => (string) ($r['updated_at'] ?? ''),
            '_db'        => true,
        ];
    }
    return $out;
}

function db_user_find_email(string $email): ?array
{
    $email = strtolower(trim($email));
    $r = db_one('SELECT * FROM ha_users WHERE email = ? LIMIT 1', [$email]);
    if (!$r) {
        return null;
    }
    return [
        'id'         => (string) $r['id'],
        'name'       => (string) $r['name'],
        'email'      => (string) $r['email'],
        'pass_hash'  => (string) $r['pass_hash'],
        'role'       => (string) ($r['role'] ?? 'user'),
        'last_login' => $r['last_login'] ?? null,
        'created_at' => $r['created_at'] ?? '',
        '_db'        => true,
    ];
}

function db_user_find_id(string $id): ?array
{
    $r = db_one('SELECT * FROM ha_users WHERE id = ? LIMIT 1', [$id]);
    if (!$r) {
        return null;
    }
    return [
        'id'         => (string) $r['id'],
        'name'       => (string) $r['name'],
        'email'      => (string) $r['email'],
        'pass_hash'  => (string) $r['pass_hash'],
        'role'       => (string) ($r['role'] ?? 'user'),
        'last_login' => $r['last_login'] ?? null,
        'created_at' => $r['created_at'] ?? '',
        '_db'        => true,
    ];
}

function db_user_create(string $name, string $email, string $password, string $role = 'user'): array
{
    $id = bin2hex(random_bytes(16));
    $now = db_now();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $email = strtolower(trim($email));
    $ok = db_exec(
        'INSERT INTO ha_users (id, name, email, pass_hash, role, created_at, updated_at) VALUES (?,?,?,?,?,?,?)',
        [$id, trim($name), $email, $hash, $role === 'admin' ? 'admin' : 'user', $now, $now]
    );
    if (!$ok) {
        return ['ok' => false, 'user' => null, 'error' => 'db'];
    }
    return [
        'ok'   => true,
        'user' => [
            'id' => $id, 'name' => trim($name), 'email' => $email,
            'pass_hash' => $hash, 'role' => $role === 'admin' ? 'admin' : 'user',
            'created_at' => date('c'), '_db' => true,
        ],
        'error' => null,
    ];
}

function db_user_update_password(string $id, string $password): bool
{
    return db_exec(
        'UPDATE ha_users SET pass_hash = ?, updated_at = ? WHERE id = ?',
        [password_hash($password, PASSWORD_DEFAULT), db_now(), $id]
    );
}

function db_user_set_role(string $id, string $role): bool
{
    $role = $role === 'admin' ? 'admin' : 'user';
    return db_exec('UPDATE ha_users SET role = ?, updated_at = ? WHERE id = ?', [$role, db_now(), $id]);
}

function db_user_touch_login(string $id): bool
{
    return db_exec('UPDATE ha_users SET last_login = ?, updated_at = ? WHERE id = ?', [db_now(), db_now(), $id]);
}

function db_user_delete(string $id): bool
{
    return $id !== '' && db_exec('DELETE FROM ha_users WHERE id = ?', [$id]);
}

function db_user_count(): int
{
    $r = db_one('SELECT COUNT(*) AS c FROM ha_users');
    return (int) ($r['c'] ?? 0);
}

/* ------------------------------------------------------------------ */
/*  Progress (lesson / exercise state per user)                       */
/* ------------------------------------------------------------------ */

/** @return list<array{item_type:string,item_key:string,state:string}> */
function db_progress_all(string $userId): array
{
    if ($userId === '') {
        return [];
    }
    return db_all(
        'SELECT item_type, item_key, state FROM ha_progress WHERE user_id = ? ORDER BY updated_at ASC',
        [$userId]
    );
}

/**
 * نوشتنِ وضعیتِ یک قلم. state='' ⇒ حذفِ رکورد.
 */
function db_progress_set(string $userId, string $type, string $key, string $state): bool
{
    if ($userId === '' || $key === '') {
        return false;
    }
    $type = $type === 'exercise' ? 'exercise' : 'lesson';
    if ($state !== 'started' && $state !== 'done') {
        return db_exec(
            'DELETE FROM ha_progress WHERE user_id = ? AND item_type = ? AND item_key = ?',
            [$userId, $type, $key]
        );
    }
    $now = db_now();
    return db_exec(
        'INSERT INTO ha_progress (user_id, item_type, item_key, state, created_at, updated_at)
         VALUES (?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE state = VALUES(state), updated_at = VALUES(updated_at)',
        [$userId, $type, $key, $state, $now, $now]
    );
}

/* ------------------------------------------------------------------ */
/*  Contact messages                                                  */
/* ------------------------------------------------------------------ */

/** @return list<array<string,mixed>> */
function db_messages_all(int $limit = 500, ?string $status = null, string $search = ''): array
{
    $sql = 'SELECT id, name, email, subject, message, status, read_at, ip, created_at FROM ha_contact_messages';
    $where = [];
    $params = [];
    if ($status === 'read' || $status === 'unread') {
        $where[] = 'status = ?';
        $params[] = $status;
    }
    $search = trim($search);
    if ($search !== '') {
        $where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . max(1, min(2000, $limit));
    $rows = db_all($sql, $params);
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'      => (int) $r['id'],
            'time'    => (string) ($r['created_at'] ?? ''),
            'name'    => (string) ($r['name'] ?? ''),
            'email'   => (string) ($r['email'] ?? ''),
            'subject' => (string) ($r['subject'] ?? ''),
            'message' => (string) ($r['message'] ?? ''),
            'status'  => ((string) ($r['status'] ?? 'unread')) === 'read' ? 'read' : 'unread',
            'read_at' => (string) ($r['read_at'] ?? ''),
            'ip'      => (string) ($r['ip'] ?? ''),
            'source'  => 'db',
            'ref'     => 'db-' . (int) $r['id'],
        ];
    }
    return $out;
}

function db_message_save(array $record): bool
{
    $status = ($record['status'] ?? 'unread') === 'read' ? 'read' : 'unread';
    return db_exec(
        'INSERT INTO ha_contact_messages (name, email, subject, message, status, ip, created_at) VALUES (?,?,?,?,?,?,?)',
        [
            (string) ($record['name'] ?? ''),
            (string) ($record['email'] ?? ''),
            (string) ($record['subject'] ?? ''),
            (string) ($record['message'] ?? ''),
            $status,
            (string) ($record['ip'] ?? ''),
            (string) ($record['time'] ?? db_now()),
        ]
    );
}

function db_message_find(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $r = db_one('SELECT id, name, email, subject, message, status, read_at, ip, created_at FROM ha_contact_messages WHERE id = ? LIMIT 1', [$id]);
    if (!$r) {
        return null;
    }
    return [
        'id'      => (int) $r['id'],
        'time'    => (string) ($r['created_at'] ?? ''),
        'name'    => (string) ($r['name'] ?? ''),
        'email'   => (string) ($r['email'] ?? ''),
        'subject' => (string) ($r['subject'] ?? ''),
        'message' => (string) ($r['message'] ?? ''),
        'status'  => ((string) ($r['status'] ?? 'unread')) === 'read' ? 'read' : 'unread',
        'read_at' => (string) ($r['read_at'] ?? ''),
        'ip'      => (string) ($r['ip'] ?? ''),
        'source'  => 'db',
        'ref'     => 'db-' . (int) $r['id'],
    ];
}

function db_message_set_status(int $id, string $status): bool
{
    if ($id <= 0) {
        return false;
    }
    $status = $status === 'read' ? 'read' : 'unread';
    $readAt = $status === 'read' ? db_now() : null;
    return db_exec('UPDATE ha_contact_messages SET status = ?, read_at = ? WHERE id = ?', [$status, $readAt, $id]);
}

function db_message_delete(int $id): bool
{
    return $id > 0 && db_exec('DELETE FROM ha_contact_messages WHERE id = ?', [$id]);
}

function db_message_count(?string $status = null): int
{
    if ($status === 'unread' || $status === 'read') {
        $r = db_one('SELECT COUNT(*) AS c FROM ha_contact_messages WHERE status = ?', [$status]);
    } else {
        $r = db_one('SELECT COUNT(*) AS c FROM ha_contact_messages');
    }
    return (int) ($r['c'] ?? 0);
}

/* ------------------------------------------------------------------ */
/*  Bootstrap flag (first admin — one time only)                      */
/* ------------------------------------------------------------------ */

function db_setting_get(string $key, string $default = ''): string
{
    $r = db_one('SELECT setting_value FROM ha_settings WHERE setting_key = ? LIMIT 1', [$key]);
    return $r !== null ? (string) ($r['setting_value'] ?? $default) : $default;
}

/* ------------------------------------------------------------------ */
/*  Settings                                                          */
/* ------------------------------------------------------------------ */

function db_settings_get(): array
{
    $rows = db_all('SELECT setting_key, setting_value FROM ha_settings');
    $out = [];
    foreach ($rows as $r) {
        $out[(string) $r['setting_key']] = (string) ($r['setting_value'] ?? '');
    }
    return $out;
}

function db_settings_set(array $settings): bool
{
    $now = db_now();
    $ok = true;
    foreach ($settings as $k => $v) {
        $k = preg_replace('/[^a-z0-9_]/i', '', (string) $k) ?? '';
        if ($k === '') {
            continue;
        }
        $ok = db_exec(
            'INSERT INTO ha_settings (setting_key, setting_value, updated_at) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)',
            [$k, (string) $v, $now]
        ) && $ok;
    }
    return $ok;
}

/* ------------------------------------------------------------------ */
/*  Content status toggle (generic)                                   */
/* ------------------------------------------------------------------ */

function db_set_status(string $table, string $keyCol, string $key, string $status): bool
{
    $allowed = [
        'ha_categories' => 'slug',
        'ha_courses'    => 'slug',
        'ha_articles'   => 'slug',
        'ha_books'      => 'slug',
        'ha_exercises'  => 'ex_key',
        'ha_tips'       => 'tip_key',
        'ha_research'   => 'slug',
        'ha_media'      => 'slug',
    ];
    if (!isset($allowed[$table]) || $allowed[$table] !== $keyCol) {
        return false;
    }
    $status = db_status($status, 'draft');
    return db_exec("UPDATE {$table} SET status = ?, updated_at = ? WHERE {$keyCol} = ?", [$status, db_now(), $key]);
}

/* ------------------------------------------------------------------ */
/*  Interactions — Reactions / Comments / Likes (v5)                  */
/* ------------------------------------------------------------------ */

function db_reaction_types(): array
{
    return ['like','love','laugh','wow','sad'];
}

function db_content_types(): array
{
    return ['article','video','audio','book','research','course','lesson','exercise','tip','category'];
}

function db_reaction_get_user(string $userId, string $cType, string $cSlug): ?array
{
    if ($userId === '' || $cType === '' || $cSlug === '') return null;
    return db_one('SELECT * FROM ha_content_reactions WHERE user_id = ? AND content_type = ? AND content_slug = ? LIMIT 1', [$userId, $cType, $cSlug]);
}

function db_reaction_set(string $userId, string $cType, string $cSlug, string $reaction): bool
{
    $now = db_now();
    $reaction = in_array($reaction, db_reaction_types(), true) ? $reaction : 'like';
    // upsert
    return db_exec(
        'INSERT INTO ha_content_reactions (user_id, content_type, content_slug, reaction_type, created_at, updated_at)
         VALUES (?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE reaction_type = VALUES(reaction_type), updated_at = VALUES(updated_at)',
        [$userId, $cType, $cSlug, $reaction, $now, $now]
    );
}

function db_reaction_delete(string $userId, string $cType, string $cSlug): bool
{
    return db_exec('DELETE FROM ha_content_reactions WHERE user_id = ? AND content_type = ? AND content_slug = ? LIMIT 1', [$userId, $cType, $cSlug]);
}

function db_reaction_counts(string $cType, string $cSlug): array
{
    $rows = db_all('SELECT reaction_type, COUNT(*) AS c FROM ha_content_reactions WHERE content_type = ? AND content_slug = ? GROUP BY reaction_type', [$cType, $cSlug]);
    $out = ['total'=>0,'like'=>0,'love'=>0,'laugh'=>0,'wow'=>0,'sad'=>0];
    foreach ($rows as $r) {
        $t = (string)($r['reaction_type'] ?? '');
        $cnt = (int)($r['c'] ?? 0);
        if (isset($out[$t])) $out[$t] = $cnt;
        $out['total'] += $cnt;
    }
    return $out;
}

function db_reaction_list(string $cType, string $cSlug, int $limit = 50): array
{
    return db_all('SELECT r.*, u.name FROM ha_content_reactions r LEFT JOIN ha_users u ON u.id = r.user_id WHERE r.content_type = ? AND r.content_slug = ? ORDER BY r.updated_at DESC LIMIT '.max(1,min(200,$limit)), [$cType, $cSlug]);
}

/* Comments */

function db_content_comment_add(string $userId, string $cType, string $cSlug, ?int $parentId, string $body, string $ip): ?int
{
    $now = db_now();
    $ok = db_exec(
        'INSERT INTO ha_content_comments (user_id, content_type, content_slug, parent_id, body, status, likes_count, ip, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?)',
        [$userId, $cType, $cSlug, $parentId, $body, 'approved', 0, $ip, $now, $now]
    );
    return $ok ? db_last_id() : null;
}

function db_content_comments_all(string $cType, string $cSlug, string $status = 'approved'): array
{
    $sql = 'SELECT cc.*, u.name, u.email FROM ha_content_comments cc LEFT JOIN ha_users u ON u.id = cc.user_id WHERE cc.content_type = ? AND cc.content_slug = ?';
    $params = [$cType, $cSlug];
    if ($status === 'approved' || $status === 'pending') {
        $sql .= ' AND cc.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY cc.created_at ASC, cc.id ASC';
    return db_all($sql, $params);
}

function db_content_comment_find(int $id): ?array
{
    return db_one('SELECT * FROM ha_content_comments WHERE id = ? LIMIT 1', [$id]);
}

function db_content_comment_counts(string $cType, string $cSlug): array
{
    $row = db_one('SELECT COUNT(*) AS total FROM ha_content_comments WHERE content_type = ? AND content_slug = ? AND status = ?', [$cType, $cSlug, 'approved']);
    $total = (int)($row['total'] ?? 0);
    // replies count = total where parent_id not null
    $row2 = db_one('SELECT COUNT(*) AS replies FROM ha_content_comments WHERE content_type = ? AND content_slug = ? AND parent_id IS NOT NULL AND status = ?', [$cType, $cSlug, 'approved']);
    $replies = (int)($row2['replies'] ?? 0);
    $comments = $total - $replies;
    return ['total'=>$total, 'comments'=>$comments, 'replies'=>$replies];
}

function db_comment_like_exists(string $userId, int $commentId): bool
{
    $r = db_one('SELECT id FROM ha_comment_likes WHERE user_id = ? AND comment_id = ? LIMIT 1', [$userId, $commentId]);
    return $r !== null;
}

function db_comment_like_add(string $userId, int $commentId): bool
{
    $now = db_now();
    $ok = db_exec('INSERT IGNORE INTO ha_comment_likes (user_id, comment_id, created_at) VALUES (?,?,?)', [$userId, $commentId, $now]);
    if ($ok) {
        db_exec('UPDATE ha_content_comments SET likes_count = likes_count + 1, updated_at = ? WHERE id = ?', [$now, $commentId]);
    }
    return $ok;
}

function db_comment_like_remove(string $userId, int $commentId): bool
{
    $ok = db_exec('DELETE FROM ha_comment_likes WHERE user_id = ? AND comment_id = ? LIMIT 1', [$userId, $commentId]);
    if ($ok) {
        db_exec('UPDATE ha_content_comments SET likes_count = GREATEST(likes_count - 1, 0), updated_at = ? WHERE id = ? AND likes_count > 0', [db_now(), $commentId]);
    }
    return $ok;
}

function db_comment_likes_for_user(string $userId, array $commentIds): array
{
    if ($userId === '' || $commentIds === []) return [];
    $place = implode(',', array_fill(0, count($commentIds), '?'));
    $rows = db_all('SELECT comment_id FROM ha_comment_likes WHERE user_id = ? AND comment_id IN ('.$place.')', array_merge([$userId], $commentIds));
    $out = [];
    foreach ($rows as $r) $out[(int)$r['comment_id']] = true;
    return $out;
}


/* ------------------------------------------------------------------ */
/*  Virtual Board v6 — Posts / Reactions / Comments / Visits          */
/* ------------------------------------------------------------------ */

function db_board_post_add(string $userId, string $body, string $image, string $mediaUrl, ?string $mediaType, string $ip): ?int
{
    $now = db_now();
    $ok = db_exec(
        'INSERT INTO ha_board_posts (user_id, body, image, media_url, media_type, status, likes_count, reactions_count, comments_count, views_count, ip, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [$userId, $body, $image, $mediaUrl, $mediaType, 'approved', 0, 0, 0, 0, $ip, $now, $now]
    );
    return $ok ? db_last_id() : null;
}

function db_board_posts_all(string $status = 'approved', int $limit = 100, int $offset = 0): array
{
    $sql = 'SELECT bp.*, u.name, u.email FROM ha_board_posts bp LEFT JOIN ha_users u ON u.id = bp.user_id';
    $params = [];
    if ($status === 'approved' || $status === 'pending' || $status === 'hidden') {
        $sql .= ' WHERE bp.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY bp.created_at DESC, bp.id DESC LIMIT '.max(1,min(500,$limit)).' OFFSET '.max(0,$offset);
    return db_all($sql, $params);
}

function db_board_post_find(int $id): ?array
{
    return db_one('SELECT bp.*, u.name, u.email FROM ha_board_posts bp LEFT JOIN ha_users u ON u.id = bp.user_id WHERE bp.id = ? LIMIT 1', [$id]);
}

function db_board_post_find_many(array $ids): array
{
    if ($ids === []) return [];
    $place = implode(',', array_fill(0, count($ids), '?'));
    return db_all('SELECT * FROM ha_board_posts WHERE id IN ('.$place.')', $ids);
}

function db_board_post_delete(int $id): bool
{
    return db_exec('DELETE FROM ha_board_posts WHERE id = ? LIMIT 1', [$id]);
}

function db_board_post_set_status(int $id, string $status): bool
{
    $status = in_array($status, ['pending','approved','hidden'], true) ? $status : 'approved';
    return db_exec('UPDATE ha_board_posts SET status = ?, updated_at = ? WHERE id = ?', [$status, db_now(), $id]);
}

function db_board_post_inc(string $field, int $postId, int $delta = 1): bool
{
    $allowed = ['likes_count','reactions_count','comments_count','views_count'];
    if (!in_array($field, $allowed, true)) return false;
    $delta = $delta > 0 ? 1 : -1;
    if ($delta > 0) {
        return db_exec('UPDATE ha_board_posts SET '.$field.' = '.$field.' + 1, updated_at = ? WHERE id = ?', [db_now(), $postId]);
    } else {
        return db_exec('UPDATE ha_board_posts SET '.$field.' = GREATEST('.$field.' - 1, 0), updated_at = ? WHERE id = ? AND '.$field.' > 0', [db_now(), $postId]);
    }
}

function db_board_reaction_get(int $postId, string $userId): ?array
{
    return db_one('SELECT * FROM ha_board_reactions WHERE post_id = ? AND user_id = ? LIMIT 1', [$postId, $userId]);
}

function db_board_reaction_set(int $postId, string $userId, string $type): bool
{
    $now = db_now();
    $type = in_array($type, ['like','love','laugh','wow','sad'], true) ? $type : 'like';
    return db_exec(
        'INSERT INTO ha_board_reactions (user_id, post_id, reaction_type, created_at, updated_at)
         VALUES (?,?,?,?,?)
         ON DUPLICATE KEY UPDATE reaction_type = VALUES(reaction_type), updated_at = VALUES(updated_at)',
        [$userId, $postId, $type, $now, $now]
    );
}

function db_board_reaction_delete(int $postId, string $userId): bool
{
    return db_exec('DELETE FROM ha_board_reactions WHERE post_id = ? AND user_id = ? LIMIT 1', [$postId, $userId]);
}

function db_board_reaction_counts(int $postId): array
{
    $rows = db_all('SELECT reaction_type, COUNT(*) AS c FROM ha_board_reactions WHERE post_id = ? GROUP BY reaction_type', [$postId]);
    $out = ['total'=>0,'like'=>0,'love'=>0,'laugh'=>0,'wow'=>0,'sad'=>0];
    foreach ($rows as $r) {
        $t = (string)($r['reaction_type'] ?? '');
        $cnt = (int)($r['c'] ?? 0);
        if (isset($out[$t])) $out[$t] = $cnt;
        $out['total'] += $cnt;
    }
    return $out;
}

function db_board_reactions_for_user(string $userId, array $postIds): array
{
    if ($userId === '' || $postIds === []) return [];
    $place = implode(',', array_fill(0, count($postIds), '?'));
    $rows = db_all('SELECT post_id, reaction_type FROM ha_board_reactions WHERE user_id = ? AND post_id IN ('.$place.')', array_merge([$userId], $postIds));
    $out = [];
    foreach ($rows as $r) $out[(int)$r['post_id']] = (string)($r['reaction_type'] ?? '');
    return $out;
}

function db_board_comment_add(int $postId, string $userId, ?int $parentId, string $body, string $ip): ?int
{
    $now = db_now();
    $ok = db_exec(
        'INSERT INTO ha_board_comments (user_id, post_id, parent_id, body, status, likes_count, ip, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?)',
        [$userId, $postId, $parentId, $body, 'approved', 0, $ip, $now, $now]
    );
    return $ok ? db_last_id() : null;
}

function db_board_comments_all(int $postId, string $status = 'approved'): array
{
    $sql = 'SELECT bc.*, u.name, u.email FROM ha_board_comments bc LEFT JOIN ha_users u ON u.id = bc.user_id WHERE bc.post_id = ?';
    $params = [$postId];
    if ($status === 'approved' || $status === 'pending') {
        $sql .= ' AND bc.status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY bc.created_at ASC, bc.id ASC';
    return db_all($sql, $params);
}

function db_board_comment_find(int $id): ?array
{
    return db_one('SELECT * FROM ha_board_comments WHERE id = ? LIMIT 1', [$id]);
}

function db_board_comment_delete(int $id): bool
{
    return db_exec('DELETE FROM ha_board_comments WHERE id = ? LIMIT 1', [$id]);
}

function db_board_comment_set_status(int $id, string $status): bool
{
    $status = in_array($status, ['pending','approved'], true) ? $status : 'approved';
    return db_exec('UPDATE ha_board_comments SET status = ?, updated_at = ? WHERE id = ?', [$status, db_now(), $id]);
}

function db_board_comment_counts(int $postId): array
{
    $row = db_one('SELECT COUNT(*) AS total FROM ha_board_comments WHERE post_id = ? AND status = ?', [$postId, 'approved']);
    $total = (int)($row['total'] ?? 0);
    $row2 = db_one('SELECT COUNT(*) AS replies FROM ha_board_comments WHERE post_id = ? AND parent_id IS NOT NULL AND status = ?', [$postId, 'approved']);
    $replies = (int)($row2['replies'] ?? 0);
    return ['total'=>$total,'comments'=>$total-$replies,'replies'=>$replies];
}

function db_board_comment_like_exists(string $userId, int $commentId): bool
{
    $r = db_one('SELECT id FROM ha_board_comment_likes WHERE user_id = ? AND comment_id = ? LIMIT 1', [$userId, $commentId]);
    return $r !== null;
}

function db_board_comment_like_add(string $userId, int $commentId): bool
{
    $now = db_now();
    $ok = db_exec('INSERT IGNORE INTO ha_board_comment_likes (user_id, comment_id, created_at) VALUES (?,?,?)', [$userId, $commentId, $now]);
    if ($ok) {
        db_exec('UPDATE ha_board_comments SET likes_count = likes_count + 1, updated_at = ? WHERE id = ?', [$now, $commentId]);
    }
    return $ok;
}

function db_board_comment_like_remove(string $userId, int $commentId): bool
{
    $ok = db_exec('DELETE FROM ha_board_comment_likes WHERE user_id = ? AND comment_id = ? LIMIT 1', [$userId, $commentId]);
    if ($ok) {
        db_exec('UPDATE ha_board_comments SET likes_count = GREATEST(likes_count - 1, 0), updated_at = ? WHERE id = ? AND likes_count > 0', [db_now(), $commentId]);
    }
    return $ok;
}

function db_board_comment_likes_for_user(string $userId, array $commentIds): array
{
    if ($userId === '' || $commentIds === []) return [];
    $place = implode(',', array_fill(0, count($commentIds), '?'));
    $rows = db_all('SELECT comment_id FROM ha_board_comment_likes WHERE user_id = ? AND comment_id IN ('.$place.')', array_merge([$userId], $commentIds));
    $out = [];
    foreach ($rows as $r) $out[(int)$r['comment_id']] = true;
    return $out;
}

/* Analytics */

function db_site_visit_add(?string $userId, string $ip, string $route, string $slug, string $ua): bool
{
    $now = db_now();
    return db_exec('INSERT INTO ha_site_visits (user_id, ip, route, slug, user_agent, created_at) VALUES (?,?,?,?,?,?)',
        [$userId, $ip, $route, $slug, substr($ua,0,500), $now]);
}

function db_site_visits_count(string $period = 'all'): int
{
    if ($period === 'today') {
        $r = db_one("SELECT COUNT(*) AS c FROM ha_site_visits WHERE DATE(created_at) = CURDATE()");
    } elseif ($period === 'week') {
        $r = db_one("SELECT COUNT(*) AS c FROM ha_site_visits WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    } elseif ($period === 'month') {
        $r = db_one("SELECT COUNT(*) AS c FROM ha_site_visits WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    } else {
        $r = db_one('SELECT COUNT(*) AS c FROM ha_site_visits');
    }
    return (int)($r['c'] ?? 0);
}

function db_content_view_inc(string $cType, string $cSlug): bool
{
    $now = db_now();
    return db_exec(
        'INSERT INTO ha_content_views (content_type, content_slug, views_count, updated_at) VALUES (?,?,1,?)
         ON DUPLICATE KEY UPDATE views_count = views_count + 1, updated_at = VALUES(updated_at)',
        [$cType, $cSlug, $now]
    );
}

function db_content_views_count(string $cType = '', string $cSlug = ''): int
{
    if ($cType !== '' && $cSlug !== '') {
        $r = db_one('SELECT views_count AS c FROM ha_content_views WHERE content_type = ? AND content_slug = ? LIMIT 1', [$cType, $cSlug]);
        return (int)($r['c'] ?? 0);
    }
    if ($cType !== '') {
        $r = db_one('SELECT SUM(views_count) AS c FROM ha_content_views WHERE content_type = ?', [$cType]);
        return (int)($r['c'] ?? 0);
    }
    $r = db_one('SELECT SUM(views_count) AS c FROM ha_content_views');
    return (int)($r['c'] ?? 0);
}

function db_board_stats(): array
{
    $posts = db_one('SELECT COUNT(*) AS c FROM ha_board_posts WHERE status = ?', ['approved']);
    $reactions = db_one('SELECT COUNT(*) AS c FROM ha_board_reactions');
    $comments = db_one('SELECT COUNT(*) AS c FROM ha_board_comments WHERE status = ?', ['approved']);
    $likes = db_one('SELECT COUNT(*) AS c FROM (SELECT id FROM ha_board_reactions WHERE reaction_type = ? UNION ALL SELECT id FROM ha_board_comment_likes) AS t', ['like']);
    // for content reactions/comments
    $contentReactions = db_one('SELECT COUNT(*) AS c FROM ha_content_reactions');
    $contentComments = db_one('SELECT COUNT(*) AS c FROM ha_content_comments WHERE status = ?', ['approved']);
    return [
        'posts' => (int)($posts['c'] ?? 0),
        'board_reactions' => (int)($reactions['c'] ?? 0),
        'board_comments' => (int)($comments['c'] ?? 0),
        'board_likes' => (int)($likes['c'] ?? 0),
        'content_reactions' => (int)($contentReactions['c'] ?? 0),
        'content_comments' => (int)($contentComments['c'] ?? 0),
    ];
}


