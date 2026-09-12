<?php
/**
 * HAvoice — صفحه‌ی اصلی
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$site       = data('site');
$hero       = (array) ($site['hero'] ?? []);
$stages     = course_stages();
$latest     = latest_articles(3);
$firstEx    = array_slice(exercises(), 0, 3);
$firstTips  = array_slice(tips(), 0, 3);
$practice   = exercises()[0] ?? null;
$practiceSeconds = (int) ($practice['seconds'] ?? 180);
?>

<!-- ============  Hero  ============ -->
<section class="hero">
    <div class="container hero__grid">
        <div class="hero__content">
            <p class="eyebrow"><?= e($hero['eyebrow'] ?? '') ?></p>
            <h1 class="hero__title"><?= e($hero['title'] ?? '') ?></h1>
            <p class="hero__lead"><?= e($hero['lead'] ?? '') ?></p>

            <div class="btn-row">
                <?php if (!empty($hero['primary'])): ?>
                    <a class="btn btn--primary btn--lg" href="<?= e(url((string) $hero['primary']['route'], isset($hero['primary']['slug']) ? ['slug' => $hero['primary']['slug']] : [])) ?>">
                        <?= e($hero['primary']['label']) ?>
                    </a>
                <?php endif; ?>
                <?php if (!empty($hero['ghost'])): ?>
                    <a class="btn btn--ghost btn--lg" href="<?= e(url((string) $hero['ghost']['route'])) ?>">
                        <?= e($hero['ghost']['label']) ?>
                    </a>
                <?php endif; ?>
            </div>

            <ul class="hero__stats">
<?php foreach ([
                ['value' => fa_num(count(course_lesson_index())), 'label' => 'درس مرحله‌ای'],
                ['value' => fa_num(count(data('articles'))),      'label' => 'مقاله‌ی آموزشی'],
                ['value' => fa_num(count(exercises())),           'label' => 'تمرین زمان‌دار'],
                ['value' => fa_num(count(tips())),                'label' => 'نکته‌ی کوتاه'],
            ] as $stat): ?>
                <li><strong><?= e($stat['value']) ?></strong><span><?= e($stat['label']) ?></span></li>
<?php endforeach; ?>
            </ul>
        </div>

        <aside class="hero__panel" aria-label="تمرین پیشنهادی امروز">
            <div class="practice-card" data-practice data-practice-seconds="<?= $practiceSeconds ?>">
                <header class="practice-card__head">
                    <span class="practice-card__live"><i aria-hidden="true"></i> تمرین روز</span>
                    <span class="chip chip--ghost"><?= e(minutes_label((int) ceil($practiceSeconds / 60))) ?></span>
                </header>
                <h2 class="practice-card__title"><?= e($practice['title'] ?? 'تمرین روز') ?></h2>
                <p class="practice-card__text"><?= e($practice['goal'] ?? '') ?></p>
                <div class="practice-card__timer">
                    <span class="practice-card__digits" data-practice-digits aria-live="off">۰۰:۰۰</span>
                    <button class="btn btn--primary btn--sm" type="button" data-practice-toggle>شروع</button>
                </div>
                <div class="wave" aria-hidden="true">
                    <span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span>
                </div>
                <footer class="practice-card__foot">
                    <span><?= e($practice['success'] ?? '') ?></span>
                    <a class="link-arrow" href="<?= e(url('exercises')) ?>">همه‌ی تمرین‌ها</a>
                </footer>
            </div>
        </aside>
    </div>
</section>

<!-- ============  چرا های‌ویس  ============ -->
<section class="section section--soft">
    <div class="container">
        <?= section_head($site['why'], 'section-head--center') ?>
        <div class="grid grid--4">
            <?php foreach ((array) $site['why']['items'] as $item): ?>
                <article class="card feature-card reveal">
                    <span class="feature-card__icon" data-icon="<?= e($item['icon']) ?>" aria-hidden="true"></span>
                    <h3><?= e($item['title']) ?></h3>
                    <p><?= e($item['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============  مسیر آموزشی  ============ -->
<section class="section">
    <div class="container">
        <?= section_head([
            'eyebrow' => 'گام‌به‌گام',
            'title'   => $site['path_preview']['title'],
            'lead'    => $site['path_preview']['lead'],
            'action'  => ['label' => 'دیدن کل مسیر', 'url' => url('course')],
        ]) ?>

        <ol class="stage-rail">
            <?php foreach ($stages as $i => $stage): ?>
                <li class="stage-card reveal">
                    <div class="stage-card__head">
                        <span class="stage-card__badge"><?= e($stage['label'] ?? ('مرحله ' . fa_num($i + 1))) ?></span>
                        <span class="chip chip--ghost"><?= e($stage['duration'] ?? '') ?></span>
                    </div>
                    <h3 class="stage-card__title"><?= e($stage['title']) ?></h3>
                    <p class="stage-card__summary"><?= e($stage['summary']) ?></p>
                    <p class="stage-card__outcome"><strong>خروجی:</strong> <?= e($stage['outcome']) ?></p>

                    <div class="stage-card__meta">
                        <span><?= fa_num(count((array) ($stage['lessons'] ?? []))) ?> درس</span>
                        <span class="meta-dot" aria-hidden="true"></span>
                        <span>
                            <?= e(minutes_label(array_sum(array_map(static function (array $l) {
                                return (int) ($l['minutes'] ?? 0);
                            }, (array) ($stage['lessons'] ?? []))))) ?>
                        </span>
                    </div>

                    <div class="stage-progress" data-stage-progress="<?= e(implode(',', course_stage_slugs($stage))) ?>">
                        <span class="muted-sm">پیشرفت این مرحله</span>
                        <?= progress_bar(0, '') ?>
                    </div>

<?php if (!empty($stage['lessons'][0]['slug'])): ?>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('lesson', ['slug' => (string) $stage['lessons'][0]['slug']])) ?>">
                        شروع <?= e($stage['label'] ?? 'مرحله') ?>
                    </a>
<?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>

        <div class="course-summary">
            <div class="course-summary__stats">
                <span><strong><?= fa_num(count(course_lesson_index())) ?></strong> درس</span>
                <span><strong><?= fa_num(count($stages)) ?></strong> مرحله</span>
                <span><strong><?= e(minutes_label(course_total_minutes())) ?></strong> مطالعه</span>
            </div>
            <div class="course-summary__progress">
                <span class="muted-sm">پیشرفت کل دوره در این مرورگر</span>
                <div data-total-progress><?= progress_bar(0, '۰٪') ?></div>
            </div>
        </div>
    </div>
</section>

<!-- ============  تازه‌ترین مقاله‌ها  ============ -->
<section class="section section--soft">
    <div class="container">
        <?= section_head([
            'eyebrow' => 'مجله‌ی های‌ویس',
            'title'   => 'تازه‌ترین مقاله‌ها',
            'lead'    => 'مقاله‌های کوتاه و کاربردی؛ هر کدام با یک تمرین مشخص تمام می‌شود.',
            'action'  => ['label' => 'همه‌ی مقاله‌ها', 'url' => url('articles')],
        ]) ?>
        <div class="grid grid--3">
            <?php foreach ($latest as $article): ?>
                <div class="reveal"><?= article_card($article) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============  روش کار  ============ -->
<section class="section">
    <div class="container method">
        <?= section_head($site['method'], 'section-head--center') ?>
        <ol class="method__list">
            <?php foreach ((array) $site['method']['steps'] as $step): ?>
                <li class="reveal">
                    <h3><?= e($step['title']) ?></h3>
                    <p><?= e($step['text']) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<!-- ============  تمرین‌ها  ============ -->
<section class="section section--dark">
    <div class="container">
        <?= section_head([
            'eyebrow' => 'ابزار تمرین',
            'title'   => $site['exercise_teaser']['title'],
            'lead'    => $site['exercise_teaser']['lead'],
            'action'  => ['label' => 'شروع تمرین‌ها', 'url' => url('exercises')],
        ], 'section-head--invert') ?>
        <div class="grid grid--3">
            <?php foreach ($firstEx as $ex): ?>
                <div class="reveal"><?= exercise_card($ex, url('exercises')) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============  نکته‌های کوتاه  ============ -->
<section class="section">
    <div class="container">
        <?= section_head([
            'eyebrow' => 'یک دقیقه، یک تغییر',
            'title'   => $site['tips_teaser']['title'],
            'lead'    => $site['tips_teaser']['lead'],
            'action'  => ['label' => 'همه‌ی نکته‌ها', 'url' => url('tips')],
        ]) ?>
        <div class="grid grid--3">
            <?php foreach ($firstTips as $tip): ?>
                <div class="reveal"><?= tip_card($tip) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============  دعوت به اقدام  ============ -->
<section class="section">
    <div class="container">
        <div class="cta-band reveal">
            <div class="cta-band__text">
                <h2><?= e($site['cta_band']['title']) ?></h2>
                <p><?= e($site['cta_band']['text']) ?></p>
            </div>
            <div class="cta-band__actions">
                <a class="btn btn--primary btn--lg" href="<?= e(url((string) $site['cta_band']['primary']['route'], ['slug' => (string) $site['cta_band']['primary']['slug']])) ?>">
                    <?= e($site['cta_band']['primary']['label']) ?>
                </a>
                <a class="btn btn--ghost btn--lg" href="<?= e(url((string) $site['cta_band']['ghost']['route'])) ?>">
                    <?= e($site['cta_band']['ghost']['label']) ?>
                </a>
            </div>
        </div>
    </div>
</section>
