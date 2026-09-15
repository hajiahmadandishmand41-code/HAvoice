<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_videos'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_videos')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
if ($slug === '') { flash('error','نامک ویدیو مشخص نیست.'); redirect(url('admin_videos')); }
if (!repo_delete_media('video', $slug)) { flash('error','حذف ویدیو ناموفق بود.'); redirect(url('admin_videos')); }
flash('success','ویدیو حذف شد.'); redirect(url('admin_videos'));
