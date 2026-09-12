<?php
/**
 * HAvoice 2.0 — توابع کمکی مشترک (هسته‌ی سبک، بدون وابستگی)
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  داده‌ها                                                            */
/* ------------------------------------------------------------------ */

function data(string $name): array
{
    static $cache = [];
    $name = (string) preg_replace('/[^a-z_]/', '', strtolower($name));
    if (!isset($cache[$name])) {
        $file = HA_ROOT . '/data/' . $name . '.php';
        $cache[$name] = is_file($file) ? (array) require $file : [];
    }
    return $cache[$name];
}

function nav_items(): array
{
    $routes = [
        'home'      => 'خانه',
        'courses'   => 'دوره‌ها',
        'articles'  => 'مقالات',
        'videos'    => 'ویدیو',
        'audios'    => 'پادکست',
        'books'     => 'کتاب‌ها',
        'research'  => 'پژوهش',
        'exercises' => 'تمرین‌ها',
        'about'     => 'درباره مدرس',
        'contact'   => 'تماس',
    ];
    $out = [];
    foreach ($routes as $route => $label) {
        $out[] = ['route' => $route, 'label' => $label, 'url' => url($route)];
    }
    return $out;
}

function nav_mega(): array
{
    $cats = data('categories');
    $groups = [];
    foreach ($cats as $cat) {
        $groups[] = [
            'label' => $cat['title'],
            'url'   => url('category', ['slug' => $cat['slug']]),
            'slug'  => $cat['slug'],
        ];
    }
    return $groups;
}

/* ------------------------------------------------------------------ */
/*  آدرس‌ها                                                            */
/* ------------------------------------------------------------------ */

function base_path(): string
{
    return HA_PRETTY_URLS ? rtrim(HA_BASE_PATH, '/') : '';
}

/**
 * آیا درخواست فعلی روی HTTPS است؟
 * روی هاست‌های اشتراکی (InfinityFree) معمولاً SSL در لایه‌ی پروکسی خاتمه می‌یابد،
 * بنابراین علاوه بر $_SERVER['HTTPS']، سرصفحه‌های استاندارد پروکسی هم بررسی می‌شوند.
 */
function ha_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($proto !== '' && in_array($proto, ['https', 'https,on'], true)) {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
        return true;
    }
    return false;
}

/**
 * نام میزبان درخواست، با اعتبارسنجی.
 * از Host Header Injection و open-redirect جلوگیری می‌کند:
 * اگر HA_SITE_URL تنظیم شده باشد همان ملاک است، وگرنه فقط نام‌های
 * معتبرِ RFC-1035 با طول محدود پذیرفته می‌شوند.
 */
function ha_request_host(): string
{
    if (HA_SITE_URL !== '') {
        $parts = parse_url(HA_SITE_URL);
        $host  = $parts['host'] ?? '';
        return ($parts['port'] ?? null) ? $host . ':' . (int) $parts['port'] : $host;
    }
    $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($host === '' || strlen($host) > 253) {
        return '';
    }
    // فقط حروف، رقم، نقطه، خط تیره و یک port اختیاری
    if (!preg_match('/^[a-z0-9]([a-z0-9\-\.]*[a-z0-9])?(:\d{1,5})?$/', $host)) {
        return '';
    }
    return $host;
}

/**
 * نشانی مطلقِ ریشه‌ی سایت (بدون اسلش انتهایی).
 * برای canonical، og:url، sitemap و robots ضروری است.
 */
function site_url(): string
{
    if (HA_SITE_URL !== '') {
        return rtrim(HA_SITE_URL, '/');
    }
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $host = ha_request_host();
    if ($host === '') {
        $cached = '';
        return '';
    }
    $base = rtrim(HA_BASE_PATH, '/');
    $cached = (ha_is_https() ? 'https://' : 'http://') . $host . $base;
    return $cached;
}

/** نشانی نسبیِ داخلی را به مطلق تبدیل می‌کند. */
function absolute_url(string $relative): string
{
    $root = site_url();
    if ($root === '') {
        return $relative;
    }
    if (preg_match('#^[a-z][a-z0-9+.\-]*://#i', $relative)) {
        return $relative; // از قبل مطلق است
    }
    return $root . '/' . ltrim($relative, '/');
}

