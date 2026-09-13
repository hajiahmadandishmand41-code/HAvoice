/*!
 * HAvoice — تعیینِ حالتِ نمایش پیش از اولین رنگ‌آمیزی
 *
 * سه حالت پشتیبانی می‌شود: light | dark | auto.
 */
(function () {
    'use strict';

    var KEY = 'ha-theme';
    var VALID = { light: 1, dark: 1, auto: 1 };
    var root = document.documentElement;

    var read = function () {
        try {
            var v = window.localStorage.getItem(KEY);
            return (v && VALID[v]) ? v : 'auto';
        } catch (e) {
            return 'auto';
        }
    };

    root.setAttribute('data-theme', read());

    /*
     * Mobile layout hardening.
     * The drawer is never translated outside the viewport while closed.
     * It is removed from layout with display:none and becomes display:block
     * only in the open state. This prevents the closed state from creating
     * horizontal overflow or changing the page width.
     */
    var installMobileHardFix = function () {
        if (document.getElementById('ha-mobile-hard-fix')) return;

        var style = document.createElement('style');
        style.id = 'ha-mobile-hard-fix';
        style.textContent = [
            '@media (max-width:1119.98px){',
            'html,body{width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;padding:0!important;overflow-x:hidden!important;}',
            'body{display:block!important;position:relative!important;}',
            'body>.site-header,body>.site-main,body>.site-footer{display:block!important;width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;box-sizing:border-box!important;transform:none!important;left:auto!important;right:auto!important;inset-inline-start:auto!important;inset-inline-end:auto!important;}',
            'body>.site-main{position:relative!important;}',
            'body>.site-main>section,body>.site-main>div,body>.site-main>article,body>.site-main>aside{width:100%!important;max-width:100%!important;min-width:0!important;margin-left:0!important;margin-right:0!important;margin-inline-start:0!important;margin-inline-end:0!important;box-sizing:border-box!important;transform:none!important;}',
            'body>.site-main>.container,body>.site-main>section>.container,body>.site-main>div>.container,body>.site-main>article>.container{width:100%!important;max-width:100%!important;min-width:0!important;margin-left:0!important;margin-right:0!important;margin-inline-start:0!important;margin-inline-end:0!important;box-sizing:border-box!important;}',
            '.site-header__inner{width:100%!important;max-width:100%!important;min-width:0!important;margin:0!important;box-sizing:border-box!important;}',
            '.site-header__inner>.nav-toggle{display:grid!important;}',
            '.header-actions__auth,.header-actions .theme-toggle{display:grid!important;}',
            '.header-actions__admin,.header-actions__cta{display:none!important;}',
            '.main-nav{position:fixed!important;top:var(--ha-header-h,64px)!important;right:0!important;left:auto!important;bottom:0!important;width:min(56vw,260px)!important;max-width:calc(100vw - 18px)!important;height:auto!important;margin:0!important;box-sizing:border-box!important;overflow-x:hidden!important;overflow-y:auto!important;visibility:visible!important;pointer-events:auto!important;z-index:var(--z-drawer,70)!important;display:none!important;transform:none!important;}',
            'html.nav-open .main-nav,body.nav-open .main-nav{display:block!important;}',
            '[dir="ltr"] .main-nav{right:auto!important;left:0!important;}',
            '[dir="ltr"] html.nav-open .main-nav,[dir="ltr"] body.nav-open .main-nav{display:block!important;}',
            '.nav-backdrop{position:fixed!important;top:var(--ha-header-h,64px)!important;right:0!important;left:0!important;bottom:0!important;}',
            '}',
            '@media (max-width:767.98px){',
            '.container{width:100%!important;max-width:100%!important;min-width:0!important;margin-left:0!important;margin-right:0!important;padding-left:max(var(--gutter),env(safe-area-inset-left))!important;padding-right:max(var(--gutter),env(safe-area-inset-right))!important;box-sizing:border-box!important;}',
            '.route-home .hero__grid,.detail-grid,.course-layout,.article__grid,.account-grid,.contact-grid,.contact-hero,.two-col{grid-template-columns:minmax(0,1fr)!important;width:100%!important;max-width:100%!important;min-width:0!important;}',
            '.route-home .grid--2,.route-home .grid--3,.route-home .grid--4{grid-template-columns:minmax(0,1fr)!important;width:100%!important;}',
            '.route-home .hero,.route-home .section,.route-home .topic-strip{width:100%!important;max-width:100%!important;min-width:0!important;}',
            '}',
            '@media (max-width:479.98px){',
            '.main-nav{width:min(56vw,250px)!important;max-width:calc(100vw - 16px)!important;}',
            '.header-actions{gap:1px!important;}',
            '.header-actions .icon-btn{width:40px!important;height:40px!important;min-width:40px!important;min-height:40px!important;}',
            '}',
            '@media (max-width:359.98px){',
            '.main-nav{width:55vw!important;max-width:228px!important;}',
            '.header-actions .icon-btn{width:38px!important;height:38px!important;min-width:38px!important;min-height:38px!important;}',
            '.nav-toggle{width:42px!important;height:42px!important;min-width:42px!important;min-height:42px!important;}',
            '}',
            '@media (min-width:1120px){',
            '.main-nav{position:relative!important;width:auto!important;max-width:none!important;display:block!important;transform:none!important;visibility:visible!important;overflow:visible!important;}',
            '.nav-toggle{display:none!important;}',
            '.nav-backdrop{display:none!important;}',
            '}',
        ].join('');
        document.head.appendChild(style);
    };

    installMobileHardFix();

    try {
        if (window.matchMedia) {
            var mq = window.matchMedia('(prefers-color-scheme: dark)');
            var onChange = function () {
                if (read() === 'auto') root.setAttribute('data-theme', 'auto');
            };
            if (mq.addEventListener) mq.addEventListener('change', onChange);
            else if (mq.addListener) mq.addListener(onChange);
        }
    } catch (e) { /* اختیاری است */ }

    var syncHeaderHeight = function () {
        var header = document.querySelector('[data-header]');
        if (!header) return;
        var height = Math.ceil(header.getBoundingClientRect().height);
        if (height > 0) root.style.setProperty('--ha-header-h', height + 'px');
    };

    var watchHeader = function () {
        syncHeaderHeight();
        var header = document.querySelector('[data-header]');
        if (!header) return;
        if (window.ResizeObserver) {
            var observer = new ResizeObserver(syncHeaderHeight);
            observer.observe(header);
        }
        window.addEventListener('resize', syncHeaderHeight, { passive: true });
        window.addEventListener('orientationchange', syncHeaderHeight, { passive: true });
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', watchHeader, { once: true });
    else watchHeader();
})();