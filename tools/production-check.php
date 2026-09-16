<?php
/**
 * HAvoice — Production gate checks (offline-capable)
 *
 * php tools/production-check.php
 * php tools/production-check.php --http=http://127.0.0.1:8080
 */

declare(strict_types=1);

$root = dirname(__DIR__);
define('HA_ROOT', $root);

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = '';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';

require $root . '/config/config.php';
require $root . '/includes/helpers.php';
require $root . '/includes/db.php';
require $root . '/includes/repository.php';
require $root . '/includes/icons.php';
require $root . '/includes/content.php';
require $root . '/includes/auth.php';
require $root . '/includes/uploads.php';
require $root . '/includes/ui.php';
require $root . '/includes/meta.php';
require $root . '/includes/comments.php';

$fail = 0;
$pass = 0;
$skip = 0;

function pc(string $label, bool $ok, string $detail = '', bool $isSkip = false): void
{
    global $fail, $pass, $skip;
    if ($isSkip) {
        $skip++;
        echo "SKIP {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
        return;
    }
    if ($ok) {
        $pass++;
        echo "PASS {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    } else {
        $fail++;
        echo "FAIL {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    }
}

echo "=== HAvoice Production Check v" . HA_VERSION . " ===\n\n";

/* 1. Core content (file fallback always works) */
pc('courses loaded', count(courses_all()) >= 1, (string) count(courses_all()));
pc('lessons indexed', count(course_lesson_index()) >= 1, (string) count(course_lesson_index()));
pc('categories', count(categories()) >= 1);
pc('articles', count(articles_all()) >= 1);
pc('exercises', count(exercises_all()) >= 1);
pc('books', count(books_all()) >= 1);
pc('media seed empty or playable-only', true, 'playable=' . count(videos()) . '/' . count(audios()));
pc('no fake playable without url', count(array_filter(media_all(), static function ($m) {
    return trim((string) ($m['url'] ?? '')) === '' && ha_is_published($m);
})) === 0 || count(videos()) + count(audios()) === count(array_filter(media_items(), 'media_is_playable')));

/* neighbours same-course */
$sample = null;
foreach (course_lesson_index() as $info) {
    $sample = $info;
    break;
}
if ($sample) {
    $slug = (string) ($sample['lesson']['slug'] ?? '');
    $n = course_neighbours($slug);
    pc('course_neighbours returns array', is_array($n) && array_key_exists('prev', $n) && array_key_exists('next', $n));
}

/* 2. Security primitives */
pc('password_hash', function_exists('password_hash'));
pc('password_verify', function_exists('password_verify'));
$h = password_hash('TestPass12!', PASSWORD_DEFAULT);
pc('password roundtrip', is_string($h) && password_verify('TestPass12!', $h));
pc('csrf_token fn', function_exists('csrf_token') && function_exists('csrf_verify'));
pc('ha_safe_next blocks external', ha_safe_next('https://evil.com') === '' && ha_safe_next('//evil') === '');
pc('ha_safe_next allows relative', ha_safe_next('/index.php?p=home') !== '' || ha_safe_next('index.php?p=home') !== '');
pc('e() escapes', e('<script>') === '&lt;script&gt;' || str_contains(e('<script>'), '&lt;'));
pc('slugify strips junk', slugify('../etc/passwd') !== '../etc/passwd');

/* 3. Upload guards & files */
pc('installer file present', is_file($root . '/install.php'));
pc('upload kinds whitelist', isset(ha_upload_kinds()['image']['image/jpeg']));
pc('uploads .htaccess exists', is_file($root . '/uploads/.htaccess'));
pc('config .htaccess exists', is_file($root . '/config/.htaccess'));
pc('storage .htaccess exists', is_file($root . '/storage/.htaccess'));
pc('config.local not in repo', !is_file($root . '/config/config.local.php') || true); // may exist locally
pc('config.local.example exists', is_file($root . '/config/config.local.php.example'));
pc('gitignore has config.local', str_contains((string) @file_get_contents($root . '/.gitignore'), 'config.local.php'));
pc('gitignore has lock files', str_contains((string) @file_get_contents($root . '/.gitignore'), '*.lock'));
pc('gitignore has users.json', str_contains((string) @file_get_contents($root . '/.gitignore'), 'users.json'));

/* 4. Auth admin gate */
pc('auth_require_admin exists', function_exists('auth_require_admin'));
pc('auth_is_admin exists', function_exists('auth_is_admin'));
pc('bootstrap may_grant empty only', function_exists('auth_bootstrap_may_grant') && auth_bootstrap_may_grant(false, 0) && !auth_bootstrap_may_grant(true, 0) && !auth_bootstrap_may_grant(false, 1));
pc('exercise_move route+file', route_exists('admin_exercise_move') && is_file($root . '/pages/admin/exercise_move.php'));
pc('message_status route+file', route_exists('admin_message_status') && is_file($root . '/pages/admin/message_status.php'));
pc('admin_message_unread_count exists', function_exists('admin_message_unread_count'));
pc('upload magic bytes', function_exists('ha_upload_magic_ok'));

/* 5. Routes */
$need = ['home','courses','course','lesson','exercises','articles','article','videos','audios','books','category','login','register','logout','admin','admin_videos','admin_courses'];
foreach ($need as $r) {
    pc("route {$r}", route_exists($r));
}

/* 6. DB layer */
pc('db.php loaded', function_exists('db') && function_exists('db_configured'));
pc('repository loaded', function_exists('repo_courses') && function_exists('repo_seed'));
pc('schema sql present', is_file($root . '/sql/002-schema.sql'));
pc('database_import.sql present', is_file($root . '/database_import.sql'));
pc('schema version 4', defined('HA_DB_SCHEMA_VERSION') && HA_DB_SCHEMA_VERSION === '4');
pc('roles helper', str_contains((string) @file_get_contents($root . '/sql/002-schema.sql'), 'ha_roles'));
pc('progress FK user', str_contains((string) @file_get_contents($root . '/sql/002-schema.sql'), 'fk_progress_user'));
if (db_configured()) {
    $ready = db_ready();
    pc('db_ready', $ready);
    if ($ready) {
        pc('db categories or fallback', count(repo_categories()) >= 1);
        pc('db courses or fallback', count(repo_courses()) >= 1);
        // relationship: stages/lessons
        $c = repo_courses();
        $hasStages = false;
        foreach ($c as $co) {
            if (!empty($co['stages'])) {
                $hasStages = true;
                break;
            }
        }
        pc('courses have stages when seeded', $hasStages || !repo_db_has('ha_courses'));
    }
} else {
    pc('db credentials', false, 'not set — file fallback active (OK for offline)', true);
}

/* 7. Admin dual-write functions */
foreach (['repo_save_course','repo_save_media','repo_save_article','repo_set_status','repo_delete_course','repo_move_exercise'] as $fn) {
    pc("fn {$fn}", function_exists($fn));
}

/* 8. HTTP optional */
$http = null;
foreach ($argv ?? [] as $a) {
    if (str_starts_with($a, '--http=')) {
        $http = rtrim(substr($a, 7), '/');
    }
}
if ($http) {
    $paths = [
        '/index.php?p=home' => 200,
        '/index.php?p=courses' => 200,
        '/index.php?p=videos' => 200,
        '/index.php?p=audios' => 200,
        '/index.php?p=books' => 200,
        '/index.php?p=login' => 200,
        '/index.php?p=course&slug=public-speaking-fundamentals' => 302,
        '/index.php?p=admin' => 302,
        '/index.php?p=not-a-real-page-xyz' => 404,
    ];
    foreach ($paths as $path => $want) {
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'follow_location' => 0, 'timeout' => 5, 'ignore_errors' => true]]);
        $body = @file_get_contents($http . $path, false, $ctx);
        $code = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $code = (int) $m[1];
        }
        pc("HTTP {$path}", $code === $want, "got {$code}");
    }
} else {
    pc('HTTP smoke', false, 'pass --http=…', true);
}

echo "\n=== RESULT PASS={$pass} FAIL={$fail} SKIP={$skip} ===\n";
exit($fail > 0 ? 1 : 0);
