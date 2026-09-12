<?php
/**
 * HAvoice — مجموعه آیکونِ درون‌خطی (Inline SVG)
 *
 * چرا این فایل؟
 * پیش‌تر آیکون‌ها با emoji و گلیف‌های یونیکدِ پراکنده (⛬ ⛨ ◷ ▣ ⇄ 🎙 🔬 …)
 * در CSS ساخته می‌شدند. آن گلیف‌ها بین پلتفرم‌ها یکسان رندر نمی‌شوند،
 * در بعضی مرورگرها به شکل مربعِ خالی (tofu) درمی‌آیند، از currentColor
 * پیروی نمی‌کنند و در حالت تاریک/روشن قابل تنظیم نیستند.
 *
 * این مجموعه، آیکون‌های برداریِ ۲۴×۲۴ با خطوطِ هم‌ضخامت است که:
 *   • با currentColor رنگ می‌گیرند (سازگار با Design Tokenها و Dark Mode)
 *   • هیچ وابستگی و هیچ درخواست شبکه‌ی اضافه ندارند
 *   • روی InfinityFree به‌صورت static کار می‌کنند
 *   • با aria-hidden از درخت دسترس‌پذیری حذف می‌شوند (آیکون‌ها تزئینی‌اند؛
 *     برچسب متنی همیشه کنارشان هست)
 *
 * اگر آیکونی درخواست شود که وجود ندارد، یک دایره‌ی ساده برمی‌گردد تا
 * چیدمان هرگز نشکند.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/**
 * مسیرهای آیکون. همه در viewBox="0 0 24 24" با stroke و بدون fill.
 */
