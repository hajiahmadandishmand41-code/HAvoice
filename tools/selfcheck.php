<?php
/**
 * HAvoice — خودآزمایی (self-check)
 * -------------------------------------------------------------
 * یک ابزارِ QA کاملاً PHP-محور و بدونِ هیچ وابستگیِ خارجی
 * (بدونِ Node، بدونِ Composer، بدونِ PHPUnit) که همان بررسی‌هایی را
 * انجام می‌دهد که برای «آماده‌ی انتشار» بودن لازم است:
 *
 *   ۱. محیط: نسخه‌ی PHP، توابع/افزونه‌های لازم، مسیر نشست
 *   ۲. پیکربندی: ثابت‌های حیاتی و سازگاریِ مقادیر
 *   ۳. ذخیره‌سازی: نوشتن‌پذیریِ storage و وجودِ لایه‌های .htaccess
 *   ۴. دارایی‌ها: فونت/تصویر/سی‌اس‌اس/جی‌اس محلی و نبودِ CDN خارجی
 *   ۵. منطقِ محدودیت نرخ: پنجره، بازنشانی، باکتِ کهنه، GC و سقف فایل
 *   ۶. کنتراستِ رنگ: محاسبه‌ی نسبتِ WCAG برای توکن‌های کلیدی (روشن/تاریک)
 *   ۷. دودِ مسیرها (اختیاری، با HTTP خودکار): یک <h1>، نبودِ خطای PHP،
 *      canonical مطلق، JSON-LD معتبر، منابعِ خارجی و پویشِ XSS
 *
 * اجرا:
 *   php tools/selfcheck.php            # همه‌ی بررسی‌های آفلاین
 *   php tools/selfcheck.php --http     # + آزمونِ دودِ مسیرها روی سایتِ زنده
 *   php tools/selfcheck.php --http=https://example.com
 *
 * کدِ خروج: ۰ اگر هیچ FAIL نباشد، ۱ در غیر این صورت (برای CI).
 * دسترسیِ وب به این فایل بسته است (tools/.htaccess)؛ اگر از مرورگر
 * اجرا شود فقط در حالتِ HA_DEBUG کار می‌کند.
 */

declare(strict_types=1);

define('HA_ROOT', dirname(__DIR__));

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server' || isset($_SERVER['argc']);
if (!$isCli) {
    require HA_ROOT . '/config/config.php';
    if (!HA_DEBUG) {
        http_response_code(403);
        exit("403 — این ابزار فقط از خطِ فرمان (یا با HA_DEBUG=true) اجرا می‌شود.\n");
    }
}

require HA_ROOT . '/config/config.php';
require HA_ROOT . '/includes/helpers.php';
require HA_ROOT . '/includes/content.php';

/* ------------------------------------------------------------------ */
/*  زیرساختِ گزارش                                                     */
/* ------------------------------------------------------------------ */

$RESULTS = [];
$GROUP   = '';

function group(string $name): void
{
    global $GROUP;
    $GROUP = $name;
    echo "\n\033[1m— {$name}\033[0m\n";
}

function check(string $label, bool $pass, string $detail = '', bool $skip = false): void
{
    global $RESULTS, $GROUP;
    $status = $skip ? 'SKIP' : ($pass ? 'PASS' : 'FAIL');
    $color  = $skip ? "\033[33m" : ($pass ? "\033[32m" : "\033[31m");
    $RESULTS[] = ['group' => $GROUP, 'label' => $label, 'status' => $status, 'detail' => $detail];
    printf("  %s%-4s\033[0m %s%s\n", $color, $status, $label, $detail !== '' ? "  \033[2m({$detail})\033[0m" : '');
}

function info(string $label, string $value): void
{
    printf("  \033[36m•\033[0m %s: %s\n", $label, $value);
}

$argv = $GLOBALS['argv'] ?? $_SERVER['argv'] ?? [];
if (is_string($argv)) {                    // برخی اجراکننده‌ها آرایه را به‌صورتِ JSON می‌دهند
    $decoded = json_decode($argv, true);
    $argv = is_array($decoded) ? $decoded : [$argv];
}
if (!is_array($argv)) {
    $argv = [];
}
$wantHttp = false;
$baseUrl  = '';
foreach ($argv as $arg) {
    if ($arg === '--http') {
        $wantHttp = true;
    } elseif (str_starts_with($arg, '--http=')) {
        $wantHttp = true;
        $baseUrl  = rtrim(substr($arg, 7), '/');
    }
}

echo "\033[1mHAvoice self-check\033[0m — " . date('Y-m-d H:i:s') . "\n";

/* ------------------------------------------------------------------ */
/*  ۱) محیط                                                            */
/* ------------------------------------------------------------------ */

