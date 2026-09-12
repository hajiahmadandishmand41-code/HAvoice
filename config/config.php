<?php
/**
 * HAvoice — تنظیمات اصلی سایت
 * برند: حاجی احمد صالحی | مدرس و پژوهشگر
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  مسیر و حالت آدرس‌دهی                                               */
/* ------------------------------------------------------------------ */

define('HA_BASE_PATH', '');
define('HA_PRETTY_URLS', false);

/* ------------------------------------------------------------------ */
/*  اطلاعات هویتی                                                     */
/* ------------------------------------------------------------------ */

define('HA_NAME',  'حاجی احمد صالحی');
define('HA_LEGAL', 'HAvoice');
define('HA_TAGLINE', 'مدرس و پژوهشگر');
define('HA_BRAND_FULL', 'حاجی احمد صالحی | مدرس و پژوهشگر');
define('HA_EMAIL', 'info@havoice.ir');
define('HA_PHONE', '۰۲۱ ۹۱۰۰ ۰۰۰۰');
define('HA_HOTLINE', '+989120000000');

define('HA_STORE_MESSAGES', true);
define('HA_SEND_MAIL',      false);
define('HA_DEBUG',          false);
define('HA_FORCE_HTTPS', false);

define('HA_RATE_LIMIT_MAX',  3);
define('HA_RATE_LIMIT_WINDOW', 600);

define('HA_VERSION', '2.0.0');
