<?php
/**
 * HAvoice — درباره‌ی های‌ویس، ارزش‌ها و سؤالات متداول.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$about = data('site')['about'] ?? [];
?>

<section class="section section--tight">
    <div class="container container--narrow">
        <p class="lead"><?= e($about['lead'] ?? '') ?></p>

        <div class="mission-card card">
            <h2>مأموریت</h2>
            <p><?= e($about['mission'] ?? '') ?></p>
        </div>

        <div class="grid grid--2 values">
<?php foreach ((array) ($about['values'] ?? []) as $value): ?>
            <article class="card value-card reveal">
                <h3><?= e($value['title']) ?></h3>
                <p><?= e($value['text']) ?></p>
            </article>
<?php endforeach; ?>
        </div>

        <div class="two-col">
            <section class="card">
                <h2><?= e($about['who']['title'] ?? 'مخاطب ما') ?></h2>
                <ul class="rich-list">
<?php foreach ((array) ($about['who']['items'] ?? []) as $item): ?>
                    <li><span class="tick" aria-hidden="true">✓</span><span><?= e($item) ?></span></li>
<?php endforeach; ?>
                </ul>
            </section>
            <section class="card">
                <h2><?= e($about['not_for']['title'] ?? 'خارج از حوزه‌ی ما') ?></h2>
                <ul class="rich-list">
<?php foreach ((array) ($about['not_for']['items'] ?? []) as $item): ?>
                    <li><span class="cross" aria-hidden="true">✕</span><span><?= e($item) ?></span></li>
<?php endforeach; ?>
                </ul>
            </section>
        </div>

        <section class="numbers">
            <div class="numbers__item"><strong><?= fa_num(count(course_lesson_index())) ?></strong><span>درس رایگان در مسیر آموزشی</span></div>
            <div class="numbers__item"><strong><?= fa_num(count(data('articles'))) ?></strong><span>مقاله‌ی کاربردی</span></div>
            <div class="numbers__item"><strong><?= fa_num(count(exercises())) ?></strong><span>تمرین زمان‌دار</span></div>
            <div class="numbers__item"><strong><?= fa_num(count(tips())) ?></strong><span>نکته‌ی کوتاه</span></div>
        </section>

        <?php if (!empty($about['faq'])): ?>
        <section class="faq">
            <h2>سؤالات متداول</h2>
            <div class="faq__list">
<?php foreach ((array) $about['faq'] as $i => $item): ?>
                <details class="faq__item"<?= $i === 0 ? ' open' : '' ?>>
                    <summary><?= e($item['q']) ?></summary>
                    <p><?= e($item['a']) ?></p>
                </details>
<?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <div class="cta-band cta-band--inline">
            <div class="cta-band__text">
                <h2>حالا نوبت شماست</h2>
                <p>مسیر آموزشی را از درس اول شروع کنید؛ بیست دقیقه‌ی امروز، تفاوت جلسه‌ی بعدی شماست.</p>
            </div>
            <div class="cta-band__actions">
                <a class="btn btn--primary" href="<?= e(url('course')) ?>">شروع مسیر آموزشی</a>
                <a class="btn btn--ghost" href="<?= e(url('contact')) ?>">تماس با ما</a>
            </div>
        </div>
    </div>
</section>
