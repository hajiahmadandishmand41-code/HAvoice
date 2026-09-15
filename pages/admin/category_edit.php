<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$slug = param('slug'); $item = null;
if ($slug !== '') { foreach (admin_load('categories') as $c) { if (($c['slug'] ?? '') === $slug) { $item = $c; break; } } if (!$item) foreach (ha_content_admin('categories') as $c) { if (($c['slug'] ?? '') === $slug) { $item = $c; break; } } }
?>
<?= admin_flash() ?>
<form class="admin-form" method="post" action="<?= e(url('admin_category_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_slug" value="<?= e($slug) ?>">
<div class="admin-card">
    <div class="admin-form-grid">
        <div class="field"><label for="c-title">عنوان *</label><input class="input" id="c-title" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required maxlength="80"></div>
        <div class="field"><label for="c-slug">نامک (slug) *</label><input class="input" id="c-slug" type="text" name="slug" value="<?= e($item['slug'] ?? '') ?>" required dir="ltr" maxlength="60"></div>
        <div class="field"><label for="c-short">عنوان کوتاه</label><input class="input" id="c-short" type="text" name="short" value="<?= e($item['short'] ?? '') ?>" maxlength="30"></div>
        <div class="field"><label for="c-icon">آیکون</label><input class="input" id="c-icon" type="text" name="icon" value="<?= e($item['icon'] ?? 'compass') ?>" dir="ltr" maxlength="30"></div>
        <div class="field"><label for="c-color">رنگ اصلی</label><input class="input" id="c-color" type="color" name="color" value="<?= e($item['color'] ?? '#1A3A7C') ?>"></div>
        <div class="field"><label for="c-accent">رنگ فرعی</label><input class="input" id="c-accent" type="color" name="accent" value="<?= e($item['accent'] ?? '#4F46E5') ?>"></div>
        <div class="field field--full"><label for="c-desc">توضیحات</label><textarea class="input" id="c-desc" name="description" rows="3" maxlength="300"><?= e($item['description'] ?? '') ?></textarea></div>
        <?= admin_status_field($item ?? []) ?>
    </div>
</div>
<div class="admin-form-actions"><button class="btn btn--primary" type="submit"><?= ha_icon('check', 15) ?> ذخیره</button><a class="btn btn--ghost" href="<?= e(url('admin_categories')) ?>">بازگشت</a></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
