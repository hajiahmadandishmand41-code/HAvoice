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

/**
 * ناوبریِ اصلیِ هدر.
 *
 * چرا فقط ۸ قلم؟ نسخه‌ی پیشین ۱۰ قلم داشت و در عرض‌های میانی
 * (۱۰۸۰ تا ۱۲۴۰ پیکسل) از کادرِ هدر بیرون می‌زد. سه قلمِ کمتر‌مراجعه
 * (نکته‌ها، درباره‌ی مدرس، تماس) به منویِ «بیشتر» منتقل شدند؛ همان
 * منو حوزه‌های آموزشی را هم نشان می‌دهد.
 */
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
    ];
    $out = [];
    foreach ($routes as $route => $label) {
        $out[] = ['route' => $route, 'label' => $label, 'url' => url($route)];
    }
    return $out;
}

/** قلم‌های «بیشتر» — داخلِ مگا‌منو و انتهایِ کشویِ موبایل. */
function nav_more_items(): array
{
    $routes = [
        'tips'     => ['نکته‌های کوتاه', 'sparkle'],
        'about'    => ['درباره‌ی مدرس', 'user'],
        'comments' => ['نظرات عمومی', 'chat'],
        'contact'  => ['تماس با ما', 'mail'],
    ];
    $out = [];
    foreach ($routes as $route => [$label, $icon]) {
        $out[] = ['route' => $route, 'label' => $label, 'icon' => $icon, 'url' => url($route)];
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

/**
 * شبکه‌های اجتماعی — ادغامِ پیش‌فرضِ data/site.php با تنظیماتِ پنل.
 *
 * مقدارِ ذخیره‌شده در پنل (تنظیمات → شبکه‌های اجتماعی) بر پیش‌فرضِ
 * فایلِ داده اولویت دارد؛ شبکه‌های بدونِ مقدارِ پنلی از همان پیش‌فرض
 * می‌آیند. خروجی: فهرستِ [label, url, icon] به‌ترتیبِ ثابت.
 */
function ha_social_links(): array
{
    $iconMap = [
        'تلگرام'     => 'telegram',
        'اینستاگرام' => 'instagram',
        'فیسبوک'     => 'facebook',
        'واتساپ'     => 'whatsapp',
    ];
    $order = ['تلگرام', 'اینستاگرام', 'فیسبوک', 'واتساپ'];

    $links = [];
    foreach ((array) (ha_site()['social'] ?? []) as $s) {
        $label = (string) ($s['label'] ?? '');
        $url   = (string) ($s['url'] ?? '');
        if ($label === '' || $url === '' || !isset($iconMap[$label])) {
            continue; // شبکه‌ی ناشناخته یا ناقص نمایش داده نمی‌شود
        }
        $links[$label] = ['label' => $label, 'url' => $url, 'icon' => $iconMap[$label]];
    }

    /* بازنویسیِ پنلی — مدیر می‌تواند هر شبکه را عوض کند */
    $admin = function_exists('admin_settings') ? admin_settings() : [];
    $adminFields = ['social_telegram' => 'تلگرام', 'social_instagram' => 'اینستاگرام', 'social_facebook' => 'فیسبوک', 'social_whatsapp' => 'واتساپ'];
    foreach ($adminFields as $field => $label) {
        $url = trim((string) ($admin[$field] ?? ''));
        if ($url !== '') {
            $links[$label] = ['label' => $label, 'url' => $url, 'icon' => $iconMap[$label]];
        }
    }

    /* ترتیبِ ثابت و قابلِ پیش‌بینی */
    $out = [];
    foreach ($order as $label) {
        if (isset($links[$label])) {
            $out[] = $links[$label];
        }
    }
    return $out;
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
        // https: برای تصاویر/رسانه‌هایی که مدیر با نشانیِ معتبر ثبت می‌کند
        // (تصویرِ جلدِ مقاله/کتاب، فایلِ صوت/ویدیوی بیرونی). تصویر/رسانه
        // قادر به اجرای اسکریپت نیست و script-src همچنان 'self' است.
        "img-src 'self' data: https:",
        "media-src 'self' https:",
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
        // auth: مهمان به صفحه‌ی ورود هدایت می‌شود و پس از ورود به همین‌جا برمی‌گردد
        'course'    => ['file' => 'course.php',    'pretty' => 'course',    'title' => 'دوره',   'auth' => true],
        'lesson'    => ['file' => 'lesson.php',    'pretty' => 'lesson',    'title' => 'درس',    'auth' => true],
        'articles'  => ['file' => 'articles.php',  'pretty' => 'articles',  'title' => 'مقالات'],
        'article'   => ['file' => 'article.php',   'pretty' => 'articles',  'title' => 'مقاله'],
        'videos'    => ['file' => 'videos.php',    'pretty' => 'videos',    'title' => 'ویدیوهای آموزشی'],
        'audios'    => ['file' => 'audios.php',    'pretty' => 'audios',    'title' => 'پادکست و صوت'],
        'books'     => ['file' => 'books.php',     'pretty' => 'books',     'title' => 'کتاب‌ها'],
        'research'  => ['file' => 'research.php',  'pretty' => 'research',  'title' => 'تحقیقات'],
        'category'  => ['file' => 'category.php',  'pretty' => 'category',  'title' => 'حوزه آموزشی'],
        'exercises' => ['file' => 'exercises.php', 'pretty' => 'exercises', 'title' => 'تمرین‌ها', 'auth' => true],
        'tips'      => ['file' => 'tips.php',      'pretty' => 'tips',      'title' => 'نکات کوتاه'],
        'about'     => ['file' => 'about.php',     'pretty' => 'about',     'title' => 'درباره مدرس'],
        'contact'   => ['file' => 'contact.php',   'pretty' => 'contact',   'title' => 'تماس با ما', 'session' => true],
        'comments'  => ['file' => 'comments.php',  'pretty' => 'comments',  'title' => 'نظرات عمومی', 'session' => true],
        'search'    => ['file' => 'search.php',    'pretty' => 'search',    'title' => 'جستجو'],
        'login'     => ['file' => 'login.php',     'pretty' => 'login',     'title' => 'ورود',       'session' => true],
        'register'  => ['file' => 'register.php',  'pretty' => 'register',  'title' => 'ثبت‌نام',    'session' => true],
        'logout'    => ['file' => 'logout.php',    'pretty' => 'logout',    'title' => 'خروج',       'session' => true],
        'account'   => ['file' => 'account.php',   'pretty' => 'account',   'title' => 'حساب کاربری', 'session' => true],

        // Admin routes
        'admin'                 => ['file' => 'admin/dashboard.php',      'pretty' => 'admin',         'title' => 'پنل مدیریت',      'admin' => true],
        'admin_courses'         => ['file' => 'admin/courses.php',        'pretty' => 'admin/courses', 'title' => 'مدیریت دوره‌ها',   'admin' => true],
        'admin_course_edit'     => ['file' => 'admin/course_edit.php',    'pretty' => 'admin/course-edit','title' => 'ویرایش دوره',  'admin' => true],
        'admin_course_save'     => ['file' => 'admin/course_save.php',    'pretty' => 'admin/course-save','title' => 'ذخیره دوره',   'admin' => true],
        'admin_course_delete'   => ['file' => 'admin/course_delete.php',  'pretty' => 'admin/course-del', 'title' => 'حذف دوره',    'admin' => true],
        'admin_articles'        => ['file' => 'admin/articles.php',       'pretty' => 'admin/articles','title' => 'مدیریت مقالات',    'admin' => true],
        'admin_article_edit'    => ['file' => 'admin/article_edit.php',   'pretty' => 'admin/article-edit','title' => 'ویرایش مقاله', 'admin' => true],
        'admin_article_save'    => ['file' => 'admin/article_save.php',   'pretty' => 'admin/article-save','title' => 'ذخیره مقاله',  'admin' => true],
        'admin_article_delete'  => ['file' => 'admin/article_delete.php', 'pretty' => 'admin/article-del', 'title' => 'حذف مقاله',   'admin' => true],
        'admin_videos'          => ['file' => 'admin/videos.php',         'pretty' => 'admin/videos',  'title' => 'مدیریت ویدیوها',   'admin' => true],
        'admin_video_edit'      => ['file' => 'admin/video_edit.php',     'pretty' => 'admin/video-edit', 'title' => 'ویرایش ویدیو', 'admin' => true],
        'admin_video_save'      => ['file' => 'admin/video_save.php',     'pretty' => 'admin/video-save', 'title' => 'ذخیره ویدیو',  'admin' => true],
        'admin_video_delete'    => ['file' => 'admin/video_delete.php',   'pretty' => 'admin/video-del',  'title' => 'حذف ویدیو',   'admin' => true],
        'admin_audios'          => ['file' => 'admin/audios.php',         'pretty' => 'admin/audios',  'title' => 'مدیریت صوتها',     'admin' => true],
        'admin_audio_edit'      => ['file' => 'admin/audio_edit.php',     'pretty' => 'admin/audio-edit', 'title' => 'ویرایش صوت',   'admin' => true],
        'admin_audio_save'      => ['file' => 'admin/audio_save.php',     'pretty' => 'admin/audio-save', 'title' => 'ذخیره صوت',    'admin' => true],
        'admin_audio_delete'    => ['file' => 'admin/audio_delete.php',   'pretty' => 'admin/audio-del',  'title' => 'حذف صوت',     'admin' => true],
        'admin_books'           => ['file' => 'admin/books.php',          'pretty' => 'admin/books',   'title' => 'مدیریت کتاب‌ها',    'admin' => true],
        'admin_book_edit'       => ['file' => 'admin/book_edit.php',      'pretty' => 'admin/book-edit', 'title' => 'ویرایش کتاب',   'admin' => true],
        'admin_book_save'       => ['file' => 'admin/book_save.php',      'pretty' => 'admin/book-save', 'title' => 'ذخیره کتاب',    'admin' => true],
        'admin_book_delete'     => ['file' => 'admin/book_delete.php',    'pretty' => 'admin/book-del',   'title' => 'حذف کتاب',    'admin' => true],
        'admin_research'        => ['file' => 'admin/research.php',       'pretty' => 'admin/research','title' => 'مدیریت پژوهش‌ها',   'admin' => true],
        'admin_research_edit'   => ['file' => 'admin/research_edit.php',  'pretty' => 'admin/research-edit','title' => 'ویرایش پژوهش','admin' => true],
        'admin_research_save'   => ['file' => 'admin/research_save.php',  'pretty' => 'admin/research-save','title' => 'ذخیره پژوهش', 'admin' => true],
        'admin_research_delete' => ['file' => 'admin/research_delete.php','pretty' => 'admin/research-del','title' => 'حذف پژوهش',  'admin' => true],
        'admin_exercises'       => ['file' => 'admin/exercises.php',      'pretty' => 'admin/exercises','title' => 'مدیریت تمرین‌ها',   'admin' => true],
        'admin_exercise_edit'   => ['file' => 'admin/exercise_edit.php',  'pretty' => 'admin/exercise-edit','title' => 'ویرایش تمرین','admin' => true],
        'admin_exercise_save'   => ['file' => 'admin/exercise_save.php',  'pretty' => 'admin/exercise-save','title' => 'ذخیره تمرین', 'admin' => true],
        'admin_exercise_delete' => ['file' => 'admin/exercise_delete.php','pretty' => 'admin/exercise-del','title' => 'حذف تمرین',  'admin' => true],
        'admin_tips'            => ['file' => 'admin/tips.php',           'pretty' => 'admin/tips',    'title' => 'مدیریت نکته‌ها',    'admin' => true],
        'admin_tip_edit'        => ['file' => 'admin/tip_edit.php',       'pretty' => 'admin/tip-edit', 'title' => 'ویرایش نکته',   'admin' => true],
        'admin_tip_save'        => ['file' => 'admin/tip_save.php',       'pretty' => 'admin/tip-save', 'title' => 'ذخیره نکته',    'admin' => true],
        'admin_tip_delete'      => ['file' => 'admin/tip_delete.php',     'pretty' => 'admin/tip-del',   'title' => 'حذف نکته',    'admin' => true],
        'admin_categories'      => ['file' => 'admin/categories.php',     'pretty' => 'admin/categories','title' => 'مدیریت حوزه‌ها',  'admin' => true],
        'admin_category_edit'   => ['file' => 'admin/category_edit.php',  'pretty' => 'admin/category-edit','title' => 'ویرایش حوزه','admin' => true],
        'admin_category_save'   => ['file' => 'admin/category_save.php',  'pretty' => 'admin/category-save','title' => 'ذخیره حوزه', 'admin' => true],
        'admin_category_delete' => ['file' => 'admin/category_delete.php','pretty' => 'admin/category-del','title' => 'حذف حوزه',  'admin' => true],
        'admin_users'           => ['file' => 'admin/users.php',          'pretty' => 'admin/users',   'title' => 'مدیریت کاربران',    'admin' => true],
        'admin_user_edit'       => ['file' => 'admin/user_edit.php',      'pretty' => 'admin/user-edit', 'title' => 'ویرایش کاربر', 'admin' => true],
        'admin_user_save'       => ['file' => 'admin/user_save.php',      'pretty' => 'admin/user-save', 'title' => 'ذخیره کاربر',  'admin' => true],
        'admin_user_delete'     => ['file' => 'admin/user_delete.php',    'pretty' => 'admin/user-del',   'title' => 'حذف کاربر',   'admin' => true],
        'admin_messages'        => ['file' => 'admin/messages.php',       'pretty' => 'admin/messages','title' => 'پیام‌های تماس',     'admin' => true],
        'admin_message_view'    => ['file' => 'admin/message_view.php',   'pretty' => 'admin/message-view','title' => 'مشاهده پیام', 'admin' => true],
        'admin_message_delete'  => ['file' => 'admin/message_delete.php', 'pretty' => 'admin/message-del', 'title' => 'حذف پیام',    'admin' => true],
        'admin_comments'        => ['file' => 'admin/comments.php',       'pretty' => 'admin/comments','title' => 'مدیریت نظرات',     'admin' => true],
        'admin_comment_status'  => ['file' => 'admin/comment_status.php', 'pretty' => 'admin/comment-status','title' => 'وضعیت نظر', 'admin' => true],
        'admin_comment_delete'  => ['file' => 'admin/comment_delete.php', 'pretty' => 'admin/comment-del', 'title' => 'حذف نظر',    'admin' => true],
        'admin_settings'        => ['file' => 'admin/settings.php',       'pretty' => 'admin/settings','title' => 'تنظیمات سایت',      'admin' => true],
        'admin_settings_save'   => ['file' => 'admin/settings_save.php',  'pretty' => 'admin/settings-save','title' => 'ذخیره تنظیمات','admin' => true],
        'admin_content_status'  => ['file' => 'admin/content_status.php', 'pretty' => 'admin/content-status','title' => 'تغییر وضعیت انتشار','admin' => true],
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
    /* توکن ۸ ساعت اعتبار دارد؛ پس از آن تازه می‌شود تا توکنِ لو رفته
       برای همیشه قابل استفاده نماند. */
    if (empty($_SESSION['ha_csrf']) || (time() - (int) ($_SESSION['ha_csrf_t'] ?? 0)) > HA_CSRF_TTL) {
        $_SESSION['ha_csrf']   = bin2hex(random_bytes(16));
        $_SESSION['ha_csrf_t'] = time();
    }
    return $_SESSION['ha_csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
function csrf_verify(): bool
{
    $sent = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if ($sent === '' || session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['ha_csrf'])) {
        return false;
    }
    if (!hash_equals((string) $_SESSION['ha_csrf'], $sent)) {
        return false;
    }
    if ((time() - (int) ($_SESSION['ha_csrf_t'] ?? time())) > HA_CSRF_TTL) {   // انقضای توکن
        return false;
    }
    return ha_is_same_origin();          // لایه‌ی دوم: رد کردن cross-origin
}

/**
 * آیا مبدأِ درخواست با میزبانِ سایت یکی است؟
 * Origin/Referer را مرورگر کنترل می‌کند و جعل‌شدنی نیست.
 */
function ha_is_same_origin(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = (string) preg_replace('/:\d+$/', '', $host);
    if ($host === '') {
        return true;
    }
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $key) {
        $raw = trim((string) ($_SERVER[$key] ?? ''));
        if ($raw === '') {
            continue;
        }
        $h = strtolower((string) (parse_url($raw, PHP_URL_HOST) ?: ''));
        return $h !== '' && $h === $host;
    }
    return true;   // مرورگرِ قدیمی بدونِ این سرصفحه‌ها
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
    /* معیار سنجشِ تمرین (اختیاری): پاسِ «فهمیدم که جواب داده» را از
       حسِ کلی به نشانه‌ی قابلِ مشاهده تبدیل می‌کند. */
    $success = empty($block['success'])
        ? ''
        : '<p class="drill__success"><span class="drill__success-icon">' . ha_icon('target', 14) . '</span><span><strong>معیار سنجش:</strong> ' . inline((string)$block['success']) . '</span></p>';
    return '<section class="drill"><header class="drill__head"><h3>' . e($block['title'] ?? 'تمرین') . '</h3>' . (empty($block['time']) ? '' : '<span class="chip chip--ghost">' . minutes_label((int)$block['time']) . '</span>') . '</header><ul class="drill__list">' . $items . '</ul>' . $success . (empty($block['note']) ? '' : '<p class="drill__note">' . inline((string)$block['note']) . '</p>') . '</section>';
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
function ha_rate_limit_gc(string $dir, bool $force = false): void
{
    /* در هر درخواست فقط یک بار اجرا می‌شود (هزینه‌ی I/O)؛ ابزارِ
       خودآزمایی می‌تواند با $force آن را مجبور به اجرا کند. */
    static $ran = false;
    if ($ran && !$force) {
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
function ha_rate_limit_acquire(string $scope, string $ip, int $max, int $window, int $minInterval = 0, ?string $dir = null): array
{
    $dir = $dir ?? storage_dir('rate-limit');
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

    return ['ok' => $ok, 'retry' => $retry, 'error' => null, 'count' => $count];
}

/* ------------------------------------------------------------------ */
/*  دسته‌ها                                                           */
/* ------------------------------------------------------------------ */

/**
 * ادغامِ محتوای پایه‌ی فایل با محتوای پنل، بر پایه‌ی کلیدِ یکتا.
 *
 * قاعده: اگر مدیر موردی را با همان slug/id ذخیره کرده باشد، نسخه‌ی پنل
 * «در همان جای» نسخه‌ی فایل جایگزین می‌شود (نه اینکه هر دو نمایش داده
 * شوند و نسخه‌ی قدیمی همیشه برنده شود). مواردِ تازه‌ی پنل به انتهای
 * فهرست اضافه می‌شوند. بدین‌ترتیب فایلِ پایه محفوظ می‌ماند و ویرایشِ
 * مدیر هم واقعاً اعمال می‌شود.
 */
function ha_merge_overrides(array $base, array $overrides, string $key = 'slug'): array
{
    $positions = [];
    foreach ($base as $i => $item) {
        if (!is_array($item)) continue;
        $k = slugify((string) ($item[$key] ?? ''));
        if ($k !== '') $positions[$k] = $i;
    }

    $used = [];
    foreach ($overrides as $item) {
        if (!is_array($item)) continue;
        $k = slugify((string) ($item[$key] ?? ''));
        if ($k === '') continue;
        $used[$k] = true;
        if (isset($positions[$k])) {
            $base[$positions[$k]] = $item;  // جایگزینی در همان جایگاه
        }
    }
    foreach ($overrides as $item) {
        if (!is_array($item)) continue;
        $k = slugify((string) ($item[$key] ?? ''));
        if ($k === '' || !isset($positions[$k])) {
            $base[] = $item;                // موردِ تازه‌ی پنل
            if ($k !== '') $positions[$k] = count($base) - 1;
        }
    }
    return array_values($base);
}

/**
 * آیا این محتوا «منتشرشده» است؟
 * مقدارِ پیش‌فرض (نبودِ کلیدِ status) منتشر است تا محتوای موجودِ پایه
 * هیچ تغییری در رفتارش نبیند. مدیر می‌تواند status=draft یا hidden=1
 * بگذارد تا محتوا از دیدِ عمومی مخفی شود (بدونِ حذف).
 */
function ha_is_published(array $item): bool
{
    if (!empty($item['hidden'])) return false;
    $status = strtolower(trim((string) ($item['status'] ?? 'published')));
    return $status !== 'draft' && $status !== 'hidden' && $status !== 'unlisted';
}

/** فقط مواردِ منتشرشده (با حفظِ ترتیب). */
function ha_visible(array $items): array
{
    return array_values(array_filter($items, 'ha_is_published'));
}

/**
 * فهرستِ کاملِ یک نوع محتوا برای «پنل مدیریت»: ادغامِ فایل و پنل با
 * اعمالِ بازنویسی‌ها، بدونِ فیلترِ انتشار (مدیر باید پیش‌نویس‌ها را هم
 * ببیند). نسخه‌ی عمومیِ همان فهرست را توابعِ content.php با همان نامِ
 * کوتاه (articles() و…) برمی‌گردانند.
 */
function ha_content_admin(string $name, string $key = 'slug'): array
{
    $name = (string) preg_replace('/[^a-z_]/', '', strtolower($name));
    return ha_merge_overrides(data($name), admin_load($name), $key);
}

/**
 * نامکِ حوزه‌ی مؤثرِ یک محتوا.
 * اولویت با فیلدِ اتصالِ مستقیمِ پنل (`field`) است؛ بعد فیلدِ category
 * اگر خودش نامکِ حوزه باشد؛ و در غیر این صورت نگاشتِ موضوعِ فارسی
 * (find_category_by_title). اگر هیچ‌کدام نشد ''.
 */
function ha_item_field_slug(array $item): string
{
    $field = slugify((string) ($item['field'] ?? ''));
    if ($field !== '' && find_category($field) !== null) {
        return $field;
    }
    $catSlug = slugify((string) ($item['category'] ?? ''));
    if ($catSlug !== '' && find_category($catSlug) !== null) {
        return $catSlug;
    }
    $mapped = find_category_by_title((string) ($item['category'] ?? ''));
    return $mapped !== null ? (string) ($mapped['slug'] ?? '') : '';
}

/* ------------------------------------------------------------------ */
/*  بازگشت پس از ورود (next)                                           */
/* ------------------------------------------------------------------ */

/**
 * اعتبارسنجیِ نشانیِ «بازگشت پس از ورود».
 * فقط نشانی‌های نسبیِ داخلی پذیرفته می‌شوند؛ هر طرحِ مطلق، protocol-relative
 * یا کاراکترِ کنترلی رد می‌شود تا open-redirect بسته بماند.
 */
function ha_safe_next(string $next): string
{
    $next = trim(str_replace('\\', '/', $next));
    if ($next === '' || strlen($next) > 400) {
        return '';
    }
    if (preg_match('/[\\x00-\\x1F\\x7F]/', $next)) {
        return '';
    }
    if (str_starts_with($next, '//')) {
        return '';
    }
    if (preg_match('#^[a-z][a-z0-9+.\\-]*:#i', $next)) {
        return ''; // هر طرحی (http:, javascript:, …) ممنوع است
    }
    if ($next[0] !== '/' && !str_starts_with($next, 'index.php')) {
        return '';
    }
    return $next;
}

/**
 * نشانیِ جاریِ درخواست به‌صورتِ نسبیِ امن — برای پر کردنِ next هنگامِ
 * هدایتِ مهمان به صفحه‌ی ورود.
 */
function ha_current_request_url(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $uri = (string) preg_replace('/[\\x00-\\x1F\\x7F]/', '', $uri);
    if ($uri !== '' && $uri[0] === '/' && !str_starts_with($uri, '//')) {
        return substr($uri, 0, 400);
    }
    /* وقتی REQUEST_URI قابلِ اتکا نیست، از مسیر+نامک بازسازی می‌کنیم. */
    $route = active_route();
    $slug  = (string) ($GLOBALS['HA_SLUG'] ?? '');
    $params = $slug !== '' ? ['slug' => $slug] : [];
    return url($route === '404' ? 'home' : $route, $params);
}

function categories(): array { return ha_visible(repo_categories()); }
/** همه حوزه‌ها بدون فیلتر انتشار (پنل). */
function categories_all(): array { return repo_categories(); }
function find_category(string $slug): ?array {
    $slug = slugify($slug);
    foreach (categories() as $cat) if (slugify($cat['slug']??'')=== $slug) return $cat;
    return null;
}
/** حوزه حتی اگر پیش‌نویس — برای اعتبارسنجی Admin. */
function find_category_any(string $slug): ?array {
    $slug = slugify($slug);
    $list = function_exists('categories_all') ? categories_all() : categories();
    foreach ($list as $cat) {
        if (slugify((string)($cat['slug'] ?? '')) === $slug) return $cat;
    }
    return null;
}
function category_label(string $slug, string $fallback=''): string {
    $c = find_category($slug);
    return $c ? $c['title'] : ($fallback ?: $slug);
}

/**
 * پیدا کردنِ «حوزه» از روی نامِ فارسیِ موضوع.
 *
 * چرا لازم است؟ مقاله‌ها/کتاب‌ها/پژوهش‌ها در داده‌ها یک موضوعِ فارسیِ
 * آزاد دارند (مثل «صدا و نفس») نه slugِ حوزه. برای اینکه هر کارت رنگ و
 * آیکونِ هماهنگِ همان حوزه را بگیرد، این تابع:
 *   ۱) تطبیقِ دقیق با title یا shortِ حوزه‌ها
 *   ۲) نگاشتِ کلیدواژه‌ایِ موضوع → حوزه
 *   ۳) در غیر این صورت یک انتخابِ پایدار (هشِ موضوع) از فهرستِ حوزه‌ها
 * را برمی‌گرداند تا رنگِ کارت‌ها بینِ بارگذاری‌ها یکسان بماند.
 */
function find_category_by_title(string $label): ?array
{
    $label = trim($label);
    if ($label === '') {
        return null;
    }
    $cats = categories();
    if ($cats === []) {
        return null;
    }

    /* ۱) تطبیقِ دقیق */
    foreach ($cats as $cat) {
        if (($cat['title'] ?? '') === $label || ($cat['short'] ?? '') === $label) {
            return $cat;
        }
    }
    $bySlug = find_category($label);
    if ($bySlug !== null) {
        return $bySlug;
    }

    /* ۲) نگاشتِ کلیدواژه‌ای */
    static $map = [
        'public-speaking' => ['صدا', 'نفس', 'تنفس', 'بیان', 'سخنوری', 'زبان بدن', 'ساختار کلام', 'کلام', 'مکث', 'بداهه'],
        'communication'   => ['گوش', 'ارتباط', 'همدلی', 'بازخورد', 'گفت‌وگو', 'پیام'],
        'psychology'      => ['اضطراب', 'استرس', 'اعتمادبه‌نفس', 'اعتماد به نفس', 'روان', 'هیجان', 'خودشناسی', 'ذهن'],
        'success'         => ['عادت', 'انضباط', 'رشد', 'تمرین', 'بازخورد', 'تاب‌آوری', 'پیشرفت'],
        'goals-time'      => ['هدف', 'زمان', 'برنامه', 'اولویت', 'تمرکز'],
        'negotiation'     => ['مذاکره', 'جلسه', 'متقاعد', 'چانه'],
        'career'          => ['کار', 'شغل', 'حرفه', 'رزومه', 'مصاحبه', 'سازمان'],
        'research'        => ['پژوهش', 'تحقیق', 'مطالعه', 'منبع'],
        'books'           => ['کتاب', 'خلاصه'],
        'podcast'         => ['پادکست', 'صوت'],
        'video'           => ['ویدیو', 'فیلم'],
        'life-skills'     => ['زندگی', 'تصمیم', 'حل مسئله', 'مسئله'],
    ];
    foreach ($map as $slug => $keys) {
        foreach ($keys as $key) {
            if (mb_strpos($label, $key, 0, 'UTF-8') !== false) {
                $hit = find_category($slug);
                if ($hit !== null) {
                    return $hit;
                }
                break;
            }
        }
    }

    /* ۳) انتخابِ پایدار بر اساسِ هشِ موضوع */
    $index = (int) (hexdec(substr(md5($label), 0, 6)) % count($cats));
    return $cats[$index];
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
    static $base = null;
    if ($base === null) {
        $base = HA_STORAGE_PATH !== '' ? rtrim(HA_STORAGE_PATH, '/') : (HA_ROOT . '/storage');
        /* روی استقرارهای read-only (مثل Vercel) پوشه‌ی storage همراهِ کد
           قابلِ نوشتن نیست؛ به‌جای شکست، به پوشه‌ی موقتِ سیستم برمی‌گردیم تا
           نشست، فرم تماس و حسابِ کاربری همچنان کار کنند. */
        if (!is_dir($base) || !is_writable($base)) {
            $tmp = function_exists('sys_get_temp_dir') ? rtrim((string) sys_get_temp_dir(), '/') : '';
            if ($tmp !== '' && is_dir($tmp) && is_writable($tmp)) {
                $alt = $tmp . '/havoice-storage';
                if (!is_dir($alt)) {
                    @mkdir($alt, 0700, true);
                }
                if (is_dir($alt) && is_writable($alt)) {
                    $base = $alt;
                }
            }
        }
    }
    return $base . ($sub !== '' ? '/' . trim($sub, '/') : '');
}

/* ------------------------------------------------------------------ */
/*  ذخیره‌سازیِ داده‌ی پنلِ مدیریت (JSON در storage/admin)              */
/*                                                                    */
/*  این دو تابع عمداً «اینجا» و نه در auth.php هستند: هیچ وابستگی به    */
/*  احرازِ هویت ندارند، فقط به storage_dir() تکیه می‌کنند، و هم          */
/*  helpers.php (categories) و هم content.php (articles, books,        */
/*  media, courses, …) به آن‌ها نیاز دارند. گذاشتنشان در auth.php       */
/*  باعث می‌شد هر نقطه‌ی ورودِ مستقلی که auth.php را بار نمی‌کند — مثلِ  */
/*  sitemap.php — با «Call to undefined function admin_load()» و        */
/*  پاسخِ ۵۰۰ از کار بیفتد.                                           */
/* ------------------------------------------------------------------ */

/** ذخیره‌ی داده‌ی JSON در storage */
function admin_store(string $name, array $data): bool
{
    $file = storage_dir('admin') . '/' . $name . '.json';
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $tmp = $file . '.tmp-' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
        $ok = @rename($tmp, $file);
        /*
         * پاک‌سازی فقط وقتی لازم است که rename ناموفق بوده باشد.
         *
         * پیش‌تر @unlink($tmp) بی‌قید و شرط اجرا می‌شد، ولی بعد از یک
         * rename موفق فایلِ موقت «دیگر وجود ندارد» — پس هر بار یک هشدارِ
         * «No such file or directory» برمی‌خاست. عملگرِ @ فقط مقدارِ
         * بازگشتی را خفه می‌کند و خودِ هشدار همچنان برانگیخته می‌شود: در
         * لاگِ خطا می‌نشیند و هر set_error_handler() آن را می‌گیرد (که
         * باعث می‌شد در آزمون‌ها به‌اشتباه «خطای PHP» گزارش شود).
         */
        if (!$ok && is_file($tmp)) {
            @unlink($tmp);
        }
        return $ok;
    }
    if (is_file($tmp)) {
        @unlink($tmp);
    }
    return false;
}

/** بارگذاری داده‌ی JSON از storage */
function admin_load(string $name): array
{
    $file = storage_dir('admin') . '/' . $name . '.json';
    if (!is_file($file)) return [];
    $raw = @file_get_contents($file);
    if (!is_string($raw)) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function redirect(string $to): void
{
    /* نشانیِ هدف برای ابزارهای تست و لاگ در دسترس باشد؛ در تولید بدون اثر است. */
    $GLOBALS['HA_REDIRECT_TO'] = $to;

    /*
     * «اول» نشست را می‌بندیم، بعد سرصفحه را می‌فرستیم.
     *
     * این ترتیب برایِ الگوی PRG حیاتی است: هر handler اول flash() می‌زند و
     * بعد redirect() می‌کند، و redirect با exit پایان می‌دهد — یعنی هرگز به
     * session_write_close() انتهای bootstrap نمی‌رسد. اگر نشست فقط در
     * خاموشیِ اسکریپت نوشته شود (که روی برخی SAPIها و میزبانی‌های اشتراکی
     * اتکاپذیر نیست)، پیامِ flash و خودِ ورودِ کاربر بی‌صدا گم می‌شود:
     * کاربر رمز را درست می‌زند ولی به صفحه‌ی ورود برمی‌گردد، بی‌آنکه
     * پیامی ببیند. نوشتنِ صریح، پیش از exit، این را قطعی می‌کند.
     *
     * $_SESSION پس از بستن هم در حافظه خواندنی/نوشتنی می‌ماند، فقط دیگر به
     * ذخیره‌ساز متصل نیست — پس چیزی را برای ادامه‌ی اجرا خراب نمی‌کند.
     */
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    /*
     * دور ریختنِ خروجیِ بافرشده.
     *
     * bootstrap کلِ رندر را بافر می‌کند، پس ممکن است بخشی از هدر/بدنه
     * «نوشته» ولی هنوز «فرستاده» نشده باشد. تا وقتی بافر باز است
     * headers_sent() ناراست است و می‌توانیم سرصفحه‌ی Location را بفرستیم —
     * به‌شرطِ آنکه محتوایِ بافرشده را دور بریزیم، وگرنه کلاینت هم ۳۰۲
     * می‌گیرد و هم HTML ناقصِ صفحه‌ای که نیمه‌کاره رها شده.
     */
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        header('Location: ' . $to, true, 302);
    } elseif (!empty($GLOBALS['HA_TEST_NO_EXIT'])) {
        // حالتِ تست: سرصفحه قابلِ ارسال نیست، فقط هدف ثبت می‌شود.
    } else {
        /*
         * واپسین لایه‌ی اطمینان: اگر به هر دلیلی (بافرِ بسته در پیکربندیِ
         * خاصِ میزبان، یا خروجیِ پیش از redirect) سرصفحه‌ها رفته باشند،
         * کاربر را روی یک صفحه‌ی خالی و بی‌پیوند رها نمی‌کنیم.
         */
        $safe = e($to);
        echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8">'
            . '<meta http-equiv="refresh" content="0;url=' . $safe . '">'
            . '<title>در حالِ انتقال…</title></head><body>'
            . '<p>در حالِ انتقال به <a href="' . $safe . '">' . $safe . '</a>…</p>'
            . '</body></html>';
    }

    /* فقط برای تستِ خودکار: به‌جای exit یک استثنا پرتاب می‌شود تا اجرایِ
       اسکریپت بلافاصله متوقف شود (دقیقاً مثل exit) ولی مهارِ آن در harness
       ممکن باشد. در تولید هرگز اجرا نمی‌شود. */
    if (!empty($GLOBALS['HA_TEST_NO_EXIT'])) {
        throw new \RuntimeException('HA_REDIRECT');
    }
    exit;
}

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
