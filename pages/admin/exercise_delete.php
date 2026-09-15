<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_exercises'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_exercises')); }
$id = slugify((string)($_POST['id'] ?? ''));
if ($id === '') { flash('error','شناسه تمرین مشخص نیست.'); redirect(url('admin_exercises')); }
if (!repo_delete_exercise($id)) { flash('error','حذف تمرین ناموفق بود.'); redirect(url('admin_exercises')); }
flash('success','تمرین حذف شد.'); redirect(url('admin_exercises'));
