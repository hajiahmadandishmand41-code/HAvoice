<?php
/**
 * HAvoice Admin — تغییرِ وضعیتِ انتشار (منتشرشده ⇄ پیش‌نویس)
 * مسیر: Validation → repo_set_status → PDO/JSON
 * نوع‌های lesson/stage به داشبوردِ دوره برمی‌گردند (پارامترِ course لازم است).
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
require HA_ROOT . '/pages/admin/_helpers.php';

$registry = [
    'course'   => 'admin_courses',
    'article'  => 'admin_articles',
    'video'    => 'admin_videos',
    'audio'    => 'admin_audios',
    'book'     => 'admin_books',
    'research' => 'admin_research',
    'exercise' => 'admin_exercises',
    'tip'      => 'admin_tips',
    'category' => 'admin_categories',
];

$type       = (string) ($_POST['type'] ?? '');
$courseSlug = slugify((string) ($_POST['course'] ?? ''));

/* lesson/stage یک مسیرِ ویژه دارند: بازگشت به داشبوردِ دوره */
$isStructure = in_array($type, ['lesson', 'stage'], true);
if ($isStructure) {
    $goBack = $courseSlug !== ''
        ? url('admin_course_view', ['slug' => $courseSlug])
        : url('admin_courses');
} else {
    $goBack = url($registry[$type] ?? 'admin');
}

if (!$isStructure && !isset($registry[$type])) {
    flash('error', 'نوعِ محتوا معتبر نیست.');
    redirect(url('admin'));
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect($goBack);
}
if (!csrf_verify()) {
    flash('error', 'نشست شما تمام شده است. دوباره تلاش کنید.');
    redirect($goBack);
}

$key    = slugify((string) ($_POST['key'] ?? ''));
$status = admin_post_status();
if ($key === '') {
    flash('error', 'شناسه‌ی محتوا معتبر نیست.');
    redirect($goBack);
}

$ok = $isStructure
    ? repo_set_status($type, $key, $status, $courseSlug)
    : repo_set_status($type, $key, $status);

if (!$ok) {
    flash('error', 'ذخیره‌سازی ناموفق بود؛ دیتابیس یا storage قابل نوشتن نیست.');
    redirect($goBack);
}

flash('success', $status === 'published'
    ? 'با موفقیت منتشر شد؛ اکنون برای کاربران نمایش داده می‌شود.'
    : 'مخفی شد؛ دیگر در سایت نمایش داده نمی‌شود ولی حذف نشده است.');
redirect($goBack);