/**
 * سرصفحه‌های امنیتی.
 * از سمت PHP ارسال می‌شوند چون mod_headers روی برخی هاست‌های اشتراکی
 * فعال نیست؛ در .htaccess هم به‌عنوان لایه‌ی دوم تکرار شده‌اند.
 */
function ha_security_headers(): array
{
    $https = ha_is_https();

    // frame-src: فقط میزبان‌های ویدیویی که برای امبد اجازه می‌دهیم.
    $frameSrc = "'self' https://www.aparat.com https://player.vimeo.com"
              . " https://www.youtube.com https://www.youtube-nocookie.com";

    $csp = [
        "default-src 'self'",
        "base-uri 'self'",
        "object-src 'none'",
        "frame-ancestors 'self'",
        "form-action 'self'",
        "script-src 'self'",
        // style-src-attr: صفات style="" در قالب‌ها (کنترل‌شده و ثابت) استفاده می‌شوند
        "style-src 'self' 'unsafe-inline'",
        "img-src 'self' data:",
        "media-src 'self'",
        "font-src 'self'",
        "connect-src 'self'",
        "frame-src " . $frameSrc,
    ];
    if ($https) {
        $csp[] = 'upgrade-insecure-requests';
    }

    return [
        'Content-Security-Policy'   => implode('; ', $csp),
        'X-Content-Type-Options'    => 'nosniff',
        'X-Frame-Options'           => 'SAMEORIGIN',
        'Referrer-Policy'           => 'strict-origin-when-cross-origin',
        'Permissions-Policy'        => 'geolocation=(), microphone=(), camera=(), payment=(), usb=(), interest-cohort=()',
        'X-XSS-Protection'          => '0',
        'Cross-Origin-Opener-Policy' => 'same-origin',
    ];
}

function url(string $route = 'home', array $params = []): string
{
    $params = array_filter($params, static function ($v) {
        return $v !== null && $v !== '';
    });
    if (!HA_PRETTY_URLS) {
        $query = ['p' => $route] + $params;
        return 'index.php?' . http_build_query($query);
    }
    $slug = isset($params['slug']) ? '/' . rawurlencode((string) $params['slug']) : '';
    unset($params['slug']);
    $prefix = route_meta($route, 'pretty', $route);
    $path   = ($route === 'home') ? '' : '/' . $prefix . $slug;
    $query  = $params ? '?' . http_build_query($params) : '';
    return (base_path() ?: '') . '/' . ltrim($path . $query, '/');
}

/**
 * نشانی asset با نسخه‌گذاری خودکار.
 * از filemtime استفاده می‌کند تا پس از هر تغییر، کش مرورگر/CDN بدون
 * ویرایش دستیِ شماره‌ی نسخه باطل شود (با .htaccess immutable سازگار است).
 * اگر فایل موجود نبود، به HA_VERSION برمی‌گردد.
 */
function asset(string $file): string
{
    static $cache = [];
    $path = ltrim($file, '/');
    if (isset($cache[$path])) {
        return $cache[$path];
    }
    $full  = HA_ROOT . '/' . $path;
    $stamp = is_file($full) ? (int) @filemtime($full) : 0;
    $ver   = $stamp > 0 ? $stamp : HA_VERSION;
    $prefix = HA_PRETTY_URLS ? base_path() . '/' : '';
    return $cache[$path] = $prefix . $path . '?v=' . $ver;
}

/* ------------------------------------------------------------------ */
/*  متادیتای مسیرها                                                   */
/* ------------------------------------------------------------------ */

