<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$slug = param('slug'); $item = null;
if ($slug !== '') { foreach (admin_load('research') as $r) { if (($r['slug'] ?? '') === $slug) { $item = $r; break; } } if (!$item) foreach (research_items() as $r) { if (($r['slug'] ?? '') === $slug) { $item = $r; break; } } }
$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<form class="admin-form" method="post" action="<?= e(url('admin_research_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_slug" value="<?= e($slug) ?>">
<div class="field"><label>عنوان *</label><input class="input" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required></div>
<div class="field"><label>نامک (slug) *</label><input class="input" type="text" name="slug" value="<?= e($item['slug'] ?? '') ?>" required dir="ltr"></div>
<div class="field"><label>دسته</label><input class="input" type="text" name="category" value="<?= e($item['category'] ?? '') ?>"></div>
<div class="field"><label>خلاصه</label><textarea class="input" name="summary" rows="4"><?= e($item['summary'] ?? '') ?></textarea></div>
<div class="field"><label>تاریخ فارسی</label><input class="input" type="text" name="date_fa" value="<?= e($item['date_fa'] ?? '') ?>"></div>
<div class="field"><label>برچسب‌ها (کاما)</label><input class="input" type="text" name="tags" value="<?= e(implode(', ', $item['tags'] ?? [])) ?>"></div>
<div class="admin-form-actions"><button class="btn btn--primary" type="submit">ذخیره</button><a class="btn btn--ghost" href="<?= e(url('admin_research')) ?>">بازگشت</a></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
