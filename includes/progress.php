<?php
/**
 * HAvoice — پیشرفتِ یادگیری و «مسیرِ یادگیری»
 *
 * این ماژول یک سؤال را برای کاربر پاسخ می‌دهد:
 *   «الان کجام؟ درسِ بعدی چیست؟ تمرینِ بعدی چیست؟»
 *
 * سه وضعیت برای هر درس/تمرین:
 *   ''        → انجام‌نشده (هنوز باز نشده)
 *   'started' → در حالِ مطالعه (صفحه‌ی درس باز شده)
 *   'done'    → تکمیل‌شده (کاربر دکمه‌ی «تکمیل شد» را زده)
 *
 * ذخیره‌سازی:
 *   ۱. MySQL (جدولِ ha_progress) — منبعِ حقیقت در Production
 *   ۲. storage/admin/progress.json — وقتی DB پیکربندی نشده باشد
 *      (همان الگوی dual-storage بقیه‌ی ماژول‌ها)
 *
 * پیش‌تر پیشرفت فقط در localStorage مرورگر بود؛ یعنی با عوض‌شدنِ دستگاه
 * یا پاک‌شدنِ کش، «درسِ فعلی» کاربر گم می‌شد و صفحه‌ی دوره نمی‌توانست
 * بگوید قدمِ بعدی چیست. اکنون وضعیت سمتِ سرور است و چون صفحه‌های
 * دوره/درس/تمرین پشتِ ورود هستند، همیشه کاربرِ مشخصی وجود دارد.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/** وضعیت‌های مجاز. */
function progress_valid_states(): array
{
    return ['', 'started', 'done'];
}

/** نوع‌های مجازِ قلمِ پیشرفت. */
function progress_valid_types(): array
{
    return ['lesson', 'exercise'];
}

/** شناسه‌ی کاربرِ جاری برای ثبتِ پیشرفت ('' یعنی کسی وارد نشده). */
function progress_user_id(): string
{
    $user = function_exists('auth_current_user') ? auth_current_user() : null;
    return $user !== null ? (string) ($user['id'] ?? '') : '';
}

/* ------------------------------------------------------------------ */
/*  ذخیره‌سازی                                                         */
/* ------------------------------------------------------------------ */

/**
 * نقشه‌ی کاملِ پیشرفتِ کاربرِ جاری: ['lesson' => [key => state], 'exercise' => […]].
 * یک بار در هر درخواست خوانده می‌شود (کشِ static).
 */
function progress_states(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = ['lesson' => [], 'exercise' => []];

    $userId = progress_user_id();
    if ($userId === '') {
        return $cache;
    }

    if (function_exists('db_ready') && db_ready()) {
        foreach (db_progress_all($userId) as $row) {
            $type = (string) ($row['item_type'] ?? '');
            $key  = (string) ($row['item_key'] ?? '');
            if (!in_array($type, progress_valid_types(), true) || $key === '') {
                continue;
            }
            $cache[$type][$key] = (string) ($row['state'] ?? 'started');
        }
        return $cache;
    }

    $all   = admin_load('progress');
    $mine  = $all[$userId] ?? [];
    if (!is_array($mine)) {
        return $cache;
    }
    foreach ($mine as $composite => $state) {
        if (!is_string($composite) || strpos($composite, ':') === false) {
            continue;
        }
        [$type, $key] = explode(':', $composite, 2);
        if (!in_array($type, progress_valid_types(), true) || $key === '') {
            continue;
        }
        $cache[$type][$key] = in_array($state, ['started', 'done'], true) ? $state : 'started';
    }
    return $cache;
}

/** وضعیتِ یک قلم ('' | 'started' | 'done'). */
function progress_state(string $type, string $key): string
{
    $type = in_array($type, progress_valid_types(), true) ? $type : 'lesson';
    $key  = slugify($key);
    if ($key === '') {
        return '';
    }
    $states = progress_states();
    return (string) ($states[$type][$key] ?? '');
}

