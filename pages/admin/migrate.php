<?php
/**
 * HAvoice Admin — انتقالِ داده‌ی فایل به دیتابیس (Migrate)
 *
 * مسیرِ مهاجرتِ تعریف‌شده در مستند (docs/ADMIN-DB-AUDIT.md):
 *   Current File Data → Normalize → Import (DB) → Verify (COUNT) →
 *   Switch Read Path (db_ready با داده‌ی پر) → حذفِ مسیرِ قدیمی «فقط
 *   بعد» از تأیید.
 * Import غیرتخریبی است: INSERT IGNORE — رکوردهای موجود حفظ می‌شوند.
 * POST-only + CSRF.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_helpers.php';
auth_require_admin();

if (!csrf_verify()) {
    flash('error', 'نشست تمام شده است. لطفاً دوباره تلاش کنید.');
    redirect(url('admin'));
}

if (!db_ready()) {
    flash('error', 'دیتابیس متصل نیست؛ انتقال ممکن نیست. ابتدا اتصال را در config برقرار کنید.');
    redirect(url('admin'));
}

/* شمارش‌ها با prepared-statement و نامِ جدول‌های ثابت (اجازه‌ی ورودیِ کاربر نیست) */
$tables = [
    'courses'   => 'ha_courses',
    'stages'    => 'ha_stages',
    'lessons'   => 'ha_lessons',
    'exercises' => 'ha_exercises',
    'videos'    => 'ha_media',
    'books'     => 'ha_books',
    'articles'  => 'ha_articles',
    'tips'      => 'ha_tips',
    'research'  => 'ha_research',
    'cats'      => 'ha_categories',
];
$migCount = static function (string $table): int {
    $r = db_one("SELECT COUNT(*) AS c FROM {$table}");
    return (int) ($r['c'] ?? 0);
};

$before = [];
foreach ($tables as $k => $t) { $before[$k] = $migCount($t); }
$before['users'] = db_user_count();

/* Import — محتوای فایل + آینه‌های پنل را به DB می‌ریزد (بدون تخریب). */
$res = repo_seed(false);
$ok  = !empty($res['ok']);

$after = [];
foreach ($tables as $k => $t) { $after[$k] = $migCount($t); }
$after['users'] = db_user_count();

/* Verify: هیچ شماری کمتر نشده باشد (Import نباید نابود کند) */
$lost = [];
foreach ($before as $k => $n) {
    if (($after[$k] ?? 0) < $n) {
        $lost[] = $k;
    }
}

if (!$ok || $lost !== []) {
    flash('error', 'انتقال ناکامل بود' . ($lost !== [] ? (' — کاهشِ رکورد در: ' . implode(', ', $lost)) : '') . '. گزارش در docs/ADMIN-DB-AUDIT.md را بخوانید و دوباره تلاش کنید.');
} else {
    $added = 0;
    foreach ($before as $k => $n) {
        $added += max(0, ((int) $after[$k]) - $n);
    }
    flash('success', 'انتقال/همگام‌سازی انجام شد. ' . fa_num($added) . ' رکوردِ جدید به دیتابیس اضافه شد؛ مسیرِ فایل همچنان به‌عنوانِ fallback پابرجاست.');
}
redirect(url('admin'));
