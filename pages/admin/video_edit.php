<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$slug = param('slug');
$item = null;
if ($slug !== '') {
    foreach (media_all() as $m) {
        if (slugify((string) ($m['slug'] ?? '')) === slugify($slug) && ($m['type'] ?? '') === 'video') { $item = $m; break; }
    }
    if ($item === null) { flash('error', 'ویدیو پیدا نشد.'); redirect(url('admin_videos')); }
}
?>
<?= admin_flash() ?>
<form class="admin-form" method="post" action="<?= e(url('admin_video_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_slug" value="<?= e($slug) ?>">
<div class="admin-card">
    <h2>مشخصاتِ ویدیو</h2>
    <div class="admin-form-grid">
        <div class="field"><label for="v-title">عنوان *</label><input class="input" id="v-title" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required maxlength="200"></div>
        <div class="field"><label for="v-slug">نامک (slug) *</label><input class="input" id="v-slug" type="text" name="slug" value="<?= e($item['slug'] ?? '') ?>" required dir="ltr" maxlength="100"></div>
        <?= admin_field_select($item ?? []) ?>
        <div class="field"><label for="v-category">برچسبِ موضوع (نمایشی)</label><input class="input" id="v-category" type="text" name="category" value="<?= e($item['category'] ?? '') ?>" maxlength="80"></div>
        <div class="field"><label for="v-url">نشانیِ ویدیو (آپارات/یوتیوب/فایل mp4)</label><input class="input" id="v-url" type="url" name="url" value="<?= e($item['url'] ?? '') ?>" dir="ltr" maxlength="300">
            <p class="field__help">نشانیِ امبد از میزبان‌های مجاز (آپارات، یوتیوب، Vimeo) یا فایلِ مستقیم.</p></div>
        <div class="field"><label for="v-seconds">مدت (ثانیه)</label><input class="input" id="v-seconds" type="number" name="seconds" value="<?= e((string)($item['seconds'] ?? 0)) ?>" min="0" max="86400"></div>
        <div class="field"><label for="v-datefa">تاریخ فارسی</label><input class="input" id="v-datefa" type="text" name="date_fa" value="<?= e($item['date_fa'] ?? '') ?>"></div>
        <div class="field"><label for="v-excerpt">چکیده</label><textarea class="input" id="v-excerpt" name="excerpt" rows="3" maxlength="400"><?= e($item['excerpt'] ?? '') ?></textarea></div>
        <?= admin_status_field($item ?? []) ?>
        <?= admin_featured_field($item ?? []) ?>
    </div>
</div>
<input type="hidden" name="type" value="video">
<div class="admin-form-actions"><button class="btn btn--primary" type="submit"><?= ha_icon('check', 15) ?> ذخیره</button><a class="btn btn--ghost" href="<?= e(url('admin_videos')) ?>">بازگشت</a></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
