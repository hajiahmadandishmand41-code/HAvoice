<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$adminMedia = admin_load('media');
$adminAudios = array_values(array_filter($adminMedia, fn($m) => ($m['type'] ?? '') === 'audio'));
$allAudios = array_merge(audios(), $adminAudios);
$srcCount = count(audios());
?>
<a class="btn btn--primary" href="<?= e(url('admin_audio_edit')) ?>"><?= ha_icon('headphones',16) ?> صوت جدید</a>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>عنوان</th><th>دسته</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allAudios as $i => $a): $isData = $i < $srcCount; ?>
<tr><td><?= e($a['title'] ?? '') ?></td><td><span class="badge badge--soft"><?= e($a['category'] ?? '') ?></span></td>
<td><span class="admin-badge <?= $isData ? 'admin-badge--info' : 'admin-badge--success' ?>"><?= $isData ? 'فایل' : 'پنل' ?></span></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_audio_edit', ['slug' => $a['slug'] ?? ''])) ?>">ویرایش</a>
<?php if (!$isData): ?><form method="post" action="<?= e(url('admin_audio_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($a['slug'] ?? '') ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
