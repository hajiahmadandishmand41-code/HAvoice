<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_tips'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_tips')); }
$id = trim((string)($_POST['id'] ?? '')); $text = trim((string)($_POST['text'] ?? ''));
if ($id === '' || $text === '') { flash('error','شناسه و متن الزامی.'); redirect(url('admin_tip_edit')); }
$items = admin_load('tips');
$item = ['id'=>$id,'text'=>$text,'try'=>trim((string)($_POST['try']??'')),'category'=>trim((string)($_POST['category']??'عمومی'))];
$orig = (string)($_POST['original_id'] ?? ''); $found = false;
foreach ($items as $i => $t) { if (($t['id'] ?? '') === $orig || ($t['id'] ?? '') === $id) { $items[$i] = $item; $found = true; break; } }
if (!$found) $items[] = $item;
if (!admin_store('tips', $items)) { flash('error','ذخیره‌سازی نکته ناموفق بود؛ storage قابل نوشتن نیست.'); redirect(url('admin_tips')); }
flash('success','نکته ذخیره شد.'); redirect(url('admin_tips'));