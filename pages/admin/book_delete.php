<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_books'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_books')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
if ($slug === '') { flash('error','نامک کتاب مشخص نیست.'); redirect(url('admin_books')); }
$items = array_values(array_filter(admin_load('books'), fn($b) => ($b['slug'] ?? '') !== $slug));
if (!admin_store('books', $items)) { flash('error','حذف کتاب ناموفق بود؛ storage قابل نوشتن نیست.'); redirect(url('admin_books')); }
flash('success','کتاب حذف شد.'); redirect(url('admin_books'));