group('۱. محیطِ PHP');
check('نسخه‌ی PHP ≥ 8.1', PHP_VERSION_ID >= 80100, 'PHP ' . PHP_VERSION);
check('mbstring', function_exists('mb_strlen'));
check('json', function_exists('json_encode'));
check('hash_equals', function_exists('hash_equals'));
check('random_bytes', function_exists('random_bytes'));
check('filter_var', function_exists('filter_var'));
check('session', function_exists('session_start'));
check('allow_url_fopen (برای آزمونِ HTTP)', (bool) ini_get('allow_url_fopen'), '', !ini_get('allow_url_fopen'));

/* ------------------------------------------------------------------ */
/*  ۲) پیکربندی                                                        */
/* ------------------------------------------------------------------ */

group('۲. پیکربندی');
$consts = ['HA_NAME', 'HA_TAGLINE', 'HA_EMAIL', 'HA_HOTLINE', 'HA_SITE_URL', 'HA_DEBUG', 'HA_PRETTY_URLS',
           'HA_FORCE_HTTPS', 'HA_RATE_LIMIT_MAX', 'HA_RATE_LIMIT_WINDOW', 'HA_RATE_LIMIT_MIN_INTERVAL',
           'HA_RATE_LIMIT_MAX_FILES', 'HA_CSRF_TTL', 'HA_VERSION', 'HA_STORAGE_PATH',
           'HA_AUTH_MIN_PASSWORD', 'HA_AUTH_LOGIN_RATE_LIMIT_MAX', 'HA_AUTH_REGISTER_RATE_LIMIT_MAX',
           'HA_AUTH_RATE_LIMIT_WINDOW'];
foreach ($consts as $c) {
    check("ثابت {$c}", defined($c));
}
check('HA_DEBUG خاموش است', HA_DEBUG === false, HA_DEBUG ? 'برای Production باید false باشد' : 'false');
check('HA_CSRF_TTL معقول است', HA_CSRF_TTL >= 900 && HA_CSRF_TTL <= 86400, (string) HA_CSRF_TTL . ' ثانیه');
check('HA_RATE_LIMIT_MAX ≥ 1', HA_RATE_LIMIT_MAX >= 1);
check('HA_RATE_LIMIT_WINDOW ≥ 60', HA_RATE_LIMIT_WINDOW >= 60);
check('HA_RATE_LIMIT_MIN_INTERVAL < WINDOW', HA_RATE_LIMIT_MIN_INTERVAL < HA_RATE_LIMIT_WINDOW);
check('ایمیلِ تماس معتبر است', (bool) filter_var(HA_EMAIL, FILTER_VALIDATE_EMAIL), HA_EMAIL);
check('HA_SITE_URL خالی یا مطلقِ معتبر', HA_SITE_URL === '' || (bool) preg_match('#^https?://#', HA_SITE_URL), HA_SITE_URL === '' ? 'خالی ⇒ از میزبانِ درخواست' : HA_SITE_URL);
if (HA_PRETTY_URLS) {
    $rewriteOn = is_file(HA_ROOT . '/.htaccess')
        && (bool) preg_match('/^\s*RewriteRule \^\(\[a-z0-9/m', (string) file_get_contents(HA_ROOT . '/.htaccess'));
    check('HA_PRETTY_URLS فعال ⇒ قاعده‌ی بازنویسی در .htaccess هم باز باشد', $rewriteOn);
}

/* ------------------------------------------------------------------ */
/*  ۳) ذخیره‌سازی و محافظت                                             */
/* ------------------------------------------------------------------ */

group('۳. ذخیره‌سازی و محافظت از وب');
foreach (['messages', 'rate-limit', 'sessions'] as $sub) {
    $dir = storage_dir($sub);
    $exists = is_dir($dir);
    check("پوشه‌ی storage/{$sub}", $exists, $exists ? 'موجود' : 'ساخته نشد');
    if ($exists) {
        $probe = $dir . '/.w-' . bin2hex(random_bytes(4));
        $ok = @file_put_contents($probe, 'x') !== false;
        @unlink($probe);
        check("  نوشتن‌پذیریِ storage/{$sub}", $ok, $ok ? 'writable' : 'برای فرم تماس لازم است');
    }
}
$ht = [
    '/'                 => 'بستنِ فایل‌های داخلی + سرصفحه‌های امنیتی',
    '/config/'          => 'deny',
    '/data/'            => 'deny',
    '/includes/'        => 'deny',
    '/pages/'           => 'deny',
    '/storage/'         => 'deny + php_flag engine off',
];
foreach ($ht as $path => $why) {
    check(".htaccess در {$path}", is_file(HA_ROOT . $path . '.htaccess'), $why);
}
$rootHt = (string) @file_get_contents(HA_ROOT . '/.htaccess');
check('.htaccess ریشه: بستنِ فایل‌های حساس', str_contains($rootHt, 'Require all denied'));
check('.htaccess ریشه: سرصفحه‌های امنیتی', str_contains($rootHt, 'X-Content-Type-Options') && str_contains($rootHt, 'Content-Security-Policy'));
check('.htaccess ریشه: Options -Indexes', str_contains($rootHt, '-Indexes'));
$stHt = (string) @file_get_contents(HA_ROOT . '/storage/.htaccess');
check('storage/.htaccess: اجرای PHP خاموش', str_contains($stHt, 'engine off'));

