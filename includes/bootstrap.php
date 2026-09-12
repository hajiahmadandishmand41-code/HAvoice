<?php
/**
 * HAvoice — بوت‌استرپ
 *
 * جریان کار: config → helpers → content → ui → meta
 *          → route → session → security headers → POST handler
 *          → header.php → pages/*.php → footer.php
 *
 * همه‌ی include‌ها از فهرست سفیدِ routes() می‌آیند؛ هیچ مسیر کاربری
 * مستقیماً به require نمی‌رسد (LFI/path-traversal بسته است).
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

require HA_ROOT . '/config/config.php';
require HA_ROOT . '/includes/helpers.php';
require HA_ROOT . '/includes/icons.php';
require HA_ROOT . '/includes/content.php';
require HA_ROOT . '/includes/ui.php';
require HA_ROOT . '/includes/meta.php';

error_reporting(E_ALL);
ini_set('display_errors', HA_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

/* ------------------------------------------------------------------ */
/*  Routing                                                           */
/* ------------------------------------------------------------------ */

/** فهرست سفیدِ route‌ها؛ هرچه اینجا نباشد ⇒ ۴۰۴ */
function ha_known_routes(): array
{
    return ['home','courses','course','lesson','articles','article','videos','audios','books',
            'research','category','exercises','tips','about','contact','search','404'];
}

function ha_resolve_route(): array
{
    $route = '';
    $slug  = '';

    if (HA_PRETTY_URLS && isset($_GET['r']) && is_string($_GET['r'])) {
        $path  = trim((string) preg_replace('#[^A-Za-z0-9_/\-]#', '', $_GET['r']), '/');
        $parts = $path === '' ? [] : explode('/', $path);
        $first = $parts[0] ?? '';
        $second = $parts[1] ?? '';

        if ($first === 'articles' && $second !== '')      { $route = 'article';  $slug = slugify($second); }
        elseif ($first === 'lesson' && $second !== '')    { $route = 'lesson';   $slug = slugify($second); }
        elseif ($first === 'course' && $second !== '')    { $route = 'course';   $slug = slugify($second); }
        elseif ($first === 'category' && $second !== '')  { $route = 'category'; $slug = slugify($second); }
        elseif ($first === 'books' && $second !== '')     { $route = 'books';    $slug = slugify($second); }
        elseif ($first === 'research' && $second !== '')  { $route = 'research'; $slug = slugify($second); }
        elseif ($first === '' )                           { $route = 'home'; }
        elseif (route_exists($first))                     { $route = $first; }
        else                                              { $route = ''; } // ناشناخته ⇒ ۴۰۴ واقعی
    } else {
        $raw   = isset($_GET['p']) && is_string($_GET['p']) ? $_GET['p'] : '';
        $route = slugify($raw);
        if ($route === '') {
            $route = 'home';
        } elseif (!route_exists($route)) {
            $route = ''; // ناشناخته ⇒ ۴۰۴ واقعی، نه صفحه‌ی اصلی
        }
    }

    if ($slug === '' && isset($_GET['slug']) && is_string($_GET['slug'])) {
        $slug = slugify($_GET['slug']);
    }
    if ($slug !== '') {
        $_GET['slug'] = $slug;
    }

    return [$route, $slug];
}

[$route, $slug] = ha_resolve_route();

if ($route === '') {
    $route = '404';
    http_response_code(404);
}

/* ۴۰۴ برای slug نامعتبر — بدون soft-404 */
if ($route === 'article' && find_by_slug(all_articles_sorted(), $slug)[1] === null) {
    $route = '404'; http_response_code(404);
} elseif ($route === 'lesson' && course_find_lesson($slug) === null) {
    $route = '404'; http_response_code(404);
} elseif ($route === 'category' && $slug !== '' && find_category($slug) === null) {
    $route = '404'; http_response_code(404);
} elseif ($route === 'course' && $slug !== '' && find_course($slug) === null) {
    $route = '404'; http_response_code(404);
} elseif (($route === 'books' || $route === 'research') && $slug !== '') {
    $found = $route === 'books' ? find_book($slug) : find_research($slug);
    if ($found === null) { $route = '404'; http_response_code(404); }
}

$GLOBALS['HA_ROUTE'] = $route;
$GLOBALS['HA_SLUG']  = $slug;

/* ------------------------------------------------------------------ */
/*  انتقال به HTTPS (پیش از هر خروجی)                                  */
/* ------------------------------------------------------------------ */

if (HA_FORCE_HTTPS && !ha_is_https() && !headers_sent()) {
    $host = ha_request_host();
    if ($host !== '') {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
        // فقط مسیرهای نسبی مجازند؛ از redirect به بیرون جلوگیری می‌شود
        if ($uri === '' || $uri[0] !== '/') { $uri = '/'; }
        header('Location: https://' . $host . $uri, true, 301);
        header('Cache-Control: no-store');
        exit;
    }
}

/* ------------------------------------------------------------------ */
/*  Session (فقط برای route‌هایی که لازم دارند)                        */
/* ------------------------------------------------------------------ */

if ((bool) route_meta($route, 'session', false) && session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    ini_set('session.use_strict_mode', '1');   // جلوگیری از session fixation با SID تحمیلی
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_name('HAVOICE');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => HA_BASE_PATH === '' ? '/' : HA_BASE_PATH,
        'domain'   => '',
        'secure'   => ha_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ------------------------------------------------------------------ */
/*  سرصفحه‌های امنیتی                                                  */
/* ------------------------------------------------------------------ */

if (HA_SECURITY_HEADERS && !headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    foreach (ha_security_headers() as $name => $value) {
        header($name . ': ' . $value);
    }
}

/* ------------------------------------------------------------------ */
/*  پردازش POST (الگوی PRG — پیش از رندر قالب)                         */
/* ------------------------------------------------------------------ */

if ($route === 'contact' && (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
    require HA_ROOT . '/includes/handlers/contact.php';
}

/* ------------------------------------------------------------------ */
/*  رندر                                                              */
/* ------------------------------------------------------------------ */

$GLOBALS['HA_META'] = ha_page_meta($route);

require HA_ROOT . '/includes/header.php';

$pageFile = HA_ROOT . '/pages/' . basename((string) route_meta($route, 'file', '404.php'));
if (!is_file($pageFile)) {
    $pageFile = HA_ROOT . '/pages/404.php';
}
require $pageFile;

require HA_ROOT . '/includes/footer.php';
