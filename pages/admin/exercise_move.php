<?php
/**
 * HAvoice Admin — جابه‌جایی ترتیب تمرین (بالا/پایین)
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();

$listRoute = 'admin_exercises';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست تمام شده.'); redirect(url($listRoute)); }

$id  = slugify((string) ($_POST['id'] ?? ''));
$dir = ((string) ($_POST['dir'] ?? '')) === 'up' ? 'up' : 'down';
if ($id === '') {
    flash('error', 'شناسه تمرین مشخص نیست.');
    redirect(url($listRoute));
}

if (!function_exists('repo_move_exercise') || !repo_move_exercise($id, $dir)) {
    flash('error', 'جابه‌جایی ترتیب ناموفق بود.');
    redirect(url($listRoute));
}
flash('success', 'ترتیب تمرین به‌روز شد.');
redirect(url($listRoute));
