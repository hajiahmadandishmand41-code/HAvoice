<?php
/**
 * HAvoice — صفحه‌ی اصلی (Design System v4)
 *
 * ساختارِ صفحه — دقیقاً به همان ترتیبی که یک کاربرِ تازه‌وارد نیاز دارد:
 *
 *   ۱) هیرو            : سایت چیست؟ چه چیزی یاد می‌گیرم؟ از کجا شروع کنم؟
 *   ۲) نوارِ حوزه‌ها   : نقشه‌ی سریعِ محتوا (اسکرولِ افقی، سبک)
 *   ۳) دوره‌های منتخب : اصلی‌ترین محصولِ آموزشی
 *   ۴) آخرین مقاله‌ها : تازه‌ترین محتوای متنی
 *   ۵) ویدیوهای منتخب : محتوای دیداری
 *   ۶) فایل‌های صوتی  : پادکست و آموزشِ صوتی
 *   ۷) کتاب‌ها/منابع  : منابعِ عمیق‌تر (+ پژوهش)
 *   ۸) تمرین‌ها        : ابزارِ عملی (بخشِ تیره برای تنوعِ بصری)
 *   ۹) معرفی سایت      : مدرس + چرا HAvoice + روشِ کار
 *  ۱۰) فراخوانِ پایانی
 *
 * اصلِ مهم: در صفحه‌ی اصلی فقط «نمونه» نشان می‌دهیم (۳ مورد از هر نوع)
 * و برای ادامه یک دکمه‌ی «مشاهده همه» می‌گذاریم. صفحه شلوغ نمی‌شود.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$site     = ha_site();
$hero     = (array) ($site['hero'] ?? []);
$cats     = categories();
$instructor = (array) ($site['instructor'] ?? []);

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

$practice        = exercises()[0] ?? null;
$practiceSeconds = (int) ($practice['seconds'] ?? 180);

$countCourses  = count(courses());
$countLessons  = count(course_lesson_index());
$countArticles = count(all_articles_sorted());
$countMedia    = count(videos()) + count(audios());

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

<!-- ۱) بنر مدرس: یک بنر ساده و مستقل در صدر صفحه ======================= -->
<section class="home-instructor-banner" aria-labelledby="home-instructor-banner-title">
    <style>
        .home-instructor-banner{padding:18px 0 0}
        .home-instructor-banner__card{display:flex;align-items:center;gap:20px;min-width:0;padding:18px 20px;border:1px solid var(--border);border-radius:20px;background:linear-gradient(135deg,rgba(26,58,124,.08),rgba(79,70,229,.05));box-shadow:var(--sh-sm);overflow:hidden}
        .home-instructor-banner__avatar{flex:0 0 74px;width:74px;height:74px;display:grid;place-items:center;border-radius:20px;background:linear-gradient(135deg,#1A3A7C,#4F46E5);color:#fff;font-size:28px;font-weight:800;box-shadow:var(--sh-md)}
        .home-instructor-banner__body{min-width:0;flex:1}
        .home-instructor-banner__eyebrow{margin:0 0 5px;font-size:.8rem;font-weight:700;color:var(--accent,#4F46E5)}
        .home-instructor-banner__title{margin:0;font-size:1.35rem;line-height:1.35;color:var(--text-strong,var(--text))}
        .home-instructor-banner__lead{margin:5px 0 0;color:var(--muted);line-height:1.8;font-size:.92rem}
        .home-instructor-banner__action{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:0 15px;border-radius:12px;background:var(--accent,#4F46E5);color:#fff;text-decoration:none;font-weight:700;white-space:nowrap}
        .home-instructor-banner__action:hover{filter:brightness(.96)}
        @media (max-width:680px){
            .home-instructor-banner{padding-top:10px}
            .home-instructor-banner__card{gap:12px;padding:14px;border-radius:16px}
            .home-instructor-banner__avatar{flex-basis:56px;width:56px;height:56px;border-radius:15px;font-size:22px}
            .home-instructor-banner__title{font-size:1.05rem}
            .home-instructor-banner__lead{font-size:.8rem;line-height:1.65}
            .home-instructor-banner__action{display:none}
        }
    </style>
    <div class="container">
        <div class="home-instructor-banner__card">
            <div class="home-instructor-banner__avatar" aria-hidden="true">
                <?= e(mb_substr((string) ($instructor['name'] ?? ha_site_name()), 0, 1, 'UTF-8')) ?>
            </div>
            <div class="home-instructor-banner__body">
                <p class="home-instructor-banner__eyebrow">مدرس و پژوهشگر · HAvoice</p>
                <h2 id="home-instructor-banner-title" class="home-instructor-banner__title"><?= e($instructor['name'] ?? ha_site_name()) ?></h2>
                <p class="home-instructor-banner__lead">آموزش فن بیان، سخنوری و مهارت‌های ارتباطی به‌صورت کاربردی و مرحله‌به‌مرحله.</p>
            </div>
            <a class="home-instructor-banner__action" href="<?= e(url('about')) ?>">آشنایی با مدرس <?= ha_icon('chevron-left', 13) ?></a>
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
                <div class="hero__avatar" aria-hidden="true"><?= e(mb_substr((string) ($instructor['name'] ?? ha_site_name()), 0, 1, 'UTF-8')) ?></div>
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
        <?= $head('مجله‌ی HAvoice', 'تازه‌ترین مقاله‌ها', 'مقاله‌های کوتاه و کاربردی؛ هر کدام با یک تمرینِ مشخص تمام می‌شود.', url('articles'), 'همه‌ی مقاله‌ها') ?>
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
        <?= $head('ویدیو', 'ویدیوهای آموزشی — کوتاه و تمرین‌محور', 'هر ویدیو یک مهارتِ قابلِ اجرا را گام‌به‌گام نشان می‌دهد.', url('videos'), 'همه‌ی ویدیوها') ?>
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
        <?= $head('صدا', 'پادکست و آموزشِ صوتی', 'برای یادگیری در مسیر؛ هر قسمت کوتاه است و پخش‌کننده‌ی داخلی دارد.', url('audios'), 'همه‌ی صوت‌ها') ?>
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
        <?= $head('منابع', 'کتاب و خلاصه‌ی کتاب', 'معرفی و خلاصه‌ی کتاب‌های شاخصِ هر حوزه با برداشت‌های قابلِ اجرا.', url('books'), 'همه‌ی کتاب‌ها') ?>
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

<!-- ۱۰) معرفی سایت: مدرس + چرا HAvoice + روشِ کار ========================= -->
<section class="section section--soft">
    <div class="container">
        <div class="instructor reveal">
            <div class="instructor__media">
                <div class="instructor__avatar" aria-hidden="true"><?= e(mb_substr((string) ($instructor['name'] ?? ha_site_name()), 0, 1, 'UTF-8')) ?></div>
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

<!-- ۱۱) فراخوانِ پایانی ================================================== -->
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
