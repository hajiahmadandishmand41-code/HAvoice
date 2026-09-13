<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$adminResearch = admin_load('research');
$allResearch = array_merge(research_items(), $adminResearch);
$srcCount = count(research_items());
?>
<a class="btn btn--primary" href="<?= e(url('admin_research_edit')) ?>"><?= ha_icon('research',16) ?> پژوهش جدید</a>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>عنوان</th><th>دسته</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allResearch as $i => $r): $isData = $i < $srcCount; ?>
<tr><td><?= e($r['title'] ?? '') ?></td><td><span class="badge badge--soft"><?= e($r['category'] ?? '') ?></span></td>
<td><span class="admin-badge <?= $isData ? 'admin-badge--info' : 'admin-badge--success' ?>"><?= $isData ? 'فایل' : 'پنل' ?></span></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_research_edit', ['slug' => $r['slug'] ?? ''])) ?>">ویرایش</a>
<?php if (!$isData): ?><form method="post" action="<?= e(url('admin_research_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($r['slug'] ?? '') ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
