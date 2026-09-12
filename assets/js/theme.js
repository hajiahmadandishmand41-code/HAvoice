/*!
 * HAvoice — تعیینِ حالتِ نمایش پیش از اولین رنگ‌آمیزی
 *
 * چرا یک فایلِ جدا و بدونِ defer؟
 * این اسکریپت باید پیش از رندرِ <body> اجرا شود تا «چشمکِ تم» (FOUC)
 * رخ ندهد. حجمش زیر یک کیلوبایت است، بنابراین هزینه‌ی render-blocking
 * ناچیز است.
 *
 * چرا inline نیست؟
 * تا بتوان Content-Security-Policy را با script-src 'self' و بدونِ
 * 'unsafe-inline' اعمال کرد — یعنی هیچ اسکریپتِ تزریق‌شده‌ای اجرا نمی‌شود.
 *
 * سه حالت پشتیبانی می‌شود: light | dark | auto (پیروی از سیستم).
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
            // localStorage ممکن است در حالتِ خصوصی یا با کوکیِ مسدود
            // در دسترس نباشد؛ در این حالت بی‌صدا به 'auto' برمی‌گردیم.
            return 'auto';
        }
    };

    root.setAttribute('data-theme', read());

    // اگر حالت «خودکار» است و کاربر تمِ سیستم را عوض کند، بدونِ بارگذاریِ
    // مجددِ صفحه به‌روز می‌شویم.
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
                mq.addListener(onChange); // مرورگرهای قدیمی‌تر
            }
        }
    } catch (e) { /* اختیاری است */ }
})();
