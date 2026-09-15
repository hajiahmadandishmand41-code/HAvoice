<?php
/**
 * HAvoice Admin — صوت/پادکست (یک فرم واحد و ساده)
 * فقط: عنوان · توضیح · نشانی/فایل · دوره/درس · وضعیت.
 * نامک، تاریخ و ترتیب خودکار ساخته می‌شوند.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$slug = param('slug');
$item = null;
if ($slug !== '') {
    foreach (media_all() as $m) {
        if (slugify((string) ($m['slug'] ?? '')) === slugify($slug) && ($m['type'] ?? '') === 'audio') {
            $item = $m;
            break;
        }
    }
    if ($item === null) {
        flash('error', 'فایل صوتی پیدا نشد.');
        redirect(url('admin_audios'));
    }
}

$curCourse = slugify((string) ($item['course'] ?? ''));
$curLesson = slugify((string) ($item['lesson'] ?? ''));
$allCourses = courses_all();
$lessonIndex = course_lesson_index();
?>
<?= admin_flash() ?>
<form class="admin-form" method="post" action="<?= e(url('admin_audio_save')) ?>" enctype="multipart/form-data">
<?= csrf_field() ?>
<input type="hidden" name="original_slug" value="<?= e($slug) ?>">

<div class="admin-card">
    <h2><?= $slug === '' ? 'صوت/پادکست جدید' : 'ویرایش صوت' ?></h2>
    <div class="admin-form-grid">
        <div class="field">
            <label for="a-title">عنوان *</label>
            <input class="input" id="a-title" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required maxlength="200">
        </div>
        <div class="field" style="grid-column:1/-1">
            <label for="a-excerpt">توضیح</label>
            <textarea class="input" id="a-excerpt" name="excerpt" rows="3" maxlength="500"><?= e($item['excerpt'] ?? '') ?></textarea>
        </div>
        <div class="field" style="grid-column:1/-1">
            <label for="a-url">نشانی فایل صوتی * (mp3 / ogg یا URL مستقیم)</label>
            <input class="input" id="a-url" type="url" name="url" value="<?= e($item['url'] ?? '') ?>" dir="ltr" maxlength="400" required placeholder="https://… یا /uploads/…">
        </div>
        <div class="field">
            <label for="a-course">دوره</label>
            <select class="input" id="a-course" name="course">
                <option value="">— بدون اتصال —</option>
                <?php foreach ($allCourses as $c): $cs = slugify((string) ($c['slug'] ?? '')); ?>
                <option value="<?= e($cs) ?>"<?= $cs === $curCourse ? ' selected' : '' ?>><?= e($c['title'] ?? $cs) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="a-lesson">درس</label>
            <select class="input" id="a-lesson" name="lesson">
                <option value="">— بدون اتصال —</option>
                <?php foreach ($lessonIndex as $ls => $info): ?>
                <option value="<?= e($ls) ?>"<?= $ls === $curLesson ? ' selected' : '' ?>>
                    <?= e(($info['course']['title'] ?? '') . ' · ' . ($info['lesson']['title'] ?? $ls)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?= admin_field_select($item ?? []) ?>
        <?= admin_status_field($item ?? [], $slug === '') ?>
        <?= admin_featured_field($item ?? []) ?>
    </div>
</div>

<div class="admin-form-actions">
    <button class="btn btn--primary" type="submit"><?= ha_icon('check', 15) ?> ذخیره</button>
    <a class="btn btn--ghost" href="<?= e(url('admin_audios')) ?>">بازگشت</a>
</div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
