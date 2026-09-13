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
