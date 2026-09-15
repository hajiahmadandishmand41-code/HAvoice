<?php
/**
 * HAvoice Admin — حذفِ درس
 *
 * CASCADE تعریف‌شده: تمرین‌های متصل به این درس هم حذف می‌شوند — تمرینِ
 * بدونِ درس (orphan) بر اساس معماری مجاز نیست.
 * DB با FK (ha_exercises.lesson_id → ha_lessons.id ON DELETE CASCADE) و
 * آینه‌ی JSON با repo_delete_lesson.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_helpers.php';
auth_require_admin();

if (!csrf_verify()) {
    flash('error', 'نشست تمام شده است. لطفاً دوباره تلاش کنید.');
    redirect(url('admin'));
}

$courseSlug = slugify((string) ($_POST['course'] ?? ''));
$lessonSlug = slugify((string) ($_POST['slug'] ?? ''));
$stageKey   = trim((string) ($_POST['stage_key'] ?? ''));

if ($courseSlug === '' || $lessonSlug === '') {
    flash('error', 'ورودی حذفِ درس ناقص است.');
    redirect(url('admin_courses'));
}

if (repo_delete_lesson($courseSlug, $lessonSlug)) {
    flash('success', 'درس و تمرین‌های متصل به آن حذف شدند.');
    redirect(url('admin_course_view', ['slug' => $courseSlug]));
}

flash('error', 'حذفِ درس ناموفق بود.');
redirect(url('admin_course_view', ['slug' => $courseSlug]));
