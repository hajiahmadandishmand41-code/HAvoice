<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_books'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_books')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
if ($slug === '') { flash('error','نامک کتاب مشخص نیست.'); redirect(url('admin_books')); }
$old=null; foreach (books_all() as $b) { if (slugify((string)($b['slug']??''))===$slug) {$old=$b; break;}}
if (!repo_delete_book($slug)) { flash('error','حذف کتاب ناموفق بود.'); redirect(url('admin_books')); }
if ($old!==null) { if (!empty($old['image'])) ha_upload_delete((string)$old['image']); $f=$old['file']??($old['file_url']??''); if ($f!=='') ha_upload_delete((string)$f); }
flash('success','کتاب حذف شد.'); redirect(url('admin_books'));
