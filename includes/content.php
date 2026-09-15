<?php
/**
 * HAvoice 2.0 — لایه‌ی محتوا
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  دوره‌ها                                                            */
/* ------------------------------------------------------------------ */

/**
 * همه‌ی دوره‌ها: داده‌ی فایل + دوره‌های ساخته‌شده در پنل.
 *
 * پیش‌تر فقط data('course') خوانده می‌شد و هیچ ادغامی با admin_load()
 * وجود نداشت — برخلافِ مقاله‌ها/کتاب‌ها/تمرین‌ها که همه ادغام می‌شدند.
 * به همین دلیل course_save.php یک stub بود که فقط پیامِ «دوره‌ها را در
 * فایل ویرایش کنید» می‌داد و عملاً ساختِ دوره از پنل ناممکن بود.
 *
 * ترتیب مهم است: دوره‌های پنل «بعد» از دوره‌های فایل می‌آیند تا اگر
 * slug یکسانی ساخته شد، دوره‌ی پنل در find_course() برنده شود (همان
 * الگویی که برای بقیه‌ی محتواها استفاده شده و ویرایشِ رویِ داده‌ی فایل
 * را ممکن می‌کند).
 */
/**
 * فهرستِ کاملِ دوره‌ها (فایل + پنل، با اعمالِ بازنویسیِ پنل، بدونِ
 * فیلترِ انتشار) — برای پنل مدیریت و شمارش‌های داخلی.
 */
function courses_all(): array
{
    $raw = data('course');
    if (isset($raw['courses']) && is_array($raw['courses'])) {
        return ha_merge_overrides($raw['courses'], admin_courses());
    }
    // fallback: legacy single course
    if (isset($raw['stages'])) {
        return ha_merge_overrides([[
            'slug'     => 'public-speaking-fundamentals',
            'title'    => $raw['title'] ?? 'مسیر آموزشی',
            'category' => 'public-speaking',
            'level'    => 'مقدماتی تا متوسط',
            'excerpt'  => $raw['intro'] ?? '',
            'intro'    => $raw['intro'] ?? '',
            'how_to'   => $raw['how_to'] ?? [],
            'stages'   => $raw['stages'] ?? [],
            'featured' => true,
        ]], admin_courses());
    }
    return admin_courses();
}

function courses(): array
{
    return ha_visible(courses_all());
}

/** فقط دوره‌هایی که از پنلِ مدیریت ذخیره شده‌اند. */
function admin_courses(): array
{
    return admin_load('courses');
}


function course(): array { return data('course'); }

function course_stages(): array { return (array)(course()['stages'] ?? []); }

function courses_featured(int $limit=3): array
{
    $list = array_values(array_filter(courses(), fn($c)=>!empty($c['featured'])));
    if (count($list) < $limit) $list = courses();
    return array_slice($list,0,$limit);
}

function find_course(string $slug): ?array
{
    $slug = slugify($slug);
    foreach (courses() as $c) if (slugify($c['slug']??'')=== $slug) return $c;
    return null;
}

function course_lesson_index(): array
{
    static $index=null;
    if ($index!==null) return $index;
    $index=[];
    $n=0; $total=0;
    $all = courses();
    foreach ($all as $course) foreach ((array)($course['stages']??[]) as $stage) $total += count((array)($stage['lessons']??[]));
    foreach ($all as $course) {
        foreach ((array)($course['stages']??[]) as $sIdx=>$stage) {
            foreach ((array)($stage['lessons']??[]) as $lIdx=>$lesson) {
                $n++;
                $index[(string)$lesson['slug']] = [
                    'course'      => $course,
                    'stage'       => $stage,
                    'stageIndex'  => (int)$sIdx,
                    'lesson'      => $lesson,
                    'lessonIndex' => (int)$lIdx,
                    'position'    => $n,
                    'total'       => $total,
                ];
            }
        }
    }
    return $index;
}

function course_find_lesson(string $slug): ?array
{
    $index = course_lesson_index();
    return $index[slugify($slug)] ?? null;
}

function course_neighbours(string $slug): array
{
    $index = course_lesson_index();
    $slugs = array_keys($index);
    $cursor = array_search(slugify($slug),$slugs,true);
    if ($cursor===false) return ['prev'=>null,'next'=>null];
    return [
        'prev'=> $cursor>0 ? $index[$slugs[$cursor-1]]['lesson']:null,
        'next'=> isset($slugs[$cursor+1]) ? $index[$slugs[$cursor+1]]['lesson']:null,
    ];
}

