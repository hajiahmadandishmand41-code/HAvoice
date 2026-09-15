<?php
/**
 * HAvoice Admin — Inbox پیام‌های تماس (Database-driven)
 *
 * هر پیام یک ref پایدار دارد (db:N برای دیتابیس؛ csv:N و pan:N برای
 * منابعِ قدیمیِ فایل — دوره‌ی مهاجرت). وضعیت «جدید/خوانده‌شده» بر اساسِ
 * read_at دیتابیس است. حذف با POST + CSRF.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$allMessages  = admin_messages_all();
$unreadCount  = admin_messages_unread_count();
$dbReady      = db_ready();
$flash        = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>"><?= e($flash['message']) ?></div><?php endif; ?>

<div class="admin-toolbar">
    <span class="muted-sm">
        <?= fa_num(count($allMessages)) ?> پیام
        <?php if ($dbReady): ?>· <?= fa_num($unreadCount) ?> خوانده‌نشده<?php endif; ?>
        <?php if (!$dbReady): ?>· <span class="admin-badge admin-badge--warn">حالتِ فایل — دیتابیس برای «خوانده‌شده» فعال نیست</span><?php endif; ?>
    </span>
</div>

<div class="admin-table-wrap"><table class="admin-table"><thead>
<tr><th></th><th>تاریخ</th><th>نام</th><th>ایمیل</th><th>موضوع</th><th>منبع</th><th>عملیات</th></tr>
</thead><tbody>
<?php foreach ($allMessages as $msg):
    $ref = (string) ($msg['ref'] ?? '');
    if ($ref === '') { continue; }
    $isRead  = !empty($msg['read_at']);
    $src     = (string) ($msg['source'] ?? '');
    $srcLbl  = $src === 'db' ? 'دیتابیس' : ($src === 'panel' ? 'پنل (قدیمی)' : 'فرم تماس (فایل)');
    $srcCls  = $src === 'db' ? 'admin-badge--success' : 'admin-badge--info';
?>
<tr<?= !$isRead && $src === 'db' ? ' class="admin-row--unread"' : '' ?>>
<td><?php if (!$isRead && $src === 'db'): ?><span class="admin-badge admin-badge--warning-dot" title="جدید"><?= ha_icon('mail', 12) ?></span><?php endif; ?></td>
<td class="muted-sm"><?= e($msg['time'] ?? '') ?></td>
<td><?= e($msg['name'] ?? '') ?></td>
<td dir="ltr"><?= e($msg['email'] ?? '') ?></td>
<td><?= e($msg['subject'] ?? '') ?></td>
<td><span class="admin-badge <?= e($srcCls) ?>"><?= e($srcLbl) ?></span></td>
<td class="actions">
    <a class="btn btn--ghost btn--sm" href="<?= e(url('admin_message_view', ['slug' => $ref])) ?>"><?= ha_icon('eye', 14) ?> <?= (!$isRead && $src === 'db') ? 'مشاهده و خوانده‌شدن' : 'مشاهده' ?></a>
    <form method="post" action="<?= e(url('admin_message_delete')) ?>" class="inline-form"
          data-confirm="پیامِ «<?= e(($msg['subject'] ?? '') !== '' ? $msg['subject'] : ($msg['name'] ?? '—')) ?>» برای همیشه حذف شود؟">
        <?= csrf_field() ?><input type="hidden" name="ref" value="<?= e($ref) ?>">
        <button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button>
    </form>
</td>
</tr>
<?php endforeach; ?>
<?php if ($allMessages === []): ?><tr><td colspan="7" class="admin-empty">هنوز پیامی دریافت نشده.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
