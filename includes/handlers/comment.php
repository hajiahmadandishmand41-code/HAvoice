<?php
/**
 * HAvoice — پردازش فرم «نظرات عمومی».
 *
 * همان الگویِ handlers/contact.php:
 *   فقط POST ← CSRF ← فیلدِ زنبوری (honeypot) ← محدودیتِ نرخ ←
 *   اعتبارسنجی ← پاک‌سازی ← ذخیره در MySQL ← redirect (PRG).
 *
 * نمایشِ نظر پس از تأییدِ مدیر است (status = pending).
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$backUrl = url('comments');

/* ۱) فقط POST */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect($backUrl);
}

/* ۲) بررسی CSRF */
if (!csrf_verify()) {
    flash('error', 'نشست شما تمام شده است. لطفاً فرم را دوباره پر کنید.');
    redirect($backUrl);
}

/* ۳) فیلد زنبوری: ربات‌ها آن را پر می‌کنند */
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    flash('success', 'نظر شما ثبت شد و پس از تأیید نمایش داده می‌شود.');
    redirect($backUrl);
}

/* ۴) محدودیت نرخ — جدا از فرمِ تماس، با پنجره‌ی خودِ نظرات */
$ip    = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$limit = ha_rate_limit_acquire(
    'comment',
    $ip,
    (int) HA_COMMENTS_RATE_LIMIT_MAX,
    (int) HA_COMMENTS_RATE_LIMIT_WINDOW,
    (int) HA_COMMENTS_RATE_LIMIT_MIN_INTERVAL
);

if (!$limit['ok']) {
    if ($limit['error'] !== null) {
        flash('error', 'سامانه‌ی ضداسپام موقتاً در دسترس نیست. لطفاً چند دقیقه‌ی دیگر دوباره تلاش کنید.');
    } else {
        $minutes = max(1, (int) ceil($limit['retry'] / 60));
        flash('error', 'تعداد نظرهای شما در این بازه به سقف رسیده است. لطفاً ' . fa_num($minutes) . ' دقیقه‌ی دیگر دوباره تلاش کنید.');
    }
    redirect($backUrl);
}

/* ۵) اعتبارسنجی */
$name  = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$body  = trim((string) ($_POST['message'] ?? ''));
$consent = isset($_POST['consent']) && $_POST['consent'] === '1';

$errors = [];

if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 60) {
    $errors['name'] = 'نام را کامل بنویسید (بین ۲ تا ۶۰ نویسه).';
}

if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120)) {
    $errors['email'] = 'نشانی ایمیل معتبر نیست.';
}

$bodyLen = mb_strlen($body, 'UTF-8');
if ($bodyLen < 10) {
    $errors['message'] = 'متن نظر حداقل ۱۰ نویسه باشد.';
} elseif ($bodyLen > 1500) {
    $errors['message'] = 'متن نظر کوتاه‌تر کنید (حداکثر ۱۵۰۰ نویسه).';
}

/* ضداسپام ساده: نظرِ تبلیغاتی با چند پیوند */
if (preg_match_all('#https?://#i', $body) > 2) {
    $errors['message'] = 'درجِ بیش از دو پیوند در نظر مجاز نیست.';
}

if (!$consent) {
    $errors['consent'] = 'برای نمایشِ عمومیِ نظرتان، تأیید را بزنید.';
}

if ($errors !== []) {
    old_set(['name' => $name, 'email' => $email, 'message' => $body]);
    $_SESSION['ha_errors'] = $errors;
    flash('error', 'چند مورد در فرم نیاز به اصلاح دارد.');
    redirect($backUrl);
}

/* ۶) پاک‌سازی نهایی: حذف کاراکترهای کنترلی و یک‌خطی‌کردنِ نام/ایمیل */
$clean = static function (string $value): string {
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    return trim((string) $value);
};

$name  = $clean(str_replace("\n", ' ', $name));
$email = $clean(str_replace("\n", ' ', $email));
$body  = $clean($body);

/* ۷) ذخیره در MySQL */
$result = comment_add($name, $email, $body, $ip);

old_clear();
unset($_SESSION['ha_errors']);

if ($result['ok']) {
    flash('success', 'نظر شما ثبت شد. پس از بازبینیِ مدیر (معمولاً ۱ تا ۲ روز کاری) همین‌جا نمایش داده می‌شود. سپاس!');
} elseif (($result['error'] ?? '') === 'db') {
    old_set(['name' => $name, 'email' => $email, 'message' => $body]);
    flash('error', 'در حالِ حاضر سامانه‌ی ثبتِ نظر در دسترس نیست. لطفاً بعداً تلاش کنید یا از صفحه‌ی «تماس با ما» بنویسید.');
} else {
    flash('error', 'ثبتِ نظر ناموفق بود. لطفاً دوباره تلاش کنید.');
}

redirect($backUrl);
