<?php
/**
 * HAvoice — حساب کاربری (file-based، بدون دیتابیس)
 *
 * تصمیم‌های امنیتی:
 *  • رمز عبور فقط با password_hash() (bcrypt/argon پیش‌فرضِ PHP) ذخیره می‌شود
 *    و با password_verify() بررسی می‌شود؛ هیچ‌وقت plaintext نگه نمی‌داریم.
 *  • ایمیل به‌صورت lowercase + trim نرمال‌سازی می‌شود تا ثبتِ تکراری
 *    (با حروفِ بزرگ/کوچک یا فاصله‌ی اضافه) ممکن نباشد.
 *  • نوشتنِ کاربران با flock اتمیک است تا دو ثبتِ همزمان همدیگر را پاک نکنند.
 *  • ورود با session_regenerate_id() همراه است (ضد session fixation).
 *  • هیچ اطلاعاتِ حساسی (هشِ رمز، ایمیلِ دیگران) به قالب نشت نمی‌کند.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  ذخیره‌ی کاربران                                                   */
/* ------------------------------------------------------------------ */

/** مسیرِ فایلِ کاربران (JSON). */
function auth_users_file(): string
{
    return storage_dir() . '/users.json';
}

/** بارگذاریِ فهرستِ کاربران. در هر حالتِ خرابی، آرایه‌ی خالی برمی‌گردد. */
function auth_load_users(): array
{
    $file = auth_users_file();
    if (!is_file($file)) {
        return [];
    }
    $raw = @file_get_contents($file);
    if (!is_string($raw)) {
        return [];
    }
    $users = json_decode($raw, true);
    return is_array($users) ? $users : [];
}

/**
 * ذخیره‌ی اتمیکِ کاربران.
 * خواندن-تغییر-نوشتن زیر قفلِ انحصاریِ همان فایل انجام می‌شود و در پایان
 * با rename به نسخه‌ی موقت جایگزین می‌شود تا فایل هرگز نیمه‌نوشته نماند.
 */
function auth_save_users(array $users): bool
{
    $file = auth_users_file();
    $dir  = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        return false;
    }

    $json = json_encode($users, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }

    $tmp = $file . '.tmp-' . bin2hex(random_bytes(4));
    $fp  = @fopen($file, 'c+');
    if ($fp === false) {
        return false;
    }

    $ok = false;
    if (flock($fp, LOCK_EX)) {
        if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
            $ok = @rename($tmp, $file);
        }
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    @unlink($tmp);
    return $ok;
}

/* ------------------------------------------------------------------ */
/*  جستجو و ساختِ کاربر                                                */
/* ------------------------------------------------------------------ */

/** نرمال‌سازیِ ایمیل: trim + lowercase (جلوگیری از ثبتِ تکراری). */
function auth_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function auth_find_user_by_email(string $email): ?array
{
    $email = auth_normalize_email($email);
    foreach (auth_load_users() as $user) {
        if (isset($user['email']) && auth_normalize_email((string) $user['email']) === $email) {
            return $user;
        }
    }
    return null;
}

function auth_find_user_by_id(string $id): ?array
{
    foreach (auth_load_users() as $user) {
        if (($user['id'] ?? '') === $id) {
            return $user;
        }
    }
    return null;
}

/**
 * اعتبارسنجیِ کاملِ ثبت‌نام. همه‌ی خطاها را به‌صورتِ [field => message]
 * برمی‌گرداند؛ اگر خالی بود یعنی ورودی معتبر است.
 */
function auth_validate_registration(string $name, string $email, string $password, string $confirm): array
{
    $errors = [];

    $name = trim($name);
    $nameLen = mb_strlen($name, 'UTF-8');
    if ($nameLen < 2 || $nameLen > 60) {
        $errors['name'] = 'نام را کامل بنویسید (بین ۲ تا ۶۰ نویسه).';
    }

    $email = auth_normalize_email($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
        $errors['email'] = 'نشانی ایمیل معتبر نیست.';
    } elseif (auth_find_user_by_email($email) !== null) {
        $errors['email'] = 'این ایمیل قبلاً ثبت شده است. اگر حساب دارید وارد شوید.';
    }

    $passLen = strlen($password);   // بایت — برای سقفِ bcrypt مهم است
    if ($passLen < HA_AUTH_MIN_PASSWORD) {
        $errors['password'] = 'رمز عبور باید حداقل ' . fa_num(HA_AUTH_MIN_PASSWORD) . ' نویسه باشد.';
    } elseif ($passLen > 72) {
        $errors['password'] = 'رمز عبور حداکثر ۷۲ نویسه می‌تواند باشد.';
    }

    if ($password !== $confirm) {
        $errors['confirm'] = 'تکرار رمز عبور با رمز عبور یکسان نیست.';
    }

    return $errors;
}

