<?php
/**
 * HAvoice — پل محتوا: DB (اولویت) ⇄ فایل + JSON (fallback)
 *
 * خواندن: اگر DB آماده و برای آن نوع داده داشته باشد → DB
 * نوشتن Admin: DB (اگر آماده) + آینهٔ JSON برای پشتیبان/آفلاین
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/** آیا جدول محتوا در DB داده دارد؟ */
function repo_db_has(string $table): bool
{
    if (!db_ready()) {
        return false;
    }
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    $allowed = [
        'ha_categories', 'ha_courses', 'ha_articles', 'ha_books',
        'ha_media', 'ha_exercises', 'ha_tips', 'ha_research', 'ha_users',
    ];
    if (!in_array($table, $allowed, true)) {
        return $cache[$table] = false;
    }
    $r = db_one("SELECT COUNT(*) AS c FROM {$table}");
    return $cache[$table] = ((int) ($r['c'] ?? 0)) > 0;
}

function repo_invalidate_cache(): void
{
    // static cache داخل repo_db_has با request-life است؛ seed وسط request
    // با فراخوانی مستقیم db_* کار می‌کند. برای تست‌ها:
}

/* ------------------------------------------------------------------ */
/*  خواندن                                                            */
/* ------------------------------------------------------------------ */

function repo_categories(): array
{
    if (repo_db_has('ha_categories')) {
        return db_categories_all();
    }
    return ha_merge_overrides(data('categories'), admin_load('categories'));
}

function repo_courses(): array
{
    if (repo_db_has('ha_courses')) {
        return db_courses_all();
    }
    $raw = data('course');
    if (isset($raw['courses']) && is_array($raw['courses'])) {
        return ha_merge_overrides($raw['courses'], admin_courses_json());
    }
    if (isset($raw['stages'])) {
        return ha_merge_overrides([[
            'slug'     => 'public-speaking-fundamentals',
            'title'    => $raw['title'] ?? 'مسیر آموزشی',
            'category' => 'public-speaking',
            'level'    => 'مقدماتی تا متوسط',
            'excerpt'  => $raw['intro'] ?? '',
            'intro'    => $raw['intro'] ?? '',
            'how_to'   => $raw['how_to'] ?? [],
            'stages'   => $raw['stages'] ?? [],
            'featured' => true,
        ]], admin_courses_json());
    }
    return admin_courses_json();
}

/** فقط JSON پنل (بدون DB) — برای fallback و dual-write. */
function admin_courses_json(): array
{
    return admin_load('courses');
}

function repo_articles(): array
{
    if (repo_db_has('ha_articles')) {
        return db_articles_all();
    }
    return ha_merge_overrides(data('articles'), admin_load('articles'));
}

function repo_books(): array
{
    if (repo_db_has('ha_books')) {
        return db_books_all();
    }
    return ha_merge_overrides(data('books'), admin_load('books'));
}

function repo_media(): array
{
    if (repo_db_has('ha_media')) {
        return db_media_all();
    }
    return ha_merge_overrides(data('media'), admin_load('media'));
}

function repo_exercises(): array
{
    if (repo_db_has('ha_exercises')) {
        return db_exercises_all();
    }
    return ha_merge_overrides(data('exercises'), admin_load('exercises'), 'id');
}

function repo_tips(): array
{
    if (repo_db_has('ha_tips')) {
        return db_tips_all();
    }
    return ha_merge_overrides(data('tips'), admin_load('tips'), 'id');
}

function repo_research(): array
{
    if (repo_db_has('ha_research')) {
        return db_research_all();
    }
    return ha_merge_overrides(data('research'), admin_load('research'));
}

/* ------------------------------------------------------------------ */
/*  نوشتن — dual-write                                                */
/* ------------------------------------------------------------------ */

/**
 * ذخیرهٔ آرایهٔ کامل در JSON + در صورت امکان همگام‌سازی تک‌آیتم به DB.
 * برای handlerهایی که کل لیست را می‌نویسند.
 */
function repo_store_list(string $name, array $items): bool
{
    $ok = admin_store($name, $items);
    return $ok;
}

function repo_write_ok(bool $dbOk, bool $jsonOk): bool
{
    if (db_ready()) {
        return $dbOk; // DB منبع حقیقت Production
    }
    return $jsonOk;
}

