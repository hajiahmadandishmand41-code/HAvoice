<?php
/**
 * HAvoice — صفحه‌ی اصلی (Design System v4)
 *
 * ساختارِ صفحه — دقیقاً به همان ترتیبی که یک کاربرِ تازه‌وارد نیاز دارد:
 *
 *   ۱) بنرِ مدرس    : عکسِ واقعی + متنِ ادیتوریال (از داده/پنل تغذیه می‌شود)
 *   ۲) هیرو          : سایت چیست؟ چه چیزی یاد می‌گیرم؟ از کجا شروع کنم؟
 *   ۳) نوارِ حوزه‌ها : نقشه‌ی سریعِ محتوا (اسکرولِ افقی، سبک)
 *   ۴) دوره‌های منتخب : اصلی‌ترین محصولِ آموزشی
 *   ۵) آخرین مقاله‌ها : تازه‌ترین محتوای متنی
 *   ۶) ویدیوهای منتخب : محتوای دیداری
 *   ۷) فایل‌های صوتی  : پادکست و آموزشِ صوتی
 *   ۸) کتاب‌ها/منابع  : منابعِ عمیق‌تر (+ پژوهش)
 *   ۹) تمرین‌ها        : ابزارِ عملی (بخشِ تیره برای تنوعِ بصری)
 *  ۱۰) نظرِ کاربران   : نمونه‌ی نظراتِ تأییدشده (دیتابیس)
 *  ۱۱) معرفی سایت      : مدرس + چرا HAvoice + روشِ کار
 *  ۱۲) فراخوانِ پایانی
 *
 * اصلِ مهم: در صفحه‌ی اصلی فقط «نمونه» نشان می‌دهیم (۳ مورد از هر نوع)
 * و برای ادامه یک دکمه‌ی «مشاهده همه» می‌گذاریم. صفحه شلوغ نمی‌شود.
 * متنِ بخش‌ها از data/site.php (و در صورتِ تنظیم، از پنل) می‌آید.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$site     = ha_site();
$hero     = (array) ($site['hero'] ?? []);
$cats     = categories();
$instructor = (array) ($site['instructor'] ?? []);
$banner   = (array) ($site['home_banner'] ?? []);
$adminCfg = admin_settings();

/* متنِ بنر: مقدارِ پنل بر پیش‌فرضِ فایلِ داده اولویت دارد */
$bannerLead = trim((string) ($adminCfg['banner_lead'] ?? ''));
if ($bannerLead === '') {
    $bannerLead = (string) ($banner['lead'] ?? '');
}

/* تعدادِ نمونه‌ها در صفحه‌ی اصلی — عمداً کم */
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

$latest   = latest_articles($LIMIT);
$videos   = array_slice(videos(), 0, $LIMIT);
$audios   = array_slice(audios(), 0, $LIMIT);
$books    = array_slice(books(), 0, $LIMIT);
$research = array_slice(research_items(), 0, 2);
$exList   = array_slice(exercises(), 0, $LIMIT);
$why      = (array) ($site['why'] ?? []);
$method   = (array) ($site['method'] ?? []);
$cta      = (array) ($site['cta_band'] ?? []);
$commentsTeaser = (array) ($site['comments_teaser'] ?? []);

/* نمونه‌ی نظراتِ تأییدشده — فقط وقتی دیتابیس آماده است */
$homeComments    = [];
$homeCommentsAll = 0;
if (comments_db() !== null) {
    $homeCommentsAll = comments_count_approved();
    if ($homeCommentsAll > 0) {
        $homeComments = comments_approved(3);
    }
}

$practice        = exercises()[0] ?? null;
$practiceSeconds = (int) ($practice['seconds'] ?? 180);

$countCourses  = count(courses());
$countLessons  = count(course_lesson_index());
$countArticles = count(all_articles_sorted());
$countMedia    = count(videos()) + count(audios());

/* عکسِ مدرس — فایلِ واقعیِ داخلِ پروژه (cropping با CSS در instructor-banner.css) */
$bannerPhoto = asset('assets/img/instructor-banner.jpg');
$avatarPhoto = asset('assets/img/instructor.jpg');

/** سرصفحه‌ی استانداردِ بخش‌های صفحه‌ی اصلی */
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

