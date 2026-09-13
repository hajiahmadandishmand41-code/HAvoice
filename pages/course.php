<?php
/**
 * HAvoice 2.0 — صفحه‌ی دوره (پشتیبانی از چند دوره + حالت قدیم)
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
$videosRelated = array_slice(array_filter(videos(), fn($v)=>($v['category']??'')===($courseData['category']??'')),0,2);
$audiosRelated = array_slice(array_filter(audios(), fn($a)=>($a['category']??'')===($courseData['category']??'')),0,2);
?>

<section class="section section--tight">
    <div class="container">
        <div class="chip-row">
            <?php if($cat): ?><span class="badge"><?= e($cat['title']) ?></span><?php endif; ?>
            <span class="chip chip--ghost"><?= e($courseData['level']??'') ?></span>
            <span class="muted-sm"><?= fa_num($totalLessons) ?> درس · <?= minutes_label($totalMinutes) ?></span>
        </div>

        <div class="course-layout">
            <aside class="course-aside">
                <div class="card course-card" data-course-card>
                    <h2 class="course-card__title">پیشرفت شما</h2>
                    <p class="course-card__muted">روی همین مرورگر ذخیره می‌شود؛ بدون ثبت‌نام.</p>
                    <div data-total-progress><?= progress_bar(0,'۰٪') ?></div>
                    <ul class="course-card__legend">
                        <li><strong data-count-done>۰</strong> درس انجام‌شده</li>
                        <li><strong><?= fa_num($totalLessons) ?></strong> درس این دوره</li>
                        <li><strong><?= e(minutes_label($totalMinutes)) ?></strong> زمان مطالعه</li>
                    </ul>
                    <button class="btn btn--ghost btn--sm btn--block" type="button" data-reset-progress>پاک کردن پیشرفت</button>
                </div>

                <div class="card how-card">
                    <h2>چطور پیش برویم؟</h2>
                    <ul class="rich-list">
                        <?php foreach((array)($courseData['how_to']??[]) as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
                    </ul>
                    <?php if(!empty($courseData['excerpt'])): ?><p class="muted-sm mt-sm"><?= e($courseData['excerpt']) ?></p><?php endif; ?>
                </div>

                <?php if($videosRelated || $audiosRelated): ?>
                <div class="card">
                    <h2>ویدیو و صوتِ همین حوزه</h2>
                    <ul class="rich-list">
                        <?php foreach($videosRelated as $v): ?><li><a href="<?= e(url('videos')) ?>"><?= e($v['title']) ?></a> <span class="muted-sm">— ویدیو</span></li><?php endforeach; ?>
                        <?php foreach($audiosRelated as $a): ?><li><a href="<?= e(url('audios')) ?>"><?= e($a['title']) ?></a> <span class="muted-sm">— صوت</span></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </aside>

            <div class="course-main">
                <p class="lead course-intro"><?= e($courseData['intro']??$courseData['excerpt']??'') ?></p>

                <?php foreach($stages as $sIdx=>$stage): ?>
                    <section class="stage" id="<?= e($stage['id'] ?? ('stage-'.($sIdx+1))) ?>">
                        <header class="stage__head">
                            <div>
                                <span class="stage__badge"><?= e($stage['label'] ?? ('مرحله '.fa_num($sIdx+1))) ?></span>
                                <h2 class="stage__title"><?= e($stage['title']) ?></h2>
                            </div>
                            <div class="stage__progress" data-stage-progress="<?= e(implode(',', course_stage_slugs($stage))) ?>">
                                <?= progress_bar(0,'۰/'.fa_num(count((array)($stage['lessons']??[])))) ?>
                            </div>
                        </header>
                        <p class="stage__summary"><?= e($stage['summary']) ?></p>
                        <?php if(!empty($stage['outcome'])): ?><p class="stage__outcome"><strong>خروجی مرحله:</strong> <?= e($stage['outcome']) ?></p><?php endif; ?>
                        <ol class="lesson-list">
                            <?php foreach((array)($stage['lessons']??[]) as $lIdx=>$lesson): ?>
                                <?= lesson_row($lesson, (int)$sIdx, (int)$lIdx, $stage) ?>
                            <?php endforeach; ?>
                        </ol>
                    </section>
                <?php endforeach; ?>

                <div class="cta-band cta-band--inline">
                    <div class="cta-band__text">
                        <h2>اولین قدم، سه دقیقه تمرین است</h2>
                        <p>لازم نیست همه‌چیز را یک‌روز بخوانید. امروز فقط درس اول و تمرینش.</p>
                    </div>
                    <div class="cta-band__actions">
                        <?php $firstSlug = $stages[0]['lessons'][0]['slug'] ?? 'breathing-foundations'; ?>
                        <a class="btn btn--primary" href="<?= e(url('lesson',['slug'=>(string)$firstSlug])) ?>">شروع درس اول</a>
                        <a class="btn btn--ghost" href="<?= e(url('exercises')) ?>">تمرین‌ها</a>
                    </div>
                </div>

                <?php if($relatedArticles): ?>
                <section class="sub-section">
                    <div class="sub-section__head"><h2 class="sub-section__title">مقاله‌های مرتبط با این دوره</h2></div>
                    <div class="grid grid--3">
                        <?php foreach(array_slice($relatedArticles,0,3) as $art): ?><div><?= article_card($art) ?></div><?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
