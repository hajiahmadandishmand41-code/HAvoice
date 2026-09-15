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
    <h2>مشخصاتِ کتاب</h2>
    <div class="admin-form-grid">
        <div class="field"><label for="b-title">عنوان *</label><input class="input" id="b-title" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required maxlength="200"></div>
        <div class="field"><label for="b-slug">نامک (slug) *</label><input class="input" id="b-slug" type="text" name="slug" value="<?= e($item['slug'] ?? '') ?>" required dir="ltr" maxlength="100"></div>
        <div class="field"><label for="b-author">نویسنده</label><input class="input" id="b-author" type="text" name="author" value="<?= e($item['author'] ?? '') ?>" maxlength="120"></div>
        <div class="field"><label for="b-translator">مترجم (اختیاری)</label><input class="input" id="b-translator" type="text" name="translator" value="<?= e($item['translator'] ?? '') ?>" maxlength="120"></div>
        <?= admin_field_select($item ?? []) ?>
        <div class="field"><label for="b-category">برچسبِ موضوع (نمایشی)</label><input class="input" id="b-category" type="text" name="category" value="<?= e($item['category'] ?? '') ?>" maxlength="80"></div>
        <div class="field"><label for="b-datefa">تاریخ فارسی</label><input class="input" id="b-datefa" type="text" name="date_fa" value="<?= e($item['date_fa'] ?? '') ?>" placeholder="۱۴۰۴/۰۱/۰۱"></div>
        <div class="field"><label for="b-minutes">زمان مطالعه‌ی خلاصه (دقیقه)</label><input class="input" id="b-minutes" type="number" name="minutes" value="<?= e((string)($item['minutes'] ?? 10)) ?>" min="1" max="600"></div>
        <div class="field"><label for="b-course">دوره‌ی مرتبط (اختیاری)</label>
            <select class="input" id="b-course" name="course">
                <option value="">— بدونِ اتصال به دوره —</option>
                <?php foreach (courses_all() as $c): $cs = slugify((string) ($c['slug'] ?? '')); ?>
                <option value="<?= e($cs) ?>"<?= $cs === $curCourse ? ' selected' : '' ?>><?= e($c['title'] ?? $cs) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="field"><label for="b-tags">برچسب‌ها (کاما)</label><input class="input" id="b-tags" type="text" name="tags" value="<?= e(implode(', ', $item['tags'] ?? [])) ?>"></div>
        <div class="field"><label for="b-lessons">برداشت‌های کلیدی — هر خط یک مورد</label><textarea class="input" id="b-lessons" name="lessons_text" rows="3"><?= e(implode("\n", array_map('strval', (array) ($item['lessons'] ?? [])))) ?></textarea></div>
        <?= admin_status_field($item ?? []) ?>
        <?= admin_featured_field($item ?? []) ?>
    </div>
</div>

<div class="admin-card">
    <h2>فایل، جلد و پیوند</h2>
    <div class="admin-form-grid">
        <div class="field"><label for="b-fileurl">نشانیِ فایلِ کتاب (PDF)</label><input class="input" id="b-fileurl" type="text" name="file" value="<?= e($item['file'] ?? '') ?>" dir="ltr" maxlength="300" placeholder="https://… یا uploads/…"></div>
        <div class="field"><label for="b-file">یا آپلودِ فایلِ کتاب (PDF)</label><input class="input" id="b-file" type="file" name="file_upload" accept=".pdf"></div>
        <div class="field"><label for="b-link">پیوندِ بیرونی (فروشگاه/ناشر — اختیاری)</label><input class="input" id="b-link" type="url" name="link" value="<?= e($item['link'] ?? '') ?>" dir="ltr" maxlength="300"></div>
        <div class="field"><label for="b-imageurl">نشانیِ تصویرِ جلد</label><input class="input" id="b-imageurl" type="text" name="image" value="<?= e($item['image'] ?? '') ?>" dir="ltr" maxlength="300" placeholder="https://… یا uploads/…"></div>
        <div class="field"><label for="b-imagefile">یا آپلودِ جلد (jpg، png، webp)</label><input class="input" id="b-imagefile" type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp"></div>
        <?php if (!empty($item['image'])): ?>
        <div class="field"><span class="field__help">جلدِ فعلی:</span><img src="<?= e($item['image']) ?>" alt="" style="max-width:140px;border-radius:8px"></div>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card">
    <h2>خلاصه و متنِ کامل</h2>
    <div class="field"><label for="b-excerpt">چکیده (روی کارت)</label><textarea class="input" id="b-excerpt" name="excerpt" rows="3" maxlength="400"><?= e($item['excerpt'] ?? '') ?></textarea></div>
    <div class="field"><label for="b-summary">خلاصه و برداشت</label><textarea class="input" id="b-summary" name="summary" rows="6"><?= e($item['summary'] ?? '') ?></textarea></div>
    <div class="field"><label for="b-blocks">متنِ کاملِ کتاب برای مطالعه‌ی درون‌سایت (بلوک‌ها — JSON)</label>
        <textarea class="input input--code" id="b-blocks" name="blocks_json" rows="10" dir="ltr" spellcheck="false" placeholder='[{"type":"h2","text":"فصل ۱"},{"type":"p","text":"…"}]'><?= e(json_encode($item['blocks'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea>
        <p class="field__help">اگر پر شود، صفحه‌ی کتاب دکمه‌ی «مطالعه در سایت» می‌گیرد و متن همان‌جا خوانده می‌شود. اگر خالی بماند، کتاب فقط با خلاصه و فایل/پیوند نمایش داده می‌شود.</p></div>
</div>

<div class="admin-form-actions">
    <button class="btn btn--primary" type="submit"><?= ha_icon('check', 15) ?> ذخیره‌ی کتاب</button>
    <a class="btn btn--ghost" href="<?= e(url('admin_books')) ?>">بازگشت</a>
</div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
