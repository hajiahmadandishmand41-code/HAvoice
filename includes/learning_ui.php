<?php
/**
 * HAvoice — اجزای رابطِ «مسیرِ یادگیری»
 *
 * هدفِ این ماژول یک چیز است: کاربر با یک نگاه بفهمد
 *   «الان کجاست، درسِ فعلی چیست، درسِ بعدی چیست و تمرینِ بعدی چیست».
 *
 * همه‌ی اجزا از همان Design Tokenها و کلاس‌های موجود (btn, card, chip,
 * progress, badge) استفاده می‌کنند و استایلشان در assets/css/learning.css
 * است — بدونِ استایلِ درون‌خطی و بدونِ JS اجباری (همه‌ی دکمه‌ها یا پیوند
 * واقعی‌اند یا فرمِ POST؛ اگر JS خاموش باشد هم کار می‌کنند).
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  وضعیتِ قلم‌ها                                                      */
/* ------------------------------------------------------------------ */

/**
 * برچسبِ وضعیتِ یک درس/تمرین: انجام‌نشده | در حالِ مطالعه | تکمیل‌شده.
 */
function status_pill(string $state, string $label = ''): string
{
    $meta  = progress_state_meta($state);
    $text  = $label !== '' ? $label : (string) $meta['label'];
    return '<span class="pill pill--' . e((string) $meta['key']) . '">'
         . ha_icon((string) $meta['icon'], 13)
         . '<span>' . e($text) . '</span></span>';
}

/**
 * فرمِ تغییرِ وضعیت (PRG، بدونِ نیاز به JS).
 *
 * @param array{type?:string,key:string,state:string,label:string,class?:string,
 *             icon?:string,goto?:string,next?:string,title?:string,small?:bool,
 *             pressed?:bool,formClass?:string} $o
 */
function progress_form(array $o): string
{
    $type  = ($o['type'] ?? 'lesson') === 'exercise' ? 'exercise' : 'lesson';
    $key   = slugify((string) ($o['key'] ?? ''));
    if ($key === '') {
        return '';
    }
    $state = in_array((string) ($o['state'] ?? ''), ['done', 'started', 'none'], true) ? (string) $o['state'] : 'done';
    $next  = ha_safe_next((string) ($o['next'] ?? ha_current_request_url()));
    if ($next === '') {
        $next = url('courses');
    }
    $class = (string) ($o['class'] ?? 'btn btn--ghost btn--sm');
    $icon  = (string) ($o['icon'] ?? '');
    $label = (string) ($o['label'] ?? 'ثبت');
    $title = (string) ($o['title'] ?? '');

    ob_start(); ?>
    <form class="progress-form<?= !empty($o['formClass']) ? ' ' . e((string) $o['formClass']) : '' ?>"
          method="post" action="<?= e(url('progress')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <input type="hidden" name="key" value="<?= e($key) ?>">
        <input type="hidden" name="state" value="<?= e($state) ?>">
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <?php if (!empty($o['goto'])): ?><input type="hidden" name="goto" value="<?= e((string) $o['goto']) ?>"><?php endif; ?>
        <button class="<?= e($class) ?>" type="submit"
                <?= $title !== '' ? 'title="' . e($title) . '"' : '' ?>
                <?= isset($o['pressed']) ? 'aria-pressed="' . ($o['pressed'] ? 'true' : 'false') . '"' : '' ?>>
            <?= $icon !== '' ? ha_icon($icon, 15) : '' ?><?= e($label) ?>
        </button>
    </form>
    <?php return (string) ob_get_clean();
}

/** دکمه‌ی «تکمیل شد / برداشتنِ علامت» برای یک درس. */
function lesson_complete_form(string $lessonSlug, string $state, string $goto = 'next-lesson'): string
{
    if ($state === 'done') {
        return progress_form([
            'type'  => 'lesson',
            'key'   => $lessonSlug,
            'state' => 'none',
            'label' => 'برداشتنِ علامتِ «تکمیل‌شده»',
            'class' => 'btn btn--ghost btn--sm',
            'icon'  => 'rotate',
            'pressed' => true,
            'title' => 'این درس دوباره «انجام‌نشده» می‌شود',
        ]);
    }
    return progress_form([
        'type'  => 'lesson',
        'key'   => $lessonSlug,
        'state' => 'done',
        'label' => 'تکمیل شد — برو به قدمِ بعدی',
        'class' => 'btn btn--primary',
        'icon'  => 'check',
        'goto'  => $goto,
        'pressed' => false,
    ]);
}

