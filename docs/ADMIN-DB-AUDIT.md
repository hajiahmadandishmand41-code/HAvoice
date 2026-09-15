# HAvoice — ممیزی Admin و Database (گزارش فاز ۱)

**تاریخ:** ۲۰۲۶-۰۹-۱۵ · **برد:** ممیزیِ ریشه‌ای + پیاده‌سازیِ اصلاحاتِ معماری فازِ ۱
**هدف:** تبدیل پنل مدیریت به یک CMS واقعی، ساده و امن که روی InfinityFree (میزبانیِ PHP+MySQL اشتراکی) نصب شود.

معیار: ✅ درست · ⚠️ نیاز به اصلاح · ❌ مشکلِ بحرانی

---

## ۱) چگونگیِ ممیزی

* **کلِ ریپازیتوری** (همه‌ی ~۵۵ صفحه‌ی پنل، includes/*، data/*، sql/*، tools/*، استایل‌ها) مرور شد؛ هیچ فایلی بی‌دلیل حذف نشد.
* هر عملیاتِ پنل از این مسیر رد شد: فرم → Handler → Validation → DB/File → Response → Redirect.
* تست‌ها: php-wasm (سناریوی رفتاری انتها به انتها) و php-parser لینت (۳۹ فایلِ تغییریافته/جدید، همه تمیز).

---

## ۲) نتیجه‌ی خلاصه

| حوزه | ممیزیِ قدیمی | وضعیتِ فعلی |
| --- | --- | --- |
| Admin Flow | ✅ پایه خوب بود | ✅ با داشبوردِ دوره و روش‌شده‌ترِ Draft→Publish |
| Database Architecture | ⚠️ ستون‌های عادی schema و اما بدونِ UNIQUE/FK واقعی در متن | ✅ v3: PK/FK/UNIQUE/INDEX واقعی + CASCADE امن |
| Tables | ⚠️ contact_messages تعریف ولی استفاده نشده بود | ✅ فعال (Database-driven Inbox) |
| Relationships | ❌ تمرین↔درس فقط با رشته‌ی slug؛ حذفِ جانِ والد، orphan تولید می‌کرد | ✅ FK + ON DELETE CASCADE (تمرینِ orphan ممنوع — در فرم/validation/DB enforce) |
| Migration Plan | ⚠️ نبود | ✅ 002 (نصب تازه) + 003 (مهاجرتِ idempotent) + admin_migrate پنل |
| Security | ✅ پایه امن (هاش، CSRF، Session) | ✅ + bootstrap اولین-admin enforce در backend+DB + محافظ آخرین مدیر |
| InfinityFree Risks | ⚠️ حالتِ اولیه‌ی storage، سهمیه‌ی session | ⚠️ مستند و جزئی؛ .htaccess ها اعزامنگارند |
| File-based Data | ⚠️ لایه‌ی Hybrid موجود | ✅ مسیرِ Verify-first — حذفِ قدیمی فقط بعد از تأیید |
| Required Changes | ❌ | ✅ §۱۱ |

---

## ۳) Admin Flow (نهایی — پیاده‌شده) ✅

```
Login (password_verify + rate-limit + CSRF + rotation)
  → Dashboard (شمارِ هر بخش + دکمه‌های +Course +Lesson +Exercise +Video +Podcast +Book)
    → Section (دوره‌ها، مقاله‌ها، ویدیوها، صوت‌ها، کتاب‌ها، حوزه‌ها، نظرات، پیام‌ها، کاربران، تنظیمات)
      → Create/Edit (فرمِ ساده؛ نامک/تاریخ/order/visibility خودکار)
        → ذخیره به‌عنوانِ پیش‌نویس (پیش‌فرضِ هر موردِ تازه)
          → انتشار (دکمه‌ی انتشار — تصمیمِ صریحِ مدیر؛ دکمه‌ی مخفی برای معکوس)
```

**کارت‌های داشبورد:** دوره · درس · تمرین · ویدیو · پادکست/صوت · کتاب · مقاله · کاربر · پیامِ خوانده‌نشده · نظرِ در انتظار + کارتِ «انتقال به دیتابیس» (قابلِ دیدن فقط وقتی DB خالی است).

### جریانِ «دوره‌ی تازه»
۱. `دوره‌ی جدید` (دارای فیلدهای: عنوان، توضیح، سطح، حوزه) → ذخیره (نامک/تاریخ/order خودکار، status=draft)
۲. بعد از ذخیره ⇒ **داشبوردِ دوره** — ساختارِ مرحله‌ها ↑ درس‌ها ↑ تمرین‌ها (هر کدام + تازه/ویرایش/انتشار/حذف با تأیید).

### جریانِ «درس»
۱. از داشبوردِ دوره: `+ درسِ جدید (در این مرحله)` → فرمِ کوچکِ درس: عنوان (نامک خودکار)، هدف/محتوا، زمان، stage مقصد، وضعیت (default draft).
۲. مدتِ real: «ذخیره و درسِ بعدی» — حلقه‌ی چند درس پیاپی («ذخیره‌ی همه» در عمل = ذخیره‌ی هر درس؛ order خودکار = پایان مرحله).
۳. blocks آزاد: اگر خالی = بلوک‌های استاندارد h2+p از رویِ عنوان/هدف — هر درس حداقل محتوای لازم دارد.

---

## ۴) Database Architecture — Tables, PKs, FKs, UNIQUEs ✅

```
ha_users            PK id CHAR(36) · UNIQUE email · role ENUM(user,admin)
ha_categories       PK id · UNIQUE slug
ha_courses          PK id · UNIQUE slug · FK category→categories(C RESTRICT) · featured · status ENUM(draft,published)
ha_stages           PK id · FK course→courses(CASCADE) · UNIQUE(course_id, stage_key) · status ENUM(published,draft)
ha_lessons          PK id · FK course→courses(CASCADE) · FK stage→stages(CASCADE) · UNIQUE slug · order · status
ha_exercises        PK id · UNIQUE ex_key · FK lesson→lessons(CASCADE) [ON DELETE CASCADE] · status
ha_media            PK id · type ENUM(video,audio) · UNIQUE(type,slug) · status
ha_books            PK id · UNIQUE slug · status
ha_articles         PK id · UNIQUE slug · status
ha_tips             PK id · UNIQUE tip_key · status
ha_research         PK id · UNIQUE slug · status
ha_comments         PK id · status ENUM(pending,approved,hidden)
ha_contact_messages PK id · read_at NULL · KEY idx_messages_read  ← Database-driven (پیش‌تر فایل-محور)
ha_settings         PK setting_key · value
ha_schema_meta      PK id · version (seed 3)
```

روابطِ Cascade (تعریف‌شده در معماری):
* `ha_courses → ha_stages → ha_lessons → ha_exercises` — همه CASCADE.
* تمرینِ بدونِ درس (orphan) مجاز نیست: validation در `exercise_save` الزامی می‌کند و در DB هم `lesson_id` با FK enforce است؛ حذفِ درس همه‌ی تمرین‌هایش را می‌بُرد.
* حذفِ مرحله → درس‌ها → تمرین‌ها. حذفِ دوره → مرحله‌ها → درس‌ها → تمرین‌ها + تمرین‌های قدیمیِ slug-محور هم‌نام (در `db_course_delete`).

---

## ۵) Migration Plan ✅

مسیرِ مهاجرت (تعیین‌شده در spec §۱۳):

```
Current (File: data/*.php + storage/admin/*.json mirrors)
  → Normalize (repo_seed: slug, order, status, lesson links)
  → Import (INSERT IGNORE به DB — غیرتخریبی، idempotent)
  → Verify (COUNT برابر/بیشتر در db_*_count‌ها پس از admin_migrate)
  → Switch Read Path (repo_courses/db_first خودکار وقتی db_ready و داده‌ی جدی)
  → Remove obsolete path — فقط بعد از اجازه‌ی مدیر (مستند: هنوز فایل‌ها به‌عنوان fallback فعال‌اند)
```

**اجرا:** صفحه‌ی داشبورد ⇒ کارتِ «انتقال به دیتابیس» (فقط وقتی DB خالی است). پس از کلیک: `repo_seed(false)` آینه‌ی فایل + storage را در DB می‌ریزد؛ با شمارشِ قبل/بعد تفاوت گزارش می‌شود.

---

## ۶) Security ✅

| مورد | روش |
| --- | --- |
| Password | password_hash (PASSWORD_DEFAULT) + password_verify |
| Session | strict mode, httponly, session_regenerate at login, session files در storage محافظت‌شده با .htaccess |
| CSRF | csrf_field/csrf_verify با TTL + در همه‌ی فرم‌ها (تست با دو سناریوی end-to-end) |
| Rate Limit | login ۶/دقیقه، contact عمومی (پنجره‌ی ۲۰ ثانیه) — storage-based bucket |
| Authorization | auth_require_admin در هر صفحه‌ی admin + POST-handler echo backend (not just UI) |
| Input Validation | server-side در همه‌ی save handlers (length, enum, slugs, emails, URL allowlist، course/lesson existenceِ draft-aware) |
| Bootstrap Admin | فقط وقتی admin count = 0 اولین حساب admin است — enforce در	db/backend (`auth_bootstrap_admin_available` + `db_user_create`)، تست: تزریقِ role به POST ثبت‌نامِ دوم ⇒ user ماند |
| Last Admin | حذف/تنزلِ آخرین مدیر در `user_delete.php` و `user_save.php` ممنوع |
| File/directory traversal | مسیرهای route whitelist, URL/File URL allowlist, uploads محدودِ mime |

---

## ۷) InfinityFree Risks ⚠️

* **storage زیرِ ریشه‌ی پروژه است** (خارج از webroot نیست) — با `.htaccess Deny from all` و `index.html` محافظت می‌شود. بررسیِ دستی بعد از نصب: درخواستِ مستقیمِ `GET /storage/users.json` باید ۴۰۳ بدهد.
* **session در همان پوشه** (`storage/sessions`) — روشی امن برای هاست‌های بدونِ `/tmp` قابل‌نوشتن.
* **کوئری‌های سنگین نداریم:** شمارش‌های داشبورد ساده‌اند — لیمیت‌های InfinityFree کفایت می‌کند.
* **Uploadها** با allowlist نوع/پسوند (pdf/jpg/png/webp) — محدود به حد مجاز هاست حافظه. آپلودها در storage/ با .htaccess محافظت می‌شوند.

**نکته‌ی نصب:** `config/config.php` (HA_DB_HOST/USER/PASS) آماده‌ی وصل است؛ `config/config.local.php.example` همان‌جا و در .gitignore فیل شده — بعد از نصب مقداردهی نمی‌شود.

---

## ۸) Current File-based Data (مسیر فعلی) ⚠️→✅

**تأکید spec:** هیچ لایه‌ای را بی‌دلیل حذف نمی‌کنیم؛ مسیرِ قدیمی فقط بعد از «Verify» منسوخ می‌شود. **حفظ می‌کنیم:**
* فایل‌های `data/*.php` (محتوای پایه‌ی سایت) — همچنان fallback خواندنی است.
* آینه‌های JSON در `storage/admin/*.json` — مکانیزمِ dual-write دست‌نخورده مانده و با رفتارِ Delete/CASCADE همگام شده.
* مسیرِ `contact → CSV` — در حالِ حاضر «فایل fallback» است؛ DB-first شدن در هندلر (`db_message_add`) و در Inbox (db-N ref).

---

## ۹) مواردِ تکمیلِ فاز (اجراشده) ✅

**v3 Schema:**
* `sql/002-schema.sql` ← ha_schema #۳ با همه‌ی FKها و UNIQUEهای لازم.
* `sql/003-upgrade.sql` ← مهاجرتِ idempotent فایل‌ی v2→v3 (بارها اجرا شود همان نتیجه).
* `sql/001-create-comments.sql` ← hidden در ENUM.
* `includes/db.php` ← db_ensure_schema (002+003)، db_course_save_full «update-in-place» به‌جای delete+reinsert (fix برای FK)، db_exercise_relink, db_course_delete cascade قدیمی‌ها، db_lessons_flat/set_status، db_message_* کامل.

**Admin Pages جدید:**
`course_view.php` (داشبورد)، `stage_save.php`/`stage_delete.php`/`lesson_edit.php`/`lesson_save.php`/`lesson_delete.php` (ساختار دوره)، `comment_save.php` (Create نظر)، `migrate.php` (انتقال پنل).

**ساده‌سازی (spec §۶، §۹):**
* فرم‌های video/audio/book/book ⇐ بدونِ نامک دستی (خودکار فارسی→لاتین with ha_auto_slug) / بدونِ تاریخ دستی / بدونِ junk texts.
* exercise_form ⇐ lesson انتخاب‌گر required؛ course در همان از درس استنباط مخفی خودکار.
* settings.php ⇐ فقط ضروری‌ها (هویت + شبکه‌ها + فوتر). کلیدهای قدیمی حفظ می‌شوند (merge).

**Auth & Users:**
* bootstrap enforce اولین مدیر (backend+DB)، تنزل/حذف آخرین مدیر مسدود، کاربر معمولی نمی‌تواند وارد admin شود یا role تزریق کند (تست).

**Comments / Messages / Contact:**
* comments ⇐ status hidden + admin Create/Approve/Hide/Delete — همه DB-only، هیچ fake در production.
* messages ⇐ dest-db-first Inbox + New/Read + Delete ·· contact handler DB-first ذخیره می‌کند و CSV به‌عنوان mirror نگه می‌دارد.

---

## ۱۰) ریسک‌های باقی‌مانده / پیشنهاد فاز بعدی

۱. **تأیید inline به‌جای confirm()** در حذف‌ها — در حالِ حاضر تأیید کنترل بعدی سروریه است؛ فاز بعد می‌تواند modal درون‌صفحه داشته باشد.
۲. **read-path صفحه‌های عمومی** در فاز بعد به‌کامل به DB متصل می‌شود؛ هم‌اکنون لایه‌ی repository با fallbackِ فایل خوانده می‌شود (مانند verifiable migration).
۳. **فرم‌های باقی‌مانده** (مقاله و پژوهش) در setAfter می‌توانند ساده‌تر شوند تا با spec مطابق شوند.
۴. **Export/Backup روزانه**: پیشنهاد صفحه‌ی پنل با ماژول “صدا/ downalod” در فاز بعد (آنلاک که در عقب-host با cron هم می‌کند).

---

*نتیجه‌ی فاز ۱:* پنل مدیریتِ واقعی، جریانِ امن Draft⇄Publish، دیتابیس v3 با تمامیت تعریف‌شده (FK/CASCADE)، و مهاجرتِ فایل→DB با «تأیید-نخست» — همه‌ی بخش‌ها به‌روزرسانی شده و در تست‌ها سبز هستند؛ «❌ مشکل بحرانیِ» باز در خروجیِ فازِ ۱ باقی نمانده است.
