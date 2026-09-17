<?php
/**
 * HAvoice Admin — داشبورد v6 — آمار واقعی
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
$totalCategories = count(categories());

/* پیام‌های تماس و شمارنده‌ی خوانده‌نشده */
$allMessages    = admin_messages_all();
$totalMessages  = count($allMessages);
$unreadMessages = admin_message_unread_count();
$commentCounts  = comments_admin_counts();

/* آمار واقعی — بازدید، محتوا، تابلو */
$siteStats = function_exists('ha_site_stats') ? ha_site_stats() : [];
$visitsTotal = (int)($siteStats['visits_total'] ?? 0);
$visitsToday = (int)($siteStats['visits_today'] ?? 0);
$visitsWeek = (int)($siteStats['visits_week'] ?? 0);
$contentViews = (int)($siteStats['content_views'] ?? 0);
$boardPosts = (int)($siteStats['board_posts'] ?? 0);
$boardReactions = (int)($siteStats['board_reactions'] ?? 0);
$boardComments = (int)($siteStats['board_comments'] ?? 0);
$boardLikes = (int)($siteStats['board_likes'] ?? 0);
$contentReactions = (int)($siteStats['content_reactions'] ?? 0);
$contentComments = (int)($siteStats['content_comments'] ?? 0);
?>

<div class="admin-stats">
    <div class="admin-stat admin-stat--primary"><strong><?= fa_num($visitsTotal) ?></strong><span>بازدید کل سایت</span></div>
    <div class="admin-stat"><strong><?= fa_num($visitsToday) ?></strong><span>بازدید امروز</span></div>
    <div class="admin-stat"><strong><?= fa_num($visitsWeek) ?></strong><span>بازدید ۷ روز</span></div>
    <div class="admin-stat"><strong><?= fa_num($contentViews) ?></strong><span>بازدید محتوا</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalUsers) ?></strong><span>کاربر</span></div>
    <div class="admin-stat admin-stat--accent"><strong><?= fa_num($boardPosts) ?></strong><span>پست تابلو</span></div>
    <div class="admin-stat"><strong><?= fa_num($boardReactions + $contentReactions) ?></strong><span>واکنش کل</span></div>
    <div class="admin-stat"><strong><?= fa_num($boardComments + $contentComments) ?></strong><span>نظر کل</span></div>
    <div class="admin-stat"><strong><?= fa_num($boardLikes) ?></strong><span>لایک تابلو</span></div>
</div>

<div class="admin-stats">
    <div class="admin-stat"><strong><?= fa_num($totalCourses) ?></strong><span>دوره</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalLessons) ?></strong><span>درس</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalArticles) ?></strong><span>مقاله</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalBooks) ?></strong><span>کتاب</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalResearch) ?></strong><span>پژوهش</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalVideos + $totalAudios) ?></strong><span>ویدیو/صوت</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalExercises) ?></strong><span>تمرین</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalTips) ?></strong><span>نکته</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalMessages) ?></strong><span>پیام تماس</span></div>
    <?php if ($unreadMessages > 0): ?>
        <div class="admin-stat admin-stat--warn"><strong><?= fa_num($unreadMessages) ?></strong><span>پیامِ جدید</span></div>
    <?php endif; ?>
    <div class="admin-stat"><strong><?= fa_num($commentCounts['approved']) ?></strong><span>نظرِ منتشرشده (قدیم)</span></div>
    <?php if ($commentCounts['pending'] > 0): ?>
        <div class="admin-stat admin-stat--warn"><strong><?= fa_num($commentCounts['pending']) ?></strong><span>نظرِ در انتظار</span></div>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h2>دسترسی سریع</h2>
    <div class="admin-quick">
        <a href="<?= e(url('admin_board')) ?>"><?= ha_icon('sparkle',18) ?> مدیریت تابلوی مجازی (<?= fa_num($boardPosts) ?>)</a>
        <a href="<?= e(url('admin_board_comments')) ?>"><?= ha_icon('comment',18) ?> نظرات تابلو (<?= fa_num($boardComments) ?>)</a>
        <a href="<?= e(url('admin_courses')) ?>"><?= ha_icon('steps',18) ?> مدیریت دوره‌ها</a>
        <a href="<?= e(url('admin_articles')) ?>"><?= ha_icon('article',18) ?> مدیریت مقالات</a>
        <a href="<?= e(url('admin_videos')) ?>"><?= ha_icon('play',18) ?> مدیریت ویدیوها</a>
        <a href="<?= e(url('admin_books')) ?>"><?= ha_icon('book',18) ?> مدیریت کتاب‌ها</a>
        <a href="<?= e(url('admin_users')) ?>"><?= ha_icon('user',18) ?> مدیریت کاربران (<?= fa_num($totalUsers) ?>)</a>
        <a href="<?= e(url('admin_messages')) ?>">
            <?= ha_icon('chat',18) ?> پیام‌های تماس
            <?php if ($unreadMessages > 0): ?>
                <span class="admin-badge admin-badge--warn" style="margin-inline-start: 0.35rem;"><?= fa_num($unreadMessages) ?> جدید</span>
            <?php endif; ?>
        </a>
        <a href="<?= e(url('admin_comments')) ?>"><?= ha_icon('comment',18) ?> نظرات سایت</a>
        <a href="<?= e(url('admin_categories')) ?>"><?= ha_icon('compass',18) ?> مدیریت حوزه‌ها</a>
        <a href="<?= e(url('admin_settings')) ?>"><?= ha_icon('target',18) ?> تنظیمات سایت</a>
    </div>
