# HAvoice — Production (InfinityFree + MySQL)

نسخه: **3.2.0**

## معماری داده

```
Admin Form
  → Validation (CSRF, role=admin, field rules)
  → Handler (pages/admin/*_save.php)
  → Repository (includes/repository.php)
  → PDO (includes/db.php)
  → MySQL
  → Response (flash + redirect PRG)
  → Frontend (content.php → ha_visible)
```

اگر `HA_DB_*` خالی یا DB در دسترس نباشد، سایت با `data/*.php` + `storage/admin/*.json` کار می‌کند (سازگاری و پیش‌نمایش).

## جداول

| جدول | نقش |
|------|-----|
| `ha_users` | کاربران + role admin/user |
| `ha_categories` | حوزه‌ها |
| `ha_courses` / `ha_stages` / `ha_lessons` | مسیر یادگیری |
| `ha_exercises` | تمرین‌ها |
| `ha_media` | video + audio (type) |
| `ha_books` | کتاب‌ها |
| `ha_articles` | مقالات |
| `ha_tips` / `ha_research` | نکته و پژوهش |
| `ha_comments` | نظرات عمومی |
| `ha_settings` / `ha_contact_messages` | تنظیمات و پیام تماس |
| `ha_schema_meta` | نسخه schema |

Schema: `sql/002-schema.sql` (PK, FK, index, status, created_at, updated_at).

## استقرار InfinityFree

1. آپلود کل پروژه (بدون `config/config.local.php` از Git).
2. MySQL Databases → ساخت دیتابیس.
3. کپی `config/config.local.php.example` → `config/config.local.php` روی سرور و پر کردن:
   - `HA_DB_HOST`, `HA_DB_NAME`, `HA_DB_USER`, `HA_DB_PASS`
   - `HA_SITE_URL` = `https://your-domain…`
   - `HA_FORCE_HTTPS` = true پس از SSL
   - `HA_SEED_ADMIN_EMAIL` / `HA_SEED_ADMIN_PASSWORD` (一次性)
4. Schema خودکار در اولین اتصال PDO ساخته می‌شود **یا** Import `sql/002-schema.sql` در phpMyAdmin.
5. Seed (محتوای واقعی، بدون fake media):
   ```bash
   php tools/db-migrate.php --seed
   ```
   اگر shell ندارید: یک‌بار `HA_DB_AUTO_SEED` را true بگذارید، یک صفحه را باز کنید، بعد false کنید.
6. پوشه‌های نوشتنی: `storage/`, `uploads/` (chmod مناسب هاست).
7. `.htaccess` ریشه + پوشه‌های داخلی از قبل آماده است.

## امنیت

- رمز: `password_hash` / `password_verify`
- Admin فقط `role=admin` (+ سازگاری اولین کاربر JSON)
- CSRF + same-origin روی POST
- Session: httponly, samesite Lax, strict mode, regenerate on login, TTL
- Upload: MIME finfo، whitelist پسوند، نام تصادفی، path check، مسدود double-ext خطرناک، `.htaccess` engine off
- SQL: فقط prepared statements (PDO)
- XSS: `e()` در قالب‌ها
- Open redirect: `ha_safe_next`
- Secrets: فقط `config.local.php` (gitignore)

## Vercel

فقط Preview/compatibility (`vercel.json` + `api/*.php`).  
Production واقعی = InfinityFree (یا هر هاست PHP+MySQL). معماری برای Vercel خراب نشده.

## دستورات QA

```bash
php -l … / php tools/selfcheck.php
php tools/production-check.php
php tools/db-migrate.php --seed
php tools/production-check.php --http=http://127.0.0.1:8080
```

CI (`.github/workflows/php.yml`): lint + selfcheck + production-check + MySQL migrate/seed + HTTP smoke.
