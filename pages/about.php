<?php
/**
 * HAvoice 2.0 — درباره حاجی احمد صالحی
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$about = data('site')['about'] ?? [];
$inst  = data('site')['instructor'] ?? [];
$cats  = categories();
?>

<section class="section section--tight">
    <div class="container container--narrow">
        <?= breadcrumbs([['label'=>'درباره مدرس']]) ?>

        <!-- معرفی مدرس -->
        <div class="instructor">
            <div class="instructor__media">
                <div class="instructor__avatar" aria-hidden="true">ح</div>
                <span class="instructor__badge"><?= e($inst['role'] ?? HA_TAGLINE) ?></span>
            </div>
            <div>
                <p class="instructor__role"><?= e($inst['role'] ?? '') ?></p>
                <h1 class="instructor__name"><?= e($inst['name'] ?? HA_NAME) ?></h1>
                <p class="instructor__bio"><?= e($inst['bio'] ?? '') ?></p>
                <ul class="instructor__points">
                    <?php foreach((array)($inst['points']??[]) as $pt): ?><li><span class="tick" aria-hidden="true"><?= ha_icon('check', 12) ?></span><span><?= e($pt) ?></span></li><?php endforeach; ?>
                </ul>
                <?php if(!empty($inst['note'])): ?><p class="instructor__note"><?= e($inst['note']) ?></p><?php endif; ?>
            </div>
        </div>

        <p class="lead"><?= e($about['lead'] ?? '') ?></p>

        <div class="mission-card card">
            <h2>مأموریت</h2>
            <p><?= e($about['mission'] ?? '') ?></p>
        </div>

        <div class="grid grid--2 values">
            <?php foreach((array)($about['values']??[]) as $value): ?>
                <article class="card value-card reveal">
                    <h3><?= e($value['title']) ?></h3><p><?= e($value['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <section class="sub-section">
            <h2>حوزه‌های آموزشی</h2>
            <p class="muted-sm">این مرکز ۱۲ حوزه را پوشش می‌دهد؛ ساختار به‌گونه‌ای است که افزودنِ حوزه‌ی جدید بدونِ بازنویسی ممکن است.</p>
            <div class="grid category-grid">
                <?php foreach($cats as $cat): ?><div><?= category_card($cat) ?></div><?php endforeach; ?>
            </div>
        </section>

        <div class="two-col">
            <section class="card">
                <h2><?= e($about['who']['title'] ?? 'مخاطب ما') ?></h2>
                <ul class="rich-list">
                    <?php foreach((array)($about['who']['items']??[]) as $item): ?><li><span class="tick" aria-hidden="true"><?= ha_icon('check', 12) ?></span><span><?= e($item) ?></span></li><?php endforeach; ?>
                </ul>
            </section>
            <section class="card">
                <h2><?= e($about['not_for']['title'] ?? 'خارج از حوزه‌ی ما') ?></h2>
                <ul class="rich-list">
                    <?php foreach((array)($about['not_for']['items']??[]) as $item): ?><li><span class="cross"><?= ha_icon('close', 12) ?></span><span><?= e($item) ?></span></li><?php endforeach; ?>
                </ul>
            </section>
        </div>

        <section class="numbers">
            <div class="numbers__item"><strong><?= fa_num(count(courses())) ?></strong><span>دوره‌ی مرحله‌ای</span></div>
            <div class="numbers__item"><strong><?= fa_num(count(course_lesson_index())) ?></strong><span>درس تمرین‌محور</span></div>
            <div class="numbers__item"><strong><?= fa_num(count(data('articles'))) ?></strong><span>مقاله‌ی کاربردی</span></div>
            <div class="numbers__item"><strong><?= fa_num(count(videos())+count(audios())) ?></strong><span>ویدیو و صوت</span></div>
            <div class="numbers__item"><strong><?= fa_num(count(books())) ?></strong><span>کتاب و خلاصه</span></div>
            <div class="numbers__item"><strong><?= fa_num(count(research_items())) ?></strong><span>یادداشت پژوهشی</span></div>
        </section>

        <?php if(!empty($about['faq'])): ?>
        <section class="faq">
            <h2>سؤالات متداول</h2>
            <div class="faq__list">
                <?php foreach((array)$about['faq'] as $i=>$item): ?>
                    <details class="faq__item"<?= $i===0?' open':'' ?>>
                        <summary><?= e($item['q']) ?></summary><p><?= e($item['a']) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <div class="cta-band cta-band--inline">
            <div class="cta-band__text">
                <h2>حالا نوبت شماست</h2>
                <p>مسیر یادگیری را از یک دوره شروع کنید؛ بیست دقیقه‌ی امروز، تفاوتِ جلسه‌ی بعدی شماست.</p>
            </div>
            <div class="cta-band__actions">
                <a class="btn btn--primary" href="<?= e(url('courses')) ?>">دیدن دوره‌ها</a>
                <a class="btn btn--ghost" href="<?= e(url('contact')) ?>">تماس با ما</a>
            </div>
        </div>
    </div>
</section>