function routes(): array
{
    static $table = null;
    if ($table !== null) {
        return $table;
    }
    $table = [
        'home'      => ['file' => 'home.php',      'pretty' => 'home',      'title' => 'خانه'],
        'courses'   => ['file' => 'courses.php',   'pretty' => 'courses',   'title' => 'دوره‌ها'],
        'course'    => ['file' => 'course.php',    'pretty' => 'course',    'title' => 'دوره'],
        'lesson'    => ['file' => 'lesson.php',    'pretty' => 'lesson',    'title' => 'درس'],
        'articles'  => ['file' => 'articles.php',  'pretty' => 'articles',  'title' => 'مقالات'],
        'article'   => ['file' => 'article.php',   'pretty' => 'articles',  'title' => 'مقاله'],
        'videos'    => ['file' => 'videos.php',    'pretty' => 'videos',    'title' => 'ویدیوهای آموزشی'],
        'audios'    => ['file' => 'audios.php',    'pretty' => 'audios',    'title' => 'پادکست و صوت'],
        'books'     => ['file' => 'books.php',     'pretty' => 'books',     'title' => 'کتاب‌ها'],
        'research'  => ['file' => 'research.php',  'pretty' => 'research',  'title' => 'تحقیقات'],
        'category'  => ['file' => 'category.php',  'pretty' => 'category',  'title' => 'حوزه آموزشی'],
        'exercises' => ['file' => 'exercises.php', 'pretty' => 'exercises', 'title' => 'تمرین‌ها'],
        'tips'      => ['file' => 'tips.php',      'pretty' => 'tips',      'title' => 'نکات کوتاه'],
        'about'     => ['file' => 'about.php',     'pretty' => 'about',     'title' => 'درباره مدرس'],
        'contact'   => ['file' => 'contact.php',   'pretty' => 'contact',   'title' => 'تماس با ما', 'session' => true],
        'search'    => ['file' => 'search.php',    'pretty' => 'search',    'title' => 'جستجو'],
    ];
    return $table;
}

function route_meta(string $route, string $key, $default = null)
{
    $table = routes();
    return isset($table[$route][$key]) ? $table[$route][$key] : $default;
}

function route_exists(string $route): bool
{
    return isset(routes()[$route]);
}

/* ------------------------------------------------------------------ */
/*  امن‌سازی و خروجی                                                   */
/* ------------------------------------------------------------------ */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify($value): string
{
    $value = strtolower((string) $value);
    $value = preg_replace('/[^a-z0-9_\-]/', '', $value);
    return (string) $value;
}

function param(string $key, string $default = ''): string
{
    if (!isset($_GET[$key]) || !is_string($_GET[$key])) {
        return $default;
    }
    return trim(substr($_GET[$key], 0, 200)) ?: $default;
}

function fa_num($number): string
{
    return str_replace(
        ['0','1','2','3','4','5','6','7','8','9'],
        ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'],
        (string) $number
    );
}

function minutes_label(int $minutes): string
{
    return fa_num($minutes) . ' دقیقه';
}



/* ------------------------------------------------------------------ */
/*  CSRF                                                             */
/* ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) return '';
    if (empty($_SESSION['ha_csrf'])) $_SESSION['ha_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['ha_csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
function csrf_verify(): bool
{
    $sent = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    return $sent !== '' && session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['ha_csrf']) && hash_equals($_SESSION['ha_csrf'], $sent);
}

/* ------------------------------------------------------------------ */
/*  رندر محتوا                                                        */
/* ------------------------------------------------------------------ */

function inline(string $text): string
{
    $text = e($text);
    $text = preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $text);
    $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/su', '<em>$1</em>', $text);
    $text = preg_replace('/`(.+?)`/su', '<code>$1</code>', $text);
    return $text;
}

