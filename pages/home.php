<?php
/**
 * HAvoice — صفحه‌ی اصلی (Design System v4)
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$site     = ha_site();
$hero     = (array) ($site['hero'] ?? []);
$cats     = categories();
$instructor = (array) ($site['instructor'] ?? []);

/* متنِ جایگزینِ بنر (از پنلِ مدیریت قابلِ تغییر است) */
$bannerAlt = trim((string) (admin_settings()['banner_alt'] ?? ''));
if ($bannerAlt === '') {
    $bannerAlt = 'بنرِ فن‌بیان — فن بیان، مهارت زندگی؛ آموزش‌های کاربردی فن بیان، مهارت‌های ارتباطی، اعتمادبه‌نفس و دوره‌های آنلاین و حضوری با حاجی احمد صالحی';
}

$LIMIT = 3;

$featuredCourses = courses_featured($LIMIT);
if (count($featuredCourses) < $LIMIT) {
    foreach (courses() as $c) {
        if (count($featuredCourses) >= $LIMIT) { break; }
        $exists = false;
        foreach ($featuredCourses as $f) { if (($f['slug'] ?? '') === ($c['slug'] ?? '')) { $exists = true; break; } }
        if (!$exists) { $featuredCourses[] = $c; }
    }
}

/* فقط Featured / Important / Recent — بدون بخش‌های خالی یا جعلی */
$latest   = ha_featured_first(all_articles_sorted(), $LIMIT);
$videos   = ha_featured_first(videos(), $LIMIT);   // فقط playable
$audios   = ha_featured_first(audios(), $LIMIT);
$books    = ha_featured_first(books(), $LIMIT);
$exList   = ha_featured_first(exercises(), $LIMIT);
$why      = (array) ($site['why'] ?? []);
$method   = (array) ($site['method'] ?? []);
$cta      = (array) ($site['cta_band'] ?? []);
$commentsTeaser = (array) ($site['comments_teaser'] ?? []);

$homeComments = [];
if (comments_db() !== null && comments_count_approved() > 0) {
    $homeComments = comments_approved(3);
}

$practice        = $exList[0] ?? (exercises()[0] ?? null);
$practiceSeconds = (int) ($practice['seconds'] ?? 180);

$countCourses  = count(courses());
$countLessons  = count(course_lesson_index());
$countArticles = count(all_articles_sorted());
$countExercises = count(exercises());

/* تصاویرِ واقعیِ سایت — هر دو فایلِ داخلِ assets/img (بنر + عکسِ مدرس) */
$bannerArtwork = asset('assets/img/fanbayan-banner.webp');
$avatarPhoto   = asset('assets/img/instructor.jpg');

$head = static function (string $eyebrow, string $title, string $lead, string $url, string $label = 'مشاهده همه'): string {
    return section_head([
        'eyebrow' => $eyebrow,
        'title'   => $title,
        'lead'    => $lead,
        'action'  => ['label' => $label, 'url' => $url, 'type' => 'button'],
        'row'     => true,
    ]);
};
?>

<!-- ۱) بنرِ فن‌بیان: تصویرِ واقعیِ assets/img/fanbayan-banner.webp =========== -->
<section class="home-instructor-banner" aria-label="بنرِ فن‌بیان">
    <div class="container">
        <div class="home-instructor-banner__card">
            <img class="home-instructor-banner__art"
                 src="<?= e($bannerArtwork) ?>"
                 alt="<?= e($bannerAlt) ?>"
                 width="640"
                 height="233"
                 decoding="async"
                 fetchpriority="high">
        </div>
    </div>
</section>

