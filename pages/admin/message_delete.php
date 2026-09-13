<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_messages'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_messages')); }
$idx = (int)($_POST['index'] ?? -1);
$messages = admin_load('messages');
if ($idx >= 0 && $idx < count($messages)) { array_splice($messages, $idx, 1); admin_store('messages', $messages); }
flash('success','پیام حذف شد.'); redirect(url('admin_messages'));
