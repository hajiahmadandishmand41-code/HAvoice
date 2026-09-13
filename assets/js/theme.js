/*!
 * HAvoice — تعیینِ حالتِ نمایش پیش از اولین رنگ‌آمیزی
 *
 * سه حالت پشتیبانی می‌شود: light | dark | auto.
 * این فایل همچنین لایه‌ی واکنش‌گرای یکپارچه‌ی موبایل را بارگذاری می‌کند
 * و ارتفاع واقعی هدر را در یک CSS custom property نگه می‌دارد تا drawer
 * به‌جای حدسِ 64px دقیقاً زیر هدر قرار بگیرد.
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

    try {
        if (window.matchMedia) {
            var mq = window.matchMedia('(prefers-color-scheme: dark)');
            var onChange = function () {
                if (read() === 'auto') {
                    root.setAttribute('data-theme', 'auto');
                }
            };
            if (mq.addEventListener) {
                mq.addEventListener('change', onChange);
            } else if (mq.addListener) {
                mq.addListener(onChange);
            }
        }
    } catch (e) { /* اختیاری است */ }

    /* ----------------------------------------------------------------------
       Responsive shell layer
       ---------------------------------------------------------------------- */
    var loadMobileStyles = function () {
        var script = document.currentScript;
        if (!script || !script.src || !document.head) { return; }
        if (document.querySelector('link[data-ha-mobile-layout]')) { return; }

        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = new URL('../css/mobile-layout.css', script.src).href;
        link.setAttribute('data-ha-mobile-layout', '');
        document.head.appendChild(link);
    };

    loadMobileStyles();

    /* Header height is content-dependent on small screens, especially with
       font rendering and narrow widths. Feed the measured value to CSS so the
       drawer starts exactly below the real header and fills the remaining
       dynamic viewport (100dvh) without clipping. */
    var syncHeaderHeight = function () {
        var header = document.querySelector('[data-header]');
        if (!header) { return; }
        var height = Math.ceil(header.getBoundingClientRect().height);
        if (height > 0) {
            root.style.setProperty('--ha-header-h', height + 'px');
        }
    };

    var watchHeader = function () {
        syncHeaderHeight();
        var header = document.querySelector('[data-header]');
        if (!header) { return; }

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
