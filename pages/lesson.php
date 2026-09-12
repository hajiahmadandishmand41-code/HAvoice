<?php
/**
 * HAvoice — صفحه‌ی یک درس از مسیر آموزشی.
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
$neigh    = course_neighbours($slug);
$total    = (int) $lesson['total'];
$position = (int) $lesson['position'];
?>

<article class="lesson">
    <header class="lesson__head">
        <div class="container container--narrow">
            <nav class="breadcrumbs" aria-label="مسیر صفحه">
                <a href="<?= e(url('home')) ?>">خانه</a>
                <span aria-hidden="true">/</span>
                <a href="<?= e(url('course')) ?>">مسیر آموزشی</a>
                <span aria-hidden="true">/</span>
                <a href="<?= e(url('course')) . '#' . e($stage['id'] ?? 'stage') ?>"><?= e($stage['title']) ?></a>
            </nav>

            <p class="eyebrow"><?= e($stage['label'] ?? 'مرحله') ?> · درس <?= fa_ordinal($position, $total) ?></p>
            <h1 class="lesson__title"><?= e($data['title']) ?></h1>
            <p class="lesson__goal"><?= e($data['goal'] ?? '') ?></p>

            <div class="lesson__meta">
                <span class="chip"><?= e(minutes_label((int) ($data['minutes'] ?? 10))) ?></span>
                <span class="chip chip--soft">گام <?= fa_num($position) ?> از <?= fa_num($total) ?></span>
            </div>
        </div>
    </header>

    <div class="lesson__body">
        <div class="container container--narrow">
            <div class="prose">
                <?= render_blocks((array) ($data['blocks'] ?? [])) ?>
            </div>

<?php if (!empty($data['drill'])): ?>
            <?= render_drill((array) $data['drill']) ?>
<?php endif; ?>

            <div class="lesson__actions card">
                <div>
                    <h2>این درس را انجام دادید؟</h2>
                    <p class="muted-sm">با تیک زدن، پیشرفت شما در همین مرورگر ذخیره می‌شود.</p>
                </div>
                <button class="btn btn--primary" type="button"
                        data-lesson-complete="<?= e($data['slug']) ?>"
                        aria-pressed="false">
                    <span data-lesson-complete-label>علامت‌گذاری به‌عنوان انجام‌شده</span>
                </button>
            </div>

            <nav class="pager" aria-label="درس قبلی و بعدی">
                <?php if ($neigh['prev'] !== null): ?>
                    <a class="pager__link pager__link--prev" href="<?= e(url('lesson', ['slug' => $neigh['prev']['slug']])) ?>">
                        <span class="pager__label">درس قبلی</span>
                        <span class="pager__title"><?= e($neigh['prev']['title']) ?></span>
                    </a>
                <?php else: ?>
                    <span class="pager__spacer" aria-hidden="true"></span>
                <?php endif; ?>

                <?php if ($neigh['next'] !== null): ?>
                    <a class="pager__link pager__link--next" href="<?= e(url('lesson', ['slug' => $neigh['next']['slug']])) ?>">
                        <span class="pager__label">درس بعدی</span>
                        <span class="pager__title"><?= e($neigh['next']['title']) ?></span>
                    </a>
                <?php else: ?>
                    <a class="pager__link pager__link--next" href="<?= e(url('exercises')) ?>">
                        <span class="pager__label">پایان دوره</span>
                        <span class="pager__title">رفتن به تمرین‌ها</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</article>