<!-- ۲) هیرو ============================================================== -->
<section class="hero hero--premium">
    <div class="container hero__grid">
        <div class="hero__content">
            <span class="hero__badge">HAvoice · مرکز آموزشِ مهارت‌های کاربردی</span>
            <?php if (!empty($hero['eyebrow'])): ?>
                <p class="eyebrow"><?= e($hero['eyebrow']) ?></p>
            <?php endif; ?>
            <h1 class="hero__title hero__title--premium"><?= e($hero['title'] ?? '') ?></h1>
            <p class="hero__lead hero__lead--premium"><?= e($hero['lead'] ?? '') ?></p>

            <div class="btn-row">
                <?php if (!empty($hero['primary'])): ?>
                    <a class="btn btn--accent btn--lg" href="<?= e(url((string) $hero['primary']['route'])) ?>">
                        <?= ha_icon('steps', 16) ?> <?= e($hero['primary']['label']) ?>
                    </a>
                <?php endif; ?>
                <?php if (!empty($hero['ghost'])): ?>
                    <a class="btn btn--ghost btn--lg" href="<?= e(url((string) $hero['ghost']['route'])) ?>"><?= e($hero['ghost']['label']) ?></a>
                <?php endif; ?>
            </div>

            <?php if (!empty($hero['note'])): ?>
                <p class="hero__note"><?= e($hero['note']) ?></p>
            <?php endif; ?>

            <ul class="hero__stats hero__stats--premium">
                <li><strong><?= fa_num($countCourses) ?></strong><span>دوره‌ی مرحله‌ای</span></li>
                <li><strong><?= fa_num($countLessons) ?></strong><span>درسِ تمرین‌محور</span></li>
                <li><strong><?= fa_num($countExercises) ?></strong><span>تمرین عملی</span></li>
                <li><strong><?= fa_num($countArticles) ?></strong><span>مقاله‌ی کاربردی</span></li>
            </ul>
        </div>

        <aside class="hero__panel" aria-label="تمرین پیشنهادی و معرفی مدرس">
            <div class="practice-card" data-practice data-practice-seconds="<?= $practiceSeconds ?>">
                <header class="practice-card__head">
                    <span class="practice-card__live"><i aria-hidden="true"></i> تمرینِ روز</span>
                    <span class="chip chip--ghost"><?= ha_icon('timer', 12) ?> <?= e(minutes_label((int) ceil($practiceSeconds / 60))) ?></span>
                </header>
                <h2 class="practice-card__title"><?= e($practice['title'] ?? 'تمرینِ روز') ?></h2>
                <p class="practice-card__text"><?= e($practice['goal'] ?? '') ?></p>
                <div class="practice-card__timer">
                    <span class="practice-card__digits" data-practice-digits aria-live="off">۰۰:۰۰</span>
                    <button class="btn btn--accent btn--sm" type="button" data-practice-toggle>شروع</button>
                </div>
                <div class="wave" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
                <footer class="practice-card__foot">
                    <span><?= e($practice['success'] ?? '') ?></span>
                    <a class="link-arrow" href="<?= e(url('exercises')) ?>">همه‌ی تمرین‌ها</a>
                </footer>
            </div>

            <div class="hero__instructor">
                <span class="hero__avatar" aria-hidden="true"><img src="<?= e($avatarPhoto) ?>" alt="" width="360" height="360" loading="lazy" decoding="async"></span>
                <div>
                    <h3><?= e($instructor['name'] ?? ha_site_name()) ?></h3>
                    <p><?= e($instructor['role'] ?? ha_site_tagline()) ?></p>
                </div>
            </div>

            <div class="course-summary">
                <div class="course-summary__progress">
                    <span class="muted-sm">پیشرفتِ شما در این مرورگر</span>
                    <div data-total-progress><?= progress_bar(0, '۰٪', 'پیشرفتِ کلِ درس‌ها') ?></div>
                </div>
            </div>
        </aside>
    </div>

    <?php if ($cats !== []): ?>
    <div class="container topic-strip">
        <div class="topic-strip__head">
            <p class="topic-strip__title"><?= ha_icon('compass', 14) ?> <?= fa_num(count($cats)) ?> حوزه‌ی آموزشی — یکی را انتخاب کنید</p>
            <a class="link-arrow" href="<?= e(url('courses')) ?>">همه‌ی حوزه‌ها</a>
        </div>
        <div class="topic-strip__list" role="list">
            <?php foreach ($cats as $cat): ?>
                <a class="topic-pill" role="listitem" href="<?= e(url('category', ['slug' => (string) ($cat['slug'] ?? '')])) ?>" style="<?= e(card_style($cat)) ?>">
                    <?= ha_icon((string) ($cat['icon'] ?? 'compass'), 16) ?>
                    <span><?= e($cat['short'] ?? $cat['title'] ?? '') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</section>

