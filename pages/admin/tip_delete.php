<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_tips'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_tips')); }
$id = slugify((string)($_POST['id'] ?? ''));
if ($id === '') { flash('error','شناسه نکته مشخص نیست.'); redirect(url('admin_tips')); }
if (!repo_delete_tip($id)) { flash('error','حذف نکته ناموفق بود.'); redirect(url('admin_tips')); }
flash('success','نکته حذف شد.'); redirect(url('admin_tips'));
