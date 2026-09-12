<?php
/**
 * HAvoice — صفحه‌ی دوره: نمای کلی مسیر، مرحله‌ها و فهرست درس‌ها.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$course = course();
$stages = course_stages();
$index  = course_lesson_index();
?>

<section class="section section--tight">
    <div class="container course-layout">

        <aside class="course-aside">
            <div class="card course-card" data-course-card>
                <h2 class="course-card__title">پیشرفت شما</h2>
                <p class="course-card__muted">روی همین مرورگر ذخیره می‌شود؛ بدون ثبت‌نام.</p>
                <div data-total-progress><?= progress_bar(0, '۰٪') ?></div>
                <ul class="course-card__legend">
                    <li><strong data-count-done>۰</strong> درس انجام‌شده</li>
                    <li><strong><?= fa_num(count($index)) ?></strong> درس کل دوره</li>
                    <li><strong><?= e(minutes_label(course_total_minutes())) ?></strong> زمان مطالعه</li>
                </ul>
                <button class="btn btn--ghost btn--sm btn--block" type="button" data-reset-progress>
                    پاک کردن پیشرفت
                </button>
            </div>

            <div class="card how-card">
                <h2>چطور پیش برویم؟</h2>
                <ul class="rich-list">
<?php foreach ((array) ($course['how_to'] ?? []) as $line): ?>
                    <li><?= e($line) ?></li>
<?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <div class="course-main">
            <p class="lead course-intro"><?= e($course['intro']) ?></p>

<?php foreach ($stages as $sIdx => $stage): ?>
            <section class="stage" id="<?= e($stage['id'] ?? ('stage-' . ($sIdx + 1))) ?>">
                <header class="stage__head">
                    <div>
                        <span class="stage__badge"><?= e($stage['label'] ?? ('مرحله ' . fa_num($sIdx + 1))) ?></span>
                        <h2 class="stage__title"><?= e($stage['title']) ?></h2>
                    </div>
                    <div class="stage__progress" data-stage-progress="<?= e(implode(',', course_stage_slugs($stage))) ?>">
                        <?= progress_bar(0, '۰/' . fa_num(count((array) ($stage['lessons'] ?? [])))) ?>
                    </div>
                </header>

                <p class="stage__summary"><?= e($stage['summary']) ?></p>
                <p class="stage__outcome"><strong>خروجی مرحله:</strong> <?= e($stage['outcome']) ?></p>

                <ol class="lesson-list">
<?php foreach ((array) ($stage['lessons'] ?? []) as $lIdx => $lesson): ?>
                    <?= lesson_row($lesson, (int) $sIdx, (int) $lIdx, $stage) ?>
<?php endforeach; ?>
                </ol>
            </section>
<?php endforeach; ?>

            <div class="cta-band cta-band--inline">
                <div class="cta-band__text">
                    <h2>اولین قدم، سه دقیقه تنفس است</h2>
                    <p>لازم نیست همه‌چیز را یک‌روز بخوانید. امروز فقط درس اول و تمرینش.</p>
                </div>
                <div class="cta-band__actions">
                    <a class="btn btn--primary" href="<?= e(url('lesson', ['slug' => 'breathing-foundations'])) ?>">شروع درس اول</a>
                    <a class="btn btn--ghost" href="<?= e(url('exercises')) ?>">تمرین‌ها</a>
                </div>
            </div>
        </div>
    </div>
</section>