<!-- ۴) دوره‌های منتخب ==================================================== -->
<?php if ($featuredCourses !== []): ?>
<section class="section">
    <div class="container">
        <?= $head('دوره‌ها', (string) ($site['path_preview']['title'] ?? 'دوره‌های منتخب'), (string) ($site['path_preview']['lead'] ?? ''), url('courses')) ?>
        <div class="grid grid--3">
            <?php foreach ($featuredCourses as $course): ?>
                <div class="reveal"><?= course_card($course) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ۵) آخرین مقاله‌ها ===================================================== -->
<?php if ($latest !== []): ?>
<section class="section section--soft">
    <div class="container">
        <?= $head('مقاله‌ها', 'تازه‌ترین مقاله‌ها', 'یادداشت‌های آموزشی و کاربردی برای تمرین و تصمیم‌گیری بهتر.', url('articles')) ?>
        <div class="grid grid--3">
            <?php foreach ($latest as $article): ?>
                <div class="reveal"><?= article_card($article) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ۶) ویدیوها ============================================================ -->
<?php if ($videos !== []): ?>
<section class="section">
    <div class="container">
        <?= $head('ویدیو', 'ویدیوهای منتخب', 'یادگیری با دیدن، شنیدن و تمرین.', url('videos')) ?>
        <div class="grid grid--3">
            <?php foreach ($videos as $video): ?>
                <div class="reveal"><?= video_card($video) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ۷) فایل‌های صوتی ====================================================== -->
<?php if ($audios !== []): ?>
<section class="section section--soft">
    <div class="container">
        <?= $head('صوت', 'آموزش‌های صوتی', 'برای مرور، تمرین و یادگیری در مسیر.', url('audios')) ?>
        <div class="grid grid--3">
            <?php foreach ($audios as $audio): ?>
                <div class="reveal"><?= audio_card($audio) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ۸) کتاب‌های منتخب ===================================================== -->
<?php if ($books !== []): ?>
<section class="section">
    <div class="container">
        <?= $head('منابع', 'کتاب‌های منتخب', 'خلاصه‌ها و برداشت‌های کاربردی.', url('books')) ?>
        <div class="grid grid--3">
            <?php foreach ($books as $book): ?>
                <div class="reveal"><?= book_card($book) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ۹) تمرین‌ها =========================================================== -->
<?php if ($exList !== []): ?>
<section class="section section--dark">
    <div class="container">
        <?= $head('ابزارِ تمرین', (string) ($site['exercise_teaser']['title'] ?? 'تمرینِ امروز را شروع کنید'), (string) ($site['exercise_teaser']['lead'] ?? ''), url('exercises'), 'شروع تمرین‌ها') ?>
        <div class="grid grid--3">
            <?php foreach ($exList as $ex): ?>
                <div class="reveal"><?= exercise_card($ex, url('exercises')) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ۱۰) نظرِ کاربران ===================================================== -->
<?php if ($homeComments !== []): ?>
<section class="section">
    <div class="container">
        <?= $head('نظرات', (string) ($commentsTeaser['title'] ?? 'نظرِ همراهانِ HAvoice'), (string) ($commentsTeaser['lead'] ?? ''), url('comments'), 'همه‌ی نظرات') ?>
        <div class="grid grid--3">
            <?php foreach ($homeComments as $hc): ?>
                <figure class="comment-card reveal">
                    <div class="comment-card__head">
                        <span class="comment-card__avatar" aria-hidden="true"><?= e(mb_substr((string) ($hc['name'] ?? ''), 0, 1, 'UTF-8')) ?></span>
                        <div class="comment-card__who">
                            <strong class="comment-card__name"><?= e($hc['name'] ?? '') ?></strong>
                            <?php $hcd = (string) ($hc['created_at'] ?? ''); ?>
                            <?php if ($hcd !== ''): ?>
                                <time class="comment-card__date" datetime="<?= e($hcd) ?>"><?= ha_icon('calendar', 12) ?> <?= e(fa_num((new DateTimeImmutable($hcd))->format('Y/m/d'))) ?></time>
                            <?php endif; ?>
                        </div>
                    </div>
                    <blockquote class="comment-card__body"><?= nl2br(e($hc['body'] ?? '')) ?></blockquote>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ۱۱) معرفی سایت: مدرس + چرا HAvoice + روشِ کار ========================= -->