/** دکمه‌ی «انجام شد» برای یک تمرین. */
function exercise_complete_form(string $exerciseId, string $state, string $class = 'btn btn--ghost btn--sm'): string
{
    if ($state === 'done') {
        return progress_form([
            'type'  => 'exercise',
            'key'   => $exerciseId,
            'state' => 'none',
            'label' => 'برداشتنِ علامت',
            'class' => $class,
            'icon'  => 'rotate',
            'pressed' => true,
            'formClass' => 'progress-form--inline',
        ]);
    }
    return progress_form([
        'type'  => 'exercise',
        'key'   => $exerciseId,
        'state' => 'done',
        'label' => 'انجام شد',
        'class' => $class,
        'icon'  => 'check',
        'pressed' => false,
        'formClass' => 'progress-form--inline',
    ]);
}

/** فرمِ پاک‌کردنِ پیشرفتِ یک دوره. */
function progress_reset_form(string $courseSlug, string $label = 'پاک کردنِ پیشرفتِ این دوره'): string
{
    $courseSlug = slugify($courseSlug);
    if ($courseSlug === '') {
        return '';
    }
    $next = ha_safe_next(ha_current_request_url());
    ob_start(); ?>
    <form class="progress-form progress-form--inline" method="post" action="<?= e(url('progress')) ?>"
          data-confirm="پیشرفتِ این دوره (درس‌ها و تمرین‌ها) در حسابِ شما پاک شود؟">
        <?= csrf_field() ?>
        <input type="hidden" name="reset_course" value="<?= e($courseSlug) ?>">
        <input type="hidden" name="next" value="<?= e($next !== '' ? $next : url('course', ['slug' => $courseSlug])) ?>">
        <button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('rotate', 14) ?> <?= e($label) ?></button>
    </form>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  پنلِ مسیرِ یادگیری                                                 */
/* ------------------------------------------------------------------ */

/** یک قدم در جریانِ «دوره ← درس ← تمرین». */
function learn_step(array $step): string
{
    $n        = (string) ($step['n'] ?? '');
    $label    = (string) ($step['label'] ?? '');
    $title    = (string) ($step['title'] ?? '');
    $href     = (string) ($step['href'] ?? '');
    $meta     = (string) ($step['meta'] ?? '');
    $state    = (string) ($step['state'] ?? '');
    $actions  = (string) ($step['actions'] ?? '');
    $modifier = (string) ($step['modifier'] ?? '');
    $active   = !empty($step['active']);
    $note     = (string) ($step['note'] ?? '');

    $classes = ['learn-step'];
    if ($modifier !== '') {
        $classes[] = 'learn-step--' . $modifier;
    }
    if ($active) {
        $classes[] = 'is-active';
    }
    if ($state !== '') {
        $classes[] = 'is-' . progress_state_meta($state)['key'];
    }

    ob_start(); ?>
    <li class="<?= e(implode(' ', $classes)) ?>">
        <span class="learn-step__n" aria-hidden="true"><?= e(fa_num($n)) ?></span>
        <div class="learn-step__body">
            <p class="learn-step__label"><?= e($label) ?></p>
            <h3 class="learn-step__title">
                <?php if ($href !== ''): ?><a href="<?= e($href) ?>"><?= e($title) ?></a>
                <?php else: ?><?= e($title) ?><?php endif; ?>
            </h3>
            <?php if ($meta !== '' || $state !== ''): ?>
            <p class="learn-step__meta">
                <?php if ($state !== ''): ?><?= status_pill($state) ?><?php endif; ?>
                <?php if ($meta !== ''): ?><span class="learn-step__meta-text"><?= e($meta) ?></span><?php endif; ?>
            </p>
            <?php endif; ?>
            <?php if ($note !== ''): ?><p class="learn-step__note"><?= e($note) ?></p><?php endif; ?>
            <?php if ($actions !== ''): ?><div class="learn-step__actions"><?= $actions ?></div><?php endif; ?>
        </div>
    </li>
    <?php return (string) ob_get_clean();
}

/** دکمه‌های یک قدم (پیوند + فرم‌ها کنار هم). */
function learn_actions(array $items): string
{
    $out = '';
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        if (isset($item['html'])) {
            $out .= (string) $item['html'];
            continue;
        }
        $href  = (string) ($item['href'] ?? '');
        $label = (string) ($item['label'] ?? '');
        if ($href === '' || $label === '') {
            continue;
        }
        $class = (string) ($item['class'] ?? 'btn btn--ghost btn--sm');
        $icon  = (string) ($item['icon'] ?? '');
        $out  .= '<a class="' . e($class) . '" href="' . e($href) . '"'
               . (!empty($item['anchor']) ? ' data-scroll-to="' . e((string) $item['anchor']) . '"' : '') . '>'
               . ($icon !== '' ? ha_icon($icon, 15) : '') . e($label) . '</a>';
    }
    return $out;
}