function progress_lesson_state(string $lessonSlug): string
{
    return progress_state('lesson', $lessonSlug);
}

function progress_exercise_state(string $exerciseId): string
{
    return progress_state('exercise', $exerciseId);
}

/**
 * نوشتنِ وضعیتِ یک قلم.
 * state='' ⇒ حذفِ رکورد (برگشت به «انجام‌نشده»).
 */
function progress_set(string $type, string $key, string $state): bool
{
    $type = in_array($type, progress_valid_types(), true) ? $type : 'lesson';
    $key  = slugify($key);
    if ($key === '') {
        return false;
    }
    if (!in_array($state, ['started', 'done'], true)) {
        $state = '';
    }
    $userId = progress_user_id();
    if ($userId === '') {
        return false;
    }

    if (function_exists('db_ready') && db_ready()) {
        return db_progress_set($userId, $type, $key, $state);
    }

    $all = admin_load('progress');
    if (!isset($all[$userId]) || !is_array($all[$userId])) {
        $all[$userId] = [];
    }
    $composite = $type . ':' . $key;
    if ($state === '') {
        unset($all[$userId][$composite]);
    } else {
        $all[$userId][$composite] = $state;
    }
    return admin_store('progress', $all);
}

/**
 * ثبتِ «در حالِ مطالعه» — فقط اگر هنوز وضعیتی ندارد.
 * صفحه‌ی درس این را صدا می‌زند تا وضعیتِ سه‌گانه بدونِ کلیکِ کاربر هم
 * درست باشد (بدونِ بازنویسیِ «تکمیل‌شده»).
 */
function progress_mark_started(string $lessonSlug): bool
{
    if (progress_lesson_state($lessonSlug) !== '') {
        return false;
    }
    return progress_set('lesson', $lessonSlug, 'started');
}

/** پاک‌کردنِ پیشرفتِ یک دوره (درس‌ها + تمرین‌های همان دوره). */
function progress_reset_course(array $course): int
{
    $removed = 0;
    foreach (course_lesson_slugs($course) as $slug) {
        if (progress_set('lesson', $slug, '')) {
            $removed++;
        }
    }
    foreach (exercises_for_course_ordered(slugify((string) ($course['slug'] ?? ''))) as $ex) {
        if (progress_set('exercise', (string) ($ex['id'] ?? ''), '')) {
            $removed++;
        }
    }
    return $removed;
}

/** برچسب/کلاس/آیکونِ یک وضعیت — تنها منبعِ حقیقت برای UI. */
function progress_state_meta(string $state): array
{
    switch ($state) {
        case 'done':
            return ['key' => 'done', 'label' => 'تکمیل‌شده', 'class' => 'is-done', 'icon' => 'check'];
        case 'started':
            return ['key' => 'started', 'label' => 'در حالِ مطالعه', 'class' => 'is-started', 'icon' => 'clock'];
        default:
            return ['key' => 'todo', 'label' => 'انجام‌نشده', 'class' => 'is-todo', 'icon' => 'circle'];
    }
}

/* ------------------------------------------------------------------ */
/*  ترتیبِ تمرین‌ها                                                    */
/* ------------------------------------------------------------------ */

/**
 * تمرین‌های یک دوره با ترتیبِ قابلِ پیش‌بینی:
 *   ۱. تمرین‌های متصل به درس‌ها، به ترتیبِ خودِ درس‌ها
 *   ۲. تمرین‌های متصل به خودِ دوره (بدونِ درس)
 * اگر مدیر «ترتیب» (order) داده باشد، در هر گروه همان ملاک است.
 *
 * @return list<array<string,mixed>>
 */
