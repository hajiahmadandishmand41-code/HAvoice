<?php
/**
 * HAvoice — متادیتای سئو
 *
 * هر route یک آرایه‌ی متا می‌گیرد:
 *   title, description, h1, crumb, banner, canonical, robots, image, og_type, jsonld[]
 *
 * نکته‌های مهم:
 *  • jsonld همیشه «فهرستی از گراف‌ها» است تا بتوان چند schema در یک صفحه داد.
 *  • banner=false یعنی خودِ صفحه <h1> می‌سازد؛ این از دو <h1> در یک صفحه
 *    جلوگیری می‌کند (WCAG 1.3.1 و سئو).
 *  • canonical نسبی ساخته می‌شود و در header.php با absolute_url() مطلق می‌شود.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/*
 * نکته: all_articles_sorted() در includes/content.php تعریف شده است.
 * تعریفِ دومِ همان تابع در این فایل باعثِ «Cannot redeclare» و مرگِ کاملِ
 * سایت می‌شد (bootstrap هر دو فایل را require می‌کند). تعریفِ تکراری حذف شد.
 */

/** داده‌ی ساختاریافته‌ی «مسیر صفحه» برای گوگل. */
function ha_breadcrumb_jsonld(array $items): array
{
    $list = [['name' => 'خانه', 'item' => absolute_url(url('home'))]];
    foreach ($items as $it) {
        if (empty($it['label'])) {
            continue;
        }
        $entry = ['name' => (string) $it['label']];
        if (!empty($it['url'])) {
            $entry['item'] = absolute_url((string) $it['url']);
        }
        $list[] = $entry;
    }
    if (count($list) < 2) {
        return [];
    }
    $elements = [];
    foreach ($list as $i => $el) {
        $elements[] = array_merge(['@type' => 'ListItem', 'position' => $i + 1], $el);
    }
    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $elements,
    ];
}

/** داده‌ی ساختاریافته‌ی «سایت + شخص» — فقط در صفحه‌ی اصلی. */
function ha_website_jsonld(): array
{
    $root = absolute_url(url('home'));
    $knows = [];
    foreach (categories() as $c) {
        $knows[] = (string) ($c['title'] ?? '');
    }
    return [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'       => 'WebSite',
                '@id'         => $root . '#website',
                'url'         => $root,
                'name'        => ha_brand_full(),
                'inLanguage'  => 'fa',
                'description' => 'آموزش‌های تمرین‌محورِ ' . ha_site_name() . ' در حوزه‌های فن بیان، ارتباط، روانشناسی، رشد فردی و مذاکره.',
                'publisher'   => ['@id' => $root . '#person'],
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => [
                        '@type'       => 'EntryPoint',
                        'urlTemplate' => absolute_url(url('search')) . '&q={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type'      => 'Person',
                '@id'        => $root . '#person',
                'name'       => ha_site_name(),
                'jobTitle'   => ha_site_tagline(),
                'email'      => 'mailto:' . HA_EMAIL,
                'url'        => $root,
                'knowsAbout' => array_values(array_filter($knows)),
            ],
        ],
    ];
}

/** تصویرِ اشتراک‌گذاری (og:image / twitter:image) — همان بنرِ رسمیِ سایت. */
function ha_og_image(): string
{
    return 'assets/img/fanbayan-banner.webp';
}

