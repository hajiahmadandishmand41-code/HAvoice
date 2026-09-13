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
<?php if (!$isData): ?><form method="post" action="<?= e(url('admin_exercise_delete')) ?>" style="display:inline" onsubmit="return confirm('حذف شود؟')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($ex['id'] ?? '') ?>"><button class="btn btn--ghost btn--sm" type="submit" style="color:var(--danger)">حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/exercise_edit.php << 'EOF'
<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$id = param('slug'); $item = null;
if ($id !== '') { foreach (admin_load('exercises') as $ex) { if (($ex['id'] ?? '') === $id) { $item = $ex; break; } } if (!$item) foreach (exercises() as $ex) { if (($ex['id'] ?? '') === $id) { $item = $ex; break; } } }
$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<form class="admin-form" method="post" action="<?= e(url('admin_exercise_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_id" value="<?= e($id) ?>">
<div class="field"><label>شناسه (id) *</label><input class="input" type="text" name="id" value="<?= e($item['id'] ?? ($id !== '' ? $id : 'ex-' . bin2hex(random_bytes(4)))) ?>" required dir="ltr"></div>
<div class="field"><label>عنوان *</label><input class="input" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required></div>
<div class="field"><label>سطح</label><input class="input" type="text" name="level" value="<?= e($item['level'] ?? 'عمومی') ?>"></div>
<div class="field"><label>تمرکز</label><input class="input" type="text" name="focus" value="<?= e($item['focus'] ?? '') ?>"></div>
<div class="field"><label>هدف</label><textarea class="input" name="goal" rows="2"><?= e($item['goal'] ?? '') ?></textarea></div>
<div class="field"><label>موفقیت</label><input class="input" type="text" name="success" value="<?= e($item['success'] ?? '') ?>"></div>
<div class="field"><label>مدت (ثانیه)</label><input class="input" type="number" name="seconds" value="<?= e((string)($item['seconds'] ?? 180)) ?>"></div>
<div class="field"><label>مراحل (هر خط یک مرحله)</label><textarea class="input" name="steps_text" rows="5"><?= e(implode("\n", $item['steps'] ?? [])) ?></textarea></div>
<div class="field"><label>موضوعات بداهه (هر خط یک موضوع)</label><textarea class="input" name="topics_text" rows="3"><?= e(implode("\n", $item['topics'] ?? [])) ?></textarea></div>
<div class="admin-form-actions"><button class="btn btn--primary" type="submit">ذخیره</button><a class="btn btn--ghost" href="<?= e(url('admin_exercises')) ?>">بازگشت</a></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/exercise_save.php << 'EOF'
<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_exercises'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_exercises')); }
$id = trim((string)($_POST['id'] ?? '')); $title = trim((string)($_POST['title'] ?? ''));
if ($id === '' || $title === '') { flash('error','شناسه و عنوان الزامی.'); redirect(url('admin_exercise_edit')); }
$items = admin_load('exercises');
$steps = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['steps_text'] ?? '')))));
$topics = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['topics_text'] ?? '')))));
$item = ['id'=>$id,'title'=>$title,'level'=>trim((string)($_POST['level']??'عمومی')),'focus'=>trim((string)($_POST['focus']??'')),'goal'=>trim((string)($_POST['goal']??'')),'success'=>trim((string)($_POST['success']??'')),'seconds'=>max(0,(int)($_POST['seconds']??180)),'steps'=>$steps,'topics'=>$topics];
$orig = (string)($_POST['original_id'] ?? ''); $found = false;
foreach ($items as $i => $ex) { if (($ex['id'] ?? '') === $orig || ($ex['id'] ?? '') === $id) { $items[$i] = $item; $found = true; break; } }
if (!$found) $items[] = $item;
admin_store('exercises', $items); flash('success','تمرین ذخیره شد.'); redirect(url('admin_exercises'));
