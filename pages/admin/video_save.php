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
$editUrl = url('admin_video_edit', $orig !== '' ? ['slug' => $orig] : []);

/* نامک: تایپِ مدیر، وگرنه ساختِ خودکار از عنوان (فارسی ⇒ نویسه‌گردانی)
   و در صورتِ تکراری بودن، شماره‌دار تا ویدیوی دیگری بازنویسی نشود. */
$slug  = admin_post_slug($title, 'video', static function (string $candidate) use ($orig): bool {
    if ($candidate === $orig) {
        return false;              // همان موردی که در حالِ ویرایش است
    }
    foreach (media_all() as $m) {
        if ((string) ($m['type'] ?? '') !== 'video') {
            continue;
        }
        if (slugify((string) ($m['slug'] ?? '')) === $candidate) {
            return true;
        }
    }
    return false;
});

if ($title === '' || $slug === '') {
    flash('error', 'عنوان الزامی است.');
    redirect($editUrl);
}

$kinds = ha_upload_kinds();

/* فایلِ ویدیو (اختیاری): اگر مدیر فایل آپلود کند، همان نشانیِ پخش می‌شود و
   فیلدِ «نشانی» می‌تواند خالی بماند. ویدیوی بزرگ روی میزبانیِ اشتراکی
   معمولاً ممکن نیست؛ در آن حالت پیوندِ آپارات/یوتیوب مسیرِ پیشنهادی است. */
$upVideo = ha_upload_store('video_file', $kinds['video'], 'video');
if (!$upVideo['ok'] && $upVideo['error'] !== null) {
    flash('error', 'آپلود ویدیو: ' . $upVideo['error']);
    redirect($editUrl);
}
$uploadedVideo = $upVideo['ok'] ? (string) $upVideo['path'] : '';

$url = $uploadedVideo !== '' ? $uploadedVideo : ha_safe_media_url((string) ($_POST['url'] ?? ''));
if ($url === '') {
    flash('error', 'یا فایلِ ویدیو را آپلود کنید یا نشانیِ آن (آپارات/یوتیوب/Vimeo یا فایلِ mp4) را بنویسید؛ بدونِ یکی از این دو، ویدیو در سایت پخش نمی‌شود.');
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
$up = ha_upload_store('thumbnail_file', $kinds['image'], 'image');
if (!$up['ok'] && $up['error'] !== null) {
    flash('error', 'آپلود بندانگشتی: ' . $up['error']);
    redirect($editUrl);
}
if ($up['ok'] && $up['path'] !== '') {
    $thumb = $up['path'];
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
$oldUrl = is_array($existing) ? (string)($existing['url'] ?? '') : '';
$oldThumb = is_array($existing) ? (string)($existing['thumbnail'] ?? '') : '';

$item = [
    'type'        => 'video',
    'slug'        => $slug,
    'title'       => $title,
    'field'       => $field,
    'category'    => $category !== '' ? $category : $field,
    'excerpt'     => trim((string) ($_POST['excerpt'] ?? '')),
    'url'         => $url,
    'thumbnail'   => $thumb,
    'course'      => $course,
    'lesson'      => $lesson,
    'seconds'     => max(0, (int) ($_POST['seconds'] ?? 0)),
    'date_fa'     => trim((string) ($_POST['date_fa'] ?? '')),
    'status'      => admin_post_status_default_draft($existing === null),
    'featured'    => !empty($_POST['featured']),
    'created_at'  => is_array($existing) ? (string) ($existing['created_at'] ?? $now) : $now,
    'updated_at'  => $now,
];

if (!repo_save_media($item, $orig)) {
    flash('error', 'ذخیره‌سازی ویدیو ناموفق بود؛ دیتابیس یا storage قابل نوشتن نیست.');
    redirect($editUrl);
}
if ($oldUrl !== '' && $oldUrl !== $url) { ha_media_delete($oldUrl); ha_upload_delete($oldUrl); }
if ($oldThumb !== '' && $oldThumb !== $thumb) ha_upload_delete($oldThumb);
flash('success', ($item['status'] === 'published' ? 'ویدیو ذخیره و منتشر شد.' : 'ویدیو به‌عنوان پیش‌نویس ذخیره شد.')
    . ($uploadedVideo !== '' ? ' فایلِ ویدیو در uploads/ ذخیره شد و در سایت پخش می‌شود.' : ''));
redirect(url('admin_videos'));
