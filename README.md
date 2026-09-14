# های‌ویس — HAvoice

وب‌سایت آموزشی **فن بیان، سخنوری و مهارت‌های ارتباطی** — با PHP خام، HTML5، CSS3 و JavaScript خالص.
بدون فریم‌ورک، بدون دیتابیس، بدون Composer؛ آماده‌ی میزبانی روی **InfinityFree** و هر هاست PHP دیگری.

---

## ۱) ویژگی‌ها

| بخش | مسیر | توضیح |
|---|---|---|
| صفحه‌ی اصلی | `index.php` (مسیر `home`) | هیرو، آمار پویا، نمای مسیر دوره، تازه‌ترین مقاله‌ها، تمرین‌ها، نکته‌ها، دعوت به اقدام |
| مسیر آموزشی مرحله‌ای | `index.php?p=course` | ۳ مرحله / ۹ درس، نوار پیشرفت، «چطور پیش برویم» |
| صفحه‌ی درس | `index.php?p=lesson&slug=…` | محتوای درس + جعبه‌ی تمرین + دکمه‌ی «انجام شد» + درس قبلی/بعدی |
| مقاله‌ها | `index.php?p=articles` | فیلتر دسته (سمت سرور) + جستجوی زنده (سمت مرورگر) + مقاله‌ی ویژه |
| صفحه‌ی مقاله | `index.php?p=article&slug=…` | تایپوگرافی کامل، برچسب‌ها، مقاله‌های مرتبط، کپی نشانی |
| تمرین‌های عملی | `index.php?p=exercises` | ۸ تمرین با **تایمر**، **موضوع تصادفی**، چک‌لیست و شمارنده‌ی اجرا |
| نکته‌های کوتاه | `index.php?p=tips` | ۱۶ نکته، فیلتر موضوع، نکته‌ی تصادفی، ذخیره در مرورگر |
| درباره ما | `index.php?p=about` | مأموریت، ارزش‌ها، مخاطب، سؤالات متداول (آکاردئون) |
| تماس با ما | `index.php?p=contact` | فرم با CSRF + honeypot + محدودیت نرخ + ذخیره در فایل |
| جستجوی مقالات | `index.php?p=search&q=…` | جستجوی فارسی‌پسند روی عنوان/متن/برچسب + درس‌های مرتبط |
| ۴۰۴ | هر مسیر نامعتبر | با کد وضعیت ۴۰۴ واقعی و پیشنهاد محتوا |

* کاملاً **RTL** و **واکنش‌گرا** (موبایل، تبلت، دسکتاپ)، منوی همبرگری، حالت **تاریک/روشن/خودکار**.
* پیشرفت درس‌ها و نکته‌های ذخیره‌شده در `localStorage` می‌ماند؛ هیچ اطلاعات شخصی به سرور فرستاده نمی‌شود.
* دسترس‌پذیری: `skip-link`، `aria-*`، سلسله‌مراتب درست تگ‌ها، کنتراست مناسب، احترام به `prefers-reduced-motion` و کارکرد کامل فرم‌ها بدون JavaScript.

---

## ۲) ساختار فایل‌ها

