<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_users'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_users')); }
$id = (string)($_POST['id'] ?? '');
if ($id === '') { flash('error','شناسه الزامی.'); redirect(url('admin_users')); }
$users = auth_load_users();
foreach ($users as $i => $u) {
    if (($u['id'] ?? '') === $id) {
        $name = trim((string)($_POST['name'] ?? '')); if ($name !== '') $users[$i]['name'] = $name;
        $email = auth_normalize_email((string)($_POST['email'] ?? '')); if ($email !== '') $users[$i]['email'] = $email;
        $role = (string)($_POST['role'] ?? 'user'); if (in_array($role, ['user','admin'])) $users[$i]['role'] = $role;
        $pw = (string)($_POST['new_password'] ?? '');
        if (strlen($pw) >= HA_AUTH_MIN_PASSWORD) $users[$i]['pass_hash'] = password_hash($pw, PASSWORD_DEFAULT);
        break;
    }
}
auth_save_users($users); flash('success','کاربر به‌روز شد.'); redirect(url('admin_users'));
