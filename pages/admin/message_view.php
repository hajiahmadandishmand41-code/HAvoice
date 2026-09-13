<?php
/**
 * HAvoice Admin — مشاهده‌ی یک پیامِ تماس
 *
 * پیام با ref پایدار (csv-N یا pan-N) پیدا می‌شود، نه با اندیسِ آرایه‌ی
 * نمایشی. پیش‌تر اندیسِ آرایه‌ی reverse‌شده به اینجا می‌آمد ولی این فایل
 * آرایه‌ی «بدونِ reverse» را ایندکس می‌زد ⇒ پیامِ اشتباه نشان داده می‌شد.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$ref = param('slug');
$msg = $ref !== '' ? admin_message_find($ref) : null;

if ($msg === null) {
    echo '<div class="alert alert--error" role="alert"><p>پیام پیدا نشد یا پیش‌تر حذف شده است.</p></div>';
    echo '<a class="btn btn--ghost" href="' . e(url('admin_messages')) . '">بازگشت به فهرست</a>';
    require HA_ROOT . '/pages/admin/_layout_end.php';
    return;
}
?>
<div class="admin-card msg-detail">
    <h2><?= e($msg['subject'] ?: 'بدونِ موضوع') ?></h2>
    <dl class="msg-detail__meta">
        <div><dt>نام</dt><dd><?= e($msg['name'] ?? '') ?></dd></div>
        <div><dt>ایمیل</dt><dd dir="ltr"><a href="mailto:<?= e($msg['email'] ?? '') ?>"><?= e($msg['email'] ?? '') ?></a></dd></div>
        <div><dt>تاریخ</dt><dd><?= e($msg['time'] ?? '') ?></dd></div>
        <div><dt>منبع</dt><dd><span class="admin-badge <?= ($msg['source'] ?? '') === 'panel' ? 'admin-badge--success' : 'admin-badge--info' ?>"><?= e(($msg['source'] ?? '') === 'panel' ? 'پنل' : 'فرم تماس') ?></span></dd></div>
        <?php if (($msg['ip'] ?? '') !== ''): ?>
        <div><dt>IP</dt><dd dir="ltr"><code><?= e($msg['ip'] ?? '') ?></code></dd></div>
        <?php endif; ?>
    </dl>
    <div class="msg-body-text"><?= nl2br(e($msg['message'] ?? '')) ?></div>
</div>

<div class="admin-form-actions">
    <form method="post" action="<?= e(url('admin_message_delete')) ?>" class="inline-form"
          data-confirm="این پیام برای همیشه حذف شود؟">
        <?= csrf_field() ?><input type="hidden" name="ref" value="<?= e($ref) ?>">
        <button class="btn btn--danger" type="submit"><?= ha_icon('trash', 15) ?> حذفِ پیام</button>
    </form>
    <a class="btn btn--ghost" href="<?= e(url('admin_messages')) ?>"><?= ha_icon('arrow-right', 15) ?> بازگشت به فهرست</a>
</div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