<!-- ۱) بنر مدرس: عکسِ واقعی + متنِ ادیتوریال ============================ -->
<section class="home-instructor-banner" aria-labelledby="home-instructor-banner-title">
    <div class="container">
        <div class="home-instructor-banner__card">
            <div class="home-instructor-banner__photo">
                <img src="<?= e($bannerPhoto) ?>" alt="<?= e(($instructor['name'] ?? ha_site_name()) . ' — ' . ($instructor['role'] ?? ha_site_tagline())) ?>"
                     width="900" height="375" decoding="async" fetchpriority="high">
            </div>
            <div class="home-instructor-banner__content">
                <div class="home-instructor-banner__body">
                    <p class="home-instructor-banner__eyebrow"><?= e($banner['eyebrow'] ?? ($instructor['role'] ?? ha_site_tagline())) ?></p>
                    <h2 id="home-instructor-banner-title" class="home-instructor-banner__title"><?= e($instructor['name'] ?? ha_site_name()) ?></h2>
                    <p class="home-instructor-banner__lead"><?= e($bannerLead) ?></p>
                    <?php if (!empty($banner['tags'])): ?>
                        <ul class="home-instructor-banner__tags" aria-label="حوزه‌های تمرکز">
                            <?php foreach (array_slice((array) $banner['tags'], 0, 4) as $tag): ?>
                                <li><?= e($tag) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="home-instructor-banner__foot">
                    <div class="home-instructor-banner__actions">
                        <a class="btn btn--light btn--md" href="<?= e(url('about')) ?>">آشنایی با مدرس <?= ha_icon('chevron-left', 14) ?></a>
                        <a class="btn btn--outline btn--md" href="<?= e(url('courses')) ?>"><?= ha_icon('steps', 15) ?> دیدنِ دوره‌ها</a>
                    </div>
                    <ul class="home-instructor-banner__stats" aria-label="آمارِ سایت">
                        <li><strong><?= fa_num($countCourses) ?></strong><span>دوره</span></li>
                        <li><strong><?= fa_num($countLessons) ?></strong><span>درس</span></li>
                        <li><strong><?= fa_num($countArticles) ?></strong><span>مقاله</span></li>
                        <li><strong><?= fa_num($countMedia) ?></strong><span>ویدیو و صوت</span></li>
                    </ul>
                </div>
            </div>
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
                <li><strong><?= fa_num($countArticles) ?></strong><span>مقاله‌ی کاربردی</span></li>
                <li><strong><?= fa_num($countMedia) ?></strong><span>ویدیو و صوت</span></li>
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
                <span class="hero__avatar" aria-hidden="true"><img src="<?= e($avatarPhoto) ?>" alt="" width="320" height="320" loading="lazy" decoding="async"></span>
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

    <!-- ۳) نوارِ حوزه‌ها — نقشه‌ی سریع، بدونِ شلوغ‌کردنِ صفحه -->
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
        <?= $head('دوره‌ها', (string) ($site['path_preview']['title'] ?? 'دوره‌های منتخب'), (string) ($site['path_preview']['lead'] ?? ''), url('courses'), 'همه‌ی دوره‌ها') ?>
        <div class="grid grid--3">
            <?php foreach ($featuredCourses as $course): ?>
                <div class="reveal"><?= course_card($course) ?></div>
            <?php endforeach; ?>
        </div>
        <div class="course-summary reveal">
            <div class="course-summary__stats">
                <span><strong><?= fa_num($countCourses) ?></strong> دوره</span>
                <span><strong><?= fa_num($countLessons) ?></strong> درس</span>
                <span><strong><?= e(minutes_label(course_total_minutes())) ?></strong> مطالعه</span>
            </div>
            <div class="course-summary__progress">
                <span class="muted-sm">پیشرفتِ کل</span>
                <div data-total-progress><?= progress_bar(0, '۰٪', 'پیشرفتِ کلِ درس‌ها') ?></div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ۵) آخرین مقاله‌ها ==================================================== -->
