<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$slug = param('slug');
$item = null;
if ($slug !== '') {
    foreach (media_all() as $m) {
        if (slugify((string) ($m['slug'] ?? '')) === slugify($slug) && ($m['type'] ?? '') === 'audio') { $item = $m; break; }
    }
    if ($item === null) { flash('error', 'فایل صوتی پیدا نشد.'); redirect(url('admin_audios')); }
}
?>
<?= admin_flash() ?>
<form class="admin-form" method="post" action="<?= e(url('admin_audio_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_slug" value="<?= e($slug) ?>">
<div class="admin-card">
    <h2>مشخصاتِ فایل صوتی</h2>
    <div class="admin-form-grid">
        <div class="field"><label for="au-title">عنوان *</label><input class="input" id="au-title" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required maxlength="200"></div>
        <div class="field"><label for="au-slug">نامک (slug) *</label><input class="input" id="au-slug" type="text" name="slug" value="<?= e($item['slug'] ?? '') ?>" required dir="ltr" maxlength="100"></div>
        <?= admin_field_select($item ?? []) ?>
        <div class="field"><label for="au-category">برچسبِ موضوع (نمایشی)</label><input class="input" id="au-category" type="text" name="category" value="<?= e($item['category'] ?? '') ?>" maxlength="80"></div>
        <div class="field"><label for="au-url">نشانیِ فایل صوتی (mp3)</label><input class="input" id="au-url" type="url" name="url" value="<?= e($item['url'] ?? '') ?>" dir="ltr" maxlength="300"></div>
        <div class="field"><label for="au-seconds">مدت (ثانیه)</label><input class="input" id="au-seconds" type="number" name="seconds" value="<?= e((string)($item['seconds'] ?? 0)) ?>" min="0" max="86400"></div>
        <div class="field"><label for="au-datefa">تاریخ فارسی</label><input class="input" id="au-datefa" type="text" name="date_fa" value="<?= e($item['date_fa'] ?? '') ?>"></div>
        <div class="field"><label for="au-excerpt">چکیده</label><textarea class="input" id="au-excerpt" name="excerpt" rows="3" maxlength="400"><?= e($item['excerpt'] ?? '') ?></textarea></div>
        <?= admin_status_field($item ?? []) ?>
        <?= admin_featured_field($item ?? []) ?>
    </div>
</div>
<input type="hidden" name="type" value="audio">
<div class="admin-form-actions"><button class="btn btn--primary" type="submit"><?= ha_icon('check', 15) ?> ذخیره</button><a class="btn btn--ghost" href="<?= e(url('admin_audios')) ?>">بازگشت</a></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
