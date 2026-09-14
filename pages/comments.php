<?php
/**
 * HAvoice — نظرات عمومی
 *
 * کاربران نظر می‌نویسند؛ نظر پس از تأییدِ مدیر نمایش داده می‌شود.
 * ذخیره‌سازی: MySQL (includes/comments.php) — سازگار با InfinityFree.
 * امنیت: CSRF + honeypot + محدودیتِ نرخ + prepared statements و
 * نمایش همیشه با e() (escaping).
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$copy    = (array) (ha_site()['comments'] ?? []);
$flash   = flash();
$errors  = isset($_SESSION['ha_errors']) && is_array($_SESSION['ha_errors']) ? $_SESSION['ha_errors'] : [];
if ($errors !== []) { unset($_SESSION['ha_errors']); }

$errAttrs = static function (string $f) use ($errors): string {
    if (!isset($errors[$f])) { return ''; }
    return ' aria-invalid="true" aria-describedby="err-' . $f . '"';
};
$errText = static function (string $f) use ($errors): string {
    if (!isset($errors[$f])) { return ''; }
    return '<p class="field__error" id="err-' . $f . '" role="alert">' . e((string) $errors[$f]) . '</p>';
};

/* دیتابیس و صفحه‌بندی */
$dbReady     = comments_db() !== null;
$perPage     = (int) HA_COMMENTS_PER_PAGE;
$page        = max(1, (int) ($_GET['page'] ?? 1));
$totalShown  = comments_count_approved();
$totalPages  = max(1, (int) ceil($totalShown / $perPage));
$page        = min($page, $totalPages);
$offset      = ($page - 1) * $perPage;
$comments    = comments_approved($perPage, $offset);

$action = url('comments');
?>