function ha_page_meta(string $route): array
{
    $siteName = ha_site_name() . ' | ' . ha_site_tagline();
    $image    = ha_og_image();

    $defaultDescription = 'آموزش‌های تمرین‌محورِ ' . ha_site_name() . ': فن بیان، ارتباط مؤثر، روانشناسیِ کاربردی، رشد فردی، هدف‌گذاری، مذاکره، کتاب و پژوهش — با ویدیو، صوت، مقاله، تمرین و مسیرِ یادگیری.';

    $base = [
        'og_type' => 'website',
        'jsonld'  => [],
        'image'   => $image,
        'banner'  => true,
        'robots'  => 'index,follow',
        'crumb'   => '',
    ];

    /* ------------------------------ خانه ------------------------------ */
    if ($route === 'home') {
        // برای صفحه‌ی اصلی، ریشه‌ی دامنه canonical است نه index.php?p=home
        return array_merge($base, [
            'title'       => ha_brand_full() . ' — مرکز آموزش مهارت‌های کاربردی',
            'description' => $defaultDescription,
            'h1'          => ha_site_name(),
            'banner'      => false,
            'canonical'   => '/',
            'jsonld'      => [ha_website_jsonld()],
        ]);
    }

    /* ------------------------------ ۴۰۴ ------------------------------- */
    if ($route === '404') {
        return array_merge($base, [
            'title'       => 'صفحه پیدا نشد — ' . $siteName,
            'description' => 'نشانی‌ای که دنبال آن بودید وجود ندارد. از حوزه‌ها یا جستجو ادامه دهید.',
            'h1'          => 'صفحه پیدا نشد',
            // صفحه‌ی ۴۰۴ خودش <h1> دارد ⇒ بنر خاموش تا دو <h1> نسازیم
            'banner'      => false,
            'canonical'   => '',
            'robots'      => 'noindex,follow',
        ]);
    }

    /* ----------------------------- مقاله ------------------------------ */
    if ($route === 'article') {
        $found = find_by_slug(all_articles_sorted(), (string) $GLOBALS['HA_SLUG']);
        if ($found[1] !== null) {
            $a = $found[1];
            return array_merge($base, [
                'title'       => $a['title'] . ' | ' . $siteName,
                'description' => (string) ($a['excerpt'] ?? ''),
                'h1'          => (string) $a['title'],
                'crumb'       => 'مقالات',
                'banner'      => false,
                'canonical'   => url('article', ['slug' => $a['slug']]),
                'og_type'     => 'article',
                'jsonld'      => array_values(array_filter([
                    [
                        '@context'         => 'https://schema.org',
                        '@type'            => 'Article',
                        'headline'         => (string) $a['title'],
                        'description'      => (string) ($a['excerpt'] ?? ''),
                        'datePublished'    => (string) ($a['date'] ?? ''),
                        'dateModified'     => (string) ($a['date'] ?? ''),
                        'inLanguage'       => 'fa',
                        'author'           => ['@type' => 'Person', 'name' => ha_site_name()],
                        'publisher'        => ['@type' => 'Person', 'name' => ha_site_name()],
                        'mainEntityOfPage' => absolute_url(url('article', ['slug' => $a['slug']])),
                        'image'            => absolute_url(asset($image)),
                        'articleSection'   => (string) ($a['category'] ?? ''),
                        'keywords'         => implode(', ', (array) ($a['tags'] ?? [])),
                    ],
                    ha_breadcrumb_jsonld([
                        ['label' => 'مقالات', 'url' => url('articles')],
                        ['label' => $a['title']],
                    ]),
                ])),
            ]);
        }
    }

    /* ------------------------------ درس ------------------------------- */
    if ($route === 'lesson') {
        $lesson = course_find_lesson((string) $GLOBALS['HA_SLUG']);
        if ($lesson !== null) {
            $courseTitle = (string) ($lesson['course']['title'] ?? ($lesson['stage']['title'] ?? 'دوره'));
            $courseSlug  = (string) ($lesson['course']['slug'] ?? '');
            return array_merge($base, [
                'title'       => $lesson['lesson']['title'] . ' — ' . $courseTitle . ' | ' . ha_site_name(),
                'description' => (string) ($lesson['lesson']['goal'] ?? ''),
                'h1'          => (string) $lesson['lesson']['title'],
                'crumb'       => $courseTitle,
                'banner'      => false,
                'canonical'   => url('lesson', ['slug' => $lesson['lesson']['slug']]),
                'og_type'     => 'article',
                'jsonld'      => array_values(array_filter([
                    [
                        '@context'             => 'https://schema.org',
                        '@type'                => 'LearningResource',
                        'name'                 => (string) $lesson['lesson']['title'],
                        'description'          => (string) ($lesson['lesson']['goal'] ?? ''),
                        'inLanguage'           => 'fa',
                        'learningResourceType' => 'Lesson',
                        'educationalLevel'     => (string) ($lesson['stage']['title'] ?? ''),
                        'timeRequired'         => 'PT' . max(1, (int) ($lesson['lesson']['minutes'] ?? 10)) . 'M',
                        'isPartOf'             => ['@type' => 'Course', 'name' => $courseTitle],
                        'provider'             => ['@type' => 'Person', 'name' => ha_site_name()],
                    ],
                    ha_breadcrumb_jsonld([
                        ['label' => 'دوره‌ها', 'url' => url('courses')],
                        ['label' => $courseTitle, 'url' => $courseSlug !== '' ? url('course', ['slug' => $courseSlug]) : ''],
                        ['label' => $lesson['lesson']['title']],
                    ]),
                ])),
            ]);
        }
    }

    /* ----------------------------- دوره ------------------------------- */
    if ($route === 'course') {
        $slug = (string) ($GLOBALS['HA_SLUG'] ?? param('slug'));
        $c = $slug ? find_course($slug) : null;
        if ($c) {
            $lessonCount = 0;
            $minutes = 0;
            foreach ((array) ($c['stages'] ?? []) as $st) {
                foreach ((array) ($st['lessons'] ?? []) as $l) {
                    $lessonCount++;
                    $minutes += (int) ($l['minutes'] ?? 0);
                }
            }
            return array_merge($base, [
                'title'       => $c['title'] . ' | ' . $siteName,
                'description' => (string) ($c['excerpt'] ?? ''),
                'h1'          => (string) $c['title'],
                'crumb'       => 'دوره‌ها',
                'canonical'   => url('course', ['slug' => $c['slug']]),
                'jsonld'      => array_values(array_filter([
                    [
                        '@context'         => 'https://schema.org',
                        '@type'            => 'Course',
                        'name'             => (string) $c['title'],
                        'description'      => (string) ($c['excerpt'] ?? ''),
                        'inLanguage'       => 'fa',
                        'numberOfCredits'  => $lessonCount,
                        'timeRequired'     => 'PT' . max(1, $minutes) . 'M',
                        'educationalLevel' => (string) ($c['level'] ?? ''),
                        'provider'         => ['@type' => 'Person', 'name' => ha_site_name()],
                        'hasCourseInstance' => [[
                            '@type'          => 'CourseInstance',
                            'courseMode'     => 'online',
                            'courseWorkload' => 'PT' . max(1, (int) round($minutes / max(1, $lessonCount))) . 'M',
                        ]],
                    ],
                    ha_breadcrumb_jsonld([
                        ['label' => 'دوره‌ها', 'url' => url('courses')],
                        ['label' => $c['title']],
                    ]),
                ])),
            ]);
        }
    }

    /* ----------------------------- حوزه ------------------------------- */
    if ($route === 'category') {
        $slug = slugify((string) ($GLOBALS['HA_SLUG'] ?? param('slug')));
        $cat = $slug ? find_category($slug) : null;
        if ($cat) {
            return array_merge($base, [
                'title'       => $cat['title'] . ' | ' . $siteName,
                'description' => (string) ($cat['description'] ?? ''),
                'h1'          => (string) $cat['title'],
                // صفحه‌ی حوزه خودش <h1> و breadcrumb دارد ⇒ بنر خاموش
                'banner'      => false,
                'canonical'   => url('category', ['slug' => $cat['slug']]),
                'jsonld'      => [[
                    '@context'    => 'https://schema.org',
                    '@type'       => 'CollectionPage',
                    'name'        => (string) $cat['title'],
                    'description' => (string) ($cat['description'] ?? ''),
                    'inLanguage'  => 'fa',
                ]],
            ]);
        }
    }

    /* ------------------------------ کتاب ------------------------------ */
    if ($route === 'books' && param('slug') !== '') {
        $b = find_book(param('slug'));
        if ($b) {
            return array_merge($base, [
                'title'       => $b['title'] . ' — ' . $b['author'] . ' | ' . $siteName,
                'description' => (string) ($b['excerpt'] ?? ''),
                'h1'          => (string) $b['title'],
                'crumb'       => 'کتاب‌ها',
                'banner'      => false,
                'canonical'   => url('books', ['slug' => $b['slug']]),
                'og_type'     => 'book',
                'jsonld'      => [[
                    '@context'    => 'https://schema.org',
                    '@type'       => 'Book',
                    'name'        => (string) $b['title'],
                    'author'      => ['@type' => 'Person', 'name' => (string) ($b['author'] ?? '')],
                    'inLanguage'  => 'fa',
                    'description' => (string) ($b['excerpt'] ?? ''),
                    'review'      => [
                        '@type'      => 'Review',
                        'author'     => ['@type' => 'Person', 'name' => ha_site_name()],
                        'reviewBody' => (string) ($b['summary'] ?? ($b['excerpt'] ?? '')),
                    ],
                ]],
            ]);
        }
    }

    /* ----------------------------- پژوهش ------------------------------ */
    if ($route === 'research' && param('slug') !== '') {
        $r = find_research(param('slug'));
        if ($r) {
            return array_merge($base, [
                'title'       => $r['title'] . ' | ' . $siteName,
                'description' => (string) ($r['summary'] ?? ''),
                'h1'          => (string) $r['title'],
                'crumb'       => 'پژوهش',
                'banner'      => false,
                'canonical'   => url('research', ['slug' => $r['slug']]),
                'og_type'     => 'article',
                'jsonld'      => [[
                    '@context'      => 'https://schema.org',
                    '@type'         => 'ScholarlyArticle',
                    'headline'      => (string) $r['title'],
                    'description'   => (string) ($r['summary'] ?? ''),
                    'inLanguage'    => 'fa',
                    'datePublished' => (string) ($r['date'] ?? ''),
                    'author'        => ['@type' => 'Person', 'name' => ha_site_name()],
                ]],
            ]);
        }
    }

    /* --------------------------- فهرست‌ها ------------------------------ */
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
        'tips'      => 'نکات کوتاه و کاربردی',
        'about'     => 'درباره‌ی ما',
        'contact'   => 'تماس با ما',
        'comments'  => 'نظرات عمومی کاربران',
        'search'    => 'جستجو در همه‌ی محتوا',
        'login'     => 'ورود به حساب کاربری',
        'register'  => 'ساخت حساب کاربری',
        'account'   => 'حساب کاربری',
        'logout'    => 'خروج از حساب',
    ];
    $descriptions = [
        'courses'   => 'دوره‌های مرحله‌ای و تمرین‌محور در حوزه‌های فن بیان، ارتباط، روانشناسی، رشد فردی، زمان و مذاکره.',
        'course'    => 'مسیرِ مرحله‌ای هر دوره با درس، تمرین و پیشرفتِ قابلِ اندازه‌گیری.',
        'articles'  => 'مقالاتِ کاربردیِ ' . ha_site_name() . ' با تمرینِ مشخص در پایانِ هر متن.',
        'videos'    => 'ویدیوهای کوتاه و تمرین‌محور؛ هر ویدیو یک مهارتِ قابلِ اجرا.',
        'audios'    => 'پادکست‌های کوتاه برای یادگیری در مسیر؛ مرورِ روزانه بدونِ نیاز به نگاه.',
        'books'     => 'خلاصه و برداشتِ کاربردی از کتاب‌های شاخصِ هر حوزه.',
        'research'  => 'یادداشت‌های پژوهشی با ذکرِ منبع و قابلیتِ راستی‌آزمایی.',
        'category'  => 'همه‌ی حوزه‌های آموزشیِ HAvoice در یک نگاه.',
        'exercises' => 'تمرین‌های روزانه با تایمر، چک‌لیست و تولیدگرِ موضوعِ بداهه.',
        'tips'      => 'نکته‌های کوتاه برای استفاده‌ی فوری در جلسه و گفت‌وگو.',
        'about'     => 'معرفیِ ' . ha_site_name() . '، رویکردِ آموزشی، حوزه‌ها و سؤالاتِ متداول.',
        'contact'   => 'پرسش، پیشنهاد و همکاری؛ پاسخ‌گویی تا دو روزِ کاری.',
        'comments'  => 'نظرهای واقعیِ کاربران درباره‌ی درس‌ها، تمرین‌ها و محتوای HAvoice؛ ثبتِ نظر پس از بازبینیِ مدیر منتشر می‌شود.',
        'search'    => 'جستجو در تمامِ محتوا: مقاله، درس، کتاب، پژوهش، ویدیو و صوت.',
        'login'     => 'ورود به حساب کاربری HAvoice برای پیگیریِ مسیر یادگیری.',
        'register'  => 'ساخت حساب رایگان در HAvoice و دنبال کردنِ پیشرفتِ درس‌ها و تمرین‌ها.',
        'account'   => 'داشبورد کاربری HAvoice: مشخصات، پیشرفت و دسترسی سریع به یادگیری.',
        'logout'    => 'خروج امن از حساب کاربری HAvoice.',
    ];
    $shortTitles = [
        'courses'   => 'دوره‌ها',
        'course'    => 'دوره',
        'articles'  => 'مقالات',
        'videos'    => 'ویدیو',
        'audios'    => 'پادکست',
        'books'     => 'کتاب‌ها',
        'research'  => 'پژوهش',
        'category'  => 'حوزه‌ها',
        'exercises' => 'تمرین‌ها',
        'tips'      => 'نکته‌ها',
        'about'     => 'درباره‌ی ما',
        'contact'   => 'تماس با ما',
        'comments'  => 'نظرات',
        'search'    => 'جستجو',
        'login'     => 'ورود',
        'register'  => 'ثبت‌نام',
        'account'   => 'حساب کاربری',
        'logout'    => 'خروج',
    ];
    // صفحه‌هایی که <h1> خودشان را می‌سازند ⇒ بنر (و <h1> دوم) خاموش
    $bannerless = ['contact', 'about', 'login', 'register', 'account', 'logout', 'comments'];

    // Admin routes — noindex, no banner
    if (strpos($route, 'admin') === 0) {
        $adminTitles = [
            'admin' => 'داشبورد مدیریت', 'admin_courses' => 'مدیریت دوره‌ها', 'admin_articles' => 'مدیریت مقالات',
            'admin_videos' => 'مدیریت ویدیوها', 'admin_audios' => 'مدیریت صوتها', 'admin_books' => 'مدیریت کتاب‌ها',
            'admin_research' => 'مدیریت پژوهش‌ها', 'admin_exercises' => 'مدیریت تمرین‌ها', 'admin_tips' => 'مدیریت نکته‌ها',
            'admin_categories' => 'مدیریت حوزه‌ها', 'admin_users' => 'مدیریت کاربران', 'admin_messages' => 'پیام‌های تماس',
            'admin_settings' => 'تنظیمات سایت', 'admin_message_view' => 'مشاهده پیام', 'admin_comments' => 'مدیریت نظرات',
        ];
        $editTitles = [
            'admin_course_edit' => 'ویرایش دوره', 'admin_article_edit' => 'ویرایش مقاله',
            'admin_video_edit' => 'ویرایش ویدیو', 'admin_audio_edit' => 'ویرایش صوت',
            'admin_book_edit' => 'ویرایش کتاب', 'admin_research_edit' => 'ویرایش پژوهش',
            'admin_exercise_edit' => 'ویرایش تمرین', 'admin_tip_edit' => 'ویرایش نکته',
            'admin_category_edit' => 'ویرایش حوزه', 'admin_user_edit' => 'ویرایش کاربر',
        ];
        $title = ($adminTitles[$route] ?? $editTitles[$route] ?? 'مدیریت') . ' — پنل مدیریت';
        return array_merge($base, [
            'title'       => $title . ' | ' . $siteName,
            'description' => 'پنل مدیریت سایت HAvoice',
            'h1'          => $adminTitles[$route] ?? $editTitles[$route] ?? 'مدیریت',
            'banner'      => false,
            'canonical'   => '',
            'robots'      => 'noindex,nofollow',
        ]);
    }

    return array_merge($base, [
        'title'       => (isset($titles[$route]) ? $titles[$route] : ha_site_name()) . ' | ' . $siteName,
        'description' => $descriptions[$route] ?? $defaultDescription,
        'h1'          => $shortTitles[$route] ?? ha_site_name(),
        'banner'      => !in_array($route, $bannerless, true),
        'canonical'   => url($route),
        'robots'      => in_array($route, ['search', 'login', 'register', 'account', 'logout'], true) ? 'noindex,follow' : 'index,follow',
    ]);
}
