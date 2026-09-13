<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_videos'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_videos')); }
$slug = slugify((string)($_POST['slug'] ?? '')); $title = trim((string)($_POST['title'] ?? ''));
if ($title === '' || $slug === '') { flash('error','عنوان و نامک الزامی.'); redirect(url('admin_video_edit')); }
$items = admin_load('media');
$item = ['type'=>'video','slug'=>$slug,'title'=>$title,'category'=>trim((string)($_POST['category']??'')),'excerpt'=>trim((string)($_POST['excerpt']??'')),'url'=>trim((string)($_POST['url']??'')),'seconds'=>max(0,(int)($_POST['seconds']??0)),'date_fa'=>trim((string)($_POST['date_fa']??''))];
$orig = (string)($_POST['original_slug'] ?? ''); $found = false;
foreach ($items as $i => $m) { if (($m['slug'] ?? '') === $orig || ($m['slug'] ?? '') === $slug) { $items[$i] = $item; $found = true; break; } }
if (!$found) $items[] = $item;
if (!admin_store('media', $items)) { flash('error','ذخیره‌سازی ویدیو ناموفق بود؛ storage قابل نوشتن نیست.'); redirect(url('admin_videos')); }
flash('success','ویدیو ذخیره شد.'); redirect(url('admin_videos'));