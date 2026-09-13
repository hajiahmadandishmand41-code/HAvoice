<?php
/**
 * HAvoice — سرصفحه
 *
 * تصمیم‌های مهم این فایل:
 * ۱) فونتِ Vazirmatn به‌صورت محلی و self-host بارگذاری می‌شود (بدونِ
 *    درخواست به fonts.googleapis.com). دلیل: سرعت و در دسترس بودن برای
 *    کاربرانِ داخلِ ایران، حذفِ وابستگی به سرویسِ ثالث، و سازگاری با
 *    Content-Security-Policy سخت‌گیرانه (style-src/font-src فقط 'self').
 * ۲) اسکریپتِ تعیینِ تم از حالتِ inline به فایلِ خارجی منتقل شده تا
 *    بتوان script-src 'self' را بدونِ 'unsafe-inline' اعمال کرد.
 * ۳) همه‌ی نشانی‌های متا (canonical، og:url، og:image) مطلق‌اند.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$meta  = $GLOBALS['HA_META'];
$route = $GLOBALS['HA_ROUTE'];
$site  = data('site');
$cats  = categories();
$currentUser = auth_current_user();

$canonical = !empty($meta['canonical']) ? absolute_url((string) $meta['canonical']) : '';
$ogImage   = !empty($meta['image']) ? absolute_url(asset((string) $meta['image'])) : '';
$themeInk  = '#0d5c4d';
$themeDark = '#0b1512';
?><!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($meta['title']) ?></title>
    <meta name="description" content="<?= e($meta['description']) ?>">
    <meta name="robots" content="<?= e($meta['robots'] ?? 'index,follow') ?>">
    <meta name="author" content="<?= e(HA_NAME) ?>">
    <meta name="generator" content="HAvoice <?= e(HA_VERSION) ?> (hand-written PHP)">
<?php if ($canonical !== ''): ?>
    <link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>

    <meta name="theme-color" content="<?= e($themeInk) ?>" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="<?= e($themeDark) ?>" media="(prefers-color-scheme: dark)">

    <meta property="og:type" content="<?= e($meta['og_type'] ?? 'website') ?>">
    <meta property="og:site_name" content="<?= e(HA_BRAND_FULL) ?>">
    <meta property="og:title" content="<?= e($meta['title']) ?>">
    <meta property="og:description" content="<?= e($meta['description']) ?>">
    <meta property="og:locale" content="fa_IR">
<?php if ($canonical !== ''): ?>
    <meta property="og:url" content="<?= e($canonical) ?>">
<?php endif; ?>
<?php if ($ogImage !== ''): ?>
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:image:alt" content="<?= e(HA_BRAND_FULL) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($meta['title']) ?>">
    <meta name="twitter:description" content="<?= e($meta['description']) ?>">
    <meta name="twitter:image" content="<?= e($ogImage) ?>">
<?php endif; ?>

<?php foreach ((array) ($meta['jsonld'] ?? []) as $graph): ?>
    <script type="application/ld+json"><?= json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endforeach; ?>

    <link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preload" href="<?= e(asset('assets/fonts/vazirmatn-var.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <script src="<?= e(asset('assets/js/theme.js')) ?>"></script>
</head>
<body class="route-<?= e($route) ?>">
<a class="skip-link" href="#main">پرش به محتوای اصلی</a>

<header class="site-header" data-header>
    <div class="container site-header__inner">
        <a class="brand" href="<?= e(url('home')) ?>" aria-label="<?= e(HA_BRAND_FULL) ?>، صفحه اصلی">
            <span class="brand__mark" aria-hidden="true">
                <svg viewBox="0 0 32 32" width="32" height="32" role="presentation" focusable="false">
                    <defs>
                        <linearGradient id="brandGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#2ec4a6"/>
                            <stop offset="1" stop-color="#0d5c4d"/>
                        </linearGradient>
                    </defs>
                    <rect x="0" y="0" width="32" height="32" rx="10" fill="url(#brandGrad)"/>
                    <g fill="#ffffff">
                        <rect x="7"    y="13" width="2.6" height="6"  rx="1.3"/>
                        <rect x="11.8" y="9"  width="2.6" height="14" rx="1.3"/>
                        <rect x="16.6" y="5.5" width="2.6" height="21" rx="1.3"/>
                        <rect x="21.4" y="11" width="2.6" height="10" rx="1.3"/>
                    </g>
                </svg>
            </span>
            <span class="brand__text">
                <strong><?= e(HA_NAME) ?></strong>
                <small><?= e(HA_TAGLINE) ?> · HAvoice</small>
            </span>
        </a>

        <nav class="main-nav" id="main-nav" aria-label="ناوبری اصلی">
            <ul class="main-nav__list">
<?php foreach (nav_items() as $item): ?>
                <li><a href="<?= e($item['url']) ?>"<?= is_current($item['route']) ? ' class="is-active" aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
<?php endforeach; ?>
            </ul>

            <div class="main-nav__mega" aria-label="حوزه‌های آموزشی">
                <p class="main-nav__mega-title">حوزه‌ها</p>
                <ul class="main-nav__mega-list">
<?php foreach ($cats as $cat): ?>
                    <li><a href="<?= e(url('category',['slug'=>$cat['slug']])) ?>"><span class="main-nav__mega-icon"><?= ha_icon((string)($cat['icon'] ?? 'compass'), 16) ?></span><?= e($cat['title']) ?></a></li>
<?php endforeach; ?>
                </ul>
            </div>

            <div class="main-nav__cta">
                <a class="btn btn--primary btn--block" href="<?= e(url('courses')) ?>"><?= e($site['cta_start'] ?? 'شروع یادگیری') ?></a>
                <div class="main-nav__auth">
<?php if ($currentUser !== null): ?>
                    <a class="btn btn--ghost btn--block" href="<?= e(url('account')) ?>"><?= ha_icon('user', 16) ?> حساب کاربری</a>
                    <?php if (auth_is_admin()): ?>
                    <a class="btn btn--ghost btn--block" href="<?= e(url('admin')) ?>" style="color:var(--brand)"><?= ha_icon('shield', 16) ?> پنل مدیریت</a>
                    <?php endif; ?>
                    <form method="post" action="<?= e(url('logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn--ghost btn--block" type="submit"><?= ha_icon('external', 16) ?> خروج</button>
                    </form>
<?php else: ?>
                    <a class="btn btn--ghost btn--block" href="<?= e(url('login')) ?>"><?= ha_icon('user', 16) ?> ورود</a>
                    <a class="btn btn--ghost btn--block" href="<?= e(url('register')) ?>">ثبت‌نام</a>
<?php endif; ?>
                </div>
            </div>
        </nav>

        <div class="header-actions">
            <a class="icon-btn" href="<?= e(url('search')) ?>" aria-label="جستجو در سایت">
                <?= ha_icon('search', 19) ?>
            </a>
            <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="تغییر حالت نمایش، حالتِ کنونی: خودکارِ سیستم">
                <span class="theme-toggle__icon theme-toggle__icon--sun"><?= ha_icon('sun', 19) ?></span>
                <span class="theme-toggle__icon theme-toggle__icon--moon"><?= ha_icon('moon', 19) ?></span>
            </button>
<?php if ($currentUser !== null): ?>
            <a class="account-chip" href="<?= e(url('account')) ?>" title="حساب کاربری">
                <span class="account-chip__avatar" aria-hidden="true"><?= e(auth_initial((string) $currentUser['name'])) ?></span>
                <span class="account-chip__name"><?= e(mb_strimwidth((string) $currentUser['name'], 0, 12, '…', 'UTF-8')) ?></span>
            </a>
            <?php if (auth_is_admin()): ?>
            <a class="icon-btn" href="<?= e(url('admin')) ?>" title="پنل مدیریت" style="color:var(--brand)">
                <?= ha_icon('shield', 19) ?>
            </a>
            <?php endif; ?>
<?php else: ?>
            <a class="icon-btn" href="<?= e(url('login')) ?>" aria-label="ورود به حساب کاربری">
                <?= ha_icon('user', 19) ?>
            </a>
<?php endif; ?>
            <a class="btn btn--primary header-actions__cta" href="<?= e(url('courses')) ?>">دوره‌ها</a>
            <button class="icon-btn nav-toggle" type="button" data-nav-toggle aria-controls="main-nav" aria-expanded="false" aria-label="باز و بسته کردن منو">
                <span class="nav-toggle__bars" aria-hidden="true"></span>
            </button>
        </div>
    </div>
</header>

<main id="main" class="site-main" tabindex="-1">
<?php if (!empty($meta['banner'])): ?>
    <section class="page-banner">
        <div class="container">
            <nav class="breadcrumbs" aria-label="مسیر صفحه">
                <a href="<?= e(url('home')) ?>">خانه</a>
<?php if (!empty($meta['crumb']) && $meta['crumb'] !== ($meta['h1'] ?? '')): ?>
                <span class="breadcrumbs__sep" aria-hidden="true"><?= ha_icon('chevron-left', 14) ?></span>
                <a href="<?= e(url($route === 'article' ? 'articles' : ($route === 'lesson' || $route === 'course' ? 'courses' : $route))) ?>"><?= e($meta['crumb']) ?></a>
<?php endif; ?>
                <span class="breadcrumbs__sep" aria-hidden="true"><?= ha_icon('chevron-left', 14) ?></span>
                <span aria-current="page"><?= e($meta['h1'] ?? '') ?></span>
            </nav>
            <h1 class="page-banner__title"><?= e($meta['h1'] ?? '') ?></h1>
            <p class="page-banner__lead"><?= e($meta['description']) ?></p>
        </div>
    </section>
<?php endif; ?>
