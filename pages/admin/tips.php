<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$adminTips = admin_load('tips');
$allTips = array_merge(tips(), $adminTips);
$srcCount = count(tips());
?>
<a class="btn btn--primary" href="<?= e(url('admin_tip_edit')) ?>"><?= ha_icon('sparkle',16) ?> نکته جدید</a>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>متن</th><th>دسته</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allTips as $i => $t): $isData = $i < $srcCount; ?>
<tr><td><?= e(mb_strimwidth($t['text'] ?? '', 0, 60, '…', 'UTF-8')) ?></td><td><span class="badge badge--soft"><?= e($t['category'] ?? '') ?></span></td>
<td><span class="admin-badge <?= $isData ? 'admin-badge--info' : 'admin-badge--success' ?>"><?= $isData ? 'فایل' : 'پنل' ?></span></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_tip_edit', ['slug' => $t['id'] ?? ''])) ?>">ویرایش</a>
<?php if (!$isData): ?><form method="post" action="<?= e(url('admin_tip_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($t['id'] ?? '') ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
