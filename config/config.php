<?php
/**
 * HAvoice — تنظیمات اصلی سایت
 * برند: حاجی احمد صالحی | مدرس و پژوهشگر
 *
 * این فایل تنها جایی است که برای استقرار باید ویرایش شود.
 * هیچ رمز یا credential‌ای اینجا نگه‌داری نمی‌شود.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  PHP compatibility                                                 */
/* ------------------------------------------------------------------ */
/*
 * Installer حداقل PHP 7.4 را قبول می‌کند، در حالی‌که توابع
 * str_starts_with / str_contains / str_ends_with از PHP 8.0 هستند.
 * این polyfillها پیش از بارگذاری بقیه‌ی کد تعریف می‌شوند تا روی
 * PHP 7.4/7.x خطای Fatal Error رخ ندهد و روی PHP 8+ نیز مزاحم نشوند.
 */
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        $length = strlen($needle);
        return $length <= strlen($haystack)
            && substr($haystack, -$length) === $needle;
    }
}

/* ------------------------------------------------------------------ */
/*  پیکربندی محلی (Production) — secrets هرگز در Git                  */
/*                                                                    */
/*  فایل config/config.local.php را از روی مثال بسازید و فقط روی      */
/*  سرور نگه دارید. مقادیر HA_DB_* در آنجا تعریف می‌شوند.            */
/* ------------------------------------------------------------------ */

$haLocal = HA_ROOT . '/config/config.local.php';
if (is_file($haLocal)) {
    require $haLocal;
}

/* ------------------------------------------------------------------ */
/*  مسیر و حالت آدرس‌دهی                                               */
/* ------------------------------------------------------------------ */

/** زیرپوشه‌ی نصب نسبت به ریشه‌ی دامنه. برای نصب در ریشه: '' */
if (!defined('HA_BASE_PATH')) { define('HA_BASE_PATH', ''); }

/** مسیرِ ذخیره‌سازی (پیام‌ها، نشست‌ها، کاربران، محدودیت نرخ). */
if (!defined('HA_STORAGE_PATH')) { define('HA_STORAGE_PATH', ''); }

/** آدرس‌های کوتاه (/courses) به‌جای (?p=courses). نیاز به mod_rewrite دارد. */
if (!defined('HA_PRETTY_URLS')) { define('HA_PRETTY_URLS', false); }

/**
 * نشانی مطلق سایت، بدون اسلش انتهایی.
 * برای canonical، OG:url، sitemap و robots در Production قطعی می‌شود.
 */
if (!defined('HA_SITE_URL')) { define('HA_SITE_URL', 'https://hajivoice.kesug.com'); }

/* ------------------------------------------------------------------ */
/*  اطلاعات هویتی                                                     */
/* ------------------------------------------------------------------ */

if (!defined('HA_NAME')) { define('HA_NAME', 'حاجی احمد صالحی'); }
if (!defined('HA_LEGAL')) { define('HA_LEGAL', 'HAvoice'); }
if (!defined('HA_TAGLINE')) { define('HA_TAGLINE', 'مدرس و پژوهشگر'); }
if (!defined('HA_BRAND_FULL')) { define('HA_BRAND_FULL', 'حاجی احمد صالحی | مدرس و پژوهشگر'); }
if (!defined('HA_EMAIL')) { define('HA_EMAIL', 'hajiahmads299@gmail.com'); }
if (!defined('HA_PHONE')) { define('HA_PHONE', '۰۷۶ ۶۴۸ ۶۲۹۹'); }
if (!defined('HA_HOTLINE')) { define('HA_HOTLINE', '+98766486299'); }

/* ------------------------------------------------------------------ */
/*  فرم تماس                                                          */
/* ------------------------------------------------------------------ */

/** ذخیره‌ی پیام‌ها در storage/messages/messages.csv */
if (!defined('HA_STORE_MESSAGES')) { define('HA_STORE_MESSAGES', true); }

/** ارسال ایمیل — روی InfinityFree معمولاً mail() مسدود است؛ پیش‌فرض خاموش. */
if (!defined('HA_SEND_MAIL')) { define('HA_SEND_MAIL', false); }

/* ------------------------------------------------------------------ */
/*  امنیت و اشکال‌زدایی                                               */
/* ------------------------------------------------------------------ */

/** نمایش خطاها در خروجی. در Production حتماً false بماند. */
if (!defined('HA_DEBUG')) { define('HA_DEBUG', false); }

/** انتقال اجباری به HTTPS (پس از فعال‌سازی SSL روی هاست). */
if (!defined('HA_FORCE_HTTPS')) { define('HA_FORCE_HTTPS', false); }

