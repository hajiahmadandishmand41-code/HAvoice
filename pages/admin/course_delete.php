<?php
/**
 * HAvoice Admin — حذفِ دوره
 *
 * نسخه‌ی پیشین stub بود. اکنون دوره‌های ساخته‌شده در پنل واقعاً حذف
 * می‌شوند. دوره‌هایی که از data/course.php می‌آیند قابلِ حذف از پنل
 * «نیستند» — چون حذف‌شان باید با ویرایشِ فایل انجام شود و حذفِ بی‌صدا
 * می‌توانست کلِ محتوای آموزشیِ سایت را از بین ببرد. در این حالت پیامِ
 * روشن برمی‌گردانیم.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();

$listRoute = 'admin_courses';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست شما تمام شده. دوباره تلاش کنید.'); redirect(url($listRoute)); }

$slug = slugify((string) ($_POST['slug'] ?? ''));
if ($slug === '') { flash('error', 'نامکِ دوره مشخص نیست.'); redirect(url($listRoute)); }

$courses = admin_courses();
$kept    = [];
$removed = null;
foreach ($courses as $c) {
    if (slugify((string) ($c['slug'] ?? '')) === $slug) { $removed = $c; continue; }
    $kept[] = $c;
}

if ($removed === null) {
    /* در پنل نبود ⇒ دوره از فایل می‌آید */
    $fileCourse = find_course($slug);
    flash('error', $fileCourse !== null
        ? 'دوره «' . ($fileCourse['title'] ?? $slug) . '» از فایلِ data/course.php می‌آید و از پنل حذف نمی‌شود.'
        : 'دوره‌ای با این نامک در پنل پیدا نشد.');
    redirect(url($listRoute));
}

if (!admin_store('courses', $kept)) {
    flash('error', 'حذف ناموفق بود؛ پوشه‌ی storage قابلِ نوشتن نیست.');
    redirect(url($listRoute));
}

flash('success', 'دوره «' . ($removed['title'] ?? $slug) . '» حذف شد.');
redirect(url($listRoute));
