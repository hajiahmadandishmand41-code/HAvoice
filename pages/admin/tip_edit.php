<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$id = param('slug'); $item = null;
if ($id !== '') { foreach (admin_load('tips') as $t) { if (($t['id'] ?? '') === $id) { $item = $t; break; } } if (!$item) foreach (tips() as $t) { if (($t['id'] ?? '') === $id) { $item = $t; break; } } }
$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<form class="admin-form" method="post" action="<?= e(url('admin_tip_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_id" value="<?= e($id) ?>">
<div class="field"><label>شناسه (id) *</label><input class="input" type="text" name="id" value="<?= e($item['id'] ?? 'tip-' . bin2hex(random_bytes(4))) ?>" required dir="ltr"></div>
<div class="field"><label>متن نکته *</label><textarea class="input" name="text" rows="3" required><?= e($item['text'] ?? '') ?></textarea></div>
<div class="field"><label>تمرین فوری</label><input class="input" type="text" name="try" value="<?= e($item['try'] ?? '') ?>"></div>
<div class="field"><label>دسته</label><input class="input" type="text" name="category" value="<?= e($item['category'] ?? 'عمومی') ?>"></div>
<div class="admin-form-actions"><button class="btn btn--primary" type="submit">ذخیره</button><a class="btn btn--ghost" href="<?= e(url('admin_tips')) ?>">بازگشت</a></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
