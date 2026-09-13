<?php
/**
 * HAvoice Admin — شروع لایه‌ی مشترک پنل مدیریت
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
$adminUser = auth_current_user();
$adminRoute = active_route();
?>
<div class="admin-layout">
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar__brand">
            <?= ha_icon('shield', 20) ?>
            <div>پنل مدیریت<small><?= e(HA_NAME) ?></small></div>
        </div>
        <nav class="admin-nav">
            <a href="<?= e(url('admin')) ?>"<?= $adminRoute==='admin'?' class="is-active"':'' ?>><?= ha_icon('home',16) ?> داشبورد</a>

            <p class="admin-nav__section">محتوا</p>
            <a href="<?= e(url('admin_courses')) ?>"<?= strpos($adminRoute,'admin_course')===0?' class="is-active"':'' ?>><?= ha_icon('steps',16) ?> دوره‌ها و درس‌ها</a>
            <a href="<?= e(url('admin_articles')) ?>"<?= strpos($adminRoute,'admin_article')===0?' class="is-active"':'' ?>><?= ha_icon('article',16) ?> مقالات</a>
            <a href="<?= e(url('admin_videos')) ?>"<?= strpos($adminRoute,'admin_video')===0?' class="is-active"':'' ?>><?= ha_icon('play',16) ?> ویدیوها</a>
            <a href="<?= e(url('admin_audios')) ?>"<?= strpos($adminRoute,'admin_audio')===0?' class="is-active"':'' ?>><?= ha_icon('headphones',16) ?> صوتها</a>
            <a href="<?= e(url('admin_books')) ?>"<?= strpos($adminRoute,'admin_book')===0?' class="is-active"':'' ?>><?= ha_icon('book',16) ?> کتاب‌ها</a>
            <a href="<?= e(url('admin_research')) ?>"<?= strpos($adminRoute,'admin_research')===0?' class="is-active"':'' ?>><?= ha_icon('research',16) ?> پژوهش‌ها</a>
            <a href="<?= e(url('admin_exercises')) ?>"<?= strpos($adminRoute,'admin_exercise')===0?' class="is-active"':'' ?>><?= ha_icon('timer',16) ?> تمرین‌ها</a>
            <a href="<?= e(url('admin_tips')) ?>"<?= strpos($adminRoute,'admin_tip')===0?' class="is-active"':'' ?>><?= ha_icon('sparkle',16) ?> نکته‌ها</a>
            <a href="<?= e(url('admin_categories')) ?>"<?= strpos($adminRoute,'admin_categor')===0?' class="is-active"':'' ?>><?= ha_icon('compass',16) ?> حوزه‌ها</a>

            <p class="admin-nav__section">سیستم</p>
            <a href="<?= e(url('admin_users')) ?>"<?= strpos($adminRoute,'admin_user')===0?' class="is-active"':'' ?>><?= ha_icon('user',16) ?> کاربران</a>
            <a href="<?= e(url('admin_messages')) ?>"<?= strpos($adminRoute,'admin_message')===0?' class="is-active"':'' ?>><?= ha_icon('chat',16) ?> پیام‌ها</a>
            <a href="<?= e(url('admin_settings')) ?>"<?= $adminRoute==='admin_settings'?' class="is-active"':'' ?>><?= ha_icon('target',16) ?> تنظیمات</a>

            <div style="margin-top:1.5rem; padding-top:1rem; border-top:1px solid rgba(255,255,255,.1)">
                <a href="<?= e(url('home')) ?>"><?= ha_icon('external',16) ?> مشاهده سایت</a>
                <a href="<?= e(url('account')) ?>"><?= ha_icon('user',16) ?> حساب من</a>
            </div>
        </nav>
    </aside>
    <div class="admin-overlay" id="admin-overlay" hidden></div>
    <div class="admin-main">
        <div class="admin-main__header">
            <div style="display:flex;align-items:center;gap:.6rem">
                <button class="icon-btn admin-sidebar-toggle" type="button" data-admin-toggle aria-label="باز کردن منوی مدیریت">
                    <?= ha_icon('compass', 18) ?>
                </button>
                <h1><?= e($GLOBALS['HA_META']['h1'] ?? 'پنل مدیریت') ?></h1>
            </div>
            <span class="muted-sm"><?= e($adminUser['name'] ?? '') ?> · مدیر</span>
        </div>
