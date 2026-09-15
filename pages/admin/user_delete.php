<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_users'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_users')); }

$id = (string)($_POST['id'] ?? '');
if ($id === '' || !preg_match('/^[a-f0-9]{16,64}$/i', $id)) {
    flash('error','شناسه کاربر مشخص نیست.');
    redirect(url('admin_users'));
}

$me = auth_current_user();
if ($me && ($me['id'] ?? '') === $id) {
    flash('error','نمی‌توانید حساب خودتان را حذف کنید.');
    redirect(url('admin_users'));
}

$target = auth_find_user_by_id($id);
if ($target === null) {
    flash('error','کاربر پیدا نشد.');
    redirect(url('admin_users'));
}

/* جلوگیری از حذف آخرین admin */
if (($target['role'] ?? '') === 'admin') {
    $admins = 0;
    foreach (auth_load_users() as $u) {
        if (($u['role'] ?? '') === 'admin') $admins++;
    }
    /* legacy first-user admin */
    $json = auth_load_users_json_only();
    if ($admins === 0 && $json !== [] && ($json[0]['id'] ?? '') === $id) {
        flash('error','نمی‌توان مدیر اصلی را حذف کرد.');
        redirect(url('admin_users'));
    }
    if ($admins <= 1 && ($target['role'] ?? '') === 'admin') {
        flash('error','حداقل یک مدیر باید باقی بماند.');
        redirect(url('admin_users'));
    }
}

if (function_exists('db_ready') && db_ready()) {
    db_user_delete($id);
}
$users = auth_load_users_json_only();
$users = array_values(array_filter($users, static fn($u) => ($u['id'] ?? '') !== $id));
auth_save_users($users);

flash('success','کاربر حذف شد.');
redirect(url('admin_users'));
