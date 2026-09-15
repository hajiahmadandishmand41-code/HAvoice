<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$panelMedia = admin_load('media');
$panelSlugs = [];
foreach ($panelMedia as $m) { if (($m['type'] ?? '') === 'video') $panelSlugs[slugify((string) ($m['slug'] ?? ''))] = true; }
$allVideos = array_values(array_filter(media_all(), fn($m) => ($m['type'] ?? '') === 'video'));
?>
<?= admin_flash() ?>
<div class="admin-toolbar">
<a class="btn btn--primary" href="<?= e(url('admin_video_edit')) ?>"><?= ha_icon('play',16) ?> ویدیو جدید</a>
<span class="muted-sm"><?= fa_num(count($allVideos)) ?> ویدیو</span>
</div>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>عنوان</th><th>حوزه</th><th>وضعیت</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allVideos as $v):
    $slug = slugify((string) ($v['slug'] ?? ''));
    $isPanel = isset($panelSlugs[$slug]);
?>
<tr<?= ha_is_published($v) ? '' : ' class="admin-row--draft"' ?>>
<td><?= e($v['title'] ?? '') ?></td>
<td><span class="badge badge--soft"><?= e(category_label(ha_item_field_slug($v), (string) ($v['category'] ?? ''))) ?></span></td>
<td><?= admin_status_badge($v) ?></td>
<td><?= admin_source_badge($isPanel) ?></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_video_edit', ['slug' => $slug])) ?>"><?= ha_icon('edit', 14) ?> ویرایش</a>
<?= admin_status_toggle('video', $slug, $v) ?>
<?php if ($isPanel): ?><form method="post" action="<?= e(url('admin_video_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($slug) ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if ($allVideos === []): ?><tr><td colspan="5" class="admin-empty">ویدیویی نیست.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
