<?php
/**
 * HAvoice Admin — اجزای مشترکِ صفحه‌های فهرست و فرم
 *
 * این فایل توسط _layout_start.php بارگذاری می‌شود و فقط در پنل مدیریت
 * در دسترس است: برچسبِ وضعیتِ انتشار، دکمه‌ی انتشار/مخفی، نشانِ منبع
 * (فایل/پنل) و رندرِ یکدستِ پیام‌های flash.
 */

if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');

/** رندرِ پیامِ flash پنل (اگر چیزی هست). */
function admin_flash(): string
{
    $flash = flash();
    if (empty($flash['message'])) {
        return '';
    }
    $ok = ($flash['type'] ?? '') === 'success';
    return '<div class="alert alert--' . ($ok ? 'success' : 'error') . '" role="' . ($ok ? 'status' : 'alert') . '">'
         . '<p>' . e((string) $flash['message']) . '</p></div>';
}

/**
 * نشانِ وضعیتِ انتشار: منتشرشده / پیش‌نویس.
 */
function admin_status_badge(array $item): string
{
    if (ha_is_published($item)) {
        return '<span class="admin-badge admin-badge--success">' . ha_icon('eye', 12) . ' منتشرشده</span>';
    }
    return '<span class="admin-badge admin-badge--warn">' . ha_icon('edit', 12) . ' پیش‌نویس (مخفی)</span>';
}

/**
 * نشانِ منبع: آیا این مورد از فایلِ data می‌آید یا نسخه‌ی پنل دارد؟
 * تشخیص بر پایه‌ی وجودِ کلید در فروشگاهِ پنل است، نه جایگاه در فهرست —
 * چون فهرست‌ها با ha_merge_overrides() ادغام می‌شوند و بازنویسیِ پنل
 * در همان جایگاهِ موردِ فایل می‌نشیند.
 */
function admin_source_badge(bool $hasPanelVersion): string
{
    return $hasPanelVersion
        ? '<span class="admin-badge admin-badge--success">پنل</span>'
        : '<span class="admin-badge admin-badge--info">فایل</span>';
}

/**
 * فرمِ کوچکِ تغییرِ وضعیت (انتشار ⇄ مخفی) برای یک مورد.
 * برای مواردِ «فایل» هم کار می‌کند: handler اول یک نسخه‌ی پنلی از مورد
 * می‌سازد و بعد وضعیت را روی آن می‌نویسد — فایلِ پایه دست‌نخورده می‌ماند.
 */
function admin_status_toggle(string $type, string $key, array $item): string
{
    $published = ha_is_published($item);
    $next      = $published ? 'draft' : 'published';
    $label     = $published ? 'مخفی کردن' : 'انتشار';
    $icon      = $published ? 'close' : 'check';
    ob_start(); ?>
    <form method="post" action="<?= e(url('admin_content_status')) ?>" class="inline-form"
          data-confirm="<?= $published ? 'این مورد از دیدِ کاربران مخفی شود؟ (حذف نمی‌شود)' : 'این مورد منتشر شود؟' ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <input type="hidden" name="key" value="<?= e($key) ?>">
        <input type="hidden" name="status" value="<?= e($next) ?>">
        <button class="btn btn--ghost btn--sm" type="submit"><?= ha_icon($icon, 13) ?> <?= e($label) ?></button>
    </form>
    <?php return (string) ob_get_clean();
}

/** فیلدِ انتخابِ وضعیت برای فرم‌های ویرایش. */
function admin_status_field(array $item): string
{
    $status = ha_is_published($item) ? 'published' : 'draft';
    ob_start(); ?>
    <div class="field">
        <label for="f-status">وضعیتِ انتشار</label>
        <select class="input" id="f-status" name="status">
            <option value="published"<?= $status === 'published' ? ' selected' : '' ?>>منتشرشده — برای کاربران نمایش داده شود</option>
            <option value="draft"<?= $status === 'draft' ? ' selected' : '' ?>>پیش‌نویس — مخفی از دیدِ کاربران (بدونِ حذف)</option>
        </select>
    </div>
    <?php return (string) ob_get_clean();
}

/**
 * فیلدِ انتخابِ «حوزه» برای فرم‌های ویرایش — اتصالِ محتوا به یکی از
 * حوزه‌های آموزشی. برای مقاله‌ها برچسبِ فارسیِ category هم جداگانه
 * نگه داشته می‌شود (نمایش در کارت)، ولی فیلترها بر اساسِ همین اتصال
 * کار می‌کنند.
 */
function admin_field_select(array $item, string $inputName = 'field'): string
{
    $current = slugify((string) ($item['field'] ?? ''));
    if ($current === '' || find_category($current) === null) {
        $current = ha_item_field_slug($item);
    }
    ob_start(); ?>
    <div class="field">
        <label for="f-field">حوزه‌ی آموزشی</label>
        <select class="input" id="f-field" name="<?= e($inputName) ?>">
            <option value="">— بدونِ اتصال —</option>
            <?php foreach (categories() as $c): $cs = (string) ($c['slug'] ?? ''); ?>
            <option value="<?= e($cs) ?>"<?= $cs === $current ? ' selected' : '' ?>><?= e($c['title'] ?? $cs) ?></option>
            <?php endforeach; ?>
        </select>
        <p class="field__help">برای رنگِ کارت، صفحه‌ی حوزه و فیلترها استفاده می‌شود.</p>
    </div>
    <?php return (string) ob_get_clean();
}

/** مقدارِ ورودیِ status از POST — فقط دو مقدارِ مجاز. */
function admin_post_status(): string
{
    return ((string) ($_POST['status'] ?? '')) === 'draft' ? 'draft' : 'published';
}

/** خطوطِ غیرخالیِ یک textarea به‌صورتِ آرایه (برای refs، how_to و…). */
function admin_lines(string $key): array
{
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', (string) ($_POST[$key] ?? '')) as $line) {
        $line = trim($line);
        if ($line !== '') $out[] = $line;
    }
    return $out;
}

/** نامک از POST + fallback به ساخت از رویِ عنوان. */
function admin_post_slug(string $title = ''): string
{
    $slug = slugify((string) ($_POST['slug'] ?? ''));
    if ($slug === '' && $title !== '') {
        $slug = slugify($title);
    }
    return $slug;
}

/**
 * JSONِ بلوک‌ها از POST → آرایه یا null (null یعنی خطای پارس).
 */
function admin_post_blocks(string $key = 'blocks_json'): ?array
{
    $raw = trim((string) ($_POST[$key] ?? ''));
    if ($raw === '' || $raw === '[]') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

/** فیلدِ رأی/نشانِ «ویژه» برای فرم‌های ویرایش (نمایش در صفحه‌ی نخست). */
function admin_featured_field(array $item): string
{
    $checked = !empty($item['featured']) ? ' checked' : '';
    return '<div class="field"><label class="check"><input type="checkbox" name="featured" value="1"' . $checked . '>'
         . '<span>منتخب — در صفحه‌ی نخست نمایش داده شود</span></label></div>';
}