function render_blocks(array $blocks, int $startLevel = 2): string
{
    if ($blocks === []) return '';
    $html = [];
    $heading = 0;
    foreach ($blocks as $block) {
        if (!is_array($block) || empty($block['type'])) continue;
        switch ($block['type']) {
            case 'h2':
            case 'h3':
                $level = $block['type'] === 'h3' ? 3 : max(2, $startLevel);
                $heading++;
                $html[] = '<h' . $level . ' id="sec-' . $heading . '">' . inline((string)($block['text'] ?? '')) . '</h' . $level . '>';
                break;
            case 'p':
                $html[] = '<p>' . inline((string)($block['text'] ?? '')) . '</p>';
                break;
            case 'lead':
                $html[] = '<p class="lead">' . inline((string)($block['text'] ?? '')) . '</p>';
                break;
            case 'ul':
                $html[] = '<ul class="rich-list">' . list_items((array)($block['items'] ?? [])) . '</ul>';
                break;
            case 'ol':
                $html[] = '<ol class="rich-list rich-list--num">' . list_items((array)($block['items'] ?? [])) . '</ol>';
                break;
            case 'quote':
                $html[] = '<blockquote class="quote">' . inline((string)($block['text'] ?? '')) . (empty($block['by']) ? '' : '<cite>' . e($block['by']) . '</cite>') . '</blockquote>';
                break;
            case 'tip': {
                $tone = (string) ($block['tone'] ?? 'tip');
                $html[] = '<aside class="callout callout--' . e($tone) . '">'
                    . '<span class="callout__icon">' . ha_icon(callout_icon($tone), 17) . '</span>'
                    . '<div><h3>' . e($block['title'] ?? 'نکته') . '</h3><p>'
                    . inline((string) ($block['text'] ?? '')) . '</p></div></aside>';
                break;
            }
            case 'drill':
                $html[] = render_drill($block);
                break;
            case 'table':
                $html[] = render_table((array)($block['rows'] ?? []), (array)($block['head'] ?? []));
                break;
            case 'audio':
                $html[] = render_audio_block($block);
                break;
            case 'video':
                $html[] = render_video_block($block);
                break;
            default:
                break;
        }
    }
    return implode("\n", $html);
}

function list_items(array $items): string
{
    $out = '';
    foreach ($items as $item) $out .= '<li>' . inline((string)$item) . '</li>';
    return $out;
}

function render_table(array $rows, array $head = []): string
{
    if ($rows === []) return '';
    $html = '<div class="table-wrap"><table class="compare"><thead><tr>';
    foreach ($head as $cell) $html .= '<th>' . inline((string)$cell) . '</th>';
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ((array)$row as $cell) $html .= '<td>' . inline((string)$cell) . '</td>';
        $html .= '</tr>';
    }
    return $html . '</tbody></table></div>';
}

function render_drill(array $block): string
{
    $items = '';
    foreach ((array)($block['items'] ?? []) as $item) {
        $items .= '<li><span class="tick">' . ha_icon('check', 15) . '</span><span>' . inline((string)$item) . '</span></li>';
    }
    return '<section class="drill"><header class="drill__head"><h3>' . e($block['title'] ?? 'تمرین') . '</h3>' . (empty($block['time']) ? '' : '<span class="chip chip--ghost">' . minutes_label((int)$block['time']) . '</span>') . '</header><ul class="drill__list">' . $items . '</ul>' . (empty($block['note']) ? '' : '<p class="drill__note">' . inline((string)$block['note']) . '</p>') . '</section>';
}

/**
 * اعتبارسنجی نشانیِ رسانه.
 * فقط http/https و مسیرهای نسبی مجازند — javascript: و data: رد می‌شوند.
 */
function ha_safe_media_url(string $src): string
{
    $src = trim($src);
    if ($src === '') {
        return '';
    }
    // حذف کاراکترهای کنترلی و سفیدسازیِ خطرناک
    $src = preg_replace('/[\x00-\x20\x7F]/u', '', $src) ?? '';
    if ($src === '') {
        return '';
    }
    if (preg_match('#^(https?:)?//#i', $src)) {
        return $src;
    }
    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $src)) {
        return ''; // هر scheme دیگری (javascript:, data:, blob:, …) رد می‌شود
    }
    return $src; // مسیر نسبی
}

/**
 * آیا نشانی، از میزبان‌های مجازِ امبد است؟
 * فهرست سفید با frame-src در CSP هم‌راستاست.
 */
function ha_embed_url(string $src): string
{
    $src = ha_safe_media_url($src);
    if ($src === '' || !preg_match('#^https?://#i', $src)) {
        return '';
    }
    $host = strtolower((string) (parse_url($src, PHP_URL_HOST) ?: ''));
    $host = preg_replace('/^www\./', '', $host) ?? '';
    $allow = ['aparat.com', 'player.vimeo.com', 'vimeo.com', 'youtube.com', 'youtube-nocookie.com'];
    foreach ($allow as $ok) {
        if ($host === $ok || substr($host, -strlen('.' . $ok)) === '.' . $ok) {
            return $src;
        }
    }
    return '';
}