/**
 * ساختِ کاربرِ جدید با هشِ امن.
 * @return array{ok:bool, user:?array, error:?string}
 */
function auth_create_user(string $name, string $email, string $password): array
{
    $users = auth_load_users();
    $user  = [
        'id'         => bin2hex(random_bytes(16)),
        'name'       => trim($name),
        'email'      => auth_normalize_email($email),
        'pass_hash'  => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('c'),
    ];
    $users[] = $user;

    if (!auth_save_users($users)) {
        return ['ok' => false, 'user' => null, 'error' => 'storage'];
    }
    return ['ok' => true, 'user' => $user, 'error' => null];
}

/** بررسیِ رمز عبور با password_verify() (با rehash خودکار در صورتِ نیاز). */
function auth_verify_credentials(string $email, string $password): ?array
{
    $user = auth_find_user_by_email($email);
    if ($user === null || !isset($user['pass_hash'])) {
        return null;
    }
    if (!password_verify($password, (string) $user['pass_hash'])) {
        return null;
    }
    if (password_needs_rehash((string) $user['pass_hash'], PASSWORD_DEFAULT)) {
        auth_update_password((string) $user['id'], $password);
    }
    return $user;
}

function auth_update_password(string $id, string $password): bool
{
    $users = auth_load_users();
    $changed = false;
    foreach ($users as $i => $user) {
        if (($user['id'] ?? '') === $id) {
            $users[$i]['pass_hash'] = password_hash($password, PASSWORD_DEFAULT);
            $changed = true;
            break;
        }
    }
    return $changed && auth_save_users($users);
}

/* ------------------------------------------------------------------ */
/*  نشست: ورود، خروج، کاربرِ جاری                                      */
/* ------------------------------------------------------------------ */

function auth_login(array $user): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    /* شناسه‌ی نشست پس از ورود عوض می‌شود تا fixation ممکن نباشد. */
    session_regenerate_id(true);
    $_SESSION['ha_user_id']   = (string) $user['id'];
    $_SESSION['ha_user_name'] = (string) ($user['name'] ?? '');
    $_SESSION['ha_login_at']  = time();
    /* توکنِ CSRF قبلی با نشستِ جدید معتبر نیست؛ دوباره ساخته می‌شود. */
    unset($_SESSION['ha_csrf'], $_SESSION['ha_csrf_t']);
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function auth_current_user(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }
    $id = $_SESSION['ha_user_id'] ?? null;
    if (!is_string($id) || $id === '') {
        return null;
    }
    $user = auth_find_user_by_id($id);
    return $user !== null ? $user : null;
}

function auth_is_logged_in(): bool
{
    return auth_current_user() !== null;
}

/** محافظت از صفحه‌های کاربر: اگر وارد نشده باشد، به ورود هدایت می‌شود. */
function auth_require(string $redirectUrl = ''): void
{
    if (auth_is_logged_in()) {
        return;
    }
    $to = $redirectUrl !== '' ? $redirectUrl : url('login');
    flash('error', 'برای مشاهده‌ی این صفحه ابتدا وارد حساب کاربری شوید.');
    redirect($to);
}

/* ------------------------------------------------------------------ */
/*  محدودیت نرخِ ورود/ثبت‌نام                                          */
/* ------------------------------------------------------------------ */

/**
 * ضد brute-force: شمارنده‌ی ورود/ثبت‌نام بر پایه‌ی IP (با همان موتورِ
 * محدودیت نرخِ فرم تماس، ولی scope و سقفِ جداگانه).
 * @return array{ok:bool, retry:int, error:?string}
 */
function auth_rate_limit(string $scope): array
{
    $ip    = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $max   = $scope === 'login' ? (int) HA_AUTH_LOGIN_RATE_LIMIT_MAX : (int) HA_AUTH_REGISTER_RATE_LIMIT_MAX;
    return ha_rate_limit_acquire(
        'auth-' . $scope,
        $ip,
        $max,
        (int) HA_AUTH_RATE_LIMIT_WINDOW,
        (int) HA_RATE_LIMIT_MIN_INTERVAL
    );
}

