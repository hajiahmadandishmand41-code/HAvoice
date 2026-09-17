<?php
/**
 * HAvoice 2.0 — صفحه‌ی مقاله (علمی/آموزشی)
 *
 * علاوه بر متنِ بلوک‌بندی‌شده، مشخصاتِ علمیِ مقاله نمایش داده می‌شود:
 * نویسنده، منبع/نشریه (+ نشانی)، فهرستِ مراجع، تصویرِ جلد و فایلِ پیوست —
 * همه فقط با تأیید و ذخیره‌ی مدیر (هیچ ورودِ خودکاری از بیرون نیست).
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$slug = (string)$GLOBALS['HA_SLUG'];
list(,$article) = find_by_slug(all_articles_sorted(), $slug);

if ($article===null){
    http_response_code(404);
    echo not_found('مقاله‌ی «'.($slug!==''?$slug:'نامشخص').'»');
    return;
}

$related = related_articles($article,3);

/* حوزه: اول اتصالِ مستقیم (field)، بعد نگاشتِ برچسبِ فارسی */
$cat = null;
$fieldSlug = ha_item_field_slug($article);
if ($fieldSlug !== '') {
    $cat = find_category($fieldSlug);
}
if ($cat === null) {
    foreach(categories() as $c){ if(mb_strpos($article['category']??'',$c['short'])!==false || ($article['category']??'')=== $c['title']) {$cat=$c; break;} }
}

$author     = trim((string) ($article['author'] ?? ''));
$sourceName = trim((string) ($article['source_name'] ?? ''));
$sourceUrl  = ha_safe_file_url((string) ($article['source_url'] ?? ''));
$refs       = array_values(array_filter(array_map('strval', (array) ($article['refs'] ?? []))));
$coverImage = ha_safe_file_url((string) ($article['image'] ?? ''));
$attachFile = ha_safe_file_url((string) ($article['file'] ?? ''));
?>

<article class="article">
    <header class="article__head">
        <div class="container container--narrow">
            <?= breadcrumbs([['label'=>'مقاله‌ها','url'=>url('articles')],['label'=>$article['category']??'','url'=>url('articles',['category'=>$article['category']??''])],['label'=>$article['title']]]) ?>
            <h1 class="article__title"><?= e($article['title']) ?></h1>
            <p class="article__excerpt"><?= e($article['excerpt']) ?></p>
            <div class="article__meta">
                <span class="chip chip--soft"><?= $author !== '' ? ha_icon('user', 12) . ' ' . e($author) : e(ha_site_name()) ?></span>
                <time datetime="<?= e($article['date']??'') ?>"><?= e($article['date_fa']??'') ?></time>
                <span class="meta-dot" aria-hidden="true"></span>
                <span><?= e(minutes_label((int)($article['minutes']??5))) ?> مطالعه</span>
                <?php if($cat): ?><span class="badge"><?= e($cat['short']) ?></span><?php endif; ?>
            </div>
            <?php if ($sourceName !== ''): ?>
                <p class="article__source">
                    <?= ha_icon('research', 13) ?>
                    منبع/نشریه:
                    <?php if ($sourceUrl !== ''): ?>
                        <a href="<?= e($sourceUrl) ?>" rel="noopener nofollow" target="_blank"><?= e($sourceName) ?></a>
                    <?php else: ?>
                        <?= e($sourceName) ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($coverImage !== ''): ?>
        <div class="container container--narrow">
            <figure class="article-cover">
                <img src="<?= e($coverImage) ?>" alt="<?= e($article['title']) ?>" loading="lazy" decoding="async">
            </figure>
        </div>
    <?php endif; ?>

    <div class="container container--narrow article__grid">
        <div class="prose">
            <?= render_blocks((array)($article['blocks']??[])) ?>

            <?php if ($attachFile !== ''): ?>
                <p class="article__attachment">
                    <a class="btn btn--ghost btn--sm" href="<?= e($attachFile) ?>" rel="noopener" target="_blank"><?= ha_icon('download', 14) ?> دریافتِ فایلِ پیوستِ مقاله</a>
                </p>
            <?php endif; ?>

            <?php if ($refs !== []): ?>
                <section class="card side-card mt-md article__refs">
                    <h2><?= ha_icon('research', 15) ?> منابع و مراجع</h2>
                    <ol class="rich-list rich-list--num">
                        <?php foreach ($refs as $ref): ?><li><?= e($ref) ?></li><?php endforeach; ?>
                    </ol>
                </section>
            <?php endif; ?>

            <?= tag_list((array)($article['tags']??[])) ?>
            <?= ha_interaction_block('article', (string)($article['slug'] ?? $slug)) ?>
        </div>
        <aside class="article__side">
            <div class="card side-card">
                <h2>قدم بعدی</h2>
                <p>خواندن کافی نیست؛ این مقاله را با یک تمرینِ دو‌دقیقه‌ای ببندید.</p>
                <a class="btn btn--primary btn--block btn--sm" href="<?= e(url('exercises')) ?>">انجام تمرین مرتبط</a>
                <a class="btn btn--ghost btn--block btn--sm" href="<?= e(url('lesson',['slug'=>'breathing-foundations'])) ?>">درس اولِ مسیر</a>
            </div>
            <div class="card side-card" data-share-card>
                <h2>اشتراک‌گذاری</h2>
                <p class="muted-sm">نشانی این صفحه را کپی کنید.</p>
                <button class="btn btn--ghost btn--sm btn--block" type="button" data-copy-link>کپی نشانی صفحه</button>
            </div>
            <?php if($cat): ?>
                <div class="card side-card">
                    <h2>حوزه: <?= e($cat['title']) ?></h2>
                    <p class="muted-sm"><?= e($cat['description']) ?></p>
                    <a class="btn btn--ghost btn--sm btn--block" href="<?= e(url('category',['slug'=>$cat['slug']])) ?>">ورود به حوزه</a>
                </div>
            <?php endif; ?>
        </aside>
    </div>

    <?php if($related!==[]): ?>
    <section class="section section--soft">
        <div class="container">
            <?= section_head(['title'=>'مقاله‌های مرتبط']) ?>
            <div class="grid grid--3"><?php foreach($related as $item): ?><div><?= article_card($item) ?></div><?php endforeach; ?></div>
        </div>
    </section>
    <?php endif; ?>
</article>