function render_audio_block(array $block): string
{
    $src   = ha_safe_media_url((string) ($block['src'] ?? ''));
    $title = (string) ($block['title'] ?? 'فایل صوتی');
    if ($src === '') {
        return '<div class="media-placeholder media-placeholder--audio">' . ha_icon('headphones')
             . '<span>' . e($title) . '</span><small>فایل صوتی به‌زودی افزوده می‌شود</small></div>';
    }
    return '<div class="audio-block"><p class="audio-block__title">' . e($title) . '</p>'
         . '<audio controls preload="none" src="' . e($src) . '"></audio></div>';
}

/**
 * بلوک ویدیو.
 *
 * امنیتی: پیش‌تر اگر مقدار src شامل «iframe» بود، خام و بدون escape در
 * خروجی چاپ می‌شد (یک sink تزریق HTML). اکنون هرگز markup خام چاپ
 * نمی‌شود: iframe از روی نشانیِ اعتبارسنجی‌شده و با فهرست سفید ساخته
 * می‌شود و بقیه به <video> یا placeholder می‌روند.
 */
function render_video_block(array $block): string
{
    $raw   = (string) ($block['src'] ?? '');
    $title = (string) ($block['title'] ?? 'ویدیو');

    // اگر نویسنده markup کامل iframe گذاشته باشد، فقط src آن را بیرون می‌کشیم
    if (stripos($raw, '<iframe') !== false && preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $raw, $m)) {
        $raw = $m[1];
    }

    $embed = ha_embed_url($raw);
    if ($embed !== '') {
        return '<div class="video-embed"><iframe src="' . e($embed) . '" title="' . e($title) . '"'
             . ' loading="lazy" referrerpolicy="strict-origin-when-cross-origin"'
             . ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture"'
             . ' allowfullscreen></iframe></div>';
    }

    $src = ha_safe_media_url($raw);
    if ($src === '') {
        return '<div class="media-placeholder media-placeholder--video">' . ha_icon('play')
             . '<span>' . e($title) . '</span><small>ویدیو به‌زودی افزوده می‌شود — ساختار آماده است</small></div>';
    }
    return '<div class="video-block"><video controls preload="metadata" src="' . e($src) . '"></video>'
         . '<p class="muted-sm">' . e($title) . '</p></div>';
}

/** نگاشتِ لحنِ callout ⇒ نامِ آیکون (به‌جای گلیف‌های یونیکدِ ناسازگار). */
function callout_icon(string $tone): string
{
    $map = [
        'tip'   => 'sparkle',
        'warn'  => 'alert',
        'check' => 'check',
        'idea'  => 'idea',
        'info'  => 'info',
    ];
    return $map[$tone] ?? 'sparkle';
}

/* ------------------------------------------------------------------ */
/*  محدودیت نرخ (Rate Limit)                                          */
/* ------------------------------------------------------------------ */

/** فهرستِ فایل‌های یک پوشه، بدونِ وابستگی به glob() (روی برخی هاست‌ها غیرفعال است). */
function ha_dir_files(string $dir, string $ext = 'json'): array
{
    $out = [];
    $dh = @opendir($dir);
    if ($dh === false) {
        return $out;
    }
    while (($name = readdir($dh)) !== false) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        if ($ext === '' || substr($name, -strlen('.' . $ext)) === '.' . $ext) {
            $out[] = $dir . '/' . $name;
        }
    }
    closedir($dh);
    return $out;
}

/**
 * پاک‌سازیِ باکت‌های قدیمیِ محدودیت نرخ.
 *
 * چرا لازم است؟ هر IP یک فایل می‌سازد؛ بدونِ پاک‌سازی، تعدادِ فایل‌ها
 * بی‌نهايت رشد می‌کند و روی هاستِ اشتراکی (که سقف inode دارد) باعث
 * خرابیِ کلِ سایت می‌شود. این تابع یک‌بار در هر درخواست اجرا می‌شود.
 */