/** حرفِ اولِ نام برای آواتار (بدونِ آپلودِ تصویر). */
function auth_initial(string $name): string
{
    $first = mb_substr(trim($name), 0, 1, 'UTF-8');
    return $first !== '' ? $first : '؟';
}

/* ------------------------------------------------------------------ */
/*  مدیر سایت (Admin)                                                 */
/* ------------------------------------------------------------------ */

/** آیا کاربر جاری مدیر است؟ */
function auth_is_admin(): bool
{
    $user = auth_current_user();
    if ($user === null) return false;
    // اولین کاربر ثبت‌نام‌شده همیشه مدیر است
    $users = auth_load_users();
    if ($users !== [] && ($users[0]['id'] ?? '') === ($user['id'] ?? '')) return true;
    return !empty($user['role']) && $user['role'] === 'admin';
}

/** محافظت از صفحه‌های مدیریت */
function auth_require_admin(): void
{
    if (!auth_is_logged_in()) {
        flash('error', 'ابتدا وارد حساب کاربری شوید.');
        redirect(url('login'));
    }
    if (!auth_is_admin()) {
        flash('error', 'شما دسترسی مدیریت ندارید.');
        redirect(url('home'));
    }
}

/** تغییر نقش کاربر */
function auth_set_role(string $id, string $role): bool
{
    $users = auth_load_users();
    $changed = false;
    foreach ($users as $i => $user) {
        if (($user['id'] ?? '') === $id) {
            $users[$i]['role'] = $role;
            $changed = true;
            break;
        }
    }
    return $changed && auth_save_users($users);
}

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
        @unlink($tmp);
        return $ok;
    }
    @unlink($tmp);
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

/** بارگذاری تنظیمات سایت ذخیره‌شده توسط مدیر */
function admin_settings(): array
{
    return admin_load('settings');
}

/**
 * نامِ مدرس/برند — مقدارِ ذخیره‌شده در پنل بر ثابتِ پیکربندی اولویت دارد.
 *
 * چرا این لایه لازم بود؟ فرمِ «تنظیمات» فیلدهای name و tagline را
 * ذخیره می‌کرد، ولی هیچ‌کجای سایت آن‌ها را نمی‌خواند: هدر، فوتر،
 * meta/JSON-LD و صفحه‌های درباره/تماس/خانه همه مستقیماً از ثابت‌های
 * HA_NAME و HA_TAGLINE استفاده می‌کردند. یعنی مدیر نام را عوض می‌کرد،
 * پیامِ «ذخیره شد» می‌گرفت و هیچ تغییری در سایت نمی‌دید — یک کنترلِ
 * ناقص. اکنون هر دو از اینجا می‌آیند.
 *
 * کشِ static: این تابع در ده‌ها نقطه از یک صفحه صدا زده می‌شود و
 * admin_load() هر بار فایل را از دیسک می‌خواند؛ یک بار در هر درخواست
 * کافی است (تنظیمات وسطِ درخواست عوض نمی‌شود — handler بلافاصله
 * redirect می‌کند).
 */
function ha_site_name(): string
{
    static $cached = null;
    if ($cached === null) {
        $value = trim((string) (admin_settings()['name'] ?? ''));
        $cached = $value !== '' ? $value : HA_NAME;
    }
    return $cached;
}

/** عنوان/شغلِ مدرس — همان منطقِ ha_site_name(). */
function ha_site_tagline(): string
{
    static $cached = null;
    if ($cached === null) {
        $value = trim((string) (admin_settings()['tagline'] ?? ''));
        $cached = $value !== '' ? $value : HA_TAGLINE;
    }
    return $cached;
}

/**
 * برندِ کامل: «نام | عنوان».
 *
 * معادلِ پویایِ ثابتِ HA_BRAND_FULL. آن ثابت از مقدارِ پیش‌فرضِ
 * config ساخته می‌شد و با تغییرِ name/tagline در پنل به‌روز نمی‌شد، پس
 * og:site_name، og:image:alt، aria-label برند، نامِ JSON-LD و <title>
 * صفحه‌ی اصلی همه روی نامِ قدیم می‌ماندند در حالی که هدر و فوتر نامِ
 * تازه را نشان می‌دادند — ناسازگاریِ دیده‌شدنی.
 */
function ha_brand_full(): string
{
    return ha_site_name() . ' | ' . ha_site_tagline();
}

