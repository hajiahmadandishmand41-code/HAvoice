<?php
/**
 * HAvoice Admin — ذخیره‌ی دوره
 *
 * نسخه‌ی پیشین فقط یک stub بود: «دوره‌ها از طریق فایل مدیریت می‌شوند».
 * اکنون دوره‌ها در storage/admin/courses.json ذخیره می‌شوند و
 * courses() آن‌ها را با داده‌ی فایل ادغام می‌کند (includes/content.php).
 *
 * اعتبارسنجی‌ها: عنوان و نامکِ الزامی، JSONِ مراحلِ معتبر، و هشدار برایِ
 * نامکِ تکراریِ درس — چون course_lesson_index() با نامکِ درس کلید می‌خورد
 * و تکراری بودن باعث می‌شود یک درس بی‌صدا گم شود.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
require HA_ROOT . '/pages/admin/_helpers.php';

$listRoute = 'admin_courses';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url($listRoute));
if (!csrf_verify()) { flash('error', 'نشست شما تمام شده. دوباره تلاش کنید.'); redirect(url($listRoute)); }

$title   = trim((string) ($_POST['title'] ?? ''));
$slug    = slugify((string) ($_POST['slug'] ?? ''));
$orig    = slugify((string) ($_POST['original_slug'] ?? ''));
$excerpt = trim((string) ($_POST['excerpt'] ?? ''));
$intro   = trim((string) ($_POST['intro'] ?? ''));
$level   = trim((string) ($_POST['level'] ?? ''));

$editUrl = url('admin_course_edit', $orig !== '' ? ['slug' => $orig] : []);

if ($title === '' || $slug === '') {
    flash('error', 'عنوان و نامک الزامی است.');
    redirect($editUrl);
}

/* ---- حوزه: باید یکی از حوزه‌های شناخته‌شده باشد ---- */
$categoryRaw = trim((string) ($_POST['category'] ?? ''));
$category    = slugify($categoryRaw);
if ($category === '' || find_category_any($category) === null) {
    flash('error', 'حوزه‌ی انتخاب‌شده معتبر نیست.');
    redirect($editUrl);
}

/* ---- مراحل: JSON باید آرایه‌ی معتبر باشد ---- */
$stagesJson = (string) ($_POST['stages_json'] ?? '');
$stages     = [];
if (trim($stagesJson) !== '') {
    $decoded = json_decode($stagesJson, true);
    if (!is_array($decoded)) {
        flash('error', 'JSON مراحل معتبر نیست: ' . json_last_error_msg());
        redirect($editUrl);
    }
    $stages = $decoded;
}

/* ---- نرمال‌سازیِ مراحل و درس‌ها ---- */
$lessonSlugs = [];
$duplicates  = [];
$cleanStages = [];
foreach ($stages as $si => $stage) {
    if (!is_array($stage)) continue;
    $lessons = [];
    foreach ((array) ($stage['lessons'] ?? []) as $lesson) {
        if (!is_array($lesson)) continue;
        $lSlug = slugify((string) ($lesson['slug'] ?? ''));
        if ($lSlug === '') {
            /* درسِ بدونِ نامک صفحه‌ی اختصاصی ندارد؛ از عنوان می‌سازیم تا
               در course_lesson_index() گم نشود. */
            $lSlug = slugify((string) ($lesson['title'] ?? ('lesson-' . ($si + 1) . '-' . (count($lessons) + 1))));
        }
        if ($lSlug === '') continue;
        if (isset($lessonSlugs[$lSlug])) $duplicates[] = $lSlug;
        $lessonSlugs[$lSlug] = true;

        $lessons[] = [
            'slug'    => $lSlug,
            'title'   => trim((string) ($lesson['title'] ?? '')),
            'minutes' => max(0, (int) ($lesson['minutes'] ?? 0)),
            'goal'    => trim((string) ($lesson['goal'] ?? '')),
            'blocks'  => is_array($lesson['blocks'] ?? null) ? $lesson['blocks'] : [],
            'drill'   => is_array($lesson['drill'] ?? null) ? $lesson['drill'] : [],
            /* فیلدهای اختیاریِ استانداردِ درس: پیش‌نیاز و منابع.
               نرمال‌سازیِ پیشین این‌ها را حذف می‌کرد و ویرایشِ یک دوره از
               پنل، ساختارِ آموزشیِ درس را بی‌صدا می‌شکست. */
            'prerequisite' => slugify((string) ($lesson['prerequisite'] ?? '')),
            'refs'         => array_values(array_filter(array_map('trim', (array) ($lesson['refs'] ?? [])), fn($r) => $r !== '')),
        ];
    }
    $cleanStages[] = [
        'id'       => trim((string) ($stage['id'] ?? ('stage-' . ($si + 1)))),
        'label'    => trim((string) ($stage['label'] ?? '')),
        'title'    => trim((string) ($stage['title'] ?? '')),
        'summary'  => trim((string) ($stage['summary'] ?? '')),
        'outcome'  => trim((string) ($stage['outcome'] ?? '')),
        'duration' => trim((string) ($stage['duration'] ?? '')),
        'lessons'  => $lessons,
        /* آزمونِ مرحله (اختیاری) — از JSON مراحل نگه داشته می‌شود */
        'assessment' => is_array($stage['assessment'] ?? null) ? $stage['assessment'] : [],
    ];
}

