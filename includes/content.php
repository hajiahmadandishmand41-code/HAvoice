<?php
/**
 * HAvoice — لایه‌ی محتوا: توابع خواندن دوره، درس‌ها، مقالات و تمرین‌ها.
 * همه‌ی صفحات از همین توابع استفاده می‌کنند تا منطق تکراری نباشد.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  دوره آموزشی                                                       */
/* ------------------------------------------------------------------ */

function course(): array
{
    return data('course');
}

function course_stages(): array
{
    return (array) (course()['stages'] ?? []);
}

/** فهرست خطی همه درس‌ها + جایگاه هر درس در کل دوره. */
function course_lesson_index(): array
{
    static $index = null;
    if ($index !== null) {
        return $index;
    }

    $index = [];
    $n     = 0;
    $stages = course_stages();
    $total  = 0;

    foreach ($stages as $sIdx => $stage) {
        $total += count((array) ($stage['lessons'] ?? []));
    }

    foreach ($stages as $sIdx => $stage) {
        foreach ((array) ($stage['lessons'] ?? []) as $lIdx => $lesson) {
            $n++;
            $index[(string) $lesson['slug']] = [
                'stage'       => $stage,
                'stageIndex'  => (int) $sIdx,
                'lesson'      => $lesson,
                'lessonIndex' => (int) $lIdx,
                'position'    => $n,
                'total'       => $total,
            ];
        }
    }

    return $index;
}

function course_find_lesson(string $slug): ?array
{
    $index = course_lesson_index();

    return $index[slugify($slug)] ?? null;
}

/** درس قبلی/بعدی برای پیمایش. */
function course_neighbours(string $slug): array
{
    $index  = course_lesson_index();
    $slugs  = array_keys($index);
    $cursor = array_search(slugify($slug), $slugs, true);

    if ($cursor === false) {
        return ['prev' => null, 'next' => null];
    }

    return [
        'prev' => $cursor > 0 ? $index[$slugs[$cursor - 1]]['lesson'] : null,
        'next' => isset($slugs[$cursor + 1]) ? $index[$slugs[$cursor + 1]]['lesson'] : null,
    ];
}

/** فهرست نامک درس‌های هر مرحله (برای محاسبه پیشرفت در سمت کلاینت). */
function course_stage_slugs(array $stage): array
{
    $out = [];
    foreach ((array) ($stage['lessons'] ?? []) as $lesson) {
        $out[] = (string) $lesson['slug'];
    }

    return $out;
}

/** مجموع زمان کل دوره به دقیقه. */
function course_total_minutes(): int
{
    $sum = 0;
    foreach (course_lesson_index() as $item) {
        $sum += (int) ($item['lesson']['minutes'] ?? 0);
    }

    return $sum;
}

/* ------------------------------------------------------------------ */
/*  مقالات                                                            */
/* ------------------------------------------------------------------ */

function article_categories(): array
{
    $cats = [];
    foreach (data('articles') as $article) {
        $cat = (string) ($article['category'] ?? 'عمومی');
        $cats[$cat] = ($cats[$cat] ?? 0) + 1;
    }
    ksort($cats, SORT_STRING | SORT_FLAG_CASE);

    return $cats;
}

/** تازه‌ترین‌ها به ترتیب تاریخ. */
function latest_articles(int $limit = 3, ?string $excludeSlug = null): array
{
    $list = all_articles_sorted();

    if ($excludeSlug !== null) {
        $list = array_values(array_filter($list, static function (array $a) use ($excludeSlug) {
            return (string) ($a['slug'] ?? '') !== $excludeSlug;
        }));
    }

    return array_slice($list, 0, $limit);
}

/** مقالات مرتبط: هم‌دسته بودن و اشتراک برچسب. */
function related_articles(array $current, int $limit = 3): array
{
    $scored = [];

    foreach (data('articles') as $article) {
        if (($article['slug'] ?? null) === ($current['slug'] ?? null)) {
            continue;
        }

        $score = 0;
        if (($article['category'] ?? '') === ($current['category'] ?? '')) {
            $score += 3;
        }

        $shared = array_intersect(
            (array) ($article['tags'] ?? []),
            (array) ($current['tags'] ?? [])
        );
        $score += count($shared) * 2;

        if ($score > 0) {
            $scored[] = ['score' => $score, 'item' => $article];
        }
    }

    usort($scored, static function (array $a, array $b) {
        return $b['score'] <=> $a['score'];
    });

    return array_column(array_slice($scored, 0, $limit), 'item');
}

/* ------------------------------------------------------------------ */
/*  تمرین‌ها و نکته‌ها                                                 */
/* ------------------------------------------------------------------ */

function exercises(): array
{
    return data('exercises');
}

function tips(): array
{
    return data('tips');
}

/** گروه‌بندی تمرین‌ها بر اساس سطح. */
function exercises_by_level(): array
{
    $grouped = [];
    foreach (exercises() as $exercise) {
        $level = (string) ($exercise['level'] ?? 'عمومی');
        $grouped[$level][] = $exercise;
    }

    return $grouped;
}
