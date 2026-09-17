<?php
/**
 * HAvoice — حساب کاربری (DB با PDO در اولویت، JSON fallback)
 *
 * تصمیم‌های امنیتی:
 *  • رمز عبور فقط با password_hash() ذخیره و با password_verify() بررسی می‌شود.
 *  • ایمیل lowercase + trim.
 *  • Session: regenerate_id روی login، httponly، samesite Lax، TTL عدم‌فعالیت.
 *  • نقش admin صریح (role=admin)؛ اولین کاربر فقط یک‌بار در نصب اولیه مدیر است.
 *  • CSRF روی همهٔ فرم‌های حساس (لایهٔ helpers).
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

/** بارگذاریِ فهرستِ کاربران. DB در اولویت؛ در غیر این صورت JSON. */
function auth_load_users(): array
{
    if (function_exists('db_ready') && db_ready() && function_exists('repo_db_has') && repo_db_has('ha_users')) {
        return db_users_all();
    }
    if (function_exists('db_ready') && db_ready()) {
        $rows = db_users_all();
        if ($rows !== []) {
            return $rows;
        }
    }
    $file = auth_users_file();
    if (!is_file($file)) {
        return [];
    }
    $raw = @file_get_contents($file);
    if (!is_string($raw)) {
        return [];
    }
    $users = json_decode($raw, true);
    return is_array($users) ? auth_normalize_roles($users) : [];
}

/**
 * نرمال‌سازیِ نقش‌ها در فهرستِ کاربران.
 *
 * نقش فقط از مقدارِ ذخیره‌شده می‌آید. رکوردِ بدونِ role همیشه «user» است —
 * هرگز «اولین کاربرِ فایل» مدیر نمی‌شود. تنها استثنا: اگر bootstrap یک‌باره
 * شناسه‌ی مدیرِ اولیه را ثبت کرده و همان رکورد role نداشته باشد، همان شناسه
 * (نه جایگاهِ فهرست) مدیر می‌ماند تا نصبِ قدیمی قفل نشود.
 *
 * @param list<array<string,mixed>> $users
 * @return list<array<string,mixed>>
 */
function auth_normalize_roles(array $users): array
{
    $primaryId = '';
    if (function_exists('auth_bootstrap_state')) {
        $primaryId = (string) (auth_bootstrap_state()['admin_id'] ?? '');
    }
    foreach ($users as $i => $u) {
        $role = (string) ($u['role'] ?? '');
        if ($role !== 'admin' && $role !== 'user') {
            $id   = (string) ($u['id'] ?? '');
            $role = ($primaryId !== '' && $id !== '' && $id === $primaryId) ? 'admin' : 'user';
        }
        $users[$i]['role'] = $role;
    }
    return $users;
}

/**
 * تعدادِ کاربرانِ واقعی در منبعِ حقیقت.
 * اگر DB آماده باشد همان جدول ملاک است؛ در غیر این صورت فایل JSON.
 * اگر DB خالی باشد ولی JSON کاربر داشته باشد، همان JSON شمرده می‌شود تا
 * bootstrap روی کاربرانِ موجودِ فایل دوباره مدیر نسازد.
 */
function auth_user_count(): int
{
    if (function_exists('db_ready') && db_ready() && function_exists('db_user_count')) {
        $n = db_user_count();
        if ($n > 0) {
            return $n;
        }
    }
    return count(auth_load_users_json_only());
}

/**
 * آیا در این لحظه می‌توان نقشِ admin را به «اولین کاربر» داد؟
 * فقط وقتی پرچم قفل نشده و هیچ کاربری در سیستم نیست.
 */
function auth_bootstrap_may_grant(bool $flagDone, int $existingUsers): bool
{
    return $flagDone === false && $existingUsers === 0;
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
    /* پاک‌سازی فقط اگر فایلِ موقت هنوز هست: بعد از rename موفق وجود ندارد
       و @unlink بی‌قید و شرط هر بار یک هشدار برمی‌انگیخت (همان اصلاحی که
       در admin_store() انجام شد — @ مقدارِ بازگشتی را خفه می‌کند، نه خودِ
       هشدار را). */
    if (is_file($tmp)) {
        @unlink($tmp);
    }
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
    if (function_exists('db_ready') && db_ready()) {
        $u = db_user_find_email($email);
        if ($u !== null) {
            return $u;
        }
        // اگر جدول users خالی است، به JSON هم نگاه کن (مهاجرت)
    }
    foreach (auth_load_users() as $user) {
        if (isset($user['email']) && auth_normalize_email((string) $user['email']) === $email) {
            return $user;
        }
    }
    return null;
}

