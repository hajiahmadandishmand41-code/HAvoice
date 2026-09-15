<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
require HA_ROOT . '/pages/admin/_helpers.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_categories'));
if (!csrf_verify()) { flash('error', 'نشست تمام شده.'); redirect(url('admin_categories')); }

$slug  = slugify((string) ($_POST['slug'] ?? ''));
$title = trim((string) ($_POST['title'] ?? ''));
if ($title === '' || $slug === '') { flash('error', 'عنوان و نامک الزامی.'); redirect(url('admin_category_edit')); }

$color  = trim((string) ($_POST['color'] ?? '#1A3A7C'));
$accent = trim((string) ($_POST['accent'] ?? '#4F46E5'));
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color) || !preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
    flash('error', 'رنگ‌ها باید به‌صورت کد HEX شش‌رقمی باشند.');
    redirect(url('admin_category_edit', ['slug' => $slug]));
}

$item = [
    'slug'        => $slug,
    'title'       => $title,
    'short'       => trim((string) ($_POST['short'] ?? '')),
    'description' => trim((string) ($_POST['description'] ?? '')),
    'icon'        => trim((string) ($_POST['icon'] ?? 'compass')),
    'color'       => $color,
    'accent'      => $accent,
    'status'      => admin_post_status(),
];

$items = admin_load('categories');
$orig  = slugify((string) ($_POST['original_slug'] ?? ''));
$found = false;
foreach ($items as $i => $c) {
    $cSlug = slugify((string) ($c['slug'] ?? ''));
    if (($orig !== '' && $cSlug === $orig) || $cSlug === $slug) { $items[$i] = $item; $found = true; break; }
}
if (!$found) $items[] = $item;

if (!admin_store('categories', $items)) { flash('error', 'ذخیره‌سازی حوزه ناموفق بود؛ storage قابل نوشتن نیست.'); redirect(url('admin_categories')); }
flash('success', $item['status'] === 'published' ? 'حوزه ذخیره و منتشر شد.' : 'حوزه به‌عنوان پیش‌نویس ذخیره شد (مخفی).');
redirect(url('admin_categories'));
