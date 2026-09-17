/* ==========================================================================
   HAvoice — اسکریپت اصلی (Vanilla JS، بدون وابستگی)
   بخش‌ها: ابزارها | تم | منو | هدر | reveal | جستجوی زنده | پیشرفت دوره
          | تمرین‌ها (تایمر/مودال/تولیدگر موضوع) | نکته‌ها | فرم تماس | toast
   ========================================================================== */

/* ---------------- ابزارهای خردِ مشترک (Global) ----------------
   این کمک‌تابع‌ها عمداً در سطحِ بالای فایل (بیرون از IIFE) تعریف
   شده‌اند تا هر دو IIFE این فایل — بدنه‌ی اصلی و adminBuilderModule —
   به یک نمونه دسترسی داشته باشند. در اسکریپتِ کلاسیک، «var» در سطحِ
   بالا روی window می‌نشیند؛ پس $/$$/fa عملاً گلوبال‌اند و خطای
   «Uncaught ReferenceError: $ is not defined at adminBuilderModule»
   (گزارشِ Google Search Console از main.js:975) رخ نمی‌دهد.
   توجه: این پروژه jQuery ندارد و $ یعنی querySelector. */
var $ = function (sel, root) { return (root || document).querySelector(sel); };
var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
var FA_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
var fa = function (value) { return String(value).replace(/[0-9]/g, function (d) { return FA_DIGITS[+d]; }); };
var pad = function (n) { return (n < 10 ? '0' : '') + n; };

