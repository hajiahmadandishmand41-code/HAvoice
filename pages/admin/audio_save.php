<?php
/**
 * HAvoice Admin — ذخیره‌ی فایل صوتی
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
require HA_ROOT . '/pages/admin/_helpers.php';

$listRoute = 'admin_audios';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست تمام شده.'); redirect(url($listRoute)); }

$title = trim((string) ($_POST['title'] ?? ''));
$slug  = admin_post_slug($title);
if ($title === '' || $slug === '') {
    flash('error', 'عنوان و نامک الزامی.');
    redirect(url('admin_audio_edit'));
}

$field = slugify((string) ($_POST['field'] ?? ''));
if ($field !== '' && find_category($field) === null) {
    flash('error', 'حوزه‌ی انتخاب‌شده معتبر نیست.');
    redirect(url('admin_audio_edit', ['slug' => $slug]));
}
$category = trim((string) ($_POST['category'] ?? ''));
if ($category === '' && $field !== '') {
    $category = category_label($field, $field);
}

$item = [
    'type'     => 'audio',
    'slug'     => $slug,
    'title'    => $title,
    'field'    => $field,
    'category' => $category !== '' ? $category : $field,
    'excerpt'  => trim((string) ($_POST['excerpt'] ?? '')),
    'url'      => ha_safe_file_url((string) ($_POST['url'] ?? '')),
    'seconds'  => max(0, (int) ($_POST['seconds'] ?? 0)),
    'date_fa'  => trim((string) ($_POST['date_fa'] ?? '')),
    'status'   => admin_post_status(),
    'featured' => !empty($_POST['featured']),
];

$items = admin_load('media');
$orig  = slugify((string) ($_POST['original_slug'] ?? ''));
$found = false;
foreach ($items as $i => $m) {
    $mSlug = slugify((string) ($m['slug'] ?? ''));
    $same  = ($orig !== '' && $mSlug === $orig) || ($mSlug === $slug && ($m['type'] ?? '') === 'audio');
    if ($same) {
        $items[$i] = $item;
        $found = true;
        break;
    }
}
if (!$found) $items[] = $item;

if (!admin_store('media', $items)) {
    flash('error', 'ذخیره‌سازی صوت ناموفق بود؛ storage قابل نوشتن نیست.');
    redirect(url('admin_audio_edit', ['slug' => $slug]));
}
flash('success', $item['status'] === 'published' ? 'فایل صوتی ذخیره و منتشر شد.' : 'فایل صوتی به‌عنوان پیش‌نویس ذخیره شد (مخفی).');
redirect(url('admin_audios'));
