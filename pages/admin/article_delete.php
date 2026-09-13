<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_articles'));
if (!csrf_verify()) { flash('error', 'نشست شما تمام شده.'); redirect(url('admin_articles')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
if ($slug === '') { flash('error', 'نامک مقاله مشخص نیست.'); redirect(url('admin_articles')); }
$articles = admin_load('articles');
$articles = array_values(array_filter($articles, fn($a) => ($a['slug'] ?? '') !== $slug));
if (!admin_store('articles', $articles)) { flash('error', 'حذف مقاله ناموفق بود؛ storage قابل نوشتن نیست.'); redirect(url('admin_articles')); }
flash('success', 'مقاله حذف شد.');
redirect(url('admin_articles'));