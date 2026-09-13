<?php
/**
 * HAvoice — ورود به حساب
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

if (auth_is_logged_in()) {
    redirect(url('account'));
}

$flash  = flash();
$errors = isset($_SESSION['ha_errors']) && is_array($_SESSION['ha_errors']) ? $_SESSION['ha_errors'] : [];
if ($errors !== []) {
    unset($_SESSION['ha_errors']);
}
$errAttrs = static function (string $f) use ($errors): string {
    return isset($errors[$f]) ? ' aria-invalid="true" aria-describedby="err-' . $f . '"' : '';
};
$errText = static function (string $f) use ($errors): string {
    return isset($errors[$f])
        ? '<p class="field__error" id="err-' . $f . '" role="alert">' . e((string) $errors[$f]) . '</p>'
        : '';
};
?>
<section class="section section--tight auth-section">
    <div class="container">
        <div class="auth-card">
            <div class="auth-card__brand">
                <span class="auth-card__mark" aria-hidden="true"><?= ha_icon('user', 20) ?></span>
                <p class="eyebrow">حساب کاربری</p>
                <h1>ورود به HAvoice</h1>
                <p class="auth-card__lead">برای پیگیریِ مسیر یادگیری و همگام‌سازیِ پیشرفت وارد شوید.</p>
            </div>

            <?php if ($flash !== [] && !empty($flash['message'])): ?>
                <div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>">
                    <span class="alert__icon" aria-hidden="true"><?= ha_icon($flash['type'] === 'success' ? 'check' : 'alert', 13) ?></span>
                    <p><?= e($flash['message']) ?></p>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="post" action="<?= e(url('login')) ?>" novalidate>
                <?= csrf_field() ?>
                <div class="honeypot" aria-hidden="true"><label for="website">وب‌سایت</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>

                <div class="field">
                    <label for="f-email">ایمیل <span class="req">*</span></label>
                    <input class="input<?= field_error('email', $errors) ?>" type="email" id="f-email" name="email" value="<?= e(old('email')) ?>" required maxlength="120" autocomplete="email" dir="ltr" autofocus<?= $errAttrs('email') ?>>
                    <?= $errText('email') ?>
                </div>

                <div class="field">
                    <label for="f-password">رمز عبور <span class="req">*</span></label>
                    <input class="input<?= field_error('password', $errors) ?>" type="password" id="f-password" name="password" required maxlength="72" autocomplete="current-password"<?= $errAttrs('password') ?>>
                    <?= $errText('password') ?>
                </div>

                <button class="btn btn--primary btn--lg btn--block" type="submit"><?= ha_icon('arrow-left', 16) ?> ورود</button>
            </form>

            <p class="auth-card__alt">هنوز حساب ندارید؟ <a href="<?= e(url('register')) ?>">ثبت‌نام کنید</a></p>
        </div>
    </div>
</section>
