<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$adminTips = admin_load('tips');
$allTips = array_merge(tips(), $adminTips);
$srcCount = count(tips());
?>
<a class="btn btn--primary" href="<?= e(url('admin_tip_edit')) ?>"><?= ha_icon('sparkle',16) ?> نکته جدید</a>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>متن</th><th>دسته</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allTips as $i => $t): $isData = $i < $srcCount; ?>
<tr><td><?= e(mb_strimwidth($t['text'] ?? '', 0, 60, '…', 'UTF-8')) ?></td><td><span class="badge badge--soft"><?= e($t['category'] ?? '') ?></span></td>
<td><span class="admin-badge <?= $isData ? 'admin-badge--info' : 'admin-badge--success' ?>"><?= $isData ? 'فایل' : 'پنل' ?></span></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_tip_edit', ['slug' => $t['id'] ?? ''])) ?>">ویرایش</a>
<?php if (!$isData): ?><form method="post" action="<?= e(url('admin_tip_delete')) ?>" style="display:inline" onsubmit="return confirm('حذف شود؟')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($t['id'] ?? '') ?>"><button class="btn btn--ghost btn--sm" type="submit" style="color:var(--danger)">حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/tip_edit.php << 'EOF'
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

cat > pages/admin/tip_save.php << 'EOF'
<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_tips'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_tips')); }
$id = trim((string)($_POST['id'] ?? '')); $text = trim((string)($_POST['text'] ?? ''));
if ($id === '' || $text === '') { flash('error','شناسه و متن الزامی.'); redirect(url('admin_tip_edit')); }
$items = admin_load('tips');
$item = ['id'=>$id,'text'=>$text,'try'=>trim((string)($_POST['try']??'')),'category'=>trim((string)($_POST['category']??'عمومی'))];
$orig = (string)($_POST['original_id'] ?? ''); $found = false;
foreach ($items as $i => $t) { if (($t['id'] ?? '') === $orig || ($t['id'] ?? '') === $id) { $items[$i] = $item; $found = true; break; } }
if (!$found) $items[] = $item;
admin_store('tips', $items); flash('success','نکته ذخیره شد.'); redirect(url('admin_tips'));