/**
 * داده‌ی سایت با هویتِ اعمال‌شده از پنل.
 *
 * data/site.php متنِ نویسه‌شده است و نامِ مدرس در چند جای آن «داخلِ
 * جمله» آمده (abریوِ هیرو، عنوانِ بخشِ «چرا»، برچسبِ دکمه‌ی «درباره»،
 * footer_about). وقتی مدیر نام را در پنل عوض می‌کرد، هدر/فوتر/meta به
 * نامِ تازه می‌رفتند ولی این متن‌ها روی نامِ قدیم می‌ماندند ⇒ یک صفحه‌ی
 * واحد با دو نامِ متفاوت.
 *
 * راه‌حل: جایگزینیِ «دقیقِ» نامِ پیش‌فرض (HA_NAME) با نامِ مؤثر، به‌اضافه‌ی
 * بازنویسیِ صریحِ فیلدهای ساخت‌یافته‌ی هویت. چون فقط همان رشته‌ی ثابتِ
 * پیش‌فرض جایگزین می‌شود، بقیه‌ی متن دست‌نخورده و قابلِ پیش‌بینی می‌ماند
 * (مثلاً عبارتِ «مدرس و پژوهشگر» داخلِ بیوگرافی عوض نمی‌شود). اگر در پنل
 * چیزی ذخیره نشده باشد، ha_site_name() === HA_NAME و این تابع عملاً
 * همان data('site') را برمی‌گرداند.
 *
 * @return array<string, mixed>
 */
function ha_site(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $site = data('site');
    $name = ha_site_name();
    $role = ha_site_tagline();

    if ($name !== HA_NAME) {
        /* جایگزینیِ نام در متن‌های نویسه‌شده — بازگشتی روی کلِ درخت. */
        $walk = static function ($node) use (&$walk, $name) {
            if (is_string($node)) {
                return str_replace(HA_NAME, $name, $node);
            }
            if (is_array($node)) {
                foreach ($node as $k => $v) {
                    $node[$k] = $walk($v);
                }
            }
            return $node;
        };
        $site = $walk($site);
    }

    /* فیلدهای ساخت‌یافته‌ی هویت: صریح و بدونِ ابهام */
    if (isset($site['instructor']) && is_array($site['instructor'])) {
        $site['instructor']['name'] = $name;
        $site['instructor']['role'] = $role;
    }
    if (isset($site['hero']) && is_array($site['hero']) && !empty($site['hero']['eyebrow'])) {
        $site['hero']['eyebrow'] = $name . ' — ' . $role;
    }

    return $cached = $site;
}

/* ------------------------------------------------------------------ */
/*  پیام‌های تماس                                                       */
/*                                                                    */
/*  چرا این لایه لازم بود؟ پیام‌ها در «دو» جا ذخیره می‌شوند: فرمِ تماس   */
/*  در storage/messages/messages.csv می‌نویسد (append-only) و پنل در     */
/*  storage/admin/messages.json. صفحه‌ی فهرست این دو را به هم می‌چسباند  */
/*  و بعد array_reverse() می‌کرد، ولی «همان» شمارنده‌ی معکوس را به‌عنوان   */
/*  شناسه به message_view و message_delete می‌فرستاد. آن دو فایل یکی      */
/*  آرایه‌ی «بدونِ reverse» را ایندکس می‌زد و دیگری فقط آرایه‌ی پنل را —    */
/*  پس «مشاهده» پیامِ اشتباه را باز می‌کرد و «حذف» رکوردِ اشتباه را        */
/*  می‌برد (یا هیچ). اکنون هر پیام یک ref پایدار دارد: csv-N یا pan-N.   */
/*  از خطِ تیره استفاده می‌کنیم نه دونقطه، چون در حالتِ HA_PRETTY_URLS   */
/*  فیلترِ bootstrap کاراکترهای غیرِ [A-Za-z0-9_/\-] را از مسیر پاک      */
/*  می‌کند و دونقطه باعثِ شکستنِ پیوندِ مشاهده می‌شد.                    */
/* ------------------------------------------------------------------ */

/** مسیرِ فایلِ CSV پیام‌های تماس. */
function contact_messages_file(): string
{
    return storage_dir('messages') . '/messages.csv';
}

