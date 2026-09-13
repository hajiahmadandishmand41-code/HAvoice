<?php
/**
 * HAvoice Admin — داشبورد
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$totalUsers = count(auth_load_users());
$totalCourses = count(courses());
$totalLessons = count(course_lesson_index());
$totalArticles = count(data('articles'));
$totalBooks = count(books());
$totalResearch = count(research_items());
$totalVideos = count(videos());
$totalAudios = count(audios());
$totalExercises = count(exercises());
$totalTips = count(tips());
$totalCategories = count(categories());
$adminMessages = admin_load('messages');
$totalMessages = count($adminMessages);
?>

<div class="admin-stats">
    <div class="admin-stat"><strong><?= fa_num($totalUsers) ?></strong><span>کاربر</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalCourses) ?></strong><span>دوره</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalLessons) ?></strong><span>درس</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalArticles) ?></strong><span>مقاله</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalBooks) ?></strong><span>کتاب</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalResearch) ?></strong><span>پژوهش</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalVideos + $totalAudios) ?></strong><span>ویدیو/صوت</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalExercises) ?></strong><span>تمرین</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalTips) ?></strong><span>نکته</span></div>
    <div class="admin-stat"><strong><?= fa_num($totalMessages) ?></strong><span>پیام</span></div>
</div>

<div class="admin-card">
    <h2>دسترسی سریع</h2>
    <div class="admin-quick">
        <a href="<?= e(url('admin_courses')) ?>"><?= ha_icon('steps',18) ?> مدیریت دوره‌ها</a>
        <a href="<?= e(url('admin_articles')) ?>"><?= ha_icon('article',18) ?> مدیریت مقالات</a>
        <a href="<?= e(url('admin_videos')) ?>"><?= ha_icon('play',18) ?> مدیریت ویدیوها</a>
        <a href="<?= e(url('admin_books')) ?>"><?= ha_icon('book',18) ?> مدیریت کتاب‌ها</a>
        <a href="<?= e(url('admin_users')) ?>"><?= ha_icon('user',18) ?> مدیریت کاربران</a>
        <a href="<?= e(url('admin_messages')) ?>"><?= ha_icon('chat',18) ?> پیام‌های تماس</a>
        <a href="<?= e(url('admin_categories')) ?>"><?= ha_icon('compass',18) ?> مدیریت حوزه‌ها</a>
        <a href="<?= e(url('admin_settings')) ?>"><?= ha_icon('target',18) ?> تنظیمات سایت</a>
    </div>
</div>

<div class="admin-card">
    <h2>آخرین پیام‌های تماس</h2>
    <?php if ($adminMessages === []): ?>
        <p class="muted-sm">هنوز پیامی دریافت نشده است.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>تاریخ</th><th>نام</th><th>موضوع</th><th>عملیات</th></tr></thead>
                <tbody>
                <?php foreach (array_slice(array_reverse($adminMessages), 0, 5) as $i => $msg): ?>
                    <tr>
                        <td class="muted-sm"><?= e($msg['time'] ?? '') ?></td>
                        <td><?= e($msg['name'] ?? '') ?></td>
                        <td><?= e($msg['subject'] ?? '') ?></td>
                        <td><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_message_view', ['slug' => $i])) ?>">مشاهده</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem"><a class="link-arrow" href="<?= e(url('admin_messages')) ?>">همه‌ی پیام‌ها</a></div>
    <?php endif; ?>
</div>

<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
