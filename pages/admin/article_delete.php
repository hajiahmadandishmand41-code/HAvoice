<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_articles'));
if (!csrf_verify()) { flash('error', 'نشست شما تمام شده.'); redirect(url('admin_articles')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
if ($slug === '') { flash('error', 'نامک مقاله مشخص نیست.'); redirect(url('admin_articles')); }
require HA_ROOT . '/pages/admin/_helpers.php';
$doomed = admin_find_by_slug(articles_all(), $slug);
$paths  = [admin_existing_path($doomed, 'image'), admin_existing_path($doomed, 'file')];
if (!repo_delete_article($slug)) { flash('error', 'حذف مقاله ناموفق بود.'); redirect(url('admin_articles')); }
foreach ($paths as $p) { ha_upload_discard($p); }
flash('success', 'مقاله حذف شد.');
redirect(url('admin_articles'));
