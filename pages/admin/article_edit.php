<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$slug = param('slug');
$article = null;
if ($slug !== '') {
    foreach (admin_load('articles') as $a) { if (($a['slug'] ?? '') === $slug) { $article = $a; break; } }
    if (!$article) { foreach (data('articles') as $a) { if (($a['slug'] ?? '') === $slug) { $article = $a; break; } } }
}
$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>" role="<?= $flash['type']==='success'?'status':'alert' ?>"><p><?= e($flash['message']) ?></p></div><?php endif; ?>
<form class="admin-form" method="post" action="<?= e(url('admin_article_save')) ?>">
<?= csrf_field() ?>
<input type="hidden" name="original_slug" value="<?= e($slug) ?>">
<div class="field"><label>عنوان *</label><input class="input" type="text" name="title" value="<?= e($article['title'] ?? '') ?>" required maxlength="200"></div>
<div class="field"><label>نامک (slug) *</label><input class="input" type="text" name="slug" value="<?= e($article['slug'] ?? '') ?>" required maxlength="100" dir="ltr"></div>
<div class="field"><label>دسته *</label><input class="input" type="text" name="category" value="<?= e($article['category'] ?? 'عمومی') ?>" required></div>
<div class="field"><label>چکیده</label><textarea class="input" name="excerpt" rows="3"><?= e($article['excerpt'] ?? '') ?></textarea></div>
<div class="field"><label>تاریخ (میلادی)</label><input class="input" type="date" name="date" value="<?= e($article['date'] ?? date('Y-m-d')) ?>"></div>
<div class="field"><label>تاریخ فارسی</label><input class="input" type="text" name="date_fa" value="<?= e($article['date_fa'] ?? '') ?>"></div>
<div class="field"><label>زمان مطالعه (دقیقه)</label><input class="input" type="number" name="minutes" value="<?= e((string)($article['minutes'] ?? 5)) ?>" min="1" max="120"></div>
<div class="field"><label>برچسب‌ها (با کاما جدا کنید)</label><input class="input" type="text" name="tags" value="<?= e(implode(', ', $article['tags'] ?? [])) ?>"></div>
<div class="field"><label>متن محتوا (بلوک‌ها — JSON)</label><textarea class="input" name="blocks_json" rows="12" dir="ltr"><?= e(json_encode($article['blocks'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></textarea>
<p class="field__help">ساختار JSON آرایه‌ای از بلوک‌ها: {"type":"p","text":"..."} یا {"type":"h2","text":"..."} یا {"type":"ul","items":["..."]}</p></div>
<div class="admin-form-actions">
<button class="btn btn--primary" type="submit">ذخیره</button>
<a class="btn btn--ghost" href="<?= e(url('admin_articles')) ?>">بازگشت</a>
</div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
