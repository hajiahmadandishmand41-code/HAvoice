<?php
/**
 * HAvoice Admin — مدیریتِ نظرات عمومی
 *
 * نظرات از MySQL می‌آیند (includes/comments.php). هر ردیف: تأیید/پنهان‌سازی
 * (تغییر وضعیت) و حذف — هر دو با POST + CSRF (الگویِ بقیه‌ی پنل).
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$flash  = flash();
$filter = (string) ($_GET['status'] ?? '');
if (!in_array($filter, ['', 'pending', 'approved'], true)) {
    $filter = '';
}

$dbReady = comments_db() !== null;
$counts  = comments_admin_counts();
$rows    = comments_admin_list($filter);

$tabUrl = static function (string $status): string {
    return $status === '' ? url('admin_comments') : url('admin_comments', ['status' => $status]);
};
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>"><?= e($flash['message']) ?></div><?php endif; ?>

<?php if (!$dbReady): ?>
<div class="alert alert--warning" role="status">
    دیتابیس پیکربندی نشده؛ نظرات در فایل ذخیره می‌شوند و از همین صفحه قابلِ تأیید/حذف هستند.
</div>
<?php endif; ?>

<div class="admin-toolbar">
    <nav class="chip-row mt-0" aria-label="فیلترِ وضعیت">
        <a class="chip<?= $filter === '' ? ' chip--active' : '' ?>" href="<?= e($tabUrl('')) ?>">همه (<?= fa_num($counts['all']) ?>)</a>
        <a class="chip<?= $filter === 'pending' ? ' chip--active' : '' ?>" href="<?= e($tabUrl('pending')) ?>">در انتظارِ تأیید (<?= fa_num($counts['pending']) ?>)</a>
        <a class="chip<?= $filter === 'approved' ? ' chip--active' : '' ?>" href="<?= e($tabUrl('approved')) ?>">منتشرشده (<?= fa_num($counts['approved']) ?>)</a>
    </nav>
    <span class="muted-sm"><?= fa_num(count($rows)) ?> ردیف در این نمایش</span>
</div>

<div class="admin-table-wrap"><table class="admin-table"><thead>
<tr><th>تاریخ</th><th>نام</th><th>متن نظر</th><th>وضعیت</th><th>عملیات</th></tr>
</thead><tbody>
<?php foreach ($rows as $r):
    $id = (int) ($r['id'] ?? 0);
    $status = (string) ($r['status'] ?? '');
    $dt = (string) ($r['created_at'] ?? '');
    $dateFa = $dt !== '' ? fa_num((new DateTimeImmutable($dt))->format('Y/m/d H:i')) : '';
?>
<tr>
<td class="muted-sm" style="white-space:nowrap"><?= e($dateFa) ?></td>
<td><?= e($r['name'] ?? '') ?></td>
<td class="msg-body-text" style="max-width:420px"><?= e($r['body'] ?? '') ?></td>
<td><span class="admin-badge <?= $status === 'approved' ? 'admin-badge--success' : 'admin-badge--info' ?>"><?= $status === 'approved' ? 'منتشرشده' : 'در انتظار' ?></span></td>
<td class="actions">
    <?php if ($status === 'approved'): ?>
    <form method="post" action="<?= e(url('admin_comment_status')) ?>" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <input type="hidden" name="status" value="pending">
        <button class="btn btn--ghost btn--sm" type="submit"><?= ha_icon('eye', 14) ?> پنهان</button>
    </form>
    <?php else: ?>
    <form method="post" action="<?= e(url('admin_comment_status')) ?>" class="inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <input type="hidden" name="status" value="approved">
        <button class="btn btn--primary btn--sm" type="submit"><?= ha_icon('check', 14) ?> تأیید و انتشار</button>
    </form>
    <?php endif; ?>
    <form method="post" action="<?= e(url('admin_comment_delete')) ?>" class="inline-form" data-confirm="این نظر برای همیشه حذف شود؟">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button>
    </form>
</td>
</tr>
<?php endforeach; ?>
<?php if ($rows === []): ?>
<tr><td colspan="5" class="admin-empty"><?= $filter === 'pending' ? 'نظری در انتظارِ تأیید نیست.' : ($filter === 'approved' ? 'هنوز نظری منتشر نشده است.' : 'هنوز نظری ثبت نشده است.') ?></td></tr>
<?php endif; ?>
</tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
