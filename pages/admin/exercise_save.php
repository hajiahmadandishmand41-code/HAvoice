<?php
/**
 * HAvoice Admin — ذخیره‌ی تمرین
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
require HA_ROOT . '/pages/admin/_helpers.php';

$listRoute = 'admin_exercises';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست تمام شده.'); redirect(url($listRoute)); }

$id    = slugify((string) ($_POST['id'] ?? ''));
$title = trim((string) ($_POST['title'] ?? ''));
if ($id === '' || $title === '') {
    flash('error', 'شناسه و عنوان الزامی.');
    redirect(url('admin_exercise_edit'));
}

/* اعتبارسنجیِ اتصال‌ها */
$lesson = slugify((string) ($_POST['lesson'] ?? ''));
if ($lesson !== '' && course_find_lesson($lesson) === null) {
    flash('error', 'درسِ انتخاب‌شده معتبر نیست.');
    redirect(url('admin_exercise_edit', ['slug' => $id]));
}
$course = slugify((string) ($_POST['course'] ?? ''));
if ($course !== '' && find_course($course) === null) {
    flash('error', 'دوره‌ی انتخاب‌شده معتبر نیست.');
    redirect(url('admin_exercise_edit', ['slug' => $id]));
}
/* اگر درس انتخاب شده و دوره خالی مانده، از رویِ درس استنباط می‌شود */
if ($course === '' && $lesson !== '') {
    $info = course_find_lesson($lesson);
    $course = slugify((string) ($info['course']['slug'] ?? ''));
}

$item = [
    'id'       => $id,
    'title'    => $title,
    'level'    => trim((string) ($_POST['level'] ?? 'عمومی')),
    'focus'    => trim((string) ($_POST['focus'] ?? '')),
    'goal'     => trim((string) ($_POST['goal'] ?? '')),
    'success'  => trim((string) ($_POST['success'] ?? '')),
    'seconds'  => max(0, (int) ($_POST['seconds'] ?? 180)),
    'steps'    => admin_lines('steps_text'),
    'topics'   => admin_lines('topics_text'),
    'lesson'   => $lesson,
    'course'   => $course,
    'status'   => admin_post_status(),
    'featured' => !empty($_POST['featured']),
];

$orig = slugify((string) ($_POST['original_id'] ?? ''));
if (!repo_save_exercise($item, $orig)) {
    flash('error', 'ذخیره‌سازی تمرین ناموفق بود؛ دیتابیس یا storage قابل نوشتن نیست.');
    redirect(url('admin_exercises'));
}
flash('success', $item['status'] === 'published' ? 'تمرین ذخیره و منتشر شد.' : 'تمرین به‌عنوان پیش‌نویس ذخیره شد (مخفی).');
redirect(url('admin_exercises'));
