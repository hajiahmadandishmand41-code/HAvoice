<?php
/**
 * HAvoice Admin — ذخیره‌ی پژوهش
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
require HA_ROOT . '/pages/admin/_helpers.php';

$listRoute = 'admin_research';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست تمام شده.'); redirect(url($listRoute)); }

$title = trim((string) ($_POST['title'] ?? ''));
$orig  = slugify((string) ($_POST['original_slug'] ?? ''));
$slug  = admin_post_slug_keep($title, $orig);
$editUrl = url('admin_research_edit', $orig !== '' ? ['slug' => $orig] : ($slug !== '' ? ['slug' => $slug] : []));

if ($title === '' || $slug === '') {
    flash('error', 'عنوان و نامک الزامی.');
    redirect($editUrl);
}

$blocks = admin_post_blocks('blocks_json');
if ($blocks === null) {
    flash('error', 'JSON بلوک‌های پژوهش معتبر نیست: ' . json_last_error_msg());
    redirect($editUrl);
}

$field = slugify((string) ($_POST['field'] ?? ''));
if ($field !== '' && find_category_any($field) === null) {
    flash('error', 'حوزه‌ی انتخاب‌شده معتبر نیست.');
    redirect($editUrl);
}
$category = trim((string) ($_POST['category'] ?? ''));
if ($category === '' && $field !== '') {
    $category = category_label($field, $field);
}


/* یکتاییِ خودکارِ نامکِ تازه (برخورد ⇒ پسوندِ -2/-3 — نه بازنویسیِ بی‌خبر) */
if ($orig === '') {
    $allSlugs = [];
    foreach (research_all() as $m) { $allSlugs[] = (string) ($m['slug'] ?? ''); }
    $slug = admin_unique_slug($slug, $allSlugs, $orig);
}

$item = [
    'slug'     => $slug,
    'title'    => $title,
    'field'    => $field,
    'category' => $category !== '' ? $category : $field,
    'summary'  => trim((string) ($_POST['summary'] ?? '')),
    'blocks'   => $blocks,
    'refs'     => admin_lines('refs_text'),
    'date_fa'  => trim((string) ($_POST['date_fa'] ?? '')),
    'tags'     => array_map('trim', array_filter(explode(',', (string) ($_POST['tags'] ?? '')))),
    'link'     => ha_safe_file_url((string) ($_POST['link'] ?? '')),
    'status'   => admin_post_status(),
    'featured' => !empty($_POST['featured']),
];

if (!repo_save_research($item, $orig)) {
    flash('error', 'ذخیره ناموفق بود.');
    redirect($editUrl);
}
flash('success', $item['status'] === 'published' ? 'پژوهش ذخیره و منتشر شد.' : 'پژوهش به‌عنوان پیش‌نویس ذخیره شد (مخفی).');
redirect(url('admin_research'));
