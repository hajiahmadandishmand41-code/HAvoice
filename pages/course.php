<?php
/**
 * HAvoice — صفحه‌ی دوره
 *
 * ترتیبِ نمایش عمداً همین است (ساده و قابلِ پیش‌بینی):
 *   ۱. شناسنامه‌ی دوره (حوزه، سطح، تعدادِ درس، زمان)
 *   ۲. پنلِ «مسیرِ یادگیری»: دوره ← درسِ فعلی ← درسِ بعدی ← تمرین ← تمرینِ بعدی
 *   ۳. سرفصل‌ها (مرحله‌ها و درس‌ها با وضعیتِ هر درس)
 *   ۴. رسانه، تمرین‌ها، پروژه‌ی نهایی، مقاله‌های مرتبط و تجربیاتِ کاربران
 *
 * وضعیتِ درس‌ها (انجام‌نشده / در حالِ مطالعه / تکمیل‌شده) از پیشرفتِ
 * ذخیره‌شده‌ی کاربر می‌آید (includes/progress.php) — سمتِ سرور رندر
 * می‌شود، پس بدونِ JS هم درست است.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$slug = slugify((string)($GLOBALS['HA_SLUG'] ?? param('slug')));

// اگر slug نداریم: اگر فقط یک دوره داریم یا برای سازگاری قدیم، همان course() قدیم را نمایش بده (دوره پایه)
if ($slug==='') {
    // نمایش لیست دوره‌ها به عنوان fallback (یا اولین دوره)
    $first = courses()[0] ?? null;
    if ($first) {
        $slug = $first['slug'];
    }
}

$courseData = $slug ? find_course($slug) : null;

// سازگاری: اگر course قدیم درخواست شده با /course بدون slug، اولین دوره را نشان بده
if ($courseData===null && $slug==='') {
    $courseData = course(); // legacy includes stages directly
    // تبدیل legacy به ساختار دوره
    if (isset($courseData['stages']) && !isset($courseData['slug'])) {
        $courseData = [
            'slug'=>'public-speaking-fundamentals',
            'title'=>$courseData['title']??'مسیر آموزشی',
            'category'=>'public-speaking',
            'level'=>'مقدماتی',
            'excerpt'=>$courseData['intro']??'',
            'intro'=>$courseData['intro']??'',
            'how_to'=>$courseData['how_to']??[],
            'stages'=>$courseData['stages']??[],
        ];
    }
}

if ($courseData===null) {
    http_response_code(404);
    echo not_found('دوره');
    return;
}

$stages = (array)($courseData['stages'] ?? []);
$cat = find_category($courseData['category'] ?? '');
$totalLessons = 0; $totalMinutes=0;
foreach ($stages as $st) { $totalLessons+= count($st['lessons']??[]); foreach(($st['lessons']??[]) as $l) $totalMinutes+=(int)($l['minutes']??0); }

/* مسیرِ یادگیریِ این دوره برای کاربرِ جاری (درسِ فعلی/بعدی + تمرین‌ها) */
$courseSlug      = slugify((string) ($courseData['slug'] ?? ''));
$learningPath    = course_learning_path($courseData);
$pathSummary     = (array) ($learningPath['summary'] ?? []);
$currentLesson   = $learningPath['current'] ?? null;
$nextLesson      = $learningPath['next'] ?? null;
$currentSlug     = (string) ($currentLesson['slug'] ?? '');
$nextSlug        = (string) ($nextLesson['slug'] ?? '');
$courseExercisesAll = $learningPath['exercises'];
$courseExercises = array_slice($courseExercisesAll, 0, 6);
$courseMedia     = media_for_context($courseSlug);
$courseLessonSlugs = course_lesson_slugs($courseData);
$firstSlug = $stages[0]['lessons'][0]['slug'] ?? '';

// محتوای مرتبط
/* مقاله‌های مرتبط با همین حوزه.
   نسخه‌ی پیشین هیچ فیلتری نداشت (کامنت می‌گفت «اگر دسته مرتبط باشد» ولی
   کد فقط سه مقاله‌ی آخر را برمی‌داشت). اکنون موضوعِ فارسیِ مقاله با
   find_category_by_title() به حوزه نگاشت می‌شود و واقعاً فیلتر می‌شود؛
   اگر به حدِ نصاب نرسید، با تازه‌ترین‌ها کامل می‌شود. */
$courseCatSlug = (string) ($courseData['category'] ?? '');
$relatedArticles = array_values(array_filter(all_articles_sorted(), static function ($a) use ($courseCatSlug) {
    $mapped = find_category_by_title((string) ($a['category'] ?? ''));
    return $mapped !== null && (string) ($mapped['slug'] ?? '') === $courseCatSlug;
}));
$relatedArticles = array_slice($relatedArticles, 0, 3);
if (count($relatedArticles) < 3) {
    foreach (latest_articles(6) as $a) {
        if (count($relatedArticles) >= 3) { break; }
        $dup = false;
        foreach ($relatedArticles as $r) { if (($r['slug'] ?? '') === ($a['slug'] ?? '')) { $dup = true; break; } }
        if (!$dup) { $relatedArticles[] = $a; }
    }
}
?>

