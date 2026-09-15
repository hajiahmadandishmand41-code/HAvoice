<?php
/**
 * HAvoice Admin — ویرایش/ساختِ مقاله‌ی علمی
 *
 * هر مقاله فقط با تأیید و ذخیره‌ی مدیر منتشر می‌شود؛ هیچ محتوای خارجی
 * به‌صورتِ خودکار وارد سایت نمی‌شود. فیلدهای علمی: نویسنده، منبع/رفرنس
 * (نام + نشانی + فهرستِ مراجع)، تاریخ، حوزه، تصویر و فایل.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$slug = param('slug');
$article = null;
if ($slug !== '') {
    foreach (articles_all() as $a) {
        if (slugify((string) ($a['slug'] ?? '')) === slugify($slug)) { $article = $a; break; }
    }
    if ($article === null) { flash('error', 'مقاله پیدا نشد.'); redirect(url('admin_articles')); }
}
?>
<?= admin_flash() ?>
<form class="admin-form" method="post" action="<?= e(url('admin_article_save')) ?>" enctype="multipart/form-data">
<?= csrf_field() ?>
<input type="hidden" name="original_slug" value="<?= e($slug) ?>">

<div class="admin-card">
    <h2>مشخصاتِ مقاله</h2>
    <div class="admin-form-grid">
        <div class="field"><label for="a-title">عنوان *</label><input class="input" id="a-title" type="text" name="title" value="<?= e($article['title'] ?? '') ?>" required maxlength="200"></div>
        <div class="field"><label for="a-slug">نامک (slug) *</label><input class="input" id="a-slug" type="text" name="slug" value="<?= e($article['slug'] ?? '') ?>" required maxlength="100" dir="ltr">
            <p class="field__help">حروفِ لاتین، عدد و خطِ تیره؛ نشانیِ صفحه‌ی مقاله.</p></div>
        <?= admin_field_select($article ?? []) ?>
        <div class="field"><label for="a-category">برچسبِ موضوع (نمایشی)</label><input class="input" id="a-category" type="text" name="category" value="<?= e($article['category'] ?? '') ?>" maxlength="80" placeholder="مثلاً: صدا و نفس">
            <p class="field__help">متنِ آزادِ روی کارت؛ اگر خالی بماند از عنوانِ حوزه پر می‌شود.</p></div>
        <div class="field"><label for="a-author">نویسنده</label><input class="input" id="a-author" type="text" name="author" value="<?= e($article['author'] ?? '') ?>" maxlength="120" placeholder="<?= e(ha_site_name()) ?>"></div>
        <div class="field"><label for="a-date">تاریخ (میلادی)</label><input class="input" id="a-date" type="date" name="date" value="<?= e($article['date'] ?? date('Y-m-d')) ?>"></div>
        <div class="field"><label for="a-datefa">تاریخ فارسی</label><input class="input" id="a-datefa" type="text" name="date_fa" value="<?= e($article['date_fa'] ?? '') ?>" placeholder="۱۴۰۴/۰۲/۱۵"></div>
        <div class="field"><label for="a-minutes">زمان مطالعه (دقیقه)</label><input class="input" id="a-minutes" type="number" name="minutes" value="<?= e((string)($article['minutes'] ?? 5)) ?>" min="1" max="240"></div>
        <div class="field"><label for="a-tags">برچسب‌ها (با کاما جدا کنید)</label><input class="input" id="a-tags" type="text" name="tags" value="<?= e(implode(', ', $article['tags'] ?? [])) ?>"></div>
        <?= admin_status_field($article ?? []) ?>
        <?= admin_featured_field($article ?? []) ?>
    </div>
</div>

<div class="admin-card">
    <h2>منبع و رفرنسِ علمی</h2>
    <div class="admin-form-grid">
        <div class="field"><label for="a-source">نامِ منبع/نشریه</label><input class="input" id="a-source" type="text" name="source_name" value="<?= e($article['source_name'] ?? '') ?>" maxlength="200" placeholder="مثلاً: مجله‌ی روانشناسیِ کاربردی، شماره‌ی ۱۲"></div>
        <div class="field"><label for="a-sourceurl">نشانیِ منبع (اختیاری)</label><input class="input" id="a-sourceurl" type="url" name="source_url" value="<?= e($article['source_url'] ?? '') ?>" dir="ltr" maxlength="300" placeholder="https://…"></div>
        <div class="field field--full"><label for="a-refs">فهرستِ مراجع — هر خط یک مرجع</label>
            <textarea class="input" id="a-refs" name="refs_text" rows="4" placeholder="Clark, H. H. (2002). Using uh and um in spontaneous speaking. Cognition."><?= e(implode("\n", array_map('strval', (array) ($article['refs'] ?? [])))) ?></textarea></div>
    </div>
</div>

<div class="admin-card">
    <h2>تصویر و فایل</h2>
    <div class="admin-form-grid">
        <div class="field"><label for="a-imageurl">نشانیِ تصویرِ جلد</label><input class="input" id="a-imageurl" type="text" name="image" value="<?= e($article['image'] ?? '') ?>" dir="ltr" maxlength="300" placeholder="https://… یا uploads/…">
            <p class="field__help">اگر فایل آپلود کنید، همین فیلد خودکار پر می‌شود.</p></div>
        <div class="field"><label for="a-imagefile">یا آپلودِ تصویر (jpg، png، webp)</label><input class="input" id="a-imagefile" type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp"></div>
        <?php if (!empty($article['image'])): ?>
        <div class="field"><span class="field__help">پیش‌نمایشِ فعلی:</span><img src="<?= e($article['image']) ?>" alt="" style="max-width:220px;border-radius:8px"></div>
        <?php endif; ?>
        <div class="field"><label for="a-fileurl">نشانیِ فایلِ پیوست (PDF و…)</label><input class="input" id="a-fileurl" type="text" name="file" value="<?= e($article['file'] ?? '') ?>" dir="ltr" maxlength="300" placeholder="https://… یا uploads/…"></div>
        <div class="field"><label for="a-file">یا آپلودِ فایل (PDF)</label><input class="input" id="a-file" type="file" name="file_upload" accept=".pdf"></div>
    </div>
</div>

<div class="admin-card">
    <h2>متنِ کاملِ مقاله</h2>
    <div class="field"><label for="a-excerpt">چکیده</label><textarea class="input" id="a-excerpt" name="excerpt" rows="3" maxlength="400"><?= e($article['excerpt'] ?? '') ?></textarea></div>
    <div class="field"><label for="a-blocks">متنِ محتوا (بلوک‌ها — JSON)</label>
        <textarea class="input input--code" id="a-blocks" name="blocks_json" rows="14" dir="ltr" spellcheck="false"><?= e(json_encode($article['blocks'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea>
        <p class="field__help">ساختار JSON آرایه‌ای از بلوک‌ها: <code dir="ltr">{"type":"p","text":"…"}</code>،
        <code dir="ltr">{"type":"h2","text":"…"}</code>، <code dir="ltr">{"type":"ul","items":["…"]}</code>،
        <code dir="ltr">{"type":"quote","text":"…","by":"…"}</code>، <code dir="ltr">{"type":"tip","tone":"warn","title":"…","text":"…"}</code></p></div>
</div>

<div class="admin-form-actions">
    <button class="btn btn--primary" type="submit"><?= ha_icon('check', 15) ?> ذخیره و انتشار</button>
    <a class="btn btn--ghost" href="<?= e(url('admin_articles')) ?>">بازگشت</a>
</div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
