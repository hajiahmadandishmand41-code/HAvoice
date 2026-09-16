# HAvoice — Production (InfinityFree + MySQL)

نسخه: **3.5.0** — schema **v4**

## استقرار InfinityFree

1. کل پروژه را در `htdocs` آپلود کنید (بدون `config/config.local.php` از Git).
2. MySQL Databases → ساخت دیتابیس. **هاست معمولاً `sqlXXX.infinityfree.com` است، نه `localhost`.**
3. phpMyAdmin همان دیتابیس → Import فایل **`database_import.sql`** (utf8mb4).
4. کپی `config/config.local.php.example` → `config/config.local.php` روی سرور و پر کردن:
   - `HA_DB_HOST`, `HA_DB_NAME`, `HA_DB_USER`, `HA_DB_PASS`
   - `HA_SITE_URL` = `https://your-domain.infinityfreeapp.com`
   - پس از SSL: `HA_FORCE_HTTPS` = true
5. پوشه‌های نوشتنی: `storage/` و `uploads/` (chmod 755 یا مطابق پنل).
6. اولین بازدیدکننده که ثبت‌نام کند مدیر می‌شود؛ بعد از آن هیچ کاربری خودکار مدیر نیست.
7. محتوای دوره از `data/*.php` تا وقتی جداول دوره خالی باشند خوانده می‌شود. برای کپی به MySQL یک‌بار `HA_DB_AUTO_SEED=true` بگذارید، یک صفحه را باز کنید، بعد false کنید.

## جداول (PK / FK)

| جدول | نقش | روابط |
|------|-----|--------|
| `ha_roles` | نقش user/admin | PK `role_key` |
| `ha_users` | کاربر + `pass_hash` | FK `role` → `ha_roles` |
| `ha_categories` | حوزه‌ها | PK + unique slug |
| `ha_courses` | دوره | FK `category_slug` → categories |
| `ha_stages` | مرحله | FK `course_id` CASCADE |
| `ha_lessons` | درس | FK course + stage CASCADE |
| `ha_exercises` | تمرین | FK اختیاری course_id / lesson_id |
| `ha_media` | ویدیو + پادکست | FK اختیاری course_id / lesson_id |
| `ha_books` | PDF/کتاب | `file_url` مسیر `uploads/…` |
| `ha_comments` | نظر/تجربه | FK اختیاری `user_id` |
| `ha_contact_messages` | پیام تماس | PK |
| `ha_progress` | پیشرفت یادگیری | FK `user_id` CASCADE |
| `ha_settings` / `ha_schema_meta` | تنظیمات و نسخه schema | |

رمز عبور فقط با `password_hash` ذخیره می‌شود. در SQL هیچ حسابی نیست.

## امنیت

- Admin فقط `role=admin` ذخیره‌شده
- CSRF + same-origin روی POST
- Session: httponly, samesite Lax, regenerate on login, TTL
- SQL: PDO prepared statements
- XSS: `e()` در قالب
- Upload: MIME + پسوند + magic + `realpath` داخل `uploads/` + `.htaccess` engine off
- خطاهای PHP/SQL در Production نمایش داده نمی‌شوند (`HA_DEBUG=false`)
- Secrets فقط در `config.local.php` (gitignore)
- `sql/` و `config/` و `tools/` از وب بسته‌اند

## رسانه روی هاست اشتراکی

فایل‌های PDF/ویدیو/پادکست در `uploads/` با نام تصادفی ذخیره می‌شوند و با مسیر نسبی `uploads/….mp4` پخش می‌شوند. سقف واقعی PHP (اغلب ۲–۸ مگابایت) را `ha_upload_max_bytes()` رعایت می‌کند. برای ویدیوی بزرگ، پیوند آپارات/یوتیوب در فیلد نشانی.

## QA

```bash
php tools/selfcheck.php
php tools/production-check.php
php tools/db-migrate.php --status
```
