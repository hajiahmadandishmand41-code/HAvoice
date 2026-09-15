<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_categories'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_categories')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
if ($slug === '') { flash('error','نامک حوزه مشخص نیست.'); redirect(url('admin_categories')); }
if (!repo_delete_category($slug)) { flash('error','حذف حوزه ناموفق بود.'); redirect(url('admin_categories')); }
flash('success','حوزه حذف شد.'); redirect(url('admin_categories'));