<?php if ($latest !== []): ?>
<section class="section section--soft">
    <div class="container">
        <?= $head('مجله‌ی HAvoice', (string) ($site['articles_teaser']['title'] ?? 'تازه‌ترین مقاله‌ها'), (string) ($site['articles_teaser']['lead'] ?? ''), url('articles'), 'همه‌ی مقاله‌ها') ?>
        <div class="grid grid--3">
            <?php foreach ($latest as $article): ?>
                <div class="reveal"><?= article_card($article) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ۶) ویدیوهای منتخب ==================================================== -->
<?php if ($videos !== []): ?>
<section class="section">
    <div class="container">
        <?= $head('ویدیو', (string) ($site['videos_teaser']['title'] ?? 'ویدیوهای آموزشی'), (string) ($site['videos_teaser']['lead'] ?? ''), url('videos'), 'همه‌ی ویدیوها') ?>
        <div class="grid grid--3">
            <?php foreach ($videos as $v): ?>
                <div class="reveal"><?= video_card($v) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ۷) فایل‌های صوتی ===================================================== -->
<?php if ($audios !== []): ?>
<section class="section section--soft">
    <div class="container">
        <?= $head('صدا', (string) ($site['audios_teaser']['title'] ?? 'پادکست و آموزشِ صوتی'), (string) ($site['audios_teaser']['lead'] ?? ''), url('audios'), 'همه‌ی صوت‌ها') ?>
        <div class="grid grid--3">
            <?php foreach ($audios as $a): ?>
                <div class="reveal"><?= audio_card($a) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ۸) کتاب‌ها و منابع =================================================== -->
<?php if ($books !== [] || $research !== []): ?>
<section class="section">
    <div class="container">
        <?= $head('منابع', (string) ($site['books_teaser']['title'] ?? 'کتاب و خلاصه‌ی کتاب'), (string) ($site['books_teaser']['lead'] ?? ''), url('books'), 'همه‌ی کتاب‌ها') ?>
        <?php if ($books !== []): ?>
            <div class="grid grid--3">
                <?php foreach ($books as $book): ?>
                    <div class="reveal"><?= book_card($book) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($research !== []): ?>
            <div class="two-col mt-md">
                <div>
                    <h3 class="h3"><?= ha_icon('research', 16) ?> پژوهش و یادداشتِ منبع‌دار</h3>
                    <p class="muted-sm mb-sm">مرورِ منابع با ذکرِ دقیقِ رفرنس؛ قابلِ راستی‌آزمایی و بدونِ ادعای بی‌منبع.</p>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('research')) ?>">همه‌ی پژوهش‌ها <?= ha_icon('chevron-left', 14) ?></a>
                </div>
                <div class="grid gap-sm">
                    <?php foreach ($research as $r): ?>
                        <a class="result" href="<?= e(url('research', ['slug' => (string) ($r['slug'] ?? '')])) ?>">
                            <p class="result__title"><?= e($r['title'] ?? '') ?></p>
                            <p class="result__kind"><span class="badge badge--soft"><?= e($r['category'] ?? '') ?></span><span class="muted-sm"><?= e($r['date_fa'] ?? '') ?></span></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- ۹) تمرین‌ها — بخشِ تیره ============================================== -->
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

<!-- ۱۰) نظرِ کاربران — نمونه‌ای از نظراتِ تأییدشده ======================== -->
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
                    <img src="<?= e($avatarPhoto) ?>" alt="<?= e(($instructor['name'] ?? ha_site_name()) . ' — ' . ($instructor['role'] ?? ha_site_tagline())) ?>" width="320" height="320" loading="lazy" decoding="async">
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
        <div class="cta-band reveal">
            <div class="cta-band__text">
                <h2><?= e($cta['title']) ?></h2>
                <p><?= e($cta['text'] ?? '') ?></p>
            </div>
            <div class="cta-band__actions">
                <?php if (!empty($cta['primary'])): ?>
                    <a class="btn btn--primary btn--lg" href="<?= e(url((string) $cta['primary']['route'], ['slug' => (string) ($cta['primary']['slug'] ?? '')])) ?>">
                        <?= ha_icon('play', 15) ?> <?= e($cta['primary']['label']) ?>
                    </a>
                <?php endif; ?>
                <?php if (!empty($cta['ghost'])): ?>
                    <a class="btn btn--ghost btn--lg" href="<?= e(url((string) $cta['ghost']['route'])) ?>"><?= e($cta['ghost']['label']) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
