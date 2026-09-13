<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$slug = param('slug'); $item = null;
if ($slug !== '') { foreach (admin_load('categories') as $c) { if (($c['slug'] ?? '') === $slug) { $item = $c; break; } } if (!$item) foreach (categories() as $c) { if (($c['slug'] ?? '') === $slug) { $item = $c; break; } } }
$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<form class="admin-form" method="post" action="<?= e(url('admin_category_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_slug" value="<?= e($slug) ?>">
<div class="field"><label>عنوان *</label><input class="input" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required></div>
<div class="field"><label>نامک (slug) *</label><input class="input" type="text" name="slug" value="<?= e($item['slug'] ?? '') ?>" required dir="ltr"></div>
<div class="field"><label>عنوان کوتاه</label><input class="input" type="text" name="short" value="<?= e($item['short'] ?? '') ?>"></div>
<div class="field"><label>توضیحات</label><textarea class="input" name="description" rows="3"><?= e($item['description'] ?? '') ?></textarea></div>
<div class="field"><label>آیکون</label><input class="input" type="text" name="icon" value="<?= e($item['icon'] ?? 'compass') ?>" dir="ltr"></div>
<div class="field"><label>رنگ اصلی</label><input class="input" type="color" name="color" value="<?= e($item['color'] ?? '#1A3A7C') ?>"></div>
<div class="field"><label>رنگ فرعی</label><input class="input" type="color" name="accent" value="<?= e($item['accent'] ?? '#4F46E5') ?>"></div>
<div class="admin-form-actions"><button class="btn btn--primary" type="submit">ذخیره</button><a class="btn btn--ghost" href="<?= e(url('admin_categories')) ?>">بازگشت</a></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
