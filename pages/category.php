<?php
/**
 * HAvoice 2.0 — صفحه‌ی حوزه (دسته)
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$slug = slugify((string)($GLOBALS['HA_SLUG'] ?? param('slug')));
$cat = $slug ? find_category($slug) : null;

if ($cat===null) {
    // فهرست همه حوزه‌ها
    $cats = categories();
    ?>
    <section class="section section--tight">
        <div class="container">
            <div class="grid category-grid">
                <?php foreach($cats as $c): ?><div class="reveal"><?= category_card($c) ?></div><?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    return;
}

// محتوای مرتبط با حوزه
$catSlug = $cat['slug'];
$coursesInCat = array_values(array_filter(courses(), fn($c)=>($c['category']??'')=== $catSlug));
$articlesInCat = array_values(array_filter(all_articles_sorted(), function($a) use($catSlug){
    // نگاشت: دسته‌ی مقاله به slug حوزه — برای سازگاری، اگر دسته فارسی بود، آن را به حوزه نگاشت می‌کنیم
    // ساده: همه مقالات را اگر دسته‌شان شامل کلمه مرتبط باشد نمایش می‌دهیم؛ فعلاً فقط نمایش 3 تا
    return true;
}));
$articlesInCat = array_slice($articlesInCat,0,3);
$videosInCat = media_by_category($catSlug,'video');
$audiosInCat = media_by_category($catSlug,'audio');
$booksInCat = books_by_category($catSlug);
$researchInCat = research_by_category($catSlug);
$lessonsInCat = lessons_by_category($catSlug);
?>
<section class="section section--tight">
    <div class="container">
        <div class="card" style="border-inline-start:4px solid <?= e($cat['color']) ?>; background:linear-gradient(135deg, var(--surface), var(--surface-2))">
            <?= breadcrumbs([['label'=>'حوزه‌ها','url'=>url('category')],['label'=>$cat['title']]]) ?>
            <p class="eyebrow">حوزه‌ی آموزشی</p>
            <h1 style="margin:.2rem 0 .4rem"><?= e($cat['title']) ?></h1>
            <p class="lead"><?= e($cat['description']) ?></p>
            <div class="btn-row" style="margin-top:1rem">
                <?php if($coursesInCat): ?><a class="btn btn--primary btn--sm" href="<?= e(url('courses',['category'=>$catSlug])) ?>">دوره‌های این حوزه</a><?php endif; ?>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('courses')) ?>">همه‌ی حوزه‌ها</a>
            </div>
        </div>

        <?php if($coursesInCat): ?>
            <section class="section" style="padding-block:2rem 0">
                <h2>دوره‌های <?= e($cat['title']) ?></h2>
                <div class="grid grid--3" style="margin-top:1rem">
                    <?php foreach($coursesInCat as $course): ?><div><?= course_card($course) ?></div><?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if($lessonsInCat): ?>
            <section class="section" style="padding-block:1.6rem 0">
                <h2>درس‌های این حوزه</h2>
                <ol class="lesson-list" style="margin-top:1rem">
                    <?php foreach($lessonsInCat as $info): ?>
                        <?= lesson_row($info['lesson'], (int)$info['stageIndex'], (int)$info['lessonIndex'], $info['stage']) ?>
                    <?php endforeach; ?>
                </ol>
            </section>
        <?php endif; ?>

        <div class="grid grid--2" style="margin-top:2rem">
            <?php if($videosInCat): ?>
                <section>
                    <h2>ویدیوها</h2>
                    <div class="grid" style="margin-top:.8rem; grid-template-columns:1fr">
                        <?php foreach(array_slice($videosInCat,0,2) as $v): ?><div><?= video_card($v) ?></div><?php endforeach; ?>
                    </div>
                    <a class="link-arrow" href="<?= e(url('videos',['category'=>$catSlug])) ?>" style="margin-top:.6rem; display:inline-flex">همه‌ی ویدیوهای این حوزه</a>
                </section>
            <?php endif; ?>
            <?php if($audiosInCat): ?>
                <section>
                    <h2>صوت‌ها</h2>
                    <div class="grid" style="margin-top:.8rem; grid-template-columns:1fr">
                        <?php foreach(array_slice($audiosInCat,0,2) as $a): ?><div><?= audio_card($a) ?></div><?php endforeach; ?>
                    </div>
                    <a class="link-arrow" href="<?= e(url('audios',['category'=>$catSlug])) ?>" style="margin-top:.6rem; display:inline-flex">همه‌ی صوت‌ها</a>
                </section>
            <?php endif; ?>
        </div>

        <?php if($booksInCat): ?>
            <section class="section" style="padding-block:1.6rem 0">
                <h2>کتاب‌های مرتبط</h2>
                <div class="grid grid--2" style="margin-top:1rem">
                    <?php foreach(array_slice($booksInCat,0,2) as $b): ?><div><?= book_card($b) ?></div><?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if($researchInCat): ?>
            <section class="section" style="padding-block:1.6rem 0">
                <h2>پژوهش‌های مرتبط</h2>
                <div class="grid grid--2" style="margin-top:1rem">
                    <?php foreach(array_slice($researchInCat,0,2) as $r): ?><div><?= research_card($r) ?></div><?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="section" style="padding-block:1.6rem 0">
            <h2>مقالاتِ پیشنهادی</h2>
            <div class="grid grid--3" style="margin-top:1rem">
                <?php foreach($articlesInCat as $art): ?><div><?= article_card($art) ?></div><?php endforeach; ?>
            </div>
        </section>
    </div>
</section>