function repo_save_course(array $course, string $origSlug = ''): bool
{
    $course['_original_slug'] = $origSlug;
    $dbOk = true;
    if (db_ready()) {
        $dbOk = db_course_save_full($course);
    }
    // آینه JSON
    $courses = admin_load('courses');
    $slug = slugify((string) ($course['slug'] ?? ''));
    $orig = slugify($origSlug !== '' ? $origSlug : $slug);
    $found = false;
    $mirror = $course;
    unset($mirror['_original_slug'], $mirror['_db'], $mirror['_id']);
    foreach ($courses as $i => $c) {
        $cs = slugify((string) ($c['slug'] ?? ''));
        if (($orig !== '' && $cs === $orig) || $cs === $slug) {
            $courses[$i] = $mirror;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $courses[] = $mirror;
    }
    $jsonOk = admin_store('courses', $courses);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_delete_course(string $slug): bool
{
    $slug = slugify($slug);
    $dbOk = !db_ready() || db_course_delete($slug);
    $courses = admin_load('courses');
    $courses = array_values(array_filter($courses, static function ($c) use ($slug) {
        return slugify((string) ($c['slug'] ?? '')) !== $slug;
    }));
    $jsonOk = admin_store('courses', $courses);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_save_article(array $item, string $orig = ''): bool
{
    $dbOk = !db_ready() || db_article_upsert($item, $orig);
    $jsonOk = repo_mirror_by_slug('articles', $item, $orig);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_delete_article(string $slug): bool
{
    $slug = slugify($slug);
    $dbOk = !db_ready() || db_article_delete($slug);
    $jsonOk = repo_mirror_delete('articles', 'slug', $slug);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_save_book(array $item, string $orig = ''): bool
{
    // map cover/image
    if (empty($item['cover']) && !empty($item['image'])) {
        $item['cover'] = $item['image'];
    }
    $dbOk = !db_ready() || db_book_upsert($item, $orig);
    $jsonOk = repo_mirror_by_slug('books', $item, $orig);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_delete_book(string $slug): bool
{
    $slug = slugify($slug);
    $dbOk = !db_ready() || db_book_delete($slug);
    $jsonOk = repo_mirror_delete('books', 'slug', $slug);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_save_media(array $item, string $orig = ''): bool
{
    $dbOk = !db_ready() || db_media_upsert($item, $orig);
    // media list dual
    $items = admin_load('media');
    $slug = slugify((string) ($item['slug'] ?? ''));
    $orig = slugify($orig !== '' ? $orig : $slug);
    $type = (string) ($item['type'] ?? 'video');
    $found = false;
    $mirror = $item;
    unset($mirror['_db'], $mirror['_id']);
    foreach ($items as $i => $m) {
        $ms = slugify((string) ($m['slug'] ?? ''));
        $same = ($orig !== '' && $ms === $orig) || ($ms === $slug && ($m['type'] ?? '') === $type);
        if ($same) {
            $items[$i] = $mirror;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $items[] = $mirror;
    }
    $jsonOk = admin_store('media', $items);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_delete_media(string $type, string $slug): bool
{
    $type = $type === 'audio' ? 'audio' : 'video';
    $slug = slugify($slug);
    $dbOk = !db_ready() || db_media_delete($type, $slug);
    $items = admin_load('media');
    $items = array_values(array_filter($items, static function ($m) use ($type, $slug) {
        return !(($m['type'] ?? '') === $type && slugify((string) ($m['slug'] ?? '')) === $slug);
    }));
    $jsonOk = admin_store('media', $items);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_save_exercise(array $item, string $orig = ''): bool
{
    $dbOk = !db_ready() || db_exercise_upsert($item, $orig);
    $jsonOk = repo_mirror_by_key('exercises', 'id', $item, $orig);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_delete_exercise(string $key): bool
{
    $key = slugify($key);
    $dbOk = !db_ready() || db_exercise_delete($key);
    $jsonOk = repo_mirror_delete('exercises', 'id', $key);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_save_tip(array $item, string $orig = ''): bool
{
    $dbOk = !db_ready() || db_tip_upsert($item, $orig);
    $jsonOk = repo_mirror_by_key('tips', 'id', $item, $orig);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_delete_tip(string $key): bool
{
    $key = slugify($key);
    $dbOk = !db_ready() || db_tip_delete($key);
    $jsonOk = repo_mirror_delete('tips', 'id', $key);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_save_research(array $item, string $orig = ''): bool
{
    $dbOk = !db_ready() || db_research_upsert($item, $orig);
    $jsonOk = repo_mirror_by_slug('research', $item, $orig);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_delete_research(string $slug): bool
{
    $slug = slugify($slug);
    $dbOk = !db_ready() || db_research_delete($slug);
    $jsonOk = repo_mirror_delete('research', 'slug', $slug);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_save_category(array $item, string $orig = ''): bool
{
    $dbOk = !db_ready() || db_category_upsert($item);
    $jsonOk = repo_mirror_by_slug('categories', $item, $orig);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_delete_category(string $slug): bool
{
    $slug = slugify($slug);
    $dbOk = !db_ready() || db_category_delete($slug);
    $jsonOk = repo_mirror_delete('categories', 'slug', $slug);
    return repo_write_ok($dbOk, $jsonOk);
}

function repo_set_status(string $type, string $key, string $status): bool
{
    $map = [
        'course'   => ['ha_courses', 'slug', 'courses', 'slug'],
        'article'  => ['ha_articles', 'slug', 'articles', 'slug'],
        'book'     => ['ha_books', 'slug', 'books', 'slug'],
        'research' => ['ha_research', 'slug', 'research', 'slug'],
        'exercise' => ['ha_exercises', 'ex_key', 'exercises', 'id'],
        'tip'      => ['ha_tips', 'tip_key', 'tips', 'id'],
        'category' => ['ha_categories', 'slug', 'categories', 'slug'],
        'video'    => ['ha_media', 'slug', 'media', 'slug'],
        'audio'    => ['ha_media', 'slug', 'media', 'slug'],
    ];
    if (!isset($map[$type])) {
        return false;
    }
    [$table, $col, $store, $idKey] = $map[$type];
    $key = slugify($key);
    $status = db_status($status, 'draft');
    $dbOk = true;
    if (db_ready()) {
        if ($type === 'video' || $type === 'audio') {
            $dbOk = db_exec(
                'UPDATE ha_media SET status = ?, updated_at = ? WHERE type = ? AND slug = ?',
                [$status, db_now(), $type, $key]
            );
        } else {
            $dbOk = db_set_status($table, $col, $key, $status);
        }
    }
    // JSON mirror
    $items = admin_load($store);
    $found = false;
    $mediaType = ($type === 'video' || $type === 'audio') ? $type : null;
    foreach ($items as $i => $item) {
        if (!is_array($item)) {
            continue;
        }
        if (slugify((string) ($item[$idKey] ?? '')) !== $key) {
            continue;
        }
        if ($mediaType !== null && (string) ($item['type'] ?? '') !== $mediaType) {
            continue;
        }
        $items[$i]['status'] = $status;
        unset($items[$i]['hidden']);
        $found = true;
        break;
    }
    if (!$found) {
        // pull from current public list
        $sourceFn = [
            'course' => 'courses_all', 'article' => 'articles_all', 'book' => 'books_all',
            'research' => 'research_all', 'exercise' => 'exercises_all', 'tip' => 'tips_all',
            'category' => 'categories', 'video' => 'media_all', 'audio' => 'media_all',
        ][$type] ?? null;
        if ($sourceFn && function_exists($sourceFn)) {
            foreach ($sourceFn() as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (slugify((string) ($item[$idKey] ?? '')) !== $key) {
                    continue;
                }
                if ($mediaType && (string) ($item['type'] ?? '') !== $mediaType) {
                    continue;
                }
                $item['status'] = $status;
                unset($item['hidden'], $item['_db'], $item['_id']);
                $items[] = $item;
                $found = true;
                break;
            }
        }
    }
    $jsonOk = admin_store($store, $items);
    return repo_write_ok($dbOk, $jsonOk);
}

/* ------------------------------------------------------------------ */
/*  JSON mirror helpers                                               */
/* ------------------------------------------------------------------ */

function repo_mirror_by_slug(string $store, array $item, string $orig = ''): bool
{
    $items = admin_load($store);
    $slug = slugify((string) ($item['slug'] ?? ''));
    $orig = slugify($orig !== '' ? $orig : $slug);
    $mirror = $item;
    unset($mirror['_db'], $mirror['_id']);
    $found = false;
    foreach ($items as $i => $row) {
        $rs = slugify((string) ($row['slug'] ?? ''));
        if (($orig !== '' && $rs === $orig) || $rs === $slug) {
            $items[$i] = $mirror;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $items[] = $mirror;
    }
    return admin_store($store, $items);
}

function repo_mirror_by_key(string $store, string $keyName, array $item, string $orig = ''): bool
{
    $items = admin_load($store);
    $key = slugify((string) ($item[$keyName] ?? ''));
    $orig = slugify($orig !== '' ? $orig : $key);
    $mirror = $item;
    unset($mirror['_db'], $mirror['_id']);
    $found = false;
    foreach ($items as $i => $row) {
        $rk = slugify((string) ($row[$keyName] ?? ''));
        if (($orig !== '' && $rk === $orig) || $rk === $key) {
            $items[$i] = $mirror;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $items[] = $mirror;
    }
    return admin_store($store, $items);
}

function repo_mirror_delete(string $store, string $keyName, string $key): bool
{
    $key = slugify($key);
    $items = admin_load($store);
    $items = array_values(array_filter($items, static function ($row) use ($keyName, $key) {
        return slugify((string) ($row[$keyName] ?? '')) !== $key;
    }));
    return admin_store($store, $items);
}

/* ------------------------------------------------------------------ */
/*  Seed                                                              */
/* ------------------------------------------------------------------ */

/**
 * Seed محتوای واقعی از data/*.php — بدون fake media.
 * @return array{ok:bool, counts:array<string,int>, error:?string}
 */
function repo_seed(bool $force = false): array
{
    if (!db_ready()) {
        return ['ok' => false, 'counts' => [], 'error' => 'db_not_ready'];
    }
    $counts = [];
    $now = db_now();

    // Categories
    if ($force || !repo_db_has('ha_categories')) {
        $n = 0;
        foreach (data('categories') as $i => $c) {
            if (!is_array($c)) {
                continue;
            }
            $c['order'] = $i;
            $c['status'] = 'published';
            if (db_category_upsert($c)) {
                $n++;
            }
        }
        $counts['categories'] = $n;
    }

    // Courses + stages + lessons
    if ($force || !repo_db_has('ha_courses')) {
        $n = 0;
        $raw = data('course');
        $list = [];
        if (isset($raw['courses']) && is_array($raw['courses'])) {
            $list = $raw['courses'];
        }
        foreach ($list as $i => $course) {
            if (!is_array($course)) {
                continue;
            }
            $course['status'] = 'published';
            $course['order'] = $i;
            $course['_original_slug'] = '';
            if (db_course_save_full($course)) {
                $n++;
            }
        }
        $counts['courses'] = $n;
    }

    // Articles
    if ($force || !repo_db_has('ha_articles')) {
        $n = 0;
        foreach (data('articles') as $i => $a) {
            if (!is_array($a)) {
                continue;
            }
            $a['status'] = $a['status'] ?? 'published';
            $a['order'] = $i;
            if (db_article_upsert($a)) {
                $n++;
            }
        }
        $counts['articles'] = $n;
    }

    // Books
    if ($force || !repo_db_has('ha_books')) {
        $n = 0;
        foreach (data('books') as $i => $b) {
            if (!is_array($b)) {
                continue;
            }
            $b['status'] = $b['status'] ?? 'published';
            $b['order'] = $i;
            if (db_book_upsert($b)) {
                $n++;
            }
        }
        $counts['books'] = $n;
    }

    // Exercises
    if ($force || !repo_db_has('ha_exercises')) {
        $n = 0;
        foreach (data('exercises') as $i => $ex) {
            if (!is_array($ex)) {
                continue;
            }
            $ex['status'] = $ex['status'] ?? 'published';
            $ex['order'] = $i;
            if (db_exercise_upsert($ex)) {
                $n++;
            }
        }
        $counts['exercises'] = $n;
    }

    // Tips
    if ($force || !repo_db_has('ha_tips')) {
        $n = 0;
        foreach (data('tips') as $i => $t) {
            if (!is_array($t)) {
                continue;
            }
            $t['status'] = 'published';
            $t['order'] = $i;
            if (db_tip_upsert($t)) {
                $n++;
            }
        }
        $counts['tips'] = $n;
    }

    // Research
    if ($force || !repo_db_has('ha_research')) {
        $n = 0;
        foreach (data('research') as $i => $r) {
            if (!is_array($r)) {
                continue;
            }
            $r['status'] = $r['status'] ?? 'published';
            $r['order'] = $i;
            if (db_research_upsert($r)) {
                $n++;
            }
        }
        $counts['research'] = $n;
    }

    // Media: فقط playable — seed خالی = بدون fake
    if ($force || !repo_db_has('ha_media')) {
        $n = 0;
        foreach (data('media') as $m) {
            if (!is_array($m)) {
                continue;
            }
            $url = trim((string) ($m['url'] ?? ''));
            if ($url === '') {
                continue; // fake skip
            }
            $m['status'] = $m['status'] ?? 'published';
            if (db_media_upsert($m)) {
                $n++;
            }
        }
        // admin JSON media
        foreach (admin_load('media') as $m) {
            if (!is_array($m) || trim((string) ($m['url'] ?? '')) === '') {
                continue;
            }
            if (db_media_upsert($m)) {
                $n++;
            }
        }
        $counts['media'] = $n;
    }

    // Users: migrate from JSON if DB empty
    if ($force || !repo_db_has('ha_users')) {
        $n = 0;
        $file = storage_dir() . '/users.json';
        $users = [];
        if (is_file($file)) {
            $raw = @file_get_contents($file);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                $users = $decoded;
            }
        }
        foreach ($users as $u) {
            if (!is_array($u) || empty($u['email']) || empty($u['pass_hash'])) {
                continue;
            }
            $id = (string) ($u['id'] ?? bin2hex(random_bytes(16)));
            $ok = db_exec(
                'INSERT IGNORE INTO ha_users (id, name, email, pass_hash, role, last_login, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)',
                [
                    $id,
                    (string) ($u['name'] ?? ''),
                    strtolower(trim((string) $u['email'])),
                    (string) $u['pass_hash'],
                    (($u['role'] ?? '') === 'admin' || $n === 0) ? 'admin' : 'user',
                    !empty($u['last_login']) ? date('Y-m-d H:i:s', strtotime((string) $u['last_login']) ?: time()) : null,
                    !empty($u['created_at']) ? date('Y-m-d H:i:s', strtotime((string) $u['created_at']) ?: time()) : $now,
                    $now,
                ]
            );
            if ($ok) {
                $n++;
            }
        }
        // Seed admin if still empty and credentials provided
        if ($n === 0 && defined('HA_SEED_ADMIN_EMAIL') && HA_SEED_ADMIN_EMAIL !== ''
            && defined('HA_SEED_ADMIN_PASSWORD') && HA_SEED_ADMIN_PASSWORD !== '') {
            $res = db_user_create(
                defined('HA_SEED_ADMIN_NAME') ? (string) HA_SEED_ADMIN_NAME : 'مدیر',
                (string) HA_SEED_ADMIN_EMAIL,
                (string) HA_SEED_ADMIN_PASSWORD,
                'admin'
            );
            if (!empty($res['ok'])) {
                $n = 1;
            }
        }
        $counts['users'] = $n;
    }

    db_exec(
        "INSERT INTO ha_schema_meta (meta_key, meta_value) VALUES ('seeded_at', ?)
         ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)",
        [$now]
    );

    return ['ok' => true, 'counts' => $counts, 'error' => null];
}

/** Auto-seed once if enabled and courses empty. */
function repo_maybe_auto_seed(): void
{
    static $tried = false;
    if ($tried) {
        return;
    }
    $tried = true;
    if (!defined('HA_DB_AUTO_SEED') || !HA_DB_AUTO_SEED) {
        return;
    }
    if (!db_ready()) {
        return;
    }
    if (repo_db_has('ha_courses')) {
        return;
    }
    repo_seed(false);
}
