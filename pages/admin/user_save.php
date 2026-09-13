<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_users'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_users')); }
$id = (string)($_POST['id'] ?? '');
if ($id === '') { flash('error','شناسه الزامی.'); redirect(url('admin_users')); }
$users = auth_load_users();
$found = false;
foreach ($users as $i => $u) {
    if (($u['id'] ?? '') !== $id) continue;
    $found = true;

    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '' || mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 60) {
        flash('error','نام باید بین ۲ تا ۶۰ نویسه باشد.');
        redirect(url('admin_user_edit', ['slug' => $id]));
    }

    $email = auth_normalize_email((string)($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
        flash('error','ایمیل معتبر نیست.');
        redirect(url('admin_user_edit', ['slug' => $id]));
    }
    foreach ($users as $j => $other) {
        if ($j !== $i && auth_normalize_email((string)($other['email'] ?? '')) === $email) {
            flash('error','این ایمیل قبلاً برای کاربر دیگری ثبت شده است.');
            redirect(url('admin_user_edit', ['slug' => $id]));
        }
    }

    $role = (string)($_POST['role'] ?? 'user');
    if (!in_array($role, ['user', 'admin'], true)) {
        flash('error','نقش کاربر معتبر نیست.');
        redirect(url('admin_user_edit', ['slug' => $id]));
    }

    $pw = (string)($_POST['new_password'] ?? '');
    if ($pw !== '' && (strlen($pw) < HA_AUTH_MIN_PASSWORD || strlen($pw) > 72)) {
        flash('error','رمز جدید باید بین ' . fa_num(HA_AUTH_MIN_PASSWORD) . ' تا ۷۲ نویسه باشد.');
        redirect(url('admin_user_edit', ['slug' => $id]));
    }

    $users[$i]['name'] = $name;
    $users[$i]['email'] = $email;
    $users[$i]['role'] = $role;
    if ($pw !== '') $users[$i]['pass_hash'] = password_hash($pw, PASSWORD_DEFAULT);
    break;
}
if (!$found) { flash('error','کاربر پیدا نشد.'); redirect(url('admin_users')); }
if (!auth_save_users($users)) { flash('error','ذخیره‌سازی کاربر ناموفق بود؛ storage قابل نوشتن نیست.'); redirect(url('admin_users')); }
flash('success','کاربر به‌روز شد.'); redirect(url('admin_users')); 