/* ------------------------------------------------------------------ */
/*  ۴) دارایی‌های محلی                                                  */
/* ------------------------------------------------------------------ */

group('۴. دارایی‌های محلی');
$assets = [
    '/assets/css/style.css',
    '/assets/css/mobile-layout.css',
    '/assets/css/instructor-banner.css',
    '/assets/js/main.js',
    '/assets/js/theme.js',
    '/assets/fonts/vazirmatn-var.woff2',
    '/assets/fonts/OFL.txt',
    '/assets/img/fanbayan-banner.webp',   // بنرِ صفحه‌ی اصلی
    '/assets/img/instructor.jpg',         // عکسِ مدرس (هیرو + مدرس + درباره)
    '/assets/img/favicon.svg',
];
foreach ($assets as $a) {
    $exists = is_file(HA_ROOT . $a);
    $size   = $exists ? (int) @filesize(HA_ROOT . $a) : 0;
    check("فایل {$a}", $exists, $exists ? number_format($size / 1024, 1) . ' KB' : 'گم‌شده');
}
$css = (string) @file_get_contents(HA_ROOT . '/assets/css/style.css');
check('CSS: @font-face محلی برای وزیرمتن', (bool) preg_match('/@font-face[^}]*Vazirmatn/s', $css));
check('CSS: ارجاع به fonts.googleapis.com ندارد', !str_contains($css, 'fonts.googleapis'));

/* پوشه‌ی تصویرها باید فقط همین فایل‌ها را داشته باشد: بنر، عکسِ مدرس و آیکونِ سایت */
$allowedImgs = ['favicon.svg', 'fanbayan-banner.webp', 'instructor.jpg'];
sort($allowedImgs);
$foundImgs = [];
foreach ((array) @scandir(HA_ROOT . '/assets/img') as $name) {
    if (!is_string($name) || $name === '' || $name[0] === '.') { continue; }
    if (is_file(HA_ROOT . '/assets/img/' . $name)) { $foundImgs[] = $name; }
}
sort($foundImgs);
check('assets/img: فقط تصویرهای رسمیِ سایت (بنر، مدرس، آیکون)', $foundImgs === $allowedImgs, implode('، ', $foundImgs));

