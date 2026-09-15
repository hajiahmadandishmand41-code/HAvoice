<?php
/**
 * HAvoice 2.0 — صفحه‌ی درس
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$slug   = (string)$GLOBALS['HA_SLUG'];
$lesson = course_find_lesson($slug);

if ($lesson===null){
    http_response_code(404);
    echo not_found('درس موردنظر');
    return;
}

$data     = $lesson['lesson'];
$stage    = $lesson['stage'];
$course   = $lesson['course'] ?? find_course($stage['category'] ?? '') ?? courses()[0];
$neigh    = course_neighbours($slug);
/* شماره‌ی «گام X از Y» درونِ همان دوره است، نه در میانِ همه‌ی دوره‌های سایت */
$total    = (int)($lesson['courseTotal'] ?? $lesson['total']);
$position = (int)($lesson['coursePosition'] ?? $lesson['position']);
$cat      = find_category($course['category'] ?? '');

/* پیش‌نیازِ درس (اختیاری): نمایش فقط وقتی که تعریف شده و قابلِ حل است */
$prereqSlug = slugify((string)($data['prerequisite'] ?? ''));
$prereq     = $prereqSlug !== '' ? course_find_lesson($prereqSlug) : null;

// مرتبط‌ها: مقاله‌های همین حوزه، ویدیو/صوت، پژوهش
$relatedVideos = array_slice(array_filter(videos(), fn($v)=>($v['category']??'')===($course['category']??'')),0,2);
$relatedAudios = array_slice(array_filter(audios(), fn($a)=>($a['category']??'')===($course['category']??'')),0,2);
$relatedArticles = array_slice(array_filter(all_articles_sorted(), fn($a)=> strpos(article_text_index($a), normalize_persian($data['title']??''))!==false ),0,2);
if(count($relatedArticles)<2) $relatedArticles = latest_articles(2);
?>

