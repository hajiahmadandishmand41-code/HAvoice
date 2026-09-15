<?php
/**
 * HAvoice Admin — ذخیره‌ی مرحله (ساخت/ویرایشِ عنوان و خلاصه)
 *
 * POST-only + CSRF. درس‌های مرحله دست‌نخورده می‌مانند؛ فقط متادیتای خودِ
 * مرحله (label/title/summary/outcome/duration) ذخیره می‌شود.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_helpers.php';
auth_require_admin();

if (!csrf_verify()) {
    flash('error', 'نشست تمام شده است. لطفاً دوباره تلاش کنید.');
    redirect(url('admin'));
}

$courseSlug = slugify((string) ($_POST['course'] ?? ''));
$origKey    = trim((string) ($_POST['orig_key'] ?? ''));

if ($courseSlug === '') {
    flash('error', 'دوره مشخص نشده است.');
    redirect(url('admin_courses'));
}

$title = trim((string) ($_POST['title'] ?? ''));
if ($title === '') {
    flash('error', 'عنوان مرحله الزامی است.');
    redirect(url('admin_course_view', ['slug' => $courseSlug]));
}

$stage = [
    'id'       => trim((string) ($_POST['id'] ?? '')),
    'label'    => trim((string) ($_POST['label'] ?? '')),
    'title'    => $title,
    'summary'  => trim((string) ($_POST['summary'] ?? '')),
    'outcome'  => trim((string) ($_POST['outcome'] ?? '')),
    'duration' => trim((string) ($_POST['duration'] ?? '')),
];

$key = repo_save_stage($courseSlug, $stage, $origKey);
if ($key === null) {
    flash('error', 'ذخیره‌ی مرحله ناموفق بود.');
    redirect(url('admin_course_view', ['slug' => $courseSlug]));
}

flash('success', $origKey !== '' ? 'مرحله ویرایش شد.' : 'مرحله‌ی جدید ساخته شد.');
redirect(url('admin_course_view', ['slug' => $courseSlug]));