function exercises_for_course_ordered(string $courseSlug): array
{
    $courseSlug = slugify($courseSlug);
    if ($courseSlug === '') {
        return [];
    }
    $all = exercises_for_course($courseSlug);
    if ($all === []) {
        return [];
    }

    /* جایگاهِ هر درس در دوره — برای چیدنِ تمرین‌ها همان ترتیبِ درس‌ها */
    $lessonPos = [];
    $course    = find_course($courseSlug);
    if ($course !== null) {
        $i = 0;
        foreach ((array) ($course['stages'] ?? []) as $stage) {
            foreach ((array) ($stage['lessons'] ?? []) as $lesson) {
                $lessonPos[slugify((string) ($lesson['slug'] ?? ''))] = $i++;
            }
        }
    }

    $decorated = [];
    foreach ($all as $index => $ex) {
        $lesson = slugify((string) ($ex['lesson'] ?? ''));
        $decorated[] = [
            'ex'       => $ex,
            'order'    => (int) ($ex['order'] ?? 0),
            'hasOrder' => isset($ex['order']) && (int) $ex['order'] > 0,
            'group'    => $lesson !== '' ? 0 : 1,
            'pos'      => $lesson !== '' ? (int) ($lessonPos[$lesson] ?? 999) : 999,
            'index'    => $index,
        ];
    }

    /* ترتیبِ قابلِ پیش‌بینی و قابلِ کنترل از پنل:
         ۱. اول تمرین‌های متصل به درس‌ها، بعد تمرین‌های بدونِ درس
         ۲. بینِ تمرین‌های یک درس، جایِ خودِ درس در دوره ملاک است
         ۳. بعد عددِ «ترتیب» که مدیر در پنلِ تمرین می‌نویسد (sort_order)
         ۴. در تساوی، ترتیبِ ذخیره‌شده
       همین سه معیار باعث می‌شود «تمرینِ بعدی» در پنلِ مسیرِ یادگیری همیشه
       همان چیزی باشد که مدیر چیده است. */
    usort($decorated, static function (array $a, array $b): int {
        if ($a['group'] !== $b['group']) {
            return $a['group'] <=> $b['group'];
        }
        if ($a['pos'] !== $b['pos']) {
            return $a['pos'] <=> $b['pos'];
        }
        if ($a['order'] !== $b['order']) {
            return $a['order'] <=> $b['order'];
        }
        return $a['index'] <=> $b['index'];
    });

    return array_map(static fn(array $d): array => $d['ex'], $decorated);
}

/* ------------------------------------------------------------------ */
/*  مسیرِ یادگیری                                                      */
/* ------------------------------------------------------------------ */

/**
 * خلاصه‌ی پیشرفتِ یک دوره برای کاربرِ جاری.
 *
 * @return array{total:int, done:int, started:int, percent:int, minutes:int, done_minutes:int}
 */
