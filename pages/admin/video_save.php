<?php
/**
 * HAvoice Admin — ذخیره‌ی ویدیو (مسیر واحد)
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
require HA_ROOT . '/pages/admin/_helpers.php';

$listRoute = 'admin_videos';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست تمام شده.'); redirect(url($listRoute)); }

$title = trim((string) ($_POST['title'] ?? ''));
$orig  = slugify((string) ($_POST['original_slug'] ?? ''));
$slug  = admin_post_slug_keep($title, $orig);
$editUrl = url('admin_video_edit', $orig !== '' ? ['slug' => $orig] : []);

if ($title === '' || $slug === '') {
    flash('error', 'عنوان الزامی است.');
    redirect($editUrl);
}

$url = ha_safe_media_url((string) ($_POST['url'] ?? ''));
if ($url === '') {
    flash('error', 'نشانی ویدیو معتبر نیست. بدون URL واقعی، ویدیو در سایت نمایش داده نمی‌شود.');
    redirect($editUrl);
}

$field = slugify((string) ($_POST['field'] ?? ''));
if ($field !== '' && find_category_any($field) === null) {
    flash('error', 'حوزه‌ی انتخاب‌شده معتبر نیست.');
    redirect($editUrl);
}

$course = slugify((string) ($_POST['course'] ?? ''));
if ($course !== '') {
    $ok = false;
    foreach (courses_all() as $c) {
        if (slugify((string) ($c['slug'] ?? '')) === $course) { $ok = true; break; }
    }
    if (!$ok) {
        flash('error', 'دوره‌ی انتخاب‌شده معتبر نیست.');
        redirect($editUrl);
    }
}

$lesson = slugify((string) ($_POST['lesson'] ?? ''));
if ($lesson !== '' && course_find_lesson($lesson) === null) {
    /* lesson may be in draft course; still store slug */
}

$category = trim((string) ($_POST['category'] ?? ''));
if ($category === '' && $field !== '') {
    $category = category_label($field, $field);
}

/* بندانگشتی: آپلود یا URL */
$thumb = ha_safe_file_url((string) ($_POST['thumbnail'] ?? ''));
if (function_exists('ha_upload_store')) {
    $up = ha_upload_store('thumbnail_file', [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ]);
    if (!$up['ok'] && !empty($up['error'])) {
        flash('error', 'آپلود بندانگشتی: ' . $up['error']);
        redirect($editUrl);
    }
    if ($up['ok'] && $up['path'] !== '') {
        $thumb = $up['path'];
    }
}

$now = date('c');
$existing = null;
$items = admin_load('media');
foreach ($items as $m) {
    $mSlug = slugify((string) ($m['slug'] ?? ''));
    if (($orig !== '' && $mSlug === $orig) || ($mSlug === $slug && ($m['type'] ?? '') === 'video')) {
        $existing = $m;
        break;
    }
}

/* یکتاییِ خودکارِ نامکِ تازه (برخورد ⇒ پسوندِ -2/-3 — نه بازنویسیِ بی‌خبر) */
if ($existing === null) {
    $allSlugs = [];
    foreach (media_all() as $m) { $allSlugs[] = (string) ($m['slug'] ?? ''); }
    $slug = admin_unique_slug($slug, $allSlugs, $orig);
}

$item = [
    'type'        => 'video',
    'slug'        => $slug,
    'title'       => $title,
    'field'       => $field,
    'category'    => $category !== '' ? $category : ($existing['category'] ?? $field),
    'excerpt'     => trim((string) ($_POST['excerpt'] ?? '')),
    'url'         => $url,
    'thumbnail'   => $thumb,
    'course'      => $course,
    'lesson'      => $lesson,
    'seconds'     => max(0, (int) ($_POST['seconds'] ?? (int) ($existing['seconds'] ?? 0))),
    'date_fa'     => trim((string) ($_POST['date_fa'] ?? '')) ?: (string) ($existing['date_fa'] ?? ''),
    'status'      => admin_post_status_default_draft($existing === null),
    'featured'    => !empty($_POST['featured']),
    'created_at'  => (string) ($existing['created_at'] ?? $now),
    'updated_at'  => $now,
];

if (!repo_save_media($item, $orig)) {
    flash('error', 'ذخیره‌سازی ویدیو ناموفق بود؛ دیتابیس یا storage قابل نوشتن نیست.');
    redirect($editUrl);
}
flash('success', $item['status'] === 'published' ? 'ویدیو ذخیره و منتشر شد.' : 'ویدیو به‌عنوان پیش‌نویس ذخیره شد.');
redirect(url('admin_videos'));
