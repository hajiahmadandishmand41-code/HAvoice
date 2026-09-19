<?php
/**
 * HAvoice Admin — ذخیره‌ی مقاله‌ی علمی
 * فقط آنچه مدیر تأیید و ذخیره کند منتشر می‌شود؛ اعتبارسنجیِ کامل دارد.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
require HA_ROOT . '/pages/admin/_helpers.php';

$listRoute = 'admin_articles';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست شما تمام شده.'); redirect(url($listRoute)); }

$title = trim((string) ($_POST['title'] ?? ''));
$orig  = slugify((string) ($_POST['original_slug'] ?? ''));

/* نامک: تایپِ مدیر، وگرنه ساختِ خودکار از عنوان (فارسی ⇒ نویسه‌گردانی)
   و در صورتِ تکراری بودن، شماره‌دار تا مقاله‌ی دیگری بازنویسی نشود. */
$slug  = admin_post_slug($title, 'article', static function (string $candidate) use ($orig): bool {
    if ($candidate === $orig) {
        return false;              // همان موردی که در حالِ ویرایش است
    }
    foreach (articles_all() as $m) {
        if (slugify((string) ($m['slug'] ?? '')) === $candidate) {
            return true;
        }
    }
    return false;
});

$editParams = $orig !== '' ? ['slug' => $orig] : ($slug !== '' ? ['slug' => $slug] : []);
$editUrl = url('admin_article_edit', $editParams);

if ($title === '' || $slug === '') {
    flash('error', 'عنوان الزامی است؛ نامک به‌صورتِ خودکار از عنوان ساخته می‌شود.');
    redirect($editUrl);
}

$blocks = admin_post_blocks('blocks_json');
if ($blocks === null) {
    flash('error', 'JSON بلوک‌های مقاله معتبر نیست: ' . json_last_error_msg());
    redirect($editUrl);
}

/* حوزه: اگر انتخاب شده باید معتبر باشد */
$field = slugify((string) ($_POST['field'] ?? ''));
if ($field !== '' && find_category_any($field) === null) {
    flash('error', 'حوزه‌ی انتخاب‌شده معتبر نیست.');
    redirect($editUrl);
}

/* برچسبِ موضوع: اگر خالی بود از عنوانِ حوزه پر می‌شود تا کارت خالی نماند */
$category = trim((string) ($_POST['category'] ?? ''));
if ($category === '' && $field !== '') {
    $category = category_label($field, $field);
}
if ($category === '') {
    $category = 'عمومی';
}

/* آپلودها (اختیاری؛ در خطا متوقف نمی‌شویم ولی پیام می‌دهیم) */
$uploadNotes = [];
$kinds  = ha_upload_kinds();
// old values for cleanup on replace/remove
$oldImage = ''; $oldFile = '';
if ($orig !== '') {
    foreach (articles_all() as $a) { if (slugify((string)($a['slug'] ?? '')) === $orig) { $oldImage = (string)($a['image'] ?? ''); $oldFile = (string)($a['file'] ?? ''); break; } }
}
$image  = ha_safe_file_url((string) ($_POST['image'] ?? ''));
$upImg  = ha_upload_store('image_file', $kinds['image'], 'image');
if (!$upImg['ok']) {
    $uploadNotes[] = 'تصویر آپلود نشد: ' . $upImg['error'];
} elseif ($upImg['path'] !== '') {
    $image = $upImg['path'];
}
$file  = ha_safe_file_url((string) ($_POST['file'] ?? ''));
$upDoc = ha_upload_store('file_upload', $kinds['document'], 'document');
if (!$upDoc['ok']) {
    $uploadNotes[] = 'فایل آپلود نشد: ' . $upDoc['error'];
} elseif ($upDoc['path'] !== '') {
    $file = $upDoc['path'];
}

$article = [
    'slug'        => $slug,
    'title'       => $title,
    'field'       => $field,
    'category'    => $category,
    'author'      => trim((string) ($_POST['author'] ?? '')),
    'excerpt'     => trim((string) ($_POST['excerpt'] ?? '')),
    'date'        => trim((string) ($_POST['date'] ?? date('Y-m-d'))),
    'date_fa'     => trim((string) ($_POST['date_fa'] ?? '')),
    'minutes'     => max(1, (int) ($_POST['minutes'] ?? 5)),
    'tags'        => array_map('trim', array_filter(explode(',', (string) ($_POST['tags'] ?? '')))),
    'source_name' => trim((string) ($_POST['source_name'] ?? '')),
    'source_url'  => ha_safe_file_url((string) ($_POST['source_url'] ?? '')),
    'refs'        => admin_lines('refs_text'),
    'image'       => $image,
    'file'        => $file,
    'blocks'      => $blocks,
    'status'      => admin_post_status(),
    'featured'    => !empty($_POST['featured']),
];

if (!repo_save_article($article, $orig)) {
    flash('error', 'ذخیره‌سازی مقاله ناموفق بود؛ دیتابیس یا storage قابل نوشتن نیست.');
    redirect($editUrl);
}
// cleanup old uploads if replaced or removed
if ($oldImage !== '' && $oldImage !== $image) ha_upload_delete($oldImage);
if ($oldFile !== '' && $oldFile !== $file) ha_upload_delete($oldFile);

$msg = $article['status'] === 'published' ? 'مقاله ذخیره و منتشر شد.' : 'مقاله به‌عنوان پیش‌نویس ذخیره شد (در سایت مخفی است).';
if ($uploadNotes !== []) $msg .= ' ' . implode(' ', $uploadNotes);
flash($uploadNotes === [] ? 'success' : 'error', $msg);
redirect(url('admin_articles'));