function course_stage_slugs(array $stage): array
{
    $out=[];
    foreach ((array)($stage['lessons']??[]) as $lesson) $out[]=(string)$lesson['slug'];
    return $out;
}

function course_total_minutes(): int
{
    $sum=0; foreach (course_lesson_index() as $item) $sum+=(int)($item['lesson']['minutes']??0); return $sum;
}

function lessons_by_category(string $category): array
{
    $out=[];
    foreach (course_lesson_index() as $info) if (($info['course']['category']??'')=== $category) $out[]=$info;
    return $out;
}

/* ------------------------------------------------------------------ */
/*  مقالات                                                            */
/* ------------------------------------------------------------------ */

/** همه‌ی مقاله‌ها (فایل + پنل) بدونِ فیلترِ انتشار — برای پنل مدیریت. */
function articles_all(): array { return ha_content_admin('articles'); }
function articles(): array { return ha_visible(articles_all()); }
function all_articles_sorted(): array
{
    $articles = articles();
    usort($articles, static function (array $a, array $b) {
        return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
    });
    return $articles;
}

function article_categories(): array
{
    $cats=[];
    foreach (articles() as $article){ $cat=(string)($article['category']??'عمومی'); $cats[$cat]=($cats[$cat]??0)+1; }
    ksort($cats,SORT_STRING|SORT_FLAG_CASE);
    return $cats;
}

function latest_articles(int $limit=3, ?string $excludeSlug=null): array
{
    $list = all_articles_sorted();
    if ($excludeSlug!==null) $list=array_values(array_filter($list, fn(array $a)=> (string)($a['slug']??'')!== $excludeSlug));
    return array_slice($list,0,$limit);
}

function related_articles(array $current, int $limit=3): array
{
    $scored=[];
    foreach (articles() as $article){
        if (($article['slug']??null)===($current['slug']??null)) continue;
        $score=0;
        if (($article['category']??'')===($current['category']??'')) $score+=3;
        $shared = array_intersect((array)($article['tags']??[]),(array)($current['tags']??[]));
        $score+=count($shared)*2;
        if ($score>0) $scored[]=['score'=>$score,'item'=>$article];
    }
    usort($scored, fn(array $a,array $b)=> $b['score']<=>$a['score']);
    return array_column(array_slice($scored,0,$limit),'item');
}

/* ------------------------------------------------------------------ */
/*  تمرین‌ها و نکته‌ها                                                 */
/* ------------------------------------------------------------------ */

/** همه‌ی تمرین‌ها (فایل + پنل) بدونِ فیلترِ انتشار — برای پنل مدیریت. */
function exercises_all(): array { return ha_content_admin('exercises', 'id'); }
function exercises(): array { return ha_visible(exercises_all()); }
/** همه‌ی نکته‌ها (فایل + پنل) بدونِ فیلترِ انتشار — برای پنل مدیریت. */
function tips_all(): array { return ha_content_admin('tips', 'id'); }
function tips(): array { return ha_visible(tips_all()); }
function exercises_by_level(): array { $grouped=[]; foreach(exercises() as $ex){ $level=(string)($ex['level']??'عمومی'); $grouped[$level][]=$ex; } return $grouped; }

/**
 * تمرین‌های متصل به یک درس مشخص (با نامکِ درس).
 * اگر تمرینی فقط به دوره متصل شده باشد اینجا نمی‌آید — آن‌ها را
 * exercises_for_course() برمی‌گرداند.
 */
function exercises_for_lesson(string $lessonSlug): array
{
    $lessonSlug = slugify($lessonSlug);
    if ($lessonSlug === '') return [];
    return array_values(array_filter(exercises(), static function ($ex) use ($lessonSlug) {
        return slugify((string) ($ex['lesson'] ?? '')) === $lessonSlug;
    }));
}