function ha_rate_limit_gc(string $dir): void
{
    static $ran = false;
    if ($ran) {
        return;
    }
    $ran = true;

    $now   = time();
    $stale = HA_RATE_LIMIT_WINDOW * 2;
    $keep  = [];

    foreach (ha_dir_files($dir, 'json') as $file) {
        $mtime = @filemtime($file);
        if ($mtime === false || ($now - $mtime) > $stale) {
            @unlink($file);
            continue;
        }
        $keep[] = [$mtime, $file];
    }

    $over = count($keep) - (int) HA_RATE_LIMIT_MAX_FILES;
    if ($over > 0) {
        sort($keep); // قدیمی‌ترین‌ها اول
        for ($i = 0; $i < $over; $i++) {
            @unlink($keep[$i][1]);
        }
    }
}

/**
 * محدودیت نرخِ پنجره‌ی ثابت (Fixed Window) برای یک scope و IP.
 *
 * باگی که این تابع رفع می‌کند: پیاده‌سازی قبلی زمانِ شروعِ پنجره را هرگز
 * بازنشانی نمی‌کرد؛ در نتیجه پس از گذشتِ نخستین پنجره، شرطِ
 * `time() - $start < $window` برای همیشه نادرست می‌ماند و محدودیت
 * عملاً «هرگز» اعمال نمی‌شد (ارسالِ نامحدود). اکنون با انقضای پنجره،
 * شمارنده و زمانِ شروع از صفر آغاز می‌شوند.
 *
 * هم‌زمانی: read-modify-write زیر قفلِ انحصاریِ همان فایل انجام می‌شود
 * تا درخواست‌های هم‌زمان شمارنده را گم نکنند.
 *
 * @return array{ok:bool, retry:int, error:?string}
 */
function ha_rate_limit_acquire(string $scope, string $ip, int $max, int $window, int $minInterval = 0): array
{
    $dir = storage_dir('rate-limit');
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    ha_rate_limit_gc($dir);

    $key  = substr(hash('sha256', $scope . '|' . $ip), 0, 32);
    $file = $dir . '/' . $key . '.json';

    $fp = @fopen($file, 'c+');
    if ($fp === false) {
        // storage قابلِ نوشتن نیست ⇒ fail closed (محافظت در برابر سوءاستفاده)
        return ['ok' => false, 'retry' => $window, 'error' => 'storage'];
    }
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return ['ok' => false, 'retry' => $window, 'error' => 'lock'];
    }

    $stats = json_decode((string) stream_get_contents($fp), true);
    $now   = time();
    $count = is_array($stats) ? (int) ($stats['n'] ?? 0) : 0;
    $start = is_array($stats) ? (int) ($stats['t'] ?? $now) : $now;
    $last  = is_array($stats) ? (int) ($stats['l'] ?? 0) : 0;

    // بازنشانیِ پنجره — همان سیاستی که config اعلام کرده
    if ($now - $start >= $window) {
        $count = 0;
        $start = $now;
        $last  = 0;
    }

    $ok    = true;
    $retry = 0;
    if ($minInterval > 0 && $last > 0 && ($now - $last) < $minInterval) {
        $ok    = false;
        $retry = $minInterval - ($now - $last);
    } elseif ($count >= $max) {
        $ok    = false;
        $retry = max(1, $window - ($now - $start));
    }

    if ($ok) {
        $count++;
        $last = $now;
    }

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode(['n' => $count, 't' => $start, 'l' => $last]));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return ['ok' => $ok, 'retry' => $retry, 'error' => null];
}

/* ------------------------------------------------------------------ */
/*  دسته‌ها                                                           */
/* ------------------------------------------------------------------ */

function categories(): array { return data('categories'); }
function find_category(string $slug): ?array {
    $slug = slugify($slug);
    foreach (categories() as $cat) if (slugify($cat['slug']??'')=== $slug) return $cat;
    return null;
}
function category_label(string $slug, string $fallback=''): string {
    $c = find_category($slug);
    return $c ? $c['title'] : ($fallback ?: $slug);
}

/* ------------------------------------------------------------------ */
/*  جستجو                                                            */
/* ------------------------------------------------------------------ */

