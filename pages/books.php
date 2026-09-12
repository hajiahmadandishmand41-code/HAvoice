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
            <div class="book-detail" style="display:grid; grid-template-columns:110px 1fr; gap:1.4rem; align-items:start; margin-top:1rem">
                <div class="book-card__cover" aria-hidden="true" style="width:110px; height:156px"><span>📚</span></div>
                <div>
                    <p class="eyebrow"><?= e($cat['title']?? $book['category']) ?></p>
                    <h1 style="margin:.2rem 0 .3rem"><?= e($book['title']) ?></h1>
                    <p class="muted-sm">نویسنده: <?= e($book['author']??'') ?> · <?= e($book['date_fa']??'') ?> · <?= minutes_label((int)($book['minutes']??5)) ?></p>
                    <p class="lead" style="margin-top:.8rem"><?= e($book['excerpt']??'') ?></p>
                    <p><?= e($book['summary']??'') ?></p>
                    <?php if(!empty($book['lessons'])): ?>
                        <h2>سه برداشتِ کاربردی</h2>
                        <ul class="rich-list">
                            <?php foreach((array)$book['lessons'] as $l): ?><li><?= e($l) ?></li><?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?= tag_list((array)($book['tags']??[])) ?>
                    <div class="btn-row" style="margin-top:1.2rem">
                        <a class="btn btn--primary" href="<?= e(url('books')) ?>">بازگشت به کتاب‌ها</a>
                        <button class="btn btn--ghost" type="button" data-copy-link>کپی نشانی</button>
                    </div>
                </div>
            </div>
            <?php if($related): ?>
                <section style="margin-top:2.4rem">
                    <h2>کتاب‌های مرتبط</h2>
                    <div class="grid grid--3" style="margin-top:1rem">
                        <?php foreach($related as $rb): ?><div><?= book_card($rb) ?></div><?php endforeach; ?>
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
