<?php
/**
 * HAvoice 2.0 — اجزای UI
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

function section_head(array $s, string $class=''): string
{
    $html = '<header class="section-head' . ($class!==''?' '.e($class):'') . '">';
    if (!empty($s['eyebrow'])) $html .= '<p class="eyebrow">' . e($s['eyebrow']) . '</p>';
    if (!empty($s['title'])) $html .= '<h2 class="section-head__title">' . e($s['title']) . '</h2>';
    if (!empty($s['lead'])) $html .= '<p class="section-head__lead">' . e($s['lead']) . '</p>';
    if (!empty($s['action'])) $html .= '<a class="link-arrow" href="' . e($s['action']['url']) . '">' . e($s['action']['label']) . '</a>';
    return $html . '</header>';
}

/* کارت مقاله */
function article_card(array $article, bool $featured=false): string
{
    $href = url('article',['slug'=>(string)$article['slug']]);
    ob_start();
    ?>
    <article class="card article-card<?= $featured?' article-card--featured':'' ?>" data-search-card data-hay="<?= e(mb_strtolower(strip_tags(article_text_index($article)),'UTF-8')) ?>">
        <div class="article-card__top">
            <span class="badge"><?= e($article['category']??'عمومی') ?></span>
            <span class="meta-dot" aria-hidden="true"></span>
            <span class="muted-sm"><?= minutes_label((int)($article['minutes']??5)) ?></span>
        </div>
        <h3 class="article-card__title"><a href="<?= e($href) ?>"><?= e($article['title']) ?></a></h3>
        <p class="article-card__excerpt"><?= excerpt_of($article,$featured?190:135) ?></p>
        <footer class="article-card__foot">
            <time datetime="<?= e($article['date']??'') ?>"><?= e($article['date_fa']??'') ?></time>
            <a class="link-arrow" href="<?= e($href) ?>">خواندن مقاله</a>
        </footer>
    </article>
    <?php return (string)ob_get_clean();
}

/* کارت نکته */
function tip_card(array $tip): string
{
    ob_start(); ?>
    <article class="card tip-card" data-tip-id="<?= e($tip['id']??'') ?>">
        <span class="tip-card__mark" aria-hidden="true">✦</span>
        <p class="tip-card__text"><?= e($tip['text']) ?></p>
        <?php if(!empty($tip['try'])): ?><p class="tip-card__try"><strong>همین حالا:</strong> <?= e($tip['try']) ?></p><?php endif; ?>
        <footer class="tip-card__foot"><span class="badge badge--soft"><?= e($tip['category']??'عمومی') ?></span></footer>
    </article>
    <?php return (string)ob_get_clean();
}

/* ردیف درس */
function lesson_row(array $lesson, int $stageIndex, int $lessonIndex, array $stage): string
{
    $href = url('lesson',['slug'=>(string)$lesson['slug']]);
    $number = fa_num(($stageIndex+1).'.'.($lessonIndex+1));
    ob_start(); ?>
    <li class="lesson-item" data-lesson-row="<?= e($lesson['slug']) ?>">
        <a class="lesson-item__link" href="<?= e($href) ?>">
            <span class="lesson-item__num"><?= e($number) ?></span>
            <span class="lesson-item__body">
                <span class="lesson-item__title"><?= e($lesson['title']) ?></span>
                <span class="lesson-item__goal"><?= e($lesson['goal']??'') ?></span>
            </span>
            <span class="lesson-item__side">
                <span class="chip chip--ghost" data-lesson-flag hidden>انجام شد</span>
                <span class="chip"><?= minutes_label((int)($lesson['minutes']??10)) ?></span>
            </span>
        </a>
    </li>
    <?php return (string)ob_get_clean();
}