<section class="section section--soft">
    <div class="container">
        <div class="instructor reveal">
            <div class="instructor__media">
                <div class="instructor__avatar">
                    <img src="<?= e($avatarPhoto) ?>" alt="<?= e(($instructor['name'] ?? ha_site_name()) . ' — ' . ($instructor['role'] ?? ha_site_tagline())) ?>" width="360" height="360" loading="lazy" decoding="async">
                </div>
                <span class="instructor__badge"><?= e($instructor['role'] ?? ha_site_tagline()) ?></span>
            </div>
            <div class="instructor__text">
                <p class="instructor__role"><?= e($instructor['role'] ?? '') ?></p>
                <h2 class="instructor__name"><?= e($instructor['name'] ?? ha_site_name()) ?></h2>
                <p class="instructor__bio"><?= e($instructor['bio'] ?? '') ?></p>
                <?php if (!empty($instructor['points'])): ?>
                    <ul class="instructor__points">
                        <?php foreach ((array) $instructor['points'] as $pt): ?>
                            <li><span class="tick" aria-hidden="true"><?= ha_icon('check', 12) ?></span><span><?= e($pt) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if (!empty($instructor['note'])): ?>
                    <p class="instructor__note"><?= e($instructor['note']) ?></p>
                <?php endif; ?>
                <div class="btn-row mt-sm">
                    <a class="btn btn--primary" href="<?= e(url('about')) ?>"><?= ha_icon('user', 15) ?> بیشتر درباره‌ی مدرس</a>
                    <a class="btn btn--ghost" href="<?= e(url('contact')) ?>">دعوت به همکاری</a>
                </div>
            </div>
        </div>

        <?php if (!empty($why['items'])): ?>
            <div class="mt-section">
                <?= section_head(['eyebrow' => 'چرا HAvoice', 'title' => (string) ($why['title'] ?? ''), 'lead' => (string) ($why['lead'] ?? '')], 'section-head--center') ?>
                <div class="grid grid--4">
                    <?php foreach ((array) $why['items'] as $item): ?>
                        <article class="card feature-card reveal">
                            <span class="feature-card__icon" aria-hidden="true"><?= ha_icon((string) ($item['icon'] ?? 'check'), 22) ?></span>
                            <h3><?= e($item['title'] ?? '') ?></h3>
                            <p><?= e($item['text'] ?? '') ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($method['steps'])): ?>
            <div class="method mt-section">
                <?= section_head(['eyebrow' => 'ساختارِ درس‌ها', 'title' => (string) ($method['title'] ?? ''), 'lead' => (string) ($method['lead'] ?? '')], 'section-head--center') ?>
                <ol class="method__list">
                    <?php foreach ((array) $method['steps'] as $step): ?>
                        <li class="reveal"><h3><?= e($step['title'] ?? '') ?></h3><p><?= e($step['text'] ?? '') ?></p></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ۱۲) فراخوانِ پایانی ================================================== -->
<?php if ($cta !== [] && !empty($cta['title'])): ?>
<section class="section section--tight">
    <div class="container">
        <?= section_head(['eyebrow' => 'شروع از همین‌جا', 'title' => (string) ($cta['title'] ?? ''), 'lead' => (string) ($cta['lead'] ?? '')], 'section-head--center') ?>
        <div class="btn-row btn-row--center mt-sm">
            <?php if (!empty($cta['primary'])): ?>
                <a class="btn btn--primary btn--lg" href="<?= e(url((string) $cta['primary']['route'])) ?>"><?= e($cta['primary']['label']) ?> <?= ha_icon('chevron-left', 15) ?></a>
            <?php endif; ?>
            <?php if (!empty($cta['secondary'])): ?>
                <a class="btn btn--ghost btn--lg" href="<?= e(url((string) $cta['secondary']['route'])) ?>"><?= e($cta['secondary']['label']) ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>
