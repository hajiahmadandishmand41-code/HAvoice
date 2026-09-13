<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_research'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_research')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
$items = array_values(array_filter(admin_load('research'), fn($r) => ($r['slug'] ?? '') !== $slug));
admin_store('research', $items); flash('success','پژوهش حذف شد.'); redirect(url('admin_research'));