/* integrity: هر ارجاع asset(...) در PHP/JS و هر url(...) در CSS باید به فایلِ موجود برسد */
$brokenRefs = [];
$assetExt   = '\.(?:css|js|png|jpe?g|webp|avif|gif|ico|svg|woff2?|ttf|otf|eot)';
$tree = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(HA_ROOT, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($tree as $info) {
    if (!$info->isFile()) { continue; }
    $file = str_replace('\\', '/', $info->getPathname());
    if (strpos($file, '/.git/') !== false || strpos($file, '/storage/') !== false) { continue; }
    $ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
    if ($ext !== 'php' && $ext !== 'js' && $ext !== 'css') { continue; }

    $src  = (string) @file_get_contents($file);
    $refs = [];

    if ($ext === 'css') {
        if (preg_match_all('#url\(\s*[\x27"]?([^\x27")]+)[\x27"]?\s*\)#i', $src, $mm)) {
            foreach ($mm[1] as $r) { $refs[] = ['base' => dirname($file), 'ref' => $r]; }
        }
    } else {
        // فقط مسیرهایِ واقعیِ فایل: assets/…/name.ext (داخل asset() یا رشته‌ی مستقیمِ og:image)
        if (preg_match_all('#[\x27"](assets/[A-Za-z0-9_\-./]+' . $assetExt . ')[\x27"]#i', $src, $mm)) {
            foreach ($mm[1] as $r) { $refs[] = ['base' => HA_ROOT, 'ref' => $r]; }
        }
    }

    foreach ($refs as $one) {
        $ref = (string) preg_replace('/[?#].*$/', '', trim($one['ref']));
        if ($ref === '' || preg_match('#^(?:https?:)?//#i', $ref) || strpos($ref, 'data:') === 0) { continue; }
        $target = $ref[0] === '/' ? HA_ROOT . $ref : $one['base'] . '/' . $ref;
        if (!is_file($target)) {
            $brokenRefs[] = str_replace(HA_ROOT . '/', '', $file) . ' -> ' . $ref;
        }
    }
}
check('مسیرهای assets: هیچ ارجاع شکسته‌ای وجود ندارد',
    $brokenRefs === [],
    $brokenRefs === [] ? 'همه‌ی فایل‌های ارجاع‌شده موجودند' : implode(' | ', array_slice($brokenRefs, 0, 6)));
$hdr = (string) @file_get_contents(HA_ROOT . '/includes/header.php');
check('header: preload فونت محلی', str_contains($hdr, 'vazirmatn-var.woff2'));
/* فقط تگ‌های واقعی بررسی می‌شوند، نه توضیحاتِ داخلِ کامنت */
$hdrNoComment = (string) preg_replace('#/\*.*?\*/|//[^\n]*#s', '', $hdr);
check('header: هیچ درخواستی به fonts.googleapis/gstatic ندارد',
    !preg_match('#(?:href|src)="https?://fonts\.(?:googleapis|gstatic)#i', $hdrNoComment));
check('header: اسکریپتِ اجراییِ درون‌خطی ندارد (سازگار با CSP)',
    !preg_match('#<script(?![^>]*\bsrc=)(?![^>]*application/ld\+json)[^>]*>#i', $hdrNoComment));
$inlineAll = preg_match_all('#<script(?![^>]*\bsrc=)[^>]*>#i', $hdrNoComment);
$inlineLd  = preg_match_all('#<script[^>]*application/ld\+json[^>]*>#i', $hdrNoComment);
check('تنها اسکریپتِ درون‌خطی، داده‌ی JSON-LD است (CSP آن را اجرا نمی‌کند)',
    $inlineAll === $inlineLd, "درون‌خطی={$inlineAll}، JSON-LD={$inlineLd}");
check('og:image یک تصویرِ واقعی (PNG/JPG/WebP) از داخل assets است',
    (bool) preg_match('#[\x27"]assets/img/[A-Za-z0-9_\-./]+\.(?:png|jpe?g|webp)[\x27"]#i',
        (string) @file_get_contents(HA_ROOT . '/includes/meta.php')));
check('اسپرایتِ آیکون در footer چاپ می‌شود', str_contains((string) @file_get_contents(HA_ROOT . '/includes/footer.php'), 'ha_icon_sprite'));

/* ------------------------------------------------------------------ */
/*  ۵) منطقِ محدودیت نرخ (آزمونِ واحد، آفلاین)                          */
/* ------------------------------------------------------------------ */

group('۵. محدودیت نرخ — آزمونِ منطق');
$tmp = sys_get_temp_dir() . '/ha-rl-' . bin2hex(random_bytes(4));
@mkdir($tmp, 0700, true);
$now = time();
try {
    // الف) پنجره‌ی منقضی‌شده باید بازنشانی شود
    $stale = $tmp . '/' . substr(hash('sha256', 'contact|1.2.3.4'), 0, 32) . '.json';   // همان نامِ واقعیِ باکت
    file_put_contents($stale, json_encode(['n' => 99, 't' => $now - 700, 'l' => $now - 700]));
    $r = ha_rate_limit_acquire('contact', '1.2.3.4', 3, 600, 0, $tmp);
    check('باکتِ منقضی (n=99) بازنشانی می‌شود', $r['ok'] === true && $r['count'] === 1, 'count=' . $r['count']);

    // ب) سقفِ پنجره
    $ip = '10.0.0.9';
    $seq = [];
    for ($i = 0; $i < 5; $i++) {
        $rr = ha_rate_limit_acquire('contact', $ip, 3, 600, 0, $tmp);
        $seq[] = $rr['ok'] ? 'OK' : 'BLOCK';
    }
    check('سقفِ پنجره: ۳ مجاز و ۴-۵ مسدود', $seq === ['OK', 'OK', 'OK', 'BLOCK', 'BLOCK'], implode(',', $seq));

    // پ) حداقل فاصله (ضدِ رگبار)
    $ip2 = '10.0.0.10';
    $a = ha_rate_limit_acquire('contact', $ip2, 10, 600, 20, $tmp);
    $b = ha_rate_limit_acquire('contact', $ip2, 10, 600, 20, $tmp);
    check('فاصله‌ی حداقلی: دومی مسدود با retry≈۲۰', $a['ok'] && !$b['ok'] && $b['retry'] > 0 && $b['retry'] <= 20, 'retry=' . $b['retry']);

    // ت) IPهای جداگانه بر هم اثر ندارند
    $c = ha_rate_limit_acquire('contact', '10.0.0.11', 1, 600, 0, $tmp);
    check('باکت‌ها بر پایه‌ی IP جدا هستند', $c['ok'] === true);

    // ث) GC: حذفِ باکت‌های رهاشده
    for ($i = 0; $i < 12; $i++) {
        $f = $tmp . '/old' . $i . '.json';
        file_put_contents($f, '{"n":1}');
        touch($f, $now - 9999);
    }
    ha_rate_limit_gc($tmp, true);
    $leftOld   = count(glob($tmp . '/old*.json') ?: []);
    $leftFresh = count(glob($tmp . '/*.json') ?: []) - $leftOld;
    check('GC: باکت‌های رهاشده پاک می‌شوند', $leftOld === 0, "باقی‌مانده‌ی کهنه={$leftOld} از ۱۲");
    check('GC: باکت‌های تازه دست‌نخورده می‌مانند', $leftFresh === 4, "تازه={$leftFresh}");

    // ج) ساختارِ باکت
    $bucket = json_decode((string) @file_get_contents($tmp . '/' . substr(hash('sha256', 'contact|10.0.0.9'), 0, 32) . '.json'), true);
    check('باکت دارای n/t/l است', is_array($bucket) && isset($bucket['n'], $bucket['t'], $bucket['l']));
    check('t (آغازِ پنجره) در طولِ پنجره ثابت می‌ماند', isset($bucket['t']) && abs($bucket['t'] - $now) < 5, 't=' . ($bucket['t'] ?? '؟'));
} catch (Throwable $ex) {
    check('آزمونِ محدودیت نرخ بدون خطا اجرا شود', false, $ex->getMessage());
} finally {
    foreach (glob($tmp . '/*') ?: [] as $f) {
        @unlink($f);
    }
    @rmdir($tmp);
}

/* ------------------------------------------------------------------ */
/*  ۶) کنتراستِ رنگ (WCAG 2.2 AA)                                      */
/* ------------------------------------------------------------------ */

group('۶. کنتراستِ رنگ');

/** تبدیلِ #rgb/#rrggbb به [r,g,b] */
function hex_rgb(string $hex): ?array
{
    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        return null;
    }
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

function rel_lum(array $rgb): float
{
    $ch = [];
    foreach ($rgb as $v) {
        $s = $v / 255;
        $ch[] = $s <= 0.03928 ? $s / 12.92 : (($s + 0.055) / 1.055) ** 2.4;
    }
    return 0.2126 * $ch[0] + 0.7152 * $ch[1] + 0.0722 * $ch[2];
}

function contrast(string $a, string $b): float
{
    $ra = hex_rgb($a);
    $rb = hex_rgb($b);
    if ($ra === null || $rb === null) {
        return 0.0;
    }
    $la = rel_lum($ra);
    $lb = rel_lum($rb);
    $hi = max($la, $lb);
    $lo = min($la, $lb);
    return round(($hi + 0.05) / ($lo + 0.05), 2);
}

/**
 * بیرون کشیدنِ توکن‌های خامِ یک بلوکِ CSS (بدونِ حلِ var()).
 *
 * نکته: کامنت‌ها اول پاک می‌شوند، وگرنه یک کدِ رنگِ داخلِ کامنت می‌تواند
 * به‌اشتباه به‌عنوانِ مقدارِ توکن خوانده شود.
 *
 * @return array<string, string>
 */
function css_tokens_raw(string $css, string $blockPattern): array
{
    if (!preg_match($blockPattern, $css, $m)) {
        return [];
    }
    $body = (string) preg_replace('/\/\*.*?\*\//s', '', $m[0]);
    preg_match_all('/(--[a-z0-9-]+)\s*:\s*([^;}]+)/', $body, $tm, PREG_SET_ORDER);
    $out = [];
    foreach ($tm as $t) {
        $out[$t[1]] = trim($t[2]);
    }
    return $out;
}

/**
 * حلِ زنجیره‌ی var() تا رسیدن به یک کدِ رنگِ واقعی.
 *
 * چرا لازم است؟ Design System عمداً دو لایه دارد: پالتِ پایه
 * (--blue-700) و توکنِ معنایی (--brand). نسخه‌ی پیشینِ این تابع فقط
 * مقدارهای hexِ «مستقیم» را می‌گرفت، پس هر توکنی که به var() اشاره
 * می‌کرد «یافت نشد» گزارش می‌شد و ۶ آزمونِ کنتراست همیشه قرمزِ کاذب
 * بودند. دروازه‌ی انتشاری که اشتباه قرمز شود، نادیده گرفته می‌شود.
 *
 * @param array<string, string> $map
 */
function css_resolve_color(string $value, array $map, int $depth = 0): ?string
{
    if ($depth > 12) {
        return null;                       // محافظِ چرخه
    }
    $value = trim($value);
    if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $value)) {
        return $value;
    }
    if (preg_match('/^var\(\s*(--[a-z0-9-]+)\s*(?:,\s*(.*))?\)$/i', $value, $m)) {
        $next = $map[$m[1]] ?? null;
        if ($next !== null) {
            return css_resolve_color($next, $map, $depth + 1);
        }
        if (isset($m[2]) && trim($m[2]) !== '') {
            return css_resolve_color($m[2], $map, $depth + 1);   // fallback
        }
        return null;
    }
    return null;                            // rgba()/gradiant/… ⇒ قابلِ سنجشِ ساده نیست
}

