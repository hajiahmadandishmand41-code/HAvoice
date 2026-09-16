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
$orig  = slugify((string) ($_POST['original_slug'] ?? ''));
$editUrl = url('admin_audio_edit', $orig !== '' ? ['slug' => $orig] : []);

/* نامک: اول آنچه مدیر تایپ کرده، وگرنه ساختِ خودکار از عنوان — عنوانِ
   فارسی هم با نویسه‌گردانی به نامکِ خوانا تبدیل می‌شود و اگر تکراری باشد
   شماره می‌گیرد تا صوتِ دیگری بازنویسی نشود. */
$slug  = admin_post_slug($title, 'audio', static function (string $candidate) use ($orig): bool {
    if ($candidate === $orig) {
        return false;              // همان موردی که در حالِ ویرایش است
    }
    foreach (media_all() as $m) {
        if ((string) ($m['type'] ?? '') !== 'audio') {
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

/* فایلِ صوتی (اختیاری): اگر آپلود شود، همان نشانیِ پخش می‌شود. */
$upAudio = ha_upload_store('audio_file', $kinds['audio'], 'audio');
if (!$upAudio['ok'] && $upAudio['error'] !== null) {
    flash('error', 'آپلود صوت: ' . $upAudio['error']);
    redirect($editUrl);
}
$uploadedAudio = $upAudio['ok'] ? (string) $upAudio['path'] : '';

$url = $uploadedAudio !== '' ? $uploadedAudio : ha_safe_media_url((string) ($_POST['url'] ?? ''));
if ($url === '') {
    flash('error', 'یا فایلِ صوتی را آپلود کنید یا نشانیِ آن را بنویسید؛ بدونِ یکی از این دو، پادکست در سایت پخش نمی‌شود.');
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
flash('success', ($item['status'] === 'published' ? 'فایل صوتی ذخیره و منتشر شد.' : 'فایل صوتی به‌عنوان پیش‌نویس ذخیره شد.')
    . ($uploadedAudio !== '' ? ' فایلِ صوتی در uploads/ ذخیره شد و با پخش‌کننده‌ی سایت پخش می‌شود.' : ''));
redirect(url('admin_audios'));
