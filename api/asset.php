<?php
/**
 * HAvoice — Vercel static-asset router + Progressive Streaming.
 * - Range/206 Partial Content برای صوت/ویدیو (شروع سریع، seek سریع، اینترنت ضعیف)
 * - Accept-Ranges, Content-Range
 * - محافظت realpath + block php
 */

define('HA_ROOT', dirname(__DIR__));

$path = isset($_GET['path']) && is_string($_GET['path']) ? $_GET['path'] : '';
$path = ltrim(str_replace("\\", '/', $path), '/');

$rootName = 'assets';
if (str_starts_with($path, 'uploads/')) {
    $rootName = 'uploads';
    $path = substr($path, strlen('uploads/'));
}
$base = HA_ROOT . '/' . $rootName;
$realBase = realpath($base);
$full = $base . '/' . $path;
$real = is_string($full) ? realpath($full) : false;

$extCheck = strtolower((string) pathinfo((string) $real, PATHINFO_EXTENSION));
$blocked  = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phar', 'htaccess', 'cgi', 'pl'];

if ($realBase === false || $real === false || !is_file($real)
    || strpos($real, $realBase . DIRECTORY_SEPARATOR) !== 0
    || in_array($extCheck, $blocked, true)) {
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
    'm4v'   => 'video/x-m4v',
    'mov'   => 'video/quicktime',
    'mp3'   => 'audio/mpeg',
    'm4a'   => 'audio/mp4',
    'ogg'   => 'audio/ogg',
    'wav'   => 'audio/wav',
    'pdf'   => 'application/pdf',
];

$mime = $mimes[$ext] ?? 'application/octet-stream';
$size = filesize($real);
$mtime = filemtime($real);

header('Content-Type: ' . $mime);
header('Accept-Ranges: bytes');
header('X-Content-Type-Options: nosniff');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
header('ETag: "' . md5($real . $size . $mtime) . '"');

// برای فایل‌های استاتیک معمولی کش طولانی، برای رسانه کش کوتاه‌تر تا Range بهتر کار کند
if (in_array($ext, ['mp4','webm','mp3','ogg','wav','m4a','mov','m4v'], true)) {
    header('Cache-Control: public, max-age=86400, must-revalidate');
} else {
    header('Cache-Control: public, max-age=31536000, immutable');
}

// Handle If-None-Match / If-Modified-Since (304)
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH'], '" ') === md5($real . $size . $mtime)) {
    http_response_code(304);
    exit;
}

// --- Range support ---
$rangeHeader = $_SERVER['HTTP_RANGE'] ?? '';
if ($rangeHeader !== '' && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $m)) {
    $start = $m[1] === '' ? null : (int)$m[1];
    $end   = $m[2] === '' ? null : (int)$m[2];

    if ($start === null && $end !== null) {
        // suffix: last N bytes
        $start = $size - $end;
        $end = $size - 1;
    }
    if ($start !== null && $end === null) {
        $end = $size - 1;
    }
    if ($start < 0) $start = 0;
    if ($end >= $size) $end = $size - 1;
    if ($start > $end || $start >= $size) {
        http_response_code(416);
        header('Content-Range: bytes */' . $size);
        exit;
    }

    $length = $end - $start + 1;
    http_response_code(206);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    header('Content-Length: ' . $length);

    $fp = fopen($real, 'rb');
    if ($fp === false) {
        http_response_code(500);
        exit;
    }
    fseek($fp, $start);
    $remaining = $length;
    $chunk = 8192;
    while ($remaining > 0 && !feof($fp)) {
        $read = min($chunk, $remaining);
        $data = fread($fp, $read);
        if ($data === false) break;
        echo $data;
        $remaining -= strlen($data);
        if ($remaining > 0) {
            // flush for progressive
            if (ob_get_level() > 0) @ob_flush();
            @flush();
        }
    }
    fclose($fp);
    exit;
}

// No range — full file
header('Content-Length: ' . $size);
readfile($real);
exit;
