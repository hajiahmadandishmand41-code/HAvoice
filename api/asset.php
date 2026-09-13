<?php
/**
 * HAvoice — Vercel static-asset router.
 *
 * روی Apache، assets مستقیماً از دیسک سرو می‌شوند؛ اما روی Vercel همه‌ی
 * درخواست‌ها به PHP می‌رسند. این Lambda فایل‌های پوشه‌ی /assets را با
 * MIME درست و Cache-Control دائمی برمی‌گرداند و در برابر path traversal
 * (با realpath و بررسیِ پیشوندِ پوشه) محافظت می‌شود.
 */

define('HA_ROOT', dirname(__DIR__));

$path = isset($_GET['path']) && is_string($_GET['path']) ? $_GET['path'] : '';
$base = HA_ROOT . '/assets';
$realBase = realpath($base);
$full = $base . '/' . ltrim(str_replace("\\", '/', $path), '/');
$real = is_string($full) ? realpath($full) : false;

if ($realBase === false || $real === false || !is_file($real)
    || strpos($real, $realBase . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Not Found';
    exit;
}

$ext = strtolower((string) pathinfo($real, PATHINFO_EXTENSION));
$mimes = [
    'css'   => 'text/css; charset=UTF-8',
    'js'    => 'application/javascript; charset=UTF-8',
    'svg'   => 'image/svg+xml',
    'png'   => 'image/png',
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'gif'   => 'image/gif',
    'webp'  => 'image/webp',
    'ico'   => 'image/x-icon',
    'woff2' => 'font/woff2',
    'woff'  => 'font/woff',
    'ttf'   => 'font/ttf',
    'json'  => 'application/json; charset=UTF-8',
    'txt'   => 'text/plain; charset=UTF-8',
    'mp4'   => 'video/mp4',
    'webm'  => 'video/webm',
    'mp3'   => 'audio/mpeg',
    'ogg'   => 'audio/ogg',
    'pdf'   => 'application/pdf',
];

header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . (string) filesize($real));
readfile($real);
exit;
