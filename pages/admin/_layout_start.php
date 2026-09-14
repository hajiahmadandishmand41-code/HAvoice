<?php
/**
 * HAvoice Admin — شروع لایه‌ی مشترک پنل مدیریت
 *
 * ساختار: یک گریدِ دوستونی. ستونِ راست سایدبارِ چسبان است و در عرض‌های
 * کوچک‌تر از ۹۴۰px به کشویِ لغزان تبدیل می‌شود (همان الگویِ ناوبریِ سایت).
 * هیچ استایلِ درون‌خطی‌ای اینجا نوشته نمی‌شود تا پنل از همان Design System
 * پیروی کند که بقیه‌ی سایت.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
$adminUser = auth_current_user();
$adminRoute = active_route();
$pendingComments = comments_admin_counts()['pending'];

/** آیا مسیرِ فعلی زیرمجموعه‌ی یک بخشِ پنل است؟ */
$admin_is = static function (string $prefix) use ($adminRoute): bool {
    return strpos($adminRoute, $prefix) === 0;
};
?>
<div class="admin-layout">
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar__brand">
            <?= ha_icon('shield', 20) ?>
            <div>پنل مدیریت<small><?= e(ha_site_name()) ?></small></div>
        </div>
        <nav class="admin-nav" aria-label="ناوبریِ پنل مدیریت">
            <a href="<?= e(url('admin')) ?>"<?= $adminRoute === 'admin' ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('home', 16) ?> داشبورد</a>

            <p class="admin-nav__section">محتوا</p>
            <a href="<?= e(url('admin_courses')) ?>"<?= $admin_is('admin_course') ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('steps', 16) ?> دوره‌ها و درس‌ها</a>
            <a href="<?= e(url('admin_articles')) ?>"<?= $admin_is('admin_article') ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('article', 16) ?> مقاله‌ها</a>
            <a href="<?= e(url('admin_videos')) ?>"<?= $admin_is('admin_video') ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('play', 16) ?> ویدیوها</a>
            <a href="<?= e(url('admin_audios')) ?>"<?= $admin_is('admin_audio') ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('headphones', 16) ?> صوت‌ها</a>
            <a href="<?= e(url('admin_books')) ?>"<?= $admin_is('admin_book') ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('book', 16) ?> کتاب‌ها</a>
            <a href="<?= e(url('admin_research')) ?>"<?= $admin_is('admin_research') ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('research', 16) ?> پژوهش‌ها</a>
            <a href="<?= e(url('admin_exercises')) ?>"<?= $admin_is('admin_exercise') ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('timer', 16) ?> تمرین‌ها</a>
            <a href="<?= e(url('admin_tips')) ?>"<?= $admin_is('admin_tip') ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('sparkle', 16) ?> نکته‌ها</a>
            <a href="<?= e(url('admin_categories')) ?>"<?= $admin_is('admin_categor') ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('compass', 16) ?> حوزه‌ها</a>

            <p class="admin-nav__section">سیستم</p>
            <a href="<?= e(url('admin_users')) ?>"<?= $admin_is('admin_user') ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('user', 16) ?> کاربران</a>
            <a href="<?= e(url('admin_messages')) ?>"<?= $admin_is('admin_message') ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('chat', 16) ?> پیام‌ها</a>
            <a href="<?= e(url('admin_comments')) ?>"<?= $admin_is('admin_comment') ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('comment', 16) ?> نظرات سایت<?= $pendingComments > 0 ? ' <span class="admin-nav__badge">' . fa_num($pendingComments) . '</span>' : '' ?></a>
            <a href="<?= e(url('admin_settings')) ?>"<?= $adminRoute === 'admin_settings' ? ' class="is-active" aria-current="page"' : '' ?>><?= ha_icon('target', 16) ?> تنظیمات</a>
        </nav>

        <div class="admin-sidebar-footer">
            <a class="btn btn--ghost btn--sm btn--block" href="<?= e(url('home')) ?>"><?= ha_icon('external', 15) ?> مشاهده‌ی سایت</a>
            <a class="btn btn--ghost btn--sm btn--block" href="<?= e(url('account')) ?>"><?= ha_icon('user', 15) ?> حسابِ من</a>
        </div>
    </aside>

    <div class="admin-overlay" id="admin-overlay" hidden></div>

    <div class="admin-main">
        <div class="admin-main__header">
            <div class="admin-main__header-title">
                <button class="icon-btn admin-sidebar-toggle" type="button" data-admin-toggle
                        aria-label="باز کردن منوی مدیریت" aria-controls="admin-sidebar" aria-expanded="false">
                    <?= ha_icon('menu', 18) ?>
                </button>
                <h1><?= e($GLOBALS['HA_META']['h1'] ?? 'پنل مدیریت') ?></h1>
            </div>
            <span class="admin-main__user"><?= ha_icon('user', 14) ?> <?= e($adminUser['name'] ?? '') ?> · مدیر</span>
        </div>
