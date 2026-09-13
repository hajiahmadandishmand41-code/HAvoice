<?php
/**
 * HAvoice Admin — فهرستِ پیام‌های تماس
 *
 * هر پیام یک ref پایدار دارد (csv:N یا pan:N) که هم برایِ «مشاهده» و هم
 * برایِ «حذف» فرستاده می‌شود. پیش‌تر اندیسِ آرایه‌ی reverse‌شده فرستاده
 * می‌شد و مقصدها آن را روی آرایه‌های دیگری ایندکس می‌زدند ⇒ پیامِ اشتباه.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$allMessages = admin_messages_all();
$csvCount    = count(contact_messages_read());
$panelCount  = count(admin_load('messages'));
$flash       = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>"><?= e($flash['message']) ?></div><?php endif; ?>

<div class="admin-toolbar">
    <span class="muted-sm"><?= fa_num(count($allMessages)) ?> پیام · <?= fa_num($csvCount) ?> از فرمِ تماس · <?= fa_num($panelCount) ?> از پنل</span>
</div>

<div class="admin-table-wrap"><table class="admin-table"><thead>
<tr><th>تاریخ</th><th>نام</th><th>ایمیل</th><th>موضوع</th><th>منبع</th><th>عملیات</th></tr>
</thead><tbody>
<?php foreach ($allMessages as $msg):
    $ref = (string) ($msg['ref'] ?? '');
    if ($ref === '') { continue; }
?>
<tr>
<td class="muted-sm"><?= e($msg['time'] ?? '') ?></td>
<td><?= e($msg['name'] ?? '') ?></td>
<td dir="ltr"><?= e($msg['email'] ?? '') ?></td>
<td><?= e($msg['subject'] ?? '') ?></td>
<td><span class="admin-badge <?= ($msg['source'] ?? '') === 'panel' ? 'admin-badge--success' : 'admin-badge--info' ?>"><?= e(($msg['source'] ?? '') === 'panel' ? 'پنل' : 'فرم تماس') ?></span></td>
<td class="actions">
    <a class="btn btn--ghost btn--sm" href="<?= e(url('admin_message_view', ['slug' => $ref])) ?>"><?= ha_icon('eye', 14) ?> مشاهده</a>
    <form method="post" action="<?= e(url('admin_message_delete')) ?>" class="inline-form"
          data-confirm="پیامِ «<?= e($msg['subject'] ?: ($msg['name'] ?? '')) ?>» برای همیشه حذف شود؟">
        <?= csrf_field() ?><input type="hidden" name="ref" value="<?= e($ref) ?>">
        <button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button>
    </form>
</td>
</tr>
<?php endforeach; ?>
<?php if ($allMessages === []): ?><tr><td colspan="6" class="admin-empty">هنوز پیامی دریافت نشده.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
