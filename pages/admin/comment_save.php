<?php
/**
 * HAvoice Admin — ثبتِ نظر توسطِ مدیر (Create)
 *
 * مدیر می‌تواند «تجربه»/نظر واقعی را خودش ثبت کند — بدون تقلیدِ کاربران.
 * همان اعتبارسنجیِ فرمِ عمومی (نام+متن+ایمیل معتبر) را رعایت می‌کنیم.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_helpers.php';
auth_require_admin();

if (!csrf_verify()) {
    flash('error', 'نشست تمام شده است. لطفاً دوباره تلاش کنید.');
    redirect(url('admin'));
}

$name   = trim((string) ($_POST['name'] ?? ''));
$email  = trim((string) ($_POST['email'] ?? ''));
$body   = trim((string) ($_POST['body'] ?? ''));
$status = (string) ($_POST['status'] ?? 'approved');

if (!in_array($status, comment_statuses(), true)) {
    $status = 'approved';
}
if ($name === '' || $body === '') {
    flash('error', 'نام و متنِ نظر الزامی است.');
    redirect(url('admin_comments', ['status' => $status]));
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'ایمیل معتبر وارد کنید.');
    redirect(url('admin_comments', ['status' => $status]));
}

$res = comment_admin_create($name, $email, $body, $status);
if (!empty($res['ok'])) {
    flash('success', 'نظر با موفقیت ثبت شد.');
} else {
    flash('error', 'ثبتِ نظر ناموفق بود.');
}
redirect(url('admin_comments', ['status' => $status]));
