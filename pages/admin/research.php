<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$panelSlugs = [];
foreach (admin_load('research') as $r) { $panelSlugs[slugify((string) ($r['slug'] ?? ''))] = true; }
$allResearch = research_all();
?>
<?= admin_flash() ?>
<div class="admin-toolbar">
<a class="btn btn--primary" href="<?= e(url('admin_research_edit')) ?>"><?= ha_icon('research',16) ?> پژوهش جدید</a>
<span class="muted-sm"><?= fa_num(count($allResearch)) ?> پژوهش</span>
</div>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>عنوان</th><th>حوزه</th><th>تاریخ</th><th>وضعیت</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allResearch as $r):
    $slug = slugify((string) ($r['slug'] ?? ''));
    $isPanel = isset($panelSlugs[$slug]);
?>
<tr<?= ha_is_published($r) ? '' : ' class="admin-row--draft"' ?>>
<td><a href="<?= e(url('research', ['slug' => $slug])) ?>"><?= e($r['title'] ?? '') ?></a></td>
<td><span class="badge badge--soft"><?= e(category_label(ha_item_field_slug($r), (string) ($r['category'] ?? ''))) ?></span></td>
<td class="muted-sm"><?= e($r['date_fa'] ?? '') ?></td>
<td><?= admin_status_badge($r) ?></td>
<td><?= admin_source_badge($isPanel) ?></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_research_edit', ['slug' => $slug])) ?>"><?= ha_icon('edit', 14) ?> ویرایش</a>
<?= admin_status_toggle('research', $slug, $r) ?>
<?php if ($isPanel): ?><form method="post" action="<?= e(url('admin_research_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($slug) ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if ($allResearch === []): ?><tr><td colspan="6" class="admin-empty">پژوهشی نیست.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
