<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$panelIds = [];
foreach (admin_load('exercises') as $ex) { $panelIds[slugify((string) ($ex['id'] ?? ''))] = true; }
$allEx = exercises_all();
?>
<?= admin_flash() ?>
<div class="admin-toolbar">
<a class="btn btn--primary" href="<?= e(url('admin_exercise_edit')) ?>"><?= ha_icon('timer',16) ?> تمرین جدید</a>
<span class="muted-sm"><?= fa_num(count($allEx)) ?> تمرین · هر تمرین می‌تواند به یک درس یا دوره متصل شود.</span>
</div>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>ترتیب</th><th>عنوان</th><th>سطح</th><th>درسِ متصل</th><th>دوره</th><th>وضعیت</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allEx as $ex):
    $id = (string) ($ex['id'] ?? '');
    $isPanel = isset($panelIds[slugify($id)]);
    $lessonSlug = slugify((string) ($ex['lesson'] ?? ''));
    $lessonInfo = $lessonSlug !== '' ? course_find_lesson($lessonSlug) : null;
    $courseSlug = slugify((string) ($ex['course'] ?? ($lessonInfo['course']['slug'] ?? '')));
    $courseRow  = $courseSlug !== '' ? find_course($courseSlug) : null;
?>
<tr<?= ha_is_published($ex) ? '' : ' class="admin-row--draft"' ?>>
<td><span class="admin-badge admin-badge--info"><?= fa_num((int) ($ex['order'] ?? 0)) ?></span></td>
<td><?= e($ex['title'] ?? '') ?><span class="muted-sm"> · <?= e($ex['focus'] ?? '') ?></span></td>
<td><span class="badge badge--level"><?= e($ex['level'] ?? '') ?></span></td>
<td class="muted-sm"><?= $lessonInfo !== null ? e($lessonInfo['lesson']['title'] ?? $lessonSlug) : ($lessonSlug !== '' ? e($lessonSlug) : '—') ?></td>
<td class="muted-sm"><?php if ($courseRow !== null): ?><a href="<?= e(url('admin_course_edit', ['slug' => (string) ($courseRow['slug'] ?? '')])) ?>"><?= e($courseRow['title'] ?? $courseSlug) ?></a><?php else: ?>—<?php endif; ?></td>
<td><?= admin_status_badge($ex) ?></td>
<td><?= admin_source_badge($isPanel) ?></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_exercise_edit', ['slug' => $id])) ?>"><?= ha_icon('edit', 14) ?> ویرایش</a>
<?= admin_status_toggle('exercise', $id, $ex) ?>
<?php if ($isPanel): ?><form method="post" action="<?= e(url('admin_exercise_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($id) ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if ($allEx === []): ?><tr><td colspan="8" class="admin-empty">تمرینی نیست؛ اولین تمرین را بسازید.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
