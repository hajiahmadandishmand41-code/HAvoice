<?php
/**
 * HAvoice — پردازش ثبت‌نام.
 * قبل از رندر قالب اجرا می‌شود (الگوی PRG).
 * امنیت: CSRF + honeypot + محدودیت نرخ + اعتبارسنجی + هشِ رمز + ضدِ ثبتِ تکراری.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* نشانیِ بازگشتِ کاربر پس از ثبت‌نامِ موفق (فقط نسبیِ داخلی). */
$next    = ha_safe_next((string) ($_POST['next'] ?? ''));
$backUrl = url('register', $next !== '' ? ['next' => $next] : []);

/* ۱) فقط POST */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect(url('register'));
}

/* ۲) CSRF */
if (!csrf_verify()) {
    flash('error', 'نشست شما تمام شده است. لطفاً فرم را دوباره پر کنید.');
    redirect($backUrl);
}

/* ۳) honeypot — ربات‌ها آن را پر می‌کنند؛ بی‌سروصدا دور انداخته می‌شود */
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    flash('success', 'ثبت‌نام شما انجام شد. لطفاً وارد شوید.');
    redirect(url('login'));
}

/* ۴) محدودیت نرخ (ضدِ ساختِ انبوهِ حساب) */
$limit = auth_rate_limit('register');
if (!$limit['ok']) {
    if ($limit['error'] !== null) {
        flash('error', 'سامانه‌ی ثبت‌نام موقتاً در دسترس نیست. لطفاً چند دقیقه‌ی دیگر دوباره تلاش کنید.');
    } else {
        $minutes = max(1, (int) ceil($limit['retry'] / 60));
        flash('error', 'تعداد درخواست‌های ثبت‌نام از این دستگاه زیاد است. لطفاً ' . fa_num($minutes) . ' دقیقه‌ی دیگر دوباره تلاش کنید.');
    }
    redirect($backUrl);
}

/* ۵) ورودی‌ها */
$name     = trim((string) ($_POST['name'] ?? ''));
$email    = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirm  = (string) ($_POST['confirm'] ?? '');

/* ۶) اعتبارسنجی (شاملِ جلوگیری از ثبتِ تکراری) */
$errors = auth_validate_registration($name, $email, $password, $confirm);
if ($errors !== []) {
    old_set(['name' => $name, 'email' => $email]);
    $_SESSION['ha_errors'] = $errors;
    flash('error', 'چند مورد در فرم ثبت‌نام نیاز به اصلاح دارد.');
    redirect($backUrl);
}

/* ۷) ساختِ کاربر با هشِ امن */
$result = auth_create_user($name, $email, $password);
if (!$result['ok'] || $result['user'] === null) {
    old_set(['name' => $name, 'email' => $email]);
    flash('error', 'سامانه‌ی ثبت‌نام موقتاً در دسترس نیست. لطفاً بعداً تلاش کنید یا از طریق ایمیل ' . HA_EMAIL . ' در تماس باشید.');
    redirect($backUrl);
}

/* ۸) ورودِ خودکار پس از ثبت‌نام و بازگشت به همان صفحه‌ی درخواستی */
auth_login($result['user']);
old_clear();
unset($_SESSION['ha_errors']);
flash('success', 'حساب شما با موفقیت ساخته شد. خوش آمدید!');
redirect($next !== '' ? $next : url('account'));