<section class="section section--tight comments-top">
    <div class="container">
        <?= breadcrumbs([['label' => 'نظرات عمومی']]) ?>

        <div class="comments-hero">
            <div>
                <p class="eyebrow"><?= ha_icon('chat', 14) ?> بازخوردِ واقعی، بدونِ فیلترِ قالبی</p>
                <h1 class="comments-hero__title"><?= e($copy['title'] ?? 'نظرات شما درباره‌ی HAvoice') ?></h1>
                <p class="lead"><?= e($copy['lead'] ?? 'تجربه‌ی خود از درس‌ها، تمرین‌ها و محتوای سایت را بنویسید؛ نظرِ شما بعد از بازبینیِ کوتاه، همین‌جا برای دیگران نمایش داده می‌شود.') ?></p>
            </div>
            <?php if ($dbReady && $totalShown > 0): ?>
                <div class="comments-hero__stat" role="status">
                    <strong><?= fa_num($totalShown) ?></strong>
                    <span>نظرِ تأییدشده</span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($flash !== [] && !empty($flash['message'])): ?>
            <div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>">
                <span class="alert__icon" aria-hidden="true"><?= ha_icon($flash['type'] === 'success' ? 'check' : 'alert', 13) ?></span>
                <p><?= e($flash['message']) ?></p>
            </div>
        <?php endif; ?>

        <div class="comments-grid">
            <?php if ($dbReady): ?>
            <form class="card comments-form" method="post" action="<?= e($action) ?>" novalidate>
                <h2>ثبت نظر</h2>
                <p class="muted-sm">فیلدهای ستاره‌دار لازم‌اند. ایمیل اختیاری است و فقط برای تماسِ احتمالیِ مدیر نگه داشته می‌شود؛ هرگز نمایش داده نمی‌شود.</p>
                <?= csrf_field() ?>
                <div class="honeypot" aria-hidden="true"><label for="c-website">وب‌سایت</label><input type="text" id="c-website" name="website" tabindex="-1" autocomplete="off"></div>

                <div class="field">
                    <label for="c-name">نام شما <span class="req">*</span></label>
                    <input class="input<?= field_error('name', $errors) ?>" type="text" id="c-name" name="name" value="<?= e(old('name')) ?>" required maxlength="60" autocomplete="name"<?= $errAttrs('name') ?>>
                    <?= $errText('name') ?>
                </div>
                <div class="field">
                    <label for="c-email">ایمیل (اختیاری)</label>
                    <input class="input<?= field_error('email', $errors) ?>" type="email" id="c-email" name="email" value="<?= e(old('email')) ?>" maxlength="120" autocomplete="email" dir="ltr"<?= $errAttrs('email') ?>>
                    <?= $errText('email') ?>
                </div>
                <div class="field">
                    <label for="c-message">متن نظر <span class="req">*</span></label>
                    <textarea class="input<?= field_error('message', $errors) ?>" id="c-message" name="message" rows="6" required maxlength="1500" data-counter<?= $errAttrs('message') ?>><?= e(old('message')) ?></textarea>
                    <p class="field__help"><span data-counter-left>۱۵۰۰</span> نویسه باقی‌مانده</p>
                    <?= $errText('message') ?>
                </div>
                <label class="check"><input type="checkbox" id="c-consent" name="consent" value="1" required<?= $errAttrs('consent') ?>><span>موافقم نام و متنِ نظرم به‌صورتِ عمومی در همین صفحه نمایش داده شود.</span></label>
                <?= $errText('consent') ?>
                <div class="btn-row">
                    <button class="btn btn--primary btn--lg" type="submit"><?= ha_icon('send', 15) ?> ثبت نظر</button>
                    <span class="muted-sm">پس از تأییدِ مدیر نمایش داده می‌شود.</span>
                </div>
            </form>
            <?php else: ?>
            <div class="card comments-form comments-form--off" role="note">
                <h2>ثبت نظر</h2>
                <div class="info-box">
                    <span class="info-box__icon" aria-hidden="true"><?= ha_icon('info', 18) ?></span>
                    <div>
                        <strong>ثبتِ نظر موقتاً غیرفعال است.</strong>
                        <p class="muted-sm">اتصالِ دیتابیسِ نظرات هنوز پیکربندی نشده است. لطفاً بعداً سر بزنید یا از <a href="<?= e(url('contact')) ?>">صفحه‌ی تماس</a> پیام بگذارید.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <aside class="comments-side">
                <div class="card side-card">
                    <h2>قواعدِ کوتاه</h2>
                    <ul class="rich-list">
                        <li>احترام به مخاطب؛ بدونِ توهین و بی‌احترامی.</li>
                        <li>تجربه‌ی واقعی از محتوای سایت — نه تبلیغ و پیوندِ تبلیغاتی.</li>
                        <li>نظرها بعد از بازبینیِ مدیر (معمولاً ۱ تا ۲ روز کاری) منتشر می‌شوند.</li>
                        <li>برای پرسش‌های خصوصی از <a href="<?= e(url('contact')) ?>">تماس با ما</a> استفاده کنید.</li>
                    </ul>
                </div>
                <div class="card side-card">
                    <h2>از کجا شروع کنیم؟</h2>
                    <p>اگر تازه آمده‌اید، مسیرِ مرحله‌ای دوره‌ها بهترین نقطه‌ی شروع است.</p>
                    <a class="btn btn--ghost btn--sm btn--block" href="<?= e(url('courses')) ?>"><?= ha_icon('steps', 14) ?> دیدنِ دوره‌ها</a>
                </div>
            </aside>
        </div>

        <?php if ($dbReady): ?>
        <section class="comments-list-wrap" aria-labelledby="comments-list-title">
            <h2 id="comments-list-title" class="h3"><?= ha_icon('chat', 16) ?> نظرهای منتشرشده <?= $totalShown > 0 ? '<span class="muted-sm">(' . fa_num($totalShown) . ')</span>' : '' ?></h2>

            <?php if ($comments === []): ?>
                <?= empty_state(
                    'هنوز نظری منتشر نشده',
                    'اولین نفر باشید — تجربه‌ی خود را از درس‌ها و تمرین‌ها بنویسید.',
                    url('courses'),
                    'دیدنِ دوره‌ها',
                    'chat'
                ) ?>
            <?php else: ?>
                <ul class="comments-list">
                    <?php foreach ($comments as $c): ?>
                        <li class="comment-card reveal">
                            <div class="comment-card__head">
                                <span class="comment-card__avatar" aria-hidden="true"><?= e(mb_substr((string) ($c['name'] ?? ''), 0, 1, 'UTF-8')) ?></span>
                                <div class="comment-card__who">
                                    <strong class="comment-card__name"><?= e($c['name'] ?? '') ?></strong>
                                    <?php
                                        $dt = (string) ($c['created_at'] ?? '');
                                        $dateFa = $dt !== '' ? fa_num((new DateTimeImmutable($dt))->format('Y/m/d')) : '';
                                    ?>
                                    <?php if ($dateFa !== ''): ?>
                                        <time class="comment-card__date" datetime="<?= e($dt) ?>"><?= ha_icon('calendar', 12) ?> <?= e($dateFa) ?></time>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="comment-card__body"><?= nl2br(e($c['body'] ?? '')) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($totalPages > 1): ?>
                    <nav class="pager pager--nums" aria-label="صفحه‌بندیِ نظرات">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="pager__current" aria-current="page"><?= fa_num($i) ?></span>
                            <?php else: ?>
                                <a href="<?= e(url('comments', ['page' => $i])) ?>"><?= fa_num($i) ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </div>
</section>
