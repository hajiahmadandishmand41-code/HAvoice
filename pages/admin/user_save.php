<?php
/**
 * HAvoice Admin — به‌روزرسانی کاربر (DB + JSON)
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_users'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_users')); }

$id = (string)($_POST['id'] ?? '');
if ($id === '' || !preg_match('/^[a-f0-9]{16,64}$/i', $id)) {
    flash('error','شناسه الزامی.');
    redirect(url('admin_users'));
}

$user = auth_find_user_by_id($id);
if ($user === null) {
    flash('error','کاربر پیدا نشد.');
    redirect(url('admin_users'));
}

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
$other = auth_find_user_by_email($email);
if ($other !== null && ($other['id'] ?? '') !== $id) {
    flash('error','این ایمیل قبلاً برای کاربر دیگری ثبت شده است.');
    redirect(url('admin_user_edit', ['slug' => $id]));
}

$role = (string)($_POST['role'] ?? 'user');
if (!in_array($role, ['user', 'admin'], true)) {
    flash('error','نقش کاربر معتبر نیست.');
    redirect(url('admin_user_edit', ['slug' => $id]));
}

/*
 * محافظِ آخرین مدیر: تنزلِ نقشِ «آخرین admin سایت» ممنوع است، وگرنه
 * سیستم بدونِ مدیرِ فعال باقی می‌ماند (بنفشِ strafing).
 * (حذفِ آخرین مدیر هم در user_delete.php مسدود می‌شود.)
 */
if ($role === 'user' && ($user['role'] ?? '') === 'admin' && auth_count_admins() <= 1) {
    flash('error','تنزلِ نقشِ تنها مدیرِ سایت مجاز نیست؛ ابتدا مدیرِ دیگری ارتقا بدهید.');
    redirect(url('admin_user_edit', ['slug' => $id]));
}

$pw = (string)($_POST['new_password'] ?? '');
if ($pw !== '' && (strlen($pw) < HA_AUTH_MIN_PASSWORD || strlen($pw) > 72)) {
    flash('error','رمز جدید باید بین ' . fa_num(HA_AUTH_MIN_PASSWORD) . ' تا ۷۲ نویسه باشد.');
    redirect(url('admin_user_edit', ['slug' => $id]));
}

/* DB path */
if (function_exists('db_ready') && db_ready()) {
    db_exec('UPDATE ha_users SET name = ?, email = ?, role = ?, updated_at = ? WHERE id = ?', [
        $name, $email, $role === 'admin' ? 'admin' : 'user', db_now(), $id,
    ]);
    if ($pw !== '') {
        db_user_update_password($id, $pw);
    }
}

/* JSON mirror */
$users = auth_load_users_json_only();
$found = false;
foreach ($users as $i => $u) {
    if (($u['id'] ?? '') !== $id) continue;
    $users[$i]['name'] = $name;
    $users[$i]['email'] = $email;
    $users[$i]['role'] = $role;
    if ($pw !== '') $users[$i]['pass_hash'] = password_hash($pw, PASSWORD_DEFAULT);
    $found = true;
    break;
}
if ($found) {
    auth_save_users($users);
} elseif (!db_ready()) {
    flash('error','ذخیره‌سازی کاربر ناموفق بود.');
    redirect(url('admin_users'));
}

auth_set_role($id, $role);
flash('success','کاربر به‌روز شد.');
redirect(url('admin_users'));
