<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_categories'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_categories')); }
$slug = slugify((string)($_POST['slug'] ?? '')); $title = trim((string)($_POST['title'] ?? ''));
if ($title === '' || $slug === '') { flash('error','عنوان و نامک الزامی.'); redirect(url('admin_category_edit')); }
$items = admin_load('categories');
$item = ['slug'=>$slug,'title'=>$title,'short'=>trim((string)($_POST['short']??'')),'description'=>trim((string)($_POST['description']??'')),'icon'=>trim((string)($_POST['icon']??'compass')),'color'=>trim((string)($_POST['color']??'#0d5c4d')),'accent'=>trim((string)($_POST['accent']??'#2ec4a6'))];
$orig = (string)($_POST['original_slug'] ?? ''); $found = false;
foreach ($items as $i => $c) { if (($c['slug'] ?? '') === $orig || ($c['slug'] ?? '') === $slug) { $items[$i] = $item; $found = true; break; } }
if (!$found) $items[] = $item;
admin_store('categories', $items); flash('success','حوزه ذخیره شد.'); redirect(url('admin_categories'));
