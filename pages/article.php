<?php
/**
 * HAvoice — صفحه‌ی اختصاصی هر مقاله.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$slug = (string) $GLOBALS['HA_SLUG'];

list(, $article) = find_by_slug(all_articles_sorted(), $slug);

if ($article === null) {
    http_response_code(404);
    echo not_found('مقاله‌ی «' . ($slug !== '' ? $slug : 'نامشخص') . '»');

    return;
}

$related = related_articles($article, 3);
$site    = data('site');
?>

<article class="article">
    <header class="article__head">
        <div class="container container--narrow">
            <nav class="breadcrumbs" aria-label="مسیر صفحه">
                <a href="<?= e(url('home')) ?>">خانه</a>
                <span aria-hidden="true">/</span>
                <a href="<?= e(url('articles')) ?>">مقاله‌ها</a>
                <span aria-hidden="true">/</span>
                <a href="<?= e(url('articles', ['category' => (string) ($article['category'] ?? '')])) ?>"><?= e($article['category'] ?? '') ?></a>
            </nav>

            <h1 class="article__title"><?= e($article['title']) ?></h1>
            <p class="article__excerpt"><?= e($article['excerpt']) ?></p>

            <div class="article__meta">
                <span class="chip chip--soft"><?= e(HA_NAME) ?></span>
                <time datetime="<?= e($article['date'] ?? '') ?>"><?= e($article['date_fa'] ?? '') ?></time>
                <span class="meta-dot" aria-hidden="true"></span>
                <span><?= e(minutes_label((int) ($article['minutes'] ?? 5))) ?> مطالعه</span>
            </div>
        </div>
    </header>

    <div class="container container--narrow article__grid">
        <div class="prose">
            <?= render_blocks((array) ($article['blocks'] ?? [])) ?>
            <?= tag_list((array) ($article['tags'] ?? [])) ?>
        </div>

        <aside class="article__side">
            <div class="card side-card">
                <h2>قدم بعدی</h2>
                <p>خواندن کافی نیست؛ این مقاله را با یک تمرین دو‌دقیقه‌ای ببندید.</p>
                <a class="btn btn--primary btn--block btn--sm" href="<?= e(url('exercises')) ?>">انجام تمرین مرتبط</a>
                <a class="btn btn--ghost btn--block btn--sm" href="<?= e(url('lesson', ['slug' => 'breathing-foundations'])) ?>">درس اول مسیر آموزشی</a>
            </div>

            <div class="card side-card" data-share-card>
                <h2>اشتراک‌گذاری</h2>
                <p class="muted-sm">نشانی این صفحه را کپی کنید و بفرستید.</p>
                <button class="btn btn--ghost btn--sm btn--block" type="button" data-copy-link>
                    کپی نشانی صفحه
                </button>
            </div>
        </aside>
    </div>

<?php if ($related !== []): ?>
    <section class="section section--soft">
        <div class="container">
            <?= section_head(['title' => 'مقاله‌های مرتبط']) ?>
            <div class="grid grid--3">
<?php foreach ($related as $item): ?>
                <div><?= article_card($item) ?></div>
<?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
</article>
