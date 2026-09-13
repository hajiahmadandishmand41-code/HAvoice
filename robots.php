<?php
/**
 * HAvoice — robots.txt پویا
 *
 * چرا پویا؟ خطِ Sitemap باید نشانیِ «مطلق» داشته باشد و دامنه‌ی سایت
 * بین محیطِ توسعه و Production فرق می‌کند. با HA_SITE_URL در
 * config/config.php می‌توان دامنه را قطعی کرد؛ در غیر این صورت از
 * میزبانِ درخواست (با اعتبارسنجی) استفاده می‌شود.
 *
 * در .htaccess نگاشته شده: robots.txt → robots.php
 * (اگر mod_rewrite فعال نباشد، فایلِ ثابتِ robots.txt سرو می‌شود.)
 */

define('HA_ROOT', __DIR__);
require HA_ROOT . '/config/config.php';
require HA_ROOT . '/includes/helpers.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$root = site_url();

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /index.php?p=search\n";
echo "Disallow: /search\n";
echo "Disallow: /index.php?p=login\n";
echo "Disallow: /index.php?p=register\n";
echo "Disallow: /index.php?p=account\n";
echo "Disallow: /index.php?p=logout\n";
echo "Disallow: /storage/\n";
echo "Disallow: /config/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /data/\n";
echo "Disallow: /pages/\n";
echo "\n";

if ($root !== '') {
    echo 'Sitemap: ' . $root . '/sitemap.php' . "\n";
}
