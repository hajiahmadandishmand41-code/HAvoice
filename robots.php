<?php
/**
 * HAvoice — robots.txt پویا
 *
 * در .htaccess نگاشته شده: robots.txt → robots.php
 * نشانی Sitemap به آدرس عمومیِ استاندارد sitemap.xml اشاره می‌کند.
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
echo "Disallow: /login\n";
echo "Disallow: /index.php?p=register\n";
echo "Disallow: /register\n";
echo "Disallow: /index.php?p=account\n";
echo "Disallow: /account\n";
echo "Disallow: /index.php?p=logout\n";
echo "Disallow: /logout\n";
echo "Disallow: /storage/\n";
echo "Disallow: /config/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /data/\n";
echo "Disallow: /pages/\n";
echo "\n";

if ($root !== '') {
    echo 'Sitemap: ' . $root . '/sitemap.xml' . "\n";
}