/* ---- how_to: هر خطِ غیرخالی یک مورد ---- */
$howTo = [];
foreach (preg_split('/\r\n|\r|\n/', (string) ($_POST['how_to_text'] ?? '')) as $line) {
    $line = trim($line);
    if ($line !== '') $howTo[] = $line;
}

/* پروژه‌ی نهاییِ دوره (اختیاری): اگر دوره‌ی موجودِ همین نامک (چه در فایلِ
   data/course.php و چه در ذخیره‌ی قبلیِ پنل) پروژه‌ای دارد، در ذخیره‌ی
   پنل حفظ می‌شود تا ویرایشِ پنل ساختارِ آموزشی را نبُرد. */
$existingProject = [];
foreach (courses_all() as $c) {
    if (slugify((string) ($c['slug'] ?? '')) === ($orig !== '' ? $orig : $slug)) {
        $existingProject = is_array($c['project'] ?? null) ? $c['project'] : [];
        break;
    }
}

$now = date('c');
$existingMeta = [];
foreach (admin_courses() as $ec) {
    if (slugify((string) ($ec['slug'] ?? '')) === ($orig !== '' ? $orig : $slug)) {
        $existingMeta = $ec;
        break;
    }
}
$course = [
    'slug'       => $slug,
    'title'      => $title,
    'category'   => $category,
    'level'      => $level !== '' ? $level : 'مقدماتی تا متوسط',
    'excerpt'    => $excerpt !== '' ? $excerpt : (mb_strimwidth($intro, 0, 160, '…', 'UTF-8')),
    'intro'      => $intro,
    'how_to'     => $howTo,
    'stages'     => $cleanStages,
    'featured'   => !empty($_POST['featured']),
    'status'     => admin_post_status_default_draft($existingMeta === [] && $orig === ''),
    'project'    => $existingProject,
    'prereq'     => trim((string) ($_POST['prereq'] ?? '')),
    'created_at' => (string) ($existingMeta['created_at'] ?? $now),
    'updated_at' => $now,
    'order'      => (int) ($existingMeta['order'] ?? count(admin_courses())),
];

/* ---- ذخیره: DB (PDO) + آینه JSON ---- */
$course['_original_slug'] = $orig;
if (!repo_save_course($course, $orig)) {
    flash('error', 'ذخیره‌سازی ناموفق بود؛ دیتابیس یا storage قابل نوشتن نیست.');
    redirect($editUrl);
}

$lessonCount = 0;
foreach ($cleanStages as $st) $lessonCount += count($st['lessons']);
flash('success', 'دوره «' . $title . '» ذخیره شد (' . fa_num($lessonCount) . ' درس).'
    . ($duplicates !== [] ? ' هشدار: نامکِ تکراریِ درس: ' . implode('، ', array_slice(array_unique($duplicates), 0, 5)) : ''));
redirect(url('admin_courses'));
