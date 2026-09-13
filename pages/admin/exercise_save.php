<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_exercises'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_exercises')); }
$id = trim((string)($_POST['id'] ?? '')); $title = trim((string)($_POST['title'] ?? ''));
if ($id === '' || $title === '') { flash('error','شناسه و عنوان الزامی.'); redirect(url('admin_exercise_edit')); }
$items = admin_load('exercises');
$steps = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['steps_text'] ?? '')))));
$topics = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['topics_text'] ?? '')))));
$item = ['id'=>$id,'title'=>$title,'level'=>trim((string)($_POST['level']??'عمومی')),'focus'=>trim((string)($_POST['focus']??'')),'goal'=>trim((string)($_POST['goal']??'')),'success'=>trim((string)($_POST['success']??'')),'seconds'=>max(0,(int)($_POST['seconds']??180)),'steps'=>$steps,'topics'=>$topics];
$orig = (string)($_POST['original_id'] ?? ''); $found = false;
foreach ($items as $i => $ex) { if (($ex['id'] ?? '') === $orig || ($ex['id'] ?? '') === $id) { $items[$i] = $item; $found = true; break; } }
if (!$found) $items[] = $item;
admin_store('exercises', $items); flash('success','تمرین ذخیره شد.'); redirect(url('admin_exercises'));
