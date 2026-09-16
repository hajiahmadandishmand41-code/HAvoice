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

$title = trim((string) ($_POST['title'] ?? ''));
$orig  = slugify((string) ($_POST['original_id'] ?? ''));
$id    = slugify((string) ($_POST['id_override'] ?? ($_POST['id'] ?? '')));
if ($title === '') {
    flash('error', 'نام تمرین الزامی است.');
    redirect(url('admin_exercise_edit', $orig !== '' ? ['slug' => $orig] : []));
}
if ($id === '') {
    $id = admin_post_slug($title, 'ex', static function (string $candidate) use ($orig): bool {
        if ($candidate === $orig) {
            return false;
        }
        foreach (exercises_all() as $ex) {
            if (slugify((string) ($ex['id'] ?? '')) === $candidate) {
                return true;
            }
        }
        return false;
    });
}
if ($id === '' && $orig !== '') {
    $id = $orig;
}
if ($id === '') {
    flash('error', 'شناسه تمرین ساخته نشد.');
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
    'order'    => max(0, (int) ($_POST['order'] ?? 0)),
    'status'   => admin_post_status(),
    'featured' => !empty($_POST['featured']),
];

if (!repo_save_exercise($item, $orig)) {
    flash('error', 'ذخیره‌سازی تمرین ناموفق بود؛ دیتابیس یا storage قابل نوشتن نیست.');
    redirect(url('admin_exercises'));
}
flash('success', $item['status'] === 'published' ? 'تمرین ذخیره و منتشر شد.' : 'تمرین به‌عنوان پیش‌نویس ذخیره شد (مخفی).');
redirect(url('admin_exercises'));
