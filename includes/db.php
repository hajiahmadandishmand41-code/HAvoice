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

/** نسخهٔ schema که ensure_schema می‌سازد.
 *  v2: جداول اصلی
 *  v3: exercises→lessons FK (CASCADE) + comments 'hidden' + messages read_at
 */
define('HA_DB_SCHEMA_VERSION', '3');

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

        /*
         * ترتیب اجرا:
         *   ۱) sql/002-schema.sql — schema کاملِ جاری (CREATE IF NOT EXISTS؛
         *      برای نصبِ تازه همه‌چیز را می‌سازد و برای ارتقا هیچ جدولی را
         *      خراب نمی‌کند چون ALTER ندارد).
         *   ۲) sql/003-upgrade.sql — مهاجرتِ v2→v3 با محافظِ idempotent؛
         *      روی نصبِ تازه هم اجرا می‌شود ولی همه‌ی تغییرها «فقط اگر نباشد»
         *      هستند پس عملیاتِ بدونِ اثر است.
         */
        foreach (['sql/002-schema.sql', 'sql/003-upgrade.sql'] as $rel) {
            $sqlFile = HA_ROOT . '/' . $rel;
            if (!is_file($sqlFile)) {
                if ($rel === 'sql/002-schema.sql') {
                    return false;
                }
                continue;
            }
            $sql = (string) file_get_contents($sqlFile);
            // حذف کامنت‌های -- خطی برای اجرای ساده
            $sql = preg_replace('/^\\s*--.*$/m', '', $sql) ?? $sql;
            $parts = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($parts as $stmt) {
                if ($stmt === '') {
                    continue;
                }
                $pdo->exec($stmt);
            }
        }

        $pdo->prepare("INSERT INTO ha_schema_meta (meta_key, meta_value) VALUES ('version', ?)
                       ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)")
            ->execute([HA_DB_SCHEMA_VERSION]);

        $done = true;
        return true;
    } catch (Throwable $e) {
        if (defined('HA_DEBUG') && HA_DEBUG) {
            error_log('HAvoice schema: ' . $e->getMessage());
        }
        return false;
    }
}

/* ------------------------------------------------------------------ */
/*  Helpers                                                           */
/* ------------------------------------------------------------------ */

