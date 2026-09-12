<?php
/**
 * HAvoice — بوت‌استرپ: پیکربندی، مسیریابی، متادیتای سئو و رندر قالب.
 * تنها نقطه‌ی ورود برنامه، index.php است.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

require HA_ROOT . '/config/config.php';
require HA_ROOT . '/includes/helpers.php';
require HA_ROOT . '/includes/content.php';
require HA_ROOT . '/includes/ui.php';
require HA_ROOT . '/includes/meta.php';

/* ------------------------------------------------------------------ */
/*  گزارش خطا                                                         */
/* ------------------------------------------------------------------ */

error_reporting(E_ALL);
ini_set('display_errors', HA_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

/* ------------------------------------------------------------------ */
/*  ۱) مسیر درخواست                                                    */
/* ------------------------------------------------------------------ */

/**
 * حالت ۱ — پیش‌فرض و همیشه کارکردی:  index.php?p=article&slug=power-of-pause
 * حالت ۲ — URL کوتاه (mod_rewrite):  /articles/power-of-pause  ==>  index.php?r=…
 */
function ha_resolve_route(): array
{
    $route = 'home';
    $slug  = '';

    if (HA_PRETTY_URLS && isset($_GET['r']) && is_string($_GET['r'])) {
        $path  = trim((string) preg_replace('#[^A-Za-z0-9_/\-]#', '', $_GET['r']), '/');
        $parts = $path === '' ? [] : explode('/', $path);
        $first = $parts[0] ?? '';
        $second = $parts[1] ?? '';

        $known = ['home', 'course', 'lesson', 'articles', 'exercises', 'tips', 'about', 'contact', 'search'];

        if ($first === 'articles' && $second !== '') {
            $route = 'article';
            $slug  = slugify($second);
        } elseif ($first === 'lesson' && $second !== '') {
            $route = 'lesson';
            $slug  = slugify($second);
        } else {
            $route = in_array($first, $known, true) ? $first : 'home';
        }

        if ($slug !== '') {
            $_GET['slug'] = $slug;
        }
    } else {
        $raw   = isset($_GET['p']) && is_string($_GET['p']) ? $_GET['p'] : '';
        $route = slugify($raw);
        $route = route_exists($route) ? $route : ($route === '' ? 'home' : '');
    }

    if ($route !== '' && !route_exists($route)) {
        $route = '';
    }

    if ($slug === '' && isset($_GET['slug']) && is_string($_GET['slug'])) {
        $slug = slugify($_GET['slug']);
    }

    return [$route, $slug];
}

[$route, $slug] = ha_resolve_route();

if ($route === '') {
    http_response_code(404);
    $route = '404';
}

/* نامک نامعتبر برای مقاله/درس = ۴۰۴ (قبل از هر خروجی، تا کد وضعیت درست بماند) */
if ($route === 'article' && find_by_slug(all_articles_sorted(), $slug)[1] === null) {
    $route = '404';
    http_response_code(404);
} elseif ($route === 'lesson' && course_find_lesson($slug) === null) {
    $route = '404';
    http_response_code(404);
}

$GLOBALS['HA_ROUTE'] = $route;
$GLOBALS['HA_SLUG']  = $slug;

/* ------------------------------------------------------------------ */
/*  ۲) جلسه — فقط برای صفحاتی که لازم دارند (فرم تماس، پیشرفت درس)     */
/* ------------------------------------------------------------------ */

if ((bool) route_meta($route, 'session', false) && session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_name('HAVOICE');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => HA_BASE_PATH === '' ? '/' : HA_BASE_PATH,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/* ------------------------------------------------------------------ */
/*  ۳) سرصفحه‌های امنیتی                                               */
/* ------------------------------------------------------------------ */

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 0');
    if (HA_FORCE_HTTPS && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'off') {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
        exit;
    }
}

/* ------------------------------------------------------------------ */
/*  ۴) پردازش فرم‌ها (قبل از رندر، تا امکان Redirect باشد)             */
/* ------------------------------------------------------------------ */

if ($route === 'contact' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require HA_ROOT . '/includes/handlers/contact.php';
}

/* ------------------------------------------------------------------ */
/*  ۵) رندر                                                           */
/* ------------------------------------------------------------------ */

$GLOBALS['HA_META'] = ha_page_meta($route);

require HA_ROOT . '/includes/header.php';

$pageFile = HA_ROOT . '/pages/' . route_meta($route, 'file', '404.php');
if (!is_file($pageFile)) {
    $pageFile = HA_ROOT . '/pages/404.php';
}
require $pageFile;

require HA_ROOT . '/includes/footer.php';
