<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$users = auth_load_users();
/* مدیرِ اصلی = همان کاربری که bootstrap یک‌باره او را مدیر کرد (پرچمِ ماندگار
   در storage/admin/auth_bootstrap.json). اگر نصبِ قدیمی پرچم نداشته باشد،
   اولین کاربرِ فهرست به‌عنوان مدیرِ اصلی در نظر گرفته می‌شود. */
$primaryId = (string) (auth_bootstrap_state()['admin_id'] ?? '');
if ($primaryId === '') {
    $primaryId = (string) ($users[0]['id'] ?? '');
}
$adminCount = 0;
foreach ($users as $u) {
    if ((string) ($u['role'] ?? '') === 'admin') { $adminCount++; }
}
$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>نام</th><th>ایمیل</th><th>نقش</th><th>تاریخ عضویت</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($users as $u):
    $id = (string) ($u['id'] ?? '');
    /* نقش فقط از داده‌ی ذخیره‌شده خوانده می‌شود (auth_normalize_roles() آن را
       برای نصب‌های قدیمی صریح می‌کند) — هیچ حدسی در UI نیست. */
    $isAdmin = (string) ($u['role'] ?? '') === 'admin';
    $isPrimary = $id !== '' && $id === $primaryId;
    /* مدیرِ اصلی هرگز حذف‌شدنی نیست؛ خودِ user_delete.php هم این را بررسی
       می‌کند، پس این فقط لایه‌ی UI است. */
    $canDelete = $id !== '' && !$isPrimary;
?>
<tr><td><?= e($u['name'] ?? '') ?></td><td dir="ltr"><?= e($u['email'] ?? '') ?></td>
<td><span class="admin-badge <?= $isAdmin ? 'admin-badge--success' : 'admin-badge--info' ?>"><?= $isAdmin ? 'مدیر' : 'کاربر' ?></span><?php if ($isPrimary): ?> <span class="admin-badge admin-badge--info">مدیرِ اصلی</span><?php endif; ?></td>
<td class="muted-sm"><?= e($u['created_at'] ?? '') ?></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_user_edit', ['slug' => $id])) ?>"><?= ha_icon('edit', 14) ?> ویرایش</a>
<?php if ($canDelete): ?>
<form method="post" action="<?= e(url('admin_user_delete')) ?>" data-confirm="کاربر «<?= e($u['name'] ?? '') ?>» حذف شود؟">
<?= csrf_field() ?><input type="hidden" name="id" value="<?= e($id) ?>">
<button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form>
<?php else: ?>
<span class="muted-sm">مدیرِ اصلی</span>
<?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if ($users === []): ?><tr><td colspan="5" class="admin-empty">هنوز کاربری ثبت‌نام نکرده است.</td></tr><?php endif; ?>
</tbody></table></div>
<p class="muted-sm"><?= fa_num(count($users)) ?> کاربر · <?= fa_num($adminCount) ?> مدیر —
<?php if (auth_bootstrap_state()['done']): ?>
    bootstrap مدیرِ اولیه انجام شده (<?= e(auth_bootstrap_state()['admin_at'] ?: '—') ?>)؛ از این پس هیچ کاربری خودکار مدیر نمی‌شود و نقش فقط از همین صفحه تغییر می‌کند.
<?php else: ?>
    هنوز هیچ کاربری ثبت‌نام نکرده است؛ «اولین» کاربری که ثبت‌نام کند یک‌بار مدیرِ اولیه می‌شود و بعد از آن این امکان بسته می‌شود.
<?php endif; ?></p>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
