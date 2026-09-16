<?php
/**
 * HAvoice — «پیشرفتِ یادگیریِ من»
 *
 * یک نگاهِ کلی به همه‌ی دوره‌ها: چند درس تمام شده، درسِ فعلی چیست و
 * تمرینِ بعدی کدام است. این صفحه مقصدِ GETِ مسیرِ progress است و از
 * حسابِ کاربری هم در دسترس است.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$user     = auth_current_user();
$overview = progress_overview();
$all      = courses();
$rows     = [];

foreach ($all as $course) {
    $path = course_learning_path($course);
    if (($path['summary']['total'] ?? 0) === 0 && ($path['exercise_summary']['total'] ?? 0) === 0) {
        continue;
    }
    $rows[] = ['course' => $course, 'path' => $path];
}

$totals  = [
    'lessons'   => (int) $overview['lessons'],
    'done'      => (int) $overview['done'],
    'exercises' => (int) $overview['exercises'],
    'exDone'    => (int) $overview['ex_done'],
];
$overall = (int) $overview['percent'];
?>

<section class="section section--tight">
    <div class="container">
        <header class="page-head">
            <p class="eyebrow"><?= ha_icon('growth', 14) ?> حسابِ <?= e($user['name'] ?? 'کاربر') ?></p>
            <h1 class="page-head__title">پیشرفتِ یادگیریِ من</h1>
            <p class="page-head__lead">درسِ فعلی، درسِ بعدی و تمرینِ بعدیِ هر دوره — همیشه روشن است که قدمِ بعد چیست.</p>
        </header>

        <div class="card progress-overview">
            <div class="progress-overview__stats">
                <div><strong><?= fa_num($totals['done']) ?></strong><span>درسِ تکمیل‌شده از <?= fa_num($totals['lessons']) ?></span></div>
                <div><strong><?= fa_num($totals['exDone']) ?></strong><span>تمرینِ انجام‌شده از <?= fa_num($totals['exercises']) ?></span></div>
                <div><strong><?= fa_num(count($rows)) ?></strong><span>دوره‌ی در جریان</span></div>
            </div>
            <?= progress_bar($overall, fa_num($overall) . '٪', 'پیشرفتِ کلِ یادگیری') ?>
            <div class="btn-row mt-sm">
                <a class="btn btn--primary btn--sm" href="<?= e(url('courses')) ?>"><?= ha_icon('steps', 14) ?> دوره‌ها</a>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('exercises')) ?>"><?= ha_icon('timer', 14) ?> تمرین‌ها</a>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('account')) ?>"><?= ha_icon('user', 14) ?> حسابِ کاربری</a>
            </div>
        </div>

        <?php if ($rows === []): ?>
            <?= empty_state('هنوز درسی شروع نکرده‌اید', 'یک دوره را باز کنید و درسِ اول را بخوانید؛ پیشرفتِ شما همین‌جا ثبت می‌شود.', url('courses'), 'دیدنِ دوره‌ها', 'steps') ?>
        <?php else: ?>
            <div class="grid grid--2 mt-md">
                <?php foreach ($rows as $row):
                    $course  = $row['course'];
                    $path    = $row['path'];
                    $summary = $path['summary'];
                    $current = $path['current'];
                    $next    = $path['next'];
                    $ex      = $path['exercise_current'];
                    $cat     = find_category((string) ($course['category'] ?? ''));
                ?>
                <article class="card progress-card" style="<?= e(card_style($cat)) ?>">
                    <div class="progress-card__top">
                        <span class="badge badge--soft"><?= e(cat_label($cat, (string) ($course['category'] ?? ''))) ?></span>
                        <span class="muted-sm"><?= fa_num((int) $summary['done']) ?>/<?= fa_num((int) $summary['total']) ?> درس</span>
                    </div>
                    <h2 class="progress-card__title"><a href="<?= e(url('course', ['slug' => (string) ($course['slug'] ?? '')])) ?>"><?= e($course['title'] ?? '') ?></a></h2>
                    <?= progress_bar((int) $summary['percent'], fa_num((int) $summary['percent']) . '٪', 'پیشرفتِ دوره') ?>

                    <ul class="progress-card__list">
                        <?php if ($current !== null): ?>
                        <li>
                            <span class="progress-card__k">درسِ فعلی</span>
                            <span class="progress-card__v">
                                <a href="<?= e((string) $current['url']) ?>"><?= e((string) $current['title']) ?></a>
                                <?= status_pill((string) $current['state']) ?>
                            </span>
                        </li>
                        <?php endif; ?>
                        <?php if ($next !== null): ?>
                        <li>
                            <span class="progress-card__k">درسِ بعدی</span>
                            <span class="progress-card__v"><a href="<?= e((string) $next['url']) ?>"><?= e((string) $next['title']) ?></a></span>
                        </li>
                        <?php endif; ?>
                        <?php if ($ex !== null): ?>
                        <li>
                            <span class="progress-card__k">تمرین</span>
                            <span class="progress-card__v">
                                <a href="<?= e((string) $ex['url']) ?>"><?= e((string) $ex['title']) ?></a>
                                <?= status_pill((string) $ex['state']) ?>
                            </span>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <div class="progress-card__actions">
                        <?php if ($current !== null): ?>
                        <a class="btn btn--primary btn--sm" href="<?= e((string) $current['url']) ?>"><?= ha_icon('play', 14) ?> ادامه‌ی یادگیری</a>
                        <?php endif; ?>
                        <a class="btn btn--ghost btn--sm" href="<?= e(url('course', ['slug' => (string) ($course['slug'] ?? '')])) ?>"><?= ha_icon('list', 14) ?> صفحه‌ی دوره</a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