<section class="section section--tight" data-course-slug="<?= e($courseSlug) ?>" data-course-lessons="<?= e(implode(',', $courseLessonSlugs)) ?>">
    <div class="container">
        <div class="chip-row">
            <?php if($cat): ?><span class="badge"><?= e($cat['title']) ?></span><?php endif; ?>
            <span class="chip chip--ghost"><?= e($courseData['level']??'') ?></span>
            <span class="muted-sm"><?= fa_num($totalLessons) ?> درس · <?= minutes_label($totalMinutes) ?></span>
            <?php if ((int) ($pathSummary['done'] ?? 0) > 0): ?>
            <span class="muted-sm"><?= ha_icon('check', 12) ?> <?= fa_num((int) $pathSummary['done']) ?> درس را تمام کرده‌اید</span>
            <?php endif; ?>
        </div>
        <?php if(!empty($courseData['prereq'])): ?>
        <p class="course-prereq"><?= ha_icon('steps', 14) ?> <?= e($courseData['prereq']) ?></p>
        <?php endif; ?>

        <?php /* ---- ۲) مسیرِ یادگیری: مهم‌ترین بخشِ صفحه ---- */ ?>
        <?= learning_flow($courseData, $learningPath, ['mode' => 'course']) ?>

        <div class="course-layout">
            <aside class="course-aside">
                <div class="card course-card" data-course-card>
                    <h2 class="course-card__title">پیشرفت این دوره</h2>
                    <p class="course-card__muted">
                        <?php if ($currentLesson !== null && ($currentLesson['state'] ?? '') !== 'done'): ?>
                            ادامه از: <strong><?= e((string) $currentLesson['title']) ?></strong>
                        <?php elseif ($totalLessons > 0): ?>
                            همه‌ی درس‌های این دوره را تمام کرده‌اید.
                        <?php else: ?>
                            این دوره هنوز درسی ندارد.
                        <?php endif; ?>
                    </p>
                    <div data-total-progress><?= progress_bar((int) ($pathSummary['percent'] ?? 0), fa_num((int) ($pathSummary['percent'] ?? 0)) . '٪', 'پیشرفت این دوره') ?></div>
                    <ul class="course-card__legend">
                        <li><strong data-count-done><?= fa_num((int) ($pathSummary['done'] ?? 0)) ?></strong> درس تکمیل‌شده</li>
                        <li><strong><?= fa_num((int) ($pathSummary['started'] ?? 0)) ?></strong> در حالِ مطالعه</li>
                        <li><strong><?= fa_num($totalLessons) ?></strong> درس این دوره</li>
                        <li><strong><?= e(minutes_label($totalMinutes)) ?></strong> زمان مطالعه</li>
                    </ul>
                    <?php if ($currentLesson !== null): ?>
                    <a class="btn btn--primary btn--sm btn--block" href="<?= e((string) $currentLesson['url']) ?>">
                        <?= ha_icon('play', 15) ?> <?= (int) ($pathSummary['done'] ?? 0) > 0 ? 'ادامه‌ی یادگیری' : 'شروع / ادامه' ?>
                    </a>
                    <?php elseif ($firstSlug !== ''): ?>
                    <a class="btn btn--primary btn--sm btn--block" href="<?= e(url('lesson', ['slug' => (string) $firstSlug])) ?>">شروع / ادامه</a>
                    <?php endif; ?>
                    <?php if ($nextLesson !== null): ?>
                    <a class="btn btn--ghost btn--sm btn--block" href="<?= e((string) $nextLesson['url']) ?>"><?= ha_icon('arrow-left', 14) ?> درسِ بعدی: <?= e((string) $nextLesson['title']) ?></a>
                    <?php endif; ?>
                    <a class="btn btn--ghost btn--sm btn--block" href="<?= e(url('progress')) ?>"><?= ha_icon('growth', 14) ?> پیشرفتِ همه‌ی دوره‌ها</a>
                    <?php if ($totalLessons > 0): ?><?= progress_reset_form($courseSlug) ?><?php endif; ?>
                </div>

                <?php if (!empty($courseData['how_to'])): ?>
                <div class="card how-card">
                    <h2>چطور پیش برویم؟</h2>
                    <ul class="rich-list">
                        <?php foreach((array)($courseData['how_to']??[]) as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if($courseMedia): ?>
                <div class="card">
                    <h2><?= ha_icon('video', 15) ?> رسانه‌ی این دوره</h2>
                    <p class="muted-sm mb-sm">ویدیو و پادکستِ دوره، همین‌جا داخلِ سایت پخش می‌شود.</p>
                    <ul class="media-stack">
                        <?php foreach(array_slice($courseMedia, 0, 4) as $m): ?>
                        <li class="media-stack__item">
                            <div class="media-stack__head">
                                <span class="badge badge--soft"><?= ($m['type'] ?? '') === 'video' ? ha_icon('play', 11) . ' ویدیو' : ha_icon('headphones', 11) . ' صوت' ?></span>
                                <span class="media-stack__title"><?= e($m['title'] ?? '') ?></span>
                            </div>
                            <?= media_player($m) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (count($courseMedia) > 4): ?>
                    <a class="btn btn--ghost btn--sm btn--block" href="<?= e(url('videos')) ?>">همه‌ی ویدیوها</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if($courseExercises): ?>
                <div class="card">
                    <h2><?= ha_icon('timer', 15) ?> تمرین‌های این دوره</h2>
                    <p class="muted-sm mb-sm">
                        <?= fa_num((int) ($learningPath['exercise_summary']['done'] ?? 0)) ?> از
                        <?= fa_num((int) ($learningPath['exercise_summary']['total'] ?? 0)) ?> تمرین انجام شده.
                        <?php if (!empty($learningPath['exercise_current']) && ($learningPath['exercise_current']['state'] ?? '') !== 'done'): ?>
                            تمرینِ بعدی: <strong><?= e((string) $learningPath['exercise_current']['title']) ?></strong>
                        <?php endif; ?>
                    </p>
                    <ul class="rich-list exercise-mini-list">
                        <?php foreach($courseExercises as $cex): ?>
                        <li class="exercise-mini-list__item <?= e(progress_state_meta((string) ($cex['state'] ?? ''))['class']) ?>">
                            <a href="<?= e((string) $cex['url']) ?>"><?= e($cex['title'] ?? '') ?></a>
                            <?php if (!empty($cex['lessonTitle'])): ?>
                                <span class="muted-sm">— <?= e((string) $cex['lessonTitle']) ?></span>
                            <?php endif; ?>
                            <?= status_pill((string) ($cex['state'] ?? '')) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="btn btn--ghost btn--sm btn--block" href="<?= e(url('exercises', ['course' => $courseSlug])) ?>">همه‌ی تمرین‌های این دوره</a>
                </div>
                <?php endif; ?>

                <?= comments_teaser(2, 'تجربیاتِ دیگران از این دوره') ?>
            </aside>

            <div class="course-main">
                <p class="lead course-intro"><?= e($courseData['intro']??$courseData['excerpt']??'') ?></p>

                <div class="syllabus-head" id="syllabus">
                    <h2 class="syllabus-head__title"><?= ha_icon('list', 17) ?> سرفصل‌های دوره</h2>
                    <p class="muted-sm">درس‌ها به‌ترتیبِ زیر پیش می‌روند. وضعیتِ هر درس: <span class="pill pill--todo"><?= ha_icon('circle', 11) ?><span>انجام‌نشده</span></span> <span class="pill pill--started"><?= ha_icon('clock', 11) ?><span>در حالِ مطالعه</span></span> <span class="pill pill--done"><?= ha_icon('check', 11) ?><span>تکمیل‌شده</span></span></p>
                </div>

                <?php foreach($stages as $sIdx=>$stage): ?>
                    <?php
                    $stageLessons = (array)($stage['lessons']??[]);
                    $stageSlugs   = course_stage_slugs($stage);
                    $stageDone    = 0; $stageStarted = 0;
                    foreach ($stageSlugs as $ss) {
                        $st = progress_lesson_state($ss);
                        if ($st === 'done') { $stageDone++; } elseif ($st === 'started') { $stageStarted++; }
                    }
                    $stagePercent = $stageSlugs !== [] ? (int) round($stageDone / count($stageSlugs) * 100) : 0;
                    ?>
                    <section class="stage" id="<?= e($stage['id'] ?? ('stage-'.($sIdx+1))) ?>">
                        <header class="stage__head">
                            <div>
                                <span class="stage__badge"><?= e($stage['label'] ?? ('مرحله '.fa_num($sIdx+1))) ?></span>
                                <h2 class="stage__title"><?= e($stage['title']) ?></h2>
                            </div>
                            <div class="stage__progress" data-stage-progress="<?= e(implode(',', $stageSlugs)) ?>">
                                <?= progress_bar($stagePercent, fa_num($stageDone).'/'.fa_num(count($stageLessons)), 'پیشرفتِ مرحله') ?>
                                <?php if ($stageStarted > 0): ?>
                                <span class="muted-sm"><?= fa_num($stageStarted) ?> درس در حالِ مطالعه</span>
                                <?php endif; ?>
                            </div>
                        </header>
                        <p class="stage__summary"><?= e($stage['summary']) ?></p>
                        <?php if(!empty($stage['outcome'])): ?><p class="stage__outcome"><strong>خروجی مرحله:</strong> <?= e($stage['outcome']) ?></p><?php endif; ?>
                        <ol class="lesson-list">
                            <?php foreach($stageLessons as $lIdx=>$lesson):
                                $lSlug = slugify((string) ($lesson['slug'] ?? ''));
                                $hl = $lSlug === $currentSlug ? 'current' : ($lSlug === $nextSlug ? 'next' : '');
                            ?>
                                <?= lesson_row($lesson, (int)$sIdx, (int)$lIdx, $stage, $hl) ?>
                            <?php endforeach; ?>
                        </ol>
                        <?php
                        /* آزمونِ مرحله (اختیاری): معیارِ عبور از این مرحله — در data/course.php */
                        $stageAssessment = (array)($stage['assessment'] ?? []);
                        $assessmentItems  = array_values(array_filter(array_map('trim', (array)($stageAssessment['items'] ?? [])), fn($i) => $i !== ''));
                        ?>
                        <?php if($assessmentItems !== []): ?>
                        <aside class="stage-assessment" aria-label="آزمون این مرحله">
                            <h3 class="stage-assessment__title"><?= ha_icon('target', 15) ?> <?= e($stageAssessment['title'] ?? 'آزمون این مرحله') ?></h3>
                            <ol class="rich-list rich-list--num">
                                <?php foreach($assessmentItems as $ai): ?><li><?= e($ai) ?></li><?php endforeach; ?>
                            </ol>
                        </aside>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>

                <?php
                /* پروژه‌ی نهاییِ دوره (اختیاری) — جمع‌بندیِ همه‌ی مراحل در یک خروجیِ واقعی */
                $courseProject = (array)($courseData['project'] ?? []);
                $projectCriteria = array_values(array_filter(array_map('trim', (array)($courseProject['criteria'] ?? [])), fn($c) => $c !== ''));
                ?>
                <?php if($courseProject !== [] && !empty($courseProject['title'])): ?>
                <section class="course-project" aria-label="پروژه نهایی دوره">
                    <h2 class="course-project__title"><?= ha_icon('compass', 17) ?> <?= e($courseProject['title']) ?></h2>
                    <?php if(!empty($courseProject['description'])): ?><p class="course-project__desc"><?= e($courseProject['description']) ?></p><?php endif; ?>
                    <?php if($projectCriteria !== []): ?>
                    <h3 class="course-project__sub">معیارهای پذیرش</h3>
                    <ul class="rich-list">
                        <?php foreach($projectCriteria as $pc): ?><li><?= e($pc) ?></li><?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <?php if(!empty($courseProject['deliverable'])): ?><p class="course-project__deliverable"><strong>خروجی مورد انتظار:</strong> <?= e($courseProject['deliverable']) ?></p><?php endif; ?>
                </section>
                <?php endif; ?>

                <div class="cta-band cta-band--inline course-end-band">
                    <div class="cta-band__text">
                        <h2>مسیر این دوره، درس‌به‌درس</h2>
                        <p>
                            <?php if ($currentLesson !== null && $nextLesson !== null): ?>
                                الان در «<?= e((string) $currentLesson['title']) ?>» هستید؛ بعد از آن «<?= e((string) $nextLesson['title']) ?>» و بعد تمرین.
                            <?php else: ?>
                                هر درس یک هدف و یک تمرین دارد. بعد از آخرین درس، «پایان دوره» نمایش داده می‌شود.
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="cta-band__actions">
                        <?php if ($currentLesson !== null): ?>
                        <a class="btn btn--primary" href="<?= e((string) $currentLesson['url']) ?>"><?= ha_icon('play', 15) ?> ادامه‌ی یادگیری</a>
                        <?php elseif ($firstSlug !== ''): ?>
                        <a class="btn btn--primary" href="<?= e(url('lesson',['slug'=>(string)$firstSlug])) ?>">شروع درس اول</a>
                        <?php endif; ?>
                        <a class="btn btn--ghost" href="<?= e(url('exercises', ['course' => $courseSlug])) ?>"><?= ha_icon('timer', 15) ?> تمرین‌ها</a>
                    </div>
                </div>

                <?php if($relatedArticles): ?>
                <section class="sub-section">
                    <div class="sub-section__head"><h2 class="sub-section__title">مقاله‌های مرتبط</h2></div>
                    <div class="grid grid--3">
                        <?php foreach(array_slice($relatedArticles,0,3) as $art): ?><div><?= article_card($art) ?></div><?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
