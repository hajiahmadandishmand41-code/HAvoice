<?php
/**
 * HAvoice Admin — حذف دوره (DB + JSON)
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();

$listRoute = 'admin_courses';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست شما تمام شده. دوباره تلاش کنید.'); redirect(url($listRoute)); }

$slug = slugify((string) ($_POST['slug'] ?? ''));
if ($slug === '') { flash('error', 'نامکِ دوره مشخص نیست.'); redirect(url($listRoute)); }

$course = null;
foreach (courses_all() as $c) {
    if (slugify((string) ($c['slug'] ?? '')) === $slug) {
        $course = $c;
        break;
    }
}
if ($course === null) {
    flash('error', 'دوره‌ای با این نامک پیدا نشد.');
    redirect(url($listRoute));
}

if (!repo_delete_course($slug)) {
    flash('error', 'حذف ناموفق بود؛ دیتابیس یا storage قابل نوشتن نیست.');
    redirect(url($listRoute));
}

flash('success', 'دوره «' . ($course['title'] ?? $slug) . '» حذف شد.');
redirect(url($listRoute));
