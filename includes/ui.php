<?php
/**
 * HAvoice — اجزای مشترک ظاهری (کارت‌ها و سربخش‌ها).
 * هدف: جلوگیری از تکرار HTML در فایل‌های pages/*.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  سربخش                                                             */
/* ------------------------------------------------------------------ */

function section_head(array $s, string $class = ''): string
{
    $html = '<header class="section-head' . ($class !== '' ? ' ' . e($class) : '') . '">';
    if (!empty($s['eyebrow'])) {
        $html .= '<p class="eyebrow">' . e($s['eyebrow']) . '</p>';
    }
    if (!empty($s['title'])) {
        $html .= '<h2 class="section-head__title">' . e($s['title']) . '</h2>';
    }
    if (!empty($s['lead'])) {
        $html .= '<p class="section-head__lead">' . e($s['lead']) . '</p>';
    }
    if (!empty($s['action'])) {
        $html .= '<a class="link-arrow" href="' . e($s['action']['url']) . '">' . e($s['action']['label']) . '</a>';
    }
    return $html . '</header>';
}

/* ------------------------------------------------------------------ */
/*  کارت مقاله                                                        */
/* ------------------------------------------------------------------ */

function article_card(array $article, bool $featured = false): string
{
    $href = url('article', ['slug' => (string) $article['slug']]);

    ob_start();
    ?>
    <article class="card article-card<?= $featured ? ' article-card--featured' : '' ?>" data-search-card
             data-hay="<?= e(mb_strtolower(strip_tags(article_text_index($article)), 'UTF-8')) ?>">
        <div class="article-card__top">
            <span class="badge"><?= e($article['category'] ?? 'عمومی') ?></span>
            <span class="meta-dot" aria-hidden="true"></span>
            <span class="muted-sm"><?= minutes_label((int) ($article['minutes'] ?? 5)) ?></span>
        </div>
        <h3 class="article-card__title">
            <a href="<?= e($href) ?>"><?= e($article['title']) ?></a>
        </h3>
        <p class="article-card__excerpt"><?= excerpt_of($article, $featured ? 190 : 135) ?></p>
        <footer class="article-card__foot">
            <time datetime="<?= e($article['date'] ?? '') ?>"><?= e($article['date_fa'] ?? '') ?></time>
            <a class="link-arrow" href="<?= e($href) ?>">خواندن مقاله</a>
        </footer>
    </article>
    <?php
    return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارت نکته                                                         */
/* ------------------------------------------------------------------ */

function tip_card(array $tip): string
{
    ?>
    <article class="card tip-card" data-tip-id="<?= e($tip['id'] ?? '') ?>">
        <span class="tip-card__mark" aria-hidden="true">✦</span>
        <p class="tip-card__text"><?= e($tip['text']) ?></p>
        <?php if (!empty($tip['try'])): ?>
            <p class="tip-card__try"><strong>همین حالا:</strong> <?= e($tip['try']) ?></p>
        <?php endif; ?>
        <footer class="tip-card__foot">
            <span class="badge badge--soft"><?= e($tip['category'] ?? 'عمومی') ?></span>
        </footer>
    </article>
    <?php
    return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  ردیف درس در مرحله                                                 */
/* ------------------------------------------------------------------ */

function lesson_row(array $lesson, int $stageIndex, int $lessonIndex, array $stage): string
{
    $href    = url('lesson', ['slug' => (string) $lesson['slug']]);
    $number  = fa_num(($stageIndex + 1) . '.' . ($lessonIndex + 1));

    ?>
    <li class="lesson-item" data-lesson-row="<?= e($lesson['slug']) ?>">
        <a class="lesson-item__link" href="<?= e($href) ?>">
            <span class="lesson-item__num"><?= e($number) ?></span>
            <span class="lesson-item__body">
                <span class="lesson-item__title"><?= e($lesson['title']) ?></span>
                <span class="lesson-item__goal"><?= e($lesson['goal'] ?? '') ?></span>
            </span>
            <span class="lesson-item__side">
                <span class="chip chip--ghost" data-lesson-flag hidden>انجام شد</span>
                <span class="chip"><?= minutes_label((int) ($lesson['minutes'] ?? 10)) ?></span>
            </span>
        </a>
    </li>
    <?php
    return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارت تمرین                                                        */
/* ------------------------------------------------------------------ */

function exercise_card(array $ex, string $detailUrl = ''): string
{
    ?>
    <article class="card exercise-card" data-exercise="<?= e($ex['id']) ?>">
        <header class="exercise-card__head">
            <span class="badge badge--level"><?= e($ex['level'] ?? 'عمومی') ?></span>
            <span class="badge badge--soft"><?= e($ex['focus'] ?? '') ?></span>
        </header>
        <h3 class="exercise-card__title"><?= e($ex['title']) ?></h3>
        <p class="exercise-card__goal"><?= e($ex['goal'] ?? '') ?></p>
        <ul class="exercise-card__steps">
<?php foreach ((array) ($ex['steps'] ?? []) as $i => $step): ?>
            <li><span class="exercise-card__n"><?= fa_num($i + 1) ?></span><span><?= e($step) ?></span></li>
<?php endforeach; ?>
        </ul>
        <div class="exercise-card__tools">
            <span class="muted-sm">
<?php if (!empty($ex['seconds'])): ?>
    <?= fa_num((int) ceil(((int) $ex['seconds']) / 60)) ?> دقیقه تایمر
<?php else: ?>
    بدون تایمر، با چک‌لیست
<?php endif; ?>
                · <span data-exercise-runs="<?= e($ex['id']) ?>">۰</span> اجرا
            </span>
<?php /* در صفحه‌هایی که مودال تمرین نیست (مثل خانه)، دکمه به صفحه‌ی تمرین‌ها پیوند می‌خورد */ ?>
<?php if ($detailUrl !== ''): ?>
            <a class="btn btn--sm btn--primary" href="<?= e($detailUrl) ?>">شروع تمرین</a>
<?php else: ?>
            <button class="btn btn--sm btn--primary" type="button"
                    data-run-exercise="<?= e($ex['id']) ?>"
                    data-exercise-payload="<?= e(json_encode([
                        'id'      => (string) $ex['id'],
                        'title'   => (string) $ex['title'],
                        'goal'    => (string) ($ex['goal'] ?? ''),
                        'seconds' => (int) ($ex['seconds'] ?? 0),
                        'steps'   => (array) ($ex['steps'] ?? []),
                        'topics'  => (array) ($ex['topics'] ?? []),
                        'success' => (string) ($ex['success'] ?? ''),
                    ], JSON_UNESCAPED_UNICODE)) ?>">
                شروع تمرین
            </button>
<?php endif; ?>
        </div>
    </article>
    <?php
    return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  اجزای خرد                                                         */
/* ------------------------------------------------------------------ */

function progress_bar(int $percent, string $label = ''): string
{
    $percent = max(0, min(100, $percent));

    ?>
    <div class="progress" role="progressbar" aria-valuenow="<?= (int) $percent ?>" aria-valuemin="0" aria-valuemax="100">
        <span class="progress__bar" style="--progress: <?= $percent ?>%"></span>
<?php if ($label !== ''): ?>
        <span class="progress__label"><?= e($label) ?></span>
<?php endif; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}

function empty_state(string $title, string $text, string $url = '', string $label = ''): string
{
    ?>
    <div class="empty-state">
        <p class="empty-state__title"><?= e($title) ?></p>
        <p class="empty-state__text"><?= e($text) ?></p>
<?php if ($url !== ''): ?>
        <a class="btn btn--ghost btn--sm" href="<?= e($url) ?>"><?= e($label) ?></a>
<?php endif; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}

/** حالت «محتوا پیدا نشد» برای مقاله/درسِ نامعتبر. */
function not_found(string $label): string
{
    ?>
    <section class="container section">
        <div class="not-found">
            <h1>«<?= e($label) ?>» پیدا نشد</h1>
            <p>ممکن است نشانی را اشتباه وارد کرده باشید یا محتوا جابه‌جا شده باشد.</p>
            <div class="btn-row">
                <a class="btn btn--primary" href="<?= e(url('articles')) ?>">دیدن مقاله‌ها</a>
                <a class="btn btn--ghost" href="<?= e(url('course')) ?>">مسیر آموزشی</a>
            </div>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
}

function tag_list(array $tags): string
{
    if ($tags === []) {
        return '';
    }

    $out = '<ul class="tag-list">';
    foreach ($tags as $tag) {
        $out .= '<li><a href="' . e(url('search', ['q' => $tag])) . '">#' . e($tag) . '</a></li>';
    }

    return $out . '</ul>';
}
