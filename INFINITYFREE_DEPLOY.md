# راهنمای نصبِ HAvoice روی InfinityFree (hajivoice.kesug.com)

این پروژه بدونِ نیاز به Composer یا Node، با PHP خام و MySQL اختیاری روی **InfinityFree** آماده است. تمامِ کُد با **PHP 7.4+** سازگار (polyfill برای `str_*` در `config/config.php`) و `.htaccess`ها برای `AllowOverride` محدودِ هاستِ اشتراکی ایمن شده‌اند.

## ۱) دانلود و آپلود

1. از ریشهٔ پروژه ZIP بگیرید یا `git clone` کنید.
2. وارد **File Manager** InfinityFree شوید (یا FTP: `ftps://ftps.infinityfree.net`).
3. محتویاتِ `HAvoice/` را **مستقیم داخلِ `htdocs/`** آپلود کنید — ساختارِ نهایی:
   ```
   htdocs/
   ├── index.php
   ├── install.php
   ├── .htaccess
   ├── config/config.php
   ├── uploads/.htaccess
   ├── storage/.htaccess
   ├── database_import.sql
   └── ...
   ```
   - `/.git` را آپلود نکنید (در `.gitignore` است).
4. مطمئن شوید `htdocs/storage/` و `htdocs/uploads/` و `htdocs/config/` نوشتنی هستند (File Manager → راست‌کلیک → Permissions → 755).

## ۲) دیتابیس (اختیاری ولی پیشنهادی)

> بدونِ دیتابیس هم سایت با `storage/admin/*.json` کار می‌کند، ولی برای چندکاربره و جستجویِ سریع MySQL بهتر است.

1. در پنلِ InfinityFree → **MySQL Databases** → دیتابیس بسازید (مثلاً `epiz_12345678_havoice`). هاستِ MySQL معمولاً `sqlXXX.infinityfree.com` است، نه `localhost`.
2. **phpMyAdmin** → Import → فایلِ `database_import.sql` (ریشه) → `utf8mb4` → Go. جداولِ `ha_courses`, `ha_articles`, `ha_media`, `ha_users` … ساخته می‌شود.
3. فایلِ `config/config.local.php` را از روی نمونه بسازید:
   ```bash
   cp config/config.local.php.example config/config.local.php
   ```
   و داخلِ File Manager ویرایش کنید:
   ```php
   <?php
   define('HA_DB_HOST', 'sql123.infinityfree.com');
   define('HA_DB_PORT', 3306);
   define('HA_DB_NAME', 'epiz_12345678_havoice');
   define('HA_DB_USER', 'epiz_12345678');
   define('HA_DB_PASS', 'YOUR_DB_PASSWORD');
   // define('HA_SITE_URL', 'https://hajivoice.kesug.com'); // پیش‌فرض همین است
   // define('HA_BASE_PATH', '');
   // define('HA_PRETTY_URLS', false); // InfinityFree پیش‌فرض false ( ?p=... )
   // define('HA_FORCE_HTTPS', true); // وقتی SSL فعال شد
   ```
   این فایل در `.gitignore` است و هرگز commit نمی‌شود.

## ۳) اجرای نصب‌کننده

1. مرورگر: `https://hajivoice.kesug.com/install.php` (نسخهٔ 3.5.0).
2. نصب‌کننده چک می‌کند:
   - PHP ≥ 7.4، `storage/` و `uploads/` نوشتنی، `session.save_path` سالم (اگر نبود به `storage/sessions` برمی‌گردد).
   - اتصالِ DB (اگر `config.local.php` پر باشد)؛ اگر خالی بماند، fallbackِ JSON فعال می‌ماند.
3. اگر جداول خالی‌اند، می‌توانید یک‌بار `HA_DB_AUTO_SEED=true` و `HA_SEED_ADMIN_*` را در `config.local.php` بگذارید تا دوره‌ها/مقالاتِ `data/` وارد DB شوند، سپس دوباره `false` کنید.
4. فرمِ ساختِ مدیر را پر کنید → **نصب**.
5. پس از موفقیت، `install.php` را **حذف یا Rename** کنید (یا `config/installed.lock` و `storage/installed.lock` ساخته می‌شود و نصبِ دوباره بسته می‌ماند).