/* کارت تمرین */
function exercise_card(array $ex, string $detailUrl=''): string
{
    ob_start(); ?>
    <article class="card exercise-card" data-exercise="<?= e($ex['id']) ?>">
        <header class="exercise-card__head">
            <span class="badge badge--level"><?= e($ex['level']??'عمومی') ?></span>
            <span class="badge badge--soft"><?= e($ex['focus']??'') ?></span>
        </header>
        <h3 class="exercise-card__title"><?= e($ex['title']) ?></h3>
        <p class="exercise-card__goal"><?= e($ex['goal']??'') ?></p>
        <ul class="exercise-card__steps">
<?php foreach((array)($ex['steps']??[]) as $i=>$step): ?>
            <li><span class="exercise-card__n"><?= fa_num($i+1) ?></span><span><?= e($step) ?></span></li>
<?php endforeach; ?>
        </ul>
        <div class="exercise-card__tools">
            <span class="muted-sm">
<?php if(!empty($ex['seconds'])): ?> <?= fa_num((int)ceil(((int)$ex['seconds'])/60)) ?> دقیقه تایمر
<?php else: ?> بدون تایمر، با چک‌لیست
<?php endif; ?> · <span data-exercise-runs="<?= e($ex['id']) ?>">۰</span> اجرا
            </span>
<?php if($detailUrl!==''): ?>
            <a class="btn btn--sm btn--primary" href="<?= e($detailUrl) ?>">شروع تمرین</a>
<?php else: ?>
            <button class="btn btn--sm btn--primary" type="button"
                    data-run-exercise="<?= e($ex['id']) ?>"
                    data-exercise-payload="<?= e(json_encode(['id'=>(string)$ex['id'],'title'=>(string)$ex['title'],'goal'=>(string)($ex['goal']??''),'seconds'=>(int)($ex['seconds']??0),'steps'=>(array)($ex['steps']??[]),'topics'=>(array)($ex['topics']??[]),'success'=>(string)($ex['success']??'')],JSON_UNESCAPED_UNICODE)) ?>">
                شروع تمرین
            </button>
<?php endif; ?>
        </div>
    </article>
    <?php return (string)ob_get_clean();
}

/* کارت دوره */
function course_card(array $course): string
{
    $href = url('course',['slug'=>$course['slug']]);
    $cat = find_category($course['category']??'');
    $lessons = (int)($course['lessons'] ?? array_sum(array_map(fn($s)=>count($s['lessons']??[]), $course['stages']??[])));
    $minutes = (int)($course['minutes'] ?? 0);
    if(!$minutes){
        foreach(($course['stages']??[]) as $st) foreach(($st['lessons']??[]) as $l) $minutes += (int)($l['minutes']??0);
    }
    ob_start(); ?>
    <article class="card course-card--grid">
        <div class="course-card__top">
            <span class="badge" style="--badge-bg:<?= e($cat['color']??'#0d5c4d') ?>;--badge-accent:<?= e($cat['accent']??'#2ec4a6') ?>"><?= e($cat['short']?? $cat['title']?? $course['category']) ?></span>
            <span class="chip chip--ghost"><?= e($course['level']??'') ?></span>
        </div>
        <h3 class="course-card__title"><a href="<?= e($href) ?>"><?= e($course['title']) ?></a></h3>
        <p class="course-card__excerpt"><?= e($course['excerpt']??'') ?></p>
        <div class="course-card__meta">
            <span><?= fa_num($lessons) ?> درس</span><span class="meta-dot"></span><span><?= minutes_label($minutes) ?></span>
        </div>
        <div class="course-card__foot">
            <a class="btn btn--primary btn--sm" href="<?= e($href) ?>">مشاهده دوره</a>
            <span class="muted-sm"><?= e($cat['title']??'') ?></span>
        </div>
    </article>
    <?php return (string)ob_get_clean();
}

