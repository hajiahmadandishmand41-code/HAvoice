<?php
/**
 * HAvoice 2.0 — مسیرهای یادگیری (ترکیبی از چند حوزه)
 * هر مسیر: slug, title, excerpt, categories[], steps [{type, slug, note}]
 */

return [
    [
        'slug'       => 'path-confident-presence',
        'title'      => 'مسیرِ حضورِ مطمئن در جلسه',
        'excerpt'    => 'از تنفس تا پاسخِ منظم؛ برای کسی که می‌خواهد در جلسات، کوتاه، روشن و مطمئن صحبت کند.',
        'categories' => ['public-speaking', 'communication', 'negotiation'],
        'steps'      => [
            ['type' => 'lesson', 'slug' => 'breathing-foundations', 'note' => 'پایه‌ی صدا'],
            ['type' => 'article', 'slug' => 'power-of-pause', 'note' => 'مکثِ هدفمند'],
            ['type' => 'exercise', 'slug' => 'pause-marks', 'note' => 'تمرینِ مکث'],
            ['type' => 'lesson', 'slug' => 'hard-questions', 'note' => 'پاسخ به سؤال سخت'],
        ],
        'minutes'    => 65,
    ],
    [
        'slug'       => 'path-deep-communication',
        'title'      => 'مسیرِ ارتباطِ عمیق',
        'excerpt'    => 'شنیدنِ دقیق، گفتنِ روشن؛ برای بهبودِ کیفیتِ گفت‌وگوهای روزمره و کاری.',
        'categories' => ['communication', 'psychology', 'life-skills'],
        'steps'      => [
            ['type' => 'article', 'slug' => 'listening-is-half-of-speaking', 'note' => 'گوش دادن'],
            ['type' => 'article', 'slug' => 'eye-contact-guide', 'note' => 'حضورِ دیداری'],
            ['type' => 'book', 'slug' => 'nonviolent-communication', 'note' => 'خلاصه کتاب'],
        ],
        'minutes'    => 48,
    ],
    [
        'slug'       => 'path-focus-habit',
        'title'      => 'مسیرِ تمرکز و عادتِ پایدار',
        'excerpt'    => 'از هدف‌گذاریِ If-Then تا کارِ عمیق؛ برای ساختنِ یک عادتِ کوچکِ روزانه.',
        'categories' => ['goals-time', 'success', 'life-skills'],
        'steps'      => [
            ['type' => 'book', 'slug' => 'atomic-habits', 'note' => 'عادت‌های اتمی'],
            ['type' => 'book', 'slug' => 'deep-work', 'note' => 'کار عمیق'],
            ['type' => 'exercise', 'slug' => 'three-point-summary', 'note' => 'تمرین خلاصه‌سازی'],
        ],
        'minutes'    => 52,
    ],
];
