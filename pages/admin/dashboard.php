<?php
/**
 * HAvoice Admin — داشبورد
 *
 * شمارِ همه‌ی بخش‌ها، دکمه‌های افزودنِ سریع (+Course +Lesson +Exercise
 * +Video +Podcast +Book)، کارتِ انتقال به دیتابیس (اگر هنوز خالی است) و
 * آخرین پیام‌ها.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$totalUsers = count(auth_load_users());
$totalCourses = count(courses_all());
$totalLessons = count(course_lesson_index());
$totalArticles = count(articles_all());
$totalBooks = count(books_all());
$totalResearch = count(research_all());
$totalVideos = count(array_filter(media_all(), static fn($m) => ($m['type'] ?? '') === 'video'));
$totalAudios = count(array_filter(media_all(), static fn($m) => ($m['type'] ?? '') === 'audio'));
$totalExercises = count(exercises_all());
$totalTips = count(tips_all());

/* پیام‌ها: دیتابیسی (db-N) و آمارِ خوانده‌نشده */
$allMessages = admin_messages_all();
$totalMessages = count($allMessages);
$unreadCount = admin_messages_unread_count();
$commentCounts = comments_admin_counts();

$dbReady = db_ready();
/* آیا دیتابیس محتوا دارد یا هنوز مهاجرت نکرده‌ایم (فقط راهنمای مدیر) */
$dbCoursesRow = $dbReady ? db_one('SELECT COUNT(*) AS c FROM ha_courses') : null;
$dbHasContent = $dbCoursesRow !== null && (int) ($dbCoursesRow['c'] ?? 0) > 0;
?>

<div class="admin-stats">
    <div class="admin-stat"><strong><?= fa_num($totalCourses) ?></strong><span>دوره</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalLessons) ?></strong><span>درس</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalExercises) ?></strong><span>تمرین</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalVideos) ?></strong><span>ویدیو</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalAudios) ?></strong><span>پادکست/صوت</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalBooks) ?></strong><span>کتاب</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalArticles) ?></strong><span>مقاله</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalUsers) ?></strong><span>کاربر</span></div>
    <div class="admin-stat<?= $unreadCount > 0 ? ' admin-stat--warn' : '' ?>"><strong><?= fa_num($unreadCount) ?></strong><span>پیامِ خوانده‌نشده</span></div>
    <?php if ($commentCounts['pending'] > 0): ?>
    <div class="admin-stat admin-stat--warn"><strong><?= fa_num($commentCounts['pending']) ?></strong><span>نظرِ در انتظارِ تأیید</span></div>
    <?php endif; ?>
</div>

<?php if ($dbReady && !$dbHasContent): ?>
<div class="admin-card admin-card--cta mb-md">
    <h2>انتقال داده‌ی فعلی به دیتابیس</h2>
    <p class="muted-sm mb-sm">دیتابیس متصل است ولی هنوز محتوایی ندارد — محتوا از فایل‌ها خوانده می‌شود. با یک کلیک همه‌ای دوره‌ها، مرحله‌ها، درس‌ها، تمرین‌ها و بقیه‌ی محتوا وارد دیتابیس می‌شود (Import + Verify). مسیرِ فایل فقط بعد از تأییدِ صحت به‌عنوان fallback باقی می‌ماند و خراب نمی‌شود.</p>
    <form method="post" action="<?= e(url('admin_migrate')) ?>" class="inline-form" data-confirm="همه‌ی محتوای فایل به دیتابیس منتقل شود؟ (عملیات غیرتخریبی)">
        <?= csrf_field() ?>
        <button class="btn btn--primary" type="submit"><?= ha_icon('growth', 15) ?> انتقال به دیتابیس</button>
    </form>
</div>
<?php endif; ?>

<div class="admin-card">
    <h2>افزودنِ سریع</h2>
    <div class="admin-quick">
        <a href="<?= e(url('admin_course_edit')) ?>"><?= ha_icon('plus', 16) ?> +Course</a>
        <a href="<?= e(url('admin_lesson_edit')) ?>"><?= ha_icon('plus', 16) ?> +Lesson</a>
        <a href="<?= e(url('admin_exercise_edit')) ?>"><?= ha_icon('plus', 16) ?> +Exercise</a>
        <a href="<?= e(url('admin_video_edit')) ?>"><?= ha_icon('plus', 16) ?> +Video</a>
        <a href="<?= e(url('admin_audio_edit')) ?>"><?= ha_icon('plus', 16) ?> +Podcast</a>
        <a href="<?= e(url('admin_book_edit')) ?>"><?= ha_icon('plus', 16) ?> +Book</a>
    </div>
</div>

<div class="admin-card">
    <h2>مدیریت بخش‌ها</h2>
    <div class="admin-quick">
        <a href="<?= e(url('admin_courses')) ?>"><?= ha_icon('steps',18) ?> دوره‌ها و درس‌ها</a>
        <a href="<?= e(url('admin_articles')) ?>"><?= ha_icon('article',18) ?> مقاله‌ها</a>
        <a href="<?= e(url('admin_videos')) ?>"><?= ha_icon('play',18) ?> ویدیوها</a>
        <a href="<?= e(url('admin_audios')) ?>"><?= ha_icon('headphones',18) ?> پادکست‌ها</a>
        <a href="<?= e(url('admin_books')) ?>"><?= ha_icon('book',18) ?> کتاب‌ها</a>
        <a href="<?= e(url('admin_categories')) ?>"><?= ha_icon('compass',18) ?> حوزه‌ها</a>
        <a href="<?= e(url('admin_research')) ?>"><?= ha_icon('research',18) ?> پژوهش‌ها</a>
        <a href="<?= e(url('admin_messages')) ?>"><?= ha_icon('chat',18) ?> پیام‌ها<?= $unreadCount > 0 ? ' <span class="admin-nav__badge">' . fa_num($unreadCount) . '</span>' : '' ?></a>
        <a href="<?= e(url('admin_comments')) ?>"><?= ha_icon('comment',18) ?> نظرات</a>
        <a href="<?= e(url('admin_users')) ?>"><?= ha_icon('user',18) ?> کاربران</a>
        <a href="<?= e(url('admin_settings')) ?>"><?= ha_icon('target',18) ?> تنظیمات</a>
    </div>
</div>

<div class="admin-card">
    <h2>آخرین پیام‌های تماس</h2>
    <?php if ($allMessages === []): ?>
        <p class="muted-sm">هنوز پیامی دریافت نشده است.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th></th><th>تاریخ</th><th>نام</th><th>موضوع</th><th>عملیات</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($allMessages, 0, 5) as $msg):
                    $ref = (string) ($msg['ref'] ?? '');
                    if ($ref === '') { continue; }
                    $isUnread = empty($msg['read_at']);
                ?>
                    <tr<?= $isUnread ? ' class="admin-row--unread"' : '' ?>>
                        <td><?php if ($isUnread): ?><span class="admin-badge admin-badge--warn">جدید</span><?php endif; ?></td>
                        <td class="muted-sm"><?= e($msg['time'] ?? '') ?></td>
                        <td><?= e($msg['name'] ?? '') ?></td>
                        <td><?= e($msg['subject'] ?? '') ?></td>
                        <td><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_message_view', ['slug' => $ref])) ?>">مشاهده</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-card__more"><a class="link-arrow" href="<?= e(url('admin_messages')) ?>">همه‌ی پیام‌ها</a></div>
    <?php endif; ?>
</div>

<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
