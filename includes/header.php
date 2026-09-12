<?php
/**
 * HAvoice — سرصفحه‌ی مشترک: <head> + هدر + ناوبری + بنر صفحه.
 * داده‌ها از bootstrap در $GLOBALS['HA_META'] قرار می‌گیرد.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$meta  = $GLOBALS['HA_META'];
$route = $GLOBALS['HA_ROUTE'];
$site  = data('site');
?><!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($meta['title']) ?></title>
    <meta name="description" content="<?= e($meta['description']) ?>">
    <meta name="robots" content="<?= e($meta['robots'] ?? 'index,follow') ?>">
<?php if (!empty($meta['canonical'])): ?>
    <link rel="canonical" href="<?= e($meta['canonical']) ?>">
<?php endif; ?>

    <meta name="theme-color" content="#0d5c4d">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(HA_NAME) ?>">
    <meta property="og:title" content="<?= e($meta['title']) ?>">
    <meta property="og:description" content="<?= e($meta['description']) ?>">
    <meta property="og:locale" content="fa_IR">
<?php if (!empty($meta['image'])): ?>
    <meta property="og:image" content="<?= e(asset($meta['image'])) ?>">
<?php endif; ?>
<?php if (!empty($meta['jsonld'])): ?>
    <script type="application/ld+json"><?= json_encode($meta['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>

    <link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;900&display=swap">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <script>
        /* تم را قبل از رندر ست می‌کنیم تا پرش رنگ (FOUC) رخ ندهد */
        (function () {
            try {
                document.documentElement.dataset.theme = localStorage.getItem('ha-theme') || 'auto';
            } catch (err) {}
        })();
    </script>
</head>
<body class="route-<?= e($route) ?>">
<a class="skip-link" href="#main">پرش به محتوای اصلی</a>

<header class="site-header" data-header>
    <div class="container site-header__inner">
        <a class="brand" href="<?= e(url('home')) ?>" aria-label="<?= e(HA_NAME) ?>، صفحه اصلی">
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
                <small><?= e($site['brand_sub'] ?? 'هنرِ بیان مؤثر') ?></small>
            </span>
        </a>

        <nav class="main-nav" id="main-nav" aria-label="ناوبری اصلی">
            <ul class="main-nav__list">
<?php foreach (nav_items() as $item): ?>
                <li><a href="<?= e($item['url']) ?>"<?= is_current($item['route']) ? ' class="is-active" aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
<?php endforeach; ?>
            </ul>
            <div class="main-nav__cta">
                <a class="btn btn--primary btn--block" href="<?= e(url('course')) ?>"><?= e($site['cta_start'] ?? 'شروع مسیر آموزشی') ?></a>
            </div>
        </nav>

        <div class="header-actions">
            <a class="icon-btn" href="<?= e(url('search')) ?>" aria-label="جستجوی مقالات">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="7"/><path d="M20 20l-3.7-3.7"/>
                </svg>
            </a>
            <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="تغییر تم روشن یا تاریک">
                <svg class="icon-sun" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
                </svg>
                <svg class="icon-moon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M20 14.5A8.2 8.2 0 0 1 9.5 4 8.5 8.5 0 1 0 20 14.5z"/>
                </svg>
            </button>
            <a class="btn btn--primary header-actions__cta" href="<?= e(url('course')) ?>"><?= e($site['cta_start'] ?? 'شروع دوره') ?></a>
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
                <span aria-hidden="true">/</span>
                <a href="<?= e(url($route === 'article' ? 'articles' : 'course')) ?>"><?= e($meta['crumb']) ?></a>
<?php endif; ?>
                <span aria-hidden="true">/</span>
                <span aria-current="page"><?= e($meta['h1'] ?? '') ?></span>
            </nav>
            <h1 class="page-banner__title"><?= e($meta['h1'] ?? '') ?></h1>
            <p class="page-banner__lead"><?= e($meta['description']) ?></p>
        </div>
    </section>
<?php endif; ?>