(function () {
    'use strict';

    /* ابزارهای خرد ($/$$/fa/pad) در سطحِ گلوبالِ همین فایل‌اند (بالا). */

    /* آیکون‌ها در PHP از یک sprite یکتا می‌آیند (includes/icons.php).
       در JS هم به‌جایِ گلیفِ یونیکدِ «✓» — که در برخی فونت‌ها/سیستم‌ها
       ناهمگون رندر می‌شود — همان مسیرِ SVG را می‌سازیم تا وزنِ خط،
       اندازه و رنگ با بقیه‌ی آیکون‌های سایت یکی باشد. */
    var SVG_NS = 'http://www.w3.org/2000/svg';
    var ICON_PATHS = {
        check: ['m4.8 12.6 4.6 4.6L19.2 7.4'],
        copy: null /* از sprite استفاده می‌شود */
    };
    /** ساختِ آیکونِ خطیِ هم‌خانواده با sprite (برای متنِ دکمه‌ها). */
    var makeIcon = function (name, size) {
        var svg = document.createElementNS(SVG_NS, 'svg');
        svg.setAttribute('class', 'ha-icon');
        svg.setAttribute('width', String(size || 16));
        svg.setAttribute('height', String(size || 16));
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        var paths = ICON_PATHS[name] || [];
        for (var i = 0; i < paths.length; i++) {
            var el = document.createElementNS(SVG_NS, 'path');
            el.setAttribute('d', paths[i]);
            svg.appendChild(el);
        }
        return svg;
    };
    /** متنِ یک دکمه را با یک آیکونِ ابتدایی جای‌گزین می‌کند. */
    var setLabel = function (el, text, iconName) {
        if (!el) { return; }
        el.textContent = text;
        if (iconName) {
            var icon = makeIcon(iconName, 15);
            icon.style.marginInlineEnd = '0.4em';
            el.insertBefore(icon, el.firstChild);
        }
    };
    var mmss = function (seconds) {
        var s = Math.max(0, Math.round(seconds));
        return fa(pad(Math.floor(s / 60)) + ':' + pad(s % 60));
    };
    var store = {
        get: function (key, fallback) {
            try {
                var raw = localStorage.getItem(key);
                return raw === null ? fallback : JSON.parse(raw);
            } catch (e) { return fallback; }
        },
        set: function (key, value) {
            try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) {}
        },
        raw: function (key, fallback) {
            try { var v = localStorage.getItem(key); return v === null ? fallback : v; } catch (e) { return fallback; }
        },
        setRaw: function (key, value) {
            try { localStorage.setItem(key, value); } catch (e) {}
        },
        remove: function (key) {
            try { localStorage.removeItem(key); } catch (e) {}
        }
    };

    var config = (function () {
        var el = $('#ha-config');
        try { return el ? JSON.parse(el.textContent) : {}; } catch (e) { return {}; }
    })();

    var normalize = function (text) {
        return String(text)
            .toLowerCase()
            .replace(/ي/g, 'ی').replace(/ك/g, 'ک')
            .replace(/‌/g, ' ')
            .replace(/[^\p{L}\p{N}\s-]/gu, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    };

    var toastEl = $('[data-toast]');
    var toastTimer = null;
    var toast = function (message) {
        if (!toastEl) { return; }
        toastEl.textContent = message;
        toastEl.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.hidden = true; }, 2600);
    };

    /* ---------------- تم روشن / تاریک ---------------- */
    (function themeModule() {
        var root = document.documentElement;
        var button = $('[data-theme-toggle]');
        if (!button) { return; }

        var cycle = { auto: 'light', light: 'dark', dark: 'auto' };
        var label = { auto: 'خودکار سیستم', light: 'روشن', dark: 'تاریک' };

        var sync = function () {
            var mode = root.dataset.theme || 'auto';
            button.setAttribute('title', 'حالت تم: ' + label[mode] + ' (برای تغییر کلیک کنید)');
            // دسترس‌پذیری: وضعیتِ فعلی باید برای صفحه‌خوان هم اعلام شود
            button.setAttribute('aria-label', 'تغییر حالت نمایش، حالتِ کنونی: ' + label[mode]);
        };

        button.addEventListener('click', function () {
            var next = cycle[root.dataset.theme || 'auto'];
            root.dataset.theme = next;
            store.setRaw('ha-theme', next);
            sync();
            toast('حالت نمایش: ' + label[next]);
        });
        sync();
    })();

    /* ---------------- منوی موبایل (کشوی لغزان) ----------------
       وضعیت باز/بسته فقط با کلاسِ nav-open روی <html>/<body> و صفتِ
       aria-expanded دکمه بیان می‌شود؛ هیچ استایلِ درون‌خطی یا
       display/hidden دستی روی خودِ کشو لازم نیست (همه‌اش در CSS است). */
    (function navModule() {
        var toggle = $('[data-nav-toggle]');
        var nav = $('#main-nav');
        if (!toggle || !nav) { return; }

        var backdrop = $('[data-nav-backdrop]');
        var DESKTOP = '(min-width: 1120px)';
        var mq = window.matchMedia(DESKTOP);

        var isOpen = function () { return document.documentElement.classList.contains('nav-open'); };

        var setOpen = function (open) {
            if (isOpen() === open) { return; }
            // در حالتِ دسکتاپ (دکمه پنهان و ناوبری درون‌خطی) کشو معنایی ندارد
            if (open && mq.matches) { return; }
            // کلاس روی html و body هر دو: قفلِ پیمایش در iOS هم تضمین شود
            document.documentElement.classList.toggle('nav-open', open);
            document.body.classList.toggle('nav-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'بستنِ منوی ناوبری' : 'باز و بسته کردن منوی ناوبری');
            if (backdrop) { backdrop.hidden = !open; }
            // دسترس‌پذیری: وقتی کشو باز است، محتوای پشتِ آن نباید خوانده شود
            document.querySelectorAll('main, .site-footer').forEach(function (el) {
                if (open) { el.setAttribute('aria-hidden', 'true'); }
                else { el.removeAttribute('aria-hidden'); }
            });
            if (open) {
                var first = nav.querySelector('a, button');
                if (first) { setTimeout(function () { first.focus(); }, 120); }
            } else {
                toggle.focus();
            }
        };

        toggle.addEventListener('click', function () { setOpen(!isOpen()); });

        if (backdrop) {
            backdrop.addEventListener('click', function () { setOpen(false); });
        }

        nav.addEventListener('click', function (event) {
            if (event.target.closest('a')) { setOpen(false); }
        });

        // کشیدن انگشت روی لایه‌ی تیره معادل کلیکِ بستن است
        if (backdrop) {
            var touchStart = null;
            backdrop.addEventListener('touchstart', function (e) {
                touchStart = e.touches[0] ? { x: e.touches[0].clientX, y: e.touches[0].clientY } : null;
            }, { passive: true });
            backdrop.addEventListener('touchend', function (e) {
                if (!touchStart || !e.changedTouches[0]) { return; }
                var t = e.changedTouches[0];
                var dx = Math.abs(t.clientX - touchStart.x);
                var dy = Math.abs(t.clientY - touchStart.y);
                if (dx < 12 && dy < 12) { setOpen(false); } // تپک واقعی، نه اسکرول
                touchStart = null;
            }, { passive: true });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isOpen()) { setOpen(false); }
        });

        // اگر موقعِ باز بودنِ کشو عرضِ پنجره به دسکتاپ برسد، بسته شود
        var onBreakpoint = function () { if (mq.matches && isOpen()) { setOpen(false); } };
        if (mq.addEventListener) { mq.addEventListener('change', onBreakpoint); }
        else if (mq.addListener) { mq.addListener(onBreakpoint); }

        if (backdrop) { backdrop.hidden = true; }
    })();

    /* ---------------- تأییدِ عملیاتِ خطرناک ----------------
       پیش‌تر فرم‌های حذف در پنل از onsubmit="return confirm(...)" استفاده
       می‌کردند. ولی CSP سایت «script-src 'self'» است و بدونِ 'unsafe-inline'
       همه‌ی event handlerهای درون‌خطی «بلاک» می‌شوند؛ یعنی آن ۹ تأیید هرگز
       نمایش داده نمی‌شدند و حذف بدونِ هیچ پرسشی انجام می‌شد.
       اکنون به‌جایش صفتِ data-confirm و یک delegated listener اینجاست
       (اسکریپتِ بیرونی ⇒ سازگار با CSP). */
    (function confirmModule() {
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form || !form.getAttribute) { return; }
            var message = form.getAttribute('data-confirm');
            if (!message) { return; }
            // پرسشِ بومی؛ اگر کاربر لغو کرد، ارسال متوقف می‌شود
            if (!window.confirm(message)) { event.preventDefault(); }
        }, true);

        // دکمه‌های حذف با data-confirm روی خودِ دکمه
        document.addEventListener('click', function (event) {
            var el = event.target.closest ? event.target.closest('[data-confirm-click]') : null;
            if (!el) { return; }
            if (!window.confirm(el.getAttribute('data-confirm-click'))) { event.preventDefault(); }
        });
    })();

    /* ---------------- ماشه‌ی «بیشتر» (مگامنو) ----------------
       hover و focus-within در CSS مدیریت می‌شوند. این ماژول فقط چیزی را
       اضافه می‌کند که CSS نمی‌تواند: باز/بسته‌شدن با «کلیک و لمس»
       (دستگاه‌های لمسی hover ندارند) و بستن با کلیکِ بیرون. */
    (function navMoreModule() {
        var item = $('.main-nav__item--mega');
        var trigger = $('[data-nav-more]');
        if (!item || !trigger) { return; }

        var isOpen = function () { return item.classList.contains('is-open'); };
        var setOpen = function (open) {
            item.classList.toggle('is-open', open);
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            setOpen(!isOpen());
        });

        // کلیک روی پیوندهای داخلِ پنل ⇒ بستن (و اجازه‌ی پیمایش)
        item.addEventListener('click', function (event) {
            if (event.target.closest('a')) { setOpen(false); }
        });

        document.addEventListener('click', function (event) {
            if (isOpen() && !item.contains(event.target)) { setOpen(false); }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isOpen()) { setOpen(false); trigger.focus(); }
        });

        // در حالتِ کشویِ موبایل، پنل همیشه بازِ درون‌خطی است ⇒ is-open بی‌معناست
        var mq = window.matchMedia('(min-width: 1120px)');
        var sync = function () { if (!mq.matches && isOpen()) { setOpen(false); } };
        if (mq.addEventListener) { mq.addEventListener('change', sync); }
        else if (mq.addListener) { mq.addListener(sync); }
    })();

    /* ---------------- سایدبارِ پنلِ مدیریت ----------------
       پیش‌تر این منطق به‌صورتِ <script> درون‌خطی در pages/admin/_layout_end.php
       بود و چون CSP سایت «script-src 'self'» است (بدونِ 'unsafe-inline')،
       مرورگر آن را بلاک می‌کرد و منوی مدیریت روی موبایل باز نمی‌شد.
       اینجا همان رفتار، ولی از فایلِ مجازِ خارجی. */
    (function adminNavModule() {
        var toggle = $('[data-admin-toggle]');
        var sidebar = $('#admin-sidebar');
        var overlay = $('#admin-overlay');
        if (!toggle || !sidebar) { return; }

        var isOpen = function () { return document.body.classList.contains('admin-nav-open'); };

        var setOpen = function (open) {
            document.body.classList.toggle('admin-nav-open', open);
            document.documentElement.classList.toggle('nav-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (overlay) { overlay.hidden = !open; }
            document.body.classList.toggle('admin-nav-locked', open);
        };

        toggle.addEventListener('click', function () { setOpen(!isOpen()); });
        if (overlay) { overlay.addEventListener('click', function () { setOpen(false); }); }

        sidebar.addEventListener('click', function (event) {
            if (event.target.closest('a')) { setOpen(false); }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isOpen()) { setOpen(false); toggle.focus(); }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 940 && isOpen()) { setOpen(false); }
        });

        setOpen(false);
    })();

    /* ---------------- هدر چسبان + دکمه‌ی بالا ---------------- */
    (function scrollModule() {
        var header = $('[data-header]');
        var toTop = $('[data-to-top]');
        var last = 0;

        var onScroll = function () {
            var y = window.scrollY || window.pageYOffset || 0;
            if (header) { header.classList.toggle('is-stuck', y > 8); }
            if (toTop) { toTop.hidden = y < 600; }
            last = y;
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        if (toTop) {
            toTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: last > 0 ? 'smooth' : 'auto' });
            });
        }
    })();

    /* ---------------- ظاهر شدن تدریجی کارت‌ها ---------------- */
    (function revealModule() {
        var items = $$('.reveal');
        if (!items.length || !('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) {
            items.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry, i) {
                if (!entry.isIntersecting) { return; }
                var el = entry.target;
                setTimeout(function () { el.classList.add('is-visible'); }, Math.min(i * 60, 240));
                io.unobserve(el);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.06 });

        items.forEach(function (el) { io.observe(el); });
    })();

    /* ---------------- جستجوی زنده در فهرست مقاله‌ها ---------------- */
    (function liveFilterModule() {
        var input = $('[data-live-filter]');
        if (!input) { return; }

        var items = $$('[data-filter-item]');
        var counter = $('[data-filter-count]');
        var empty = $('[data-filter-empty]');

        var apply = function () {
            var q = normalize(input.value);
            var words = q.length ? q.split(' ') : [];
            var visible = 0;

            items.forEach(function (item) {
                var card = $('[data-hay]', item) || item;
                var hay = normalize(card.getAttribute('data-hay') || card.textContent || '');
                var match = words.every(function (w) { return hay.indexOf(w) !== -1; });
                item.hidden = !match;
                if (match) { visible++; }
            });

            if (counter) { counter.textContent = fa(visible); }
            if (empty) { empty.hidden = visible !== 0; }
        };

        var timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(apply, 120);
        });
        input.addEventListener('search', apply);
        input.form && input.form.addEventListener('submit', function (e) { e.preventDefault(); });
    })();

    /* ---------------- پیشرفت دوره (localStorage) — scoped به دوره جاری ---------------- */
    /* ---------------- مسیرِ یادگیری (سرور-محور) ----------------
       وضعیتِ درس‌ها و تمرین‌ها سمتِ سرور ذخیره و رندر می‌شود
       (includes/progress.php + pages/progress.php) و همه‌ی دکمه‌های وضعیت
       فرمِ واقعیِ POST هستند؛ پس بدونِ JS هم کامل کار می‌کنند.
       این ماژول فقط سه بهبودِ اختیاری اضافه می‌کند:
         ۱) تأییدِ پیش از «پاک کردنِ پیشرفتِ دوره» (data-confirm)
         ۲) پخشِ ویدیو/پادکست در مودالِ داخلِ سایت (data-media-open)
         ۳) اسکرولِ نرمِ لنگرها با احتسابِ ارتفاعِ هدرِ چسبان (data-scroll-to)
       و هنگامِ ثبتِ وضعیت، پیامِ کوتاهِ تأیید نشان می‌دهد. */
    (function learningModule() {

        /* --- ۱) تأییدِ عملیاتِ مخرب (فرم‌هایی که data-confirm دارند) --- */
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form || !form.getAttribute) { return; }
            var message = form.getAttribute('data-confirm');
            if (!message || form.getAttribute('data-ha-confirmed') === '1') { return; }
            event.preventDefault();
            if (!window.confirm(message)) { return; }
            form.setAttribute('data-ha-confirmed', '1');
            var button = form.querySelector('button[type="submit"]');
            if (button) { button.disabled = true; }
            if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
        });

        /* --- ۲) مودالِ پخشِ رسانه (ویدیو / پادکست / امبدِ مجاز) --- */
        var mediaModal = $('[data-media-modal]');
        var mediaTitle = mediaModal ? $('[data-media-modal-title]', mediaModal) : null;
        var mediaBody  = mediaModal ? $('[data-media-modal-body]', mediaModal) : null;
        var mediaFocus = null;

        /* با خالی‌کردنِ src پخش متوقف می‌شود؛ وگرنه صدای ویدیو پشتِ مودال می‌ماند. */
        var clearMedia = function () {
            if (!mediaBody) { return; }
            $$('video, audio, iframe', mediaBody).forEach(function (el) {
                try { if (el.pause) { el.pause(); } } catch (e) {}
                if (el.tagName === 'IFRAME') { el.src = 'about:blank'; }
                else { el.removeAttribute('src'); try { el.load(); } catch (e) {} }
            });
            while (mediaBody.firstChild) { mediaBody.removeChild(mediaBody.firstChild); }
        };

        var closeMedia = function () {
            if (!mediaModal) { return; }
            clearMedia();
            mediaModal.hidden = true;
            document.body.classList.remove('modal-open');
            if (mediaFocus && mediaFocus.focus) { mediaFocus.focus(); }
            mediaFocus = null;
        };

        var buildPlayer = function (payload) {
            var wrap = document.createElement('div');
            wrap.className = 'media-player';
            var el;
            if (payload.embed) {
                wrap.className += ' media-player--embed';
                el = document.createElement('iframe');
                el.src = payload.embed;
                el.title = payload.title || 'ویدیو';
                el.setAttribute('allowfullscreen', '');
                el.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture');
                el.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
                el.setAttribute('loading', 'lazy');
            } else if (payload.type === 'audio') {
                wrap.className += ' media-player--audio';
                el = document.createElement('audio');
                el.controls = true;
                el.preload = 'auto';
                el.src = payload.src;
                el.setAttribute('aria-label', payload.title || 'فایلِ صوتی');
            } else {
                wrap.className += ' media-player--video';
                el = document.createElement('video');
                el.controls = true;
                el.playsInline = true;
                el.preload = 'metadata';
                el.src = payload.src;
                el.setAttribute('aria-label', payload.title || 'ویدیو');
            }
            wrap.appendChild(el);
            return wrap;
        };

        var openMedia = function (payload, trigger) {
            if (!mediaModal || !mediaBody) { return false; }
            if (!payload || (!payload.embed && !payload.src)) { return false; }
            clearMedia();
            if (mediaTitle) {
                mediaTitle.textContent = payload.title || (payload.type === 'audio' ? 'پادکست' : 'ویدیو');
            }
            mediaBody.appendChild(buildPlayer(payload));
            mediaFocus = trigger || null;
            mediaModal.hidden = false;
            document.body.classList.add('modal-open');
            var closer = $('button[data-media-modal-close]', mediaModal);
            if (closer) { setTimeout(function () { closer.focus(); }, 40); }
            return true;
        };

        document.addEventListener('click', function (event) {
            var target = event.target;
            if (!target || !target.closest) { return; }
            var trigger = target.closest('[data-media-open]');
            if (!trigger || !mediaModal) { return; }
            var payload = null;
            try { payload = JSON.parse(trigger.getAttribute('data-media-payload') || 'null'); } catch (e) { payload = null; }
            /* اگر داده‌ای نبود، پیوندِ واقعی کارِ خودش را می‌کند (fallback بدونِ JS). */
            if (payload && openMedia(payload, trigger)) { event.preventDefault(); }
        });

        if (mediaModal) {
            $$('[data-media-modal-close]', mediaModal).forEach(function (el) {
                el.addEventListener('click', closeMedia);
            });
            document.addEventListener('keydown', function (event) {
                if (mediaModal.hidden) { return; }
                if (event.key === 'Escape') { closeMedia(); return; }
                if (event.key !== 'Tab') { return; }
                var focusables = $$('button, [href], input, select, textarea, video[controls], audio[controls]', mediaModal)
                    .filter(function (el) { return el.offsetParent !== null; });
                if (!focusables.length) { return; }
                var first = focusables[0];
                var last = focusables[focusables.length - 1];
                if (event.shiftKey && document.activeElement === first) { last.focus(); event.preventDefault(); }
                else if (!event.shiftKey && document.activeElement === last) { first.focus(); event.preventDefault(); }
            });
        }

        /* --- ۳) اسکرولِ نرمِ لنگرها با احتسابِ هدرِ چسبان --- */
        var headerOffset = function () {
            var header = $('.site-header');
            var h = header ? header.offsetHeight : 0;
            return Math.max(12, h + 12);
        };
        var scrollToTarget = function (target) {
            if (!target) { return; }
            var top = target.getBoundingClientRect().top + window.pageYOffset - headerOffset();
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: Math.max(0, top), behavior: reduce ? 'auto' : 'smooth' });
            /* دسترس‌پذیری: تمرکز روی مقصد تا صفحه‌خوان بداند کجاییم */
            if (!target.hasAttribute('tabindex')) { target.setAttribute('tabindex', '-1'); }
            window.setTimeout(function () { try { target.focus({ preventScroll: true }); } catch (e) { target.focus(); } }, reduce ? 0 : 420);
        };
        document.addEventListener('click', function (event) {
            var target = event.target;
            if (!target || !target.closest) { return; }
            var link = target.closest('a[data-scroll-to], a[href^="#"]');
            if (!link) { return; }
            var id = link.getAttribute('data-scroll-to') || String(link.getAttribute('href') || '').replace(/^#/, '');
            if (!id) { return; }
            var el = document.getElementById(id);
            if (!el) { return; }              /* لنگرِ نامعتبر: چیزی را خراب نکن */
            event.preventDefault();
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', '#' + id);
            }
            scrollToTarget(el);
        });
        /* اگر صفحه با #لنگر باز شد (مثلاً بازگشت از درس)، همان‌جا برو */
        if (window.location.hash) {
            var initial = document.getElementById(String(window.location.hash).replace(/^#/, ''));
            if (initial) { window.setTimeout(function () { scrollToTarget(initial); }, 120); }
        }

        /* --- ۴) بازخوردِ ثبتِ وضعیت (فرم‌های پیشرفت) --- */
        $$('.progress-form').forEach(function (form) {
            form.addEventListener('submit', function () {
                var button = form.querySelector('button[type="submit"]');
                if (!button || button.getAttribute('data-busy') === '1') { return; }
                button.setAttribute('data-busy', '1');
                button.disabled = true;
                button.classList.add('is-busy');
                window.setTimeout(function () {
                    button.disabled = false;
                    button.classList.remove('is-busy');
                    button.removeAttribute('data-busy');
                }, 6000);
            });
        });
    })();

    /* ---------------- تایمر قابل استفاده‌ی مجدد ---------------- */
    var Timer = function (onTick, onDone) {
        this.total = 0;
        this.left = 0;
        this.running = false;
        this.id = null;
        this.onTick = onTick || function () {};
        this.onDone = onDone || function () {};
    };
    Timer.prototype.set = function (seconds) {
        this.stop();
        this.total = Math.max(0, seconds | 0);
        this.left = this.total;
        this.onTick(this);
    };
    Timer.prototype.start = function () {
        if (this.running || this.left <= 0) { return; }
        var self = this;
        this.running = true;
        this.id = setInterval(function () {
            self.left -= 1;
            if (self.left <= 0) {
                self.left = 0;
                self.stop();
                self.onTick(self);
                self.onDone(self);
                return;
            }
            self.onTick(self);
        }, 1000);
    };
    Timer.prototype.stop = function () {
        this.running = false;
        if (this.id) { clearInterval(this.id); this.id = null; }
    };
    Timer.prototype.toggle = function () { this.running ? this.stop() : this.start(); };
    Timer.prototype.reset = function () { this.stop(); this.left = this.total; this.onTick(this); };

    /* ---------------- تمرین‌ها: مودال، تایمر، موضوع ---------------- */
    (function exerciseModule() {
        var modal = $('[data-modal]');
        var runButtons = $$('[data-run-exercise]');
        if (!modal && !$$('[data-topic-machine]').length) { return; }

        var extraTopics = [];
        try {
            var raw = $('#ha-topics');
            extraTopics = raw ? (JSON.parse(raw.textContent).topics || []) : [];
        } catch (e) { extraTopics = []; }

        var topicBag = [];
        var nextTopic = function (pool) {
            var list = (pool && pool.length) ? pool : extraTopics;
            if (!list.length) { return ''; }
            if (!topicBag.length) {
                topicBag = list.slice();
                for (var i = topicBag.length - 1; i > 0; i--) {
                    var j = Math.floor(Math.random() * (i + 1));
                    var t = topicBag[i]; topicBag[i] = topicBag[j]; topicBag[j] = t;
                }
            }
            return topicBag.pop();
        };

        var runKey = 'ha-exercise-runs';
        var runs = store.get(runKey, {});
        var paintRuns = function () {
            $$('[data-exercise-runs]').forEach(function (el) {
                el.textContent = fa(runs[el.getAttribute('data-exercise-runs')] || 0);
            });
        };
        paintRuns();

        if (!modal) { return; }

        var title = $('[data-modal-title]', modal);
        var goal = $('[data-modal-goal]', modal);
        var steps = $('[data-modal-steps]', modal);
        var success = $('[data-modal-success]', modal);
        var timerBox = $('[data-timer]', modal);
        var digits = $('[data-timer-digits]', modal);
        var phase = $('[data-timer-phase]', modal);
        var bar = $('[data-timer-bar]', modal);
        var toggleBtn = $('[data-timer-toggle]', modal);
        var resetBtn = $('[data-timer-reset]', modal);
        var topicBox = $('[data-modal-topic]', modal);
        var topicText = $('[data-modal-topic-text]', modal);
        var topicNext = $('[data-modal-topic-next]', modal);
        var doneBtn = $('[data-modal-done]', modal);
        var lastFocus = null;
        var currentId = null;
        var currentTopics = [];

        var timer = new Timer(function (t) {
            if (digits) { digits.textContent = mmss(t.left); }
            if (bar) { bar.style.width = (t.total ? ((t.total - t.left) / t.total * 100) : 0) + '%'; }
            if (phase) {
                phase.textContent = t.running ? 'در حال تمرین… نفس‌هایت را بشمار' : (t.left === 0 && t.total ? 'آفرین! تمام شد' : 'آماده‌ی شروع');
            }
            if (toggleBtn) { toggleBtn.textContent = t.running ? 'توقف' : (t.left === t.total ? 'شروع' : 'ادامه'); }
        }, function () {
            toast('تایمر تمرین تمام شد');
        });

        var close = function () {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
            timer.stop();
            if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
        };

        var open = function (payload, trigger) {
            lastFocus = trigger || null;
            currentId = payload.id || null;
            currentTopics = payload.topics || [];

            if (title) { title.textContent = payload.title || 'تمرین'; }
            if (goal) { goal.textContent = payload.goal || ''; }

            if (steps) {
                steps.innerHTML = '';
                (payload.steps || []).forEach(function (step, i) {
                    var li = document.createElement('li');
                    var n = document.createElement('span');
                    n.className = 'exercise-card__n';
                    n.textContent = fa(i + 1);
                    var span = document.createElement('span');
                    span.textContent = step;
                    li.appendChild(n);
                    li.appendChild(span);
                    steps.appendChild(li);
                });
            }

            if (success) {
                success.hidden = !payload.success;
                success.textContent = payload.success ? ('معیار موفقیت: ' + payload.success) : '';
            }

            var seconds = payload.seconds || 0;
            if (timerBox) { timerBox.hidden = seconds <= 0; }
            timer.set(seconds);

            if (topicBox) {
                var hasTopics = currentTopics.length > 0;
                topicBox.hidden = !hasTopics;
                if (hasTopics) { topicText.textContent = nextTopic(currentTopics); }
            }

            modal.hidden = false;
            document.body.classList.add('modal-open');
            if (doneBtn) { setTimeout(function () { doneBtn.focus(); }, 40); }
        };

        runButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var payload = {};
                try { payload = JSON.parse(button.getAttribute('data-exercise-payload') || '{}'); } catch (e) {}
                open(payload, button);
            });
        });

        $$('[data-modal-close]', modal).forEach(function (el) {
            el.addEventListener('click', close);
        });

        if (toggleBtn) { toggleBtn.addEventListener('click', function () { timer.toggle(); }); }
        if (resetBtn) { resetBtn.addEventListener('click', function () { timer.reset(); }); }
        if (topicNext) {
            topicNext.addEventListener('click', function () {
                topicText.textContent = nextTopic(currentTopics) || 'موضوعی تعریف نشده است.';
            });
        }

        if (doneBtn) {
            doneBtn.addEventListener('click', function () {
                if (currentId) {
                    runs[currentId] = (runs[currentId] || 0) + 1;
                    store.set(runKey, runs);
                    paintRuns();
                }
                close();
                toast('عالی بود! تمرین ثبت شد.');
            });
        }

        document.addEventListener('keydown', function (event) {
            if (modal.hidden) { return; }
            if (event.key === 'Escape') { close(); return; }
            if (event.key === 'Tab') {
                var focusables = $$('button, [href], input, textarea, select', modal).filter(function (el) {
                    return el.offsetParent !== null;
                });
                if (!focusables.length) { return; }
                var first = focusables[0];
                var last = focusables[focusables.length - 1];
                if (event.shiftKey && document.activeElement === first) { last.focus(); event.preventDefault(); }
                else if (!event.shiftKey && document.activeElement === last) { first.focus(); event.preventDefault(); }
            }
        });

        /* تولیدگر موضوع بالای صفحه‌ی تمرین‌ها */
        var machine = $('[data-topic-machine]');
        if (machine) {
            var out = $('[data-topic-text]', machine);
            var nextBtn = $('[data-topic-next]', machine);
            if (!nextBtn || !out) { return; }
            nextBtn.addEventListener('click', function () {
                var topic = nextTopic(extraTopics);
                out.textContent = topic || 'موضوعی تعریف نشده است.';
                machine.dataset.topic = topic || '';
            });
            var copy = $('[data-topic-copy]', machine);
            if (copy) {
                copy.addEventListener('click', function () {
                    var text = machine.dataset.topic || (out ? out.textContent : '');
                    if (!text || !navigator.clipboard) { toast('مرورگر اجازه‌ی کپی نمی‌دهد.'); return; }
                    navigator.clipboard.writeText(text).then(function () { toast('موضوع کپی شد'); },
                        function () { toast('کپی نشد.'); });
                });
            }
        }
    })();

    /* ---------------- تایمر کوتاه «تمرین روز» در هیرو ---------------- */
    (function heroPractice() {
        var card = $('[data-practice]');
        if (!card) { return; }
        var out = $('[data-practice-digits]', card);
        var button = $('[data-practice-toggle]', card);
        var total = parseInt(card.getAttribute('data-practice-seconds') || '180', 10) || 180;
        var timer = new Timer(function (t) {
            if (out) { out.textContent = mmss(t.left); }
            card.classList.toggle('is-running', t.running);
            if (button) { button.textContent = t.running ? 'توقف' : (t.left === total ? 'شروع' : 'ادامه'); }
        }, function () { toast('تمرین روز تمام شد'); });
        timer.set(total);
        if (button) { button.addEventListener('click', function () { timer.toggle(); }); }
    })();

    /* ---------------- نکته‌ها: تصادفی + ذخیره ---------------- */
    (function tipsModule() {
        var box = $('[data-random-tip]');
        if (!box) { return; }

        var tipsList = [];
        try {
            var raw = $('#ha-tips');
            tipsList = raw ? JSON.parse(raw.textContent) : [];
        } catch (e) { tipsList = []; }

        if (!tipsList.length) { return; }

        var text = $('[data-random-text]', box);
        var tryLine = $('[data-random-try]', box);
        var last = -1;

        var show = function (tip) {
            text.textContent = tip.text;
            if (tryLine) {
                tryLine.innerHTML = '';
                if (tip.try) {
                    var strong = document.createElement('strong');
                    strong.textContent = 'همین حالا:';
                    tryLine.appendChild(strong);
                    tryLine.appendChild(document.createTextNode(' ' + tip.try));
                    tryLine.hidden = false;
                } else {
                    tryLine.hidden = true;
                }
            }
        };

        var picker = function () {
            var i = Math.floor(Math.random() * tipsList.length);
            if (tipsList.length > 1) { while (i === last) { i = Math.floor(Math.random() * tipsList.length); } }
            last = i;
            return tipsList[i];
        };

        $('[data-random-next]', box).addEventListener('click', function () { show(picker()); });

        /* ذخیره‌ی نکته‌ها */
        var KEY = 'ha-saved-tips';
        var saved = store.get(KEY, []);
        var wrap = $('[data-saved-tips]');
        var list = $('[data-saved-list]');
        var count = $('[data-saved-count]');

        var renderSaved = function () {
            if (!wrap || !list) { return; }
            wrap.hidden = saved.length === 0;
            if (count) { count.textContent = fa(saved.length); }
            list.innerHTML = '';
            saved.forEach(function (item) {
                var li = document.createElement('li');
                var p = document.createElement('span');
                p.textContent = item.text;
                var b = document.createElement('button');
                b.type = 'button';
                b.textContent = 'حذف';
                b.addEventListener('click', function () {
                    saved = saved.filter(function (s) { return s.id !== item.id; });
                    store.set(KEY, saved);
                    renderSaved();
                    updateSaveButton();
                });
                li.appendChild(p);
                li.appendChild(b);
                list.appendChild(li);
            });
        };

        var saveButton = $('[data-random-save]');
        var current = function () {
            return tipsList.filter(function (t) { return t.text === text.textContent; })[0] || null;
        };
        var updateSaveButton = function () {
            if (!saveButton) { return; }
            var tip = current();
            var isSaved = tip && saved.some(function (s) { return s.id === tip.id; });
            setLabel(saveButton, isSaved ? 'ذخیره شد' : 'ذخیره در این مرورگر', isSaved ? 'check' : null);
            saveButton.disabled = !tip;
        };

        if (saveButton) {
            saveButton.addEventListener('click', function () {
                var tip = current();
                if (!tip) { return; }
                if (saved.some(function (s) { return s.id === tip.id; })) {
                    saved = saved.filter(function (s) { return s.id !== tip.id; });
                } else {
                    saved.push({ id: tip.id, text: tip.text });
                }
                store.set(KEY, saved);
                renderSaved();
                updateSaveButton();
            });
        }

        renderSaved();
        updateSaveButton();
        if (text) { /* نکته‌ی اول از سرور آمده؛ فقط دکمه را هم‌راستا می‌کنیم */ }
    })();

    /* ---------------- کپی نشانی مقاله ---------------- */
    (function copyLinkModule() {
        var button = $('[data-copy-link]');
        if (!button) { return; }
        button.addEventListener('click', function () {
            var link = window.location.href;
            if (!navigator.clipboard) { toast('مرورگر اجازه‌ی کپی نمی‌دهد؛ نشانی را دستی کپی کنید.'); return; }
            navigator.clipboard.writeText(link).then(function () { toast('نشانی کپی شد'); },
                function () { toast('کپی نشد.'); });
        });
    })();

    /* ---------------- فرم تماس: شمارنده‌ی نویسه + تأیید سمت کلاینت ---------------- */
    (function contactFormModule() {
        var form = $('[data-contact-form]');
        var area = form && $('[data-counter]', form);
        if (area) {
            var left = $('[data-counter-left]', form);
            var max = parseInt(area.getAttribute('maxlength') || '2000', 10);
            var paint = function () {
                if (left) { left.textContent = fa(Math.max(0, max - area.value.length)); }
            };
            area.addEventListener('input', paint);
            paint();
        }

        if (!form) { return; }
        form.addEventListener('submit', function (event) {
            var bad = [];
            $$('[required]', form).forEach(function (field) {
                if (!field.value.trim()) { bad.push(field); return; }
                if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(field.value.trim())) { bad.push(field); }
            });
            if (area && area.value.trim().length < 20) { bad.push(area); }

            if (bad.length) {
                event.preventDefault();
                bad.forEach(function (f) { f.classList.add('is-invalid'); });
                bad[0].focus();
                toast('فیلدهای مشخص‌شده را کامل کنید.');
            }
        });

        form.addEventListener('input', function (event) {
            if (event.target.classList) { event.target.classList.remove('is-invalid'); }
        });
    })();
})();

    /* ---------------- سازنده‌ی دوره در پنلِ مدیریت ----------------
       جابه‌جاییِ مرحله/درس با دکمه‌های بالا و پایین و «افزودنِ درس».
       بدونِ JS هم کار می‌کند: مدیر عددِ «ترتیب» را می‌نویسد و برایِ درسِ
       تازه، ردیف‌های خالیِ آماده در فرم هست. این ماژول فقط همان کار را
       سریع‌تر می‌کند و در پایان، عددهای «ترتیب» را از نو می‌نویسد تا
       سرور همان چیدمانی را ذخیره کند که مدیر می‌بیند. */
    (function adminBuilderModule() {
        var builder = $('[data-builder]');
        if (!builder) { return; }

        var renumber = function (container) {
            if (!container) { return; }
            var items = $$(':scope > [data-sort-item]', container).filter(function (el) { return !el.hidden && !el.hasAttribute('data-lesson-template'); });
            items.forEach(function (item, index) {
                var input = $('[data-sort-input]', item);
                if (input) { input.value = String(index + 1); }
                var badge = $('.builder__n', item);
                if (badge && item.classList.contains('builder__lesson')) { badge.textContent = fa(index + 1); }
            });
        };

        var flash = function (item) {
            item.classList.remove('is-moved');
            /* restart animation */
            void item.offsetWidth;
            item.classList.add('is-moved');
        };

        var listOf = function (item) {
            var parent = item.parentElement;
            while (parent && !parent.hasAttribute('data-sortable') && parent !== builder) { parent = parent.parentElement; }
            return parent;
        };

        var move = function (item, direction) {
            var list = listOf(item);
            if (!list) { return; }
            var siblings = $$(':scope > [data-sort-item]', list).filter(function (el) { return !el.hidden && !el.hasAttribute('data-lesson-template'); });
            var index = siblings.indexOf(item);
            if (index === -1) { return; }
            var target = index + direction;
            if (target < 0 || target >= siblings.length) { return; }
            if (direction < 0) {
                list.insertBefore(item, siblings[target]);
            } else {
                list.insertBefore(siblings[target], item);
            }
            renumber(list);
            flash(item);
            item.scrollIntoView({ block: 'nearest', behavior: window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        };

        builder.addEventListener('click', function (event) {
            var button = event.target && event.target.closest ? event.target.closest('button') : null;
            if (!button) { return; }
            var item = button.closest('[data-sort-item]');

            if (button.hasAttribute('data-sort-up') && item) { move(item, -1); return; }
            if (button.hasAttribute('data-sort-down') && item) { move(item, 1); return; }

            if (button.hasAttribute('data-sort-add')) {
                var lessonsWrap = button.parentElement;
                var template = lessonsWrap ? $('[data-lesson-template]', lessonsWrap) : null;
                if (!lessonsWrap || !template) { return; }
                /* بزرگ‌ترین شماره‌ی ردیفِ موجود + ۱ تا نامِ فیلدها تکراری نشود */
                var maxIndex = 0;
                $$('input[name], textarea[name], select[name]', lessonsWrap).forEach(function (field) {
                    var match = /\[lessons\]\[(\d+)\]/.exec(field.getAttribute('name') || '');
                    if (match) { maxIndex = Math.max(maxIndex, parseInt(match[1], 10)); }
                });
                var clone = template.cloneNode(true);
                clone.removeAttribute('hidden');
                clone.removeAttribute('data-lesson-template');
                $$('[name]', clone).forEach(function (field) {
                    field.setAttribute('name', field.getAttribute('name').replace('__IDX__', String(maxIndex + 1)));
                });
                var addButtons = $('[data-sort-add]', lessonsWrap);
                lessonsWrap.insertBefore(clone, addButtons || null);
                renumber(lessonsWrap);
                flash(clone);
                var firstInput = $('input[type="text"]', clone);
                if (firstInput) { firstInput.focus(); }
            }
        });

        /* عنوانِ درس/مرحله در سربرگ زنده تازه می‌شود تا گم نشود */
        builder.addEventListener('input', function (event) {
            var field = event.target;
            if (!field || !field.getAttribute) { return; }
            var name = field.getAttribute('name') || '';
            if (!/\]\[title\]$/.test(name)) { return; }
            var item = field.closest('[data-sort-item]');
            if (!item) { return; }
            var label = $('.builder__lesson-title, .builder__legend-title', item);
            if (label && field.value.trim() !== '') { label.textContent = field.value.trim(); }
        });

        /* ترتیبِ اولیه بر اساسِ عددهایِ خودِ فرم */
        $$('[data-sortable]', builder).forEach(renumber);
        renumber(builder);
    })();


/* ==========================================================================
   HAvoice — Media Player حرفه‌ای: Progressive Streaming + Lazy Load
   - فقط وقتی دیده شد یا کلیک شد، src واقعی از data-src لود می‌شود
   - IntersectionObserver برای جلوگیری از دانلود اضافی
   - Loading / Buffering / Error استیت‌های زیبا
   - سرعت، ولوم، seek، بافر، mute، fullscreen
   - سازگار با اینترنت ضعیف، شروع سریع، بدون شکستن روت‌ها
   ========================================================================== */
(function haMediaModule() {
    'use strict';
    var SELECTOR = '[data-ha-player]';
    var players = [];
    var current = null; // تنها یک پلیر همزمان پخش شود (اختیاری UX)

    var faDigits = function(s){ try{ return window.fa ? window.fa(s) : s; } catch(e){ return s; } };

    function fmtTime(sec){
        if (!isFinite(sec) || sec < 0) sec = 0;
        sec = Math.floor(sec);
        var m = Math.floor(sec / 60);
        var s = sec % 60;
        var pad = function(n){ return n<10?'0'+n:''+n; };
        var txt = pad(m)+':'+pad(s);
        return typeof faDigits === 'function' ? faDigits(txt) : txt;
    }

    function ensureSrc(media){
        if (!media) return false;
        if (media.src && media.src !== '' && !media.src.endsWith('about:blank')) return true;
        var ds = media.getAttribute('data-src');
        if (!ds) return false;
        media.src = ds;
        // keep data-src for retry
        return true;
    }

    function setState(root, state){
        if (!root) return;
        root.setAttribute('data-ha-state', state || '');
        // for CSS compat: also add class is-*
        root.classList.remove('is-loading','is-buffering','is-error','is-playing','is-paused');
        if (state) root.classList.add('is-'+state);
    }

    function createPlayer(root){
        if (root.__haBound) return root.__haBound;
        var media = root.querySelector('[data-ha-media]');
        var type = root.getAttribute('data-ha-type') || (media ? media.tagName.toLowerCase() : 'audio');
        if (root.classList.contains('is-embed')) {
            // embed already handled by modal; still lazy maybe
            return null;
        }
        if (!media) return null;

        var bigPlay = root.querySelector('[data-ha-bigplay]');
        var playPause = root.querySelector('[data-ha-playpause]');
        var seekWrap = root.querySelector('[data-ha-seek]');
        var fill = root.querySelector('[data-ha-fill]');
        var bufferedEl = root.querySelector('[data-ha-buffered]');
        var thumb = root.querySelector('[data-ha-thumb]');
        var timeEl = root.querySelector('[data-ha-time]');
        var muteBtn = root.querySelector('[data-ha-mute]');
        var volTrack = root.querySelector('[data-ha-vol-track]');
        var volFill = root.querySelector('[data-ha-vol-fill]');
        var speedBtn = root.querySelector('[data-ha-speed]');
        var retryBtn = root.querySelector('[data-ha-retry]');
        var fullscreenBtn = root.querySelector('[data-ha-fullscreen]');
        var errorText = root.querySelector('[data-ha-error-text]');

        var state = {
            root: root,
            media: media,
            loaded: false,
            seeking: false,
            speeds: [1, 1.25, 1.5, 1.75, 2],
            speedIdx: 0
        };

        function loadIfNeeded(){
            if (state.loaded) return;
            if (!ensureSrc(media)){
                return;
            }
            state.loaded = true;
            setState(root, 'loading');
            // start loading metadata only; browser will progressive stream
            media.load();
        }

        function updateBuffered(){
            if (!media.buffered || !media.duration) return;
            try{
                var dur = media.duration;
                if (!isFinite(dur) || dur===0) return;
                var end = 0;
                for (var i=0;i<media.buffered.length;i++){
                    if (media.buffered.start(i) <= media.currentTime){
                        end = Math.max(end, media.buffered.end(i));
                    }
                }
                // also take last buffered end for overall
                if (media.buffered.length){
                    var lastEnd = media.buffered.end(media.buffered.length-1);
                    end = Math.max(end, lastEnd);
                }
                var pct = Math.min(100, (end / dur)*100);
                if (bufferedEl) bufferedEl.style.width = pct + '%';
                try{ root.style.setProperty('--hp-buffered', pct + '%'); }catch(e){}
            }catch(e){}
        }

        function updateProgress(){
            var dur = media.duration;
            var cur = media.currentTime || 0;
            if (!isFinite(dur) || dur===0) {
                if (fill) fill.style.width = '0%';
                if (thumb) thumb.style.left = '0%';
                try{ root.style.setProperty('--hp-progress','0%'); }catch(e){}
                if (timeEl) timeEl.textContent = fmtTime(cur) + ' / --:--';
                return;
            }
            var pct = (cur / dur)*100;
            if (fill) fill.style.width = pct + '%';
            if (thumb) thumb.style.left = pct + '%';
            try{ root.style.setProperty('--hp-progress', pct + '%'); }catch(e){}
            if (timeEl) timeEl.textContent = fmtTime(cur) + ' / ' + fmtTime(dur);
            updateBuffered();
        }

        function play(){
            loadIfNeeded();
            // pause other
            if (current && current !== state && current.media && !current.media.paused){
                try{ current.media.pause(); }catch(e){}
            }
            var p = media.play();
            if (p && p.catch){
                p.catch(function(err){
                    // maybe autoplay blocked; show paused state
                    setState(root, 'paused');
                });
            }
        }
        function pause(){
            try{ media.pause(); }catch(e){}
        }

        // events
        if (bigPlay){
            bigPlay.addEventListener('click', function(){
                loadIfNeeded();
                if (media.paused) play(); else pause();
            });
        }
        if (playPause){
            playPause.addEventListener('click', function(){
                loadIfNeeded();
                if (media.paused) play(); else pause();
            });
        }

        // retry
        if (retryBtn){
            retryBtn.addEventListener('click', function(){
                setState(root, 'loading');
                state.loaded = false;
                // keep data-src
                media.removeAttribute('src');
                try{ media.load(); }catch(e){}
                loadIfNeeded();
                setTimeout(function(){ play(); }, 120);
            });
        }

        // speed
        if (speedBtn){
            speedBtn.addEventListener('click', function(){
                state.speedIdx = (state.speedIdx + 1) % state.speeds.length;
                var sp = state.speeds[state.speedIdx];
                media.playbackRate = sp;
                speedBtn.textContent = (sp===1?'۱×': (String(sp).replace('.', '٫') + '×'));
                // convert to fa if possible
                try{ if (window.fa) speedBtn.textContent = window.fa(speedBtn.textContent); }catch(e){}
            });
        }

        // mute
        if (muteBtn){
            muteBtn.addEventListener('click', function(){
                media.muted = !media.muted;
                root.classList.toggle('is-muted', media.muted);
                if (volFill){
                    volFill.style.width = media.muted ? '0%' : ((media.volume*100)+'%');
                }
            });
        }

        // volume track
        if (volTrack){
            var setVolFromEvent = function(e){
                var rect = volTrack.getBoundingClientRect();
                var x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
                var pct = Math.max(0, Math.min(1, x / rect.width));
                media.volume = pct;
                media.muted = pct===0 ? false : media.muted; // keep muted false if >0
                if (volFill) volFill.style.width = (pct*100)+'%';
                if (pct>0) media.muted = false;
                root.classList.toggle('is-muted', media.muted);
            };
            volTrack.addEventListener('click', setVolFromEvent);
            volTrack.addEventListener('touchstart', function(e){ setVolFromEvent(e); }, {passive:true});
        }

        // seek
        if (seekWrap){
            var seekFromEvent = function(e){
                var rect = seekWrap.getBoundingClientRect();
                var x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left;
                var pct = Math.max(0, Math.min(1, x / rect.width));
                var dur = media.duration;
                if (!isFinite(dur) || dur===0) return;
                media.currentTime = pct * dur;
                updateProgress();
            };
            var onMove = function(e){ if (state.seeking) seekFromEvent(e); };
            var onUp = function(){ state.seeking = false; document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); document.removeEventListener('touchmove', onMove); document.removeEventListener('touchend', onUp); };

            seekWrap.addEventListener('mousedown', function(e){
                state.seeking = true;
                seekFromEvent(e);
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });
            seekWrap.addEventListener('touchstart', function(e){
                state.seeking = true;
                seekFromEvent(e);
                document.addEventListener('touchmove', onMove, {passive:true});
                document.addEventListener('touchend', onUp);
            }, {passive:true});
            seekWrap.addEventListener('click', function(e){
                // if not dragging
                if (!state.seeking) seekFromEvent(e);
            });
        }

        // fullscreen for video
        if (fullscreenBtn && media.tagName.toLowerCase()==='video'){
            fullscreenBtn.addEventListener('click', function(){
                try{
                    if (document.fullscreenElement) { document.exitFullscreen(); }
                    else {
                        if (root.requestFullscreen) root.requestFullscreen();
                        else if (media.requestFullscreen) media.requestFullscreen();
                        else if (media.webkitEnterFullscreen) media.webkitEnterFullscreen();
                    }
                }catch(e){}
            });
        }

        // media events -> states
        media.addEventListener('loadstart', function(){ setState(root, 'loading'); });
        media.addEventListener('loadedmetadata', function(){
            setState(root, media.paused ? 'paused' : 'playing');
            updateProgress();
        });
        media.addEventListener('canplay', function(){
            if (root.getAttribute('data-ha-state')==='loading') setState(root, 'paused');
            updateBuffered();
        });
        media.addEventListener('canplaythrough', function(){ updateBuffered(); });
        media.addEventListener('progress', updateBuffered);
        media.addEventListener('timeupdate', updateProgress);
        media.addEventListener('waiting', function(){ setState(root, 'buffering'); });
        media.addEventListener('playing', function(){
            setState(root, 'playing');
            current = state;
            root.classList.add('is-playing');
        });
        media.addEventListener('pause', function(){
            if (media.ended) return;
            setState(root, 'paused');
            root.classList.remove('is-playing');
        });
        media.addEventListener('ended', function(){
            setState(root, 'paused');
            root.classList.remove('is-playing');
            if (fill) fill.style.width = '100%';
        });
        media.addEventListener('error', function(){
            var msg = 'فایل بارگذاری نشد. اینترنت را بررسی کنید.';
            try{
                var err = media.error;
                if (err){
                    if (err.code===2) msg='خطای شبکه. دوباره تلاش کنید.';
                    else if (err.code===3) msg='فایل خراب است یا فرمت پشتیبانی نمی‌شود.';
                    else if (err.code===4) msg='فرمت پشتیبانی نمی‌شود.';
                }
            }catch(e){}
            if (errorText) errorText.textContent = msg;
            setState(root, 'error');
        });
        media.addEventListener('stalled', function(){ setState(root, 'buffering'); });

        // initial volume fill
        if (volFill){
            volFill.style.width = (media.volume*100)+'%';
        }

        root.__haBound = state;
        players.push(state);
        return state;
    }

    function initAll(){
        var nodes = document.querySelectorAll(SELECTOR);
        nodes.forEach(function(n){ createPlayer(n); });
    }

    // Lazy: only load when visible or interacted
    function initLazy(){
        var nodes = document.querySelectorAll(SELECTOR + ':not(.is-embed)');
        if (!('IntersectionObserver' in window) || !nodes.length){
            // fallback: init all but not load src
            nodes.forEach(function(n){
                createPlayer(n);
                // do not load yet; only on click will load
            });
            return;
        }
        var io = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if (!entry.isIntersecting) return;
                var root = entry.target;
                var st = createPlayer(root);
                // do NOT auto-load src; just bind. But for video we want metadata for fast start? We'll preload metadata only if near viewport and connection good
                if (st && !st.loaded){
                    var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                    var saveData = conn && conn.saveData;
                    var effective = conn && conn.effectiveType;
                    var isSlow = saveData || (effective && /2g|slow/.test(effective));
                    if (!isSlow){
                        // load metadata only to get duration quickly, not full file
                        var media = st.media;
                        if (media && media.preload!=='none'){
                            // for audio we keep none until play to avoid extra download
                        }
                        // For video, allow metadata preload if browser wants
                        if (root.getAttribute('data-ha-type')==='video' && media){
                            if (!media.getAttribute('data-src')) return;
                            // set src only for metadata if not yet
                            // Actually we defer full load until play, but metadata is cheap via preload=metadata
                            // So we ensure src now for video to get quick poster->first frame
                            // To respect "load only required", we only set src when user is likely to play: when intersecting 50%
                            if (entry.intersectionRatio > 0.5){
                                // still don't auto play, just prepare metadata
                                if (!st.loaded){
                                    ensureSrc(media);
                                    st.loaded = true;
                                    try{ media.load(); }catch(e){}
                                }
                            }
                        }
                    }
                }
                // once bound, unobserve
                io.unobserve(root);
            });
        }, { rootMargin: '200px 0px', threshold: [0, 0.25, 0.5] });

        nodes.forEach(function(n){ io.observe(n); });

        // Also: click anywhere on player before bound should bind immediately
        document.addEventListener('click', function(e){
            var root = e.target.closest ? e.target.closest(SELECTOR) : null;
            if (!root) return;
            var st = root.__haBound || createPlayer(root);
            if (st && !st.loaded){
                // if user clicked, load now
                st.loaded = false; // force load
                // createPlayer's loadIfNeeded will be triggered by play handler, but ensure
            }
        }, true);
    }

    // Enhance modal players as well (when modal opens)
    document.addEventListener('click', function(e){
        var trigger = e.target.closest ? e.target.closest('[data-media-open]') : null;
        if (!trigger) return;
        // modal will be built by learningModule; we need to enhance its inner media after a tick
        setTimeout(function(){
            var modalBody = document.querySelector('[data-media-modal-body]');
            if (!modalBody) return;
            var m = modalBody.querySelector('video, audio');
            if (!m) return;
            // if modal media has no ha-player wrapper, wrap behavior
            // Add basic controls already exist, but add buffering detection
            m.addEventListener('waiting', function(){ m.setAttribute('data-state','buffering'); });
            m.addEventListener('playing', function(){ m.removeAttribute('data-state'); });
        }, 200);
    });

    // Boot
    if (document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', function(){ initAll(); initLazy(); });
    } else {
        initAll(); initLazy();
    }

    // expose for debugging / console test
    window.HAMedia = {
        players: players,
        fmtTime: fmtTime
    };
})();


/* ----------------------------------------------------------------------
   الحاقاتِ سبک — فقط رفتارهایی که به CSS/دسترس‌پذیری مربوط می‌شوند.
   ---------------------------------------------------------------------- */
(function () {
    'use strict';
    /* کاهشِ حرکت: همان‌طور که CSS با prefers-reduced-motion رفتار می‌کند،
       منحنیِ حرکتِ JS هم خطی می‌شود تا حسِ «پرش» نداشته باشد. */
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.style.setProperty('--ease', 'linear');
    }
})();
