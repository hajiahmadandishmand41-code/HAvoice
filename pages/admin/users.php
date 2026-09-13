<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$users = auth_load_users();
?>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>نام</th><th>ایمیل</th><th>نقش</th><th>تاریخ عضویت</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($users as $u): ?>
<tr><td><?= e($u['name'] ?? '') ?></td><td dir="ltr"><?= e($u['email'] ?? '') ?></td>
<td><span class="admin-badge <?= ($u['role'] ?? '') === 'admin' || $u === ($users[0] ?? null) ? 'admin-badge--success' : 'admin-badge--info' ?>"><?= ($u['role'] ?? '') === 'admin' || $u === ($users[0] ?? null) ? 'مدیر' : 'کاربر' ?></span></td>
<td class="muted-sm"><?= e($u['created_at'] ?? '') ?></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_user_edit', ['slug' => $u['id'] ?? ''])) ?>">ویرایش</a></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/user_edit.php << 'EOF'
<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$id = param('slug'); $user = null;
if ($id !== '') { foreach (auth_load_users() as $u) { if (($u['id'] ?? '') === $id) { $user = $u; break; } } }
if (!$user) { flash('error','کاربر پیدا نشد.'); redirect(url('admin_users')); }
$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<form class="admin-form" method="post" action="<?= e(url('admin_user_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="id" value="<?= e($user['id'] ?? '') ?>">
<div class="field"><label>نام</label><input class="input" type="text" name="name" value="<?= e($user['name'] ?? '') ?>"></div>
<div class="field"><label>ایمیل</label><input class="input" type="email" name="email" value="<?= e($user['email'] ?? '') ?>" dir="ltr"></div>
<div class="field"><label>نقش</label><select class="input" name="role"><option value="user" <?= ($user['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>کاربر</option><option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>مدیر</option></select></div>
<div class="field"><label>رمز عبور جدید (خالی = بدون تغییر)</label><input class="input" type="password" name="new_password" dir="ltr"></div>
<div class="admin-form-actions"><button class="btn btn--primary" type="submit">ذخیره</button><a class="btn btn--ghost" href="<?= e(url('admin_users')) ?>">بازگشت</a></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/user_save.php << 'EOF'
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
