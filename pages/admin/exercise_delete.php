<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_exercises'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_exercises')); }
$id = trim((string)($_POST['id'] ?? ''));
$items = array_values(array_filter(admin_load('exercises'), fn($ex) => ($ex['id'] ?? '') !== $id));
admin_store('exercises', $items); flash('success','تمرین حذف شد.'); redirect(url('admin_exercises'));