function article_text_index(array $article): string
{
    $parts = [$article['title'] ?? '', $article['excerpt'] ?? '', $article['category'] ?? ''];
    $parts = array_merge($parts, (array)($article['tags'] ?? []));
    foreach ((array)($article['blocks'] ?? []) as $block) {
        if (!is_array($block)) continue;
        if (isset($block['text'])) $parts[] = $block['text'];
        $parts = array_merge($parts, (array)($block['items'] ?? []));
    }
    return mb_strtolower(strip_tags(implode(' ', $parts)), 'UTF-8');
}

/**
 * چکیده‌ی فشرده‌ی متن برای فیلترِ زنده‌ی سمتِ کلاینت.
 *
 * چرا؟ پیش‌تر کلِ متنِ مقاله در صفتِ data-hay روی هر کارت چاپ می‌شد
 * (۱۱٫۷ کیلوبایت فقط در صفحه‌ی مقالات). این یعنی:
 *   • HTML سنگین‌تر و پارسِ کندتر
 *   • متنِ تکراریِ پنهان در DOM که برای سئو هم نویز است
 * اکنون عنوان + حوزه + برچسب‌ها + بخشِ کوتاهی از چکیده می‌آید؛
 * کیفیتِ فیلتر حفظ می‌شود و حجم به کمترِ از یک‌پانزدهم می‌رسد.
 */
function search_haystack(array $item, int $limit = 140): string
{
    $parts = [
        (string) ($item['title'] ?? ''),
        (string) ($item['category'] ?? ''),
        (string) ($item['level'] ?? ''),
        (string) ($item['focus'] ?? ''),
    ];
    $parts = array_merge($parts, (array) ($item['tags'] ?? []));

    $excerpt = (string) ($item['excerpt'] ?? ($item['goal'] ?? ($item['summary'] ?? '')));
    if ($excerpt !== '') {
        $excerpt = trim(strip_tags($excerpt));
        if (mb_strlen($excerpt, 'UTF-8') > $limit) {
            $excerpt = rtrim(mb_substr($excerpt, 0, $limit, 'UTF-8')) . '…';
        }
        $parts[] = $excerpt;
    }

    $parts = array_filter(array_map('trim', $parts), static function ($v) { return $v !== ''; });
    $text  = normalize_persian(implode(' ', array_unique($parts)));

    return mb_substr($text, 0, 320, 'UTF-8');
}

function search_articles(string $query, array $articles, int $limit = 0): array
{
    $needle = normalize_persian($query);
    $words = array_values(array_filter(preg_split('/\s+/', $needle) ?: [], static function($w){ return mb_strlen($w,'UTF-8')>=2; }));
    if ($words===[]) return [];
    $results=[];
    foreach ($articles as $index=>$article) {
        $haystack = normalize_persian(article_text_index($article));
        $title = normalize_persian((string)($article['title']??''));
        $excerpt = normalize_persian((string)($article['excerpt']??''));
        $score=0; $matched=0;
        foreach ($words as $word) {
            $hit=false;
            if (mb_strpos($title,$word,0,'UTF-8')!==false){ $score+=12; $hit=true; }
            if (mb_strpos($excerpt,$word,0,'UTF-8')!==false){ $score+=6; $hit=true; }
            if (mb_strpos($haystack,$word,0,'UTF-8')!==false){ $score+=2; $hit=true; }
            if ($hit) $matched++;
        }
        if ($matched===0) continue;
        if ($matched===count($words)) $score+=5;
        $article['_score']=$score; $article['_index']=$index; $results[]=$article;
    }
    usort($results, static function(array $a,array $b){
        if ($a['_score']===$b['_score']) return $b['_index']<=>$a['_index'];
        return $b['_score']<=>$a['_score'];
    });
    return $limit>0?array_slice($results,0,$limit):$results;
}

function normalize_persian(string $text): string
{
    $text = mb_strtolower($text,'UTF-8');
    $map = ["\u{200c}"=>' ', 'ي'=>'ی','ك'=>'ک','َ'=>'','ِ'=>'','ُ'=>'',"\u{00A0}"=>' ',"\u{200f}"=>'' ];
    $text = strtr($text,$map);
    $text = preg_replace('/[^\p{L}\p{N}\s\-_]/u',' ',$text);
    return trim((string)preg_replace('/\s+/u',' ',(string)$text));
}

