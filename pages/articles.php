<?php
/**
 * HAvoice — فهرست مقاله‌ها با فیلتر دسته و جستجوی زنده.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$categories = article_categories();
$current    = param('category');
$articles   = all_articles_sorted();

if ($current !== '' && isset($categories[$current])) {
    $articles = array_values(array_filter($articles, static function (array $a) use ($current) {
        return (string) ($a['category'] ?? '') === $current;
    }));
}

$featured = array_shift($articles);
?>

<section class="section section--tight">
    <div class="container">

        <form class="filter-bar" method="get" action="index.php" role="search" data-search-url="<?= e(url('search')) ?>">
            <input type="hidden" name="p" value="articles">
            <label class="sr-only" for="live-filter">جستجو در عنوان و متن مقاله‌ها</label>
            <div class="filter-bar__field">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="7"/><path d="M20 20l-3.7-3.7"/>
                </svg>
                <input type="search" id="live-filter" data-live-filter placeholder="مثلاً: مکث، تنفس، زبان بدن" autocomplete="off">
            </div>
            <p class="filter-bar__count"><span data-filter-count><?= fa_num(count($articles) + ($featured ? 1 : 0)) ?></span> مقاله نمایش داده می‌شود</p>
        </form>

<?php if ($categories !== []): ?>
        <nav class="chip-row" aria-label="فیلتر موضوعی">
            <a class="chip<?= $current === '' ? ' is-active' : '' ?>" href="<?= e(url('articles')) ?>">همه</a>
<?php foreach ($categories as $category => $count): ?>
            <a class="chip<?= $current === (string) $category ? ' is-active' : '' ?>"
               href="<?= e(url('articles', ['category' => (string) $category])) ?>">
                <?= e($category) ?> <span class="chip__n"><?= fa_num($count) ?></span>
            </a>
<?php endforeach; ?>
        </nav>
<?php endif; ?>

<?php if ($featured !== null && $current === ''): ?>
        <div class="featured reveal" data-filter-item>
            <?= article_card($featured, true) ?>
        </div>
<?php endif; ?>

        <div class="grid grid--3" data-filter-target>
<?php foreach ($articles as $article): ?>
            <div class="reveal" data-filter-item><?= article_card($article) ?></div>
<?php endforeach; ?>
        </div>

        <div class="no-results" data-filter-empty hidden>
            <p>مقاله‌ای با این کلمه پیدا نشد. یک کلمه‌ی کوتاه‌تر را امتحان کنید.</p>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('search')) ?>">جستجوی پیشرفته</a>
        </div>

<?php if ($current !== ''): ?>
        <p class="filter-active">
            نمایش دسته‌ی <strong>«<?= e($current) ?>»</strong> —
            <a href="<?= e(url('articles')) ?>">حذف فیلتر</a>
        </p>
<?php endif; ?>
    </div>
</section>