function ha_icon_paths(): array
{
    static $icons = null;
    if ($icons !== null) {
        return $icons;
    }

    $icons = [
        /* --- حوزه‌های آموزشی --- */
        'mic'       => '<path d="M12 3.8a2.4 2.4 0 0 0-2.4 2.4v5a2.4 2.4 0 0 0 4.8 0v-5A2.4 2.4 0 0 0 12 3.8Z"/><path d="M6.4 10.8v.6a5.6 5.6 0 0 0 11.2 0v-.6"/><path d="M12 17v3.2"/><path d="M9 20.2h6"/>',
        'chat'      => '<path d="M20.2 12.4c0 3.7-3.7 6.7-8.2 6.7a9.7 9.7 0 0 1-2.6-.35L4.6 20.4l1.3-3.3a6.4 6.4 0 0 1-2.1-4.7c0-3.7 3.7-6.7 8.2-6.7s8.2 3 8.2 6.7Z"/>',
        'brain'     => '<path d="M9.6 4.4a2.6 2.6 0 0 0-2.6 2.6 2.4 2.4 0 0 0-1.4 4.3 2.5 2.5 0 0 0 1 4.2 2.6 2.6 0 0 0 5 1V4.9a2.6 2.6 0 0 0-2-.5Z"/><path d="M14.4 4.4a2.6 2.6 0 0 1 2.6 2.6 2.4 2.4 0 0 1 1.4 4.3 2.5 2.5 0 0 1-1 4.2 2.6 2.6 0 0 1-5 1"/>',
        'growth'    => '<path d="M4 19.2 9.4 13l3.3 3.2L20 7.6"/><path d="M15.2 7.6H20v4.8"/><path d="M4 20.4h16"/>',
        'life'      => '<path d="M12 20.2s-7.2-4.2-7.2-9a4 4 0 0 1 7.2-2.4A4 4 0 0 1 19.2 11.2c0 4.8-7.2 9-7.2 9Z"/>',
        'target'    => '<circle cx="12" cy="12" r="8.2"/><circle cx="12" cy="12" r="4.4"/><circle cx="12" cy="12" r="1"/>',
        'briefcase' => '<rect x="3.4" y="7.6" width="17.2" height="12" rx="2"/><path d="M9 7.6V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.6"/><path d="M3.4 12.6h17.2"/>',
        'handshake' => '<path d="m3.6 12.4 3-3.2a2 2 0 0 1 2.8 0l1.5 1.5a1.4 1.4 0 0 0 2 0l1.4-1.5a2 2 0 0 1 2.8 0l3.3 3.2"/><path d="m7.4 13.4 3 3a1.6 1.6 0 0 0 2.3 0l.8-.8"/><path d="m13.2 16.6 1.7 1.7a1.6 1.6 0 0 0 2.3-2.3"/>',
        'book'      => '<path d="M5 4.6h9.4a2.6 2.6 0 0 1 2.6 2.6v12.2H7.6A2.6 2.6 0 0 1 5 16.8Z"/><path d="M17 19.4a2.4 2.4 0 0 0 2.4-2.4V7.2"/><path d="M8.2 8.6h5.6M8.2 11.8h5.6"/>',
        'research'  => '<circle cx="10.6" cy="10.6" r="6"/><path d="m15.2 15.2 4.4 4.4"/><path d="M8.4 10.6h4.4M10.6 8.4v4.4"/>',
        'podcast'   => '<path d="M8.2 17.6a6.6 6.6 0 1 1 7.6 0"/><circle cx="12" cy="11.4" r="2.4"/><path d="M12 13.8v6"/>',
        'video'     => '<rect x="3.2" y="6.2" width="12.6" height="11.6" rx="2.2"/><path d="m15.8 12 5-3.2v6.4l-5-3.2Z"/>',

        /* --- اصول و ویژگی‌ها --- */
        'steps'     => '<path d="M4.6 19.4h4.2v-4.2h4.2V11h4.2V6.8h2.6"/>',
        'timer'     => '<circle cx="12" cy="13.4" r="7.2"/><path d="M12 9.8v3.6l2.4 1.6"/><path d="M9.4 3.6h5.2"/>',
        'check'     => '<path d="m4.8 12.6 4.6 4.6L19.2 7.4"/>',
        'shield'    => '<path d="M12 3.6 5.2 6.2v5.4c0 4 2.9 7.5 6.8 8.8 3.9-1.3 6.8-4.8 6.8-8.8V6.2Z"/><path d="m9.2 12 2 2 3.6-3.8"/>',
        'sparkle'   => '<path d="M12 4.2 13.7 9l4.8 1.7-4.8 1.7L12 17.2 10.3 12.4 5.5 10.7 10.3 9Z"/><path d="M18.4 16.6l.7 1.9 1.9.7-1.9.7-.7 1.9-.7-1.9-1.9-.7 1.9-.7Z"/>',
        'idea'      => '<path d="M9.4 17.6a6 6 0 1 1 5.2 0"/><path d="M9.8 17.6h4.4M10.4 20.2h3.2"/>',
        'alert'     => '<path d="M12 4.4 3.4 19.2h17.2Z"/><path d="M12 10v3.8"/><circle cx="12" cy="16.4" r=".9"/>',
        'info'      => '<circle cx="12" cy="12" r="8.4"/><path d="M12 11.2v5"/><circle cx="12" cy="8.2" r=".9"/>',

        /* --- رسانه --- */
        'play'      => '<path d="M8.4 5.6 18 12l-9.6 6.4Z"/>',
        'headphones'=> '<path d="M5 15.6v-3a7 7 0 0 1 14 0v3"/><rect x="3.2" y="14.4" width="3.6" height="5.6" rx="1.6"/><rect x="17.2" y="14.4" width="3.6" height="5.6" rx="1.6"/>',
        'clock'     => '<circle cx="12" cy="12" r="8.4"/><path d="M12 7.4V12l3.2 2"/>',

        /* --- ناوبری و عملیات --- */
        'search'    => '<circle cx="10.8" cy="10.8" r="6.4"/><path d="m15.6 15.6 4 4"/>',
        'sun'       => '<circle cx="12" cy="12" r="4"/><path d="M12 2.8v2.4M12 18.8v2.4M4.6 4.6l1.7 1.7M17.7 17.7l1.7 1.7M2.8 12h2.4M18.8 12h2.4M4.6 19.4l1.7-1.7M17.7 6.3l1.7-1.7"/>',
        'moon'      => '<path d="M20 14.6A8.4 8.4 0 0 1 9.4 4 8.6 8.6 0 1 0 20 14.6Z"/>',
        'menu'      => '<path d="M4 7.2h16M4 12h16M4 16.8h16"/>',
        'close'     => '<path d="M6.4 6.4 17.6 17.6M17.6 6.4 6.4 17.6"/>',
        'arrow-up'  => '<path d="M12 19.2V5.4"/><path d="m6.2 11.2 5.8-5.8 5.8 5.8"/>',
        'arrow-left'=> '<path d="M19.4 12H5.2"/><path d="m11 5.8-5.8 6.2 5.8 6.2"/>',
        'arrow-right'=> '<path d="M4.6 12h14.2"/><path d="m13 5.8 5.8 6.2L13 18.2"/>',
        'chevron-left' => '<path d="m14.4 6.4-5.6 5.6 5.6 5.6"/>',
        'chevron-right'=> '<path d="m9.6 6.4 5.6 5.6-5.6 5.6"/>',
        'chevron-down' => '<path d="m6.4 9.6 5.6 5.6 5.6-5.6"/>',
        'copy'      => '<rect x="8.6" y="8.6" width="11.4" height="11.4" rx="2"/><path d="M15.4 5.6a2 2 0 0 0-2-1.6H6a2 2 0 0 0-2 2v7.4a2 2 0 0 0 1.6 2"/>',
        'link'      => '<path d="M10.2 13.8a3.6 3.6 0 0 0 5.2 0l2.8-2.8a3.7 3.7 0 0 0-5.2-5.2l-1.4 1.4"/><path d="M13.8 10.2a3.6 3.6 0 0 0-5.2 0l-2.8 2.8a3.7 3.7 0 0 0 5.2 5.2l1.4-1.4"/>',
        'external'  => '<path d="M14.4 4.6h5v5"/><path d="m19 5-7.6 7.6"/><path d="M18 14.6v3.4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3.4"/>',
        'mail'      => '<rect x="3.2" y="5.6" width="17.6" height="12.8" rx="2"/><path d="m3.8 7 8.2 6 8.2-6"/>',
        'phone'     => '<path d="M7.4 4.2h3l1.4 3.6-2 1.4a11 11 0 0 0 5 5l1.4-2 3.6 1.4v3a1.8 1.8 0 0 1-2 1.8A15.6 15.6 0 0 1 5.6 6.2a1.8 1.8 0 0 1 1.8-2Z"/>',
        'user'      => '<circle cx="12" cy="8.4" r="3.6"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'list'      => '<path d="M8.6 6.6h11M8.6 12h11M8.6 17.4h11"/><circle cx="4.8" cy="6.6" r=".9"/><circle cx="4.8" cy="12" r=".9"/><circle cx="4.8" cy="17.4" r=".9"/>',
        'layers'    => '<path d="m12 3.6 8.4 4.2-8.4 4.2-8.4-4.2Z"/><path d="m4.4 12.4 7.6 3.8 7.6-3.8"/><path d="m4.4 16.6 7.6 3.8 7.6-3.8"/>',
        'compass'   => '<circle cx="12" cy="12" r="8.4"/><path d="m14.8 9.2-1.6 4.4-4.4 1.6 1.6-4.4Z"/>',
        'bookmark'  => '<path d="M6.6 4.4h10.8v15.2L12 15.8l-5.4 3.8Z"/>',
        'grid'      => '<rect x="4" y="4" width="7" height="7" rx="1.6"/><rect x="13" y="4" width="7" height="7" rx="1.6"/><rect x="4" y="13" width="7" height="7" rx="1.6"/><rect x="13" y="13" width="7" height="7" rx="1.6"/>',
        'dot'       => '<circle cx="12" cy="12" r="3.2"/>',
    ];

    return $icons;
}

