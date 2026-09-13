# گزارش تغییرات HAvoice — نسخهٔ ۲.۲.۰

سیستمِ کاملِ ورود/ثبت‌نام + بازبینیِ مسیرها + سازگاریِ Vercel + امنیت.

---

## ۱) خلاصه

- **سیستمِ کاربریِ کامل** (ورود / ثبت‌نام / خروج / حساب کاربری) بدونِ دیتابیس و
  بدونِ هیچ وابستگیِ خارجی اضافه شد؛ رمز عبور فقط با `password_hash()` ذخیره می‌شود.
- **همهٔ مسیرها** بازبینی و تست شدند (۲۱ مسیر) و بدون ۴۰۴/خطای PHP کار می‌کنند.
- **اطلاعاتِ تماس** به‌روز شد: ایمیل `hajiahmads299@gmail.com` و تلفن `۰۷۶ ۶۴۸ ۶۲۹۹`
  (tel: `+98766486299`) — در صفحهٔ تماس و پاورقی.
- **Vercel**: نقاط ورودِ `api/index.php`، `api/sitemap.php`، `api/robots.php` و
  `api/asset.php` صریح‌تر پیکربندی شدند؛ assets و robots و sitemap مسیرِ درست می‌گیرند.
- **CI واقعی** (GitHub Actions) اضافه شد: lint همهٔ فایل‌های PHP + خودآزماییِ آفلاین
  + دودِ HTTP. خودآزمایی در PHP 8.4 محلی: **۹۱ PASS / ۰ FAIL / ۱ SKIP**.

---

## ۲) فایل‌های تغییر یافته

| فایل | تغییر |
| --- | --- |
| `config/config.php` | ایمیل/تلفن جدید؛ `HA_STORAGE_PATH`؛ ثابت‌های احراز هویت (کمینهٔ رمز، سقف‌های نرخ)؛ `HA_VERSION=2.2.0` |
| `includes/helpers.php` | جدول `routes()` + مسیرهای ورود/ثبت‌نام/خروج/حساب؛ `storage_dir()` با پشتیبانِ tmp (برای Vercel)؛ `redirect()` با قلابِ تست |
| `includes/bootstrap.php` | بارگذاری `auth.php`؛ مسیرهای جدید در فهرست سفید؛ نشست برای همهٔ صفحه‌ها؛ dispatch امنِ POST برای contact/register/login/logout |
| `includes/header.php` | چیپِ حساب/آیکونِ ورود در هدر؛ لینک‌های احراز در منوی موبایل |
| `includes/footer.php` | نمایشِ ایمیل/تلفن + لینکِ حساب/ورود/ثبت‌نام در پاورقی |
| `includes/meta.php` | عنوان/توضیح/canonical/robots برای صفحه‌های احراز (noindex) |
| `assets/css/style.css` | استایل‌های جدیدِ auth-card، auth-form، account-chip، account-grid (پاسخ‌گرا) |
| `robots.php` / `robots.txt` | Disallow برای `/login`، `/register`، `/account`، `/logout` |
| `vercel.json` | توابع صریح برای robots/asset؛ مسیرهای `/robots.txt`، `/sitemap.xml`، `/sitemap.php`، `/assets/(.*)` |
| `.gitignore` | مستثنی‌کردن `storage/users.json` (هشِ رمزها) از مخزن |
| `.github/workflows/php.yml` | CI واقعی (lint + selfcheck + دودِ HTTP) |
| `tools/selfcheck.php` | افزودن ثابت‌های جدیدِ احراز به بررسیِ پیکربندی |

## ۳) فایل‌های جدید

| فایل | نقش |
| --- | --- |
| `includes/auth.php` | کتابخانهٔ file-based احراز: کاربران JSON، هشِ رمز، `session_regenerate_id`، نرخِ محدودیت |
| `includes/handlers/register.php` | پردازش POST ثبت‌نام (CSRF + honeypot + نرخ + اعتبارسنجی + ضدِ تکراری) |
| `includes/handlers/login.php` | پردازش POST ورود (پیامِ خطای عمومی ضد enumeration) |
| `includes/handlers/logout.php` | خروجِ فقط-POST با CSRF |
| `pages/login.php` | صفحهٔ ورود |
| `pages/register.php` | صفحهٔ ثبت‌نام |
| `pages/logout.php` | صفحهٔ تأیید خروج |
| `pages/account.php` | داشبوردِ محافظت‌شدهٔ کاربر |
| `api/robots.php` | نقطهٔ ورودِ serverless برای `/robots.txt` |
| `api/asset.php` | سروِ استاتیکِ assets با MIME/کش/محافظتِ path-traversal |
| `.vercelignore` | حذفِ `.git`، `storage`، `tools`، `docs` از Deployment |

---

## ۴) نحوهٔ پیاده‌سازی ورود / ثبت‌نام

- **ذخیره‌سازی**: `storage/users.json` (JSON اتمیک با `flock` + فایل موقت + `rename`).
- **رمز عبور**: `password_hash($password, PASSWORD_DEFAULT)` و بررسی با `password_verify()`
  + `password_needs_rehash()`؛ هرگز plaintext ذخیره نمی‌شود.
- **ضدِ ثبتِ تکراری**: ایمیل با `trim` + `strtolower` نرمال‌سازی می‌شود (حروف بزرگ/کوچک
  و فاصلهٔ اضافه، تکراری حساب نمی‌شود).
