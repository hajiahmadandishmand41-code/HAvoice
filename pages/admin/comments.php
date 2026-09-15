<?php
/**
 * HAvoice Admin — مدیریتِ نظرات عمومی
 *
 * نظرات از MySQL می‌آیند (includes/comments.php) — Database-driven واقعی.
 * چهار عمل: ساخت (Create)، تأیید (Approve)، پنهان‌سازی (Hide)، حذف (Delete)
 * — هر یک با POST + CSRF. وضعیت: pending / approved / hidden.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$flash  = flash();
$filter = (string) ($_GET['status'] ?? '');
if (!in_array($filter, ['', 'pending', 'approved', 'hidden'], true)) {
    $filter = '';
}

$dbReady = comments_db() !== null;
$counts  = comments_admin_counts();
$rows    = $dbReady ? comments_admin_list($filter) : [];

$tabUrl = static function (string $status): string {
    return $status === '' ? url('admin_comments') : url('admin_comments', ['status' => $status]);
};

$statusLabel = [
    'pending'  => 'در انتظار',
    'approved' => 'منتشرشده',
    'hidden'   => 'پنهان‌شده',
];
$statusClass = [
    'pending'  => 'admin-badge--info',
    'approved' => 'admin-badge--success',
    'hidden'   => 'admin-badge--warn',
];
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>"><?= e($flash['message']) ?></div><?php endif; ?>

<?php if (!$dbReady): ?>
<div class="alert alert--error" role="alert">
    اتصالِ دیتابیسِ نظرات برقرار نشد. مقادیرِ <code>HA_DB_HOST / HA_DB_NAME / HA_DB_USER / HA_DB_PASS</code>
    را در <code>config/config.php</code> تنظیم کنید (راهنما در همان فایل و <code>sql/001-create-comments.sql</code>).
</div>
<?php else: ?>

<div class="admin-toolbar">
    <nav class="chip-row mt-0" aria-label="فیلترِ وضعیت">
        <a class="chip<?= $filter === '' ? ' chip--active' : '' ?>" href="<?= e($tabUrl('')) ?>">همه (<?= fa_num($counts['all']) ?>)</a>
        <a class="chip<?= $filter === 'pending' ? ' chip--active' : '' ?>" href="<?= e($tabUrl('pending')) ?>">در انتظارِ تأیید (<?= fa_num($counts['pending']) ?>)</a>
        <a class="chip<?= $filter === 'approved' ? ' chip--active' : '' ?>" href="<?= e($tabUrl('approved')) ?>">منتشرشده (<?= fa_num($counts['approved']) ?>)</a>
        <a class="chip<?= $filter === 'hidden' ? ' chip--active' : '' ?>" href="<?= e($tabUrl('hidden')) ?>">پنهان‌شده (<?= fa_num($counts['hidden']) ?>)</a>
    </nav>
    <span class="muted-sm"><?= fa_num(count($rows)) ?> ردیف در این نمایش</span>
</div>

<section class="admin-card mb-md" id="new-comment">
    <h3 class="admin-card__title mb-sm"><?= ha_icon('plus', 15) ?> ثبتِ نظر/تجربه‌ی جدید (توسطِ مدیر)</h3>
    <form method="post" action="<?= e(url('admin_comment_save')) ?>" class="admin-inline-form">
        <?= csrf_field() ?>
        <div class="field-row field-row--2">
            <div class="field"><label for="cm-name">نامِ نمایش‌یافته <span class="req">*</span></label>
                <input class="input" id="cm-name" name="name" maxlength="60" required></div>
            <div class="field"><label for="cm-email">ایمیل (اختیاری — نمایش داده نمی‌شود)</label>
                <input class="input" id="cm-email" name="email" type="email" maxlength="190"></div>
        </div>
        <div class="field"><label for="cm-body">متنِ نظر/تجربه <span class="req">*</span></label>
            <textarea class="input textarea" id="cm-body" name="body" rows="3" maxlength="5000" required></textarea></div>
        <div class="field-row field-row--2">
            <div class="field"><label for="cm-status">وضعیت</label>
                <select class="input" id="cm-status" name="status">
                    <option value="approved" selected>منتشرشده — بلافاصله نمایش داده شود</option>
                    <option value="pending">در انتظارِ تأیید — ابتدا در صف بنشیند</option>
                    <option value="hidden">پنهان — نمایش داده نشود</option>
                </select></div>
            <div class="actions mt-sm"><button class="btn btn--primary" type="submit"><?= ha_icon('check', 14) ?> ثبت نظر</button></div>
        </div>
    </form>
</section>

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
<td><span class="admin-badge <?= $statusClass[$status] ?? 'admin-badge--info' ?>"><?= e($statusLabel[$status] ?? $status) ?></span></td>
<td class="actions">
<?php if ($status === 'approved'): ?>
    <form method="post" action="<?= e(url('admin_comment_status')) ?>" class="inline-form"
          data-confirm="این نظر از دیدِ عمومی پنهان شود؟">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="status" value="hidden">
        <button class="btn btn--ghost btn--sm" type="submit"><?= ha_icon('eye', 14) ?> پنهان کردن</button>
    </form>
<?php elseif ($status === 'hidden'): ?>
    <form method="post" action="<?= e(url('admin_comment_status')) ?>" class="inline-form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="status" value="approved">
        <button class="btn btn--primary btn--sm" type="submit"><?= ha_icon('check', 14) ?> بازنشر (انتشار)</button>
    </form>
<?php else: // pending ?>
    <form method="post" action="<?= e(url('admin_comment_status')) ?>" class="inline-form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="status" value="approved">
        <button class="btn btn--primary btn--sm" type="submit"><?= ha_icon('check', 14) ?> تأیید و انتشار</button>
    </form>
<?php endif; ?>
<?php if ($status !== 'pending'): ?>
    <form method="post" action="<?= e(url('admin_comment_status')) ?>" class="inline-form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="status" value="pending">
        <button class="btn btn--ghost btn--sm" type="submit"><?= ha_icon('clock', 14) ?> بازگشت به انتظار</button>
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
<tr><td colspan="5" class="admin-empty"><?= $filter === 'pending' ? 'نظری در انتظارِ تأیید نیست.' : ($filter === 'approved' ? 'هنوز نظری منتشر نشده است.' : ($filter === 'hidden' ? 'نظری پنهان نیست.' : 'هنوز نظری ثبت نشده است.')) ?></td></tr>
<?php endif; ?>
</tbody></table></div>
<?php endif; ?>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
