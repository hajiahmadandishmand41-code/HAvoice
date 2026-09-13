<?php
/**
 * HAvoice 2.0 — تحقیقات (فهرست + جزئیات)
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$slug = slugify(param('slug'));
if ($slug!=='') {
    $item = find_research($slug);
    if ($item===null) {
        http_response_code(404);
        echo not_found('پژوهش');
        return;
    }
    $cat = find_category($item['category'] ?? '');
    $related = related_research($item, 2);
    ?>
    <article class="article">
        <header class="article__head">
            <div class="container container--narrow">
                <?= breadcrumbs([['label'=>'پژوهش','url'=>url('research')],['label'=>$item['title']]]) ?>
                <p class="eyebrow"><?= e($cat['title'] ?? $item['category']) ?> · پژوهش</p>
                <h1 class="article__title"><?= e($item['title']) ?></h1>
                <p class="article__excerpt"><?= e($item['summary'] ?? '') ?></p>
                <div class="article__meta">
                    <span class="chip chip--soft"><?= e(HA_NAME) ?></span>
                    <time datetime="<?= e($item['date_fa']??'') ?>"><?= e($item['date_fa']??'') ?></time>
                    <span class="meta-dot" aria-hidden="true"></span>
                    <span><?= e($cat['title']??'') ?></span>
                </div>
            </div>
        </header>
        <div class="container container--narrow article__grid">
            <div class="prose">
                <?= render_blocks((array)($item['blocks'] ?? [])) ?>
                <?php if(!empty($item['refs'])): ?>
                    <section class="card side-card mt-md">
                        <h2>منابع</h2>
                        <ol class="rich-list rich-list--num">
                            <?php foreach((array)$item['refs'] as $ref): ?><li><?= e($ref) ?></li><?php endforeach; ?>
                        </ol>
                        <p class="muted-sm">امکانِ افزودنِ DOI/لینکِ منبع در همین بخش فراهم است.</p>
                    </section>
                <?php endif; ?>
                <?= tag_list((array)($item['tags'] ?? [])) ?>
            </div>
            <aside class="article__side">
                <div class="card side-card">
                    <h2>قدمِ بعدی</h2>
                    <p>این پژوهش را با تمرینِ مرتبط ببندید.</p>
                    <a class="btn btn--primary btn--block btn--sm" href="<?= e(url('exercises')) ?>">تمرین‌های مرتبط</a>
                    <a class="btn btn--ghost btn--block btn--sm" href="<?= e(url('research')) ?>">همه‌ی پژوهش‌ها</a>
                </div>
                <div class="card side-card" data-share-card>
                    <h2>اشتراک‌گذاری</h2>
                    <button class="btn btn--ghost btn--sm btn--block" type="button" data-copy-link>کپی نشانی</button>
                </div>
            </aside>
        </div>
        <?php if($related): ?>
            <section class="section section--soft">
                <div class="container">
                    <?= section_head(['title'=>'پژوهش‌های مرتبط']) ?>
                    <div class="grid grid--2"><?php foreach($related as $r): ?><div><?= research_card($r) ?></div><?php endforeach; ?></div>
                </div>
            </section>
        <?php endif; ?>
    </article>
    <?php
    return;
}

$all = research_items();
$cats = categories();
$filter = param('category');
if($filter!=='' && find_category($filter)===null) $filter='';
$list = $filter ? array_values(array_filter($all, fn($r)=>($r['category']??'')=== $filter)) : $all;
?>
<section class="section section--tight">
    <div class="container">
        <div class="filter-tabs" aria-label="فیلتر حوزه">
            <a class="chip<?= $filter===''?' is-active':'' ?>" href="<?= e(url('research')) ?>">همه</a>
            <?php foreach($cats as $cat):
                $cnt = count(array_filter($all, fn($r)=>($r['category']??'')=== $cat['slug']));
                if($cnt===0) continue;
            ?>
                <a class="chip<?= $filter=== $cat['slug']?' is-active':'' ?>" href="<?= e(url('research',['category'=>$cat['slug']])) ?>"><?= e($cat['title']) ?> <span class="chip__n"><?= fa_num($cnt) ?></span></a>
            <?php endforeach; ?>
        </div>

        <?php if($list===[]): ?>
            <?= empty_state('پژوهشی در این حوزه نیست','حوزه‌ی دیگری را ببینید.', url('research'),'همه‌ی پژوهش‌ها') ?>
        <?php else: ?>
            <div class="grid grid--2">
                <?php foreach($list as $item): ?><div class="reveal"><?= research_card($item) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h2>الگوی نگارشِ پژوهشی</h2>
            <p class="muted-sm">هر پژوهش شامل: عنوان، خلاصه، متنِ بلوک‌بندی‌شده، منابعِ قابلِ راستی‌آزمایی، تاریخِ انتشار و موضوع است — همان‌طور که در <code>data/research.php</code> تعریف شده.</p>
        </div>
    </div>
</section>
