<?php
/**
 * HAvoice Admin — تأیید/پنهان‌سازیِ نظر (تغییر وضعیت)
 * POST + CSRF + اعتبارسنجیِ سختِ مقدارها.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();

$listRoute = 'admin_comments';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست تمام شده. دوباره تلاش کنید.'); redirect(url($listRoute)); }

$id     = (int) ($_POST['id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');

if ($id <= 0 || !in_array($status, ['pending', 'approved'], true)) {
    flash('error', 'درخواستِ نامعتبر است.');
    redirect(url($listRoute));
}

$comment = comment_find($id);
if ($comment === null) {
    flash('error', 'نظر پیدا نشد؛ ممکن است پیش‌تر حذف شده باشد.');
    redirect(url($listRoute));
}

if (!comment_set_status($id, $status)) {
    flash('error', 'به‌روزرسانی ناموفق بود؛ اتصالِ دیتابیس را بررسی کنید.');
    redirect(url($listRoute));
}

flash('success', $status === 'approved'
    ? 'نظرِ «' . ($comment['name'] ?? '') . '» تأیید و منتشر شد.'
    : 'نظرِ «' . ($comment['name'] ?? '') . '» پنهان شد (به «در انتظار» بازگشت).');
redirect(url($listRoute, ['status' => $status === 'approved' ? 'pending' : 'approved']));
