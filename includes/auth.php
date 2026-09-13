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
