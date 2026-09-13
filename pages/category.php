<?php
/**
 * HAvoice — صفحه‌ی حوزه (دسته)
 *
 * اگر slug داده نشود ⇒ گالریِ همه‌ی حوزه‌ها.
 * اگر slug معتبر باشد ⇒ صفحه‌ی اختصاصیِ همان حوزه با محتوای فیلترشده.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$slug = slugify((string) ($GLOBALS['HA_SLUG'] ?? param('slug')));
$cat  = $slug !== '' ? find_category($slug) : null;

/* ---------- حالت ۱: فهرستِ همه‌ی حوزه‌ها ---------- */
if ($cat === null) {
    $cats = categories();
    ?>
    <section class="section section--tight">
        <div class="container">
            <?php if ($cats === []): ?>
                <?= empty_state('حوزه‌ای تعریف نشده است', 'به‌زودی حوزه‌های آموزشی اضافه می‌شود.', url('home'), 'صفحه‌ی اصلی', 'compass') ?>
            <?php else: ?>
                <div class="grid category-grid">
                    <?php foreach ($cats as $c): ?>
                        <div class="reveal"><?= category_card($c) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return;
}

/* ---------- حالت ۲: صفحه‌ی یک حوزه ---------- */
$catSlug = (string) ($cat['slug'] ?? '');

$coursesInCat  = array_values(array_filter(courses(), static fn($c) => ($c['category'] ?? '') === $catSlug));
$videosInCat   = media_by_category($catSlug, 'video');
$audiosInCat   = media_by_category($catSlug, 'audio');
$booksInCat    = books_by_category($catSlug);
$researchInCat = research_by_category($catSlug);
$lessonsInCat  = lessons_by_category($catSlug);

/* مقاله‌ها موضوعِ فارسیِ آزاد دارند نه slugِ حوزه؛ با find_category_by_title()
   موضوع را به حوزه نگاشت می‌کنیم تا فهرست واقعاً مرتبط باشد.
   (نسخه‌ی پیشین یک فیلترِ `return true` داشت — یعنی همه‌ی مقاله‌ها
   بدونِ هیچ ارتباطی زیرِ عنوانِ «مقالاتِ این حوزه» چاپ می‌شدند.) */
$articlesInCat = array_values(array_filter(all_articles_sorted(), static function ($a) use ($catSlug) {
    $mapped = find_category_by_title((string) ($a['category'] ?? ''));
    return $mapped !== null && (string) ($mapped['slug'] ?? '') === $catSlug;
}));

