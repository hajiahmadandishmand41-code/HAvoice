<?php
/**
 * HAvoice Admin — ذخیره‌ی کتاب
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
require HA_ROOT . '/pages/admin/_helpers.php';

$listRoute = 'admin_books';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست تمام شده.'); redirect(url($listRoute)); }

$title = trim((string) ($_POST['title'] ?? ''));
$orig  = slugify((string) ($_POST['original_slug'] ?? ''));

/* نامک: تایپِ مدیر، وگرنه ساختِ خودکار از عنوان (فارسی ⇒ نویسه‌گردانی)
   و در صورتِ تکراری بودن، شماره‌دار تا کتابِ دیگری بازنویسی نشود. */
$slug  = admin_post_slug($title, 'book', static function (string $candidate) use ($orig): bool {
    if ($candidate === $orig) {
        return false;              // همان موردی که در حالِ ویرایش است
    }
    foreach (books_all() as $m) {
        if (slugify((string) ($m['slug'] ?? '')) === $candidate) {
            return true;
        }
    }
    return false;
});
$editParams = $orig !== '' ? ['slug' => $orig] : ($slug !== '' ? ['slug' => $slug] : []);
$editUrl = url('admin_book_edit', $editParams);

if ($title === '' || $slug === '') {
    flash('error', 'عنوان الزامی است؛ نامک به‌صورتِ خودکار از عنوان ساخته می‌شود.');
    redirect($editUrl);
}

$blocks = admin_post_blocks('blocks_json');
if ($blocks === null) {
    flash('error', 'JSON متنِ کاملِ کتاب معتبر نیست: ' . json_last_error_msg());
    redirect($editUrl);
}

$field = slugify((string) ($_POST['field'] ?? ''));
if ($field !== '' && find_category_any($field) === null) {
    flash('error', 'حوزه‌ی انتخاب‌شده معتبر نیست.');
    redirect($editUrl);
}

$course = slugify((string) ($_POST['course'] ?? ''));
if ($course !== '' && find_course($course) === null) {
    flash('error', 'دوره‌ی مرتبط معتبر نیست.');
    redirect($editUrl);
}

$category = trim((string) ($_POST['category'] ?? ''));
if ($category === '' && $field !== '') {
    $category = category_label($field, $field);
}

/* آپلودها */
$uploadNotes = [];
$kinds  = ha_upload_kinds();
$image  = ha_safe_file_url((string) ($_POST['image'] ?? ''));
$upImg  = ha_upload_store('image_file', $kinds['image'], 'image');
if (!$upImg['ok']) {
    $uploadNotes[] = 'جلد آپلود نشد: ' . $upImg['error'];
} elseif ($upImg['path'] !== '') {
    $image = $upImg['path'];
}
$file  = ha_safe_file_url((string) ($_POST['file'] ?? ''));
$upDoc = ha_upload_store('file_upload', $kinds['document'], 'document');
if (!$upDoc['ok']) {
    $uploadNotes[] = 'فایل کتاب آپلود نشد: ' . $upDoc['error'];
} elseif ($upDoc['path'] !== '') {
    $file = $upDoc['path'];
}

$item = [
    'slug'       => $slug,
    'title'      => $title,
    'author'     => trim((string) ($_POST['author'] ?? '')),
    'translator' => trim((string) ($_POST['translator'] ?? '')),
    'field'      => $field,
    'category'   => $category !== '' ? $category : $field,
    'course'     => $course,
    'excerpt'    => trim((string) ($_POST['excerpt'] ?? '')),
    'summary'    => trim((string) ($_POST['summary'] ?? '')),
    'lessons'    => admin_lines('lessons_text'),
    'date_fa'    => trim((string) ($_POST['date_fa'] ?? '')),
    'minutes'    => max(1, (int) ($_POST['minutes'] ?? 10)),
    'tags'       => array_map('trim', array_filter(explode(',', (string) ($_POST['tags'] ?? '')))),
    'image'      => $image,
    'file'       => $file,
    'link'       => ha_safe_file_url((string) ($_POST['link'] ?? '')),
    'blocks'     => $blocks,
    'status'     => admin_post_status(),
    'featured'   => !empty($_POST['featured']),
];

if (!repo_save_book($item, $orig)) {
    flash('error', 'ذخیره‌سازی کتاب ناموفق بود؛ دیتابیس یا storage قابل نوشتن نیست.');
    redirect($editUrl);
}
$msg = $item['status'] === 'published' ? 'کتاب ذخیره و منتشر شد.' : 'کتاب به‌عنوان پیش‌نویس ذخیره شد (مخفی).';
if ($uploadNotes !== []) $msg .= ' ' . implode(' ', $uploadNotes);
flash($uploadNotes === [] ? 'success' : 'error', $msg);
redirect(url('admin_books'));
