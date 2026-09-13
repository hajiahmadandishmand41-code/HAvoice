<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$adminMedia = admin_load('media');
$adminVideos = array_values(array_filter($adminMedia, fn($m) => ($m['type'] ?? '') === 'video'));
$allVideos = array_merge(videos(), $adminVideos);
$srcCount = count(videos());
?>
<a class="btn btn--primary" href="<?= e(url('admin_video_edit')) ?>"><?= ha_icon('play',16) ?> ویدیو جدید</a>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>عنوان</th><th>دسته</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allVideos as $i => $v): $isData = $i < $srcCount; ?>
<tr><td><?= e($v['title'] ?? '') ?></td><td><span class="badge badge--soft"><?= e($v['category'] ?? '') ?></span></td>
<td><span class="admin-badge <?= $isData ? 'admin-badge--info' : 'admin-badge--success' ?>"><?= $isData ? 'فایل' : 'پنل' ?></span></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_video_edit', ['slug' => $v['slug'] ?? ''])) ?>">ویرایش</a>
<?php if (!$isData): ?><form method="post" action="<?= e(url('admin_video_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($v['slug'] ?? '') ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
