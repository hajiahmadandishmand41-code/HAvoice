<?php
/**
 * HAvoice Admin — ذخیره‌ی صوت
 * مسیر: Validation → repo_save_media → PDO + JSON
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
require HA_ROOT . '/pages/admin/_helpers.php';

$listRoute = 'admin_audios';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست تمام شده.'); redirect(url($listRoute)); }

$title = trim((string) ($_POST['title'] ?? ''));
$slug  = admin_post_slug($title);
$orig  = slugify((string) ($_POST['original_slug'] ?? ''));
$editUrl = url('admin_audio_edit', $orig !== '' ? ['slug' => $orig] : []);

if ($title === '' || $slug === '') {
    flash('error', 'عنوان الزامی است.');
    redirect($editUrl);
}

$url = ha_safe_media_url((string) ($_POST['url'] ?? ''));
if ($url === '') {
    flash('error', 'نشانی فایل صوتی معتبر نیست.');
    redirect($editUrl);
}

$field = slugify((string) ($_POST['field'] ?? ''));
if ($field !== '' && find_category_any($field) === null) {
    flash('error', 'حوزه‌ی انتخاب‌شده معتبر نیست.');
    redirect($editUrl);
}

$course = slugify((string) ($_POST['course'] ?? ''));
$lesson = slugify((string) ($_POST['lesson'] ?? ''));
$category = trim((string) ($_POST['category'] ?? ''));
if ($category === '' && $field !== '') {
    $category = category_label($field, $field);
}

$now = date('c');
$existing = null;
foreach (media_all() as $m) {
    $mSlug = slugify((string) ($m['slug'] ?? ''));
    if (($orig !== '' && $mSlug === $orig) || ($mSlug === $slug && ($m['type'] ?? '') === 'audio')) {
        $existing = $m;
        break;
    }
}

$item = [
    'type'       => 'audio',
    'slug'       => $slug,
    'title'      => $title,
    'field'      => $field,
    'category'   => $category !== '' ? $category : $field,
    'excerpt'    => trim((string) ($_POST['excerpt'] ?? '')),
    'url'        => $url,
    'course'     => $course,
    'lesson'     => $lesson,
    'seconds'    => max(0, (int) ($_POST['seconds'] ?? 0)),
    'date_fa'    => trim((string) ($_POST['date_fa'] ?? '')),
    'status'     => admin_post_status_default_draft($existing === null),
    'featured'   => !empty($_POST['featured']),
    'created_at' => (string) ($existing['created_at'] ?? $now),
    'updated_at' => $now,
];

if (!repo_save_media($item, $orig)) {
    flash('error', 'ذخیره‌سازی صوت ناموفق بود؛ دیتابیس یا storage قابل نوشتن نیست.');
    redirect($editUrl);
}
flash('success', $item['status'] === 'published' ? 'فایل صوتی ذخیره و منتشر شد.' : 'فایل صوتی به‌عنوان پیش‌نویس ذخیره شد.');
redirect(url('admin_audios'));