$style = card_style($cat);
?>
<section class="section section--tight">
    <div class="container">

        <header class="topic-hero" style="<?= e($style) ?>">
            <?= breadcrumbs([['label' => 'حوزه‌ها', 'url' => url('category')], ['label' => (string) ($cat['title'] ?? '')]]) ?>
            <div class="topic-hero__top">
                <span class="topic-hero__icon" aria-hidden="true"><?= ha_icon((string) ($cat['icon'] ?? 'compass'), 26) ?></span>
                <div class="topic-hero__heading">
                    <p class="eyebrow">حوزه‌ی آموزشی</p>
                    <h1><?= e($cat['title'] ?? '') ?></h1>
                </div>
            </div>
            <p class="lead"><?= e($cat['description'] ?? '') ?></p>
            <div class="topic-hero__stats">
                <span><strong><?= fa_num(count($coursesInCat)) ?></strong> دوره</span>
                <span><strong><?= fa_num(count($lessonsInCat)) ?></strong> درس</span>
                <span><strong><?= fa_num(count($articlesInCat)) ?></strong> مقاله</span>
                <span><strong><?= fa_num(count($videosInCat) + count($audiosInCat)) ?></strong> رسانه</span>
                <span><strong><?= fa_num(count($booksInCat)) ?></strong> کتاب</span>
            </div>
            <div class="btn-row">
                <?php if ($coursesInCat !== []): ?>
                    <a class="btn btn--primary btn--sm" href="<?= e(url('courses', ['category' => $catSlug])) ?>"><?= ha_icon('steps', 14) ?> دوره‌های این حوزه</a>
                <?php endif; ?>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('category')) ?>">همه‌ی حوزه‌ها</a>
            </div>
        </header>

        <?php if ($coursesInCat !== []): ?>
            <section class="sub-section">
                <div class="sub-section__head">
                    <h2 class="sub-section__title">دوره‌های <?= e($cat['title'] ?? '') ?></h2>
                    <span class="sub-section__count"><?= fa_num(count($coursesInCat)) ?> دوره</span>
                </div>
                <div class="grid grid--3">
                    <?php foreach ($coursesInCat as $course): ?>
                        <div class="reveal"><?= course_card($course) ?></div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($lessonsInCat !== []): ?>
            <section class="sub-section">
                <div class="sub-section__head">
                    <h2 class="sub-section__title">درس‌های این حوزه</h2>
                    <span class="sub-section__count"><?= fa_num(count($lessonsInCat)) ?> درس</span>
                </div>
                <ol class="lesson-list">
                    <?php foreach (array_slice($lessonsInCat, 0, 8) as $info): ?>
                        <?= lesson_row($info['lesson'], (int) $info['stageIndex'], (int) $info['lessonIndex'], $info['stage']) ?>
                    <?php endforeach; ?>
                </ol>
                <?php if (count($lessonsInCat) > 8): ?>
                    <a class="link-arrow" href="<?= e(url('courses', ['category' => $catSlug])) ?>">دیدنِ همه‌ی <?= fa_num(count($lessonsInCat)) ?> درس</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($articlesInCat !== []): ?>
            <section class="sub-section">
                <div class="sub-section__head">
                    <h2 class="sub-section__title">مقاله‌های این حوزه</h2>
                    <a class="link-arrow" href="<?= e(url('articles')) ?>">همه‌ی مقاله‌ها</a>
                </div>
                <div class="grid grid--3">
                    <?php foreach (array_slice($articlesInCat, 0, 3) as $art): ?>
                        <div class="reveal"><?= article_card($art) ?></div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($videosInCat !== []): ?>
            <section class="sub-section">
                <div class="sub-section__head">
                    <h2 class="sub-section__title">ویدیوها</h2>
                    <a class="link-arrow" href="<?= e(url('videos', ['category' => $catSlug])) ?>">همه‌ی ویدیوهای این حوزه</a>
                </div>
                <div class="grid grid--3">
                    <?php foreach (array_slice($videosInCat, 0, 3) as $v): ?>
                        <div class="reveal"><?= video_card($v) ?></div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($audiosInCat !== []): ?>
            <section class="sub-section">
                <div class="sub-section__head">
                    <h2 class="sub-section__title">صوت‌ها و پادکست</h2>
                    <a class="link-arrow" href="<?= e(url('audios', ['category' => $catSlug])) ?>">همه‌ی صوت‌ها</a>
                </div>
                <div class="grid grid--3">
                    <?php foreach (array_slice($audiosInCat, 0, 3) as $a): ?>
                        <div class="reveal"><?= audio_card($a) ?></div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($booksInCat !== []): ?>
            <section class="sub-section">
                <div class="sub-section__head">
                    <h2 class="sub-section__title">کتاب‌های مرتبط</h2>
                    <a class="link-arrow" href="<?= e(url('books', ['category' => $catSlug])) ?>">همه‌ی کتاب‌ها</a>
                </div>
                <div class="grid grid--3">
                    <?php foreach (array_slice($booksInCat, 0, 3) as $b): ?>
                        <div class="reveal"><?= book_card($b) ?></div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($researchInCat !== []): ?>
            <section class="sub-section">
                <div class="sub-section__head">
                    <h2 class="sub-section__title">پژوهش‌های مرتبط</h2>
                    <a class="link-arrow" href="<?= e(url('research', ['category' => $catSlug])) ?>">همه‌ی پژوهش‌ها</a>
                </div>
                <div class="grid grid--2">
                    <?php foreach (array_slice($researchInCat, 0, 2) as $r): ?>
                        <div class="reveal"><?= research_card($r) ?></div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php
        $hasAny = $coursesInCat || $lessonsInCat || $articlesInCat || $videosInCat || $audiosInCat || $booksInCat || $researchInCat;
        if (!$hasAny): ?>
            <?= empty_state('هنوز محتوایی در این حوزه منتشر نشده', 'ساختارِ این حوزه آماده است؛ محتوا به‌تدریج افزوده می‌شود. در این میان حوزه‌های دیگر را ببینید.', url('category'), 'همه‌ی حوزه‌ها', 'compass') ?>
        <?php endif; ?>
    </div>
</section>
