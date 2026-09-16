<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$slug = param('slug');
$item = null;
if ($slug !== '') {
    foreach (research_all() as $r) {
        if (slugify((string) ($r['slug'] ?? '')) === slugify($slug)) { $item = $r; break; }
    }
    if ($item === null) { flash('error', 'پژوهش پیدا نشد.'); redirect(url('admin_research')); }
}
?>
<?= admin_flash() ?>
<form class="admin-form" method="post" action="<?= e(url('admin_research_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_slug" value="<?= e($slug) ?>">

<div class="admin-card">
    <h2>مشخصاتِ پژوهش</h2>
    <div class="admin-form-grid">
        <div class="field"><label for="r-title">عنوان *</label><input class="input" id="r-title" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required maxlength="200"></div>
        <div class="field"><label for="r-slug">نامک (خودکار اگر خالی)</label><input class="input" id="r-slug" type="text" name="slug" value="<?= e($item['slug'] ?? '') ?>" dir="ltr" maxlength="100" placeholder="از روی عنوان ساخته می‌شود">
            <p class="field__help">حروفِ لاتین، عدد و خطِ تیره؛ اگر خالی بگذارید به‌صورتِ خودکار از عنوان ساخته می‌شود (عنوانِ فارسی هم نویسه‌گردانی می‌شود).</p></div>
        <?= admin_field_select($item ?? []) ?>
        <div class="field"><label for="r-category">برچسبِ موضوع (نمایشی)</label><input class="input" id="r-category" type="text" name="category" value="<?= e($item['category'] ?? '') ?>" maxlength="80"></div>
        <div class="field"><label for="r-datefa">تاریخ فارسی</label><input class="input" id="r-datefa" type="text" name="date_fa" value="<?= e($item['date_fa'] ?? '') ?>" placeholder="۱۴۰۴/۰۲/۱۵"></div>
        <div class="field"><label for="r-tags">برچسب‌ها (کاما)</label><input class="input" id="r-tags" type="text" name="tags" value="<?= e(implode(', ', $item['tags'] ?? [])) ?>"></div>
        <div class="field"><label for="r-link">پیوندِ منبعِ بیرونی (اختیاری)</label><input class="input" id="r-link" type="url" name="link" value="<?= e($item['link'] ?? '') ?>" dir="ltr" maxlength="300"></div>
        <?= admin_status_field($item ?? []) ?>
        <?= admin_featured_field($item ?? []) ?>
    </div>
</div>

<div class="admin-card">
    <h2>متن و منابع</h2>
    <div class="field"><label for="r-summary">خلاصه</label><textarea class="input" id="r-summary" name="summary" rows="4" maxlength="500"><?= e($item['summary'] ?? '') ?></textarea></div>
    <div class="field"><label for="r-blocks">متنِ کامل (بلوک‌ها — JSON)</label>
        <textarea class="input input--code" id="r-blocks" name="blocks_json" rows="12" dir="ltr" spellcheck="false"><?= e(json_encode($item['blocks'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea>
        <p class="field__help">همان قالبِ بلوک‌های مقاله: <code dir="ltr">{"type":"p","text":"…"}</code>، <code dir="ltr">{"type":"h2","text":"…"}</code>، <code dir="ltr">{"type":"ul","items":["…"]}</code></p></div>
    <div class="field"><label for="r-refs">فهرستِ مراجع — هر خط یک مرجع</label>
        <textarea class="input" id="r-refs" name="refs_text" rows="4"><?= e(implode("\n", array_map('strval', (array) ($item['refs'] ?? [])))) ?></textarea></div>
</div>

<div class="admin-form-actions">
    <button class="btn btn--primary" type="submit"><?= ha_icon('check', 15) ?> ذخیره‌ی پژوهش</button>
    <a class="btn btn--ghost" href="<?= e(url('admin_research')) ?>">بازگشت</a>
</div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