```
HAvoice/
├── index.php                  نقطه‌ی ورود (هاست همین فایل را در ریشه باز می‌کند)
├── sitemap.php                نقشه‌ی سایت XML با نشانی مطلق + lastmod
├── robots.php                 robots.txt پویا (خط Sitemap با دامنه‌ی واقعی)
├── robots.txt                 نسخه‌ی ثابتِ پشتیبان (وقتی mod_rewrite نباشد)
├── .htaccess                  امنیت + فشرده‌سازی + 404 + نگاشت robots/sitemap + URL کوتاه (اختیاری)
├── config/
│   └── config.php             تنظیمات: نام، ایمیل، URL کوتاه، محدودیت فرم، حالت توسعه
├── includes/
│   ├── bootstrap.php          مسیریابی، سرصفحه‌های امنیتی، جلسه، رندر
│   ├── helpers.php            escape، url، داده‌ها، جستجو، CSRF، رندر بلوک‌ها
│   ├── content.php            توابع دوره/درس/مقاله/تمرین (لایه‌ی محتوا)
│   ├── ui.php                 اجزای مشترک: کارت مقاله، نکته، تمرین، نوار پیشرفت…
│   ├── icons.php              مجموعه آیکون SVG محلی + چاپ sprite (جایگزین ایموجی)
│   ├── meta.php               عنوان/توضیح/canonical/JSON-LD هر صفحه
│   ├── header.php             <head> + هدر + ناوبری + بنر صفحه
│   ├── footer.php             پاورقی + تنظیمات JS
│   └── handlers/contact.php   پردازش امن فرم تماس (اعتبارسنجی، ذخیره، محدودیت نرخ)
├── pages/
│   ├── home.php  course.php  lesson.php
│   ├── articles.php  article.php
│   ├── exercises.php  tips.php
│   └── about.php  contact.php  search.php  404.php
├── data/                      محتوا (آرایه‌ی PHP؛ بدون دیتابیس)
│   ├── site.php               متن‌های ثابت، درباره ما، پاورقی، سؤالات متداول
│   ├── course.php             مرحله‌ها و درس‌ها
│   ├── articles.php           مقاله‌ها
│   ├── exercises.php          تمرین‌ها
│   └── tips.php               نکته‌ها
├── assets/
│   ├── css/style.css          کل استایل (توکن‌های رنگی WCAG، RTL، حالت تاریک، چاپ)
│   ├── js/main.js             تم، منو، پیشرفت، تایمر، مودال، جستجوی زنده، فرم
│   ├── js/theme.js            تعیین حالت تم پیش از رنگ‌آمیزی (بدون فلاش؛ سازگار با CSP)
│   ├── fonts/vazirmatn-var.woff2   فونت وزیرمتنِ متغیر، میزبانیِ محلی (+ OFL.txt)
│   ├── img/fanbayan-banner.webp   بنرِ بالای صفحه‌ی اصلی (۶۴۰×۲۳۳)
│   ├── img/instructor.jpg         عکسِ واقعیِ مدرس (۳۶۰×۳۶۰) — هیرو، بخشِ مدرس، درباره
│   ├── img/favicon.svg            آیکونِ سایت
│   └── img/                       فقط همین سه فایل؛ هیچ تصویرِ دیگری در سایت نیست
├── tools/
│   └── selfcheck.php          خودآزماییِ انتشار، بدون وابستگی (php tools/selfcheck.php)
└── storage/                   پوشه‌ی نوشتنی (دسترسی وب بسته است)
    ├── messages/messages.csv  پیام‌های فرم تماس
    ├── rate-limit/            شمارنده‌ی ارسال (پنجره‌ی ثابت + GC)
    └── sessions/              نشست‌ها، فقط اگر save_path پیش‌فرض هاست خراب باشد
```

### تصاویرِ سایت (پوشه‌ی `assets/img/`)

| فایل | کجا نمایش داده می‌شود | اندازه |
|---|---|---|
| `fanbayan-banner.webp` | بنرِ بالای صفحه‌ی اصلی + تصویرِ اشتراک‌گذاری (`og:image`) | ۶۴۰×۲۳۳ |
| `instructor.jpg` | آواتارِ هیرو، بخشِ «معرفی مدرس» و صفحه‌ی «درباره» | ۳۶۰×۳۶۰ |
| `favicon.svg` | آیکونِ تبِ مرورگر | برداری |

