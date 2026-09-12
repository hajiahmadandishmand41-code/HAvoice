<?php
/**
 * HAvoice 2.0 — تماس با ما
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$contact = data('site')['contact'] ?? [];
$subjects = (array)($contact['subjects'] ?? []);
$channels = (array)($contact['channels'] ?? []);
$flash    = flash();
$errors   = isset($_SESSION['ha_errors']) && is_array($_SESSION['ha_errors']) ? $_SESSION['ha_errors'] : [];
if ($errors!==[]) unset($_SESSION['ha_errors']);
/* دسترس‌پذیری: اگر فیلدی خطا دارد، هم aria-invalid و هم ارتباط با
   متنِ خطا (aria-describedby) لازم است تا صفحه‌خوان آن را اعلام کند. */
$errAttrs = static function (string $f) use ($errors): string {
    if (!isset($errors[$f])) return '';
    return ' aria-invalid="true" aria-describedby="err-' . $f . '"';
};
$errText = static function (string $f) use ($errors): string {
    if (!isset($errors[$f])) return '';
    return '<p class="field__error" id="err-' . $f . '" role="alert">' . e((string) $errors[$f]) . '</p>';
};
/* نشانیِ نسبی تا در نصبِ زیرپوشه‌ای هم درست بماند */
$action = url('contact');
?>

<section class="section section--tight contact-top">
    <div class="container">
        <div class="contact-hero">
            <div class="contact-hero__text">
                <p class="eyebrow">پشتیبانی و همکاری</p>
                <h1 class="contact-hero__title">با <?= e(HA_NAME) ?> در تماس باشید</h1>
                <p class="lead"><?= e($contact['lead'] ?? '') ?></p>
            </div>
            <ul class="channel-list">
                <?php foreach($channels as $ch): ?>
                    <li class="channel">
                        <span class="channel__title"><?= e($ch['title']) ?></span>
                        <?php if(($ch['type']??'')==='mail'): ?><a class="channel__value" href="mailto:<?= e(HA_EMAIL) ?>"><?= e($ch['value']) ?></a>
                        <?php elseif(($ch['type']??'')==='tel'): ?><a class="channel__value" href="tel:<?= e(preg_replace('/[^0-9+]/','',HA_HOTLINE)) ?>"><?= e($ch['value']) ?></a>
                        <?php else: ?><a class="channel__value" href="<?= e($ch['url']??'#') ?>" rel="noopener nofollow" target="_blank"><?= e($ch['value']) ?></a><?php endif; ?>
                        <span class="channel__note"><?= e($ch['note']??'') ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if($flash!==[] && !empty($flash['message'])): ?>
            <div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>" role="<?= $flash['type']==='success'?'status':'alert' ?>">
                <span class="alert__icon" aria-hidden="true"><?= ha_icon($flash['type']==='success'?'check':'alert', 13) ?></span>
                <p><?= e($flash['message']) ?></p>
            </div>
        <?php endif; ?>

        <div class="contact-grid">
            <form class="card contact-form" method="post" action="<?= e($action) ?>" novalidate data-contact-form>
                <h2>فرم تماس</h2>
                <p class="muted-sm">فیلدهای ستاره‌دار لازم‌اند. نشانی ایمیل فقط برای پاسخ‌گویی نگه داشته می‌شود.</p>
                <?= csrf_field() ?>
                <div class="honeypot" aria-hidden="true"><label for="website">وب‌سایت</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>

                <div class="field">
                    <label for="f-name">نام و نام خانوادگی <span class="req">*</span></label>
                    <input class="input<?= field_error('name',$errors) ?>" type="text" id="f-name" name="name" value="<?= e(old('name')) ?>" required maxlength="60" autocomplete="name"<?= $errAttrs('name') ?>>
                    <?= $errText('name') ?>
                </div>
                <div class="field">
                    <label for="f-email">ایمیل <span class="req">*</span></label>
                    <input class="input<?= field_error('email',$errors) ?>" type="email" id="f-email" name="email" value="<?= e(old('email')) ?>" required maxlength="120" autocomplete="email" dir="ltr"<?= $errAttrs('email') ?>>
                    <?= $errText('email') ?>
                </div>
                <div class="field">
                    <label for="f-subject">موضوع</label>
                    <select class="input<?= field_error('subject',$errors) ?>" id="f-subject" name="subject"<?= $errAttrs('subject') ?>>
                        <?php foreach($subjects as $value=>$label): ?><option value="<?= e($value) ?>"<?= old('subject')=== $value?' selected':'' ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                    <?= $errText('subject') ?>
                </div>
                <div class="field">
                    <label for="f-message">پیام شما <span class="req">*</span></label>
                    <textarea class="input<?= field_error('message',$errors) ?>" id="f-message" name="message" rows="7" required maxlength="2000" data-counter<?= $errAttrs('message') ?>><?= e(old('message')) ?></textarea>
                    <p class="field__help"><span data-counter-left>۲۰۰۰</span> نویسه باقی‌مانده</p>
                    <?= $errText('message') ?>
                </div>
                <label class="check"><input type="checkbox" id="f-consent" name="consent" value="1" required<?= $errAttrs('consent') ?>><span>موافقم پیامم برای پاسخ‌گویی نگه داشته شود.</span></label>
                <?= $errText('consent') ?>
                <div class="btn-row">
                    <button class="btn btn--primary btn--lg" type="submit">ارسال پیام</button>
                    <span class="muted-sm">پاسخ‌گویی: شنبه تا چهارشنبه</span>
                </div>
            </form>

            <aside class="contact-side">
                <div class="card side-card">
                    <h2>قبل از نوشتن</h2>
                    <ul class="rich-list">
                        <li>سؤال‌های پرتکرار در صفحه‌ی <a href="<?= e(url('about')) ?>">درباره مدرس</a> پاسخ داده شده.</li>
                        <li>برای گزارش باگ، مرورگر و متن دقیق خطا را بنویسید.</li>
                        <li>برای پیشنهاد موضوع، یک مثالِ کاربردِ روزمره بزنید.</li>
                    </ul>
                </div>
                <div class="card side-card">
                    <h2>تمرین به‌جای ایمیل</h2>
                    <p>اگر سؤال شما «چطور شروع کنم؟» است: یک تمرینِ ده‌دقیقه‌ای، همین امروز.</p>
                    <a class="btn btn--ghost btn--sm btn--block" href="<?= e(url('exercises')) ?>">شروع تمرین‌ها</a>
                </div>
                <div class="card side-card">
                    <h2>حوزه‌ها</h2>
                    <ul class="chip-row" style="margin:0">
                        <?php foreach(array_slice(categories(),0,6) as $cat): ?><li><a class="chip chip--ghost" href="<?= e(url('category',['slug'=>$cat['slug']])) ?>"><?= e($cat['short']) ?></a></li><?php endforeach; ?>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</section>
