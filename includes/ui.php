<?php
/**
 * HAvoice — اجزای UI (Design System v4 + Media Pro)
 * کارت‌های حرفه‌ای با Progressive Loading/Streaming
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  کمکی: رنگِ حوزه → متغیرهای گرادیانِ کاور                           */
/* ------------------------------------------------------------------ */

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
/*  کارتِ مقاله                                                        */
/* ------------------------------------------------------------------ */

function article_card(array $article, bool $featured = false): string
{
    $href = url('article', ['slug' => (string) ($article['slug'] ?? '')]);
    $cat  = ha_item_field_slug($article) !== '' ? find_category(ha_item_field_slug($article)) : null;
    if ($cat === null) {
        $cat = find_category_by_title((string) ($article['category'] ?? ''));
    }
    $icon = $cat !== null ? (string) ($cat['icon'] ?? 'article') : 'article';
    $tag  = $cat !== null ? cat_label($cat, (string) ($article['category'] ?? 'عمومی')) : (string) ($article['category'] ?? 'عمومی');
    $image = ha_public_file_url((string) ($article['image'] ?? ''));

    ob_start(); ?>
    <article class="card article-card<?= $featured ? ' article-card--featured' : '' ?> reveal" data-search-card data-hay="<?= e(search_haystack($article)) ?>">
        <?php if ($image !== ''): ?>
            <div class="card-media card-media--img">
                <img src="<?= e($image) ?>" alt="<?= e($article['title'] ?? '') ?>" loading="lazy" decoding="async">
                <span class="card-media__tag"><?= e($tag) ?></span>
                <span class="card-media__badge"><?= e(minutes_label((int) ($article['minutes'] ?? 5))) ?></span>
            </div>
        <?php else: ?>
            <?= card_media($icon, $tag, minutes_label((int) ($article['minutes'] ?? 5)), card_style($cat)) ?>
        <?php endif; ?>
        <div class="card__body">
            <div class="article-card__top">
                <span class="badge"><?= e($tag) ?></span>
                <span class="meta-dot" aria-hidden="true"></span>
                <time datetime="<?= e($article['date'] ?? '') ?>"><?= e($article['date_fa'] ?? '') ?></time>
            </div>
            <h3 class="article-card__title"><a href="<?= e($href) ?>"><?= e($article['title'] ?? '') ?></a></h3>
            <?php if (!empty($article['author'])): ?>
                <p class="article-card__author"><?= ha_icon('user', 12) ?> <?= e($article['author']) ?></p>
            <?php endif; ?>
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
    <article class="card tip-card reveal" data-tip-id="<?= e($tip['id'] ?? '') ?>">
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
/*  ردیفِ درس                                                          */
/* ------------------------------------------------------------------ */

function lesson_row(array $lesson, int $stageIndex, int $lessonIndex, array $stage, string $highlight = ''): string
{
    $slug   = slugify((string) ($lesson['slug'] ?? ''));
    $href   = url('lesson', ['slug' => $slug]);
    $number = fa_num(($stageIndex + 1) . '.' . ($lessonIndex + 1));
    $state  = $slug !== '' ? progress_lesson_state($slug) : '';
    $meta   = progress_state_meta($state);
    $classes = ['lesson-item', $meta['class']];
    if ($highlight === 'current') {
        $classes[] = 'is-current';
    } elseif ($highlight === 'next') {
        $classes[] = 'is-next';
    }
    ob_start(); ?>
    <li class="<?= e(implode(' ', $classes)) ?> reveal" data-lesson-row="<?= e($slug) ?>" data-lesson-state="<?= e((string) $meta['key']) ?>">
        <a class="lesson-item__link" href="<?= e($href) ?>"<?= $highlight === 'current' ? ' aria-current="true"' : '' ?>>
            <span class="lesson-item__num" aria-hidden="true">
                <?php if ($state === 'done'): ?><?= ha_icon('check', 15) ?><?php else: ?><?= e($number) ?><?php endif; ?>
            </span>
            <span class="lesson-item__body">
                <span class="lesson-item__title"><?= e($lesson['title'] ?? '') ?></span>
                <?php if (!empty($lesson['goal'])): ?>
                    <span class="lesson-item__goal"><?= e($lesson['goal']) ?></span>
                <?php endif; ?>
            </span>
            <span class="lesson-item__side">
                <?php if ($highlight === 'current'): ?><span class="pill pill--flag"><?= ha_icon('flag', 12) ?><span>درسِ فعلی — از همین‌جا ادامه بدهید</span></span><?php endif; ?>
                <?php if ($highlight === 'next'): ?><span class="pill pill--next"><?= ha_icon('arrow-left', 12) ?><span>درسِ بعدی</span></span><?php endif; ?>
                <span class="pill pill--<?= e((string) $meta['key']) ?>"><?= ha_icon((string) $meta['icon'], 12) ?><span><?= e((string) $meta['label']) ?></span></span>
                <span class="chip chip--ghost"><?= ha_icon('clock', 12) ?> <?= minutes_label((int) ($lesson['minutes'] ?? 10)) ?></span>
                <span class="sr-only">درس <?= e($number) ?></span>
            </span>
        </a>
    </li>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارتِ تمرین                                                        */
/* ------------------------------------------------------------------ */

function exercise_card(array $ex, string $detailUrl = ''): string
{
    $lessonSlug  = slugify((string) ($ex['lesson'] ?? ''));
    $lessonInfo  = $lessonSlug !== '' ? course_find_lesson($lessonSlug) : null;
    $lessonTitle = $lessonInfo !== null ? (string) ($lessonInfo['lesson']['title'] ?? '') : '';
    ob_start(); ?>
    <?php $exStateClass = function_exists('progress_state_meta') ? progress_state_meta(function_exists('progress_exercise_state') ? progress_exercise_state((string) ($ex['id'] ?? '')) : '')['class'] : ''; ?>
    <article class="card exercise-card <?= e((string) $exStateClass) ?> reveal" id="ex-<?= e($ex['id'] ?? '') ?>" data-exercise="<?= e($ex['id'] ?? '') ?>">
        <header class="exercise-card__head">
            <span class="badge badge--level"><?= ha_icon('target', 12) ?> <?= e($ex['level'] ?? 'عمومی') ?></span>
            <?php if (!empty($ex['focus'])): ?>
                <span class="badge badge--soft"><?= e($ex['focus']) ?></span>
            <?php endif; ?>
            <?php if (function_exists('progress_exercise_state')):
                $exState = progress_exercise_state((string) ($ex['id'] ?? ''));
                if ($exState !== ''): ?>
                <span class="exercise-card__state"><?= status_pill($exState) ?></span>
                <?php endif;
            endif; ?>
        </header>
        <h3 class="exercise-card__title"><?= e($ex['title'] ?? '') ?></h3>
        <?php if ($lessonTitle !== ''): ?>
            <p class="exercise-card__lesson"><?= ha_icon('steps', 12) ?> درسِ مرتبط: <a href="<?= e(url('lesson', ['slug' => $lessonSlug])) ?>"><?= e($lessonTitle) ?></a></p>
        <?php endif; ?>
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
            <?= function_exists('exercise_complete_form') ? exercise_complete_form((string) ($ex['id'] ?? ''), function_exists('progress_exercise_state') ? progress_exercise_state((string) ($ex['id'] ?? '')) : '') : '' ?>
            <?php if ($detailUrl !== ''): ?>
                <a class="btn btn--sm btn--primary btn--cta" href="<?= e($detailUrl) ?>">شروع تمرین</a>
            <?php else: ?>
                <button class="btn btn--sm btn--primary btn--cta" type="button"
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
/*  کارتِ دوره — CTA واضح «شروع» و «ادامه یادگیری»                    */
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
    $isStarted = false;
    $progressPercent = 0;
    if (function_exists('course_learning_path')) {
        $lp = course_learning_path($course);
        $progressPercent = (int) ($lp['summary']['percent'] ?? 0);
        $isStarted = $progressPercent > 0;
    }

    ob_start(); ?>
    <article class="card course-card course-card--grid reveal">
        <?= card_media($icon, cat_label($cat, (string) ($course['category'] ?? '')), fa_num($lessons) . ' درس', card_style($cat)) ?>
        <div class="card__body">
            <div class="course-card__top">
                <span class="badge badge--soft"><?= ha_icon('steps', 12) ?> <?= e($course['level'] ?? 'همه‌ی سطوح') ?></span>
                <?php if (!empty($course['featured'])): ?>
                    <span class="badge badge--level"><?= ha_icon('star', 12) ?> منتخب</span>
                <?php endif; ?>
                <?php if ($isStarted): ?>
                    <span class="pill pill--started"><?= ha_icon('growth', 12) ?><span><?= fa_num($progressPercent) ?>٪</span></span>
                <?php endif; ?>
            </div>
            <h3 class="course-card__title"><a href="<?= e($href) ?>"><?= e($course['title'] ?? '') ?></a></h3>
            <p class="course-card__excerpt"><?= e($course['excerpt'] ?? '') ?></p>
            <div class="course-card__meta">
                <span><?= ha_icon('list', 12) ?> <?= fa_num($lessons) ?> درس</span>
                <span class="meta-dot" aria-hidden="true"></span>
                <span><?= ha_icon('clock', 12) ?> <?= minutes_label($minutes) ?></span>
            </div>
            <?php if ($isStarted): ?>
                <div class="course-card__progress" style="margin-top:.6rem"><?= progress_bar($progressPercent, fa_num($progressPercent).'٪ پیشرفت', 'پیشرفت دوره') ?></div>
            <?php endif; ?>
        </div>
        <div class="course-card__foot">
            <?php if ($isStarted): ?>
                <a class="btn btn--primary btn--sm btn--cta" href="<?= e($href) ?>"><?= ha_icon('play', 12) ?> ادامه یادگیری</a>
            <?php else: ?>
                <a class="btn btn--primary btn--sm btn--cta" href="<?= e($href) ?>"><?= ha_icon('play', 12) ?> شروع یادگیری</a>
            <?php endif; ?>
            <span class="muted-sm"><?= e($cat['title'] ?? '') ?></span>
        </div>
    </article>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارتِ ویدیو — Progressive + Streaming + Error State زیبا           */
/* ------------------------------------------------------------------ */

function video_card(array $item): string
{
    $url    = ha_public_media_url((string) ($item['url'] ?? ''));
    $hasUrl = $url !== '';
    $cat    = find_category(ha_item_field_slug($item));
    $dur    = format_duration((int) ($item['seconds'] ?? 0));
    $thumb  = ha_public_file_url((string) ($item['thumbnail'] ?? ($item['image'] ?? '')));
    $title  = (string) ($item['title'] ?? 'ویدیو');
    $excerpt = (string) ($item['excerpt'] ?? '');
    $isEmbed = ha_embed_url($url) !== '';
    $style  = $cat !== null ? card_style($cat) : '';

    $lessonSlug = slugify((string) ($item['lesson'] ?? ''));
    $courseSlug = slugify((string) ($item['course'] ?? ''));
    $relatedLabel = '';
    if ($lessonSlug !== '') {
        $li = course_find_lesson($lessonSlug);
        if ($li !== null) $relatedLabel = (string) ($li['lesson']['title'] ?? '');
    } elseif ($courseSlug !== '') {
        $c = find_course($courseSlug);
        if ($c !== null) $relatedLabel = (string) ($c['title'] ?? '');
    }

    ob_start(); ?>
    <article class="card media-card media-card--video reveal" data-ha-card="video">
        <?php if ($isEmbed): ?>
            <div class="media-card__thumb<?= $thumb !== '' ? ' media-card__thumb--img' : '' ?>" style="<?= e($style) ?>">
                <?php if ($thumb !== ''): ?>
                    <img src="<?= e($thumb) ?>" alt="" loading="lazy" decoding="async" width="640" height="360">
                <?php endif; ?>
                <span class="media-card__thumb-overlay" aria-hidden="true"></span>
                <?php if ($dur !== '' && (int) ($item['seconds'] ?? 0) > 0): ?>
                    <span class="media-card__duration"><?= ha_icon('clock', 11) ?> <?= e($dur) ?></span>
                <?php endif; ?>
                <button class="media-card__play media-card__play--link" type="button"
                   data-media-open data-media-payload="<?= e(media_player_payload($item)) ?>"
                   aria-label="پخش ویدیو: <?= e($title) ?>"><?= ha_icon('play', 22) ?></button>
            </div>
        <?php else: ?>
            <?php if ($hasUrl): ?>
                <div style="border-radius:12px;overflow:hidden;background:#000;border:1px solid var(--border)">
                    <video controls preload="metadata" playsinline <?= $thumb!=='' ? 'poster="'.e($thumb).'"' : '' ?> src="<?= e($url) ?>" aria-label="<?= e($title) ?>" style="width:100%;aspect-ratio:16/9;height:auto;display:block;background:#000;max-width:100%"></video>
                </div>
            <?php else: ?>
                <div class="media-placeholder media-placeholder--video" style="padding:1rem;text-align:center;border:1px dashed var(--border);border-radius:12px;background:var(--surface-2)"><?= ha_icon('play',24) ?><p class="muted-sm">ویدیو به‌زودی</p></div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="media-card__body">
            <div class="media-card__top">
                <span class="badge badge--soft"><?= e(cat_label($cat, (string) ($item['category'] ?? ''))) ?></span>
                <?php if (!empty($item['featured'])): ?>
                    <span class="badge badge--level"><?= ha_icon('star', 12) ?> منتخب</span>
                <?php endif; ?>
            </div>
            <h3 class="media-card__title">
                <?php if ($isEmbed): ?><a href="<?= e($url) ?>" data-media-open data-media-payload="<?= e(media_player_payload($item)) ?>"><?= e($title) ?></a><?php else: ?><?= e($title) ?><?php endif; ?>
            </h3>
            <?php if ($excerpt !== ''): ?><p class="media-card__excerpt"><?= e($excerpt) ?></p><?php endif; ?>
            <?php if ($relatedLabel !== ''): ?><p class="media-card__related muted-sm"><?= ha_icon('steps', 12) ?> <?= e($relatedLabel) ?></p><?php endif; ?>
        </div>
        <footer class="media-card__foot">
            <?php if ($hasUrl): ?>
                <?php if ($isEmbed): ?>
                    <button class="btn btn--sm btn--primary btn--cta" type="button" data-media-open data-media-payload="<?= e(media_player_payload($item)) ?>"><?= ha_icon('play', 12) ?> پخش سریع در سایت</button>
                    <span class="ha-buffering" style="display:none" data-ha-net-hint><?= ha_icon('clock', 11) ?> بهینه برای اینترنت ضعیف</span>
                <?php else: ?>
                    <span class="muted-sm"><?= ha_icon('play', 12) ?> پخش آنی • استریم پیشرونده</span>
                    <span class="muted-sm"><?= e($dur) ?></span>
                <?php endif; ?>
            <?php else: ?>
                <span class="badge badge--outline"><?= ha_icon('info', 12) ?> در دسترس نیست</span>
            <?php endif; ?>
        </footer>
        <?php
        $vSlug = slugify((string)($item['slug'] ?? ''));
        if ($vSlug !== ''):
            $vCounts = function_exists('ha_reaction_counts') ? ha_reaction_counts('video', $vSlug) : ['total'=>0];
            $vCom = function_exists('ha_comment_counts') ? ha_comment_counts('video', $vSlug) : ['total'=>0];
        ?>
        <div class="media-card__interactions">
            <span class="ha-mini-stat">❤️ <?= fa_num((int)($vCounts['total'] ?? 0)) ?></span>
            <span class="ha-mini-stat">💬 <?= fa_num((int)($vCom['total'] ?? 0)) ?></span>
            <a class="btn btn--ghost btn--xs" href="#ha-video-<?= e($vSlug) ?>">تعامل</a>
        </div>
        <?php endif; ?>
    </article>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارتِ صوت — Progressive Audio با شروع سریع                         */
/* ------------------------------------------------------------------ */

function audio_card(array $item): string
{
    $url    = ha_public_media_url((string) ($item['url'] ?? ''));
    $hasUrl = $url !== '';
    $cat    = find_category(ha_item_field_slug($item));
    $dur    = format_duration((int) ($item['seconds'] ?? 0));
    $title  = (string) ($item['title'] ?? 'صوت');
    $excerpt = (string) ($item['excerpt'] ?? '');
    $style  = $cat !== null ? card_style($cat) : '';

    ob_start(); ?>
    <article class="card media-card media-card--audio reveal" data-ha-card="audio">
        <div data-ha-player data-ha-type="audio" data-ha-src="<?= e($url) ?>" data-ha-title="<?= e($title) ?>" style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1rem;<?= e($style) ?>">
            <p style="margin:0 0 .6rem;display:flex;align-items:center;gap:.45rem;color:var(--text);font-weight:700"><?= ha_icon('headphones', 16) ?> <?= e($title) ?></p>
            <?php if ($hasUrl): ?>
                <audio controls preload="metadata" src="<?= e($url) ?>" aria-label="<?= e($title) ?>" style="width:100%;height:44px;border-radius:8px"></audio>
                <p class="muted-sm" style="margin:.5rem 0 0;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap"><a href="<?= e($url) ?>" download class="btn btn--ghost btn--xs"><?= ha_icon('download', 12) ?> دریافت</a><span><?= e($dur) ?></span></p>
            <?php else: ?>
                <p class="muted-sm">فایلِ صوتی به‌زودی افزوده می‌شود</p>
            <?php endif; ?>
        </div>

        <div class="media-card__body" style="padding-top:1rem">
            <div class="media-card__top">
                <span class="badge badge--soft"><?= e(cat_label($cat, (string) ($item['category'] ?? ''))) ?></span>
                <?php if (!empty($item['featured'])): ?><span class="badge badge--level"><?= ha_icon('star', 12) ?> منتخب</span><?php endif; ?>
                <?php if ((int) ($item['seconds'] ?? 0) > 0): ?><span class="chip chip--ghost"><?= ha_icon('clock', 12) ?> <?= e($dur) ?></span><?php endif; ?>
            </div>
            <?php if ($excerpt !== ''): ?><p class="media-card__excerpt"><?= e($excerpt) ?></p><?php endif; ?>
        </div>
        <footer class="media-card__foot"><span class="muted-sm"><?= ha_icon('headphones', 12) ?> پادکست • استریم پیشرونده • فقط هنگام پخش دانلود می‌شود</span></footer>
        <?php
        $aSlug = slugify((string)($item['slug'] ?? ''));
        if ($aSlug !== ''):
            $aCounts = function_exists('ha_reaction_counts') ? ha_reaction_counts('audio', $aSlug) : ['total'=>0];
            $aCom = function_exists('ha_comment_counts') ? ha_comment_counts('audio', $aSlug) : ['total'=>0];
        ?>
        <div class="media-card__interactions">
            <span class="ha-mini-stat">❤️ <?= fa_num((int)($aCounts['total'] ?? 0)) ?></span>
            <span class="ha-mini-stat">💬 <?= fa_num((int)($aCom['total'] ?? 0)) ?></span>
            <a class="btn btn--ghost btn--xs" href="#ha-audio-<?= e($aSlug) ?>">تعامل</a>
        </div>
        <?php endif; ?>
    </article>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  کارتِ کتاب                                                         */
/* ------------------------------------------------------------------ */

function book_card(array $book): string
{
    $href = url('books', ['slug' => (string) ($book['slug'] ?? '')]);
    $cat  = find_category(ha_item_field_slug($book));
    $image = ha_public_file_url((string) ($book['image'] ?? ''));
    $hasOnlineRead = !empty($book['blocks']);

    ob_start(); ?>
    <article class="card book-card reveal">
        <div class="book-card__cover<?= $image !== '' ? ' book-card__cover--img' : '' ?>" style="<?= e(card_style($cat)) ?>" role="img" aria-label="جلدِ <?= e($book['title'] ?? '') ?>">
            <?php if ($image !== ''): ?>
                <img src="<?= e($image) ?>" alt="" loading="lazy" decoding="async">
            <?php else: ?>
                <?= ha_icon('book', 30) ?>
            <?php endif; ?>
        </div>
        <div class="book-card__body">
            <div class="book-card__top">
                <span class="badge badge--soft"><?= e(cat_label($cat, (string) ($book['category'] ?? ''))) ?></span>
                <?php if ($hasOnlineRead): ?>
                    <span class="badge badge--level"><?= ha_icon('book', 12) ?> متن کامل</span>
                <?php endif; ?>
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
            <a class="card-cta" href="<?= e($href) ?>"><?= $hasOnlineRead ? 'مطالعه و خلاصه' : 'خلاصه و برداشت' ?> <?= ha_icon('arrow-left', 14) ?></a>
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
    $cat  = find_category(ha_item_field_slug($item));

    ob_start(); ?>
    <article class="card research-card reveal">
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
    <a class="card category-card reveal" href="<?= e($href) ?>" style="<?= e(card_style($cat)) ?>">
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

function empty_state(string $title, string $text, string $url = '', string $label = '', string $icon = 'search'): string
{
    ob_start(); ?>
    <div class="empty-state">
        <span class="empty-state__icon" aria-hidden="true"><?= ha_icon($icon, 26) ?></span>
        <p class="empty-state__title"><?= e($title) ?></p>
        <p class="empty-state__text"><?= e($text) ?></p>
        <?php if ($url !== ''): ?>
            <a class="btn btn--ghost btn--sm btn--cta" href="<?= e($url) ?>"><?= e($label !== '' ? $label : 'بازگشت') ?></a>
        <?php endif; ?>
    </div>
    <?php return (string) ob_get_clean();
}

function not_found(string $label): string
{
    ob_start(); ?>
    <section class="container section">
        <div class="not-found">
            <p class="not-found__code" aria-hidden="true">۴۰۴</p>
            <h1>«<?= e($label) ?>» پیدا نشد</h1>
            <p>ممکن است نشانی را اشتباه وارد کرده باشید یا این محتوا جابه‌جا شده باشد.</p>
            <div class="btn-row">
                <a class="btn btn--primary btn--cta" href="<?= e(url('home')) ?>"><?= ha_icon('home', 15) ?> صفحه‌ی اصلی</a>
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
