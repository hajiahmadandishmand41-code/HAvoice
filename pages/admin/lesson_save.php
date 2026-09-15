<?php
/**
 * HAvoice Admin — ذخیره‌ی درس (ساخت/ویرایش)
 *
 * POST-only + CSRF. نامک خودکار از عنوان (یکتا), ترتیب خودکار از محلِ
 * استقرار در مرحله. Blocks خالی = حداقلِ محتوای پایدارِ خودکار از goal.
 * «ذخیره و درسِ بعدی» کار را به همان فرم برمی‌گرداند (ساختِ پیاپی).
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_helpers.php';
auth_require_admin();

if (!csrf_verify()) {
    flash('error', 'نشست تمام شده است. لطفاً دوباره تلاش کنید.');
    redirect(url('admin'));
}

$courseSlug   = slugify((string) ($_POST['course'] ?? ''));
$origKey      = slugify((string) ($_POST['orig_key'] ?? ''));
$stageKey     = trim((string) ($_POST['stage_key'] ?? ''));
$title        = trim((string) ($_POST['title'] ?? ''));
$goal         = trim((string) ($_POST['goal'] ?? ''));
$minutes      = max(0, (int) ($_POST['minutes'] ?? 0));
$prerequisite = slugify((string) ($_POST['prerequisite'] ?? ''));
$saveVal      = (string) ($_POST['save_val'] ?? '1');
$featured     = !empty($_POST['featured']);
$status       = admin_post_status_default_draft($origKey === '');

if ($courseSlug === '') {
    flash('error', 'دوره مشخص نشده است.');
    redirect(url('admin_courses'));
}
$course = repo_course_find($courseSlug);
if ($course === null) {
    flash('error', 'دوره پیدا نشد.');
    redirect(url('admin_courses'));
}
if ($title === '') {
    flash('error', 'عنوان درس الزامی است.');
    redirect(url('admin_lesson_edit', ['course' => $courseSlug, 'stage_key' => $stageKey]));
}

/* مقصد: اولین مرحله‌ی موجود، یا «مرحله‌ی ۱» خودکار */
$stages = array_values((array) ($course['stages'] ?? []));
if ($stageKey === '' || $stages === []) {
    $stageKey = (string) ($stages[0]['id'] ?? 'stage-1');
}

$blocksJson = admin_post_blocks('blocks_json');
$drillJson  = admin_post_blocks('drill_json');

if ($blocksJson === null || $drillJson === null) {
    flash('error', 'فرمت JSON نامعتبر است؛ ذخیره لغو شد.');
    redirect(url('admin_lesson_edit', ['course' => $courseSlug, 'stage_key' => $stageKey, 'slug' => $origKey]));
}

if ($blocksJson === []) {
    /* محتوای حداقلیِ خودکار: عنوان+هدف به‌صورت بلوک‌های استاندارد */
    if ($goal !== '') {
        $blocksJson[] = ['type' => 'h2', 'text' => $title];
        $blocksJson[] = ['type' => 'p', 'text' => $goal];
    }
}

$refs = [];
foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($_POST['refs'] ?? '')))) as $r) {
    $refs[] = $r;
}

$lesson = [
    'slug'         => $origKey === '' ? slugify((string) ($_POST['slug'] ?? '')) : '',
    'title'        => $title,
    'goal'         => $goal,
    'minutes'      => $minutes,
    'blocks'       => $blocksJson,
    'drill'        => $drillJson,
    'prerequisite' => $prerequisite,
    'refs'         => $refs,
    'featured'     => $featured,
    'status'       => $status,
];

$saved = repo_save_lesson($courseSlug, $lesson, $origKey, $stageKey);
if ($saved === null) {
    flash('error', 'ذخیره‌ی درس ناموفق بود.');
    redirect(url('admin_course_view', ['slug' => $courseSlug]));
}

if ($saveVal === 'stay') {
    flash('success', 'درس ذخیره شد: «' . $title . '» — حالا درسِ بعدی را در همین فرم وارد کنید.');
    redirect(url('admin_lesson_edit', ['course' => $courseSlug, 'stage_key' => $stageKey]));
}

flash('success', 'درس ذخیره شد: «' . $title . '»');
redirect(url('admin_course_view', ['slug' => $courseSlug]));
