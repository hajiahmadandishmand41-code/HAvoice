<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_books'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_books')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
if ($slug === '') { flash('error','نامک کتاب مشخص نیست.'); redirect(url('admin_books')); }
require HA_ROOT . '/pages/admin/_helpers.php';
$doomed = admin_find_by_slug(books_all(), $slug);
$paths  = [admin_existing_path($doomed, 'image', 'cover'), admin_existing_path($doomed, 'file')];
if (!repo_delete_book($slug)) { flash('error','حذف کتاب ناموفق بود.'); redirect(url('admin_books')); }
foreach ($paths as $p) { ha_upload_discard($p); }
flash('success','کتاب حذف شد.'); redirect(url('admin_books'));
