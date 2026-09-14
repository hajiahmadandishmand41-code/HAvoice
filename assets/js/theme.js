/*!
 * HAvoice — تعیینِ حالتِ نمایش پیش از اولین رنگ‌آمیزی
 * سه حالت: light | dark | auto.
 *
 * نکتهٔ معماری: اندازه و وضعیتِ منوی موبایل عمداً در CSS مدیریت می‌شود.
 * theme.js نباید با تزریق CSS روی width/overflow/layout صفحه دخالت کند.
 */
(function () {
    'use strict';

    var KEY = 'ha-theme';
    var VALID = { light: 1, dark: 1, auto: 1 };
    var root = document.documentElement;

    var read = function () {
        try {
            var value = window.localStorage.getItem(KEY);
            return (value && VALID[value]) ? value : 'auto';
        } catch (e) {
            return 'auto';
        }
    };

    root.setAttribute('data-theme', read());

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

    /* فقط ارتفاع واقعی هدر را برای drawer در اختیار CSS می‌گذارد. */
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', watchHeader, { once: true });
    } else {
        watchHeader();
    }
})();