## ۴) آپلودِ فایل (ویدیو/صوت/PDF/تصویر)

- مسیر: **مدیریت → ویدیوها / پادکست / مقالات / کتاب‌ها** → فیلدِ **آپلودِ فایل**.
- فایل‌ها با نامِ تصادفیِ `YYYYMM-<random>.ext` در `uploads/` می‌روند (`0644`)، MIME با `finfo` + `magic bytes` تأیید، `php` در `uploads/.htaccess` مسدود، و نشانیِ مطلق با `asset()` + `?v=filemtime` ساخته می‌شود (بدونِ 404 در `/articles/slug`).
- سقفِ حجم از `HA_UPLOAD_MAX_BYTES` (۸MB پیش‌فرض) و سقفِ واقعیِ `upload_max_filesize`ِ InfinityFree (۱۰MB) کوچک‌ترِ هر دو ملاک است؛ برای ویدیوی بزرگ از **پیوندِ آپارات/یوتیوب** (امبدِ مجاز) استفاده کنید — ویدیو در خودِ سایت با `<iframe loading=lazy>` یا `<video controls preload=metadata>`ِ ساده و سریع پخش می‌شود.

## ۵) نکاتِ InfinityFree

- `HA_PRETTY_URLS=false` بگذارید → نشانی‌ها `index.php?p=course&slug=...` (بدونِ نیاز به `mod_rewrite`). اگر SSL و `mod_rewrite` فعال شد، می‌توانید `true` و `RewriteRule ^([a-z0-9_\-/]+)/?$ index.php?r=$1 [QSA,L]` در `.htaccess` را uncomment کنید.
- `.htaccess`ها همگی داخلِ `<IfModule>` هستند تا `500 Internal Server Error` روی ماژولِ خاموش ندهند؛ `Options -Indexes` هم در `mod_autoindex.c` پیچیده شده.
- `storage/` و `uploads/` هر دو `index.html` خالی دارند تا فهرست‌گیری بسته بماند.
- `HA_DEBUG=false` نگه دارید تا `display_errors` خاموش و `log_errors` روشن بماند؛ خطاها در `error_log`ِ هاست می‌روند، نه در خروجیِ XMLِ `sitemap.php`.
- ایمیلِ پیش‌فرض `HA_SEND_MAIL=false` است چون `mail()` روی InfinityFree مسدود است؛ پیام‌های تماس در `storage/messages/messages.csv` ذخیره می‌شوند.

## ۶) تستِ نهایی پس از آپلود

```bash
php tools/production-check.php      # باید همه PASS
php tools/selfcheck.php             # چکِ دارایی‌ها و مسیرها
```

در مرورگر:
- `https://hajivoice.kesug.com/` → هیرو + کارت‌ها
- `https://hajivoice.kesug.com/index.php?p=videos` → ۳ ویدیو با `<video controls>`ِ ساده
- `https://hajivoice.kesug.com/index.php?p=audios` → پادکست با `<audio controls>`
- `https://hajivoice.kesug.com/index.php?p=books&slug=...` → PDF داخلِ `<iframe>` + دانلود
- `https://hajivoice.kesug.com/sitemap.xml` → XMLِ تمیزِ بدونِ `Warning` (حتی اگر `config.local.php` فاصلهٔ اضافه داشته باشد)

در صورتِ `500`، اول `error_log`ِ `htdocs/` را ببینید؛ شایع‌ترین علت `config.local.php` با `<?php`ِ بدونِ `if (!defined('HA_ROOT'))` یا `Options`ِ بدونِ `IfModule` است — که اکنون هر دو رفع شده‌اند.

---
**نسخه:** 3.5.0 — آمادهٔ InfinityFree، بدونِ وابستگیِ خارجی، تمامِ مسیرِ `Upload→Storage→DB→URL→Frontend` تست‌شده.
