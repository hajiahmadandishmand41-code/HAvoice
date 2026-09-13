<?php
/**
 * HAvoice — پردازش ورود.
 * قبل از رندر قالب اجرا می‌شود (الگوی PRG).
 * امنیت: CSRF + honeypot + محدودیت نرخ + password_verify + پیامِ خطای عمومی.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$backUrl = url('login');

/* ۱) فقط POST */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect($backUrl);
}

/* ۲) CSRF */
if (!csrf_verify()) {
    flash('error', 'نشست شما تمام شده است. لطفاً فرم را دوباره پر کنید.');
    redirect($backUrl);
}

/* ۳) honeypot */
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    flash('error', 'ایمیل یا رمز عبور نادرست است.');
    redirect($backUrl);
}

/* ۴) محدودیت نرخ (ضد brute-force) */
$limit = auth_rate_limit('login');
if (!$limit['ok']) {
    if ($limit['error'] !== null) {
        flash('error', 'سامانه‌ی ورود موقتاً در دسترس نیست. لطفاً چند دقیقه‌ی دیگر دوباره تلاش کنید.');
    } else {
        $minutes = max(1, (int) ceil($limit['retry'] / 60));
        flash('error', 'تعداد تلاش‌های ورود زیاد است. لطفاً ' . fa_num($minutes) . ' دقیقه‌ی دیگر دوباره تلاش کنید.');
    }
    redirect($backUrl);
}

/* ۵) ورودی‌ها */
$email    = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

/* ۶) بررسیِ اعتبار — پیامِ خطا عمداً عمومی است تا نشانیِ «ایمیل موجود است»
      برای مهاجم افشا نشود (ضد enumeration). */
$user = ($email !== '' && $password !== '')
    ? auth_verify_credentials($email, $password)
    : null;

if ($user === null) {
    old_set(['email' => $email]);
    flash('error', 'ایمیل یا رمز عبور نادرست است.');
    redirect($backUrl);
}

/* ۷) ورودِ موفق + بازسازیِ شناسه‌ی نشست */
auth_login($user);
old_clear();
flash('success', 'خوش آمدید، ' . $user['name'] . '!');
redirect(url('account'));