/* کارت ویدیو */
function video_card(array $item): string
{
    $hasUrl = !empty($item['url']);
    $href = $hasUrl ? $item['url'] : url('videos');
    $cat = find_category($item['category']??'');
    ob_start(); ?>
    <article class="card media-card media-card--video">
        <div class="media-card__thumb">
            <span class="media-card__play" aria-hidden="true">▶</span>
            <span class="media-card__duration"><?= format_duration((int)($item['seconds']??0)) ?></span>
        </div>
        <div class="media-card__body">
            <div class="media-card__top">
                <span class="badge badge--soft"><?= e($cat['short']?? $item['category']) ?></span>
                <span class="muted-sm"><?= e($item['date_fa']??'') ?></span>
            </div>
            <h3 class="media-card__title"><?php if($hasUrl): ?><a href="<?= e($href) ?>"><?= e($item['title']) ?></a><?php else: ?><?= e($item['title']) ?><?php endif; ?></h3>
            <p class="media-card__excerpt"><?= e($item['excerpt']??'') ?></p>
        </div>
        <footer class="media-card__foot">
            <?php if($hasUrl): ?><a class="link-arrow" href="<?= e($href) ?>">تماشا</a><?php else: ?><span class="muted-sm">به‌زودی</span><?php endif; ?>
            <span class="chip chip--ghost">ویدیو</span>
        </footer>
    </article>
    <?php return (string)ob_get_clean();
}

/* کارت صوت / پادکست */
function audio_card(array $item): string
{
    $hasUrl = !empty($item['url']);
    $cat = find_category($item['category']??'');
    ob_start(); ?>
    <article class="card media-card media-card--audio">
        <div class="media-card__thumb media-card__thumb--audio">
            <span class="media-card__play" aria-hidden="true">🎧</span>
            <span class="media-card__duration"><?= format_duration((int)($item['seconds']??0)) ?></span>
        </div>
        <div class="media-card__body">
            <div class="media-card__top">
                <span class="badge badge--soft"><?= e($cat['short']?? $item['category']) ?></span>
                <span class="muted-sm"><?= e($item['date_fa']??'') ?></span>
            </div>
            <h3 class="media-card__title"><?= e($item['title']) ?></h3>
            <p class="media-card__excerpt"><?= e($item['excerpt']??'') ?></p>
            <?php if($hasUrl): ?>
                <audio controls preload="none" src="<?= e($item['url']) ?>" class="audio-player"></audio>
            <?php else: ?>
                <div class="audio-player audio-player--placeholder" role="note">فایل صوتی به‌زودی افزوده می‌شود — پخش‌کننده آماده است</div>
            <?php endif; ?>
        </div>
        <footer class="media-card__foot">
            <span class="chip chip--ghost">صوت</span>
            <span class="muted-sm"><?= e($cat['title']??'') ?></span>
        </footer>
    </article>
    <?php return (string)ob_get_clean();
}

/* کارت کتاب */
function book_card(array $book): string
{
    $href = url('books',['slug'=>$book['slug']]);
    $cat = find_category($book['category']??'');
    ob_start(); ?>
    <article class="card book-card">
        <div class="book-card__cover" aria-hidden="true"><span>📚</span></div>
        <div class="book-card__body">
            <div class="book-card__top">
                <span class="badge badge--soft"><?= e($cat['short']?? $book['category']) ?></span>
                <span class="muted-sm"><?= e($book['date_fa']??'') ?></span>
            </div>
            <h3 class="book-card__title"><a href="<?= e($href) ?>"><?= e($book['title']) ?></a></h3>
            <p class="book-card__author"><?= e($book['author']??'') ?></p>
            <p class="book-card__excerpt"><?= e($book['excerpt']??'') ?></p>
            <div class="book-card__tags">
                <?php foreach(array_slice((array)($book['tags']??[]),0,3) as $t): ?><span class="chip chip--ghost"><?= e($t) ?></span><?php endforeach; ?>
            </div>
        </div>
        <footer class="book-card__foot">
            <a class="link-arrow" href="<?= e($href) ?>">خلاصه و برداشت</a>
            <span class="muted-sm"><?= minutes_label((int)($book['minutes']??5)) ?></span>
        </footer>
    </article>
    <?php return (string)ob_get_clean();
}