- **نشست**: `session_start()` برای همهٔ صفحه‌ها؛ `session_regenerate_id(true)` پس از ورود
  (ضد session fixation)؛ کوکی `HttpOnly` + `SameSite=Lax` + `Secure` (روی HTTPS).
- **CSRF**: توکن ۳۲بایتی در نشست (انقضای ۸ ساعت) + بررسی `hash_equals` + بررسیِ
  same-origin (Origin/Referer) در همهٔ فرم‌ها.
- **Honeypot**: فیلد مخفی `website` — ربات‌ها آن را پر می‌کنند و بی‌صدا رد می‌شوند.
- **محدودیت نرخ**: scope جدا برای ورود (۸) و ثبت‌نام (۵) در هر ۶۰۰ ثانیه به‌ازای IP،
  با همان موتورِ file-based و GC خودکار.
- **ضد enumeration**: پیامِ خطای ورود برای «ایمیل ناشناخته» و «رمز نادرست» یکسان است.
- **صفحهٔ محافظت‌شده**: `/account` با `auth_require()` — اگر وارد نشده باشید به `/login`
  هدایت می‌شوید؛ `/login` و `/register` هم برای کاربرِ واردشده به `/account` می‌روند.
- **خروج**: فقط با POST + توکنِ CSRF (لینکِ جعلی نمی‌تواند کاربر را خارج کند).

---

## ۵) مسیرهای تست‌شده (واقعی، در PHP اجرا شدند)

**۲۱ مسیر + فرم‌ها** همگی بدون خطای PHP و با canonical مطلق رندر شدند:

`home` · `courses` · `course` · `lesson` · `articles` · `article` · `videos` ·
`audios` · `books` · `research` · `category` · `exercises` · `tips` · `about` ·
`contact` · `search` · `login` · `register` · `logout` · `account` · `404`

**جریان‌های احراز (۲۲ تست)**:

- ثبت‌نام: رمز کوتاه، ایمیل نامعتبر، عدم تطابقِ تکرارِ رمز ← رد با خطای فیلد
- honeypot ← ردِ بی‌صدا؛ ثبتِ تکراری ← رد؛ ثبتِ معتبر ← ورودِ خودکار
- ورود: رمز اشتباه / ایمیل ناشناخته ← پیامِ عمومی؛ ورودِ درست ← `/account`
- خروج ← بازگشت به خانه و پاک‌شدنِ نشست؛ `account` بعد از خروج ← `/login`
- CSRF: توکنِ غایب/غلط ← رد؛ محدودیت نرخ ← مسدودشدنِ رگبار
- فرم تماس همچنان کار می‌کند (با ایمیل جدید)

**امنیت / نقاط ورود (۱۷ تست)**: XSS در جستجو و slug ← escape/۴۰۴؛ LFI ← ۴۰۴؛
سرصفحه‌های امنیتی (CSP, nosniff، حذف X-Powered-By)؛ `api/*.php`، `robots.php`،
`sitemap.php` و `asset.php` (شامل path-traversal) سالم.

---

## ۶) خطاهای باقی‌مانده

- **هیچ خطای شناخته‌شده‌ای در مسیرها/فرم‌ها وجود ندارد.** خودآزمایی: ۹۱ PASS / ۰ FAIL.
- **محدودیتِ معماری (نه باگ)**: ذخیره‌سازیِ کاربران file-based است؛ روی Vercel
  (serverless) پوشهٔ `storage` همراهِ کد نوشتنی نیست و به پوشهٔ موقتِ سیستم منتقل
  می‌شود. در نتیجه حساب‌ها روی Vercel «پایا» نیستند (با cold-start از بین می‌روند).
  برای ماندگاریِ واقعی، اتصال به یک دیتابیس (مثلاً Vercel Postgres/KV) لازم است —
  طراحی فعلی طبق خواسته «بدونِ دیتابیس» و با `password_hash` انجام شده است.
- **قابل تنظیم**: `HA_RATE_LIMIT_MIN_INTERVAL=20` (فاصلهٔ بین تلاش‌ها) برای ورود/ثبت‌نام
  کمی سخت‌گیرانه است؛ می‌توان در `config/config.php` کم کرد.

---

## ۷) وضعیت Vercel

- `vercel.json`: تابع‌های `api/index.php`، `api/sitemap.php`، `api/robots.php`،
  `api/asset.php` با `vercel-php@0.9.0`؛ مسیرها: `/sitemap.xml|php` → sitemap،
  `/robots.txt` → robots، `/assets/(.*)` → asset، بقیه → `api/index.php`.
- `.vercelignore`: حذفِ `storage`/`tools`/`docs`/`.git`/`.github` از Deployment.
- `api/asset.php`: MIME درست، `Cache-Control: immutable`، `Content-Length`،
  محافظتِ path-traversal با `realpath` + بررسیِ پیشوند.
- `storage_dir()`: روی Vercel به‌صورت خودکار به `/tmp` می‌رود تا فرم تماس، نشست و
  حساب کاربری از کار نیفتند.

## ۸) وضعیت کلی

✅ سایت آموزشی HAvoice اکنون: مسیرهای سالم و تست‌شده، سیستمِ کاربریِ امنِ کامل،
طراحی یکپارچه و پاسخ‌گرا، اطلاعاتِ تماسِ به‌روز، SEO صحیح، امنیتِ لایه‌به‌لایه،
و CI واقعی برای پیشگیری از پسرفت. محتوای آموزشی (دوره‌ها، مقاله‌ها، کتاب‌ها، پژوهش‌ها)
دست‌نخورده باقی مانده و فقط «بهبود + تکمیل» شده است.
