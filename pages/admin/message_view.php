<?php
/**
 * HAvoice Admin — مشاهده‌ی یک پیامِ تماس
 *
 * پیام با ref پایدار پیدا می‌شود.
 * هنگام مشاهده‌ی پیامِ جدید، وضعیت به‌صورت خودکار به «خوانده‌شده» تغییر می‌یابد.
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

/* اگر پیام تازه است، آن را به عنوان خوانده‌شده ثبت کن */
if (($msg['status'] ?? 'unread') === 'unread') {
    admin_message_set_status($ref, 'read');
    $msg['status'] = 'read';
}

$flash = flash();
$isUnread = ($msg['status'] ?? 'unread') === 'unread';
?>
<?php if (!empty($flash['message'])): ?>
    <div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="admin-card msg-detail">
    <div class="msg-detail__header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
        <h2 style="margin: 0;"><?= e($msg['subject'] ?: 'بدونِ موضوع') ?></h2>
        <div>
            <?php if ($isUnread): ?>
                <span class="admin-badge admin-badge--warn">خوانده‌نشده</span>
            <?php else: ?>
                <span class="admin-badge admin-badge--success">خوانده‌شده</span>
            <?php endif; ?>
        </div>
    </div>

    <dl class="msg-detail__meta">
        <div><dt>نام فرستنده</dt><dd><strong><?= e($msg['name'] ?? '—') ?></strong></dd></div>
        <div><dt>ایمیل</dt><dd dir="ltr"><a href="mailto:<?= e($msg['email'] ?? '') ?>"><?= e($msg['email'] ?? '—') ?></a></dd></div>
        <div><dt>تاریخ ارسال</dt><dd><?= e($msg['time'] ?? '—') ?></dd></div>
        <div><dt>منبع</dt><dd>
            <?php
            $msgSource = (string) ($msg['source'] ?? 'csv');
            $msgBadge  = $msgSource === 'panel' ? ['admin-badge--success', 'پنل']
                : ($msgSource === 'db' ? ['admin-badge--info', 'دیتابیس'] : ['admin-badge--info', 'فرم تماس']);
            ?>
            <span class="admin-badge <?= e($msgBadge[0]) ?>"><?= e($msgBadge[1]) ?></span>
        </dd></div>
        <?php if (($msg['ip'] ?? '') !== ''): ?>
            <div><dt>نشانی IP</dt><dd dir="ltr"><code><?= e($msg['ip'] ?? '') ?></code></dd></div>
        <?php endif; ?>
    </dl>

    <div class="msg-body-text" style="margin-top: 1.5rem; padding: 1.25rem; background: var(--bg-soft, #f8fafc); border-radius: var(--r-md, 12px); border: 1px solid var(--border); line-height: 1.8; font-size: 1.05rem;">
        <?= nl2br(e($msg['message'] ?? '')) ?>
    </div>
</div>

<div class="admin-form-actions" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; margin-top: 1.5rem;">
    <?php if (!empty($msg['email'])): ?>
        <a class="btn btn--primary" href="mailto:<?= e($msg['email']) ?>?subject=<?= rawurlencode('پاسخ: ' . ($msg['subject'] ?: 'پیام شما در های‌ویس')) ?>">
            <?= ha_icon('mail', 15) ?> پاسخ با ایمیل
        </a>
    <?php endif; ?>

    <form method="post" action="<?= e(url('admin_message_status')) ?>" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="ref" value="<?= e($ref) ?>">
        <input type="hidden" name="status" value="<?= $isUnread ? 'read' : 'unread' ?>">
        <input type="hidden" name="back" value="view">
        <button class="btn btn--ghost" type="submit">
            <?= $isUnread ? ha_icon('check', 15) . ' تغییر به خوانده‌شده' : ha_icon('sparkle', 15) . ' تغییر به خوانده‌نشده' ?>
        </button>
    </form>

    <form method="post" action="<?= e(url('admin_message_delete')) ?>" class="inline-form"
          data-confirm="این پیام برای همیشه حذف شود؟">
        <?= csrf_field() ?>
        <input type="hidden" name="ref" value="<?= e($ref) ?>">
        <button class="btn btn--danger" type="submit"><?= ha_icon('trash', 15) ?> حذفِ پیام</button>
    </form>

    <a class="btn btn--ghost" href="<?= e(url('admin_messages')) ?>"><?= ha_icon('arrow-right', 15) ?> بازگشت به فهرست</a>
</div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
