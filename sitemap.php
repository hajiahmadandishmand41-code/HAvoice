<?php
/**
 * HAvoice — نقشه‌ی سایت (XML). نشانی‌ها از همان فایل‌های داده ساخته می‌شود،
 * پس همیشه با محتوا هم‌خوان است. آدرس: /sitemap.php  (یا /sitemap.xml با rewrite)
 */

define('HA_ROOT', __DIR__);
require HA_ROOT . '/includes/helpers.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^A-Za-z0-9.\-:_]/', '', (string) $_SERVER['HTTP_HOST']) : 'localhost';
$root   = $scheme . '://' . $host . rtrim(HA_BASE_PATH, '/');

$urls = [];

/** نشانی مطلق از مسیر داخلی */
$abs = static function (string $route, array $params = []) use ($root): string {
    $local = url($route, $params);
    if ($local === '' || $local[0] === '/') {
        return $root . '/' . ltrim($local, '/');
    }

    return $root . '/' . $local;
};

foreach (['home', 'course', 'articles', 'exercises', 'tips', 'about', 'contact'] as $static) {
    $priority = $static === 'home' ? '1.0' : ($static === 'course' ? '0.9' : '0.7');
    $urls[]   = [$abs($static), $priority];
}

foreach (course_lesson_index() as $slug => $item) {
    $urls[] = [$abs('lesson', ['slug' => $slug]), '0.8'];
}

foreach (data('articles') as $article) {
    $urls[] = [$abs('article', ['slug' => (string) $article['slug']]), '0.6'];
}

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$loc, $priority]) {
    echo '  <url><loc>' . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . '</loc><changefreq>monthly</changefreq><priority>'
        . $priority . '</priority></url>' . "\n";
}
echo '</urlset>';
