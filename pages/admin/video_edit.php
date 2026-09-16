<?php
/**
 * HAvoice Admin — ویدیو (یک فرم واحد)
 * Title · Description · Thumbnail · URL · Course/Lesson · Status · Featured
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$slug = param('slug');
$item = null;
if ($slug !== '') {
    foreach (media_all() as $m) {
        if (slugify((string) ($m['slug'] ?? '')) === slugify($slug) && ($m['type'] ?? '') === 'video') {
            $item = $m;
            break;
        }
    }
    if ($item === null) {
        flash('error', 'ویدیو پیدا نشد.');
        redirect(url('admin_videos'));
    }
}

$curCourse = slugify((string) ($item['course'] ?? ''));
$curLesson = slugify((string) ($item['lesson'] ?? ''));
$allCourses = courses_all();
$lessonIndex = course_lesson_index();
?>
<?= admin_flash() ?>
<form class="admin-form" method="post" action="<?= e(url('admin_video_save')) ?>" enctype="multipart/form-data">
<?= csrf_field() ?>
<input type="hidden" name="original_slug" value="<?= e($slug) ?>">
<input type="hidden" name="type" value="video">

<div class="admin-card">
    <h2><?= $slug === '' ? 'ویدیوی جدید' : 'ویرایش ویدیو' ?></h2>
    <div class="admin-form-grid">
        <div class="field">
            <label for="v-title">نام *</label>
            <input class="input" id="v-title" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required maxlength="200">
        </div>
        <div class="field" style="grid-column:1/-1">
            <label for="v-excerpt">توضیح</label>
            <textarea class="input" id="v-excerpt" name="excerpt" rows="3" maxlength="500"><?= e($item['excerpt'] ?? '') ?></textarea>
        </div>
        <div class="field" style="grid-column:1/-1">
            <label for="v-file">۱) آپلود فایلِ ویدیو (MP4 / WebM / OGG)</label>
            <input class="input" id="v-file" type="file" name="video_file" accept="video/mp4,video/webm,video/ogg">
            <p class="field__help"><?= e(ha_upload_kind_hint('video')) ?></p>
        </div>
        <div class="field" style="grid-column:1/-1">
            <label for="v-url">۲) یا نشانیِ ویدیو (آپارات / یوتیوب / Vimeo / فایلِ mp4)</label>
            <input class="input" id="v-url" type="text" name="url" value="<?= e($item['url'] ?? '') ?>" dir="ltr" maxlength="400" placeholder="https://…">
            <p class="field__help">یکی از این دو لازم است. اگر فایل آپلود کنید، همین نشانی نادیده گرفته می‌شود. ویدیو در خودِ سایت (بدونِ خروج از صفحه) پخش می‌شود.</p>
        </div>
        <div class="field">
            <label for="v-thumb-url">بندانگشتی (نشانی تصویر)</label>
            <input class="input" id="v-thumb-url" type="text" name="thumbnail" value="<?= e($item['thumbnail'] ?? '') ?>" dir="ltr" maxlength="300" placeholder="https://… یا uploads/…">
        </div>
        <div class="field">
            <label for="v-thumb-file">یا آپلود بندانگشتی</label>
            <input class="input" id="v-thumb-file" type="file" name="thumbnail_file" accept="image/jpeg,image/png,image/webp">
            <p class="field__help"><?= e(ha_upload_kind_hint('image')) ?></p>
        </div>
        <div class="field">
            <label for="v-course">دوره مرتبط</label>
            <select class="input" id="v-course" name="course">
                <option value="">— بدون اتصال —</option>
                <?php foreach ($allCourses as $c): $cs = slugify((string) ($c['slug'] ?? '')); ?>
                <option value="<?= e($cs) ?>"<?= $cs === $curCourse ? ' selected' : '' ?>><?= e($c['title'] ?? $cs) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="v-lesson">درس مرتبط</label>
            <select class="input" id="v-lesson" name="lesson">
                <option value="">— بدون اتصال —</option>
                <?php foreach ($lessonIndex as $ls => $info): ?>
                <option value="<?= e($ls) ?>"<?= $ls === $curLesson ? ' selected' : '' ?> data-course="<?= e(slugify((string) ($info['course']['slug'] ?? ''))) ?>">
                    <?= e(($info['course']['title'] ?? '') . ' · ' . ($info['lesson']['title'] ?? $ls)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?= admin_field_select($item ?? []) ?>
        <?= admin_status_field($item ?? [], $slug === '') ?>
        <?= admin_featured_field($item ?? []) ?>
    </div>

    <details class="admin-advanced">
        <summary>تنظیمات بیشتر (اختیاری)</summary>
        <div class="admin-form-grid">
            <div class="field">
                <label for="v-slug">نامک</label>
                <input class="input" id="v-slug" type="text" name="slug" value="<?= e($item['slug'] ?? '') ?>" dir="ltr" maxlength="100" placeholder="از روی نام ساخته می‌شود">
            </div>
            <div class="field">
                <label for="v-seconds">مدت (ثانیه)</label>
                <input class="input" id="v-seconds" type="number" name="seconds" value="<?= e((string) ($item['seconds'] ?? 0)) ?>" min="0" max="86400">
            </div>
            <div class="field">
                <label for="v-datefa">تاریخ فارسی</label>
                <input class="input" id="v-datefa" type="text" name="date_fa" value="<?= e($item['date_fa'] ?? '') ?>" placeholder="۱۴۰۴/۰۲/۱۵">
            </div>
            <div class="field">
                <label for="v-category">برچسب نمایشی</label>
                <input class="input" id="v-category" type="text" name="category" value="<?= e($item['category'] ?? '') ?>" maxlength="80">
            </div>
        </div>
    </details>
</div>

<div class="admin-form-actions">
    <button class="btn btn--primary" type="submit"><?= ha_icon('check', 15) ?> ذخیره</button>
    <a class="btn btn--ghost" href="<?= e(url('admin_videos')) ?>">بازگشت</a>
</div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
