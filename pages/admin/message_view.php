<?php
/**
 * HAvoice Admin — مشاهده‌ی یک پیام (با علامت «خوانده‌شده»)
 *
 * ref پایدار: db-N (دیتابیس)، csv-N و pan-N (فایل — دوره‌ی مهاجرت).
 * با باز شدنِ پیامِ دیتابیسی، read_at ثبت می‌شود و از شمارِ «جدید» کم می‌شود.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$ref = (string) ($_GET['slug'] ?? '');
$msg = is_string($ref) && $ref !== '' ? admin_message_find($ref) : null;

if ($msg !== null && (string) ($msg['source'] ?? '') === 'db') {
    admin_message_mark_read($ref);
    $msg = admin_message_find($ref) ?? $msg; /* read_at تازه */
}

$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>"><?= e($flash['message']) ?></div><?php endif; ?>

<div class="admin-toolbar">
    <a class="btn btn--ghost" href="<?= e(url('admin_messages')) ?>"><?= ha_icon('arrow-right', 15) ?> بازگشت به Inbox</a>
    <?php if ($msg !== null): ?>
    <form method="post" action="<?= e(url('admin_message_delete')) ?>" class="inline-form"
          data-confirm="این پیام برای همیشه حذف شود؟">
        <?= csrf_field() ?><input type="hidden" name="ref" value="<?= e($ref) ?>">
        <button class="btn btn--ghost btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف پیام</button>
    </form>
    <?php endif; ?>
</div>

<?php if ($msg === null): ?>
    <div class="alert alert--error" role="alert">پیام پیدا نشد؛ ممکن است حذف شده باشد.</div>
<?php else:
    $timeFa = $msg['time'] ?? '';
?>
<section class="admin-card admin-card--headline mb-md">
    <div class="admin-card__row">
        <div>
            <p class="admin-card__eyebrow"><?= e((string) ($msg['subject'] ?? '(بدون موضوع)')) ?></p>
            <h2 class="admin-card__title"><?= e((string) ($msg['name'] ?? '')) ?> <span class="muted-sm" dir="ltr">&lt;<?= e((string) ($msg['email'] ?? '')) ?>&gt;</span></h2>
            <p class="muted-sm">
                <?= e($timeFa) ?>
                <?php if (!empty($msg['read_at'])): ?>· خوانده شده: <?= e((string) $msg['read_at']) ?><?php endif; ?>
                <?php if ((string) ($msg['source'] ?? '') === 'db'): ?>· منبع: دیتابیس<?php endif; ?>
            </p>
        </div>
    </div>
</section>
<section class="admin-card">
    <h3 class="admin-card__title mb-sm">متن پیام</h3>
    <div class="prose" style="white-space:pre-wrap"><?= e((string) ($msg['message'] ?? '')) ?></div>
    <?php if (trim((string) ($msg['ip'] ?? '')) !== ''): ?>
        <p class="muted-sm mt-sm">IP فرستنده: <code dir="ltr"><?= e((string) $msg['ip']) ?></code></p>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