/**
 * خروجیِ یک آیکون.
 *
 * به‌جای چاپِ کاملِ مسیرهای SVG در هر نقطه، یک ارجاعِ <use> به sprite
 * می‌سازیم و مسیرها یک‌بار در پایانِ صفحه چاپ می‌شوند (ha_icon_sprite).
 * نتیجه: HTML کوچک‌تر و کشِ بهتر، بدونِ هیچ درخواستِ اضافه.
 *
 * @param string $name  نام آیکون
 * @param int    $size  اندازه‌ی پیکسلی (پیش‌فرض ۲۰)
 * @param string $extra کلاسِ CSS اضافی
 */
function ha_icon(string $name, int $size = 20, string $extra = ''): string
{
    $name = strtolower(trim($name));
    if (!ha_icon_exists($name)) {
        $name = 'dot';
    }
    ha_icon_used($name);
    $cls = 'ha-icon' . ($extra !== '' ? ' ' . $extra : '');

    return '<svg class="' . e($cls) . '" width="' . (int) $size . '" height="' . (int) $size
         . '" aria-hidden="true" focusable="false"><use href="#ha-i-' . e($name) . '"></use></svg>';
}

/** فهرستِ آیکون‌های استفاده‌شده در همین درخواست. */
function ha_icon_used(?string $name = null): array
{
    static $used = [];
    if ($name !== null) {
        $used[$name] = true;
    }
    return array_keys($used);
}

/**
 * چاپِ spriteِ آیکون‌ها — یک‌بار در پایانِ صفحه.
 * فقط آیکون‌هایی که واقعاً استفاده شده‌اند چاپ می‌شوند.
 */
function ha_icon_sprite(): string
{
    $used = ha_icon_used();
    if ($used === []) {
        return '';
    }
    $icons = ha_icon_paths();
    $out = '<svg class="ha-sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">';
    foreach ($used as $name) {
        $out .= '<symbol id="ha-i-' . e($name) . '" viewBox="0 0 24 24" fill="none"'
              . ' stroke="currentColor" stroke-width="1.8" stroke-linecap="round"'
              . ' stroke-linejoin="round">' . $icons[$name] . '</symbol>';
    }
    return $out . '</svg>';
}

/** آیا این نامِ آیکون در مجموعه وجود دارد؟ */
function ha_icon_exists(string $name): bool
{
    return isset(ha_icon_paths()[strtolower(trim($name))]);
}
