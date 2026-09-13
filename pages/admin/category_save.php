<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_categories'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_categories')); }
$slug = slugify((string)($_POST['slug'] ?? '')); $title = trim((string)($_POST['title'] ?? ''));
if ($title === '' || $slug === '') { flash('error','عنوان و نامک الزامی.'); redirect(url('admin_category_edit')); }
$items = admin_load('categories');
$color = trim((string)($_POST['color'] ?? '#1A3A7C'));
$accent = trim((string)($_POST['accent'] ?? '#4F46E5'));
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color) || !preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
    flash('error','رنگ‌ها باید به‌صورت کد HEX شش‌رقمی باشند.');
    redirect(url('admin_category_edit', $slug !== '' ? ['slug' => $slug] : []));
}
$item = ['slug'=>$slug,'title'=>$title,'short'=>trim((string)($_POST['short']??'')),'description'=>trim((string)($_POST['description']??'')),'icon'=>trim((string)($_POST['icon']??'compass')),'color'=>$color,'accent'=>$accent];
$orig = (string)($_POST['original_slug'] ?? ''); $found = false;
foreach ($items as $i => $c) { if (($c['slug'] ?? '') === $orig || ($c['slug'] ?? '') === $slug) { $items[$i] = $item; $found = true; break; } }
if (!$found) $items[] = $item;
if (!admin_store('categories', $items)) { flash('error','ذخیره‌سازی حوزه ناموفق بود؛ storage قابل نوشتن نیست.'); redirect(url('admin_categories')); }
flash('success','حوزه ذخیره شد.'); redirect(url('admin_categories')); 