/**
 * پنلِ «مسیرِ یادگیری» — همان ترتیبِ خواسته‌شده:
 *   دوره ← درسِ فعلی ← درسِ بعدی ← تمرین ← تمرینِ بعدی
 *
 * @param array $course  داده‌ی دوره
 * @param array $path    خروجیِ course_learning_path()
 * @param array $opts    ['mode' => 'course'|'lesson', 'currentSlug' => '']
 */
function learning_flow(array $course, array $path, array $opts = []): string
{
    $mode        = ($opts['mode'] ?? 'course') === 'lesson' ? 'lesson' : 'course';
    $currentSlug = slugify((string) ($opts['currentSlug'] ?? ''));
    $courseSlug  = slugify((string) ($course['slug'] ?? ''));
    $summary     = (array) ($path['summary'] ?? []);
    $current     = $path['current'] ?? null;
    $next        = $path['next'] ?? null;
    $exercise    = $path['exercise_current'] ?? null;
    $exerciseNext = $path['exercise_next'] ?? null;
    $lessons     = (array) ($path['lessons'] ?? []);
    $first       = $lessons[0] ?? null;
    $backHere    = ha_safe_next(ha_current_request_url());
    $courseUrl   = url('course', ['slug' => $courseSlug]);

    $total   = (int) ($summary['total'] ?? count($lessons));
    $done    = (int) ($summary['done'] ?? 0);
    $percent = (int) ($summary['percent'] ?? 0);
    $exTotal = (int) ($path['exercise_summary']['total'] ?? 0);
    $exDone  = (int) ($path['exercise_summary']['done'] ?? 0);

    /* ---- قدمِ ۱: دوره ---- */
    $courseActions = [];
    if ($mode === 'lesson') {
        $courseActions[] = ['href' => $courseUrl, 'label' => 'بازگشت به دوره', 'class' => 'btn btn--ghost btn--sm', 'icon' => 'arrow-right'];
        $courseActions[] = ['href' => url('courses'), 'label' => 'همه‌ی دوره‌ها', 'class' => 'btn btn--ghost btn--sm'];
    } else {
        if ($current !== null) {
            $courseActions[] = ['href' => (string) $current['url'], 'label' => $done > 0 ? 'ادامه‌ی یادگیری' : 'شروع یادگیری', 'class' => 'btn btn--primary btn--sm', 'icon' => 'play'];
        } elseif ($first !== null) {
            $courseActions[] = ['href' => (string) $first['url'], 'label' => 'شروع یادگیری', 'class' => 'btn btn--primary btn--sm', 'icon' => 'play'];
        }
        $courseActions[] = ['href' => $courseUrl . '#syllabus', 'label' => 'دیدنِ سرفصل‌ها', 'class' => 'btn btn--ghost btn--sm', 'icon' => 'list', 'anchor' => 'syllabus'];
    }

    $steps = [];
    $steps[] = learn_step([
        'n'        => 1,
        'label'    => 'دوره',
        'title'    => (string) ($course['title'] ?? 'دوره'),
        'href'     => $mode === 'lesson' ? $courseUrl : '',
        'meta'     => fa_num($total) . ' درس · ' . fa_num($exTotal) . ' تمرین',
        'modifier' => 'course',
        'active'   => $mode === 'course',
        'actions'  => learn_actions($courseActions),
    ]);

    /* ---- قدمِ ۲: درسِ فعلی ---- */
    if ($current !== null) {
        $isDone = ($current['state'] ?? '') === 'done';
        $actions = [];
        $actions[] = [
            'href'  => (string) $current['url'],
            'label' => $isDone ? 'مرورِ درس' : ($current['state'] === 'started' ? 'ادامه‌ی مطالعه' : 'شروع این درس'),
            'class' => 'btn btn--primary btn--sm',
            'icon'  => $isDone ? 'eye' : 'play',
        ];
        if ($mode === 'lesson' && $currentSlug === (string) $current['slug']) {
            $actions[] = ['html' => lesson_complete_form((string) $current['slug'], (string) $current['state'])];
        }
        $steps[] = learn_step([
            'n'        => 2,
            'label'    => $isDone ? 'آخرین درسِ تکمیل‌شده' : 'درسِ فعلی',
            'title'    => (string) $current['title'],
            'href'     => (string) $current['url'],
            'meta'     => (int) $current['minutes'] > 0 ? minutes_label((int) $current['minutes']) : '',
            'state'    => (string) $current['state'],
            'note'     => (string) ($current['stage'] ?? ''),
            'modifier' => 'current',
            'active'   => $mode === 'lesson' && $currentSlug === (string) $current['slug'],
            'actions'  => learn_actions($actions),
        ]);
    } else {
        $steps[] = learn_step([
            'n'        => 2,
            'label'    => 'درسِ فعلی',
            'title'    => 'این دوره هنوز درسی ندارد',
            'note'     => 'به‌زودی درس‌های این دوره اضافه می‌شود.',
            'modifier' => 'current',
        ]);
    }

    /* ---- قدمِ ۳: درسِ بعدی ---- */
    if ($next !== null) {
        $steps[] = learn_step([
            'n'        => 3,
            'label'    => 'درسِ بعدی',
            'title'    => (string) $next['title'],
            'href'     => (string) $next['url'],
            'meta'     => (int) $next['minutes'] > 0 ? minutes_label((int) $next['minutes']) : '',
            'state'    => (string) $next['state'],
            'note'     => (string) ($next['stage'] ?? ''),
            'modifier' => 'next',
            'actions'  => learn_actions([
                ['href' => (string) $next['url'], 'label' => 'درس بعدی', 'class' => 'btn btn--ghost btn--sm', 'icon' => 'arrow-left'],
            ]),
        ]);
    } else {
        $steps[] = learn_step([
            'n'        => 3,
            'label'    => 'درسِ بعدی',
            'title'    => $total > 0 ? 'پایانِ درس‌های این دوره' : '—',
            'note'     => $total > 0 ? 'همه‌ی درس‌ها را دیده‌اید؛ اکنون تمرین‌ها را کامل کنید.' : '',
            'modifier' => 'next',
            'actions'  => learn_actions($total > 0 ? [
                ['href' => url('exercises', ['course' => $courseSlug]), 'label' => 'تمرین‌های این دوره', 'class' => 'btn btn--ghost btn--sm', 'icon' => 'timer'],
                ['href' => url('courses'), 'label' => 'دوره‌ی دیگر', 'class' => 'btn btn--ghost btn--sm'],
            ] : []),
        ]);
    }

    /* ---- قدمِ ۴: تمرین ---- */
    if ($exercise !== null) {
        $steps[] = learn_step([
            'n'        => 4,
            'label'    => ($exercise['state'] ?? '') === 'done' ? 'تمرینِ انجام‌شده' : 'تمرین',
            'title'    => (string) $exercise['title'],
            'href'     => (string) $exercise['url'],
            'meta'     => (int) $exercise['seconds'] > 0 ? fa_num((int) ceil(((int) $exercise['seconds']) / 60)) . ' دقیقه' : 'بدونِ تایمر',
            'state'    => (string) ($exercise['state'] ?? ''),
            'note'     => (string) ($exercise['lessonTitle'] ?? '') !== '' ? 'تمرینِ درسِ «' . (string) $exercise['lessonTitle'] . '»' : '',
            'modifier' => 'exercise',
            'actions'  => learn_actions([
                ['href' => (string) $exercise['url'], 'label' => 'شروع تمرین', 'class' => 'btn btn--ghost btn--sm', 'icon' => 'timer'],
                ['html' => exercise_complete_form((string) $exercise['id'], (string) ($exercise['state'] ?? ''))],
            ]),
        ]);
    } else {
        $steps[] = learn_step([
            'n'        => 4,
            'label'    => 'تمرین',
            'title'    => 'تمرینی برای این دوره ثبت نشده',
            'note'     => 'تمرینِ هر درس، همان‌جا در انتهای صفحه‌ی درس پیشنهاد می‌شود.',
            'modifier' => 'exercise',
            'actions'  => learn_actions([
                ['href' => url('exercises'), 'label' => 'همه‌ی تمرین‌ها', 'class' => 'btn btn--ghost btn--sm', 'icon' => 'timer'],
            ]),
        ]);
    }

    /* ---- قدمِ ۵: تمرینِ بعدی ---- */
    if ($exerciseNext !== null) {
        $steps[] = learn_step([
            'n'        => 5,
            'label'    => 'تمرینِ بعدی',
            'title'    => (string) $exerciseNext['title'],
            'href'     => (string) $exerciseNext['url'],
            'meta'     => (int) $exerciseNext['seconds'] > 0 ? fa_num((int) ceil(((int) $exerciseNext['seconds']) / 60)) . ' دقیقه' : '',
            'state'    => (string) ($exerciseNext['state'] ?? ''),
            'note'     => (string) ($exerciseNext['lessonTitle'] ?? '') !== '' ? 'تمرینِ درسِ «' . (string) $exerciseNext['lessonTitle'] . '»' : '',
            'modifier' => 'exercise-next',
            'actions'  => learn_actions([
                ['href' => (string) $exerciseNext['url'], 'label' => 'تمرین بعدی', 'class' => 'btn btn--ghost btn--sm', 'icon' => 'arrow-left'],
            ]),
        ]);
    } else {
        $steps[] = learn_step([
            'n'        => 5,
            'label'    => 'تمرینِ بعدی',
            'title'    => $exTotal > 0 ? 'آخرین تمرینِ این دوره' : '—',
            'note'     => $exTotal > 0 ? 'بعد از این، تمرین‌های حوزه‌های دیگر را ببینید.' : '',
            'modifier' => 'exercise-next',
            'actions'  => learn_actions([
                ['href' => url('exercises'), 'label' => 'بانکِ تمرین‌ها', 'class' => 'btn btn--ghost btn--sm', 'icon' => 'timer'],
            ]),
        ]);
    }

    ob_start(); ?>
    <section class="learn-panel" aria-labelledby="learn-flow-title" data-learn-panel
             data-course-slug="<?= e($courseSlug) ?>"
             data-course-lessons="<?= e(implode(',', array_column($lessons, 'slug'))) ?>">
        <header class="learn-panel__head">
            <div class="learn-panel__heading">
                <p class="eyebrow"><?= ha_icon('steps', 13) ?> مسیرِ یادگیریِ شما</p>
                <h2 class="learn-panel__title" id="learn-flow-title">دوره ← درسِ فعلی ← درسِ بعدی ← تمرین ← تمرینِ بعدی</h2>
            </div>
            <div class="learn-panel__stats">
                <?= progress_bar($percent, fa_num($done) . ' از ' . fa_num($total) . ' درس', 'پیشرفتِ دوره‌ی ' . (string) ($course['title'] ?? '')) ?>
                <ul class="learn-panel__legend">
                    <li><span class="pill pill--done"><?= ha_icon('check', 12) ?><span>تکمیل‌شده</span></span> <strong><?= fa_num($done) ?></strong></li>
                    <li><span class="pill pill--started"><?= ha_icon('clock', 12) ?><span>در حالِ مطالعه</span></span> <strong><?= fa_num((int) ($summary['started'] ?? 0)) ?></strong></li>
                    <li><span class="pill pill--todo"><?= ha_icon('circle', 12) ?><span>انجام‌نشده</span></span> <strong><?= fa_num(max(0, $total - $done - (int) ($summary['started'] ?? 0))) ?></strong></li>
                    <?php if ($exTotal > 0): ?>
                    <li class="learn-panel__legend-ex"><?= ha_icon('timer', 12) ?> تمرین: <strong><?= fa_num($exDone) ?>/<?= fa_num($exTotal) ?></strong></li>
                    <?php endif; ?>
                </ul>
            </div>
        </header>

        <ol class="learn-flow">
            <?= implode('', $steps) ?>
        </ol>

        <?php if ($mode === 'course'): ?>
        <footer class="learn-panel__foot">
            <p class="muted-sm">پیشرفتِ شما در حسابِ کاربری‌تان ذخیره می‌شود؛ با ورود از دستگاهِ دیگر همان‌جا ادامه می‌دهید.</p>
            <?php if ($backHere !== ''): ?><?= progress_reset_form($courseSlug) ?><?php endif; ?>
        </footer>
        <?php endif; ?>
    </section>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  نوارِ «قدمِ بعدی» در صفحه‌ی درس                                    */
/* ------------------------------------------------------------------ */

/**
 * نوارِ چسبانِ پایینِ صفحه (موبایل) و نوارِ بالای محتوا (دسکتاپ):
 * همیشه یک دکمه‌ی اصلی دارد تا کاربر بداند قدمِ بعدی چیست.
 */
function lesson_next_bar(array $lp, string $lessonSlug, string $courseSlug): string
{
    $next     = $lp['next'] ?? null;
    $prev     = $lp['prev'] ?? null;
    $exercise = $lp['exercise'] ?? null;
    $state    = (string) ($lp['state'] ?? '');
    $courseUrl = url('course', ['slug' => $courseSlug]);

    $primary = '';
    if ($next !== null) {
        $primary = progress_form([
            'type'  => 'lesson',
            'key'   => $lessonSlug,
            'state' => $state === 'done' ? 'done' : 'done',
            'label' => 'تکمیل شد — درسِ بعدی',
            'class' => 'btn btn--primary btn--sm',
            'icon'  => 'arrow-left',
            'goto'  => 'next-lesson',
        ]);
    } else {
        $primary = '<a class="btn btn--primary btn--sm" href="' . e($courseUrl) . '">'
                 . ha_icon('flag', 15) . 'پایانِ درس‌ها — بازگشت به دوره</a>';
    }

    $secondary = '';
    if ($exercise !== null) {
        $secondary = '<a class="btn btn--ghost btn--sm" href="' . e((string) $exercise['url']) . '">'
                   . ha_icon('timer', 15) . 'تمرینِ این درس</a>';
    }
    $secondary .= '<a class="btn btn--ghost btn--sm" href="' . e($courseUrl) . '">'
                . ha_icon('arrow-right', 15) . 'بازگشت به دوره</a>';

    ob_start(); ?>
    <div class="next-bar" data-next-bar>
        <div class="next-bar__inner">
            <div class="next-bar__info">
                <span class="next-bar__label">
                    <?php if ($next !== null): ?>
                        <?= ha_icon('flag', 13) ?> قدمِ بعدی: <?= e((string) $next['title']) ?>
                    <?php else: ?>
                        <?= ha_icon('check', 13) ?> آخرین درسِ این دوره
                    <?php endif; ?>
                </span>
                <?php if ($prev !== null): ?>
                <a class="next-bar__prev" href="<?= e((string) $prev['url']) ?>"><?= ha_icon('arrow-right', 12) ?> درسِ قبلی</a>
                <?php endif; ?>
            </div>
            <div class="next-bar__actions">
                <?= $secondary ?>
                <?= $primary ?>
            </div>
        </div>
    </div>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  رسانه: PDF / ویدیو / صوت                                           */
/* ------------------------------------------------------------------ */

/**
 * نمایش‌دهنده‌ی PDF داخلِ سایت.
 *
 * چرا iframe و نه object/embed؟ سیاستِ امنیتیِ سایت object-src 'none' است
 * (جلوگیری از Flash/plugin)، ولی frame-src 'self' اجازه‌ی iframe از همین
 * دامنه را می‌دهد — پس PDF با مرورگرِ داخلیِ همان مرورگر باز می‌شود و
 * روی موبایل هم از عرض بیرون نمی‌زند. دکمه‌های «تبِ جدید» و «دریافت»
 * همیشه هست تا اگر مرورگری نمایشِ داخلی نداشت (بیشترِ موبایل‌ها)، راه
 * جایگزینِ یک‌کلیکی وجود داشته باشد.
 */
function pdf_viewer(string $url, string $title = '', string $note = ''): string
{
    $safe = ha_safe_file_url($url);
    if ($safe === '') {
        return '';
    }
    $title = $title !== '' ? $title : 'فایلِ PDF';
    ob_start(); ?>
    <div class="pdf-viewer">
        <div class="pdf-viewer__bar">
            <span class="pdf-viewer__title"><?= ha_icon('book', 15) ?> <?= e($title) ?></span>
            <span class="pdf-viewer__actions">
                <a class="btn btn--ghost btn--xs" href="<?= e($safe) ?>" target="_blank" rel="noopener"><?= ha_icon('external', 13) ?> تبِ جدید</a>
                <a class="btn btn--ghost btn--xs" href="<?= e($safe) ?>" download><?= ha_icon('download', 13) ?> دریافت</a>
            </span>
        </div>
        <iframe class="pdf-viewer__frame" src="<?= e($safe) ?>#toolbar=1&amp;navpanes=0&amp;view=FitH"
                title="<?= e($title) ?>" loading="lazy"></iframe>
        <?php if ($note !== ''): ?><p class="pdf-viewer__note muted-sm"><?= e($note) ?></p><?php endif; ?>
        <p class="pdf-viewer__fallback muted-sm">اگر فایل بالا دیده نمی‌شود، <a href="<?= e($safe) ?>" target="_blank" rel="noopener">اینجا باز کنید</a>.</p>
    </div>
    <?php return (string) ob_get_clean();
}

/**
 * مارکاپِ پخش‌کننده‌ی یک رسانه (ویدیو/صوت) برای نمایشِ «داخلِ سایت».
 * امبدِ مجاز (آپارات/یوتیوب/ویمئو) → iframe؛ فایلِ محلی → <video>/<audio>.
 */
function media_player(array $item): string
{
    $type = ($item['type'] ?? '') === 'audio' ? 'audio' : 'video';
    $raw  = (string) ($item['url'] ?? '');
    $title = (string) ($item['title'] ?? ($type === 'audio' ? 'فایلِ صوتی' : 'ویدیو'));

    $embed = ha_embed_url($raw);
    if ($embed !== '') {
        return '<div class="media-player media-player--embed"><iframe src="' . e($embed) . '" title="' . e($title) . '"'
             . ' loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen'
             . ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture"></iframe></div>';
    }

    $src = ha_safe_media_url($raw);
    if ($src === '') {
        return '<div class="media-placeholder media-placeholder--' . $type . '">' . ha_icon($type === 'audio' ? 'headphones' : 'play')
             . '<span>' . e($title) . '</span><small>فایلِ قابلِ پخش ثبت نشده است</small></div>';
    }

    if ($type === 'audio') {
        return '<div class="media-player media-player--audio"><audio controls preload="none" src="' . e($src) . '"'
             . ' aria-label="' . e($title) . '"></audio></div>';
    }
    return '<div class="media-player media-player--video"><video controls preload="metadata" playsinline src="' . e($src) . '"'
         . ' aria-label="' . e($title) . '"></video></div>';
}

/** آیا این رسانه امبدِ بیرونی است (نیاز به مودال دارد) یا فایلِ محلی؟ */
function media_is_embed(array $item): bool
{
    return ha_embed_url((string) ($item['url'] ?? '')) !== '';
}

/** داده‌ی JSON لازم برای مودالِ پخش (در data-* کارت‌ها). */
function media_player_payload(array $item): string
{
    /* هر کارتی که این داده را می‌گیرد، به پوسته‌ی مودال در footer نیاز دارد. */
    $GLOBALS['HA_MEDIA_MODAL'] = true;
    $payload = [
        'id'    => (string) ($item['slug'] ?? ($item['id'] ?? '')),
        'title' => (string) ($item['title'] ?? ''),
        'type'  => ($item['type'] ?? '') === 'audio' ? 'audio' : 'video',
        'embed' => ha_embed_url((string) ($item['url'] ?? '')),
        'src'   => ha_safe_media_url((string) ($item['url'] ?? '')),
    ];
    return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * پوسته‌ی مودالِ پخش — یک بار در هر صفحه چاپ می‌شود (footer).
 * JS با داده‌ی data-media-payload هر کارت پرش می‌کند.
 */
function media_modal_shell(): string
{
    ob_start(); ?>
    <div class="modal media-modal" data-media-modal hidden>
        <div class="modal__backdrop" data-media-modal-close></div>
        <div class="modal__panel modal__panel--wide" role="dialog" aria-modal="true" aria-labelledby="media-modal-title">
            <header class="modal__head">
                <h2 id="media-modal-title" class="modal__title" data-media-modal-title>پخش</h2>
                <button class="icon-btn" type="button" data-media-modal-close aria-label="بستنِ پخش‌کننده"><?= ha_icon('close', 18) ?></button>
            </header>
            <div class="modal__body" data-media-modal-body></div>
        </div>
    </div>
    <?php return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ */
/*  نظرات / تجربیات                                                    */
/* ------------------------------------------------------------------ */

/**
 * بلوکِ «تجربیاتِ دیگران» — جای مناسب برای نظرات در مسیرِ یادگیری:
 * انتهای صفحه‌ی دوره و درس، با دکمه‌ی رفتن به صفحه‌ی نظرات.
 */
function comments_teaser(int $limit = 3, string $title = 'تجربیاتِ دیگران'): string
{
    $items = function_exists('comments_approved') ? comments_approved($limit, 0) : [];
    $url   = url('comments');

    ob_start(); ?>
    <section class="card comments-teaser" aria-label="نظرات و تجربیات">
        <header class="comments-teaser__head">
            <h2><?= ha_icon('comment', 16) ?> <?= e($title) ?></h2>
            <a class="link-arrow" href="<?= e($url) ?>">همه‌ی نظرات و ثبتِ تجربه</a>
        </header>
        <?php if ($items === []): ?>
            <p class="muted-sm">هنوز تجربه‌ای ثبت نشده است. بعد از انجامِ اولین تمرین، تجربه‌ی خودتان را بنویسید تا دیگران از آن استفاده کنند.</p>
            <a class="btn btn--ghost btn--sm" href="<?= e($url) ?>"><?= ha_icon('plus', 14) ?> نوشتنِ اولین تجربه</a>
        <?php else: ?>
            <ul class="comments-teaser__list">
                <?php foreach ($items as $c): ?>
                <li class="comments-teaser__item">
                    <span class="comments-teaser__avatar" aria-hidden="true"><?= e(auth_initial((string) ($c['name'] ?? ''))) ?></span>
                    <div>
                        <p class="comments-teaser__text"><?= e($c['body'] ?? '') ?></p>
                        <p class="comments-teaser__by muted-sm"><?= e($c['name'] ?? '') ?><?php if (!empty($c['created_at'])): ?> · <?= e(ha_fa_date((string) $c['created_at'])) ?><?php endif; ?></p>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn--ghost btn--sm btn--block" href="<?= e($url) ?>"><?= ha_icon('comment', 14) ?> نظرِ من درباره‌ی این دوره</a>
        <?php endif; ?>
    </section>
    <?php return (string) ob_get_clean();
}

/** تاریخِ کوتاهِ فارسی‌شده از یک datetime (برای برچسبِ نظرات). */
function ha_fa_date(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    return fa_num(date('Y/m/d', $ts));
}