/** ارسال سرصفحه‌های امنیتی (CSP و…) از سمت PHP. */
if (!defined('HA_SECURITY_HEADERS')) { define('HA_SECURITY_HEADERS', true); }

/* ------------------------------------------------------------------ */
/*  دیتابیس (MySQL/MariaDB — InfinityFree)                           */
/* ------------------------------------------------------------------ */

if (!defined('HA_DB_HOST')) { define('HA_DB_HOST', ''); }
if (!defined('HA_DB_PORT')) { define('HA_DB_PORT', 3306); }
if (!defined('HA_DB_NAME')) { define('HA_DB_NAME', ''); }
if (!defined('HA_DB_USER')) { define('HA_DB_USER', ''); }
if (!defined('HA_DB_PASS')) { define('HA_DB_PASS', ''); }

/** Seed خودکار از data/*.php هنگام نخستین اتصال موفق (جداول خالی). */
if (!defined('HA_DB_AUTO_SEED')) { define('HA_DB_AUTO_SEED', false); }

/** ایمیل/رمز ادمین اولیه فقط برای seed (در config.local تعریف کنید). */
if (!defined('HA_SEED_ADMIN_EMAIL')) { define('HA_SEED_ADMIN_EMAIL', ''); }
if (!defined('HA_SEED_ADMIN_PASSWORD')) { define('HA_SEED_ADMIN_PASSWORD', ''); }
if (!defined('HA_SEED_ADMIN_NAME')) { define('HA_SEED_ADMIN_NAME', 'مدیر'); }

/* ------------------------------------------------------------------ */
/*  محدودیت نرخ (Rate Limit) فرم تماس                                 */
/* ------------------------------------------------------------------ */

if (!defined('HA_RATE_LIMIT_MAX')) { define('HA_RATE_LIMIT_MAX', 3); }
if (!defined('HA_RATE_LIMIT_WINDOW')) { define('HA_RATE_LIMIT_WINDOW', 600); }
if (!defined('HA_RATE_LIMIT_MIN_INTERVAL')) { define('HA_RATE_LIMIT_MIN_INTERVAL', 20); }
if (!defined('HA_RATE_LIMIT_MAX_FILES')) { define('HA_RATE_LIMIT_MAX_FILES', 400); }
if (!defined('HA_CSRF_TTL')) { define('HA_CSRF_TTL', 28800); }

/* ------------------------------------------------------------------ */
/*  فرم «نظرات عمومی»                                                 */
/* ------------------------------------------------------------------ */

if (!defined('HA_COMMENTS_RATE_LIMIT_MAX')) { define('HA_COMMENTS_RATE_LIMIT_MAX', 3); }
if (!defined('HA_COMMENTS_RATE_LIMIT_WINDOW')) { define('HA_COMMENTS_RATE_LIMIT_WINDOW', 900); }
if (!defined('HA_COMMENTS_RATE_LIMIT_MIN_INTERVAL')) { define('HA_COMMENTS_RATE_LIMIT_MIN_INTERVAL', 30); }
if (!defined('HA_COMMENTS_PER_PAGE')) { define('HA_COMMENTS_PER_PAGE', 10); }

/* ------------------------------------------------------------------ */
/*  حساب کاربری (ورود / ثبت‌نام)                                      */
/* ------------------------------------------------------------------ */

if (!defined('HA_AUTH_MIN_PASSWORD')) { define('HA_AUTH_MIN_PASSWORD', 8); }
if (!defined('HA_AUTH_LOGIN_RATE_LIMIT_MAX')) { define('HA_AUTH_LOGIN_RATE_LIMIT_MAX', 8); }
if (!defined('HA_AUTH_REGISTER_RATE_LIMIT_MAX')) { define('HA_AUTH_REGISTER_RATE_LIMIT_MAX', 5); }
if (!defined('HA_AUTH_RATE_LIMIT_WINDOW')) { define('HA_AUTH_RATE_LIMIT_WINDOW', 600); }
if (!defined('HA_AUTH_SESSION_TTL')) { define('HA_AUTH_SESSION_TTL', 43200); }

/* ------------------------------------------------------------------ */
/*  آپلودِ فایل توسط مدیر                                             */
/* ------------------------------------------------------------------ */

if (!defined('HA_UPLOAD_MAX_BYTES')) { define('HA_UPLOAD_MAX_BYTES', 8388608); }
if (!defined('HA_UPLOAD_MAX_MEDIA_BYTES')) { define('HA_UPLOAD_MAX_MEDIA_BYTES', 0); }

if (!defined('HA_VERSION')) { define('HA_VERSION', '3.5.0'); }