function auth_find_user_by_id(string $id): ?array
{
    if (function_exists('db_ready') && db_ready()) {
        $u = db_user_find_id($id);
        if ($u !== null) {
            return $u;
        }
    }
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
/* ------------------------------------------------------------------ */
/*  Bootstrap یک‌باره‌ی مدیر                                             */
/* ------------------------------------------------------------------ */

/**
 * پرچمِ ماندگارِ bootstrap: «یک کاربر به‌عنوان مدیرِ اولیه ساخته شد».
 *
 * چرا پرچمِ ماندگار؟ تا «اولین کاربر = مدیر» فقط یک بار در عمرِ نصب اتفاق
 * بیفتد. اگر مدیرِ اولیه بعداً حذف شود یا فهرستِ کاربران خالی به‌نظر برسد
 * (مثلاً DB موقتاً در دسترس نیست)، کاربرِ بعدی خودبه‌خود مدیر نمی‌شود.
 *
 * @return array{done:bool,admin_id:string,admin_at:string}
 */
function auth_bootstrap_state(): array
{
    /* کش در $GLOBALS نه static، چون بعد از ثبتِ پرچم باید در همان درخواست
       بی‌اعتبار شود (static از بیرون قابلِ پاک‌کردن نیست). */
    if (isset($GLOBALS['HA_AUTH_BOOTSTRAP']) && is_array($GLOBALS['HA_AUTH_BOOTSTRAP'])) {
        return $GLOBALS['HA_AUTH_BOOTSTRAP'];
    }
    $data = function_exists('admin_load') ? admin_load('auth_bootstrap') : [];
    $done = !empty($data['done']);
    $id   = (string) ($data['admin_id'] ?? '');
    $at   = (string) ($data['admin_at'] ?? '');

    /* منبعِ دوم: جدول تنظیمات DB — اگر فایل storage پاک شود قفل باقی بماند. */
    if (function_exists('db_ready') && db_ready() && function_exists('db_setting_get')) {
        $raw = db_setting_get('auth_bootstrap', '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && !empty($decoded['done'])) {
                $done = true;
                if ($id === '') {
                    $id = (string) ($decoded['admin_id'] ?? '');
                }
                if ($at === '') {
                    $at = (string) ($decoded['admin_at'] ?? '');
                }
            }
        }
    }

    $GLOBALS['HA_AUTH_BOOTSTRAP'] = [
        'done'     => $done,
        'admin_id' => $id,
        'admin_at' => $at,
    ];
    return $GLOBALS['HA_AUTH_BOOTSTRAP'];
}

