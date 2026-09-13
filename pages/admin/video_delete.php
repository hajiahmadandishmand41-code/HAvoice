<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_videos'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_videos')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
$items = array_values(array_filter(admin_load('media'), fn($m) => ($m['slug'] ?? '') !== $slug));
admin_store('media', $items); flash('success','ویدیو حذف شد.'); redirect(url('admin_videos'));
