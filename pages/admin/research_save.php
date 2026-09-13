<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_research'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_research')); }
$slug = slugify((string)($_POST['slug'] ?? '')); $title = trim((string)($_POST['title'] ?? ''));
if ($title === '' || $slug === '') { flash('error','عنوان و نامک الزامی.'); redirect(url('admin_research_edit')); }
$items = admin_load('research');
$item = ['slug'=>$slug,'title'=>$title,'category'=>trim((string)($_POST['category']??'')),'summary'=>trim((string)($_POST['summary']??'')),'date_fa'=>trim((string)($_POST['date_fa']??'')),'tags'=>array_map('trim',array_filter(explode(',',(string)($_POST['tags']??''))))];
$orig = (string)($_POST['original_slug'] ?? ''); $found = false;
foreach ($items as $i => $r) { if (($r['slug'] ?? '') === $orig || ($r['slug'] ?? '') === $slug) { $items[$i] = $item; $found = true; break; } }
if (!$found) $items[] = $item;
admin_store('research', $items); flash('success','پژوهش ذخیره شد.'); redirect(url('admin_research'));
