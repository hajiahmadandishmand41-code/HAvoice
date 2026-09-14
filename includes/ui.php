<?php
/**
 * HAvoice — اجزای UI (Design System v4)
 *
 * هر نوع محتوا کارتِ مخصوصِ خودش را دارد و هیچ‌کدام اطلاعاتِ اضافی
 * نشان نمی‌دهند:
 *
 *   دوره      → کاور + عنوان + توضیحِ کوتاه + تعدادِ درس + زمان + دکمه
 *   درس       → شماره + عنوان + هدف + زمان + وضعیتِ «انجام شد»
 *   مقاله    → کاور + عنوان + خلاصه + تاریخ/دسته
 *   ویدیو     → بندانگشتی + عنوان + مدت + دکمه‌ی پخش
 *   صوت       → کاور + عنوان + مدت + پخش‌کننده
 *   کتاب       → جلد + عنوان + نویسنده + خلاصه
 *   تمرین      → سطح + تمرکز + عنوان + هدف + گام‌ها + دکمه‌ی شروع
 *
 * کاورها گرادیانی‌اند و رنگشان از رنگِ همان «حوزه» می‌آید (بدونِ عکسِ
 * جعلی و بدونِ درخواستِ شبکه).
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  کمکی: رنگِ حوزه → متغیرهای گرادیانِ کاور                           */
/* ------------------------------------------------------------------ */

/**
 * style مربوط به کاورِ کارت را از رنگِ حوزه می‌سازد.
 * اگر حوزه پیدا نشود، از رنگِ پیش‌فرضِ برند استفاده می‌شود.
 */
function card_style(?array $cat, array $extra = []): string
{
    $vars = [];
    $c1 = isset($cat['color']) && $cat['color'] !== '' ? (string) $cat['color'] : 'var(--brand)';
    $c2 = isset($cat['accent']) && $cat['accent'] !== '' ? (string) $cat['accent'] : 'var(--secondary)';
    $vars[] = '--cm-1:' . $c1;
    $vars[] = '--cm-2:' . $c2;
    if ($cat !== null && !empty($cat['color'])) {
        $vars[] = '--cat:' . (string) $cat['color'];
        $vars[] = '--badge-bg:' . (string) $cat['color'];
    }
    foreach ($extra as $k => $v) {
        $vars[] = $k . ':' . $v;
    }
    return implode(';', $vars) . ';';
}

/** برچسبِ کوتاهِ حوزه برای نمایش روی کارت. */
function cat_label(?array $cat, string $fallback = ''): string
{
    if ($cat === null) {
        return $fallback !== '' ? $fallback : 'عمومی';
    }
    return (string) ($cat['short'] ?: ($cat['title'] ?: $fallback));
}

/* ------------------------------------------------------------------ */
/*  سرصفحه‌ی بخش                                                       */
/* ------------------------------------------------------------------ */

/**
 * سرصفحه‌ی یک بخش.
 *
 * کلیدها: eyebrow, title, lead, action{label,url,type}, row(bool)
 *   action.type = 'button' ⇒ دکمه‌ی «مشاهده همه» (الگوی صفحه‌ی اصلی)
 *   در غیر این صورت        ⇒ پیوندِ متنی با فلش
 *   row = true             ⇒ عنوان راست، دکمه چپ، در یک ردیف
 */
