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
$orig    = slugify((string) ($_POST['original_slug'] ?? ''));
$excerpt = trim((string) ($_POST['excerpt'] ?? ''));
$intro   = trim((string) ($_POST['intro'] ?? ''));
$level   = trim((string) ($_POST['level'] ?? ''));

$editUrl = url('admin_course_edit', $orig !== '' ? ['slug' => $orig] : []);

if ($title === '') {
    flash('error', 'نام دوره الزامی است.');
    redirect($editUrl);
}

$slug = admin_post_slug($title, 'course', static function (string $candidate) use ($orig): bool {
    if ($candidate === $orig) {
        return false;
    }
    foreach (courses_all() as $c) {
        if (slugify((string) ($c['slug'] ?? '')) === $candidate) {
            return true;
        }
    }
    return false;
});
if ($slug === '' && $orig !== '') {
    $slug = $orig;
}
if ($slug === '') {
    flash('error', 'نامک از روی نام ساخته نشد؛ یک نام لاتین یا فارسی بنویسید.');
    redirect($editUrl);
}

/* ---- حوزه: اختیاری؛ اگر خالی باشد اولین حوزه یا همان قبلی ---- */
$categoryRaw = trim((string) ($_POST['category'] ?? ''));
$category    = slugify($categoryRaw);
if ($category !== '' && find_category_any($category) === null) {
    flash('error', 'حوزه‌ی انتخاب‌شده معتبر نیست.');
    redirect($editUrl);
}
/* نسخه‌ی فعلیِ همین دوره (از فایلِ data یا ذخیره‌ی قبلیِ پنل) — برایِ اینکه
   هنگامِ ویرایش، داده‌ای که فرمِ ساده نشان نمی‌دهد پاک نشود. */
$existingCourseForBuilder = [];
foreach (courses_all() as $c) {
    $cSlug = slugify((string) ($c['slug'] ?? ''));
    if (($orig !== '' && $cSlug === $orig) || ($cSlug === $slug)) {
        $existingCourseForBuilder = $c;
        break;
    }
}
if ($category === '') {
    $cats = function_exists('categories_all') ? categories_all() : categories();
    $category = slugify((string) (($existingCourseForBuilder['category'] ?? '') !== ''
        ? $existingCourseForBuilder['category']
        : ($cats[0]['slug'] ?? '')));
}
$stages = [];

if (isset($_POST['save_from_json'])) {
    $stagesJson = (string) ($_POST['stages_json'] ?? '');
    if (trim($stagesJson) !== '') {
        $decoded = json_decode($stagesJson, true);
        if (!is_array($decoded)) {
            flash('error', 'JSON مراحل معتبر نیست: ' . json_last_error_msg());
            redirect($editUrl);
        }
        $stages = $decoded;
    }
} else {
    $stages = admin_course_stages_from_post($existingCourseForBuilder);
    $builderErrors = admin_builder_errors();
    if ($builderErrors !== []) {
        flash('error', 'ذخیره انجام نشد: ' . implode(' | ', array_slice($builderErrors, 0, 3)));
        redirect($editUrl);
    }
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
        $typedSlug = slugify((string) ($lesson['slug'] ?? ''));
        $lSlug     = ($typedSlug !== '' && $typedSlug !== '-') ? $typedSlug : '';
        if ($lSlug === '') {
            /* درسِ بدونِ نامک صفحه‌ی اختصاصی ندارد؛ پس نامک می‌سازیم.
               نکته: عنوان‌های فارسی با slugify به «-» تبدیل می‌شوند، پس
               پیشوندِ نامکِ دوره همیشه می‌آید تا نامک هم یکتا باشد (در کلِ
               سایت) و هم با ذخیره‌های بعدی ثابت بماند. */
            $latin = trim(slugify(trim((string) preg_replace('/[^\x20-\x7E]/u', '', (string) ($lesson['title'] ?? '')))), '-');
            $lSlug = slugify($slug . ($latin !== '' && $latin !== '-' ? '-' . $latin : '-l' . ($si + 1) . '-' . (count($lessons) + 1)));

            /* اگر همان نامک در درسِ دیگری (بیرون از این دوره) هست، شماره می‌گیرد
               تا lesson slug در course_lesson_index() گم نشود. */
            $taken = static function (string $candidate) use ($slug): bool {
                foreach (course_lesson_index() as $ls => $info) {
                    $key = slugify((string) $ls);
                    if ($key === $candidate && slugify((string) ($info['course']['slug'] ?? '')) !== $slug) {
                        return true;
                    }
                }
                return false;
            };
            $n = 2;
            while ($taken($lSlug) && $n < 50) {
                $lSlug = slugify($slug . '-' . $latin . '-' . $n);
                $n++;
            }
        }
        if ($lSlug === '' || $lSlug === '-') continue;
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
$existingProject = is_array($existingCourseForBuilder['project'] ?? null)
    ? $existingCourseForBuilder['project']
    : [];

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