function progress_course_summary(array $course): array
{
    $total = 0;
    $done  = 0;
    $started = 0;
    $minutes = 0;
    $doneMinutes = 0;
    foreach ((array) ($course['stages'] ?? []) as $stage) {
        foreach ((array) ($stage['lessons'] ?? []) as $lesson) {
            $slug = slugify((string) ($lesson['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $total++;
            $m = (int) ($lesson['minutes'] ?? 0);
            $minutes += $m;
            $state = progress_lesson_state($slug);
            if ($state === 'done') {
                $done++;
                $doneMinutes += $m;
            } elseif ($state === 'started') {
                $started++;
            }
        }
    }
    return [
        'total'        => $total,
        'done'         => $done,
        'started'      => $started,
        'percent'      => $total > 0 ? (int) round($done / $total * 100) : 0,
        'minutes'      => $minutes,
        'done_minutes' => $doneMinutes,
    ];
}

/** خلاصه‌ی پیشرفتِ تمرین‌های یک دوره. */
function progress_exercise_summary(string $courseSlug): array
{
    $list = exercises_for_course_ordered($courseSlug);
    $done = 0;
    foreach ($list as $ex) {
        if (progress_exercise_state((string) ($ex['id'] ?? '')) === 'done') {
            $done++;
        }
    }
    return ['total' => count($list), 'done' => $done];
}

/**
 * خلاصه‌ی پیشرفتِ کاربرِ جاری در همه‌ی دوره‌ها (یک بار محاسبه می‌شود).
 *
 * چون progress_states() کش دارد، پیمایشِ همه‌ی دوره‌ها ارزان است و صفحه‌ی
 * اصلی و صفحه‌ی «پیشرفتِ من» هر دو از همین خروجی استفاده می‌کنند — یعنی
 * عددِ پیشرفت در همه‌جای سایت یکی است و به JS نیاز ندارد.
 *
 * @return array{courses:int,lessons:int,done:int,started:int,percent:int,
 *               exercises:int,ex_done:int,ex_percent:int,
 *               active_course:array<string,mixed>|null,active_next:array<string,mixed>|null}
 */
function progress_overview(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $out = [
        'courses'       => 0,
        'lessons'       => 0,
        'done'          => 0,
        'started'       => 0,
        'percent'       => 0,
        'exercises'     => 0,
        'ex_done'       => 0,
        'ex_percent'    => 0,
        'active_course' => null,
        'active_next'   => null,
    ];

    foreach (courses() as $course) {
        $summary = progress_course_summary($course);
        $exSum   = progress_exercise_summary(slugify((string) ($course['slug'] ?? '')));

        $out['courses']++;
        $out['lessons']   += (int) $summary['total'];
        $out['done']      += (int) $summary['done'];
        $out['started']   += (int) $summary['started'];
        $out['exercises'] += (int) $exSum['total'];
        $out['ex_done']   += (int) $exSum['done'];

        /* اولین دوره‌ای که کاربر در آن قدمی برداشته است = «دوره‌ی در جریان» */
        if ($out['active_course'] === null && ((int) $summary['done'] > 0 || (int) $summary['started'] > 0 || (int) $exSum['done'] > 0)) {
            $path = course_learning_path($course);
            $out['active_course'] = $course;
            $out['active_next']   = $path['current'] ?? ($path['lessons'][0] ?? null);
        }
    }

    $out['percent']    = $out['lessons'] > 0 ? (int) round($out['done'] / $out['lessons'] * 100) : 0;
    $out['ex_percent'] = $out['exercises'] > 0 ? (int) round($out['ex_done'] / $out['exercises'] * 100) : 0;

    $cache = $out;
    return $cache;
}

/**
 * مسیرِ یادگیریِ یک دوره — همان چیزی که صفحه‌ی دوره و صفحه‌ی درس
 * برایِ «قدمِ بعدی» نشان می‌دهند.
 *
 * خروجی:
 *   lessons[]  → هر درس با وضعیت و جایگاه
 *   current    → درسِ فعلی (اولین درسِ تکمیل‌نشده) یا null
 *   next       → درسِ بعدی یا null
 *   exercises[]→ تمرین‌های دوره با وضعیت
 *   exercise_current / exercise_next
 *   summary    → progress_course_summary()
 *
 * @return array<string,mixed>
 */
function course_learning_path(array $course): array
{
    $courseSlug = slugify((string) ($course['slug'] ?? ''));

    $lessons = [];
    $pos     = 0;
    foreach ((array) ($course['stages'] ?? []) as $sIdx => $stage) {
        foreach ((array) ($stage['lessons'] ?? []) as $lesson) {
            $slug = slugify((string) ($lesson['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $pos++;
            $lessons[] = [
                'slug'       => $slug,
                'title'      => (string) ($lesson['title'] ?? ''),
                'goal'       => (string) ($lesson['goal'] ?? ''),
                'minutes'    => (int) ($lesson['minutes'] ?? 0),
                'position'   => $pos,
                'stage'      => (string) ($stage['title'] ?? $stage['label'] ?? ''),
                'stageId'    => (string) ($stage['id'] ?? ''),
                'stageIndex' => (int) $sIdx,
                'state'      => progress_lesson_state($slug),
                'url'        => url('lesson', ['slug' => $slug]),
            ];
        }
    }

    $current = null;
    $next    = null;
    foreach ($lessons as $i => $l) {
        if ($l['state'] !== 'done') {
            $current = $l;
            $next    = $lessons[$i + 1] ?? null;
            break;
        }
    }
    if ($current === null && $lessons !== []) {
        /* همه تکمیل شده‌اند: درسِ فعلی = آخرین درس، بعدی = هیچ */
        $current = $lessons[count($lessons) - 1];
    }

    $exercises = [];
    $ePos      = 0;
    foreach (exercises_for_course_ordered($courseSlug) as $ex) {
        $id = slugify((string) ($ex['id'] ?? ''));
        if ($id === '') {
            continue;
        }
        $ePos++;
        $lessonSlug = slugify((string) ($ex['lesson'] ?? ''));
        $lessonTitle = '';
        if ($lessonSlug !== '') {
            $info = course_find_lesson($lessonSlug);
            $lessonTitle = $info !== null ? (string) ($info['lesson']['title'] ?? '') : '';
        }
        $exercises[] = [
            'id'          => $id,
            'title'       => (string) ($ex['title'] ?? ''),
            'goal'        => (string) ($ex['goal'] ?? ''),
            'seconds'     => (int) ($ex['seconds'] ?? 0),
            'position'    => $ePos,
            'lesson'      => $lessonSlug,
            'lessonTitle' => $lessonTitle,
            'state'       => progress_exercise_state($id),
            'url'         => $lessonSlug !== ''
                ? url('exercises', ['lesson' => $lessonSlug]) . '#ex-' . $id
                : url('exercises') . '#ex-' . $id,
            'data'        => $ex,
        ];
    }

    $exerciseCurrent = null;
    $exerciseNext    = null;
    foreach ($exercises as $i => $ex) {
        if ($ex['state'] !== 'done') {
            $exerciseCurrent = $ex;
            $exerciseNext    = $exercises[$i + 1] ?? null;
            break;
        }
    }
    if ($exerciseCurrent === null && $exercises !== []) {
        $exerciseCurrent = $exercises[count($exercises) - 1];
    }

    return [
        'course'           => $courseSlug,
        'lessons'          => $lessons,
        'current'          => $current,
        'next'             => $next,
        'exercises'        => $exercises,
        'exercise_current' => $exerciseCurrent,
        'exercise_next'    => $exerciseNext,
        'summary'          => progress_course_summary($course),
        'exercise_summary' => progress_exercise_summary($courseSlug),
    ];
}

/**
 * مسیرِ یادگیری از نگاهِ «یک درسِ مشخص»: درسِ قبلی/بعدی داخلِ همان دوره
 * به‌همراهِ تمرینِ متصل به این درس و تمرینِ بعدیِ دوره.
 *
 * @return array<string,mixed>
 */
function lesson_learning_path(string $lessonSlug): array
{
    $info = course_find_lesson($lessonSlug);
    if ($info === null) {
        return ['path' => null, 'prev' => null, 'next' => null, 'exercise' => null, 'exercise_next' => null, 'state' => ''];
    }
    $path    = course_learning_path((array) $info['course']);
    $current = slugify($lessonSlug);

    $prev = null;
    $next = null;
    foreach ($path['lessons'] as $i => $l) {
        if ($l['slug'] !== $current) {
            continue;
        }
        $prev = $path['lessons'][$i - 1] ?? null;
        $next = $path['lessons'][$i + 1] ?? null;
        break;
    }

    /* تمرینِ همین درس (اولین تمرینِ متصل) و تمرینِ بعدیِ دوره */
    $exercise     = null;
    $exerciseNext = null;
    foreach ($path['exercises'] as $i => $ex) {
        if ($ex['lesson'] !== $current) {
            continue;
        }
        $exercise = $ex;
        $exerciseNext = $path['exercises'][$i + 1] ?? null;
        break;
    }

    return [
        'path'          => $path,
        'prev'          => $prev,
        'next'          => $next,
        'exercise'      => $exercise,
        'exercise_next' => $exerciseNext,
        'state'         => progress_lesson_state($current),
    ];
}
