<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$panelSlugs = [];
foreach (admin_load('categories') as $c) { $panelSlugs[slugify((string) ($c['slug'] ?? ''))] = true; }
$allCats = ha_content_admin('categories');
?>
<?= admin_flash() ?>
<div class="admin-toolbar">
<a class="btn btn--primary" href="<?= e(url('admin_category_edit')) ?>"><?= ha_icon('compass',16) ?> حوزه جدید</a>
<span class="muted-sm"><?= fa_num(count($allCats)) ?> حوزه · حوزه‌ی مخفی از منو و صفحه‌ی حوزه‌ها ناپدید می‌شود ولی محتوای آن حذف نمی‌شود.</span>
</div>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>عنوان</th><th>نامک</th><th>آیکون</th><th>وضعیت</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allCats as $c):
    $slug = slugify((string) ($c['slug'] ?? ''));
    $isPanel = isset($panelSlugs[$slug]);
?>
<tr<?= ha_is_published($c) ? '' : ' class="admin-row--draft"' ?>>
<td><?= e($c['title'] ?? '') ?></td>
<td dir="ltr"><?= e($c['slug'] ?? '') ?></td>
<td><?= e($c['icon'] ?? '') ?></td>
<td><?= admin_status_badge($c) ?></td>
<td><?= admin_source_badge($isPanel) ?></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_category_edit', ['slug' => $slug])) ?>"><?= ha_icon('edit', 14) ?> ویرایش</a>
<?= admin_status_toggle('category', $slug, $c) ?>
<?php if ($isPanel): ?><form method="post" action="<?= e(url('admin_category_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($slug) ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if ($allCats === []): ?><tr><td colspan="6" class="admin-empty">حوزه‌ای نیست.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
