<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_videos'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_videos')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
if ($slug === '') { flash('error','نامک ویدیو مشخص نیست.'); redirect(url('admin_videos')); }
require HA_ROOT . '/pages/admin/_helpers.php';
$doomed = admin_find_media($slug, 'video');
$paths  = [admin_existing_path($doomed, 'url'), admin_existing_path($doomed, 'thumbnail')];
if (!repo_delete_media('video', $slug)) { flash('error','حذف ویدیو ناموفق بود.'); redirect(url('admin_videos')); }
foreach ($paths as $p) { ha_upload_discard($p); }
flash('success','ویدیو حذف شد.'); redirect(url('admin_videos'));