/** تمرین‌های متصل به یک دوره (مستقیم یا از طریقِ درس‌های همان دوره). */
function exercises_for_course(string $courseSlug): array
{
    $courseSlug = slugify($courseSlug);
    if ($courseSlug === '') return [];
    $lessonSlugs = [];
    $course = find_course($courseSlug);
    if ($course !== null) {
        foreach ((array) ($course['stages'] ?? []) as $stage) {
            foreach ((array) ($stage['lessons'] ?? []) as $lesson) {
                $ls = slugify((string) ($lesson['slug'] ?? ''));
                if ($ls !== '') $lessonSlugs[$ls] = true;
            }
        }
    }
    return array_values(array_filter(exercises(), static function ($ex) use ($courseSlug, $lessonSlugs) {
        if (slugify((string) ($ex['course'] ?? '')) === $courseSlug) return true;
        $ls = slugify((string) ($ex['lesson'] ?? ''));
        return $ls !== '' && isset($lessonSlugs[$ls]);
    }));
}

/**
 * انتخابِ «منتخب» برای صفحه‌ی نخست: اول مواردی که مدیر نشانِ ویژه
 * زده، بعد تازه‌ترین‌ها تا رسیدن به سقف — بدونِ تکرار.
 */
function ha_featured_first(array $items, int $limit): array
{
    $picked = [];
    foreach ($items as $item) {
        if (!empty($item['featured'])) $picked[] = $item;
    }
    foreach ($items as $item) {
        if (count($picked) >= $limit) break;
        $id = slugify((string) ($item['slug'] ?? ($item['id'] ?? '')));
        $dup = false;
        foreach ($picked as $p) {
            if (slugify((string) ($p['slug'] ?? ($p['id'] ?? ''))) === $id) { $dup = true; break; }
        }
        if (!$dup) $picked[] = $item;
    }
    return array_slice($picked, 0, $limit);
}

/* ------------------------------------------------------------------ */
/*  کتاب، پژوهش، مدیا، مسیر                                            */
/* ------------------------------------------------------------------ */

/** همه‌ی کتاب‌ها (فایل + پنل) بدونِ فیلترِ انتشار — برای پنل مدیریت. */
function books_all(): array { return ha_content_admin('books'); }
function books(): array { return ha_visible(books_all()); }
/** همه‌ی پژوهش‌ها (فایل + پنل) بدونِ فیلترِ انتشار — برای پنل مدیریت. */
function research_all(): array { return ha_content_admin('research'); }
function research_items(): array { return ha_visible(research_all()); }
/** همه‌ی رسانه‌ها (فایل + پنل) بدونِ فیلترِ انتشار — برای پنل مدیریت. */
function media_all(): array { return ha_content_admin('media'); }
function media_items(): array { return ha_visible(media_all()); }
function videos(): array { return array_values(array_filter(media_items(), fn($m)=>($m['type']??'')==='video')); }
function audios(): array { return array_values(array_filter(media_items(), fn($m)=>($m['type']??'')==='audio')); }
function learning_paths(): array { return data('learning_paths'); }

function find_book(string $slug): ?array { foreach(books() as $b) if(slugify($b['slug']??'')===slugify($slug)) return $b; return null; }
function find_research(string $slug): ?array { foreach(research_items() as $r) if(slugify($r['slug']??'')===slugify($slug)) return $r; return null; }
function find_media(string $slug): ?array { foreach(media_items() as $m) if(slugify($m['slug']??'')===slugify($slug)) return $m; return null; }

function books_by_category(string $cat): array { return array_values(array_filter(books(), fn($b)=>ha_item_field_slug($b)=== $cat)); }
function research_by_category(string $cat): array { return array_values(array_filter(research_items(), fn($r)=>ha_item_field_slug($r)=== $cat)); }
function media_by_category(string $cat, ?string $type=null): array { return array_values(array_filter(media_items(), function($m) use($cat,$type){ if(ha_item_field_slug($m)!== $cat) return false; if($type && ($m['type']??'')!== $type) return false; return true; })); }

function related_books(array $current, int $limit=3): array {
    $out=[]; foreach(books() as $b){ if(($b['slug']??'')===($current['slug']??'')) continue; if(($b['category']??'')===($current['category']??'')) $out[]=$b; }
    return array_slice($out,0,$limit);
}
function related_research(array $current, int $limit=2): array {
    $out=[]; foreach(research_items() as $r){ if(($r['slug']??'')===($current['slug']??'')) continue; if(($r['category']??'')===($current['category']??'')) $out[]=$r; }
    return array_slice($out,0,$limit);
}
