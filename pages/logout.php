<?php
/**
 * HAvoice — صفحه‌ی تأیید خروج.
 * خروجِ واقعی با POST و توکنِ CSRF در handlers/logout.php انجام می‌شود؛
 * این صفحه فقط برای ورودِ مستقیم (GET) یک کارتِ تأیید نشان می‌دهد.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$user = auth_current_user();
?>
<section class="section section--tight auth-section">
    <div class="container">
        <div class="auth-card">
            <div class="auth-card__brand">
                <span class="auth-card__mark" aria-hidden="true"><?= ha_icon('user', 20) ?></span>
                <p class="eyebrow">حساب کاربری</p>
                <h1>خروج از حساب</h1>
                <?php if ($user !== null): ?>
                    <p class="auth-card__lead"><?= e($user['name']) ?>، آیا مطمئن هستید که می‌خواهید از حساب خود خارج شوید؟</p>
                    <form method="post" action="<?= e(url('logout')) ?>" class="btn-row btn-row--center">
                        <?= csrf_field() ?>
                        <button class="btn btn--primary" type="submit">خروج از حساب</button>
                        <a class="btn btn--ghost" href="<?= e(url('account')) ?>">بازگشت به حساب</a>
                    </form>
                <?php else: ?>
                    <p class="auth-card__lead">شما وارد حساب نشده‌اید.</p>
                    <div class="btn-row btn-row--center">
                        <a class="btn btn--primary" href="<?= e(url('login')) ?>">ورود</a>
                        <a class="btn btn--ghost" href="<?= e(url('home')) ?>">صفحه‌ی اصلی</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
