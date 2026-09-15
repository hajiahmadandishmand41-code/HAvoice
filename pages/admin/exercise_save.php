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

/* اعتبارسنجیِ اتصال‌ها — اتصال به «درس» الزامی است (تمرینِ orphan ممنوع:
   معماریِ سایت Course → Stage → Lesson → Exercise را قطعی می‌کنیم). */
$lesson = slugify((string) ($_POST['lesson'] ?? ''));
if ($lesson === '') {
    flash('error', 'اتصالِ تمرین به یک درس الزامی است (تمرینِ بدونِ درس مجاز نیست).');
    redirect(url('admin_exercise_edit', ['slug' => $id]));
}
/* وجودِ درس باید «حتی در دوره‌های پیش‌نویسِ پنل» بررسی شود، نه فقط
   فهرستِ عمومیِ منتشرشده (پنل محتوای draft را هم مدیریت می‌کند). */
$lessonInfo = repo_course_find_by_lesson($lesson);
if ($lessonInfo === null && course_find_lesson($lesson) === null) {
    flash('error', 'درسِ انتخاب‌شده معتبر نیست.');
    redirect(url('admin_exercise_edit', ['slug' => $id]));
}
$course = slugify((string) ($_POST['course'] ?? ''));
/* دوره می‌تواند پیش‌نویسِ پنل باشد — در فهرستِ عمومی (courses()) نیست */
if ($course !== '' && find_course($course) === null && repo_course_find($course) === null) {
    flash('error', 'دوره‌ی انتخاب‌شده معتبر نیست.');
    redirect(url('admin_exercise_edit', ['slug' => $id]));
}
/* اگر درس انتخاب شده و دوره خالی مانده، از رویِ درس استنباط می‌شود */
if ($course === '' && $lesson !== '') {
    $info = course_find_lesson($lesson);
    $course = $info !== null
        ? slugify((string) ($info['course']['slug'] ?? ''))
        : slugify((string) ($lessonInfo['slug'] ?? $lessonInfo['course_slug'] ?? ''));
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
$backCourse = slugify((string) ($_POST['back_course'] ?? ''));
if ($backCourse !== '' && (find_course($backCourse) !== null || repo_course_find($backCourse) !== null)) {
    redirect(url('admin_course_view', ['slug' => $backCourse]));
}
redirect(url('admin_exercises'));
