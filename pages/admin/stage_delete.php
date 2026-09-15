<?php
/**
 * HAvoice Admin — حذفِ مرحله
 *
 * CASCADE تعریف‌شده: درس‌های مرحله و تمرین‌های آن درس‌ها هم حذف می‌شوند
 * (DB با FK؛ آینه‌ی JSON با repo_delete_stage). تمرینِ orphan مجاز نیست.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_helpers.php';
auth_require_admin();

if (!csrf_verify()) {
    flash('error', 'نشست تمام شده است. لطفاً دوباره تلاش کنید.');
    redirect(url('admin'));
}

$courseSlug = slugify((string) ($_POST['course'] ?? ''));
$stageKey   = trim((string) ($_POST['stage_key'] ?? ''));

if ($courseSlug === '' || $stageKey === '') {
    flash('error', 'ورودی حذفِ مرحله ناقص است.');
    redirect(url('admin_courses'));
}

if (repo_delete_stage($courseSlug, $stageKey)) {
    flash('success', 'مرحله و درس‌ها و تمرین‌های متصل به آن حذف شدند.');
} else {
    flash('error', 'حذفِ مرحله ناموفق بود.');
}
redirect(url('admin_course_view', ['slug' => $courseSlug]));
