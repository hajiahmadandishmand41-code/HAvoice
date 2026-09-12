<?php
/**
 * HAvoice 2.0 — متای سئو
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

function all_articles_sorted(): array
{
    $articles = data('articles');
    usort($articles, static function(array $a,array $b){ return strcmp((string)($b['date']??''),(string)($a['date']??'')); });
    return $articles;
}

function ha_page_meta(string $route): array
{
    $siteName = HA_NAME . ' | ' . HA_TAGLINE;
    $default = [
        'title'       => HA_BRAND_FULL . ' — مرکز آموزش مهارت‌های کاربردی',
        'description' => 'آموزش‌های تمرین‌محورِ حاجی احمد صالحی: فن بیان، ارتباط مؤثر، روانشناسیِ کاربردی، رشد فردی، هدف‌گذاری، مذاکره، کتاب و پژوهش — با ویدیو، صوت، مقاله، تمرین و مسیرِ یادگیری.',
        'image'       => 'assets/img/og-cover.svg',
    ];

    if ($route === 'home') {
        return [
            'title'       => $default['title'],
            'description' => $default['description'],
            'h1'          => HA_NAME,
            'banner'      => false,
            'canonical'   => url('home'),
            'robots'      => 'index,follow',
            'image'       => $default['image'],
        ];
    }
    if ($route === '404') {
        return [
            'title'       => 'صفحه پیدا نشد — ' . $siteName,
            'description' => 'نشانی‌ای که دنبال آن بودید وجود ندارد.',
            'h1'          => 'صفحه پیدا نشد',
            'banner'      => true,
            'canonical'   => '',
            'robots'      => 'noindex,follow',
            'image'       => $default['image'],
        ];
    }

    if ($route === 'article') {
        $found = find_by_slug(all_articles_sorted(), (string)$GLOBALS['HA_SLUG']);
        if ($found[1]!==null){
            $a=$found[1];
            return [
                'title'       => $a['title'].' | '.$siteName,
                'description' => (string)($a['excerpt']??''),
                'h1'          => (string)$a['title'],
                'crumb'       => 'مقاله',
                'banner'      => false,
                'canonical'   => url('article',['slug'=>$a['slug']]),
                'robots'      => 'index,follow',
                'image'       => $default['image'],
                'jsonld'      => [
                    '@context'=>'https://schema.org','@type'=>'Article',
                    'headline'=>$a['title'],'description'=>$a['excerpt']??'','datePublished'=>$a['date']??'','inLanguage'=>'fa',
                    'author'=>['@type'=>'Person','name'=>HA_NAME],'publisher'=>['@type'=>'Organization','name'=>HA_NAME],
                ],
            ];
        }
    }

    if ($route === 'lesson') {
        $lesson = course_find_lesson((string)$GLOBALS['HA_SLUG']);
        if ($lesson!==null){
            return [
                'title'       => $lesson['lesson']['title'].' | '.HA_NAME,
                'description' => (string)($lesson['lesson']['goal']??''),
                'h1'          => (string)$lesson['lesson']['title'],
                'crumb'       => (string)($lesson['stage']['title']??'دوره'),
                'banner'      => false,
                'canonical'   => url('lesson',['slug'=>$lesson['lesson']['slug']]),
                'robots'      => 'index,follow',
                'image'       => $default['image'],
                'jsonld'      => [
                    '@context'=>'https://schema.org','@type'=>'LearningResource',
                    'name'=>$lesson['lesson']['title'],'inLanguage'=>'fa',
                    'educationalLevel'=>$lesson['stage']['title']??'','provider'=>['@type'=>'Person','name'=>HA_NAME],
                ],
            ];
        }
    }

    if ($route === 'course') {
        $slug = (string)($GLOBALS['HA_SLUG'] ?? param('slug'));
        $c = $slug ? find_course($slug) : null;
        if ($c){
            return [
                'title'       => $c['title'].' | '.$siteName,
                'description' => (string)($c['excerpt']??''),
                'h1'          => (string)$c['title'],
                'crumb'       => 'دوره‌ها',
                'banner'      => true,
                'canonical'   => url('course',['slug'=>$c['slug']]),
                'robots'      => 'index,follow',
                'image'       => $default['image'],
            ];
        }
    }

    if ($route === 'category') {
        $slug = slugify((string)($GLOBALS['HA_SLUG'] ?? param('slug')));
        $cat = $slug ? find_category($slug) : null;
        if ($cat){
            return [
                'title'       => $cat['title'].' | '.$siteName,
                'description' => (string)($cat['description']??''),
                'h1'          => (string)$cat['title'],
                'crumb'       => 'حوزه‌ها',
                'banner'      => true,
                'canonical'   => url('category',['slug'=>$cat['slug']]),
                'robots'      => 'index,follow',
                'image'       => $default['image'],
            ];
        }
    }

    if ($route === 'books' && param('slug')!=='') {
        $b = find_book(param('slug'));
        if ($b){
            return [
                'title'       => $b['title'].' — '.$b['author'].' | '.$siteName,
                'description' => (string)($b['excerpt']??''),
                'h1'          => (string)$b['title'],
                'crumb'       => 'کتاب‌ها',
                'banner'      => false,
                'canonical'   => url('books',['slug'=>$b['slug']]),
                'robots'      => 'index,follow',
                'image'       => $default['image'],
                'jsonld'      => ['@context'=>'https://schema.org','@type'=>'Book','name'=>$b['title'],'author'=>$b['author'],'inLanguage'=>'fa'],
            ];
        }
    }

    if ($route === 'research' && param('slug')!=='') {
        $r = find_research(param('slug'));
        if ($r){
            return [
                'title'       => $r['title'].' | '.$siteName,
                'description' => (string)($r['summary']??''),
                'h1'          => (string)$r['title'],
                'crumb'       => 'پژوهش',
                'banner'      => false,
                'canonical'   => url('research',['slug'=>$r['slug']]),
                'robots'      => 'index,follow',
                'image'       => $default['image'],
                'jsonld'      => ['@context'=>'https://schema.org','@type'=>'ScholarlyArticle','headline'=>$r['title'],'description'=>$r['summary']??'','inLanguage'=>'fa','author'=>['@type'=>'Person','name'=>HA_NAME]],
            ];
        }
    }

    $titles = [
        'courses'   => 'دوره‌های آموزشی',
        'course'    => 'دوره‌های آموزشی',
        'articles'  => 'مقالات آموزشی',
        'videos'    => 'ویدیوهای آموزشی',
        'audios'    => 'پادکست و فایل‌های صوتی',
        'books'     => 'کتاب و خلاصه کتاب',
        'research'  => 'تحقیقات و پژوهش',
        'category'  => 'حوزه‌های آموزشی',
        'exercises' => 'تمرین‌های عملی',
        'tips'      => 'نکات کوتاه',
        'about'     => 'درباره حاجی احمد صالحی',
        'contact'   => 'تماس با ما',
        'search'    => 'جستجو',
    ];
    $descriptions = [
        'courses'   => 'دوره‌های مرحله‌ای و تمرین‌محور در حوزه‌های فن بیان، ارتباط، روانشناسی، رشد فردی، زمان و مذاکره.',
        'course'    => 'مسیرِ مرحله‌ای هر دوره با درس، تمرین و پیشرفتِ قابلِ اندازه‌گیری.',
        'articles'  => 'مقالاتِ کاربردیِ حاجی احمد صالحی با تمرینِ مشخص در پایانِ هر متن.',
        'videos'    => 'ویدیوهای کوتاه و تمرین‌محور؛ هر ویدیو یک مهارتِ قابلِ اجرا.',
        'audios'    => 'پادکست‌های کوتاه برای یادگیری در مسیر؛ مرورِ روزانه بدونِ نیاز به نگاه.',
        'books'     => 'خلاصه و برداشتِ کاربردی از کتاب‌های شاخصِ هر حوزه.',
        'research'  => 'یادداشت‌های پژوهشی با ذکرِ منبع و قابلیتِ راستی‌آزمایی.',
        'category'  => 'همه‌ی حوزه‌های آموزشیِ HAvoice در یک نگاه.',
        'exercises' => 'تمرین‌های روزانه با تایمر و شمارنده.',
        'tips'      => 'نکته‌های کوتاه برای استفاده‌ی فوری در جلسه و گفت‌وگو.',
        'about'     => 'معرفیِ حاجی احمد صالحی، رویکردِ آموزشی و مسیرِ یادگیری.',
        'contact'   => 'پرسش، پیشنهاد و همکاری؛ پاسخ‌گویی تا دو روزِ کاری.',
        'search'    => 'جستجو در تمامِ محتوا: مقاله، درس، کتاب، پژوهش، ویدیو و صوت.',
    ];
    $shortTitles = [
        'courses'=>'دوره‌ها','course'=>'دوره','articles'=>'مقالات','videos'=>'ویدیو','audios'=>'پادکست','books'=>'کتاب‌ها','research'=>'پژوهش','category'=>'حوزه‌ها','exercises'=>'تمرین‌ها','tips'=>'نکته‌ها','about'=>'درباره مدرس','contact'=>'تماس با ما','search'=>'جستجو',
    ];
    $bannerless = ['contact'];

    return [
        'title'       => (isset($titles[$route])?$titles[$route]:HA_NAME).' | '.$siteName,
        'description' => $descriptions[$route] ?? $default['description'],
        'h1'          => $shortTitles[$route] ?? HA_NAME,
        'banner'      => !in_array($route,$bannerless,true),
        'canonical'   => url($route),
        'robots'      => $route==='search' ? 'noindex,follow' : 'index,follow',
        'image'       => $default['image'],
    ];
}
