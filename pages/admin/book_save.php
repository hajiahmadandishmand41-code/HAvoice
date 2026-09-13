<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_books'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_books')); }
$slug = slugify((string)($_POST['slug'] ?? '')); $title = trim((string)($_POST['title'] ?? ''));
if ($title === '' || $slug === '') { flash('error','عنوان و نامک الزامی.'); redirect(url('admin_book_edit')); }
$items = admin_load('books');
$item = ['slug'=>$slug,'title'=>$title,'author'=>trim((string)($_POST['author']??'')),'category'=>trim((string)($_POST['category']??'')),'excerpt'=>trim((string)($_POST['excerpt']??'')),'summary'=>trim((string)($_POST['summary']??'')),'date_fa'=>trim((string)($_POST['date_fa']??'')),'minutes'=>max(1,(int)($_POST['minutes']??10)),'tags'=>array_map('trim',array_filter(explode(',',(string)($_POST['tags']??''))))];
$orig = (string)($_POST['original_slug'] ?? ''); $found = false;
foreach ($items as $i => $b) { if (($b['slug'] ?? '') === $orig || ($b['slug'] ?? '') === $slug) { $items[$i] = $item; $found = true; break; } }
if (!$found) $items[] = $item;
if (!admin_store('books', $items)) { flash('error','ذخیره‌سازی کتاب ناموفق بود؛ storage قابل نوشتن نیست.'); redirect(url('admin_books')); }
flash('success','کتاب ذخیره شد.'); redirect(url('admin_books'));