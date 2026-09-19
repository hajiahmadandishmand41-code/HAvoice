<?php
/**
 * HAvoice Admin — ویرایش/ثبتِ کتاب
 * کتاب می‌تواند: خلاصه داشته باشد، فایلِ کامل (PDF) آپلود/لینک شود، و
 * در صورتِ ثبتِ متنِ کامل (بلوک‌ها) داخلِ سایت خوانده شود.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$slug = param('slug');
$item = null;
if ($slug !== '') {
    foreach (books_all() as $b) {
        if (slugify((string) ($b['slug'] ?? '')) === slugify($slug)) { $item = $b; break; }
    }
    if ($item === null) { flash('error', 'کتاب پیدا نشد.'); redirect(url('admin_books')); }
}
$curCourse = slugify((string) ($item['course'] ?? ''));
?>
<?= admin_flash() ?>
<form class="admin-form" method="post" action="<?= e(url('admin_book_save')) ?>" enctype="multipart/form-data">
<?= csrf_field() ?><input type="hidden" name="original_slug" value="<?= e($slug) ?>">

<div class="admin-card">
    <h2>کتاب / PDF</h2>
    <div class="admin-form-grid">
        <div class="field"><label for="b-title">نام *</label><input class="input" id="b-title" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required maxlength="200"></div>
        <div class="field" style="grid-column:1/-1"><label for="b-excerpt">توضیح</label><textarea class="input" id="b-excerpt" name="excerpt" rows="3" maxlength="400"><?= e($item['excerpt'] ?? '') ?></textarea></div>
        <div class="field"><label for="b-file">آپلود PDF</label><input class="input" id="b-file" type="file" name="file_upload" accept=".pdf,application/pdf">
            <p class="field__help"><?= e(ha_upload_kind_hint('document')) ?></p></div>
        <div class="field"><label for="b-fileurl">یا نشانی فایل PDF</label><input class="input" id="b-fileurl" type="text" name="file" value="<?= e($item['file'] ?? '') ?>" dir="ltr" maxlength="300" placeholder="https://… یا uploads/…"></div>
        <?= admin_status_field($item ?? []) ?>
    </div>
</div>

<details class="admin-advanced admin-card">
    <summary>تنظیمات بیشتر (اختیاری)</summary>
    <div class="admin-form-grid">
        <div class="field"><label for="b-slug">نامک</label><input class="input" id="b-slug" type="text" name="slug" value="<?= e($item['slug'] ?? '') ?>" dir="ltr" maxlength="100" placeholder="از روی نام ساخته می‌شود"></div>
        <div class="field"><label for="b-author">نویسنده</label><input class="input" id="b-author" type="text" name="author" value="<?= e($item['author'] ?? '') ?>" maxlength="120"></div>
        <div class="field"><label for="b-translator">مترجم</label><input class="input" id="b-translator" type="text" name="translator" value="<?= e($item['translator'] ?? '') ?>" maxlength="120"></div>
        <?= admin_field_select($item ?? []) ?>
        <div class="field"><label for="b-category">برچسب موضوع</label><input class="input" id="b-category" type="text" name="category" value="<?= e($item['category'] ?? '') ?>" maxlength="80"></div>
        <div class="field"><label for="b-datefa">تاریخ فارسی</label><input class="input" id="b-datefa" type="text" name="date_fa" value="<?= e($item['date_fa'] ?? '') ?>"></div>
        <div class="field"><label for="b-minutes">زمان مطالعه (دقیقه)</label><input class="input" id="b-minutes" type="number" name="minutes" value="<?= e((string)($item['minutes'] ?? 10)) ?>" min="1" max="600"></div>
        <div class="field"><label for="b-course">دوره‌ی مرتبط</label>
            <select class="input" id="b-course" name="course">
                <option value="">— بدون اتصال —</option>
                <?php foreach (courses_all() as $c): $cs = slugify((string) ($c['slug'] ?? '')); ?>
                <option value="<?= e($cs) ?>"<?= $cs === $curCourse ? ' selected' : '' ?>><?= e($c['title'] ?? $cs) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="field"><label for="b-tags">برچسب‌ها (کاما)</label><input class="input" id="b-tags" type="text" name="tags" value="<?= e(implode(', ', $item['tags'] ?? [])) ?>"></div>
        <div class="field"><label for="b-lessons">برداشت‌های کلیدی — هر خط یک مورد</label><textarea class="input" id="b-lessons" name="lessons_text" rows="3"><?= e(implode("\n", array_map('strval', (array) ($item['lessons'] ?? [])))) ?></textarea></div>
        <?= admin_featured_field($item ?? []) ?>
        <div class="field"><label for="b-link">پیوند بیرونی</label><input class="input" id="b-link" type="url" name="link" value="<?= e($item['link'] ?? '') ?>" dir="ltr" maxlength="300"></div>
        <div class="field"><label for="b-imageurl">نشانی جلد</label><input class="input" id="b-imageurl" type="text" name="image" value="<?= e($item['image'] ?? '') ?>" dir="ltr" maxlength="300"></div>
        <div class="field"><label for="b-imagefile">آپلود جلد</label><input class="input" id="b-imagefile" type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp">
            <p class="field__help"><?= e(ha_upload_kind_hint('image')) ?></p></div>
        <?php if (!empty($item['image'])): $__bPrev = ha_public_file_url((string)$item['image']); if ($__bPrev !== ''): ?>
        <div class="field"><span class="field__help">جلد فعلی:</span><img src="<?= e($__bPrev) ?>" alt="" style="max-width:140px;border-radius:8px"></div>
        <?php endif; endif; ?>
        <div class="field" style="grid-column:1/-1"><label for="b-summary">خلاصه</label><textarea class="input" id="b-summary" name="summary" rows="4"><?= e($item['summary'] ?? '') ?></textarea></div>
        <div class="field" style="grid-column:1/-1"><label for="b-blocks">متن کامل (JSON)</label>
            <textarea class="input input--code" id="b-blocks" name="blocks_json" rows="6" dir="ltr" spellcheck="false"><?= e(json_encode($item['blocks'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea></div>
    </div>
</details>

<div class="admin-form-actions">
    <button class="btn btn--primary" type="submit"><?= ha_icon('check', 15) ?> ذخیره‌ی کتاب</button>
    <a class="btn btn--ghost" href="<?= e(url('admin_books')) ?>">بازگشت</a>
</div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
