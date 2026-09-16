<?php
/**
 * HAvoice — صفحه‌ی درس
 * مسیر: Category → Course → Stage → Lesson → Exercise
 *
 * کاربر در این صفحه همیشه سه چیز را می‌بیند:
 *   ۱. کجای دوره هستم (پنلِ مسیرِ یادگیری + «گام x از y»)
 *   ۲. این درس چه محتوایی دارد (متن، ویدیو، پادکست، فایلِ PDF، تمرین)
 *   ۳. قدمِ بعدی چیست (نوارِ «تکمیل شد — درسِ بعدی» + پیجر)
 *
 * پیجر فقط داخلِ همان دوره جابه‌جا می‌شود؛ آخرین درس → «پایان دوره».
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$slug   = (string) $GLOBALS['HA_SLUG'];
$lesson = course_find_lesson($slug);

if ($lesson === null) {
    http_response_code(404);
    echo not_found('درس موردنظر');
    return;
}

$data     = $lesson['lesson'];
$stage    = $lesson['stage'];
$course   = $lesson['course'] ?? null;
if (!is_array($course) || empty($course['slug'])) {
    $course = find_course((string) ($course['slug'] ?? '')) ?? (courses()[0] ?? ['slug' => '', 'title' => 'دوره', 'category' => '']);
}
$neigh    = course_neighbours($slug);
$total    = (int) ($lesson['courseTotal'] ?? $lesson['total'] ?? 0);
$position = (int) ($lesson['coursePosition'] ?? $lesson['position'] ?? 0);
$cat      = find_category((string) ($course['category'] ?? ''));
$courseSlug = slugify((string) ($course['slug'] ?? ''));
$lessonSlugs = course_lesson_slugs($course);

$prereqSlug = slugify((string) ($data['prerequisite'] ?? ''));
$prereq     = $prereqSlug !== '' ? course_find_lesson($prereqSlug) : null;

$linkedExercises = exercises_for_lesson((string) ($data['slug'] ?? ''));
$linkedMedia     = media_for_context($courseSlug, (string) ($data['slug'] ?? ''));
$drill           = (array) ($data['drill'] ?? []);
$lessonRefs      = array_values(array_filter(array_map('trim', (array) ($data['refs'] ?? [])), static fn($r) => $r !== ''));

/* مسیرِ یادگیریِ این درس: قبلی/بعدی + تمرینِ این درس + تمرینِ بعدی */
$lp        = lesson_learning_path($slug);
$lpState   = (string) ($lp['state'] ?? '');

/* بازکردنِ صفحه‌ی درس = «در حالِ مطالعه» (فقط اگر هنوز وضعیتی ندارد) */
if ($lpState === '') {
    progress_mark_started($slug);
    $lpState = progress_lesson_state($slug);
}

/* رسانه‌ی خودِ درس (فایل/ویدیو/صوتی که مدیر روی همان درس گذاشته) */
$lessonFile  = ha_safe_file_url((string) ($data['file'] ?? ''));
$lessonVideo = ha_safe_media_url((string) ($data['video'] ?? ''));
$lessonAudio = ha_safe_media_url((string) ($data['audio'] ?? ''));

/* نکات و اشتباهات رایج از بلوک‌های tip استخراج می‌شوند (اگر جداگانه نبودند) */
$tipWarns = [];
$tipChecks = [];
foreach ((array) ($data['blocks'] ?? []) as $b) {
    if (($b['type'] ?? '') !== 'tip') {
        continue;
    }
    $tone = (string) ($b['tone'] ?? 'tip');
    $line = trim((string) ($b['title'] ?? '') . ': ' . ($b['text'] ?? ''), ': ');
    if ($line === '') {
        continue;
    }
    if ($tone === 'warn') {
        $tipWarns[] = $line;
    } elseif (in_array($tone, ['check', 'idea', 'tip'], true)) {
        $tipChecks[] = $line;
    }
}
?>

