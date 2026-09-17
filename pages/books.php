<?php
/**
 * HAvoice 2.0 — کتاب‌ها (فهرست + جزئیات)
 *
 * صفحه‌ی کتاب: معرفی، خلاصه و برداشت، برداشت‌های کلیدی، و در صورتِ
 * ثبتِ مدیر: تصویرِ جلد، فایلِ کامل (PDF)، پیوندِ بیرونی، دوره‌ی مرتبط
 * و «مطالعه‌ی درون‌سایت» (متنِ کاملِ بلوک‌بندی‌شده).
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$slug = slugify(param('slug'));
if ($slug!=='') {
    $book = find_book($slug);
    if ($book===null) {
        http_response_code(404);
        echo not_found('کتاب');
        return;
    }
    $cat = find_category(ha_item_field_slug($book));
    $related = related_books($book, 3);
    $bookImage = ha_safe_file_url((string) ($book['image'] ?? ''));
    $bookFile  = ha_safe_file_url((string) ($book['file'] ?? ''));
    $bookLink  = ha_safe_file_url((string) ($book['link'] ?? ''));
    $bookBlocks = (array) ($book['blocks'] ?? []);
    $linkedCourse = null;
    if (!empty($book['course'])) {
        $linkedCourse = find_course((string) $book['course']);
    }
    ?>
    <article class="section section--tight">
        <div class="container container--narrow">
            <?= breadcrumbs([['label'=>'کتاب‌ها','url'=>url('books')],['label'=>$book['title']]]) ?>
            <div class="detail-grid" style="<?= e(card_style($cat)) ?>">
                <div class="detail-grid__aside">
                    <div class="book-card__cover book-detail__cover<?= $bookImage !== '' ? ' book-card__cover--img' : '' ?>" role="img" aria-label="جلدِ <?= e($book['title']) ?>">
                        <?php if ($bookImage !== ''): ?>
                            <img src="<?= e($bookImage) ?>" alt="" loading="lazy" decoding="async">
                        <?php else: ?>
                            <?= ha_icon('book', 38) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="detail-grid__main">
                    <p class="eyebrow"><?= e($cat['title']?? $book['category']) ?></p>
                    <h1><?= e($book['title']) ?></h1>
                    <p class="muted-sm">نویسنده: <?= e($book['author']??'') ?><?= !empty($book['translator']) ? ' · مترجم: ' . e($book['translator']) : '' ?> · <?= e($book['date_fa']??'') ?> · <?= minutes_label((int)($book['minutes']??5)) ?></p>
                    <p class="lead mt-sm"><?= e($book['excerpt']??'') ?></p>
                    <?php if(!empty($book['summary'])): ?><p><?= e($book['summary']) ?></p><?php endif; ?>

                    <div class="btn-row mt-sm">
                        <?php if ($bookBlocks !== []): ?>
                            <a class="btn btn--primary btn--sm" href="<?= e(url('books', ['slug' => $book['slug']])) ?>#book-read"><?= ha_icon('book', 14) ?> مطالعه در سایت</a>
                        <?php endif; ?>
                        <?php if ($bookFile !== ''): ?>
                            <a class="btn btn--ghost btn--sm" href="<?= e(url('books', ['slug' => $book['slug']])) ?>#book-file"><?= ha_icon('book', 14) ?> مطالعه‌ی فایل در سایت</a>
                            <a class="btn btn--ghost btn--sm" href="<?= e($bookFile) ?>" rel="noopener" target="_blank"><?= ha_icon('download', 14) ?> دریافتِ فایلِ کتاب</a>
                        <?php endif; ?>
                        <?php if ($bookLink !== ''): ?>
                            <a class="btn btn--ghost btn--sm" href="<?= e($bookLink) ?>" rel="noopener nofollow" target="_blank"><?= ha_icon('external', 14) ?> صفحه‌ی ناشر/منبع</a>
                        <?php endif; ?>
                    </div>

                    <?php if(!empty($book['lessons'])): ?>
                        <div class="sub-section">
                            <div class="sub-section__head">
                                <h2 class="sub-section__title"><?= e(count((array)$book['lessons']) > 3 ? 'برداشت‌های کاربردی' : 'سه برداشتِ کاربردی') ?></h2>
                            </div>
                            <ul class="rich-list rich-list--num">
                                <?php foreach((array)$book['lessons'] as $l): ?><li><?= e($l) ?></li><?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($linkedCourse !== null): ?>
                        <div class="card side-card mt-md">
                            <h2><?= ha_icon('steps', 15) ?> دوره‌ی مرتبط با این کتاب</h2>
                            <p class="muted-sm">یادگیریِ این کتاب با تمرین‌های «<?= e($linkedCourse['title'] ?? '') ?>» کامل می‌شود.</p>
                            <a class="btn btn--ghost btn--sm" href="<?= e(url('course', ['slug' => (string) ($linkedCourse['slug'] ?? '')])) ?>">ورود به دوره</a>
                        </div>
                    <?php endif; ?>

                    <?= tag_list((array)($book['tags']??[])) ?>

                    <div class="btn-row mt-md">
                        <a class="btn btn--primary" href="<?= e(url('books')) ?>"><?= ha_icon('arrow-right', 15) ?> بازگشت به کتاب‌ها</a>
                        <button class="btn btn--ghost" type="button" data-copy-link><?= ha_icon('copy', 15) ?> کپی نشانی</button>
                    </div>
                </div>
            </div>

            <?php if ($bookFile !== ''): ?>
                <?php /* فایلِ PDF کتاب، داخلِ سایت خوانده می‌شود (iframe از همین دامنه؛
                         روی موبایل هم از عرض بیرون نمی‌زند و دکمه‌ی «تبِ جدید/دریافت»
                         برای مرورگرهایی که نمایشِ داخلی ندارند همیشه هست). */ ?>
                <section class="book-read" id="book-file" aria-label="فایل کتاب">
                    <header class="book-read__head">
                        <h2><?= ha_icon('book', 17) ?> فایلِ <?= e($book['title']) ?> در سایت</h2>
                        <p class="muted-sm">همین‌جا ورق بزنید؛ نیازی به دانلود نیست.</p>
                    </header>
                    <?= pdf_viewer($bookFile, (string) $book['title'], 'اگر مرورگرِ شما PDF را داخلِ صفحه نشان نمی‌دهد، از دکمه‌ی «تبِ جدید» یا «دریافت» استفاده کنید.') ?>
                </section>
            <?php endif; ?>

            <?php if ($bookBlocks !== []): ?>
                <section class="book-read" id="book-read" aria-label="متن کامل کتاب">
                    <header class="book-read__head">
                        <h2><?= ha_icon('book', 17) ?> مطالعه‌ی <?= e($book['title']) ?> در سایت</h2>
                        <p class="muted-sm">متنِ کاملِ ثبت‌شده توسط مدیر؛ بدونِ نیاز به دانلود.</p>
                    </header>
                    <div class="prose book-read__body">
                        <?= render_blocks($bookBlocks) ?>
                    </div>
                </section>
            <?php endif; ?>

            <?= ha_interaction_block('book', (string)($book['slug'] ?? $slug)) ?>

            <?php if($related): ?>
                <section class="sub-section">
                    <div class="sub-section__head">
                        <h2 class="sub-section__title">کتاب‌های مرتبط</h2>
                        <span class="sub-section__count"><?= fa_num(count($related)) ?> کتاب</span>
                    </div>
                    <div class="grid grid--3">
                        <?php foreach($related as $rb): ?><div class="reveal"><?= book_card($rb) ?></div><?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </article>
    <?php
    return;
}

$all = books();
$cats = categories();
$filter = param('category');
if($filter!=='' && find_category($filter)===null) $filter='';
$list = $filter ? books_by_category($filter) : $all;
?>
<section class="section section--tight">
    <div class="container">
        <div class="filter-tabs" aria-label="فیلتر حوزه">
            <a class="chip<?= $filter===''?' is-active':'' ?>" href="<?= e(url('books')) ?>">همه</a>
            <?php foreach($cats as $cat):
                $cnt = count(books_by_category((string) ($cat['slug'] ?? '')));
                if($cnt===0) continue;
            ?>
                <a class="chip<?= $filter=== $cat['slug']?' is-active':'' ?>" href="<?= e(url('books',['category'=>$cat['slug']])) ?>"><?= e($cat['title']) ?> <span class="chip__n"><?= fa_num($cnt) ?></span></a>
            <?php endforeach; ?>
        </div>

        <?php if($list===[]): ?>
            <?= empty_state('کتابی در این حوزه نیست','حوزه‌ی دیگری را ببینید.', url('books'),'همه‌ی کتاب‌ها') ?>
        <?php else: ?>
            <div class="grid grid--2">
                <?php foreach($list as $book): ?><div class="reveal"><?= book_card($book) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
