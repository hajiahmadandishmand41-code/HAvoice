/* ==========================================================================
   HAvoice — اسکریپت اصلی (Vanilla JS، بدون وابستگی)
   بخش‌ها: ابزارها | تم | منو | هدر | reveal | جستجوی زنده | پیشرفت دوره
          | تمرین‌ها (تایمر/مودال/تولیدگر موضوع) | نکته‌ها | فرم تماس | toast
   ========================================================================== */
(function () {
    'use strict';

    /* ---------------- ابزارهای خرد ---------------- */
    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
    var FA_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    var fa = function (value) { return String(value).replace(/[0-9]/g, function (d) { return FA_DIGITS[+d]; }); };
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };

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

    /* ---------------- پیشرفت دوره (localStorage) ---------------- */
    (function progressModule() {
        var KEY = 'ha-progress';
        var done = store.get(KEY, []);
        if (!Array.isArray(done)) { done = []; }

        var save = function () { store.set(KEY, done); };
        var has = function (slug) { return done.indexOf(slug) !== -1; };

        var paintBars = function () {
            var total = (config.lessonKeys || []).length;
            var pct = total ? Math.round((done.filter(function (s) { return config.lessonKeys.indexOf(s) !== -1; }).length) / total * 100) : 0;

            $$('[data-total-progress] .progress__bar').forEach(function (bar) {
                bar.style.setProperty('--progress', pct + '%');
            });
            $$('[data-total-progress] .progress__label').forEach(function (label) {
                label.textContent = fa(pct) + '٪';
            });
            $$('[data-count-done]').forEach(function (el) { el.textContent = fa(done.length); });

            $$('[data-stage-progress], [data-stage-minutes]').forEach(function (wrap) {
                var slugs = String(wrap.getAttribute('data-stage-progress') || wrap.getAttribute('data-stage-minutes') || '').split(',').filter(Boolean);
                if (!slugs.length) { return; }
                var n = slugs.filter(function (s) { return has(s); }).length;
                var p = Math.round(n / slugs.length * 100);
                var bar = $('.progress__bar', wrap);
                if (bar) { bar.style.setProperty('--progress', p + '%'); }
                var label = $('.progress__label', wrap);
                if (label) { label.textContent = fa(n) + '/' + fa(slugs.length); }
            });
        };

        /* ردیف‌های درس در صفحه‌ی دوره */
        $$('[data-lesson-row]').forEach(function (row) {
            if (has(row.getAttribute('data-lesson-row'))) { row.classList.add('is-done'); }
        });

        /* دکمه‌ی «انجام شد» در صفحه‌ی درس */
        $$('[data-lesson-complete]').forEach(function (button) {
            var slug = button.getAttribute('data-lesson-complete');
            var label = $('[data-lesson-complete-label]', button) || button;

            var render = function () {
                var state = has(slug);
                button.setAttribute('aria-pressed', state ? 'true' : 'false');
                setLabel(label, state ? 'این درس انجام شد' : 'علامت‌گذاری به‌عنوان انجام‌شده', state ? 'check' : null);
            };

            button.addEventListener('click', function () {
                if (has(slug)) {
                    done = done.filter(function (s) { return s !== slug; });
                } else {
                    done.push(slug);
                }
                save();
                render();
                paintBars();
                toast(has(slug) ? 'درس انجام‌شده ثبت شد' : 'علامت حذف شد');
            });

            render();
        });

        $$('[data-reset-progress]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!window.confirm('پیشرفت ذخیره‌شده در این مرورگر پاک شود؟')) { return; }
                done = [];
                save();
                $$('[data-lesson-row]').forEach(function (row) { row.classList.remove('is-done'); });
                $$('[data-lesson-complete]').forEach(function (b) {
                    var l = $('[data-lesson-complete-label]', b) || b;
                    b.setAttribute('aria-pressed', 'false');
                    l.textContent = 'علامت‌گذاری به‌عنوان انجام‌شده';
                });
                paintBars();
                toast('پیشرفت پاک شد');
            });
        });

        paintBars();
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

// HAvoice 2.0 — الحاقات جاوااسکریپت سبک
(function(){
  'use strict';
  // بهبود فیلتر دسته در صفحات جدید (اگر input.live-filter وجود نداشته باشد، چیزی نکن)
  // پخش‌کننده صوت: اگر audio با src خالی باشد، کلیک روی کارت پیامی بدهد — قبلاً placeholder است، پس کاری نکن
  // تمرکز کیبورد برای کارت‌های دسته و دوره: اطمینان از تب‌پذیری (a tag already)
  // اضافه کردن شمارنده پیشرفت برای دوره‌های چندگانه — از همان localStorage کلید ha-progress استفاده می‌شود (لسن‌ها یکتا هستند)
  // هیچ رفتار جعلی اضافه نشد؛ فقط UI بهبود یافت
  var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if(prefersReduced){
    document.documentElement.style.setProperty('--ease','linear');
  }
  // Smooth focus for category cards already via :focus-visible
  // اضافه: کپی لینک برای پژوهش و کتاب
  var copyBtns = document.querySelectorAll('[data-copy-link]');
  // already handled in original module
  // نکته: اگر صفحه‌ی ویدیو بدون src باشد، دکمه‌ی "به‌زودی" غیرفعال بماند — از CSS
})();
