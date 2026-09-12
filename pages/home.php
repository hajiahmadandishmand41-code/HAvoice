<?php
/**
 * HAvoice 2.0 — صفحه‌ی اصلی پریمیوم
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$site     = data('site');
$hero     = (array)($site['hero'] ?? []);
$cats     = categories();
$featuredCourses = courses_featured(3);
$latest   = latest_articles(3);
$videos   = array_slice(videos(), 0, 3);
$audios   = array_slice(audios(), 0, 3);
$books    = array_slice(books(), 0, 3);
$research = array_slice(research_items(), 0, 2);
$paths    = learning_paths();
$firstEx  = array_slice(exercises(), 0, 3);
$firstTips= array_slice(tips(), 0, 3);
$practice = exercises()[0] ?? null;
$practiceSeconds = (int)($practice['seconds'] ?? 180);
$instructor = $site['instructor'] ?? [];
?>

<!-- Hero پریمیوم -->
<section class="hero hero--premium">
    <div class="container hero__grid">
        <div class="hero__content">
            <span class="hero__badge">HAvoice · مرکز آموزش مهارت‌های کاربردی</span>
            <p class="eyebrow"><?= e($hero['eyebrow'] ?? '') ?></p>
            <h1 class="hero__title hero__title--premium"><?= e($hero['title'] ?? '') ?></h1>
            <p class="hero__lead hero__lead--premium"><?= e($hero['lead'] ?? '') ?></p>

            <div class="btn-row" style="margin-top:1.2rem">
                <?php if(!empty($hero['primary'])): ?>
                    <a class="btn btn--primary btn--lg" href="<?= e(url((string)$hero['primary']['route'])) ?>"><?= e($hero['primary']['label']) ?></a>
                <?php endif; ?>
                <?php if(!empty($hero['ghost'])): ?>
                    <a class="btn btn--ghost btn--lg" href="<?= e(url((string)$hero['ghost']['route'])) ?>"><?= e($hero['ghost']['label']) ?></a>
                <?php endif; ?>
            </div>

            <p class="hero__note"><?= e($hero['note'] ?? '') ?></p>

            <div class="hero__instructor">
                <div class="hero__avatar" aria-hidden="true">ح</div>
                <div>
                    <h3><?= e($instructor['name'] ?? HA_NAME) ?> — <?= e($instructor['role'] ?? HA_TAGLINE) ?></h3>
                    <p><?= e($instructor['bio'] ?? '') ?></p>
                </div>
            </div>

            <ul class="hero__stats hero__stats--premium">
                <li><strong><?= fa_num(count(courses())) ?></strong><span>دوره‌ی مرحله‌ای</span></li>
                <li><strong><?= fa_num(count(course_lesson_index())) ?></strong><span>درس تمرین‌محور</span></li>
                <li><strong><?= fa_num(count(data('articles'))) ?></strong><span>مقاله کاربردی</span></li>
                <li><strong><?= fa_num(count(videos()) + count(audios())) ?></strong><span>ویدیو و صوت</span></li>
            </ul>
        </div>

        <aside class="hero__panel" aria-label="تمرین پیشنهادی امروز">
            <div class="practice-card" data-practice data-practice-seconds="<?= $practiceSeconds ?>">
                <header class="practice-card__head">
                    <span class="practice-card__live"><i aria-hidden="true"></i> تمرین روز</span>
                    <span class="chip chip--ghost"><?= e(minutes_label((int)ceil($practiceSeconds/60))) ?></span>
                </header>
                <h2 class="practice-card__title"><?= e($practice['title'] ?? 'تمرین روز') ?></h2>
                <p class="practice-card__text"><?= e($practice['goal'] ?? '') ?></p>
                <div class="practice-card__timer">
                    <span class="practice-card__digits" data-practice-digits aria-live="off">۰۰:۰۰</span>
                    <button class="btn btn--primary btn--sm" type="button" data-practice-toggle>شروع</button>
                </div>
                <div class="wave" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
                <footer class="practice-card__foot">
                    <span><?= e($practice['success'] ?? '') ?></span>
                    <a class="link-arrow" href="<?= e(url('exercises')) ?>">همه‌ی تمرین‌ها</a>
                </footer>
            </div>
            <div class="course-summary" style="margin-top:1rem">
                <div class="course-summary__progress">
                    <span class="muted-sm">پیشرفت کل دوره‌ها در این مرورگر</span>
                    <div data-total-progress><?= progress_bar(0,'۰٪') ?></div>
                </div>
            </div>
        </aside>
    </div>
</section>

<!-- حوزه‌های آموزشی -->
<section class="section">
    <div class="container">
        <?= section_head(['eyebrow'=>'نقشه‌ی یادگیری','title'=>'۱۲ حوزه‌ی آموزشی — یک سیستمِ یکپارچه','lead'=>'هر حوزه یک مسیرِ مرحله‌ای دارد؛ از فن بیان تا پژوهش. برای افزودنِ حوزه‌ی جدید، ساختار آماده است و نیازی به بازنویسیِ سایت نیست.','action'=>['label'=>'دیدن همه‌ی حوزه‌ها','url'=>url('courses')]]) ?>
        <div class="grid category-grid">
            <?php foreach($cats as $cat): ?>
                <div class="reveal"><?= category_card($cat) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- دوره‌های منتخب -->
<section class="section section--soft">
    <div class="container">
        <?= section_head(['eyebrow'=>'دوره‌ها','title'=>$site['path_preview']['title'],'lead'=>$site['path_preview']['lead'],'action'=>['label'=>'همه‌ی دوره‌ها','url'=>url('courses')]]) ?>
        <div class="grid grid--3">
            <?php foreach($featuredCourses as $course): ?>
                <div class="reveal"><?= course_card($course) ?></div>
            <?php endforeach; ?>
        </div>
        <div class="course-summary reveal">
            <div class="course-summary__stats">
                <span><strong><?= fa_num(count(courses())) ?></strong> دوره</span>
                <span><strong><?= fa_num(count(course_lesson_index())) ?></strong> درس</span>
                <span><strong><?= e(minutes_label(course_total_minutes())) ?></strong> مطالعه</span>
            </div>
            <div class="course-summary__progress"><span class="muted-sm">پیشرفت کل</span><div data-total-progress><?= progress_bar(0,'۰٪') ?></div></div>
        </div>
    </div>
</section>

<!-- مسیرهای یادگیری -->
<?php if($paths!==[]): ?>
<section class="section">
    <div class="container">
        <?= section_head(['eyebrow'=>'مسیرهای ترکیبی','title'=>'مسیرهای یادگیری — از مسئله تا مهارت','lead'=>'ترکیبی از چند حوزه برای یک مسئله‌ی واقعی؛ هر مسیر با درس، مقاله، تمرین و کتاب.']) ?>
        <div class="grid grid--3">
            <?php foreach($paths as $path): ?>
                <article class="path-card reveal">
                    <div class="path-card__top">
                        <?php foreach(array_slice((array)($path['categories']??[]),0,3) as $cs): $c=find_category($cs); ?>
                            <span class="badge badge--soft"><?= e($c['short']?? $cs) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <h3 class="path-card__title"><?= e($path['title']) ?></h3>
                    <p class="path-card__excerpt"><?= e($path['excerpt']) ?></p>
                    <ol class="path-card__steps">
                        <?php foreach(array_slice((array)($path['steps']??[]),0,4) as $i=>$step): ?>
                            <li><span class="path-card__n"><?= fa_num($i+1) ?></span><span><?= e(($step['type']??'').' · '.$step['slug']) ?><?php if(!empty($step['note'])): ?> — <?= e($step['note']) ?><?php endif; ?></span></li>
                        <?php endforeach; ?>
                    </ol>
                    <div class="path-card__foot"><span class="muted-sm"><?= minutes_label((int)($path['minutes']??30)) ?></span><span class="chip chip--ghost"><?= fa_num(count($path['steps']??[])) ?> گام</span></div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ویدیوهای جدید -->
<section class="section section--soft">
    <div class="container">
        <?= section_head(['eyebrow'=>'ویدیو','title'=>'ویدیوهای آموزشی — کوتاه و تمرین‌محور','lead'=>'هر ویدیو یک مهارتِ قابلِ اجرا؛ ساختار آماده برای افزودنِ فایل یا لینکِ واقعی بدونِ تغییرِ کد.','action'=>['label'=>'همه‌ی ویدیوها','url'=>url('videos')]]) ?>
        <div class="grid grid--3">
            <?php foreach($videos as $v): ?><div class="reveal"><?= video_card($v) ?></div><?php endforeach; ?>
        </div>
    </div>
</section>

<!-- پادکست -->
<section class="section">
    <div class="container">
        <?= section_head(['eyebrow'=>'صدا','title'=>'پادکست و آموزشِ صوتی','lead'=>'برای یادگیری در مسیر؛ هر قسمت ۵ تا ۹ دقیقه، با پخش‌کننده‌ی داخلی و حالتِ «به‌زودی» برای محتوای آماده‌سازی.','action'=>['label'=>'همه‌ی صوت‌ها','url'=>url('audios')]]) ?>
        <div class="grid grid--3">
            <?php foreach($audios as $a): ?><div class="reveal"><?= audio_card($a) ?></div><?php endforeach; ?>
        </div>
    </div>
</section>

<!-- مقالات جدید -->
<section class="section section--soft">
    <div class="container">
        <?= section_head(['eyebrow'=>'مجله‌ی HAvoice','title'=>'تازه‌ترین مقاله‌ها','lead'=>'مقاله‌های کوتاه و کاربردی؛ هر کدام با یک تمرینِ مشخص تمام می‌شود.','action'=>['label'=>'همه‌ی مقاله‌ها','url'=>url('articles')]]) ?>
        <div class="grid grid--3">
            <?php foreach($latest as $article): ?><div class="reveal"><?= article_card($article) ?></div><?php endforeach; ?>
        </div>
    </div>
</section>

<!-- چرا HAvoice -->
<section class="section">
    <div class="container">
        <?= section_head($site['why'],'section-head--center') ?>
        <div class="grid grid--4">
            <?php foreach((array)$site['why']['items'] as $item): ?>
                <article class="card feature-card reveal">
                    <span class="feature-card__icon" data-icon="<?= e($item['icon']) ?>" aria-hidden="true"></span>
                    <h3><?= e($item['title']) ?></h3><p><?= e($item['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- روش کار -->
<section class="section section--soft">
    <div class="container method">
        <?= section_head($site['method'],'section-head--center') ?>
        <ol class="method__list">
            <?php foreach((array)$site['method']['steps'] as $step): ?>
                <li class="reveal"><h3><?= e($step['title']) ?></h3><p><?= e($step['text']) ?></p></li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<!-- تمرین‌ها -->
<section class="section section--dark">
    <div class="container">
        <?= section_head(['eyebrow'=>'ابزار تمرین','title'=>$site['exercise_teaser']['title'],'lead'=>$site['exercise_teaser']['lead'],'action'=>['label'=>'شروع تمرین‌ها','url'=>url('exercises')]],'section-head--invert') ?>
        <div class="grid grid--3">
            <?php foreach($firstEx as $ex): ?><div class="reveal"><?= exercise_card($ex, url('exercises')) ?></div><?php endforeach; ?>
        </div>
    </div>
</section>

<!-- کتاب‌ها -->
<section class="section">
    <div class="container">
        <?= section_head(['eyebrow'=>'کتاب','title'=>'کتاب و خلاصه کتاب — برداشتِ کاربردی','lead'=>'معرفی و خلاصه‌ی کتاب‌های شاخصِ هر حوزه با سه برداشتِ قابلِ اجرا.','action'=>['label'=>'همه‌ی کتاب‌ها','url'=>url('books')]]) ?>
        <div class="grid grid--3">
            <?php foreach($books as $book): ?><div class="reveal"><?= book_card($book) ?></div><?php endforeach; ?>
        </div>
    </div>
</section>

<!-- پژوهش -->
<section class="section section--soft">
    <div class="container">
        <?= section_head(['eyebrow'=>'پژوهش','title'=>'تحقیقات و یادداشت‌های پژوهشی','lead'=>'مرورِ منابع با ذکرِ دقیقِ رفرنس؛ قابلِ راستی‌آزمایی و بدونِ ادعای بی‌منبع.','action'=>['label'=>'همه‌ی پژوهش‌ها','url'=>url('research')]]) ?>
        <div class="grid grid--2">
            <?php foreach($research as $r): ?><div class="reveal"><?= research_card($r) ?></div><?php endforeach; ?>
        </div>
    </div>
</section>

<!-- نکته‌ها -->
<section class="section">
    <div class="container">
        <?= section_head(['eyebrow'=>'یک دقیقه، یک تغییر','title'=>$site['tips_teaser']['title'],'lead'=>$site['tips_teaser']['lead'],'action'=>['label'=>'همه‌ی نکته‌ها','url'=>url('tips')]]) ?>
        <div class="grid grid--3">
            <?php foreach($firstTips as $tip): ?><div class="reveal"><?= tip_card($tip) ?></div><?php endforeach; ?>
        </div>
    </div>
</section>

<!-- معرفی مدرس -->
<section class="section">
    <div class="container">
        <div class="instructor reveal">
            <div class="instructor__media">
                <div class="instructor__avatar" aria-hidden="true">ح</div>
                <span class="instructor__badge"><?= e($instructor['role'] ?? 'مدرس و پژوهشگر') ?></span>
            </div>
            <div class="instructor__text">
                <p class="instructor__role"><?= e($instructor['role'] ?? '') ?></p>
                <h2 class="instructor__name"><?= e($instructor['name'] ?? HA_NAME) ?></h2>
                <p class="instructor__bio"><?= e($instructor['bio'] ?? '') ?></p>
                <ul class="instructor__points">
                    <?php foreach((array)($instructor['points']??[]) as $pt): ?>
                        <li><span class="tick" aria-hidden="true"><?= ha_icon('check', 12) ?></span><span><?= e($pt) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <?php if(!empty($instructor['note'])): ?><p class="instructor__note"><?= e($instructor['note']) ?></p><?php endif; ?>
                <div class="btn-row" style="margin-top:1rem">
                    <a class="btn btn--primary" href="<?= e(url('about')) ?>">بیشتر درباره مدرس</a>
                    <a class="btn btn--ghost" href="<?= e(url('contact')) ?>">دعوت به همکاری</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section">
    <div class="container">
        <div class="cta-band reveal">
            <div class="cta-band__text">
                <h2><?= e($site['cta_band']['title']) ?></h2>
                <p><?= e($site['cta_band']['text']) ?></p>
            </div>
            <div class="cta-band__actions">
                <a class="btn btn--primary btn--lg" href="<?= e(url((string)$site['cta_band']['primary']['route'], ['slug'=>(string)$site['cta_band']['primary']['slug']])) ?>"><?= e($site['cta_band']['primary']['label']) ?></a>
                <a class="btn btn--ghost btn--lg" href="<?= e(url((string)$site['cta_band']['ghost']['route'])) ?>"><?= e($site['cta_band']['ghost']['label']) ?></a>
            </div>
        </div>
    </div>
</section>
