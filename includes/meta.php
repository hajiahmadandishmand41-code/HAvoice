<?php
/**
 * HAvoice — عنوان و توضیح متای هر صفحه (سئو + اشتراک‌گذاری).
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/** فهرست مقالات با فیلترهای سبک، برای استفاده‌ی مشترک چند صفحه. */
function all_articles_sorted(): array
{
    $articles = data('articles');

    usort($articles, static function (array $a, array $b) {
        return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
    });

    return $articles;
}

function ha_page_meta(string $route): array
{
    $siteName = HA_NAME . ' | ' . HA_LEGAL;
    $default  = [
        'title'       => HA_NAME . ' — فن بیان، سخنوری و مهارت‌های ارتباطی',
        'description' => 'یادگیری مرحله‌به‌مرحله‌ی فن بیان: مسیر آموزشی، مقالات تخصصی، تمرین‌های روزانه و نکات کاربردی برای صحبت مؤثر در جلسه، کلاس و جمع.',
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

    /* --- صفحه‌ی مقاله --- */
    if ($route === 'article') {
        $found = find_by_slug(all_articles_sorted(), (string) $GLOBALS['HA_SLUG']);
        if ($found[1] !== null) {
            $a = $found[1];

            return [
                'title'       => $a['title'] . ' | ' . $siteName,
                'description' => (string) ($a['excerpt'] ?? ''),
                'h1'          => (string) $a['title'],
                'crumb'       => 'مقاله',
                'banner'      => false,
                'canonical'   => url('article', ['slug' => $a['slug']]),
                'robots'      => 'index,follow',
                'image'       => $default['image'],
                'jsonld'      => [
                    '@context'      => 'https://schema.org',
                    '@type'         => 'Article',
                    'headline'      => $a['title'],
                    'description'   => $a['excerpt'] ?? '',
                    'datePublished' => $a['date'] ?? '',
                    'inLanguage'    => 'fa',
                    'author'        => ['@type' => 'Organization', 'name' => HA_NAME],
                    'publisher'     => ['@type' => 'Organization', 'name' => HA_NAME],
                ],
            ];
        }
    }

    /* --- صفحه‌ی درس --- */
    if ($route === 'lesson') {
        $lesson = course_find_lesson((string) $GLOBALS['HA_SLUG']);
        if ($lesson !== null) {
            return [
                'title'       => $lesson['lesson']['title'] . ' | ' . HA_NAME,
                'description' => (string) ($lesson['lesson']['goal'] ?? ''),
                'h1'          => (string) $lesson['lesson']['title'],
                'crumb'       => (string) ($lesson['stage']['title'] ?? 'دوره'),
                'banner'      => false,
                'canonical'   => url('lesson', ['slug' => $lesson['lesson']['slug']]),
                'robots'      => 'index,follow',
                'image'       => $default['image'],
                'jsonld'      => [
                    '@context'   => 'https://schema.org',
                    '@type'      => 'LearningResource',
                    'name'       => $lesson['lesson']['title'],
                    'learners'   => 'مخاطبان عمومی',
                    'inLanguage' => 'fa',
                    'educationalLevel' => $lesson['stage']['title'] ?? '',
                    'provider'   => ['@type' => 'Organization', 'name' => HA_NAME],
                ],
            ];
        }
    }

    $titles = [
        'course'    => 'مسیر آموزشی فن بیان (گام‌به‌گام)',
        'articles'  => 'مقالات آموزشی فن بیان و سخنوری',
        'exercises' => 'تمرین‌های عملی فن بیان',
        'tips'      => 'نکات کوتاه و کاربردی سخنوری',
        'about'     => 'درباره های‌ویس',
        'contact'   => 'تماس با های‌ویس',
        'search'    => 'جستجو در مقالات',
    ];

    $descriptions = [
                'course'    => fa_num(count(course_lesson_index())) . ' درس کاربردی در ' . fa_num(count(course_stages())) . ' مرحله: تنفس و صدا، ساختار کلام و اجرای زنده — با تمرین و پیگیری پیشرفت.',
        'articles'  => 'مقالات تخصصی و کاربردی درباره فن بیان، زبان بدن، غلبه بر استرس و هنر مذاکره.',
        'exercises' => 'تمرین‌های روزانه‌ی فن بیان با تایمر، تولیدگر موضوع و چک‌لیست — از گرم کردن صدا تا بداهه‌گویی.',
        'tips'      => 'نکته‌های کوتاه و فوری برای بهتر صحبت کردن؛ قابل استفاده در جلسه، کلاس و تماس تلفنی.',
        'about'     => 'های‌ویس یک پروژه‌ی آموزشی مستقل در حوزه فن بیان و مهارت‌های ارتباطی است.',
        'contact'   => 'با تیم های‌ویس در تماس باشید: همکاری، پرسش و پیشنهاد.',
        'search'    => 'جستجو در میان تمام مقالات و درس‌های های‌ویس.',
    ];

    $shortTitles = [
        'course' => 'مسیر آموزشی فن بیان', 'articles' => 'مقالات آموزشی',
        'exercises' => 'تمرین‌های عملی', 'tips' => 'نکته‌های کوتاه',
        'about' => 'درباره های‌ویس', 'contact' => 'تماس با ما', 'search' => 'جستجو',
    ];
    $bannerless = ['contact'];

    return [
        'title'       => (isset($titles[$route]) ? $titles[$route] : HA_NAME) . ' | ' . $siteName,
        'description' => $descriptions[$route] ?? $default['description'],
        'h1'          => $shortTitles[$route] ?? HA_NAME,
        'banner'      => !in_array($route, $bannerless, true),
        'canonical'   => url($route),
        'robots'      => $route === 'search' ? 'noindex,follow' : 'index,follow',
        'image'       => $default['image'],
    ];
}
