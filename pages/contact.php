<?php
/**
 * HAvoice — صفحه‌ی تماس با ما (فرم + راه‌های ارتباطی).
 * پردازش ارسال در includes/handlers/contact.php انجام می‌شود.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$contact = data('site')['contact'] ?? [];
$subjects = (array) ($contact['subjects'] ?? []);
$channels = (array) ($contact['channels'] ?? []);
$flash    = flash();
$errors   = isset($_SESSION['ha_errors']) && is_array($_SESSION['ha_errors']) ? $_SESSION['ha_errors'] : [];

if ($errors !== []) {
    unset($_SESSION['ha_errors']);
}

$action = HA_PRETTY_URLS ? (rtrim(HA_BASE_PATH, '/') . '/contact') : (HA_BASE_PATH . '/index.php?p=contact');
?>

<section class="section section--tight contact-top">
    <div class="container">
        <div class="contact-hero">
            <div class="contact-hero__text">
                <p class="eyebrow">پشتیبانی و همکاری</p>
                <h1 class="contact-hero__title">با های‌ویس در تماس باشید</h1>
                <p class="lead"><?= e($contact['lead'] ?? '') ?></p>
            </div>
            <ul class="channel-list">
<?php foreach ($channels as $ch): ?>
                <li class="channel">
                    <span class="channel__title"><?= e($ch['title']) ?></span>
                    <?php if (($ch['type'] ?? '') === 'mail'): ?>
                        <a class="channel__value" href="mailto:<?= e(HA_EMAIL) ?>"><?= e($ch['value']) ?></a>
                    <?php elseif (($ch['type'] ?? '') === 'tel'): ?>
                        <a class="channel__value" href="tel:<?= e(preg_replace('/[^0-9+]/', '', HA_HOTLINE)) ?>"><?= e($ch['value']) ?></a>
                    <?php else: ?>
                        <a class="channel__value" href="<?= e($ch['url'] ?? '#') ?>" rel="noopener nofollow" target="_blank"><?= e($ch['value']) ?></a>
                    <?php endif; ?>
                    <span class="channel__note"><?= e($ch['note'] ?? '') ?></span>
                </li>
<?php endforeach; ?>
            </ul>
        </div>

<?php if ($flash !== [] && !empty($flash['message'])): ?>
        <div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="status">
            <span class="alert__icon" aria-hidden="true"><?= $flash['type'] === 'success' ? '✓' : '!' ?></span>
            <p><?= e($flash['message']) ?></p>
        </div>
<?php endif; ?>

        <div class="contact-grid">
            <form class="card contact-form" method="post" action="<?= e($action) ?>" novalidate data-contact-form>
                <h2>فرم تماس</h2>
                <p class="muted-sm">فیلدهای ستاره‌دار لازم‌اند. نشانی ایمیل شما فقط برای پاسخ‌گویی نگه داشته می‌شود.</p>

                <?= csrf_field() ?>
                <div class="honeypot" aria-hidden="true">
                    <label for="website">وب‌سایت</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="field">
                    <label for="f-name">نام و نام خانوادگی <span class="req">*</span></label>
                    <input class="input<?= field_error('name', $errors) ?>" type="text" id="f-name" name="name"
                           value="<?= e(old('name')) ?>" required maxlength="60" autocomplete="name"
                           aria-describedby="<?= isset($errors['name']) ? 'err-name' : '' ?>">
<?php if (isset($errors['name'])): ?>
                    <p class="field__error" id="err-name"><?= e($errors['name']) ?></p>
<?php endif; ?>
                </div>

                <div class="field">
                    <label for="f-email">ایمیل <span class="req">*</span></label>
                    <input class="input<?= field_error('email', $errors) ?>" type="email" id="f-email" name="email"
                           value="<?= e(old('email')) ?>" required maxlength="120" autocomplete="email" dir="ltr">
<?php if (isset($errors['email'])): ?>
                    <p class="field__error"><?= e($errors['email']) ?></p>
<?php endif; ?>
                </div>

                <div class="field">
                    <label for="f-subject">موضوع</label>
                    <select class="input<?= field_error('subject', $errors) ?>" id="f-subject" name="subject">
<?php foreach ($subjects as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= old('subject') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
                    </select>
<?php if (isset($errors['subject'])): ?>
                    <p class="field__error"><?= e($errors['subject']) ?></p>
<?php endif; ?>
                </div>

                <div class="field">
                    <label for="f-message">پیام شما <span class="req">*</span></label>
                    <textarea class="input<?= field_error('message', $errors) ?>" id="f-message" name="message"
                              rows="7" required maxlength="2000" data-counter><?= e(old('message')) ?></textarea>
                    <p class="field__help"><span data-counter-left>۲۰۰۰</span> نویسه باقی‌مانده</p>
<?php if (isset($errors['message'])): ?>
                    <p class="field__error"><?= e($errors['message']) ?></p>
<?php endif; ?>
                </div>

                <label class="check">
                    <input type="checkbox" name="consent" value="1" required>
                    <span>موافقم پیامم برای پاسخ‌گویی در این سایت نگه داشته شود.</span>
                </label>
<?php if (isset($errors['consent'])): ?>
                <p class="field__error"><?= e($errors['consent']) ?></p>
<?php endif; ?>

                <div class="btn-row">
                    <button class="btn btn--primary btn--lg" type="submit">ارسال پیام</button>
                    <span class="muted-sm">پاسخ‌گویی: شنبه تا چهارشنبه</span>
                </div>
            </form>

            <aside class="contact-side">
                <div class="card side-card">
                    <h2>قبل از نوشتن</h2>
                    <ul class="rich-list">
                        <li>سؤال‌های پرتکرار درباره‌ی دوره در صفحه‌ی <a href="<?= e(url('about')) ?>">درباره ما</a> پاسخ داده شده‌اند.</li>
                        <li>برای گزارش باگ، مرورگر و متن دقیق خطا را بنویسید.</li>
                        <li>برای پیشنهاد موضوع مقاله، یک مثال از کاربردش در کار روزمره بزنید.</li>
                    </ul>
                </div>
                <div class="card side-card">
                    <h2>تمرین به‌جای ایمیل</h2>
                    <p>اگر سؤال شما «چطور شروع کنم؟» است، پاسخ کوتاه این است: یک تمرین ده‌دقیقه‌ای، همین امروز.</p>
                    <a class="btn btn--ghost btn--sm btn--block" href="<?= e(url('exercises')) ?>">شروع تمرین‌ها</a>
                </div>
            </aside>
        </div>
    </div>
</section>