برای عوض‌کردنِ عکس‌ها فقط همین دو فایل را با نامِ **همان** در `assets/img/` بگذارید
(برای بنر نسبتِ ۶۴۰×۲۳۳ و برای عکسِ مدرس ۱×۱ رعایت شود). نیازی به ویرایشِ کد نیست؛
همه‌ی مسیرها از همین یک جا خوانده می‌شود و خودآزمایی (`php tools/selfcheck.php`)
اگر فایلِ کم باشد یا مسیرِ اشتباهی در کد مانده باشد، آن را گزارش می‌کند.

هر پوشه‌ی داخلی (`config/`, `data/`, `includes/`, `pages/`, `storage/`, `tools/`) یک
`.htaccess` با `Require all denied` دارد؛ این لایه‌ی دومِ محافظت است و در نبودِ
`.htaccess` ریشه هم کار می‌کند.

**اصل معماری:** `data/` فقط داده، `includes/` فقط منطق و قالب، `pages/` فقط چیدمان صفحه.
برای افزودن مقاله فقط یک قلم به `data/articles.php` اضافه کنید — فهرست، جستجو، منوی پاورقی، نقشه‌ی سایت و «مقاله‌های مرتبط» خودکار به‌روز می‌شوند.

---

## ۳) آپلود روی InfinityFree (گام‌به‌گام)

