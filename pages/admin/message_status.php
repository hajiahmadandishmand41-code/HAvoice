<?php
/**
 * HAvoice Admin — تغییر وضعیت پیام تماس (خوانده‌شده ⇄ خوانده‌نشده)
 * POST + CSRF + اعتبارسنجی ref.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();

$listRoute = 'admin_messages';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست شما تمام شده است. لطفاً دوباره تلاش کنید.'); redirect(url($listRoute)); }

$ref          = (string) ($_POST['ref'] ?? '');
$status       = (string) ($_POST['status'] ?? '');
$redirectBack = (string) ($_POST['back'] ?? '');

if (admin_message_parse_ref($ref) === null) {
    flash('error', 'شناسه‌ی پیام معتبر نیست.');
    redirect(url($listRoute));
}

$msg = admin_message_find($ref);
if ($msg === null) {
    flash('error', 'پیام پیدا نشد یا پیش‌تر حذف شده است.');
    redirect(url($listRoute));
}

if (!in_array($status, ['read', 'unread', 'toggle'], true)) {
    $status = 'toggle';
}

if ($status === 'toggle') {
    $targetStatus = ($msg['status'] ?? 'unread') === 'read' ? 'unread' : 'read';
} else {
    $targetStatus = $status;
}

if (!admin_message_set_status($ref, $targetStatus)) {
    flash('error', 'تغییر وضعیت پیام ناموفق بود.');
    redirect(url($listRoute));
}

flash('success', $targetStatus === 'read' ? 'پیام به‌عنوان «خوانده‌شده» علامت‌گذاری شد.' : 'پیام به وضعیت «خوانده‌نشده» بازگشت.');

if ($redirectBack === 'view') {
    redirect(url('admin_message_view', ['slug' => $ref]));
} else {
    redirect(url($listRoute));
}
