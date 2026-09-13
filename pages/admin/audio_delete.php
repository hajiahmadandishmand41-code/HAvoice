<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_audios'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_audios')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
if ($slug === '') { flash('error','نامک صوت مشخص نیست.'); redirect(url('admin_audios')); }
$items = array_values(array_filter(admin_load('media'), fn($m) => ($m['slug'] ?? '') !== $slug));
if (!admin_store('media', $items)) { flash('error','حذف صوت ناموفق بود؛ storage قابل نوشتن نیست.'); redirect(url('admin_audios')); }
flash('success','صوت حذف شد.'); redirect(url('admin_audios'));