<article class="lesson">
    <header class="lesson__head">
        <div class="container container--narrow">
            <?= breadcrumbs([
                ['label'=>'دوره‌ها','url'=>url('courses')],
                ['label'=>$course['title']??$stage['title'],'url'=>url('course',['slug'=>$course['slug']??''])],
                ['label'=>$data['title']],
            ]) ?>
            <p class="eyebrow"><?= e($cat['title'] ?? $course['title'] ?? $stage['label'] ?? 'مرحله') ?> · درس <?= fa_ordinal($position,$total) ?></p>
            <h1 class="lesson__title"><?= e($data['title']) ?></h1>
            <p class="lesson__goal"><?= e($data['goal'] ?? '') ?></p>
            <div class="lesson__meta">
                <span class="chip"><?= e(minutes_label((int)($data['minutes']??10))) ?></span>
                <span class="chip chip--soft">گام <?= fa_num($position) ?> از <?= fa_num($total) ?></span>
                <?php if(!empty($course['level'])): ?><span class="chip chip--soft"><?= e($course['level']) ?></span><?php endif; ?>
                <?php if($cat): ?><span class="badge"><?= e($cat['short']) ?></span><?php endif; ?>
            </div>
            <?php if($prereq !== null): ?>
            <p class="lesson__prereq">
                <?= ha_icon('steps', 14) ?>
                پیش‌نیاز این درس: <a href="<?= e(url('lesson',['slug'=>(string)$prereq['lesson']['slug']])) ?>"><?= e($prereq['lesson']['title'] ?? '') ?></a>
            </p>
            <?php endif; ?>
        </div>
    </header>

    <div class="lesson__body">
        <div class="container container--narrow">
            <div class="prose">
                <?= render_blocks((array)($data['blocks'] ?? [])) ?>
            </div>

            <?php if(!empty($data['drill'])): ?>
                <?= render_drill((array)$data['drill']) ?>
            <?php endif; ?>

            <?php
            /* منابع و مراجعِ درس (اختیاری) — ادعاهای علمی قابلِ ردیابی می‌شوند */
            $lessonRefs = array_values(array_filter(array_map('trim', (array)($data['refs'] ?? [])), fn($r) => $r !== ''));
            ?>
            <?php if($lessonRefs !== []): ?>
            <section class="lesson-refs" aria-label="منابع و مراجع">
                <h2 class="lesson-refs__title"><?= ha_icon('research', 15) ?> منابع و بیشتر بخوانید</h2>
                <ul class="rich-list">
                    <?php foreach($lessonRefs as $ref): ?><li><?= e($ref) ?></li><?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>

            <?php
            /* تمرین‌های متصل به همین درس (از پنل: exercise.lesson = نامکِ درس) —
               زنجیره‌ی کامل «حوزه → دوره → درس → تمرین». */
            $linkedExercises = exercises_for_lesson((string) ($data['slug'] ?? ''));
            ?>
            <?php if($linkedExercises !== []): ?>
            <section class="lesson-exercises" aria-label="تمرین‌های این درس">
                <h2 class="lesson-exercises__title"><?= ha_icon('timer', 17) ?> تمرین‌های همین درس</h2>
                <p class="muted-sm mb-sm">این درس با <?= fa_num(count($linkedExercises)) ?> تمرینِ اختصاصی کامل می‌شود؛ پس از مطالعه حتماً اجرا کنید.
                    <a class="link-arrow" href="<?= e(url('exercises', ['lesson' => (string) ($data['slug'] ?? '')])) ?>">همه‌ی تمرین‌های این درس</a></p>
                <div class="grid grid--2">
                    <?php foreach($linkedExercises as $lex): ?>
                        <?= exercise_card($lex, url('exercises') . '#ex-' . (string) ($lex['id'] ?? '')) ?>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php elseif(!empty($data['drill'])): ?>
            <div class="card side-card mt-md">
                <h2>بعد از درس — تمرینِ پیشنهادی</h2>
                <p class="muted-sm">این درس را با تایمر و چک‌لیست در صفحه‌ی تمرین‌ها کامل کنید.</p>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('exercises')) ?>">رفتن به تمرین‌ها</a>
            </div>
            <?php endif; ?>

            <!-- منابع مرتبط -->
            <?php if($relatedVideos || $relatedAudios): ?>
            <div class="card side-card mt-md">
                <h2>منابعِ مرتبط همین درس</h2>
                <ul class="rich-list">
                    <?php foreach($relatedVideos as $v): ?><li><strong>ویدیو:</strong> <?= e($v['title']) ?> — <span class="muted-sm"><?= format_duration((int)($v['seconds']??0)) ?></span></li><?php endforeach; ?>
                    <?php foreach($relatedAudios as $a): ?><li><strong>صوت:</strong> <?= e($a['title']) ?> — <span class="muted-sm"><?= format_duration((int)($a['seconds']??0)) ?></span></li><?php endforeach; ?>
                    <?php foreach($relatedArticles as $ra): ?><li><strong>مقاله:</strong> <a href="<?= e(url('article',['slug'=>$ra['slug']])) ?>"><?= e($ra['title']) ?></a></li><?php endforeach; ?>
                </ul>
                <p class="muted-sm">میزانِ پیشرفتِ شما در همین مرورگر ذخیره می‌شود.</p>
            </div>
            <?php endif; ?>

            <div class="lesson__actions card">
                <div>
                    <h2>این درس را انجام دادید؟</h2>
                    <p class="muted-sm">با تیک زدن، پیشرفت شما در همین مرورگر ذخیره می‌شود.</p>
                </div>
                <button class="btn btn--primary" type="button" data-lesson-complete="<?= e($data['slug']) ?>" aria-pressed="false">
                    <span data-lesson-complete-label>علامت‌گذاری به‌عنوان انجام‌شده</span>
                </button>
            </div>

            <nav class="pager" aria-label="درس قبلی و بعدی">
                <?php if($neigh['prev']!==null): ?>
                    <a class="pager__link pager__link--prev" href="<?= e(url('lesson',['slug'=>$neigh['prev']['slug']])) ?>">
                        <span class="pager__label">درس قبلی</span><span class="pager__title"><?= e($neigh['prev']['title']) ?></span>
                    </a>
                <?php else: ?><span class="pager__spacer" aria-hidden="true"></span><?php endif; ?>
                <?php if($neigh['next']!==null): ?>
                    <a class="pager__link pager__link--next" href="<?= e(url('lesson',['slug'=>$neigh['next']['slug']])) ?>">
                        <span class="pager__label">درس بعدی</span><span class="pager__title"><?= e($neigh['next']['title']) ?></span>
                    </a>
                <?php else: ?>
                    <a class="pager__link pager__link--next" href="<?= e(url('exercises')) ?>"><span class="pager__label">پایان دوره</span><span class="pager__title">رفتن به تمرین‌ها</span></a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</article>