/**
 * توکن‌های رنگِ «حل‌شده‌ی» یک بلوک.
 *
 * @param array<string, string> $raw  توکن‌های همان بلوک
 * @param array<string, string> $inherit توکن‌های بلوکِ والد (custom property
 *        در CSS از :root به ارث می‌رسد، پس هرچه در بلوکِ تاریک بازتعریف
 *        نشده عملاً همان مقدارِ روشن است)
 * @return array<string, string>
 */
function css_tokens(array $raw, array $inherit = []): array
{
    $scope = array_merge($inherit, $raw);
    $out = [];
    foreach ($raw as $name => $value) {
        $hex = css_resolve_color($value, $scope);
        if ($hex !== null) {
            $out[$name] = $hex;
        }
    }
    return $out;
}

$lightRaw = css_tokens_raw($css, '/:root\s*\{.*?\}/s');
$darkRaw  = css_tokens_raw($css, '/\[data-theme=["\']?dark["\']?\]?\s*\{.*?\}/s');
if ($darkRaw === []) {
    $darkRaw = css_tokens_raw($css, '/prefers-color-scheme:\s*dark[^{]*\{\s*[^{]*\{.*?\}/s');
}
$light = css_tokens($lightRaw);
$dark  = css_tokens($darkRaw, $lightRaw);
info('توکن‌های رنگِ حالتِ روشن', count($light) . ' عدد');
info('توکن‌های رنگِ حالتِ تاریک', count($dark) . ' عدد');

