<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$panelMedia = admin_load('media');
$panelSlugs = [];
foreach ($panelMedia as $m) { if (($m['type'] ?? '') === 'audio') $panelSlugs[slugify((string) ($m['slug'] ?? ''))] = true; }
$allAudios = array_values(array_filter(media_all(), fn($m) => ($m['type'] ?? '') === 'audio'));
?>
<?= admin_flash() ?>
<div class="admin-toolbar">
<a class="btn btn--primary" href="<?= e(url('admin_audio_edit')) ?>"><?= ha_icon('headphones',16) ?> صوت جدید</a>
<span class="muted-sm"><?= fa_num(count($allAudios)) ?> فایل صوتی</span>
</div>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>عنوان</th><th>حوزه</th><th>وضعیت</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allAudios as $a):
    $slug = slugify((string) ($a['slug'] ?? ''));
    $isPanel = isset($panelSlugs[$slug]);
?>
<tr<?= ha_is_published($a) ? '' : ' class="admin-row--draft"' ?>>
<td><?= e($a['title'] ?? '') ?></td>
<td><span class="badge badge--soft"><?= e(category_label(ha_item_field_slug($a), (string) ($a['category'] ?? ''))) ?></span></td>
<td><?= admin_status_badge($a) ?></td>
<td><?= admin_source_badge($isPanel) ?></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_audio_edit', ['slug' => $slug])) ?>"><?= ha_icon('edit', 14) ?> ویرایش</a>
<?= admin_status_toggle('audio', $slug, $a) ?>
<?php if ($isPanel): ?><form method="post" action="<?= e(url('admin_audio_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($slug) ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if ($allAudios === []): ?><tr><td colspan="5" class="admin-empty">فایل صوتی نیست.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
