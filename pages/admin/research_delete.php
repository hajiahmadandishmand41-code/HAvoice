<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_research'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_research')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
if ($slug === '') { flash('error','نامک پژوهش مشخص نیست.'); redirect(url('admin_research')); }
$items = array_values(array_filter(admin_load('research'), fn($r) => ($r['slug'] ?? '') !== $slug));
if (!admin_store('research', $items)) { flash('error','حذف پژوهش ناموفق بود؛ storage قابل نوشتن نیست.'); redirect(url('admin_research')); }
flash('success','پژوهش حذف شد.'); redirect(url('admin_research'));