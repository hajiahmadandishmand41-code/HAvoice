<?php
/**
 * HAvoice — نقشه‌ی سایت XML برای URLهای عمومی و قابل ایندکس
 *
 * اصل مهم: فقط URLهایی وارد Sitemap می‌شوند که برای کاربر عمومی
 * قابل مشاهده‌اند. مسیرهای ورود/ثبت‌نام/حساب و محتوای آموزشیِ محافظت‌شده
 * (درس‌ها و دوره‌های نیازمند احراز هویت) عمداً در Sitemap نیستند.
 */

define('HA_ROOT', __DIR__);
require HA_ROOT . '/config/config.php';
require HA_ROOT . '/includes/helpers.php';
require HA_ROOT . '/includes/db.php';
require HA_ROOT . '/includes/repository.php';
require HA_ROOT . '/includes/content.php';

$root = site_url();
if ($root === '') {
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

/* صفحات عمومی اصلی */
foreach (['home', 'courses', 'articles', 'videos', 'audios', 'books', 'research', 'exercises', 'tips', 'about', 'contact', 'comments'] as $route) {
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

header('Content-Type: application/xml; charset=UTF-8');
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

$xml .= '</urlset>\n';
echo $xml;