/** ثبتِ پرچمِ bootstrap (یک‌بار). اگر از قبل قفل شده باشد false برمی‌گردد. */
function auth_bootstrap_record(string $adminId): bool
{
    $state = auth_bootstrap_state();
    if (!empty($state['done'])) {
        return false;
    }
    $payload = [
        'done'     => true,
        'admin_id' => $adminId,
        'admin_at' => date('c'),
        'note'     => 'اولین کاربرِ سیستم به‌عنوان مدیرِ اولیه ثبت شد؛ از این پس هیچ کاربری خودکار مدیر نمی‌شود.',
    ];
    $ok = function_exists('admin_store') && admin_store('auth_bootstrap', $payload);
    if (function_exists('db_ready') && db_ready() && function_exists('db_settings_set')) {
        db_settings_set(['auth_bootstrap' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        $ok = true;
    }
    if ($ok) {
        auth_bootstrap_state_reset();
    }
    return $ok;
}

/** بی‌اعتبارکردنِ کشِ وضعیتِ bootstrap (بعد از نوشتنِ پرچم). */
function auth_bootstrap_state_reset(): void
{
    unset($GLOBALS['HA_AUTH_BOOTSTRAP']);
}

/**
 * اگر کاربر وجود دارد ولی پرچم قفل نشده، قفل را می‌زنیم تا کاربرِ بعدی
 * خودکار مدیر نشود (نصبِ قدیمی / مهاجرت / پاک‌شدنِ فایلِ پرچم).
 */
function auth_bootstrap_heal_if_needed(): void
{
    if (!empty($authLock = $GLOBALS['HA_AUTH_BOOTSTRAP_HEALING'] ?? false)) {
        return;
    }
    if (auth_bootstrap_state()['done']) {
        return;
    }
    $GLOBALS['HA_AUTH_BOOTSTRAP_HEALING'] = true;
    $count = auth_user_count();
    if ($count > 0) {
        $adminId = '';
        foreach (auth_load_users() as $u) {
            if ((string) ($u['role'] ?? '') === 'admin') {
                $adminId = (string) ($u['id'] ?? '');
                break;
            }
        }
        auth_bootstrap_record($adminId !== '' ? $adminId : 'locked');
    }
    $GLOBALS['HA_AUTH_BOOTSTRAP_HEALING'] = false;
}

/** آیا هنوز مجاز به ساختِ مدیرِ اولیه هستیم؟ (فقط وقتی هیچ کاربری نیست) */
function auth_bootstrap_available(): bool
{
    auth_bootstrap_heal_if_needed();
    return auth_bootstrap_may_grant(
        (bool) auth_bootstrap_state()['done'],
        auth_user_count()
    );
}

/**
 * ساختِ کاربرِ جدید.
 *
 * نقشِ پیش‌فرض 'user' است. تنها استثنا: bootstrap یک‌باره‌ی مدیرِ اولیه —
 * وقتی «هیچ کاربری» در سیستم نیست و پرچمِ bootstrap هنوز ثبت نشده. بعد از
 * آن، نقش فقط از پنلِ مدیریت (auth_set_role) تغییر می‌کند.
 *
 * @return array{ok:bool,user:array<string,mixed>|null,error:string|null,bootstrapped?:bool}
 */
function auth_create_user(string $name, string $email, string $password): array
{
    $email      = auth_normalize_email($email);
    $bootstrap  = auth_bootstrap_available();
    $role       = $bootstrap ? 'admin' : 'user';

    $finish = static function (array $res) use ($bootstrap): array {
        if (empty($res['ok']) || empty($res['user'])) {
            return $res;
        }
        $user = $res['user'];
        $bootstrapped = false;
        if ($bootstrap && (string) ($user['role'] ?? '') === 'admin') {
            $bootstrapped = auth_bootstrap_record((string) ($user['id'] ?? ''));
            if (!$bootstrapped) {
                auth_set_role((string) $user['id'], 'user');
                $user['role'] = 'user';
                $res['user'] = $user;
            }
        }
        $res['bootstrapped'] = $bootstrapped;
        return $res;
    };

    if (function_exists('db_ready') && db_ready()) {
        $res = db_user_create(trim($name), $email, $password, $role);
        if (!empty($res['ok'])) {
            /* آینه JSON برای backup */
            $users = auth_load_users_json_only();
            $u = $res['user'];
            unset($u['_db']);
            $users[] = $u;
            auth_save_users($users);
            return $finish($res);
        }
        /* اگر DB fail شد، fallback JSON */
    }

    $users = auth_load_users_json_only();
    $user  = [
        'id'         => bin2hex(random_bytes(16)),
        'name'       => trim($name),
        'email'      => $email,
        'pass_hash'  => password_hash($password, PASSWORD_DEFAULT),
        'role'       => $role,
        'created_at' => date('c'),
    ];
    $users[] = $user;

    if (!auth_save_users($users)) {
        return ['ok' => false, 'user' => null, 'error' => 'storage'];
    }
    return $finish(['ok' => true, 'user' => $user, 'error' => null]);
}

/** فقط فایل JSON (بدون DB) — برای dual-write. */
function auth_load_users_json_only(): array
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
    $ok = true;
    if (function_exists('db_ready') && db_ready()) {
        $ok = db_user_update_password($id, $password);
    }
    $users = auth_load_users_json_only();
    $changed = false;
    foreach ($users as $i => $user) {
        if (($user['id'] ?? '') === $id) {
            $users[$i]['pass_hash'] = password_hash($password, PASSWORD_DEFAULT);
            $changed = true;
            break;
        }
    }
    if ($changed) {
        auth_save_users($users);
    }
    return $ok || $changed;
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
    $_SESSION['ha_last_activity'] = time();
    /* توکنِ CSRF قبلی با نشستِ جدید معتبر نیست؛ دوباره ساخته می‌شود. */
    unset($_SESSION['ha_csrf'], $_SESSION['ha_csrf_t']);

    /* ثبتِ آخرین ورود */
    if (function_exists('db_ready') && db_ready()) {
        db_user_touch_login((string) $user['id']);
    }
    $users = auth_load_users_json_only();
    foreach ($users as $i => $u) {
        if (($u['id'] ?? '') === (string) $user['id']) {
            $users[$i]['last_login'] = date('c');
            auth_save_users($users);
            break;
        }
    }
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

    /* انقضای عدم‌فعالیت: اگر از آخرین فعالیتِ کاربر بیش از
       HA_AUTH_SESSION_TTL گذشته باشد، نشست باطل می‌شود (اثرِ لغزان —
       با هر درخواستِ موفق، ساعت از نو شروع می‌شود). */
    $ttl = defined('HA_AUTH_SESSION_TTL') ? (int) HA_AUTH_SESSION_TTL : 0;
    if ($ttl > 0) {
        $now  = time();
        $last = (int) ($_SESSION['ha_last_activity'] ?? ($_SESSION['ha_login_at'] ?? $now));
        if ($now - $last > $ttl) {
            auth_logout();
            return null;
        }
        $_SESSION['ha_last_activity'] = $now;
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

/**
 * گیتِ محتوای محافظت‌شده (دوره، درس، تمرین و…):
 * مهمان به ورود/ثبت‌نام هدایت می‌شود و نشانیِ همین صفحه در پارامترِ
 * next حفظ می‌ماند تا پس از ورود به همان‌جا برگردد.
 */
function auth_require_guest(string $message = ''): void
{
    if (auth_is_logged_in()) {
        return;
    }
    if ($message === '') {
        $message = 'برای دسترسی به دوره‌ها، درس‌ها و تمرین‌ها ثبت‌نام کنید — رایگان و کمتر از ۳۰ ثانیه. پس از ثبت‌نام به همین صفحه برمی‌گردید. اگر قبلاً حساب دارید، وارد شوید.';
    }
    flash('error', $message);
    redirect(url('register', ['next' => ha_current_request_url()]));
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

/** آیا کاربر جاری مدیر است؟ فقط role=admin ذخیره‌شده در DB/فایل. */
function auth_is_admin(): bool
{
    $user = auth_current_user();
    if ($user === null) {
        return false;
    }
    /* دسترسی فقط با نقشِ واقعی. هیچ حدسِ «اولین کاربر» یا پرچمِ نشست. */
    return (string) ($user['role'] ?? '') === 'admin';
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
    $role = $role === 'admin' ? 'admin' : 'user';
    $ok = true;
    if (function_exists('db_ready') && db_ready()) {
        $ok = db_user_set_role($id, $role);
    }
    $users = auth_load_users_json_only();
    $changed = false;
    foreach ($users as $i => $user) {
        if (($user['id'] ?? '') === $id) {
            $users[$i]['role'] = $role;
            $changed = true;
            break;
        }
    }
    if ($changed) {
        auth_save_users($users);
    }
    return $ok || $changed;
}

/** تنظیمات سایت: DB settings table یا JSON. */
function auth_settings_load(): array
{
    if (function_exists('db_ready') && db_ready()) {
        $s = db_settings_get();
        if ($s !== []) {
            return $s;
        }
    }
    return admin_load('settings');
}

function auth_settings_save(array $settings): bool
{
    $ok = admin_store('settings', $settings);
    if (function_exists('db_ready') && db_ready()) {
        db_settings_set($settings);
    }
    return $ok;
}

/*
 * admin_store() و admin_load() به includes/helpers.php منتقل شدند، کنارِ
 * storage_dir(). دلیل: این دو هیچ وابستگی به احرازِ هویت ندارند — فقط
 * ذخیره‌سازیِ JSON در storage‌اند — ولی helpers.php (categories) و
 * content.php (articles/books/media/…) هر دو به آن‌ها تکیه می‌کنند.
 * وقتی اینجا بودند، هر اسکریپتِ مستقلی که helpers+content را require
 * می‌کرد و auth.php را نه (مثلِ sitemap.php) با خطایِ فاجعه‌بارِ
 * «Call to undefined function admin_load()» از کار می‌افتاد.
 */

/** بارگذاری تنظیمات سایت ذخیره‌شده توسط مدیر */
function admin_settings(): array
{
    return function_exists('auth_settings_load') ? auth_settings_load() : admin_load('settings');
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
 * همه‌ی پیام‌ها با شناسه‌ی پایدار، وضعیت خوانده‌شده/خوانده‌نشده، فیلتر و جستجو.
 * ترتیب: تازه‌ترین اول (بر اساسِ time)، ولی ref به ترتیبِ ذخیره وابسته است
 * نه به ترتیبِ نمایش — پس جابه‌جاییِ نمایش، حذف را خراب نمی‌کند.
 *
 * @return array<int, array<string, mixed>>
 */
function admin_messages_all(?string $filterStatus = null, string $search = ''): array
{
    $all = [];
    $seen = [];
    $csvMeta = admin_load('messages_meta');

    /* دیتابیس منبعِ اصلی حقیقت است */
    if (db_table_exists('ha_contact_messages')) {
        foreach (db_messages_all(1000, null, '') as $m) {
            $key = ($m['time'] ?? '') . '|' . ($m['email'] ?? '');
            $seen[$key] = true;
            $all[] = $m;
        }
    }

    /* فایل CSV پشتیبان / نصب‌های بدون DB */
    foreach (contact_messages_read() as $i => $m) {
        $key = ($m['time'] ?? '') . '|' . ($m['email'] ?? '');
        if (isset($seen[$key])) {
            continue; // قبلاً از دیتابیس بارگذاری شده
        }
        $ref = 'csv-' . $i;
        $m['ref']     = $ref;
        $m['source']  = 'csv';
        $m['status']  = $csvMeta[$ref]['status'] ?? 'unread';
        $m['read_at'] = $csvMeta[$ref]['read_at'] ?? '';
        $seen[$key]   = true;
        $all[] = $m;
    }

    /* پیام‌های ثبت‌شده در JSON پنل */
    foreach (admin_load('messages') as $i => $m) {
        if (!is_array($m)) {
            continue;
        }
        $key = ($m['time'] ?? '') . '|' . ($m['email'] ?? '');
        if (isset($seen[$key])) {
            continue;
        }
        $ref = 'pan-' . $i;
        $m['ref']     = $ref;
        $m['source']  = 'panel';
        $m['status']  = $m['status'] ?? ($csvMeta[$ref]['status'] ?? 'unread');
        $m['read_at'] = $m['read_at'] ?? ($csvMeta[$ref]['read_at'] ?? '');
        $all[] = $m;
    }

    /* فیلتر وضعیت */
    if ($filterStatus === 'unread' || $filterStatus === 'read') {
        $all = array_filter($all, static function ($item) use ($filterStatus) {
            return ($item['status'] ?? 'unread') === $filterStatus;
        });
    }

    /* جستجو */
    $search = trim($search);
    if ($search !== '') {
        $all = array_filter($all, static function ($item) use ($search) {
            $blob = ($item['name'] ?? '') . ' ' . ($item['email'] ?? '') . ' ' . ($item['subject'] ?? '') . ' ' . ($item['message'] ?? '');
            return mb_stripos($blob, $search, 0, 'UTF-8') !== false;
        });
    }

    usort($all, static function (array $a, array $b): int {
        return strcmp((string) ($b['time'] ?? ''), (string) ($a['time'] ?? ''));
    });
    return array_values($all);
}

/** تعداد پیام‌های خوانده‌نشده (جدید) */
function admin_message_unread_count(): int
{
    if (db_table_exists('ha_contact_messages')) {
        return db_message_count('unread');
    }
    $all = admin_messages_all('unread');
    return count($all);
}

/** اعتبارسنجیِ ref — فقط سه قالبِ csv-N، pan-N و db-N (شناسه‌ی ردیف) پذیرفته می‌شود. */
function admin_message_parse_ref(string $ref): ?array
{
    if (!preg_match('/^(csv|pan|db)-(\d{1,6})$/', $ref, $m)) {
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
    if ($parsed['source'] === 'db') {
        $item = db_table_exists('ha_contact_messages') ? db_message_find($parsed['index']) : null;
        if (!is_array($item)) {
            return null;
        }
        $item['ref']    = $ref;
        $item['source'] = 'db';
        return $item;
    }

    $list = $parsed['source'] === 'csv' ? contact_messages_read() : admin_load('messages');
    $item = $list[$parsed['index']] ?? null;
    if (!is_array($item)) {
        return null;
    }
    $csvMeta = admin_load('messages_meta');
    $item['ref']     = $ref;
    $item['source']  = $parsed['source'] === 'csv' ? 'csv' : 'panel';
    $item['status']  = $item['status'] ?? ($csvMeta[$ref]['status'] ?? 'unread');
    $item['read_at'] = $item['read_at'] ?? ($csvMeta[$ref]['read_at'] ?? '');
    return $item;
}

/** تغییر وضعیتِ خوانده‌شده/خوانده‌نشده پیام */
function admin_message_set_status(string $ref, string $status): bool
{
    $parsed = admin_message_parse_ref($ref);
    if ($parsed === null) {
        return false;
    }
    $status = $status === 'read' ? 'read' : 'unread';

    if ($parsed['source'] === 'db') {
        return db_table_exists('ha_contact_messages') && db_message_set_status($parsed['index'], $status);
    }

    $meta = admin_load('messages_meta');
    $meta[$ref] = [
        'status'  => $status,
        'read_at' => $status === 'read' ? date('Y-m-d H:i:s') : null,
    ];
    return admin_store('messages_meta', $meta);
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

    if ($parsed['source'] === 'db') {
        return db_table_exists('ha_contact_messages') && db_message_delete($parsed['index']);
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

