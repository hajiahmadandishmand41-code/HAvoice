<?php
/**
 * HAvoice 2.0 — کتاب‌ها (فهرست + جزئیات)
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
    $cat = find_category($book['category'] ?? '');
    $related = related_books($book, 3);
    ?>
    <article class="section section--tight">
        <div class="container container--narrow">
            <?= breadcrumbs([['label'=>'کتاب‌ها','url'=>url('books')],['label'=>$book['title']]]) ?>
            <div class="detail-grid" style="<?= e(card_style($cat)) ?>">
                <div class="detail-grid__aside">
                    <div class="book-card__cover book-detail__cover" role="img" aria-label="جلدِ <?= e($book['title']) ?>">
                        <?= ha_icon('book', 38) ?>
                    </div>
                </div>
                <div class="detail-grid__main">
                    <p class="eyebrow"><?= e($cat['title']?? $book['category']) ?></p>
                    <h1><?= e($book['title']) ?></h1>
                    <p class="muted-sm">نویسنده: <?= e($book['author']??'') ?> · <?= e($book['date_fa']??'') ?> · <?= minutes_label((int)($book['minutes']??5)) ?></p>
                    <p class="lead mt-sm"><?= e($book['excerpt']??'') ?></p>
                    <?php if(!empty($book['summary'])): ?><p><?= e($book['summary']) ?></p><?php endif; ?>
                    <?php if(!empty($book['lessons'])): ?>
                        <div class="sub-section">
                            <div class="sub-section__head">
                                <h2 class="sub-section__title">سه برداشتِ کاربردی</h2>
                            </div>
                            <ul class="rich-list rich-list--num">
                                <?php foreach((array)$book['lessons'] as $l): ?><li><?= e($l) ?></li><?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?= tag_list((array)($book['tags']??[])) ?>
                    <div class="btn-row mt-md">
                        <a class="btn btn--primary" href="<?= e(url('books')) ?>"><?= ha_icon('arrow-right', 15) ?> بازگشت به کتاب‌ها</a>
                        <button class="btn btn--ghost" type="button" data-copy-link><?= ha_icon('copy', 15) ?> کپی نشانی</button>
                    </div>
                </div>
            </div>
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
$list = $filter ? array_values(array_filter($all, fn($b)=>($b['category']??'')=== $filter)) : $all;
?>
<section class="section section--tight">
    <div class="container">
        <div class="filter-tabs" aria-label="فیلتر حوزه">
            <a class="chip<?= $filter===''?' is-active':'' ?>" href="<?= e(url('books')) ?>">همه</a>
            <?php foreach($cats as $cat):
                $cnt = count(array_filter($all, fn($b)=>($b['category']??'')=== $cat['slug']));
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