function db_now(): string
{
    return date('Y-m-d H:i:s');
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
 * ذخیرهٔ کامل یک دوره + مراحل + درس‌ها — «به‌روزرسانیِ درجا» (update-in-place).
 *
 * چرا دیگر «حذفِ کامل و درجِ دوباره» نمی‌کنیم؟
 * با FK جدیدِ ha_exercises.lesson_id → ha_lessons(id) ON DELETE CASCADE،
 * حذفِ ردیفِ درس، تمرین‌های متصل را هم حذف می‌کند. نسخه‌ی قدیمی در هر
 * ذخیره‌ی دوره همه‌ی مرحله‌ها را می‌کُشت و دوباره می‌ساخت؛ یعنی هر ویرایشِ
 * کوچکِ دوره (مثلاً تغییرِ عنوان) بی‌صدا همه‌ی تمرین‌ها را می‌بُرد!
 *
 * راهبرد: تطبیق روی slug (درس‌ها — یکتا در کل سایت) و stage_key (در هر
 * دوره). آنچه در ساختارِ ارسالی نیست حذف می‌شود (همان رفتارِ CASCADE که
 * معماری تعریف می‌کند: تمرینِ بدونِ درس اجازه ندارد زنده بماند).
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

    /* مرتب‌سازیِ پایدار برای داده‌هایی که sort_order ندارند */
    $sortOf = static function (array $row, int $i, string $key = 'sort_order'): int {
        return (int) ($row[$key] ?? $row['order'] ?? $i);
    };

    try {
        $pdo->beginTransaction();

        $existing = db_one('SELECT id FROM ha_courses WHERE slug = ? LIMIT 1', [$orig !== '' ? $orig : $slug]);
        if (!$existing && $orig !== $slug) {
            $existing = db_one('SELECT id FROM ha_courses WHERE slug = ? LIMIT 1', [$slug]);
        }

        $fields = [
            $slug,
            (string) ($course['title'] ?? ''),
            (string) ($course['category'] ?? ''),
            (string) ($course['level'] ?? ''),
            (string) ($course['excerpt'] ?? ''),
            (string) ($course['intro'] ?? ''),
            db_json_encode($course['how_to'] ?? []),
            db_json_encode($course['project'] ?? []),
            (string) ($course['prereq'] ?? ''),
            !empty($course['featured']) ? 1 : 0,
            db_status((string) ($course['status'] ?? 'draft'), 'draft'),
            (int) ($course['order'] ?? $course['sort_order'] ?? 0),
            $now,
        ];

        if ($existing) {
            $cid = (int) $existing['id'];
            db_exec(
                'UPDATE ha_courses SET slug=?, title=?, category_slug=?, level=?, excerpt=?, intro=?, how_to_json=?, project_json=?, prereq=?, featured=?, status=?, sort_order=?, updated_at=? WHERE id=?',
                array_merge($fields, [$cid])
            );
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

        /* --- مرحله‌ها: تطبیق روی stage_key --- */
        $existingStages = db_all('SELECT id, stage_key FROM ha_stages WHERE course_id = ?', [$cid]);
        $stageIds = [];
        $keepStageIds = [];

        foreach ($existingStages as $row) {
            $stageIds[(string) $row['stage_key']] = (int) $row['id'];
        }

        foreach (array_values((array) ($course['stages'] ?? [])) as $si => $stage) {
            if (!is_array($stage)) {
                continue;
            }
            $stageKey = trim((string) ($stage['id'] ?? $stage['key'] ?? ''));
            if ($stageKey === '') {
                $stageKey = 'stage-' . ($si + 1);
            }
            /* دفاع در برابرِ کلیدِ تکراری در همان دوره و همان ذخیره
               (UNIQUE روی course_id+stage_key). */
            $baseKey = $stageKey;
            $kN = 2;
            while (isset($stageIds[$stageKey]) && in_array($stageIds[$stageKey], $keepStageIds, true)) {
                $stageKey = $baseKey . '-' . $kN;
                $kN++;
            }
            $stageVals = [
                (string) ($stage['label'] ?? ''),
                (string) ($stage['title'] ?? ''),
                (string) ($stage['summary'] ?? ''),
                (string) ($stage['outcome'] ?? ''),
                (string) ($stage['duration'] ?? ''),
                db_json_encode($stage['assessment'] ?? []),
                $sortOf($stage, $si),
                db_status((string) ($stage['status'] ?? 'published')),
                $now,
            ];
            $sid = 0;
            if (isset($stageIds[$stageKey])) {
                $sid = $stageIds[$stageKey];
                db_exec(
                    'UPDATE ha_stages SET label=?, title=?, summary=?, outcome=?, duration=?, assessment_json=?, sort_order=?, status=?, updated_at=? WHERE id=?',
                    array_merge($stageVals, [$sid])
                );
            } else {
                db_exec(
                    'INSERT INTO ha_stages (course_id, stage_key, label, title, summary, outcome, duration, assessment_json, sort_order, status, created_at, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                    array_merge([$cid, $stageKey], $stageVals)
                );
                $sid = db_last_id();
                $stageIds[$stageKey] = $sid;
            }
            if ($sid <= 0) {
                continue;
            }
            $keepStageIds[] = $sid;

            /* --- درس‌ها: تطبیق روی slug --- */
            $existingLessons = db_all('SELECT id, slug FROM ha_lessons WHERE stage_id = ?', [$sid]);
            $lessonIds = [];
            foreach ($existingLessons as $row) {
                $lessonIds[(string) $row['slug']] = (int) $row['id'];
            }
            $keepLessonIds = [];

            foreach (array_values((array) ($stage['lessons'] ?? [])) as $li => $lesson) {
                if (!is_array($lesson)) {
                    continue;
                }
                $lSlug = slugify((string) ($lesson['slug'] ?? ''));
                if ($lSlug === '') {
                    $lSlug = slugify((string) ($lesson['title'] ?? ''));
                }
                if ($lSlug === '') {
                    $lSlug = 'lesson-' . $sid . '-' . ($li + 1);
                }
                /* slug باید در کلِ جدول یکتا باشد؛ اگر به درسِ دیگری تعلق
                   دارد (انتقال بین مرحله‌ها یا دوره‌ها)، ردیفِ همان درس را
                   پیدا و به این مرحله/دوره منتقل می‌کنیم (نه حذف/ساخت). */
                $lVals = [
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
                    (int) ($lesson['order'] ?? $li),
                    db_status((string) ($lesson['status'] ?? 'published')),
                    $now,
                ];
                $lid = 0;
                if (isset($lessonIds[$lSlug])) {
                    $lid = $lessonIds[$lSlug];
                    db_exec(
                        'UPDATE ha_lessons SET course_id=?, stage_id=?, slug=?, title=?, minutes=?, goal=?, blocks_json=?, drill_json=?, prerequisite=?, refs_json=?, sort_order=?, status=?, updated_at=? WHERE id=?',
                        array_merge($lVals, [$lid])
                    );
                } else {
                    $other = db_one('SELECT id FROM ha_lessons WHERE slug = ? LIMIT 1', [$lSlug]);
                    if ($other) {
                        /* جابه‌جاییِ درسِ موجود به این دوره/مرحله (حفظِ id ⇒ تمرین‌ها زنده می‌مانند) */
                        $lid = (int) $other['id'];
                        db_exec(
                            'UPDATE ha_lessons SET course_id=?, stage_id=?, slug=?, title=?, minutes=?, goal=?, blocks_json=?, drill_json=?, prerequisite=?, refs_json=?, sort_order=?, status=?, updated_at=? WHERE id=?',
                            array_merge($lVals, [$lid])
                        );
                    } else {
                        db_exec(
                            'INSERT INTO ha_lessons (course_id, stage_id, slug, title, minutes, goal, blocks_json, drill_json, prerequisite, refs_json, sort_order, status, created_at, updated_at)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                            array_merge($lVals, [$now])
                        );
                        $lid = db_last_id();
                    }
                    $lessonIds[$lSlug] = $lid;
                }
                if ($lid > 0) {
                    $keepLessonIds[] = $lid;
                }
            }

            /* حذفِ درس‌های برداشته‌شده از این مرحله — CASCADE تمرین‌ها */
            if ($keepLessonIds === []) {
                db_exec('DELETE FROM ha_lessons WHERE stage_id = ?', [$sid]);
            } else {
                $ph = implode(',', array_fill(0, count($keepLessonIds), '?'));
                $st = $pdo->prepare("DELETE FROM ha_lessons WHERE stage_id = ? AND id NOT IN ({$ph})");
                $st->execute(array_merge([$sid], $keepLessonIds));
            }
        }

        /* حذفِ مرحله‌های برداشته‌شده — CASCADE به درس‌ها و از آنجا به تمرین‌ها */
        if ($keepStageIds === []) {
            db_exec('DELETE FROM ha_stages WHERE course_id = ?', [$cid]);
        } else {
            $ph = implode(',', array_fill(0, count($keepStageIds), '?'));
            $st = $pdo->prepare("DELETE FROM ha_stages WHERE course_id = ? AND id NOT IN ({$ph})");
            $st->execute(array_merge([$cid], $keepStageIds));
        }

        /* تمرین‌ها: lesson_id را (دوباره) از روی lesson_slug لینک کن تا با
           هر جابه‌جاییِ slug درس هماهنگ بماند. */
        db_exercise_relink();

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

/**
 * همه‌ی تمرین‌ها را از روی lesson_slug به ردیفِ واقعیِ درس لینک می‌کند.
 * بدونِ تطابق ⇒ NULL (ردیفِ قدیمیِ مستقل — اگر دوره/درس حذف شود مسیرِ
 * امن، حذفِ مستقیم با course_slug نال است؛ در db_course_delete انجام می‌شود).
 */
function db_exercise_relink(): void
{
    db_exec('UPDATE ha_exercises e LEFT JOIN ha_lessons l ON l.slug = e.lesson_slug SET e.lesson_id = l.id');
}

/**
 * حذفِ دوره — رفتارِ آبشاریِ تعریف‌شده:
 *   ha_courses ─CASCADE→ ha_stages ─CASCADE→ ha_lessons ─CASCADE→ ha_exercises
 * علاوه بر آن، تمرین‌های slug-محورِ کهنسالِ بدونِ lesson_id هم پاک می‌شوند
 * (course_slug آن‌ها به همین دوره اشاره می‌کرد) تا تمرین orphan باقی نماند.
 */
function db_course_delete(string $slug): bool
{
    $slug = slugify($slug);
    if ($slug === '') {
        return false;
    }
    db_exec('DELETE FROM ha_exercises WHERE course_slug = ? AND lesson_id IS NULL', [$slug]);
    return db_exec('DELETE FROM ha_courses WHERE slug = ?', [$slug]);
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
        return db_exec(
            'UPDATE ha_media SET type=?, slug=?, title=?, excerpt=?, url=?, thumbnail=?, category=?, field_slug=?, course_slug=?, lesson_slug=?, seconds=?, date_fa=?, featured=?, status=?, sort_order=?, updated_at=? WHERE id=?',
            array_merge($vals, [(int) $existing['id']])
        );
    }
    return db_exec(
        'INSERT INTO ha_media (type, slug, title, excerpt, url, thumbnail, category, field_slug, course_slug, lesson_slug, seconds, date_fa, featured, status, sort_order, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        array_merge($vals, [$now])
    );
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
            'lesson_id'  => $r['lesson_id'] !== null ? (int) $r['lesson_id'] : null,
            'course'     => (string) ($r['course_slug'] ?? ''),
            'field'      => (string) ($r['field_slug'] ?? ''),
            'featured'   => !empty($r['featured']),
            'status'     => (string) ($r['status'] ?? 'published'),
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
    /* اتصالِ واقعی به درس: lesson_id از روی نامک (FK ⇒ orphan ممنوع). */
    $lessonSlug = slugify((string) ($item['lesson'] ?? ''));
    $lessonId = null;
    if ($lessonSlug !== '') {
        $lr = db_one('SELECT id FROM ha_lessons WHERE slug = ? LIMIT 1', [$lessonSlug]);
        $lessonId = $lr ? (int) $lr['id'] : null;
    }
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
        $lessonId,
        $lessonSlug,
        slugify((string) ($item['course'] ?? '')),
        (string) ($item['field'] ?? ''),
        !empty($item['featured']) ? 1 : 0,
        db_status((string) ($item['status'] ?? 'published')),
        (int) ($item['order'] ?? 0),
        $now,
    ];
    if ($existing) {
        return db_exec(
            'UPDATE ha_exercises SET ex_key=?, title=?, level=?, focus=?, goal=?, tool=?, seconds=?, steps_json=?, topics_json=?, success_text=?, note_text=?, lesson_id=?, lesson_slug=?, course_slug=?, field_slug=?, featured=?, status=?, sort_order=?, updated_at=? WHERE id=?',
            array_merge($vals, [(int) $existing['id']])
        );
    }
    return db_exec(
        'INSERT INTO ha_exercises (ex_key, title, level, focus, goal, tool, seconds, steps_json, topics_json, success_text, note_text, lesson_id, lesson_slug, course_slug, field_slug, featured, status, sort_order, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
        array_merge($vals, [$now])
    );
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
    /*
     * Enforce در لایه‌ی دیتابیس: نقشِ admin فقط برای «اولین» حسابِ سیستم
     * مجاز است (Bootstrap). Registration هرگز نمی‌تواند با دست‌کاریِ
     * پارامترِ role، خود را مدیر کند — کاربر دوم و سوم همواره user‌اند.
     */
    if ($role === 'admin' && db_user_count() > 0) {
        $role = 'user';
    }
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
/*  پیام‌های تماس (Database-driven)                                    */
/* ------------------------------------------------------------------ */

/** ثبتِ پیامِ جدید. id رکورد یا 0. */
function db_message_add(array $m): int
{
    $ok = db_exec(
        'INSERT INTO ha_contact_messages (name, email, subject, message, ip, created_at) VALUES (?,?,?,?,?,?)',
        [
            mb_substr((string) ($m['name'] ?? ''), 0, 120, 'UTF-8'),
            mb_substr((string) ($m['email'] ?? ''), 0, 190, 'UTF-8'),
            mb_substr((string) ($m['subject'] ?? ''), 0, 200, 'UTF-8'),
            (string) ($m['message'] ?? ''),
            mb_substr((string) ($m['ip'] ?? ''), 0, 45, 'UTF-8'),
            db_now(),
        ]
    );
    return $ok ? db_last_id() : 0;
}

/** @return list<array<string,mixed>> — تازه‌ترین اول */
function db_messages_all(int $limit = 300): array
{
    $limit = max(1, min(1000, $limit));
    $pdo = db();
    if ($pdo === null) {
        return [];
    }
    try {
        $st = $pdo->prepare('SELECT * FROM ha_contact_messages ORDER BY created_at DESC, id DESC LIMIT ?');
        $st->bindValue(1, $limit, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll();
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $r) {
            $out[] = [
                'time'    => (string) ($r['created_at'] ?? ''),
                'subject' => (string) ($r['subject'] ?? ''),
                'name'    => (string) ($r['name'] ?? ''),
                'email'   => (string) ($r['email'] ?? ''),
                'message' => (string) ($r['message'] ?? ''),
                'ip'      => (string) ($r['ip'] ?? ''),
                'read_at' => $r['read_at'] ?? null,
                'source'  => 'db',
                'ref'     => 'db-' . (int) $r['id'],
                '_id'     => (int) $r['id'],
            ];
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

function db_message_find(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $r = db_one('SELECT * FROM ha_contact_messages WHERE id = ? LIMIT 1', [$id]);
    if (!$r) {
        return null;
    }
    return [
        'time'    => (string) ($r['created_at'] ?? ''),
        'subject' => (string) ($r['subject'] ?? ''),
        'name'    => (string) ($r['name'] ?? ''),
        'email'   => (string) ($r['email'] ?? ''),
        'message' => (string) ($r['message'] ?? ''),
        'ip'      => (string) ($r['ip'] ?? ''),
        'read_at' => $r['read_at'] ?? null,
        'source'  => 'db',
        'ref'     => 'db-' . (int) $r['id'],
        '_id'     => (int) $r['id'],
    ];
}

/** علامت «خوانده‌شده». */
function db_message_mark_read(int $id): bool
{
    return $id > 0 && db_exec(
        'UPDATE ha_contact_messages SET read_at = ? WHERE id = ? AND read_at IS NULL',
        [db_now(), $id]
    );
}

function db_message_delete(int $id): bool
{
    return $id > 0 && db_exec('DELETE FROM ha_contact_messages WHERE id = ?', [$id]);
}

function db_messages_unread_count(): int
{
    $r = db_one('SELECT COUNT(*) AS c FROM ha_contact_messages WHERE read_at IS NULL');
    return (int) ($r['c'] ?? 0);
}

/** تعدادِ کل پیام‌ها (برای داشبورد). */
function db_messages_count(): int
{
    $r = db_one('SELECT COUNT(*) AS c FROM ha_contact_messages');
    return (int) ($r['c'] ?? 0);
}

/* ------------------------------------------------------------------ */
/*  درس‌ها — خواندنِ تکی (مدیریت درس)                                  */
/* ------------------------------------------------------------------ */

/** فهرستِ صافِ درس‌ها (همه‌ی دوره‌ها) — برای انتخاب‌گرهای پنل. */
function db_lessons_flat(): array
{
    $rows = db_all('SELECT l.id, l.slug, l.title, l.status, l.course_id, l.stage_id, c.slug AS course_slug, c.title AS course_title
                    FROM ha_lessons l JOIN ha_courses c ON c.id = l.course_id
                    ORDER BY c.sort_order ASC, l.sort_order ASC, l.id ASC');
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'           => (int) $r['id'],
            'slug'         => (string) $r['slug'],
            'title'        => (string) $r['title'],
            'status'       => (string) ($r['status'] ?? 'published'),
            'course_slug'  => (string) ($r['course_slug'] ?? ''),
            'course_title' => (string) ($r['course_title'] ?? ''),
        ];
    }
    return $out;
}

/** تغییرِ وضعیتِ یک درس یا مرحله (داشبوردِ دوره). */
function db_lesson_set_status(string $slug, string $status): bool
{
    $slug = slugify($slug);
    $status = db_status($status, 'draft');
    return $slug !== '' && db_exec('UPDATE ha_lessons SET status = ?, updated_at = ? WHERE slug = ?', [$status, db_now(), $slug]);
}
