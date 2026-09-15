<?php
/**
 * HAvoice Admin — تغییرِ وضعیتِ انتشار (منتشرشده ⇄ پیش‌نویس)
 *
 * یک handlerِ عمومی برای همه‌ی انواع محتوا که فهرستِ سفیدِ کامل دارد.
 * اگر مورد در فروشگاهِ پنل موجود نباشد (یعنی از فایل می‌آید)، اول یک
 * نسخه‌ی پنلی از همان مورد ساخته می‌شود و وضعیت روی آن نوشته می‌شود —
 * فایلِ پایه هرگز دست‌نخورده می‌ماند و مدیر می‌تواند محتوای پایه را هم
 * «مخفی» کند بدونِ حذف.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
require HA_ROOT . '/pages/admin/_helpers.php';

$goBack = url('admin');

/* جدولِ سفیدِ انواع: [فروشگاه, کلیدِ یکتا, مسیرِ فهرست, تابعِ منبعِ ادغام‌شده] */
$registry = [
    'course'   => ['courses',    'slug', 'admin_courses',    'courses_all'],
    'article'  => ['articles',   'slug', 'admin_articles',   'articles_all'],
    'video'    => ['media',      'slug', 'admin_videos',     'media_all'],
    'audio'    => ['media',      'slug', 'admin_audios',     'media_all'],
    'book'     => ['books',      'slug', 'admin_books',      'books_all'],
    'research' => ['research',   'slug', 'admin_research',   'research_all'],
    'exercise' => ['exercises',  'id',   'admin_exercises',  'exercises_all'],
    'tip'      => ['tips',       'id',   'admin_tips',       'tips_all'],
    'category' => ['categories', 'slug', 'admin_categories', 'categories'],
];

$type = (string) ($_POST['type'] ?? '');
if (!isset($registry[$type])) {
    flash('error', 'نوعِ محتوا معتبر نیست.');
    redirect($goBack);
}
[$store, $idKey, $listRoute, $sourceFn] = $registry[$type];
$goBack = url($listRoute);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect($goBack);
}
if (!csrf_verify()) {
    flash('error', 'نشست شما تمام شده است. دوباره تلاش کنید.');
    redirect($goBack);
}

$key    = slugify((string) ($_POST['key'] ?? ''));
$status = admin_post_status();
if ($key === '') {
    flash('error', 'شناسه‌ی محتوا معتبر نیست.');
    redirect($goBack);
}

/* برای ویدیو/صوت: فروشگاهِ مشترکِ media با فیلترِ type */
$mediaType = ($type === 'video' || $type === 'audio') ? $type : null;

$matches = static function (array $item) use ($idKey, $key, $mediaType): bool {
    if (slugify((string) ($item[$idKey] ?? '')) !== $key) {
        return false;
    }
    if ($mediaType !== null && (string) ($item['type'] ?? '') !== $mediaType) {
        return false;
    }
    return true;
};

$items = admin_load($store);
$found = false;
foreach ($items as $i => $item) {
    if (is_array($item) && $matches($item)) {
        $items[$i]['status'] = $status;
        unset($items[$i]['hidden']); // وضعیتِ قدیمیِ hidden ترکیب نشود
        $found = true;
        break;
    }
}

/* مورد از فایل می‌آید و نسخه‌ی پنلی ندارد ⇒ نسخه‌ی پنلی بساز */
if (!$found) {
    $source = [];
    if (function_exists($sourceFn)) {
        foreach ($sourceFn() as $item) {
            if (is_array($item) && $matches($item)) {
                $source = $item;
                break;
            }
        }
    }
    if ($source === []) {
        flash('error', 'موردِ موردنظر پیدا نشد.');
        redirect($goBack);
    }
    $source['status'] = $status;
    unset($source['hidden']);
    $items[] = $source;
}

if (!admin_store($store, $items)) {
    flash('error', 'ذخیره‌سازی ناموفق بود؛ پوشه‌ی storage قابلِ نوشتن نیست.');
    redirect($goBack);
}

flash('success', $status === 'published'
    ? 'با موفقیت منتشر شد؛ اکنون برای کاربران نمایش داده می‌شود.'
    : 'مخفی شد؛ دیگر در سایت نمایش داده نمی‌شود ولی حذف نشده است.');
redirect($goBack);
