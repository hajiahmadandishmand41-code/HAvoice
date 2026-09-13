<?php
/**
 * HAvoice — حساب کاربری (محافظت‌شده)
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

auth_require();

$user  = auth_current_user();
$flash = flash();

if ($user === null) {
    redirect(url('login'));
}

$created = (string) ($user['created_at'] ?? '');
$createdFa = '';
$ts = strtotime($created);
if ($ts !== false) {
    $createdFa = fa_num(date('Y/m/d', $ts));
}

/* شمارنده‌های واقعی برای نمایش در داشبورد */
$totalLessons = count(course_lesson_index());
$totalArticles = count(data('articles'));
$totalExercises = count(exercises());
?>
<section class="section section--tight">
    <div class="container">
        <?php if ($flash !== [] && !empty($flash['message'])): ?>
            <div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>">
                <span class="alert__icon" aria-hidden="true"><?= ha_icon($flash['type'] === 'success' ? 'check' : 'alert', 13) ?></span>
                <p><?= e($flash['message']) ?></p>
            </div>
        <?php endif; ?>

        <div class="account-grid">
            <aside class="card account-profile">
                <div class="account-profile__avatar" aria-hidden="true"><?= e(auth_initial((string) $user['name'])) ?></div>
                <h1 class="account-profile__name"><?= e($user['name']) ?></h1>
                <p class="account-profile__email" dir="ltr"><?= e($user['email']) ?></p>
                <?php if ($createdFa !== ''): ?><p class="muted-sm">عضو از <?= e($createdFa) ?></p><?php endif; ?>

                <form method="post" action="<?= e(url('logout')) ?>" class="account-profile__logout">
                    <?= csrf_field() ?>
                    <button class="btn btn--ghost btn--block" type="submit"><?= ha_icon('external', 16) ?> خروج از حساب</button>
                </form>
            </aside>

            <div class="account-main">
                <div class="card account-hello">
                    <p class="eyebrow">سلام، <?= e($user['name']) ?></p>
                    <h2>به داشبورد HAvoice خوش آمدید</h2>
                    <p class="muted-sm">پیشرفتِ درس‌ها و تمرین‌های شما در مرورگر همین دستگاه ذخیره می‌شود. برای ادامه‌ی مسیر، از بخش‌های زیر شروع کنید.</p>
                    <div class="btn-row">
                        <a class="btn btn--primary" href="<?= e(url('courses')) ?>">ادامه‌ی یادگیری</a>
                        <a class="btn btn--ghost" href="<?= e(url('exercises')) ?>">تمرین امروز</a>
                    </div>
                </div>

                <div class="numbers">
                    <div class="numbers__item"><strong><?= fa_num($totalLessons) ?></strong><span>درس تمرین‌محور</span></div>
                    <div class="numbers__item"><strong><?= fa_num($totalArticles) ?></strong><span>مقاله</span></div>
                    <div class="numbers__item"><strong><?= fa_num($totalExercises) ?></strong><span>تمرین عملی</span></div>
                </div>

                <div class="grid grid--2" style="margin-top:1.4rem">
                    <div class="card side-card">
                        <h2>شروعِ مسیر فن بیان</h2>
                        <p>از نفس و صدا شروع کنید؛ مسیرِ ۹ درسِ تمرین‌محور با پیشرفتِ قابلِ اندازه‌گیری.</p>
                        <a class="btn btn--ghost btn--sm" href="<?= e(url('course', ['slug' => 'public-speaking-fundamentals'])) ?>">مشاهده‌ی دوره</a>
                    </div>
                    <div class="card side-card">
                        <h2>نکته‌های کوتاه</h2>
                        <p>هر روز یک نکته‌ی یک‌دقیقه‌ای برای استفاده‌ی فوری در جلسه و گفت‌وگو.</p>
                        <a class="btn btn--ghost btn--sm" href="<?= e(url('tips')) ?>">دیدن نکته‌ها</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
