<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$adminCats = admin_load('categories');
$allCats = array_merge(categories(), $adminCats);
$srcCount = count(categories());
?>
<a class="btn btn--primary" href="<?= e(url('admin_category_edit')) ?>"><?= ha_icon('compass',16) ?> حوزه جدید</a>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>عنوان</th><th>نامک</th><th>آیکون</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allCats as $i => $c): $isData = $i < $srcCount; ?>
<tr><td><?= e($c['title'] ?? '') ?></td><td dir="ltr"><?= e($c['slug'] ?? '') ?></td><td><?= e($c['icon'] ?? '') ?></td>
<td><span class="admin-badge <?= $isData ? 'admin-badge--info' : 'admin-badge--success' ?>"><?= $isData ? 'فایل' : 'پنل' ?></span></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_category_edit', ['slug' => $c['slug'] ?? ''])) ?>">ویرایش</a>
<?php if (!$isData): ?><form method="post" action="<?= e(url('admin_category_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($c['slug'] ?? '') ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