function section_head(array $s, string $class = ''): string
{
    $isRow   = !empty($s['row']);
    $classes = ['section-head'];
    if ($isRow) {
        $classes[] = 'section-head--row';
    }
    if ($class !== '') {
        foreach (preg_split('/\s+/', trim($class)) as $c) {
            if ($c !== '') {
                $classes[] = $c;
            }
        }
    }

    $inner = '';
    if (!empty($s['eyebrow'])) {
        $inner .= '<p class="eyebrow">' . e($s['eyebrow']) . '</p>';
    }
    if (!empty($s['title'])) {
        $inner .= '<h2 class="section-head__title">' . e($s['title']) . '</h2>';
    }
    if (!empty($s['lead'])) {
        $inner .= '<p class="section-head__lead">' . e($s['lead']) . '</p>';
    }

    $action = '';
    if (!empty($s['action']) && !empty($s['action']['url'])) {
        $label = (string) ($s['action']['label'] ?? 'مشاهده همه');
        $href  = (string) $s['action']['url'];
        if (($s['action']['type'] ?? '') === 'button') {
            $action = '<a class="btn btn--ghost btn--sm" href="' . e($href) . '">'
                    . e($label) . ha_icon('chevron-left', 14) . '</a>';
        } else {
            $action = '<a class="link-arrow" href="' . e($href) . '">' . e($label) . '</a>';
        }
    }

    if ($isRow) {
        return '<header class="' . e(implode(' ', $classes)) . '">'
             . '<div class="section-head__main">' . $inner . '</div>'
             . ($action !== '' ? '<div class="section-head__side">' . $action . '</div>' : '')
             . '</header>';
    }

    return '<header class="' . e(implode(' ', $classes)) . '">' . $inner . $action . '</header>';
}

/* ------------------------------------------------------------------ */
/*  کاورِ مشترکِ کارت‌ها                                               */
/* ------------------------------------------------------------------ */

/**
 * پنلِ گرادیانیِ بالای کارت.
 *
 * @param string $icon   نامِ آیکونِ حوزه
 * @param string $tag    برچسبِ کوتاهِ روی کاور (دسته/نوع)
 * @param string $badge  برچسبِ گوشه (مثلاً مدتِ زمان)
 * @param string $style  متغیرهای رنگ
 * @param string $mod    کلاسِ افزوده (card-media--audio و…)
 */
function card_media(string $icon, string $tag = '', string $badge = '', string $style = '', string $mod = ''): string
{
    $cls = 'card-media' . ($mod !== '' ? ' ' . $mod : '');
    $out = '<div class="' . e($cls) . '"' . ($style !== '' ? ' style="' . e($style) . '"' : '') . '>';
    $out .= '<span class="card-media__glyph">' . ha_icon($icon, 96) . '</span>';
    $out .= '<span class="card-media__icon">' . ha_icon($icon, 26) . '</span>';
    $out .= '<span class="card-media__shine" aria-hidden="true"></span>';
    if ($tag !== '') {
        $out .= '<span class="card-media__tag">' . e($tag) . '</span>';
    }
    if ($badge !== '') {
        $out .= '<span class="card-media__badge">' . e($badge) . '</span>';
    }
    return $out . '</div>';
}

/* ------------------------------------------------------------------ */
/*  کارتِ مقاله — کاور + عنوان + خلاصه + تاریخ/دسته                   */
/* ------------------------------------------------------------------ */

