<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$adminEx = admin_load('exercises');
$allEx = array_merge(exercises(), $adminEx);
$srcCount = count(exercises());
?>
<a class="btn btn--primary" href="<?= e(url('admin_exercise_edit')) ?>"><?= ha_icon('timer',16) ?> تمرین جدید</a>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>عنوان</th><th>سطح</th><th>تمرکز</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allEx as $i => $ex): $isData = $i < $srcCount; ?>
<tr><td><?= e($ex['title'] ?? '') ?></td><td><span class="badge badge--level"><?= e($ex['level'] ?? '') ?></span></td><td><?= e($ex['focus'] ?? '') ?></td>
<td><span class="admin-badge <?= $isData ? 'admin-badge--info' : 'admin-badge--success' ?>"><?= $isData ? 'فایل' : 'پنل' ?></span></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_exercise_edit', ['slug' => $ex['id'] ?? $ex['slug'] ?? ''])) ?>">ویرایش</a>
<?php if (!$isData): ?><form method="post" action="<?= e(url('admin_exercise_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($ex['id'] ?? '') ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
