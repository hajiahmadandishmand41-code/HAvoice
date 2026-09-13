<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_users'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_users')); }
$id = (string)($_POST['id'] ?? '');
$users = auth_load_users();
if (($users[0]['id'] ?? '') === $id) { flash('error','نمی‌توان مدیر اصلی را حذف کرد.'); redirect(url('admin_users')); }
$users = array_values(array_filter($users, fn($u) => ($u['id'] ?? '') !== $id));
auth_save_users($users); flash('success','کاربر حذف شد.'); redirect(url('admin_users'));