1. در [infinityfree.com](https://www.infinityfree.com) یک اکانت و سپس یک **Account** بسازید (دامنه‌ی رایگان زیرمجموعه مثل `yoursite.infinityfreeapp.com` هم کافی است).
2. وارد **Control Panel** شوید → بخش **Domains** → دامنه را به‌عنوان **Primary Domain** انتخاب و **Create Domain** را بزنید.
3. **Online File Manager** → وارد پوشه‌ی **`htdocs`** شوید و فایل پیش‌فرض (`index2.html`) را حذف کنید.
4. محتوای این مخزن را **داخل خودِ `htdocs`** بریزید؛ مسیر نهایی باید این‌طور باشد:
   ```
   htdocs/index.php          ✅ درست
   htdocs/HAvoice/index.php  ❌ غلط (یک سطح اضافه است)
   ```
   روش سریع: در GitHub → `Code ▸ Download ZIP`؛ فایل را در File Manager آپلود، انتخاب و **Extract** کنید؛ اگر پوشه‌ای مثل `HAvoice-main` ساخته شد، همه‌ی فایل‌های داخل آن را به `htdocs` **Move** کنید و بعد ZIP و پوشه‌ی خالی را حذف کنید.
5. در VistaPanel مرحله‌ی **Verify you are human** را انجام دهید تا سایت suspended نماند.
6. **Control Panel ▸ Website ▸ PHP Version** را روی **PHP 8.1 یا 8.2** بگذارید و Save کنید.
7. سایت را باز کنید: `https://yoursite.infinityfreeapp.com/` — صفحه‌ی اصلی باید نمایش داده شود.
8. مجوزها: روی `storage` راست‌کلیک ▸ **Permissions** ▸ `755` (فایل‌ها `644`). اگر نوشتن پیام خطا داد، موقتاً `775` فقط روی `storage`.
9. (توصیه‌شده) **SSL**: از بخش `SSL Certificates` گواهی رایگان بسازید؛ سپس در `.htaccess` بلوک «انتقال به https» را از کامنت خارج کنید و در `config/config.php` مقدار `HA_FORCE_HTTPS` را `true` بگذارید.
10. **سئو:** در `config/config.php` مقدار `HA_SITE_URL` را با دامنه‌ی نهایی پر کنید (مثلاً `https://yoursite.infinityfreeapp.com`). تا وقتی خالی است، `canonical` و `og:url` از میزبانِ درخواست ساخته می‌شوند که برای دامنه‌های چندگانه مطمئن نیست.
11. **خودآزمایی پس از نصب:** اگر روی هاست به خطِ فرمان دسترسی ندارید، موقتاً `HA_DEBUG` را `true` کنید و `tools/selfcheck.php` را از مرورگر باز کنید (سپس `HA_DEBUG` را به `false` برگردانید). در محیطِ محلی: `php tools/selfcheck.php --http`.

### نکته‌های مهمِ InfinityFree
* **کش/OPcache:** اگر تغییرات را ندیدید، چند دقیقه صبر کنید و کش مرورگر را با `Ctrl+F5` بشکنید.
* **`mail()` روی InfinityFree غیرفعال است**؛ به همین دلیل `HA_SEND_MAIL = false` است و پیام‌ها در فایل ذخیره می‌شوند.
* **دیدن پیام‌ها:** `storage/messages/messages.csv` را از File Manager دانلود کنید (با اکسل یا Notepad++ باز می‌شود). برای امنیت بیشتر، بعد از هر دانلود پوشه را خالی کنید.
* **محدودیت inode:** فایل ZIP و نسخه‌ی پشتیبان را روی هاست نگه ندارید.
* اگر میزبانی `.htaccess` را قبول نکرد، پوشه‌ی `storage` را یک سطح بیرون `htdocs` ببرید و `HA_STORE_MESSAGES` را `false` کنید.

---

## ۴) تنظیمات بعد از نصب

در `config/config.php`:

```php
define('HA_EMAIL',  'you@yourdomain.com'); // ایمیل نمایشی و پشتیبانی
define('HA_PHONE',  '۰۲۱ …');
define('HA_HOTLINE','+98…');              // برای لینک tel:
define('HA_STORE_MESSAGES', true);         // ذخیره‌ی پیام‌های فرم
define('HA_SEND_MAIL',      false);        // روی InfinityFree false بماند
define('HA_DEBUG',          false);        // true فقط برای تست محلی
define('HA_PRETTY_URLS',    false);        // حالت URL کوتاه (پایین)
define('HA_BASE_PATH',      '');           // نصب در پوشه‌ی فرعی: '/havoice'
define('HA_SITE_URL',       '');           // دامنه‌ی قطعی برای canonical/og:url/sitemap
define('HA_FORCE_HTTPS',    false);        // پس از فعال‌سازی SSL: true
define('HA_CSRF_TTL',       28800);        // عمر توکن CSRF (۸ ساعت)
define('HA_RATE_LIMIT_MAX', 3);            // حداکثر پیام در هر پنجره
define('HA_RATE_LIMIT_WINDOW', 600);       // طول پنجره (ثانیه)
define('HA_RATE_LIMIT_MIN_INTERVAL', 20);  // حداقل فاصله‌ی دو ارسال (ضد رگبار)
define('HA_RATE_LIMIT_MAX_FILES', 400);    // سقف فایل‌های شمارنده (inode هاست)
```

### URL کوتاه (اختیاری)
پیش‌فرض نشانی‌ها `index.php?p=articles&slug=power-of-pause` است و روی **هر** هاستی کار می‌کند. برای شکل `/articles/power-of-pause`:

1. `HA_PRETTY_URLS` را `true` کنید؛
2. در `.htaccess` خط `RewriteRule ^([a-z0-9_\-/]+)/?$ index.php?r=$1 [QSA,L]` را از کامنت خارج کنید؛
3. اگر ۴۰۴ گرفتید یعنی `mod_rewrite` غیرفعال است: خط را دوباره کامنت و مقدار را `false` کنید.

### افزودن محتوا
* **مقاله:** قلمی به `data/articles.php` (`slug` لاتین و یکتا، `date` برای ترتیب، `date_fa` برای نمایش، `blocks` برای بدنه).
* **درس:** در `data/course.php` داخل `stages[x]['lessons']`؛ با افزودن `drill`، جعبه‌ی «تمرین همین درس» و شمارش پیشرفت خودکار فعال می‌شود.
* **تمرین / نکته:** `data/exercises.php` (برای تولیدگر موضوع، `'tool' => 'timer+topics'` و `topics`) و `data/tips.php`.

انواع بلوک: `h2`، `h3`، `p`، `lead`، `ul`، `ol`، `quote` (با `by`)، `tip` (با `tone`: `tip|warn|check|idea`)، `table` (با `head` و `rows`)، `drill` (با `title`، `time`، `items`، `note`).
در متن‌ها `**مهم**`، `*تأکید*` و `` `کد` `` پشتیبانی می‌شود.

---

## ۵) امنیت و کیفیت کد

* **مسیریابی با فهرست مجاز** (`routes()` در `includes/helpers.php`): نام مسیر هیچ‌وقت مستقیم به `include` نمی‌رود → امکان LFI نیست.
* **خروجی‌ها** همه با `e()` (`htmlspecialchars` با `ENT_QUOTES|ENT_SUBSTITUTE`)؛ `slug` با `slugify()` به `[a-z0-9_-]` محدود و `param()` به ۲۰۰ نویسه کرپ‌د می‌شود.
* **فرم تماس:** CSRF (`hash_equals`) + فیلد زنبوری + محدودیت نرخ بر پایه‌ی IP + اعتبارسنجی طول/قالب + حذف کاراکترهای کنترلی و تزریق سرصفحه + `flock` هنگام نوشتن CSV.
* **جلسه (session)** فقط برای صفحه‌ی تماس شروع می‌شود (کوکی `HttpOnly`، `SameSite=Lax`، `Secure` روی HTTPS)؛ بقیه‌ی صفحه‌ها بی‌جلسه و کش‌پسندند.
* **سرصفحه‌ها:** `Content-Security-Policy` (با `script-src 'self'`)، `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-Opener-Policy` از سمت PHP و به‌عنوان لایه‌ی دوم در `.htaccess`؛ `X-Powered-By` حذف و `ServerSignature Off`.
* **محدودیت نرخِ درست:** پنجره‌ی ثابت با بازنشانیِ واقعی پس از انقضا، حداقل فاصله‌ی بین دو ارسال، خواندن-تغییر-نوشتنِ اتمیک با `flock`، پاک‌سازی خودکارِ باکت‌های رهاشده و سقفِ تعداد فایل؛ اگر `storage` نوشتنی نباشد، **شکست به سمتِ بسته** (fail-closed) است.
* **CSRF:** توکن با `hash_equals`، انقضای ۸ ساعته و بررسی هم‌مبدأ بودنِ `Origin`/`Referer`.
* **نشست:** `use_strict_mode`، کوکی `HttpOnly`/`SameSite=Lax`/`Secure` روی HTTPS و مسیرِ نشستِ پشتیبان وقتی `session.save_path` هاست خالی یا غیرقابل‌نوشتن است.
* **بدون وابستگی:** بدون Composer، بدون jQuery، بدون دیتابیس، بدون مرحله‌ی build و **بدون Node در محیط عملیاتی**؛ فونت Vazirmatn به‌صورت محلی میزبانی می‌شود (یک فایل woff2 متغیر، بدون درخواست به سرویس خارجی) و آیکون‌ها SVG درون‌خطی‌اند.

### خودآزمایی (بدون وابستگی)
```bash
php tools/selfcheck.php            # محیط، پیکربندی، ذخیره‌سازی، دارایی‌ها، منطقِ محدودیت نرخ، کنتراست WCAG
php tools/selfcheck.php --http     # + آزمونِ دودِ همه‌ی مسیرها، پویش XSS/LFI و سرصفحه‌های امنیتی
```
خروجی، PASS/FAIL است و کدِ خروج در صورتِ هر FAIL برابر ۱ می‌شود (قابل استفاده در CI).
آخرین اجرا: **۱۲۲ PASS / ۰ FAIL** روی PHP 8.3.

### اجرای محلی
```bash
cd HAvoice
php -S localhost:8000     # سپس http://localhost:8000
```

---

## ۶) مجوز

کد و محتوا برای استفاده‌ی آزاد آماده است؛ در صورت انتشار مجدد، پیوند این مخزن را نگه دارید.