<article class="lesson" data-course-slug="<?= e($courseSlug) ?>" data-course-lessons="<?= e(implode(',', $lessonSlugs)) ?>" data-lesson-slug="<?= e($slug) ?>">
    <header class="lesson__head">
        <div class="container container--narrow">
            <?= breadcrumbs([
                ['label' => 'دوره‌ها', 'url' => url('courses')],
                ['label' => (string) ($course['title'] ?? 'دوره'), 'url' => url('course', ['slug' => $courseSlug])],
                ['label' => (string) ($stage['title'] ?? $stage['label'] ?? 'مرحله'), 'url' => url('course', ['slug' => $courseSlug]) . '#' . e((string) ($stage['id'] ?? ''))],
                ['label' => (string) ($data['title'] ?? '')],
            ]) ?>
            <p class="eyebrow">
                <?= e($cat['title'] ?? $course['title'] ?? 'دوره') ?>
                · <?= e($stage['label'] ?? $stage['title'] ?? 'مرحله') ?>
                · درس <?= fa_ordinal($position, $total) ?>
            </p>
            <h1 class="lesson__title"><?= e($data['title'] ?? '') ?></h1>
            <?php if (!empty($data['goal'])): ?>
                <p class="lesson__goal"><strong>هدف درس:</strong> <?= e($data['goal']) ?></p>
            <?php endif; ?>
            <div class="lesson__meta">
                <span class="chip"><?= e(minutes_label((int) ($data['minutes'] ?? 10))) ?></span>
                <span class="chip chip--soft">گام <?= fa_num($position) ?> از <?= fa_num($total) ?></span>
                <?php if (!empty($course['level'])): ?><span class="chip chip--soft"><?= e($course['level']) ?></span><?php endif; ?>
                <?php if ($cat): ?><span class="badge"><?= e($cat['short'] ?? $cat['title']) ?></span><?php endif; ?>
                <?= status_pill($lpState) ?>
            </div>
            <?php if ($prereq !== null): ?>
            <p class="lesson__prereq">
                <?= ha_icon('steps', 14) ?>
                پیش‌نیاز: <a href="<?= e(url('lesson', ['slug' => (string) ($prereq['lesson']['slug'] ?? '')])) ?>"><?= e($prereq['lesson']['title'] ?? '') ?></a>
            </p>
            <?php endif; ?>
        </div>
    </header>

    <div class="lesson__body">
        <div class="container container--narrow">

            <?php /* مسیرِ یادگیری — کاربر همیشه بداند کجاست و قدمِ بعد چیست */ ?>
            <?php if (!empty($lp['path'])): ?>
                <?= learning_flow((array) $course, (array) $lp['path'], ['mode' => 'lesson', 'currentSlug' => $slug]) ?>
            <?php endif; ?>

            <div class="prose" aria-label="محتوای درس">
                <?= render_blocks((array) ($data['blocks'] ?? [])) ?>
            </div>

            <?php /* رسانه‌ی خودِ درس: ویدیو، پادکست و فایلِ PDF — داخلِ سایت */ ?>
            <?php if ($lessonVideo !== '' || $lessonAudio !== '' || $lessonFile !== ''): ?>
            <section class="lesson-media" aria-label="فایل‌ها و رسانه‌ی این درس">
                <h2 class="lesson-media__title"><?= ha_icon('video', 16) ?> رسانه‌ی این درس</h2>
                <?php if ($lessonVideo !== ''): ?>
                <div class="lesson-media__item">
                    <?= media_player(['type' => 'video', 'title' => (string) ($data['title'] ?? 'ویدیوی درس'), 'url' => $lessonVideo]) ?>
                </div>
                <?php endif; ?>
                <?php if ($lessonAudio !== ''): ?>
                <div class="lesson-media__item">
                    <p class="lesson-media__label"><?= ha_icon('headphones', 14) ?> نسخه‌ی صوتیِ درس (پادکست)</p>
                    <?= media_player(['type' => 'audio', 'title' => (string) ($data['title'] ?? 'صوتِ درس'), 'url' => $lessonAudio]) ?>
                </div>
                <?php endif; ?>
                <?php if ($lessonFile !== ''): ?>
                <div class="lesson-media__item">
                    <?= pdf_viewer($lessonFile, 'فایلِ این درس', 'اگر نمایش‌دهنده‌ی PDF روی دستگاهِ شما باز نشد، از دکمه‌ی «تبِ جدید» یا «دریافت» استفاده کنید.') ?>
                </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <?php if ($tipWarns !== [] || $tipChecks !== []): ?>
            <aside class="lesson-callouts" aria-label="نکات کلیدی">
                <?php if ($tipChecks !== []): ?>
                <div class="lesson-callouts__box lesson-callouts__box--ok">
                    <h2 class="h3"><?= ha_icon('check', 16) ?> نکات کلیدی</h2>
                    <ul class="rich-list">
                        <?php foreach (array_slice($tipChecks, 0, 4) as $t): ?><li><?= e($t) ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if ($tipWarns !== []): ?>
                <div class="lesson-callouts__box lesson-callouts__box--warn">
                    <h2 class="h3"><?= ha_icon('alert', 16) ?> اشتباهات رایج</h2>
                    <ul class="rich-list">
                        <?php foreach (array_slice($tipWarns, 0, 4) as $t): ?><li><?= e($t) ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </aside>
            <?php endif; ?>

            <?php if ($drill !== []): ?>
                <?= render_drill($drill) ?>
                <?php if (!empty($drill['success'])): ?>
                <p class="lesson-outcome">
                    <?= ha_icon('target', 15) ?>
                    <strong>نتیجه مورد انتظار:</strong> <?= e($drill['success']) ?>
                </p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($lessonRefs !== []): ?>
            <section class="lesson-refs" aria-label="منابع و مراجع">
                <h2 class="lesson-refs__title"><?= ha_icon('research', 15) ?> منابع و بیشتر بخوانید</h2>
                <ul class="rich-list">
                    <?php foreach ($lessonRefs as $ref): ?><li><?= e($ref) ?></li><?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>

            <?php if ($linkedExercises !== []): ?>
            <section class="lesson-exercises" aria-label="تمرین‌های این درس">
                <h2 class="lesson-exercises__title"><?= ha_icon('timer', 17) ?> تمرین‌های همین درس</h2>
                <p class="muted-sm mb-sm">
                    <?= fa_num(count($linkedExercises)) ?> تمرین اختصاصی برای تثبیت این درس.
                    <a class="link-arrow" href="<?= e(url('exercises', ['lesson' => (string) ($data['slug'] ?? '')])) ?>">همه‌ی تمرین‌های این درس</a>
                </p>
                <div class="grid grid--2">
                    <?php foreach ($linkedExercises as $lex): ?>
                        <?= exercise_card($lex, url('exercises') . '#ex-' . (string) ($lex['id'] ?? '')) ?>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php elseif ($drill !== []): ?>
            <div class="card side-card mt-md">
                <h2>تمرین همین درس</h2>
                <p class="muted-sm">تمرین بالا را انجام دهید؛ معیار سنجش همان «نتیجه مورد انتظار» است.</p>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('exercises', ['course' => $courseSlug])) ?>">رفتن به تمرین‌های این دوره</a>
            </div>
            <?php elseif (!empty($lp['exercise'])): ?>
            <div class="card side-card mt-md">
                <h2><?= ha_icon('timer', 16) ?> تمرینِ این مرحله</h2>
                <p class="muted-sm">تمرینِ پیشنهادی: <strong><?= e((string) $lp['exercise']['title']) ?></strong></p>
                <a class="btn btn--ghost btn--sm" href="<?= e((string) $lp['exercise']['url']) ?>">شروع تمرین</a>
            </div>
            <?php endif; ?>

            <?php if ($linkedMedia !== []): ?>
            <div class="card side-card mt-md">
                <h2>رسانه‌ی مرتبط</h2>
                <div class="grid grid--2">
                    <?php foreach ($linkedMedia as $m): ?>
                        <?php if (($m['type'] ?? '') === 'video'): ?>
                            <?= video_card($m) ?>
                        <?php else: ?>
                            <?= audio_card($m) ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="lesson__actions card">
                <div>
                    <h2>این درس را تمام کردید؟</h2>
                    <p class="muted-sm">
                        با «تکمیل شد»، وضعیتِ درس در حسابِ شما ثبت می‌شود و مستقیم به قدمِ بعدی می‌روید.
                        <?php if (!empty($lp['next'])): ?>
                            قدمِ بعدی: <strong><?= e((string) $lp['next']['title']) ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="lesson__actions-btns">
                    <?= lesson_complete_form($slug, $lpState, $lp['next'] !== null ? 'next-lesson' : 'course') ?>
                    <?php if (!empty($lp['exercise'])): ?>
                    <a class="btn btn--ghost btn--sm" href="<?= e((string) $lp['exercise']['url']) ?>"><?= ha_icon('timer', 14) ?> تمرینِ این درس</a>
                    <?php endif; ?>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('course', ['slug' => $courseSlug])) ?>"><?= ha_icon('arrow-right', 14) ?> بازگشت به دوره</a>
                </div>
            </div>

            <?= comments_teaser(2, 'تجربه‌ی دیگران از این درس') ?>

            <nav class="pager" aria-label="درس قبلی و بعدی">
                <?php if ($neigh['prev'] !== null): ?>
                    <a class="pager__link pager__link--prev" href="<?= e(url('lesson', ['slug' => (string) $neigh['prev']['slug']])) ?>">
                        <span class="pager__label">درس قبلی</span>
                        <span class="pager__title"><?= e($neigh['prev']['title'] ?? '') ?></span>
                    </a>
                <?php else: ?>
                    <a class="pager__link pager__link--prev" href="<?= e(url('course', ['slug' => $courseSlug])) ?>">
                        <span class="pager__label">بازگشت</span>
                        <span class="pager__title">صفحه‌ی دوره</span>
                    </a>
                <?php endif; ?>

                <?php if ($neigh['next'] !== null): ?>
                    <a class="pager__link pager__link--next" href="<?= e(url('lesson', ['slug' => (string) $neigh['next']['slug']])) ?>">
                        <span class="pager__label">درس بعدی</span>
                        <span class="pager__title"><?= e($neigh['next']['title'] ?? '') ?></span>
                    </a>
                <?php else: ?>
                    <div class="pager__link pager__link--next pager__link--end course-end-cta">
                        <span class="pager__label"><?= ha_icon('check', 14) ?> پایان دوره</span>
                        <span class="pager__title"><?= e($course['title'] ?? 'این دوره') ?></span>
                        <span class="course-end-cta__actions">
                            <a class="btn btn--primary btn--sm" href="<?= e(url('course', ['slug' => $courseSlug])) ?>">مشاهده‌ی دوره</a>
                            <a class="btn btn--ghost btn--sm" href="<?= e(url('exercises', ['course' => $courseSlug])) ?>">تمرین‌ها</a>
                            <a class="btn btn--ghost btn--sm" href="<?= e(url('courses')) ?>">دوره‌های دیگر</a>
                        </span>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <?= lesson_next_bar($lp, $slug, $courseSlug) ?>
</article>