</div>

<div class="admin-card">
    <h2>آمار تفکیکی تابلو</h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>شاخص</th><th>تعداد</th><th>توضیح</th></tr></thead>
            <tbody>
                <tr><td>بازدید کل</td><td><strong><?= fa_num($visitsTotal) ?></strong></td><td>ثبت خودکار هر بازدید (بدون ربات)</td></tr>
                <tr><td>بازدید امروز</td><td><?= fa_num($visitsToday) ?></td><td>از نیمه‌شب امروز</td></tr>
                <tr><td>بازدید ۷ روز</td><td><?= fa_num($visitsWeek) ?></td><td>هفته اخیر</td></tr>
                <tr><td>بازدید محتوا</td><td><?= fa_num($contentViews) ?></td><td>مجموع بازدید مقالات/دوره‌ها/...</td></tr>
                <tr><td>پست تابلو</td><td><?= fa_num($boardPosts) ?></td><td>محتوای قدیمی حذف نمی‌شود</td></tr>
                <tr><td>واکنش تابلو</td><td><?= fa_num($boardReactions) ?></td><td>like/love/laugh/wow/sad</td></tr>
                <tr><td>نظر تابلو</td><td><?= fa_num($boardComments) ?></td><td>شامل Reply</td></tr>
                <tr><td>واکنش محتوا</td><td><?= fa_num($contentReactions) ?></td><td>واکنش مقالات/ویدیو/...</td></tr>
                <tr><td>نظر محتوا</td><td><?= fa_num($contentComments) ?></td><td>نظر مقالات/دوره‌ها/...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="admin-card">
    <h2>آخرین پیام‌های تماس</h2>
    <?php if ($allMessages === []): ?>
        <p class="muted-sm">هنوز پیامی دریافت نشده است.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                        <th>نام فرستنده</th>
                        <th>موضوع</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($allMessages, 0, 5) as $msg):
                    $ref = (string) ($msg['ref'] ?? '');
                    if ($ref === '') { continue; }
                    $isUnread = ($msg['status'] ?? 'unread') === 'unread';
                ?>
                    <tr class="<?= $isUnread ? 'is-highlight' : '' ?>">
                        <td>
                            <?php if ($isUnread): ?>
                                <span class="admin-badge admin-badge--warn">جدید</span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge--ghost">خوانده‌شده</span>
                            <?php endif; ?>
                        </td>
                        <td class="muted-sm"><?= e($msg['time'] ?? '') ?></td>
                        <td><strong><?= e($msg['name'] ?? '—') ?></strong></td>
                        <td><?= e($msg['subject'] ?: 'بدونِ موضوع') ?></td>
                        <td><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_message_view', ['slug' => $ref])) ?>">مشاهده</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-card__more"><a class="link-arrow" href="<?= e(url('admin_messages')) ?>">همه‌ی پیام‌ها (<?= fa_num($totalMessages) ?>)</a></div>
    <?php endif; ?>
</div>

<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
