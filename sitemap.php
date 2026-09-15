<?php
/**
 * HAvoice — نقشه‌ی سایت (XML) — تمام محتوا
 *
 * بهبودها نسبت به نسخه‌ی پیشین:
 *  • نشانی‌ها از site_url() (مطلق و اعتبارسنجی‌شده) ساخته می‌شوند.
 *  • <lastmod> برای محتوای تاریخ‌دار افزوده شد.
 *  • صفحه‌ی جستجو و صفحه‌های خطا در نقشه نیستند.
 */

define('HA_ROOT', __DIR__);
require HA_ROOT . '/config/config.php';
require HA_ROOT . '/includes/helpers.php';
require HA_ROOT . '/includes/db.php';
require HA_ROOT . '/includes/repository.php';
require HA_ROOT . '/includes/content.php';

$root = site_url();
if ($root === '') {
    // بدونِ میزبانِ معتبر نمی‌توان نقشه‌ی مطلق ساخت
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "HA_SITE_URL is not configured and the request host is invalid.\n";
    exit;
}

$urls = [];

$add = static function (string $route, array $params, string $priority, string $lastmod = '') use ($root, &$urls): void {
    // صفحه‌ی اصلی با ریشه‌ی دامنه فهرست می‌شود (هم‌راستا با canonical)
    if ($route === 'home' && $params === []) {
        $urls[] = [$root . '/', $priority, $lastmod];
        return;
    }
    $local = url($route, $params);
    $loc   = $root . '/' . ltrim($local, '/');
    $urls[] = [$loc, $priority, $lastmod];
};

foreach (['home' => '1.0', 'courses' => '0.9', 'articles' => '0.8', 'videos' => '0.7', 'audios' => '0.7',
          'books' => '0.7', 'research' => '0.7', 'exercises' => '0.8', 'tips' => '0.6',
          'about' => '0.7', 'contact' => '0.5', 'comments' => '0.5'] as $static => $priority) {
    $add($static, [], $priority);
}

foreach (categories() as $cat) {
    $add('category', ['slug' => $cat['slug']], '0.7');
}
foreach (courses() as $c) {
    $add('course', ['slug' => $c['slug']], '0.85');
}
foreach (course_lesson_index() as $slug => $item) {
    $add('lesson', ['slug' => $slug], '0.8');
}
foreach (data('articles') as $article) {
    $add('article', ['slug' => (string) $article['slug']], '0.65', (string) ($article['date'] ?? ''));
}
foreach (books() as $b) {
    $add('books', ['slug' => $b['slug']], '0.6', (string) ($b['date'] ?? ''));
}
foreach (research_items() as $r) {
    $add('research', ['slug' => $r['slug']], '0.6', (string) ($r['date'] ?? ''));
}

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$loc, $priority, $lastmod]) {
    echo '  <url><loc>' . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . '</loc>';
    if ($lastmod !== '' && preg_match('/^\d{4}-\d{2}-\d{2}/', $lastmod)) {
        echo '<lastmod>' . htmlspecialchars(substr($lastmod, 0, 10), ENT_QUOTES, 'UTF-8') . '</lastmod>';
    }
    echo '<changefreq>weekly</changefreq><priority>' . $priority . '</priority></url>' . "\n";
}
echo '</urlset>';
