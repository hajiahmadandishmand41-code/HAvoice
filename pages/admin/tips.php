<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$panelIds = [];
foreach (admin_load('tips') as $t) { $panelIds[slugify((string) ($t['id'] ?? ''))] = true; }
$allTips = tips_all();
?>
<?= admin_flash() ?>
<div class="admin-toolbar">
<a class="btn btn--primary" href="<?= e(url('admin_tip_edit')) ?>"><?= ha_icon('sparkle',16) ?> نکته جدید</a>
<span class="muted-sm"><?= fa_num(count($allTips)) ?> نکته</span>
</div>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>متن</th><th>دسته</th><th>وضعیت</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allTips as $t):
    $id = (string) ($t['id'] ?? '');
    $isPanel = isset($panelIds[slugify($id)]);
?>
<tr<?= ha_is_published($t) ? '' : ' class="admin-row--draft"' ?>>
<td><?= e(mb_strimwidth($t['text'] ?? '', 0, 60, '…', 'UTF-8')) ?></td>
<td><span class="badge badge--soft"><?= e($t['category'] ?? '') ?></span></td>
<td><?= admin_status_badge($t) ?></td>
<td><?= admin_source_badge($isPanel) ?></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_tip_edit', ['slug' => $id])) ?>"><?= ha_icon('edit', 14) ?> ویرایش</a>
<?= admin_status_toggle('tip', $id, $t) ?>
<?php if ($isPanel): ?><form method="post" action="<?= e(url('admin_tip_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($id) ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if ($allTips === []): ?><tr><td colspan="5" class="admin-empty">نکته‌ای نیست.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
