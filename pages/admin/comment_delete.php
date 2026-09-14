<?php
/**
 * HAvoice Admin — حذفِ دائمیِ نظر
 * POST + CSRF + شناسه‌ی عددیِ بررسی‌شده (prepared statement در comment_delete).
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();

$listRoute = 'admin_comments';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست تمام شده. دوباره تلاش کنید.'); redirect(url($listRoute)); }

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'درخواستِ نامعتبر است.');
    redirect(url($listRoute));
}

$comment = comment_find($id);
if ($comment === null) {
    flash('error', 'نظر پیدا نشد؛ ممکن است پیش‌تر حذف شده باشد.');
    redirect(url($listRoute));
}

if (!comment_delete($id)) {
    flash('error', 'حذف ناموفق بود؛ اتصالِ دیتابیس را بررسی کنید.');
    redirect(url($listRoute));
}

flash('success', 'نظرِ «' . ($comment['name'] ?? '') . '» حذف شد.');
redirect(url($listRoute));
