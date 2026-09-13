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
