<?php
/**
 * HAvoice — robots.txt پویا
 *
 * نکته‌ی InfinityFree: نسخه‌ی ثابت robots.txt مرجعِ اصلی است و مستقیم
 * سرو می‌شود (.htaccess هیچ Rewriteای برای آن ندارد). این فایلِ پویا فقط
 * به‌عنوان پشتیبان باقی می‌ماند و باید دقیقاً همان محتوای نسخه‌ی ثابت
 * را تولید کند؛ نشانی Sitemap همیشه به آدرس عمومیِ استاندارد
 * sitemap.xml اشاره می‌کند (هرگز sitemap.php).
 */

/* سپرِ خروجی — مانند sitemap.php: هیچ Warning/Noticeای چاپ نشود. */
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

/* دور ریختنِ هر خروجیِ سرگردانِ includeها پیش از ارسالِ سرصفحه‌ها. */
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$root = site_url();

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /index.php?p=search\n";
echo "Disallow: /search\n";
echo "Disallow: /index.php?p=login\n";
echo "Disallow: /login\n";
echo "Disallow: /index.php?p=register\n";
echo "Disallow: /register\n";
echo "Disallow: /index.php?p=account\n";
echo "Disallow: /account\n";
echo "Disallow: /index.php?p=logout\n";
echo "Disallow: /logout\n";
echo "Disallow: /index.php?p=progress\n";
echo "Disallow: /progress\n";
echo "Disallow: /index.php?p=admin\n";
echo "Disallow: /admin\n";
echo "Disallow: /storage/\n";
echo "Disallow: /config/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /data/\n";
echo "Disallow: /pages/\n";
echo "\n";

if ($root !== '') {
    echo 'Sitemap: ' . $root . '/sitemap.xml' . "\n";
}
exit;
