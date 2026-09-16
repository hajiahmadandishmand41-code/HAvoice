<?php
/**
 * HAvoice — ثبتِ وضعیتِ یادگیری (درس / تمرین)
 *
 * الگوی PRG مثلِ بقیه‌ی handlerها:
 *   فقط POST ← CSRF ← اعتبارسنجیِ قلم ← نوشتنِ وضعیت ← redirect
 *
 * پارامترها:
 *   type  = lesson | exercise
 *   key   = نامکِ درس یا شناسه‌ی تمرین
 *   state = done | started | none  (none ⇒ برگشت به «انجام‌نشده»)
 *   next  = نشانیِ بازگشت (فقط نسبیِ داخلی)
 *   goto  = next-lesson | course | exercises  (پس از «تکمیل شد» کاربر را
 *           مستقیم به قدمِ بعدی می‌برد — همان چیزی که مسیرِ یادگیری
 *           را برای کاربر بدونِ فکر کردن روشن نگه می‌دارد)
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$next = ha_safe_next((string) ($_POST['next'] ?? ''));
if ($next === '') {
    $next = url('courses');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect($next);
}

if (!csrf_verify()) {
    flash('error', 'نشست شما تمام شده است. لطفاً دوباره تلاش کنید.');
    redirect($next);
}

$type  = (string) ($_POST['type'] ?? 'lesson');
$key   = slugify((string) ($_POST['key'] ?? ''));
$state = (string) ($_POST['state'] ?? '');
$goto  = (string) ($_POST['goto'] ?? '');

/* پاک‌کردنِ پیشرفتِ یک دوره (درس‌ها + تمرین‌ها) — جدا از ثبتِ تک‌قلم */
$resetCourse = slugify((string) ($_POST['reset_course'] ?? ''));
if ($resetCourse !== '' && $key === '') {
    $course = find_course($resetCourse);
    if ($course === null) {
        flash('error', 'دوره پیدا نشد.');
        redirect($next);
    }
    $n = progress_reset_course($course);
    flash('success', $n > 0 ? 'پیشرفتِ این دوره پاک شد.' : 'پیشرفتی برای پاک‌کردن نبود (یا ذخیره‌سازی در دسترس نیست).');
    redirect($next);
}

if (!in_array($type, ['lesson', 'exercise'], true) || $key === '') {
    flash('error', 'قلمِ درخواستی معتبر نیست.');
    redirect($next);
}

if (!in_array($state, ['done', 'started', 'none'], true)) {
    $state = 'done';
}

/* فقط قلم‌های واقعیِ سایت پذیرفته می‌شوند (نه هر رشته‌ی دلخواه) */
if ($type === 'lesson') {
    $info = course_find_lesson($key);
    if ($info === null) {
        flash('error', 'درس پیدا نشد.');
        redirect($next);
    }
    $courseSlug = slugify((string) ($info['course']['slug'] ?? ''));
} else {
    $found = null;
    foreach (exercises_all() as $ex) {
        if (slugify((string) ($ex['id'] ?? '')) === $key) {
            $found = $ex;
            break;
        }
    }
    if ($found === null) {
        flash('error', 'تمرین پیدا نشد.');
        redirect($next);
    }
    $courseSlug = slugify((string) ($found['course'] ?? ''));
    if ($courseSlug === '') {
        $lessonSlug = slugify((string) ($found['lesson'] ?? ''));
        if ($lessonSlug !== '') {
            $li = course_find_lesson($lessonSlug);
            $courseSlug = $li !== null ? slugify((string) ($li['course']['slug'] ?? '')) : '';
        }
    }
}

$ok = progress_set($type, $key, $state === 'none' ? '' : $state);

if (!$ok) {
    flash('error', 'ثبتِ پیشرفت ناموفق بود (دیتابیس یا storage قابلِ نوشتن نیست).');
    redirect($next);
}

/* پیامِ کوتاهِ تأیید — فقط وقتی کاربر خودش دکمه را زده است */
if ($state === 'done') {
    flash('success', $type === 'lesson' ? 'درس تکمیل‌شده ثبت شد.' : 'تمرین انجام‌شده ثبت شد.');
} elseif ($state === 'none') {
    flash('success', 'علامت برداشته شد؛ این قلم دوباره «انجام‌نشده» است.');
}

/* ------------------------------------------------------------------ */
/*  قدمِ بعدی                                                          */
/*                                                                    */
/*  بعد از «تکمیل شد»، کاربر باید بی‌درنگ به قدمِ بعدی برود — بدونِ اینکه */
/*  خودش دنبالِ درسِ بعدی بگردد. ملاکِ «بعدی»:                          */
/*    ۱. درس/تمرینِ بعد از همین قلم در ترتیبِ دوره                      */
/*    ۲. اگر این آخرین بود، اولین قلمِ ناتمامِ دوره                     */
/*    ۳. اگر همه تمام شده بودند، صفحه‌ی دوره (با پیامِ پایان)            */
/* ------------------------------------------------------------------ */
if ($goto !== '' && $courseSlug !== '') {
    $course = find_course($courseSlug);
    if ($course !== null) {
        $path = course_learning_path($course);

        if ($goto === 'next-lesson') {
            $lessons = (array) $path['lessons'];
            $target  = null;
            foreach ($lessons as $i => $l) {
                if ((string) ($l['slug'] ?? '') === $key) {
                    $target = $lessons[$i + 1] ?? null;
                    break;
                }
            }
            if ($target === null) {
                /* آخرین درس بود: اولین درسِ ناتمام (اگر مانده باشد) */
                foreach ($lessons as $l) {
                    if (($l['state'] ?? '') !== 'done' && (string) ($l['slug'] ?? '') !== $key) {
                        $target = $l;
                        break;
                    }
                }
            }
            if ($target !== null && (string) ($target['slug'] ?? '') !== $key) {
                redirect((string) $target['url']);
            }
            /* همه‌ی درس‌ها تمام شده‌اند → تمرینِ بعدی، وگرنه صفحه‌ی دوره */
            $ex = $path['exercise_current'] ?? null;
            if ($ex !== null && ($ex['state'] ?? '') !== 'done') {
                redirect((string) $ex['url']);
            }
            redirect(url('course', ['slug' => $courseSlug]));
        }

        if ($goto === 'next-exercise') {
            $exercises = (array) $path['exercises'];
            $target    = null;
            foreach ($exercises as $i => $x) {
                if ((string) ($x['id'] ?? '') === $key) {
                    $target = $exercises[$i + 1] ?? null;
                    break;
                }
            }
            if ($target === null) {
                foreach ($exercises as $x) {
                    if (($x['state'] ?? '') !== 'done' && (string) ($x['id'] ?? '') !== $key) {
                        $target = $x;
                        break;
                    }
                }
            }
            if ($target !== null && (string) ($target['id'] ?? '') !== $key) {
                redirect((string) $target['url']);
            }
            redirect(url('exercises', ['course' => $courseSlug]));
        }

        if ($goto === 'course') {
            redirect(url('course', ['slug' => $courseSlug]));
        }
        if ($goto === 'exercises') {
            redirect(url('exercises', ['course' => $courseSlug]));
        }
    }
}

redirect($next);