function excerpt_of(array $article, int $limit=150): string
{
    $text = (string)($article['excerpt'] ?? '');
    if ($text==='') foreach ((array)($article['blocks']??[]) as $block) if(isset($block['text'])){ $text=(string)$block['text']; break; }
    $text = strip_tags($text);
    return e(mb_strlen($text,'UTF-8')>$limit ? rtrim(mb_substr($text,0,$limit,'UTF-8')).'…' : $text);
}

function find_by_slug(array $items, string $slug): array
{
    $slug = slugify($slug);
    foreach ($items as $index=>$item) if (slugify((string)($item['slug']??''))=== $slug) return [(int)$index,$item];
    return [-1,null];
}

/* ------------------------------------------------------------------ */
/*  ابزارهای خرد                                                      */
/* ------------------------------------------------------------------ */

function active_route(): string { return $GLOBALS['HA_ROUTE'] ?? 'home'; }

/**
 * نگاشتِ حوزه ⇒ آیتمِ ناوبری.
 * تا در صفحه‌ی یک حوزه فقط یک آیتمِ منو نشانه‌ی «صفحه‌ی جاری» بگیرد.
 */
function category_nav_route(string $slug): string
{
    $map = [
        'books'    => 'books',
        'research' => 'research',
        'podcast'  => 'audios',
        'video'    => 'videos',
    ];
    return $map[slugify($slug)] ?? '';
}

/**
 * آیا این آیتمِ ناوبری، صفحه‌ی جاری است؟
 *
 * نکته‌ی مهم: این تابع باید در هر صفحه حداکثر برای یک route مقدار true
 * برگرداند؛ در غیر این صورت چند aria-current="page" در DOM ساخته می‌شود
 * که هم از نظر ARIA نامعتبر است و هم وضعیت منو را اشتباه نشان می‌دهد.
 */
function is_current(string $route): bool
{
    $current = active_route();
    if ($current === $route) {
        return true;
    }
    // زیرصفحه‌ها، والدِ خود را در منو فعال می‌کنند
    if ($current === 'article' && $route === 'articles') {
        return true;
    }
    if (($current === 'course' || $current === 'lesson') && $route === 'courses') {
        return true;
    }
    // صفحه‌ی حوزه: فقط حوزه‌هایی که معادلِ یک بخشِ منو هستند
    if ($current === 'category') {
        return category_nav_route((string) ($GLOBALS['HA_SLUG'] ?? '')) === $route;
    }
    return false;
}

function fa_ordinal(int $n, int $total=0): string
{
    return $total>0 ? fa_num($n).' از '.fa_num($total) : fa_num($n);
}

function storage_dir(string $sub=''): string
{
    $dir = HA_ROOT . '/storage' . ($sub!=='' ? '/'.trim($sub,'/') : '');
    return $dir;
}

function redirect(string $to): void { if(!headers_sent()) header('Location: '.$to); exit; }

function flash(string $type='', string $message=''): array
{
    if (session_status()!==PHP_SESSION_ACTIVE) return [];
    if ($type!==''){ $_SESSION['ha_flash']=['type'=>$type,'message'=>$message]; return []; }
    $flash = $_SESSION['ha_flash'] ?? null; unset($_SESSION['ha_flash']); return is_array($flash)?$flash:[];
}
function old(string $key, string $default=''): string { return isset($_SESSION['ha_old'][$key])?(string)$_SESSION['ha_old'][$key]:$default; }
function old_set(array $values): void { if(session_status()===PHP_SESSION_ACTIVE) $_SESSION['ha_old']=$values; }
function old_clear(): void { if(session_status()===PHP_SESSION_ACTIVE) unset($_SESSION['ha_old']); }
function field_error(string $field, array $errors): string { return isset($errors[$field])?' is-invalid':''; }

function format_duration(int $seconds): string
{
    if ($seconds < 60) return fa_num($seconds) . ' ثانیه';
    $m = intdiv($seconds,60); $s = $seconds % 60;
    return $s ? fa_num($m) . ':' . str_pad(fa_num($s),2,'۰',STR_PAD_LEFT) : fa_num($m) . ' دقیقه';
}
