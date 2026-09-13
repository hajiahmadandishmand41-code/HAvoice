<?php
/**
 * HAvoice Admin — حذفِ پیامِ تماس
 *
 * با ref پایدار کار می‌کند و پیام را از «منبعِ درستِ خودش» حذف می‌کند:
 * csv-N ⇒ بازنویسیِ storage/messages/messages.csv بدونِ آن ردیف
 * pan-N ⇒ به‌روزرسانیِ storage/admin/messages.json
 *
 * پیش‌تر فقط admin_load('messages') را با اندیسی که از آرایه‌ی
 * reverse‌شده‌ی نمایش می‌آمد splice می‌کرد؛ یعنی برایِ پیام‌های فرمِ تماس
 * هرگز کار نمی‌کرد و برایِ پیام‌های پنل رکوردِ اشتباه را می‌برد.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();

$listRoute = 'admin_messages';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست شما تمام شده. دوباره تلاش کنید.'); redirect(url($listRoute)); }

$ref = (string) ($_POST['ref'] ?? '');
if (admin_message_parse_ref($ref) === null) {
    flash('error', 'شناسه‌ی پیام معتبر نیست.');
    redirect(url($listRoute));
}

$target = admin_message_find($ref);
if ($target === null) {
    flash('error', 'پیام پیدا نشد؛ ممکن است پیش‌تر حذف شده باشد.');
    redirect(url($listRoute));
}

if (!admin_message_delete($ref)) {
    flash('error', 'حذف ناموفق بود؛ فایلِ پیام‌ها قابلِ نوشتن نیست.');
    redirect(url($listRoute));
}

flash('success', 'پیامِ «' . ($target['subject'] ?: ($target['name'] ?? '—')) . '» حذف شد.');
redirect(url($listRoute));