/** خواندنِ پیام‌های CSV به‌صورتِ آرایه‌ی انجمنی. در هر خرابی: آرایه‌ی خالی. */
function contact_messages_read(): array
{
    $file = contact_messages_file();
    if (!is_file($file) || !is_readable($file)) {
        return [];
    }
    $fp = @fopen($file, 'r');
    if ($fp === false) {
        return [];
    }
    $out = [];
    $header = fgetcsv($fp);            // ردیفِ سرستون
    if (is_array($header)) {
        while (($row = fgetcsv($fp)) !== false) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'time'    => (string) ($row[0] ?? ''),
                'subject' => (string) ($row[1] ?? ''),
                'name'    => (string) ($row[2] ?? ''),
                'email'   => (string) ($row[3] ?? ''),
                'message' => (string) ($row[4] ?? ''),
                'ip'      => (string) ($row[5] ?? ''),
                'source'  => 'csv',
            ];
        }
    }
    fclose($fp);
    return $out;
}

/**
 * همه‌ی پیام‌ها با شناسه‌ی پایدار.
 * ترتیب: تازه‌ترین اول (بر اساسِ time)، ولی ref به ترتیبِ ذخیره وابسته است
 * نه به ترتیبِ نمایش — پس جابه‌جاییِ نمایش، حذف را خراب نمی‌کند.
 *
 * @return array<int, array<string, mixed>>
 */
function admin_messages_all(): array
{
    $all = [];
    foreach (contact_messages_read() as $i => $m) {
        $m['ref'] = 'csv-' . $i;
        $all[] = $m;
    }
    foreach (admin_load('messages') as $i => $m) {
        if (!is_array($m)) {
            continue;
        }
        $m['ref']    = 'pan-' . $i;
        $m['source'] = 'panel';
        $all[] = $m;
    }
    usort($all, static function (array $a, array $b): int {
        return strcmp((string) ($b['time'] ?? ''), (string) ($a['time'] ?? ''));
    });
    return $all;
}

/** اعتبارسنجیِ ref — فقط دو قالبِ csv-N و pan-N پذیرفته می‌شود. */
function admin_message_parse_ref(string $ref): ?array
{
    if (!preg_match('/^(csv|pan)-(\d{1,6})$/', $ref, $m)) {
        return null;
    }
    return ['source' => $m[1], 'index' => (int) $m[2]];
}

/** پیدا کردنِ یک پیام با ref. */
function admin_message_find(string $ref): ?array
{
    $parsed = admin_message_parse_ref($ref);
    if ($parsed === null) {
        return null;
    }
    $list = $parsed['source'] === 'csv' ? contact_messages_read() : admin_load('messages');
    $item = $list[$parsed['index']] ?? null;
    if (!is_array($item)) {
        return null;
    }
    $item['ref']    = $ref;
    $item['source'] = $parsed['source'] === 'csv' ? 'csv' : 'panel';
    return $item;
}

/**
 * حذفِ یک پیام از منبعِ درستِ خودش.
 * برای CSV کلِ فایل با قفلِ انحصاری بازنویسی می‌شود (بدونِ آن ردیف).
 */
function admin_message_delete(string $ref): bool
{
    $parsed = admin_message_parse_ref($ref);
    if ($parsed === null) {
        return false;
    }

    if ($parsed['source'] === 'pan') {
        $messages = admin_load('messages');
        if (!isset($messages[$parsed['index']])) {
            return false;
        }
        array_splice($messages, $parsed['index'], 1);
        return admin_store('messages', $messages);
    }

    $file = contact_messages_file();
    if (!is_file($file)) {
        return false;
    }
    $fp = @fopen($file, 'r');
    if ($fp === false) {
        return false;
    }
    $rows = [];
    $header = fgetcsv($fp);
    while (($row = fgetcsv($fp)) !== false) {
        $rows[] = $row;
    }
    fclose($fp);
    if (!isset($rows[$parsed['index']])) {
        return false;
    }
    array_splice($rows, $parsed['index'], 1);

    $tmp = $file . '.tmp-' . bin2hex(random_bytes(4));
    $out = @fopen($tmp, 'w');
    if ($out === false) {
        return false;
    }
    $ok = true;
    if (flock($out, LOCK_EX)) {
        if (is_array($header)) {
            $ok = $ok && fputcsv($out, $header) !== false;
        }
        foreach ($rows as $row) {
            $ok = $ok && fputcsv($out, (array) $row) !== false;
        }
        flock($out, LOCK_UN);
    } else {
        $ok = false;
    }
    fclose($out);
    if (!$ok) {
        @unlink($tmp);
        return false;
    }
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