/* کارت پژوهش */
function research_card(array $item): string
{
    $href = url('research',['slug'=>$item['slug']]);
    $cat = find_category($item['category']??'');
    ob_start(); ?>
    <article class="card research-card">
        <div class="research-card__top">
            <span class="badge"><?= e($cat['short']?? $item['category']) ?></span>
            <time class="muted-sm" datetime="<?= e($item['date_fa']??'') ?>"><?= e($item['date_fa']??'') ?></time>
        </div>
        <h3 class="research-card__title"><a href="<?= e($href) ?>"><?= e($item['title']) ?></a></h3>
        <p class="research-card__summary"><?= e($item['summary']??'') ?></p>
        <?php if(!empty($item['tags'])): ?>
        <div class="tag-row">
            <?php foreach(array_slice((array)$item['tags'],0,3) as $t): ?><span class="chip chip--ghost"><?= e($t) ?></span><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <footer class="research-card__foot">
            <a class="link-arrow" href="<?= e($href) ?>">مطالعه پژوهش</a>
        </footer>
    </article>
    <?php return (string)ob_get_clean();
}

/* کارت دسته */
function category_card(array $cat): string
{
    $href = url('category',['slug'=>$cat['slug']]);
    ob_start(); ?>
    <a class="card category-card" href="<?= e($href) ?>" style="--cat:<?= e($cat['color']) ?>;--cat-accent:<?= e($cat['accent']) ?>">
        <span class="category-card__icon" data-cat-icon="<?= e($cat['icon']) ?>" aria-hidden="true"></span>
        <h3 class="category-card__title"><?= e($cat['title']) ?></h3>
        <p class="category-card__desc"><?= e($cat['description']) ?></p>
        <span class="link-arrow">ورود به حوزه</span>
    </a>
    <?php return (string)ob_get_clean();
}

function progress_bar(int $percent, string $label=''): string
{
    $percent = max(0,min(100,$percent));
    ob_start(); ?>
    <div class="progress" role="progressbar" aria-valuenow="<?= (int)$percent ?>" aria-valuemin="0" aria-valuemax="100">
        <span class="progress__bar" style="--progress: <?= $percent ?>%"></span>
<?php if($label!==''): ?><span class="progress__label"><?= e($label) ?></span><?php endif; ?>
    </div>
    <?php return (string)ob_get_clean();
}

function empty_state(string $title, string $text, string $url='', string $label=''): string
{
    ob_start(); ?>
    <div class="empty-state">
        <p class="empty-state__title"><?= e($title) ?></p>
        <p class="empty-state__text"><?= e($text) ?></p>
<?php if($url!==''): ?><a class="btn btn--ghost btn--sm" href="<?= e($url) ?>"><?= e($label) ?></a><?php endif; ?>
    </div>
    <?php return (string)ob_get_clean();
}

function not_found(string $label): string
{
    ob_start(); ?>
    <section class="container section">
        <div class="not-found">
            <h1>«<?= e($label) ?>» پیدا نشد</h1>
            <p>ممکن است نشانی را اشتباه وارد کرده باشید یا محتوا جابه‌جا شده باشد.</p>
            <div class="btn-row">
                <a class="btn btn--primary" href="<?= e(url('articles')) ?>">دیدن مقاله‌ها</a>
                <a class="btn btn--ghost" href="<?= e(url('courses')) ?>">دوره‌ها</a>
            </div>
        </div>
    </section>
    <?php return (string)ob_get_clean();
}

function tag_list(array $tags): string
{
    if($tags===[]) return '';
    $out='<ul class="tag-list">'; foreach($tags as $tag) $out.='<li><a href="'.e(url('search',['q'=>$tag])).'">#'.e($tag).'</a></li>'; return $out.'</ul>';
}

function breadcrumbs(array $items): string
{
    ob_start(); ?>
    <nav class="breadcrumbs" aria-label="مسیر صفحه">
        <a href="<?= e(url('home')) ?>">خانه</a>
        <?php foreach($items as $it): ?>
            <span aria-hidden="true">/</span>
            <?php if(!empty($it['url'])): ?><a href="<?= e($it['url']) ?>"><?= e($it['label']) ?></a><?php else: ?><span aria-current="page"><?= e($it['label']) ?></span><?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php return (string)ob_get_clean();
}