$pairs = [
    ['--text',        '--bg',        4.5, 'متنِ اصلی روی پس‌زمینه'],
    ['--text-soft',   '--bg',        4.5, 'متنِ ثانویه روی پس‌زمینه'],
    ['--text-mute',   '--surface-2', 4.5, 'متنِ کم‌رنگ روی سطح'],
    ['--brand-strong', '--bg',       4.5, 'لینک/آیکونِ برند روی روشن'],
    ['--accent-strong', '--bg',      3.0, 'آیکونِ کهربایی (غیرمتنی)'],
    ['--focus',       '--bg',        3.0, 'حلقه‌ی فوکوس (WCAG 1.4.11)'],
    ['--on-brand',    '--brand-3',   4.5, 'متنِ دکمه‌ی اصلی روی گرادیان'],
    ['--success',     '--brand-soft', 4.5, 'متنِ پیامِ موفقیت'],
    ['--danger',      '--accent-soft', 4.5, 'متنِ پیامِ خطا'],
    ['--danger',      '--bg',        4.5, 'متنِ خطای فیلد'],
    ['--warning',     '--bg',        3.0, 'نشانِ سطح/هشدار'],
    ['--text',        '--surface-2', 4.5, 'متن روی سطحِ دوم'],
];
foreach ([['روشن', $light], ['تاریک', $dark]] as [$mode, $tokens]) {
    if ($tokens === []) {
        check("کنتراست در حالتِ {$mode}", false, 'توکن‌ها پیدا نشد');
        continue;
    }
    foreach ($pairs as [$fg, $bg, $min, $label]) {
        $f = $tokens[$fg] ?? null;
        $b = $tokens[$bg] ?? null;
        if ($f === null || $b === null) {
            check("{$mode}: {$label}", false, "توکنِ {$fg} یا {$bg} یافت نشد");
            continue;
        }
        $ratio = contrast($f, $b);
        check("{$mode}: {$label}", $ratio >= $min, sprintf('%s روی %s = %.2f:1 (حداقل %.1f)', $f, $b, $ratio, $min));
    }
}

/* ------------------------------------------------------------------ */
/*  ۷) آزمونِ دودِ مسیرها (اختیاری — نیازمندِ سایتِ در دسترس)           */
/* ------------------------------------------------------------------ */

group('۶ب. لایهٔ دیتابیس (PDO)');
check('تابع db_configured', function_exists('db_configured'));
check('تابع db / db_ready', function_exists('db') && function_exists('db_ready'));
check('repository repo_courses', function_exists('repo_courses'));
check('repository repo_seed', function_exists('repo_seed'));
check('schema SQL 002', is_file(HA_ROOT . '/sql/002-schema.sql'));
check('migrate tool', is_file(HA_ROOT . '/tools/db-migrate.php'));
check('config.local.example', is_file(HA_ROOT . '/config/config.local.php.example'));
$gi = (string) @file_get_contents(HA_ROOT . '/.gitignore');
check('gitignore: config.local.php', str_contains($gi, 'config.local.php'));
check('gitignore: users.json', str_contains($gi, 'users.json'));
if (function_exists('db_configured') && db_configured()) {
    check('DB اتصال', db_ready(), db_ready() ? 'PDO ready' : 'configured but not ready');
    if (db_ready()) {
        check('DB categories یا fallback', count(repo_categories()) >= 1);
        check('DB courses یا fallback', count(repo_courses()) >= 1);
    }
} else {
    check('DB اختیاری (fallback فایل)', true, 'HA_DB_* خالی — content از data/*.php', false);
}

group('۷. آزمونِ دودِ مسیرها');

