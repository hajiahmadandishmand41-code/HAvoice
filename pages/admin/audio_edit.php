<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$slug = param('slug'); $item = null;
if ($slug !== '') { foreach (admin_load('media') as $m) { if (($m['slug'] ?? '') === $slug && ($m['type'] ?? '') === 'audio') { $item = $m; break; } } if (!$item) foreach (media_items() as $m) { if (($m['slug'] ?? '') === $slug && ($m['type'] ?? '') === 'audio') { $item = $m; break; } } }
$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<form class="admin-form" method="post" action="<?= e(url('admin_audio_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_slug" value="<?= e($slug) ?>">
<div class="field"><label>عنوان *</label><input class="input" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required></div>
<div class="field"><label>نامک (slug) *</label><input class="input" type="text" name="slug" value="<?= e($item['slug'] ?? '') ?>" required dir="ltr"></div>
<div class="field"><label>دسته</label><input class="input" type="text" name="category" value="<?= e($item['category'] ?? '') ?>"></div>
<div class="field"><label>چکیده</label><textarea class="input" name="excerpt" rows="3"><?= e($item['excerpt'] ?? '') ?></textarea></div>
<div class="field"><label>URL فایل صوتی</label><input class="input" type="url" name="url" value="<?= e($item['url'] ?? '') ?>" dir="ltr"></div>
<div class="field"><label>مدت (ثانیه)</label><input class="input" type="number" name="seconds" value="<?= e((string)($item['seconds'] ?? 0)) ?>"></div>
<div class="field"><label>تاریخ فارسی</label><input class="input" type="text" name="date_fa" value="<?= e($item['date_fa'] ?? '') ?>"></div>
<input type="hidden" name="type" value="audio">
<div class="admin-form-actions"><button class="btn btn--primary" type="submit">ذخیره</button><a class="btn btn--ghost" href="<?= e(url('admin_audios')) ?>">بازگشت</a></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
