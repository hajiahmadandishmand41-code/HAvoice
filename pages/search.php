<?php
/**
 * HAvoice — جستجوی مقالات (سمت سرور، بدون دیتابیس).
 * جستجو روی عنوان، خلاصه، دسته، برچسب‌ها و متن کامل مقاله‌ها انجام می‌شود.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$raw      = param('q');
$query    = trim($raw);
$limit    = 20;
$results  = [];
$groups   = [];

if ($query !== '') {
    $results = search_articles($query, data('articles'), $limit);
    // درس‌های دوره هم جستجو می‌شوند
    foreach (course_lesson_index() as $item) {
        $hay = normalize_persian($item['lesson']['title'] . ' ' . ($item['lesson']['goal'] ?? '') . ' ' . article_text_index(['blocks' => (array) ($item['lesson']['blocks'] ?? [])]));
        $hit = false;
        foreach (preg_split('/\s+/', normalize_persian($query)) ?: [] as $word) {
            if (mb_strlen($word, 'UTF-8') >= 2 && mb_strpos($hay, $word, 0, 'UTF-8') !== false) {
                $hit = true;
                break;
            }
        }
        if ($hit) {
            $groups[] = ['type' => 'lesson', 'slug' => $item['lesson']['slug'], 'title' => $item['lesson']['title'], 'text' => (string) ($item['lesson']['goal'] ?? ''), 'tag' => $item['stage']['title'] ?? ''];
        }
    }
}

$foundParts = [];
if ($query !== '') {
    $foundParts[] = fa_num(count($results)) . ' مقاله';
    if ($groups !== []) {
        $foundParts[] = fa_num(count($groups)) . ' درس';
    }
}
$foundLabel = $foundParts === [] ? 'هیچ نتیجه‌ای' : implode(' و ', $foundParts);

$suggestions = ['مکث', 'تنفس', 'تکیه‌کلام', 'زبان بدن', 'اضطراب', 'سؤال دشوار', 'شروع ارائه'];
?>

<section class="section section--tight">
    <div class="container">

        <form class="search-form" method="get" action="index.php" role="search">
            <input type="hidden" name="p" value="search">
            <label class="sr-only" for="q">عبارت جستجو</label>
            <div class="search-form__field">
                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="7"/><path d="M20 20l-3.7-3.7"/>
                </svg>
                <input id="q" type="search" name="q" value="<?= e($query) ?>"
                       placeholder="کلیدواژه، موضوع یا برچسب…" autocomplete="off" autofocus maxlength="100">
                <button class="btn btn--primary" type="submit">جستجو</button>
            </div>
        </form>

<?php if ($query === ''): ?>
        <div class="search-help card">
            <h2>دنبال چه چیزی هستید؟</h2>
            <p>می‌توانید موضوع، مهارت یا یک عبارت را بنویسید؛ جستجو در عنوان، متن و برچسب‌های همه‌ی مقاله‌ها و درس‌ها انجام می‌شود.</p>
            <ul class="chip-row chip-row--inline">
<?php foreach ($suggestions as $s): ?>
                <li><a class="chip" href="<?= e(url('search', ['q' => $s])) ?>"><?= e($s) ?></a></li>
<?php endforeach; ?>
            </ul>
        </div>
<?php else: ?>
        <p class="search-result-info">
            <strong><?= e($foundLabel) ?></strong> برای «<strong><?= e($query) ?></strong>» پیدا شد.
        </p>

<?php if ($results === [] && $groups === []): ?>
        <?= empty_state(
            'چیزی پیدا نشد',
            'املای کوتاه‌تر یا کلمه‌ی دیگری را امتحان کنید. مثلاً «مکث»، «تنفس» یا «استرس».',
            url('articles'),
            'دیدن همه‌ی مقاله‌ها'
        ) ?>
<?php endif; ?>

<?php if ($results !== []): ?>
        <div class="search-results">
<?php foreach ($results as $item): ?>
            <article class="result">
                <a class="result__title" href="<?= e(url('article', ['slug' => $item['slug']])) ?>"><?= e($item['title']) ?></a>
                <p class="result__kind"><span class="badge"><?= e($item['category'] ?? 'مقاله') ?></span> <span class="muted-sm"><?= e(minutes_label((int) ($item['minutes'] ?? 5))) ?></span></p>
                <p class="result__text"><?= excerpt_of($item, 170) ?></p>
            </article>
<?php endforeach; ?>
        </div>
<?php endif; ?>

<?php if ($groups !== []): ?>
        <div class="search-lessons">
            <h2>درس‌های مرتبط در مسیر آموزشی</h2>
            <ol class="lesson-list">
<?php foreach ($groups as $g): ?>
                <li class="lesson-item">
                    <a class="lesson-item__link" href="<?= e(url('lesson', ['slug' => $g['slug']])) ?>">
                        <span class="lesson-item__num">درس</span>
                        <span class="lesson-item__body">
                            <span class="lesson-item__title"><?= e($g['title']) ?></span>
                            <span class="lesson-item__goal"><?= e($g['text']) ?></span>
                        </span>
                        <span class="lesson-item__side"><span class="chip chip--ghost"><?= e($g['tag']) ?></span></span>
                    </a>
                </li>
<?php endforeach; ?>
            </ol>
        </div>
<?php endif; ?>

<?php endif; ?>
    </div>
</section>