$routes = [
    'home'          => '/index.php?p=home',
    'courses'       => '/index.php?p=courses',
    'articles'      => '/index.php?p=articles',
    'videos'        => '/index.php?p=videos',
    'audios'        => '/index.php?p=audios',
    'books'         => '/index.php?p=books',
    'research'      => '/index.php?p=research',
    'exercises'     => '/index.php?p=exercises',
    'tips'          => '/index.php?p=tips',
    'about'         => '/index.php?p=about',
    'contact'       => '/index.php?p=contact',
    'comments'      => '/index.php?p=comments',
    'search'        => '/index.php?p=search',
    'course'        => '/index.php?p=course&slug=public-speaking-fundamentals',
    'lesson'        => '/index.php?p=lesson&slug=breathing-foundations',
    'category'      => '/index.php?p=category&slug=public-speaking',
    'article'       => '/index.php?p=article&slug=power-of-pause',
    'login'         => '/index.php?p=login',
    'register'      => '/index.php?p=register',
    'logout'        => '/index.php?p=logout',
    'not-found'     => '/index.php?p=__nope__',
];

function http_get(string $url): ?array
{
    $ctx = stream_context_create(['http' => [
        'method'        => 'GET',
        'timeout'       => 20,
        'ignore_errors' => true,          // تا بدنه‌ی 4xx/5xx هم برگردد
        'header'        => "User-Agent: HAvoice-selfcheck\r\n",
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        return null;
    }
    $headers = $http_response_header ?? [];
    $status  = 0;
    if (isset($headers[0]) && preg_match('#\s(\d{3})\s#', $headers[0], $m)) {
        $status = (int) $m[1];
    }
    return ['status' => $status, 'body' => $body, 'headers' => $headers];
}

if (!$wantHttp) {
    check('آزمونِ HTTP', false, 'با --http فعال کنید', true);
} else {
    if ($baseUrl === '') {
        $baseUrl = rtrim(site_url(), '/');
    }
    $probe = http_get($baseUrl . '/index.php?p=home');
    if ($probe === null) {
        check('دسترس‌پذیریِ سایت', false, "نشانی {$baseUrl} پاسخ نداد (allow_url_fopen یا فایروال؟)");
    } else {
        check('دسترس‌پذیریِ سایت', $probe['status'] === 200, $baseUrl . ' ⇒ ' . $probe['status']);
        info('نسخه', HA_VERSION);

        $allOk = true;
        foreach ($routes as $name => $path) {
            $want = $name === 'not-found' ? 404 : 200;
            $res  = http_get($baseUrl . $path);
            if ($res === null) {
                check("مسیر {$name}", false, 'پاسخی دریافت نشد');
                $allOk = false;
                continue;
            }
            $body = $res['body'];
            $notes = [];
            if ($res['status'] !== $want) {
                $notes[] = 'status=' . $res['status'] . ' انتظار=' . $want;
            }
            if ($name !== 'not-found') {
                $h1 = preg_match_all('/<h1[\s>]/i', $body);
                if ($h1 !== 1) {
                    $notes[] = "h1={$h1}";
                }
                if (preg_match('/(Warning:|Notice:|Deprecated:|Fatal error|Undefined (?:variable|index|array key))/', $body, $mm)) {
                    $notes[] = 'PHP: ' . substr($mm[0], 0, 40);
                }
                if (!preg_match('/rel="canonical"\s+href="https?:\/\//', $body)) {
                    $notes[] = 'canonical غیرمطلق';
                }
                if (preg_match('/property="og:url"\s+content="(?!https?:\/\/)/', $body)) {
                    $notes[] = 'og:url غیرمطلق';
                }
                if (preg_match_all('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $body, $jm)) {
                    foreach ($jm[1] as $json) {
                        if (json_decode($json) === null) {
                            $notes[] = 'JSON-LD نامعتبر';
                            break;
                        }
                    }
                }
                /* فقط منابعِ «بارگیری‌شدنی» مهم‌اند: اسکریپت، شیوه‌نامه، فونت،
                   تصویر و رسانه. تگ‌های متادیتا (canonical/og:url) نشانیِ خودِ
                   سایت‌اند و منبعِ خارجی محسوب نمی‌شوند. */
                $hostRe = preg_quote((string) parse_url($baseUrl, PHP_URL_HOST), '/');
                $localHosts = [$hostRe, 'localhost', '127\.0\.0\.1'];
                $allowed = implode('|', $localHosts);
                if (preg_match('#<script[^>]+src="https?://(?!' . $allowed . ')#i', $body, $em)
                    || preg_match('#<link[^>]+rel=["\']?stylesheet["\']?[^>]+href="https?://(?!' . $allowed . ')#i', $body, $em)
                    || preg_match('#<link[^>]+(?:as|rel)=["\']?(?:font|preload)["\']?[^>]+href="https?://(?!' . $allowed . ')#i', $body, $em)) {
                    $notes[] = 'منبعِ خارجی: ' . substr($em[0], -46);
                }
                $navStart = strpos($body, 'main-nav__list');
                $navEnd   = strpos($body, 'main-nav__mega');
                if ($navStart !== false && $navEnd !== false && $navEnd > $navStart) {
                    $nav = substr($body, $navStart, $navEnd - $navStart);
                    $ac  = preg_match_all('/aria-current="page"/', $nav);
                    if ($ac > 1) {
                        $notes[] = "aria-current={$ac}";
                    }
                }
            }
            $ok = $notes === [];
            $allOk = $allOk && $ok;
            check("مسیر {$name}", $ok, $ok ? (string) strlen($body) . 'B' : implode(' | ', $notes));
        }
        check('همه‌ی مسیرها سالم', $allOk);

        /* پویشِ ورودیِ مخرب */
        $xss = [
            '/index.php?p=search&q=' . rawurlencode('<script>alert(1)</script>'),
            '/index.php?p=search&q=' . rawurlencode('"><img src=x onerror=alert(1)>'),
            '/index.php?p=' . rawurlencode('../../etc/passwd'),
            '/index.php?p=article&slug=' . rawurlencode('../../../config/config'),
            '/index.php?p=category&slug=' . rawurlencode('<svg onload=alert(1)>'),
        ];
        $leak = false;
        foreach ($xss as $u) {
            $res = http_get($baseUrl . $u);
            if ($res === null) {
                continue;
            }
            $b = $res['body'];
            /* فقط «بازتابِ اجراشدنی» خطاست. اگر ورودی escape شده باشد
               (مثلاً &lt;img src=x onerror=…&gt;) بی‌خطر است و نباید
               شکست بخورد؛ بنابراین الگوها با < و > واقعی نوشته شده‌اند. */
            if (str_contains($b, '<script>alert(1)</script>')
                || str_contains($b, '<img src=x onerror=')
                || str_contains($b, '<svg onload=alert(1)>')
                || str_contains($b, 'root:x:0:0')
                || str_contains($b, "define('HA_")
                || str_contains($b, 'HA_RATE_LIMIT_MAX_FILES')
                || str_contains($b, 'session.save_path')) {
                check('پویشِ ورودی: ' . substr($u, -34), false, 'بازتابِ خطرناک یا نشتِ اطلاعات');
                $leak = true;
            }
        }
        check('پویشِ ورودی‌های مخرب (XSS/LFI)', !$leak, count($xss) . ' مورد');

        /* سرصفحه‌های امنیتی */
        $h = implode("\n", $probe['headers']);
        foreach (['Content-Security-Policy', 'X-Content-Type-Options', 'X-Frame-Options',
                  'Referrer-Policy', 'Permissions-Policy'] as $sec) {
            check("سرصفحه‌ی {$sec}", stripos($h, $sec . ':') !== false);
        }
        check('سرصفحه‌ی X-Powered-By حذف/پنهان است', stripos($h, 'x-powered-by') === false, 'در سطحِ PHP نیز خاموش شده');

        /* robots و sitemap */
        $rb = http_get($baseUrl . '/robots.php');
        check('robots.php در دسترس', $rb !== null && $rb['status'] === 200);
        if ($rb !== null) {
            check('robots.php: خطِ Sitemap مطلق', (bool) preg_match('#Sitemap: https?://#', $rb['body']));
        }
        $sm = http_get($baseUrl . '/sitemap.php');
        check('sitemap.php در دسترس', $sm !== null && $sm['status'] === 200);
        if ($sm !== null) {
            $locs = preg_match_all('/<loc>/', $sm['body']);
            check('sitemap: همه‌ی loc مطلق', !preg_match('/<loc>\/|<loc>index\.php/', $sm['body']), "{$locs} نشانی");
            check('sitemap: XML معتبر', @simplexml_load_string($sm['body']) !== false || !function_exists('simplexml_load_string'),
                function_exists('simplexml_load_string') ? '' : 'simplexml نصب نیست', !function_exists('simplexml_load_string'));
        }
    }
}

/* ------------------------------------------------------------------ */
/*  جمع‌بندی                                                           */
/* ------------------------------------------------------------------ */

$counts = ['PASS' => 0, 'FAIL' => 0, 'SKIP' => 0];
foreach ($RESULTS as $r) {
    $counts[$r['status']]++;
}
echo "\n\033[1mجمع‌بندی\033[0m\n";
printf("  \033[32mPASS: %d\033[0m   \033[31mFAIL: %d\033[0m   \033[33mSKIP: %d\033[0m\n\n", $counts['PASS'], $counts['FAIL'], $counts['SKIP']);
if ($counts['FAIL'] > 0) {
    echo "\033[31mمواردِ شکست:\033[0m\n";
    foreach ($RESULTS as $r) {
        if ($r['status'] === 'FAIL') {
            echo '  • [' . $r['group'] . '] ' . $r['label'] . ($r['detail'] !== '' ? ' — ' . $r['detail'] : '') . "\n";
        }
    }
    echo "\n";
}
exit($counts['FAIL'] > 0 ? 1 : 0);