function article_card(array $article, bool $featured = false): string
{
    $href = url('article', ['slug' => (string) ($article['slug'] ?? '')]);
    $cat  = find_category_by_title((string) ($article['category'] ?? ''));
    $icon = $cat !== null ? (string) ($cat['icon'] ?? 'article') : 'article';
    $tag  = $cat !== null ? cat_label($cat, (string) ($article['category'] ?? 'عمومی')) : (string) ($article['category'] ?? 'عمومی');

    ob_start(); ?>
    <article class="card article-card<?= $featured ? ' article-card--featured' : '' ?>" data-search-card data-hay="<?= e(search_haystack($article)) ?>">
        <?= card_media($icon, $tag, minutes_label((int) ($article['minutes'] ?? 5)), card_style($cat)) ?>
        <div class="card__body">
            <div class="article-card__top">
                <span class="badge"><?= e($tag) ?></span>
                <span class="meta-dot" aria-hidden="true"></span>
                <time datetime="<?= e($article['date'] ?? '') ?>"><?= e($article['date_fa'] ?? '') ?></time>
            </div>
            <h3 class="article-card__title"><a href="<?= e($href) ?>"><?= e($article['title'] ?? '') ?></a></h3>
            <p class="article-card__excerpt"><?= excerpt_of($article, $featured ? 210 : 140) ?></p>
        </div>
        <footer class="article-card__foot">
            <span><?= minutes_label((int) ($article['minutes'] ?? 5)) ?> مطالعه</span>
            <a class="card-cta" href="<?= e($href) ?>">خواندن مقاله <?= ha_icon('arrow-left', 14) ?></a>
        </footer>
    </article>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارتِ نکته                                                        */
/* ------------------------------------------------------------------ */

function tip_card(array $tip): string
{
    ob_start(); ?>
    <article class="card tip-card" data-tip-id="<?= e($tip['id'] ?? '') ?>">
        <span class="tip-card__mark" aria-hidden="true"><?= ha_icon('sparkle', 20) ?></span>
        <p class="tip-card__text"><?= e($tip['text'] ?? '') ?></p>
        <?php if (!empty($tip['try'])): ?>
            <p class="tip-card__try"><strong>همین حالا:</strong> <?= e($tip['try']) ?></p>
        <?php endif; ?>
        <footer class="tip-card__foot"><span class="badge badge--soft"><?= e($tip['category'] ?? 'عمومی') ?></span></footer>
    </article>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  ردیفِ درس — شماره + عنوان + هدف + زمان + «انجام شد»               */
/* ------------------------------------------------------------------ */

function lesson_row(array $lesson, int $stageIndex, int $lessonIndex, array $stage): string
{
    $href   = url('lesson', ['slug' => (string) ($lesson['slug'] ?? '')]);
    $number = fa_num(($stageIndex + 1) . '.' . ($lessonIndex + 1));
    ob_start(); ?>
    <li class="lesson-item" data-lesson-row="<?= e($lesson['slug'] ?? '') ?>">
        <a class="lesson-item__link" href="<?= e($href) ?>">
            <span class="lesson-item__num" aria-hidden="true"><?= e($number) ?></span>
            <span class="lesson-item__body">
                <span class="lesson-item__title"><?= e($lesson['title'] ?? '') ?></span>
                <?php if (!empty($lesson['goal'])): ?>
                    <span class="lesson-item__goal"><?= e($lesson['goal']) ?></span>
                <?php endif; ?>
            </span>
            <span class="lesson-item__side">
                <span class="chip chip--ghost" data-lesson-flag hidden><?= ha_icon('check', 12) ?> انجام شد</span>
                <span class="chip chip--ghost"><?= ha_icon('clock', 12) ?> <?= minutes_label((int) ($lesson['minutes'] ?? 10)) ?></span>
                <span class="sr-only">درس <?= e($number) ?></span>
            </span>
        </a>
    </li>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارتِ تمرین — سطح + تمرکز + عنوان + هدف + گام‌ها + دکمه           */
/* ------------------------------------------------------------------ */

function exercise_card(array $ex, string $detailUrl = ''): string
{
    ob_start(); ?>
    <article class="card exercise-card" data-exercise="<?= e($ex['id'] ?? '') ?>">
        <header class="exercise-card__head">
            <span class="badge badge--level"><?= ha_icon('target', 12) ?> <?= e($ex['level'] ?? 'عمومی') ?></span>
            <?php if (!empty($ex['focus'])): ?>
                <span class="badge badge--soft"><?= e($ex['focus']) ?></span>
            <?php endif; ?>
        </header>
        <h3 class="exercise-card__title"><?= e($ex['title'] ?? '') ?></h3>
        <?php if (!empty($ex['goal'])): ?>
            <p class="exercise-card__goal"><?= e($ex['goal']) ?></p>
        <?php endif; ?>
        <?php $steps = array_slice((array) ($ex['steps'] ?? []), 0, 3); if ($steps !== []): ?>
            <ul class="exercise-card__steps">
                <?php foreach ($steps as $i => $step): ?>
                    <li><span class="exercise-card__n" aria-hidden="true"><?= fa_num($i + 1) ?></span><span><?= e($step) ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="exercise-card__tools">
            <span class="muted-sm">
                <?php if (!empty($ex['seconds'])): ?>
                    <?= ha_icon('timer', 12) ?> <?= fa_num((int) ceil(((int) $ex['seconds']) / 60)) ?> دقیقه
                <?php else: ?>
                    <?= ha_icon('check', 12) ?> چک‌لیست
                <?php endif; ?>
                · <span data-exercise-runs="<?= e($ex['id'] ?? '') ?>">۰</span> اجرا
            </span>
            <?php if ($detailUrl !== ''): ?>
                <a class="btn btn--sm btn--primary" href="<?= e($detailUrl) ?>">شروع تمرین</a>
            <?php else: ?>
                <button class="btn btn--sm btn--primary" type="button"
                        data-run-exercise="<?= e($ex['id'] ?? '') ?>"
                        data-exercise-payload="<?= e(json_encode([
                            'id'      => (string) ($ex['id'] ?? ''),
                            'title'   => (string) ($ex['title'] ?? ''),
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
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارتِ دوره — کاور + عنوان + توضیح + تعدادِ درس + دکمه              */
/* ------------------------------------------------------------------ */

function course_card(array $course): string
{
    $href    = url('course', ['slug' => (string) ($course['slug'] ?? '')]);
    $cat     = find_category((string) ($course['category'] ?? ''));
    $lessons = (int) ($course['lessons'] ?? array_sum(array_map(static fn($s) => count($s['lessons'] ?? []), $course['stages'] ?? [])));
    $minutes = (int) ($course['minutes'] ?? 0);
    if (!$minutes) {
        foreach (($course['stages'] ?? []) as $st) {
            foreach (($st['lessons'] ?? []) as $l) {
                $minutes += (int) ($l['minutes'] ?? 0);
            }
        }
    }
    $icon = $cat !== null ? (string) ($cat['icon'] ?? 'steps') : 'steps';

    ob_start(); ?>
    <article class="card course-card course-card--grid">
        <?= card_media($icon, cat_label($cat, (string) ($course['category'] ?? '')), fa_num($lessons) . ' درس', card_style($cat)) ?>
        <div class="card__body">
            <div class="course-card__top">
                <span class="badge badge--soft"><?= ha_icon('steps', 12) ?> <?= e($course['level'] ?? 'همه‌ی سطوح') ?></span>
                <?php if (!empty($course['featured'])): ?>
                    <span class="badge badge--level"><?= ha_icon('star', 12) ?> منتخب</span>
                <?php endif; ?>
            </div>
            <h3 class="course-card__title"><a href="<?= e($href) ?>"><?= e($course['title'] ?? '') ?></a></h3>
            <p class="course-card__excerpt"><?= e($course['excerpt'] ?? '') ?></p>
            <div class="course-card__meta">
                <span><?= ha_icon('list', 12) ?> <?= fa_num($lessons) ?> درس</span>
                <span class="meta-dot" aria-hidden="true"></span>
                <span><?= ha_icon('clock', 12) ?> <?= minutes_label($minutes) ?></span>
            </div>
        </div>
        <div class="course-card__foot">
            <a class="btn btn--primary btn--sm" href="<?= e($href) ?>">مشاهده دوره</a>
            <span class="muted-sm"><?= e($cat['title'] ?? '') ?></span>
        </div>
    </article>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارتِ ویدیو — بندانگشتی + عنوان + مدت + دکمه‌ی پخش                */
/* ------------------------------------------------------------------ */

function video_card(array $item): string
{
    $hasUrl = !empty($item['url']);
    $href   = $hasUrl ? (string) $item['url'] : url('videos');
    $cat    = find_category((string) ($item['category'] ?? ''));
    $dur    = format_duration((int) ($item['seconds'] ?? 0));

    ob_start(); ?>
    <article class="card media-card media-card--video">
        <div class="media-card__thumb"<?= $cat !== null ? ' style="' . e(card_style($cat)) . '"' : '' ?>>
            <span class="media-card__duration"><?= ha_icon('clock', 11) ?> <?= e($dur) ?></span>
            <?php if ($hasUrl): ?>
                <a class="media-card__play" href="<?= e($href) ?>" aria-label="پخشِ ویدیو: <?= e($item['title'] ?? '') ?>"><?= ha_icon('play', 22) ?></a>
            <?php else: ?>
                <span class="media-card__play" aria-hidden="true"><?= ha_icon('play', 22) ?></span>
            <?php endif; ?>
        </div>
        <div class="media-card__body">
            <div class="media-card__top">
                <span class="badge badge--soft"><?= e(cat_label($cat, (string) ($item['category'] ?? ''))) ?></span>
                <?php if (!empty($item['date_fa'])): ?>
                    <span class="meta-dot" aria-hidden="true"></span>
                    <span><?= e($item['date_fa']) ?></span>
                <?php endif; ?>
            </div>
            <h3 class="media-card__title"><?php if ($hasUrl): ?><a href="<?= e($href) ?>"><?= e($item['title'] ?? '') ?></a><?php else: ?><?= e($item['title'] ?? '') ?><?php endif; ?></h3>
            <p class="media-card__excerpt"><?= e($item['excerpt'] ?? '') ?></p>
        </div>
        <footer class="media-card__foot">
            <?php if ($hasUrl): ?>
                <a class="btn btn--sm btn--primary" href="<?= e($href) ?>"><?= ha_icon('play', 12) ?> پخش ویدیو</a>
            <?php else: ?>
                <span class="badge badge--outline"><?= ha_icon('clock', 12) ?> به‌زودی</span>
            <?php endif; ?>
            <span class="muted-sm"><?= e($dur) ?></span>
        </footer>
    </article>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارتِ صوت — کاور + عنوان + مدت + پخش‌کننده                        */
/* ------------------------------------------------------------------ */

function audio_card(array $item): string
{
    $hasUrl = !empty($item['url']);
    $cat    = find_category((string) ($item['category'] ?? ''));
    $dur    = format_duration((int) ($item['seconds'] ?? 0));

    ob_start(); ?>
    <article class="card media-card media-card--audio">
        <div class="media-card__thumb media-card__thumb--audio"<?= $cat !== null ? ' style="' . e(card_style($cat)) . '"' : '' ?>>
            <span class="media-card__duration"><?= ha_icon('clock', 11) ?> <?= e($dur) ?></span>
            <span class="media-card__play" aria-hidden="true"><?= ha_icon('headphones', 22) ?></span>
        </div>
        <div class="media-card__body">
            <div class="media-card__top">
                <span class="badge badge--soft"><?= e(cat_label($cat, (string) ($item['category'] ?? ''))) ?></span>
                <?php if (!empty($item['date_fa'])): ?>
                    <span class="meta-dot" aria-hidden="true"></span>
                    <span><?= e($item['date_fa']) ?></span>
                <?php endif; ?>
            </div>
            <h3 class="media-card__title"><?= e($item['title'] ?? '') ?></h3>
            <?php if ($hasUrl): ?>
                <audio controls preload="none" src="<?= e($item['url']) ?>" class="audio-player" aria-label="پخش‌کننده‌ی صوت: <?= e($item['title'] ?? '') ?>"></audio>
            <?php else: ?>
                <div class="audio-player audio-player--placeholder" role="note"><?= ha_icon('info', 13) ?> فایل صوتی به‌زودی افزوده می‌شود</div>
            <?php endif; ?>
        </div>
        <footer class="media-card__foot">
            <span class="muted-sm"><?= ha_icon('headphones', 12) ?> پادکست</span>
            <span class="muted-sm"><?= e($dur) ?></span>
        </footer>
    </article>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارتِ کتاب — جلد + عنوان + نویسنده + خلاصه                        */
/* ------------------------------------------------------------------ */

function book_card(array $book): string
{
    $href = url('books', ['slug' => (string) ($book['slug'] ?? '')]);
    $cat  = find_category((string) ($book['category'] ?? ''));

    ob_start(); ?>
    <article class="card book-card">
        <div class="book-card__cover" style="<?= e(card_style($cat)) ?>" role="img" aria-label="جلدِ <?= e($book['title'] ?? '') ?>">
            <?= ha_icon('book', 30) ?>
        </div>
        <div class="book-card__body">
            <div class="book-card__top">
                <span class="badge badge--soft"><?= e(cat_label($cat, (string) ($book['category'] ?? ''))) ?></span>
                <?php if (!empty($book['minutes'])): ?>
                    <span><?= minutes_label((int) $book['minutes']) ?></span>
                <?php endif; ?>
            </div>
            <h3 class="book-card__title"><a href="<?= e($href) ?>"><?= e($book['title'] ?? '') ?></a></h3>
            <?php if (!empty($book['author'])): ?>
                <p class="book-card__author"><?= e($book['author']) ?></p>
            <?php endif; ?>
            <p class="book-card__excerpt"><?= e($book['excerpt'] ?? '') ?></p>
        </div>
        <footer class="book-card__foot">
            <a class="card-cta" href="<?= e($href) ?>">خلاصه و برداشت <?= ha_icon('arrow-left', 14) ?></a>
            <?php if (!empty($book['date_fa'])): ?>
                <span><?= e($book['date_fa']) ?></span>
            <?php endif; ?>
        </footer>
    </article>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارتِ پژوهش                                                       */
/* ------------------------------------------------------------------ */

function research_card(array $item): string
{
    $href = url('research', ['slug' => (string) ($item['slug'] ?? '')]);
    $cat  = find_category((string) ($item['category'] ?? ''));

    ob_start(); ?>
    <article class="card research-card">
        <div class="research-card__top">
            <span class="badge"><?= ha_icon('research', 12) ?> <?= e(cat_label($cat, (string) ($item['category'] ?? ''))) ?></span>
            <?php if (!empty($item['date_fa'])): ?>
                <span class="meta-dot" aria-hidden="true"></span>
                <time datetime="<?= e($item['date_fa']) ?>"><?= e($item['date_fa']) ?></time>
            <?php endif; ?>
        </div>
        <h3 class="research-card__title"><a href="<?= e($href) ?>"><?= e($item['title'] ?? '') ?></a></h3>
        <p class="research-card__summary"><?= e($item['summary'] ?? '') ?></p>
        <?php if (!empty($item['tags'])): ?>
            <div class="tag-row">
                <?php foreach (array_slice((array) $item['tags'], 0, 3) as $t): ?>
                    <span class="chip chip--ghost">#<?= e($t) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <footer class="research-card__foot">
            <a class="card-cta" href="<?= e($href) ?>">مطالعه پژوهش <?= ha_icon('arrow-left', 14) ?></a>
        </footer>
    </article>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارتِ حوزه                                                        */
/* ------------------------------------------------------------------ */

function category_card(array $cat): string
{
    $href = url('category', ['slug' => (string) ($cat['slug'] ?? '')]);
    ob_start(); ?>
    <a class="card category-card" href="<?= e($href) ?>" style="<?= e(card_style($cat)) ?>">
        <span class="category-card__icon" aria-hidden="true"><?= ha_icon((string) ($cat['icon'] ?? 'compass'), 22) ?></span>
        <h3 class="category-card__title"><?= e($cat['title'] ?? '') ?></h3>
        <p class="category-card__desc"><?= e($cat['description'] ?? '') ?></p>
        <span class="link-arrow">ورود به حوزه</span>
    </a>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  نوارِ پیشرفت / حالت‌ها / ناوبری                                    */
/* ------------------------------------------------------------------ */

/**
 * نوارِ پیشرفت.
 *
 * دسترس‌پذیری: role="progressbar" بدونِ نامِ دسترس‌پذیر از نظرِ WCAG 4.1.2
 * ناقص است؛ بنابراین aria-label اجباری است و اگر داده نشود مقدارِ
 * پیش‌فرضِ معنادار می‌گیرد.
 */
function progress_bar(int $percent, string $label = '', string $ariaLabel = 'پیشرفت'): string
{
    $percent = max(0, min(100, $percent));
    ob_start(); ?>
    <div class="progress" role="progressbar"
         aria-label="<?= e($ariaLabel) ?>"
         aria-valuenow="<?= (int) $percent ?>" aria-valuemin="0" aria-valuemax="100"
         aria-valuetext="<?= e(fa_num($percent)) ?> درصد">
        <span class="progress__bar" style="--progress: <?= $percent ?>%"></span>
        <?php if ($label !== ''): ?><span class="progress__label"><?= e($label) ?></span><?php endif; ?>
    </div>
    <?php return (string) ob_get_clean();
}

/** حالتِ خالی — وقتی فهرستی نتیجه‌ای ندارد. */
function empty_state(string $title, string $text, string $url = '', string $label = '', string $icon = 'search'): string
{
    ob_start(); ?>
    <div class="empty-state">
        <span class="empty-state__icon" aria-hidden="true"><?= ha_icon($icon, 26) ?></span>
        <p class="empty-state__title"><?= e($title) ?></p>
        <p class="empty-state__text"><?= e($text) ?></p>
        <?php if ($url !== ''): ?>
            <a class="btn btn--ghost btn--sm" href="<?= e($url) ?>"><?= e($label !== '' ? $label : 'بازگشت') ?></a>
        <?php endif; ?>
    </div>
    <?php return (string) ob_get_clean();
}

/** حالتِ خطا — محتوای درخواستی پیدا نشد. */
function not_found(string $label): string
{
    ob_start(); ?>
    <section class="container section">
        <div class="not-found">
            <p class="not-found__code" aria-hidden="true">۴۰۴</p>
            <h1>«<?= e($label) ?>» پیدا نشد</h1>
            <p>ممکن است نشانی را اشتباه وارد کرده باشید یا این محتوا جابه‌جا شده باشد.</p>
            <div class="btn-row">
                <a class="btn btn--primary" href="<?= e(url('home')) ?>"><?= ha_icon('home', 15) ?> صفحه‌ی اصلی</a>
                <a class="btn btn--ghost" href="<?= e(url('courses')) ?>">دیدن دوره‌ها</a>
            </div>
            <div class="not-found__latest">
                <h2 class="h3">تازه‌ترین مقاله‌ها</h2>
                <ul class="link-list">
                    <?php foreach (latest_articles(4) as $a): ?>
                        <li>
                            <a href="<?= e(url('article', ['slug' => (string) ($a['slug'] ?? '')])) ?>"><?= e($a['title'] ?? '') ?></a>
                            <span class="muted-sm"><?= e($a['date_fa'] ?? '') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>
    <?php return (string) ob_get_clean();
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

function breadcrumbs(array $items): string
{
    ob_start(); ?>
    <nav class="breadcrumbs" aria-label="مسیر صفحه">
        <a href="<?= e(url('home')) ?>"><?= ha_icon('home', 12) ?> خانه</a>
        <?php foreach ($items as $it): ?>
            <span class="breadcrumbs__sep" aria-hidden="true"><?= ha_icon('chevron-left', 13) ?></span>
            <?php if (!empty($it['url'])): ?>
                <a href="<?= e($it['url']) ?>"><?= e($it['label']) ?></a>
            <?php else: ?>
                <span aria-current="page"><?= e($it['label']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php return (string) ob_get_clean();
}
