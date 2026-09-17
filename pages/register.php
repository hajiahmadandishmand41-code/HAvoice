<?php
/**
 * HAvoice — ثبت‌نام
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$next = ha_safe_next(param('next'));

if (auth_is_logged_in()) {
    redirect($next !== '' ? $next : url('account'));
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
                <span class="auth-card__mark" aria-hidden="true"><?= ha_icon('sparkle', 20) ?></span>
                <p class="eyebrow">شروع رایگان — کمتر از ۳۰ ثانیه</p>
                <h1>ثبت‌نام در HAvoice</h1>
                <p class="auth-card__lead">برای تعامل با محتوا (واکنش، نظر، پسند) ثبت‌نام کنید. رایگان است و پس از ثبت‌نام به همان صفحه‌ای که بودید برمی‌گردید.</p>
            </div>

            <?php if ($flash !== [] && !empty($flash['message'])): ?>
                <div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>">
                    <span class="alert__icon" aria-hidden="true"><?= ha_icon($flash['type'] === 'success' ? 'check' : 'alert', 13) ?></span>
                    <p><?= e($flash['message']) ?></p>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="post" action="<?= e(url('register')) ?>" novalidate>
                <?= csrf_field() ?>
                <?php if ($next !== ''): ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php endif; ?>
                <div class="honeypot" aria-hidden="true"><label for="website">وب‌سایت</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>

                <div class="field">
                    <label for="f-name">نام و نام خانوادگی <span class="req">*</span></label>
                    <input class="input<?= field_error('name', $errors) ?>" type="text" id="f-name" name="name" value="<?= e(old('name')) ?>" required maxlength="60" autocomplete="name" autofocus<?= $errAttrs('name') ?>>
                    <?= $errText('name') ?>
                </div>

                <div class="field">
                    <label for="f-email">ایمیل <span class="req">*</span></label>
                    <input class="input<?= field_error('email', $errors) ?>" type="email" id="f-email" name="email" value="<?= e(old('email')) ?>" required maxlength="120" autocomplete="email" dir="ltr"<?= $errAttrs('email') ?>>
                    <?= $errText('email') ?>
                </div>

                <div class="field">
                    <label for="f-password">رمز عبور <span class="req">*</span></label>
                    <input class="input<?= field_error('password', $errors) ?>" type="password" id="f-password" name="password" required minlength="<?= (int) HA_AUTH_MIN_PASSWORD ?>" maxlength="72" autocomplete="new-password"<?= $errAttrs('password') ?>>
                    <p class="field__help">حداقل <?= fa_num(HA_AUTH_MIN_PASSWORD) ?> نویسه؛ از ترکیبِ حروف و عدد استفاده کنید.</p>
                    <?= $errText('password') ?>
                </div>

                <div class="field">
                    <label for="f-confirm">تکرار رمز عبور <span class="req">*</span></label>
                    <input class="input<?= field_error('confirm', $errors) ?>" type="password" id="f-confirm" name="confirm" required maxlength="72" autocomplete="new-password"<?= $errAttrs('confirm') ?>>
                    <?= $errText('confirm') ?>
                </div>

                <button class="btn btn--primary btn--lg btn--block" type="submit">ساخت حساب</button>
                <p class="auth-form__note">با ثبت‌نام، ایمیل شما فقط برای ورود و اطلاع‌رسانیِ خودِ سایت نگه داشته می‌شود.</p>
            </form>

            <p class="auth-card__alt">قبلاً حساب ساخته‌اید؟ <a href="<?= e(url('login', $next !== '' ? ['next' => $next] : [])) ?>">ورود — فقط برای دارندگان حساب</a></p>
        </div>
    </div>
</section>
