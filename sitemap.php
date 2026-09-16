<?php
/**
 * HAvoice — نقشه‌ی سایت XML برای URLهای عمومی و قابل ایندکس
 *
 * اصل مهم: فقط URLهایی وارد Sitemap می‌شوند که برای کاربر عمومی
 * قابل مشاهده‌اند. مسیرهای ورود/ثبت‌نام/حساب و محتوای آموزشیِ محافظت‌شده
 * (دوره، درس، تمرینِ نیازمند احراز هویت) عمداً در Sitemap نیستند.
 *
 * نکته‌ی InfinityFree: نسخه‌ی ثابت sitemap.xml مرجعِ اصلی است و مستقیم
 * سرو می‌شود (.htaccess هیچ Rewriteای برای آن ندارد). این فایلِ پویا فقط
 * به‌عنوان پشتیبان/اعتبارسنج باقی می‌ماند و باید دقیقاً همان URLها را
 * با خروجیِ XMLِ تمیز و بدونِ هیچ Warning/Notice/Whitespace تولید کند.
 */

/* ------------------------------------------------------------------ */
/*  سپرِ خروجیِ XML — باید پیش از هر include دیگری اجرا شود            */
/*                                                                    */
/*  روی هاست‌های اشتراکی (InfinityFree) ممکن است display_errors روشن   */
/*  باشد یا فایلِ config.local.php روی سرور BOM/فاصله‌ی اضافه داشته    */
/*  باشد؛ هر بایتِ اضافه پیش از ‎<?xml‎ یعنی «XML نامعتبر» در گوگل.     */
/*  پس: خطاها هرگز چاپ نمی‌شوند و هر خروجیِ سرگردان دور ریخته می‌شود.  */
/* ------------------------------------------------------------------ */
error_reporting(0);
ini_set('display_errors', '0');
if (function_exists('ob_start') && ob_get_level() === 0) {
    ob_start();
}

if (!defined('HA_ROOT')) {
    define('HA_ROOT', __DIR__);
}
require HA_ROOT . '/config/config.php';
require HA_ROOT . '/includes/helpers.php';
require HA_ROOT . '/includes/db.php';
require HA_ROOT . '/includes/repository.php';
require HA_ROOT . '/includes/content.php';

$root = site_url();
if ($root === '') {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "HA_SITE_URL is not configured and the request host is invalid.\n";
    exit;
}

$urls = [];
$seen = [];

$add = static function (string $route, array $params = [], string $lastmod = '') use ($root, &$urls, &$seen): void {
    if ($route === 'home' && $params === []) {
        $loc = $root . '/';
    } else {
        $local = url($route, $params);
        $loc   = $root . '/' . ltrim($local, '/');
    }

    if (isset($seen[$loc])) {
        return;
    }
    $seen[$loc] = true;
    $urls[] = [$loc, $lastmod];
};

/*
 * صفحات عمومی اصلی.
 *
 * توجه: 'exercises' عمداً اینجا نیست؛ آن مسیر 'auth' => true دارد و مهمان
 * (از جمله خزنده‌ی گوگل) به صفحه‌ی ورود هدایت می‌شود، پس قابل ایندکس نیست.
 * 'course' / 'lesson' / 'progress' هم به همین دلیل بیرون‌اند؛
 * 'search' / 'login' / 'register' / 'account' / 'logout' هم noindex‌اند.
 */
foreach (['home', 'courses', 'articles', 'videos', 'audios', 'books', 'research', 'tips', 'about', 'contact', 'comments'] as $route) {
    $add($route);
}

/* حوزه‌ها / دسته‌بندی‌های عمومی */
foreach (categories() as $cat) {
    if (!empty($cat['slug'])) {
        $add('category', ['slug' => (string) $cat['slug']]);
    }
}

/* مقالات منتشرشده — نه داده‌ی خام یا draftهای احتمالی */
foreach (articles() as $article) {
    if (!empty($article['slug'])) {
        $add(
            'article',
            ['slug' => (string) $article['slug']],
            (string) ($article['date'] ?? '')
        );
    }
}

/* کتاب‌های قابل مشاهده */
foreach (books() as $book) {
    if (!empty($book['slug'])) {
        $add(
            'books',
            ['slug' => (string) $book['slug']],
            (string) ($book['date'] ?? '')
        );
    }
}

/* پژوهش‌های قابل مشاهده */
foreach (research_items() as $item) {
    if (!empty($item['slug'])) {
        $add(
            'research',
            ['slug' => (string) $item['slug']],
            (string) ($item['date'] ?? '')
        );
    }
}

/* دور ریختنِ هر خروجیِ سرگردانِ includeها پیش از ارسالِ سرصفحه‌ها. */
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');
header('X-Robots-Tag: noindex');

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as [$loc, $lastmod]) {
    $xml .= '  <url><loc>' . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . '</loc>';
    if ($lastmod !== '' && preg_match('/^\d{4}-\d{2}-\d{2}/', $lastmod)) {
        $xml .= '<lastmod>' . htmlspecialchars(substr($lastmod, 0, 10), ENT_QUOTES, 'UTF-8') . '</lastmod>';
    }
    $xml .= '</url>' . "\n";
}

$xml .= '</urlset>' . "\n";
echo $xml;
exit;
