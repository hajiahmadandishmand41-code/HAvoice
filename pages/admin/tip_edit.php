<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$id = param('slug');
$item = null;
if ($id !== '') {
    foreach (tips_all() as $t) {
        if (slugify((string) ($t['id'] ?? '')) === slugify($id)) { $item = $t; break; }
    }
    if ($item === null) { flash('error', 'نکته پیدا نشد.'); redirect(url('admin_tips')); }
}
?>
<?= admin_flash() ?>
<form class="admin-form" method="post" action="<?= e(url('admin_tip_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_id" value="<?= e($id) ?>">
<div class="admin-card">
    <div class="admin-form-grid">
        <div class="field"><label for="t-id">شناسه (id) *</label><input class="input" id="t-id" type="text" name="id" value="<?= e($item['id'] ?? 'tip-' . bin2hex(random_bytes(4))) ?>" required dir="ltr" maxlength="60"></div>
        <div class="field"><label for="t-cat">دسته</label><input class="input" id="t-cat" type="text" name="category" value="<?= e($item['category'] ?? 'عمومی') ?>" maxlength="60"></div>
        <div class="field field--full"><label for="t-text">متن نکته *</label><textarea class="input" id="t-text" name="text" rows="3" required maxlength="400"><?= e($item['text'] ?? '') ?></textarea></div>
        <div class="field field--full"><label for="t-try">تمرین فوری</label><input class="input" id="t-try" type="text" name="try" value="<?= e($item['try'] ?? '') ?>" maxlength="200"></div>
        <?= admin_status_field($item ?? []) ?>
    </div>
</div>
<div class="admin-form-actions"><button class="btn btn--primary" type="submit"><?= ha_icon('check', 15) ?> ذخیره</button><a class="btn btn--ghost" href="<?= e(url('admin_tips')) ?>">بازگشت</a></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
