<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_articles'));
if (!csrf_verify()) { flash('error', 'نشست شما تمام شده.'); redirect(url('admin_articles')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
if ($slug === '') { flash('error', 'نامک مقاله مشخص نیست.'); redirect(url('admin_articles')); }
if (!repo_delete_article($slug)) { flash('error', 'حذف مقاله ناموفق بود.'); redirect(url('admin_articles')); }
flash('success', 'مقاله حذف شد.');
redirect(url('admin_articles'));
