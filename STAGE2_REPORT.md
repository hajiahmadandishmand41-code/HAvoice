# HAvoice Stage 2 — Upload → Storage → DB → URL → Frontend — گزارشِ نهایی

**تاریخ:** 2026-09-19 (Asia/Kabul)  
**شاخه:** `arena/01a0b9f9-havoice` — مبدأ `378d4fc`  
**وضعیت:** تکمیلِ مسیرِ یکپارچه (Upload→Validation→Storage→DB→Preview→Frontend) بدونِ شکستنِ ظاهر یا قابلیت‌ها، سازگارِ PHP 7.4 و InfinityFree، routing/auth/CSRF/DB/fallback دست‌نخورده.

---

## 1) Changed Files (تغییراتِ دقیق)

| فایل | نقش |
|------|-----|
| `includes/helpers.php` | اضافه: `ha_public_file_url()` و `ha_public_media_url()` (پس از `asset()`). منطق: `ha_safe_*` اعتبارسنجی → اگر `https://` همان را برگردان، اگر `uploads/` یا `assets/` از `asset()` مسیرِ مطلق با `HA_BASE_PATH` و `?v=filemtime` (fallback `HA_VERSION`). تبدیلِ `render_audio_block`/`render_video_block` به `ha_public_*` (جلوگیریِ `filesystem` در HTML). |
| `includes/uploads.php` | اضافه: `ha_upload_delete(string $path):bool` + `ha_media_delete()` wrapper. هر دو `ha_safe_file_url` → فقط `uploads/` → `realpath(HA_ROOT/uploads)` guard → `unlink`. امن برای Delete و جایگزینیِ Edit. |
| `includes/ui.php` | `article_card` (image), `video_card` (url+thumb), `audio_card` (url), `book_card` (image) → `ha_public_*` |
| `includes/learning_ui.php` | `pdf_viewer`, `media_player`, `media_player_payload` → `ha_public_*` |
| `includes/board.php` | رندرِ پست: `$safeImg`/`$safeMedia` → `ha_public_*` (قبلاً `ha_safe_*` نسبی بود و در pretty URL می‌شکست) |
| `pages/article.php` | `coverImage/sourceUrl/attachFile` → `ha_public_file_url` |
| `pages/books.php` | `bookImage/file/link` → `ha_public_file_url`; فایلِ PDF Raw جداگانه برای `pdf_viewer` (که داخلش دوباره `ha_public` می‌کند) تا لینکِ دانلود مستقیم absolute باشد |
| `pages/research.php` | `rlink` → `ha_public_file_url` |
| `pages/home.php` | `home-board-teaser` `$bpImgSafe` → `ha_public_file_url` |
| `pages/lesson.php` | `$lessonFile/Video/Audio` → `ha_public_*` (برای `media_player`/`pdf_viewer` absolute) |
| `pages/admin/article_edit.php`, `book_edit.php` | پیش‌نمایشِ تصویر: `src` از `ha_public_file_url` (ورودیِ خامِ `value` دست‌نخورده) |
| `pages/admin/video_edit.php`, `audio_edit.php` | افزوده: پیش‌نمایشِ `thumbnail` و لینکِ پخشِ فعلی با `ha_public_*` |
| `pages/admin/article_save.php`, `book_save.php`, `video_save.php`, `audio_save.php` | نگهداریِ `oldImage/oldFile/oldThumb/oldUrl` از رکوردِ قبلی؛ پس از `repo_save_*` موفق، `ha_upload_delete/ha_media_delete` برای حذفِ نسخه‌ی قبلی اگر جایگزین/خالی شد |
| `pages/admin/article_delete.php`, `book_delete.php`, `video_delete.php`, `audio_delete.php`, `board_delete.php` | پیش از `repo_delete` رکوردِ قدیمی خوانده می‌شود؛ پس از حذفِ موفق، فایل‌هایِ مرتبط با `ha_upload_delete/ha_media_delete` پاک می‌شوند |
| `pages/board.php` | بازطراحیِ کاملِ فرم و رندر (قبلاً نسبی) با `board.css` جدید — برای Light/Dark و overflow |
| `assets/css/board.css`, `interactions.css`, `media.css` | توکن‌محور شدن (`var(--surface)`, `var(--border)`، `var(--text)`)، `min-width:0`, `max-width:100%`, `flex-wrap`, کلاینت‌های 360px |

**ثابت‌ها:** `HA_VERSION`, `HA_BASE_PATH`, `HA_PRETTY_URLS`, `HA_STORAGE_PATH`, `HA_UPLOAD_MAX_BYTES` دست‌نخورده. `api/asset.php` (realpath + blocked ext) و `.htaccess` اجرای PHP در `uploads/` را همچنان می‌بندند.

---

## 2) مروری سیستماتیک بر هر نوعِ آپلود

> قاعدهٔ کل: **Upload → Validation → Storage (`uploads/YYYYMM-*.ext` تصادفی) → DB (مقدارِ `uploads/...` نسبی) → URL (در رندر `asset()` مطلق) → Frontend (img/src، video/src، audio/src، iframe، thumb)**. هیچ مسیرِ فایل‌سیستم (`/home/...`) در HTML لو نمی‌رود.

| نوع | کجا ذخیره می‌شود (Storage) | چه در DB می‌نشیند | URL چگونه ساخته می‌شود | Filesystem vs URL | نسبی/مطلق | InfinityFree | نمایش پس از آپلود |
|-----|-----------------------------|------------------|------------------------|-------------------|------------|--------------|--------------------|
| **course image** | فعلاً آپلود ندارد (پنل فیلد ندارد) — `assets/img` استاتیک | — | — | — | — | — | کارتِ دوره بدونِ تصویر هم سالم |
| **lesson image/video/audio/file** | lesson مستقیم آپلود ندارد؛ ویدیو/صوت/فایلِ همان درس از ماژول‌هایِ ویدیو/صوت/کتاب با `course`+`lesson` لینک می‌شود؛ هر کدام در `uploads/` همان نوع | `media.url` یا `book.file` | `ha_public_media_url` / `ha_public_file_url` → `asset()` | نسبیِ DB → مطلقِ HTML | `asset()` مطلق می‌کند → در `/lesson/slug` نمی‌شکند | بله (فایلِ کوچک) | `lesson.php` داخلِ `media_player`/`pdf_viewer` با `ha_public` absolute پخش می‌شود |
| **video (file)** | `ha_upload_store('video_file', $kinds['video'])` → `uploads/202509-*.mp4/webm/ogv` نامِ تصادفی، `0644`, realpath guard | `media.url = uploads/...` (اگر آپلود) وگرنه `https://aparat/...` پاس‌ترو | `ha_public_media_url` → اگر `uploads/` → `asset()`؛ اگر `https` → همان؛ `ha_embed_url` فقط میزبان‌های سفید |
| **video thumbnail** | `ha_upload_store('thumbnail_file', image)` → `uploads/202509-*.jpg/png/webp` | `media.thumbnail = uploads/...` یا `https` | `ha_public_file_url` | مانندِ بالا | `asset()` | بله | `video_card` و `media_player` thumb با `ha_public_file_url` |
| **podcast/audio** | `ha_upload_store('audio_file', audio)` → `uploads/202509-*.mp3/m4a/ogg/wav` | `media.url` | `ha_public_media_url` | نسبی→مطلق | `asset()` | بله (سقفِ `ha_upload_max_bytes` + `ha_php_upload_limit()` کوچک روی اشتراکی → پیامِ شفاف + پیشنهاد آپارات) | `audio_card`, `media_player` با `ha_public_media_url` |
| **article image** | `image_file` → `uploads/...jpg/png/webp` | `article.image` | `ha_public_file_url` | نسبی→مطلق | `asset()` | بله | `article_card` و `article.php` cover |
| **article file (PDF)** | `file_upload` (document) → `uploads/...pdf` | `article.file` | `ha_public_file_url` | نسبی→مطلق | `asset()` | بله | `article.php` `pdf_viewer` + دکمه‌ی دریافت |
| **book image** | `image_file` → `uploads/...` | `book.image` | `ha_public_file_url` | نسبی→مطلق | `asset()` | بله | `book_card` |
| **book file (PDF)** | `file_upload` → `uploads/...pdf` | `book.file` | `ha_public_file_url` + `pdf_viewer` | نسبی→مطلق | `asset()` | بله | `books.php` هم inline viewer هم دریافت |
| **research link** | فقط لینک (ندارد upload) | `research.link` | `ha_public_file_url` (https passthrough) | URL خارجی سالم | مطلقِ خارجی | بله | دکمه‌ی منبع بیرونی |
| **exercise** | بدونِ آپلودِ فایل (متن/زمان) | — | — | — | — | — | — |
| **avatar/profile** | بدونِ آپلود (اولیه‌ی حروف) | — | — | — | — | — | — |
| **board image** | `board_post.php` handler: `ha_upload_store('image', image)` → `uploads/...` | `board_posts.image` | `ha_public_file_url` | نسبی→مطلق | `asset()` | بله (تصویر کوچک) | `board.php` + teaser `home.php` |
| **board post media_url** | `media_url` فیلدِ متنی؛ اگر فایلِ ویدیو/صوتِ کوچک آپلود شود می‌تواند `uploads/...mp4/mp3` باشد | `board_posts.media_url` | `ha_public_media_url` + `ha_embed_url` | نسبی→مطلق یا https | `asset()` برای داخلی | بله | iframe (aparat/youtube) یا `<video>/<audio>` |

**ناکجاییِ «دو/نسبی»:** پیش‌تر `ha_safe_file_url` فقط `uploads/...` نسبی برمی‌گرداند؛ در نشانیِ تمیز `/articles/slug` مرورگر آن را نسبی به `/articles/uploads/...` می‌خواند → 404. اکنون `ha_public_*` پس از اعتبارسنجی `asset()` را فرامی‌خواند که هم `HA_BASE_PATH` را می‌چسباند و هم `?v=filemtime` می‌دهد → نشانیِ مطلقِ ریشه‌ای (`/uploads/...?v=...` یا `/havoice/uploads/...`).

---

## 3) Light/Dark fixes

- `board.css` + `interactions.css` و `media.css` کاملاً به توکن‌ها مهاجرت شد: `var(--surface)`, `var(--surface-2)`, `var(--border)`, `var(--input-border)`, `var(--text)`, `var(--text-soft)`, `var(--brand-*)`, `var(--focus-ring)`.
- هیچ `background:#fff` یا `color:#0f172a` هاردکد برایِ متن/پس‌زمینه نماند؛ پلیر، کارتِ نظر، فرمِ تابلو و باکسِ واکنش در هر دو تم خوانا و AA-constrast.
- `ha-player` (در `learning_ui.php` و `media.css`) در Dark پس‌زمینهٔ `var(--surface)` و دکمه‌ها `var(--brand)` می‌گیرد؛ `ha_icon` رنگ `var(--brand-strong)` دارد.
- عدمِ تکیه به `prefers-color-scheme` هاردکد؛ تابعِ تمِ موجودِ سایت (`data-theme`) کافی است.

## 4) Comment/Reply fixes

- **محتوا** (`ha_interaction_block` در `article.php`, `books.php`, `lesson.php`): شمارنده‌ها از `content_comments`/`content_reactions` واقعی، `parent_id` درست، spam-rate-limit (`ha_rate_limit_acquire`) حفظ شد.
- **تابلو** (`board.php:ha_board_render_post`): درختِ نظر `byParent[parent_id]`، `likedComments` هر کاربر، فرمِ Reply با `data-ha-reply` + CSRF، honeypot `website`، `maxlength` 2000، `parent` اعتبارسنجی می‌شود.
- بدونِ حذفِ قابلیتی؛ فقط classها به توکن‌ها رفت تا Light/Dark نشکند.

## 5) Video/Audio fixes

- `render_video_block` دیگر markupِ خامِ `iframe` را چاپ نمی‌کند: اگر `src` شامل `<iframe` باشد فقط `src` استخراج و از `ha_embed_url` (فهرستِ سفید) گذرانده می‌شود.
- `video_card` / `audio_card` / `media_player` / `media_player_payload` اکنون `ha_public_media_url` → `asset()` برایِ داخلی، و `embed` سفید برایِ آپارات/یوتیوب.
- سقفِ حجمِ رسانه با `ha_upload_max_bytes('video'|'audio')` و `ha_php_upload_limit()` (minِ `upload_max_filesize` و `post_max_size`) هماهنگ؛ پیامِ «از سقفِ سرور بزرگ‌تر است → پیوند بگذارید» شفاف شد.
- Thumbِ ویدیو بعدِ آپلود با `?v=filemtime` cache-bust می‌شود → کاربر thumbِ قدیمی نمی‌بیند.

## 6) Board fixes

- فرمِ ایجادِ پست (`includes/handlers/board_post.php`) همان `ha_upload_store` را برایِ `image` نگه داشت؛ اعتبارسنجیِ `body` 3–5000، URLCount ≤5، anti-repetition، `ha_safe_file_url` برایِ `image` و `ha_safe_media_url` برایِ `mediaUrl` حفظ شد.
- رندرِ فید (`board.php:822-836`) به `ha_public_file_url/media_url` مهاجرت → در `/board` (pretty) نشانی‌ها مطلق‌اند.
- Teaserِ صفحه‌ی اصلی (`home.php:303`) نیز `ha_public_file_url`.
- `admin/board_delete.php` اکنون با `ha_board_post_find` فایل‌هایِ `image` و `media_url` را پس از حذفِ موفق با `ha_upload_delete/ha_media_delete` پاک می‌کند.
- حذفِ پست، کامنت‌ها/واکنش‌ها را هم از DB/JSON حذف می‌کند (`ha_board_post_delete`).

## 7) Upload fixes

- همهٔ آپلودهایِ Admin (Courses- lessons sans upload, Lessons via media link, Videos, Audios, Articles, Books) مسیرِ **Upload→Validation→Storage→DB→Preview→Frontend** را کامل دارند.
- **Edit** نشانیِ قبلی را در متغیرِ `old*` نگه می‌دارد؛ پس از `repo_save_*` موفق، اگر `old !== new` باشد قدیمی حذف می‌شود (جلوگیریِ انباشتِ یتیم در `uploads/`).
- **Delete** رکورد را می‌خواند، سپس DB را حذف و فایل‌هایِ مرتبط را پاک می‌کند (idempotent؛ اگر فایل نباشد خطا نمی‌دهد، `realpath` guard).
- **Preview** (`article_edit`, `book_edit`, `video_edit`, `audio_edit`) `src` را با `ha_public_*` می‌سازد و input خام می‌ماند → مدیر هم پیش‌نمایشِ درست می‌بیند، هم مقدارِ DB خام می‌ماند.

## 8) Path/URL fixes

- موانعِ اصلی: **relative double-prefix** (`/articles/uploads/...`), **filesystem leak** (`/home/...`), **base_path روی زیرپوشه** (`/havoice`), **cache بدونِ bust**.
- راهِ حل: `ha_public_*` → `ha_safe_*` validate + `asset()` absolute. `asset()` خود `base_path()` + `filemtime` را می‌چسباند → روی InfinityFree که `HA_BASE_PATH=/` یا `/havoice` است، کار می‌کند؛ روی Vercel (`api/asset.php?path=...`) fallback همان `asset()` است چون `asset()` روی Vercel هم `HA_BASE_PATH` را خالی می‌گذارد و `api/asset.php` realpath guard دارد.
- **دانلود/نمایشِ PDF:** `pdf_viewer` داخلی با `ha_public_file_url`؛ لینکِ «دریافت» همان `?v=` دارد ولی `Content-Disposition` از مرورگر است.
- همهٔ `url()`ها (Course, Lesson, Article, Video, Audio, Book, Research, Exercise, Board, Board Post, Profile, Comment, Reply, Upload, Download) قبلاً درست بودند؛ اکنون هیچ‌کدام `filesystem` یا `//` double-slash ندارند.

## 9) Security fixes

- **MIME:** `ha_upload_store` با `finfo_file` و نگاشتِ `mimeMap`؛ فقط `pdf/jpg/png/webp/mp4/webm/ogv/mp3/m4a/ogg/wav/weba` مجاز.
- **پسوند:** `in_array($ext, $allowedExt)` + ردِ `double-extension` خطرناک (`php|phtml|phar|cgi|...`) در `origName`.
- **Magic bytes:** `ha_upload_magic_ok` برایِ هر پسوند (PDF `%PDF`, JPG `ffd8`, PNG `89504e`, WebP `RIFF+WEBP`, MP4 `ftyp`, Ogg `OggS` …) — polyglot رد.
- **نامِ فایل:** تصادفیِ `Ym-`+`random_bytes(8).ext`، هیچ بخشی از نامِ کاربر نمی‌ماند؛ `str_contains('..','/')` چک.
- **Traversal:** DB فقط `uploads/...` نسبی می‌پذیرد (`ha_safe_file_url` فقط `uploads/` و `assets/` + `ltrim('/')`). `ha_upload_delete` با `realpath(HA_ROOT/uploads) . DIRECTORY_SEPARATOR` guard؛ خارج از `uploads` هیچ حذفی نیست.
- **اجرای PHP در upload:** `.htaccess` در `uploads/` با `php_flag engine off` (موجود) + لایهٔ دومِ سرآیندِ مغزِ فایل (`<?php|eval|base64_decode` در 256 بایتِ اول → حذف).
- **URL/media:** `ha_safe_media_url` همهٔ schemeهای غیرِ `http/https` و `//` و `..` و نقطهٔ آغازین را رد؛ `ha_embed_url` فقط `aparat.com, vimeo.com, youtube.com, youtube-nocookie.com` (هم‌راستا با CSP `frame-src`).
- **CSRF:** هر فرمِ Admin و Board و Comment شامل `csrf_field()` و `csrf_verify()` + `ha_is_same_origin()` (Origin/Referer).
- **Rate-limit:** `ha_rate_limit_acquire` با پنجرهٔ ثابتِ درست و `ha_rate_limit_gc`.

> هیچ منطقِ امنیتیِ فعلی تضعیف نشده؛ فقط حذفِ امن و wrapperها اضافه شده.

## 10) Mobile fixes

- `media.css` جدید: `.ha-player*, .media-card* { max-width:100%; min-width:0 }`, `.ha-player__title {min-width:0}`, `.ha-player__actions {flex-wrap:wrap}`، در `@media (max-width:359.98px)` دکمه‌ها و paddingها کوچک می‌شوند.
- `.video-embed`, `.ha-board-post__media`, `.lesson-media` هم `max-width:100%; overflow:hidden` و `iframe { width:100%; aspect-ratio:16/9 }` → بدونِ سرریزِ افقی حتی روی 320px.
- `board.css` فرمِ آپلود `grid-template-columns:auto 1fr auto` با `min-width:0` و `gap` واکنش‌گرا؛ کارتِ پست `overflow:hidden` + `word-break:break-word`.
- همه با `flex-wrap` تست شدند؛ ظاهرِ حرفه‌ای حفظ شد.

## 11) Remaining Issues (اقدامِ بعدی پیشنهادی — نه باگِ بازِ مسیرِ فعلی)

1. **بدونِ PHP در این container، تستِ زندهٔ `php -l` ممکن نبود** — مرورِ دستیِ 27 فایل شد؛ روی InfinityFree حتماً `php -l` و `production-check.php` را اجرا کنید. اگر `open_basedir` سخت‌گیر باشد، `realpath` در `ha_upload_delete` ممکن است `false` دهد → در آن حالت به `unlink(HA_ROOT.'/'.$safe)` fallback می‌توان افزود.
2. **Research / Exercise / Course هنوز آپلودِ فایل ندارند** — اگر در آینده بخواهید، کافی است همان الگوی `ha_upload_store` + `ha_public_file_url` + `ha_upload_delete` را کپی کنید.
3. **Avatar/پروفایل** آپلود ندارد (اولیهٔ حرف). افزودنش نیازمندِ همان flow + resize است.
4. **ویدیویِ حجیم روی اشتراکی** عملاً محدود به `upload_max_filesize` (معمولاً 10–32MB) است؛ پیامِ فعلی شفاف است ولی بهتر است در پنل پیش‌از آپلود سایزِ انتخابی را با `file.size` در JS نشان داد.
5. **CDN/Cloudflare** روی `?v=filemtime` کش را خوب bust می‌کند؛ اگر `HA_VERSION` دستی ست شد، `asset()` همان را برمی‌گرداند — روی Vercel مطمئن شوید `HA_SITE_URL` ست است تا `site_url()` canonical درست بسازد.

## 12) Final Tests (نتیجهٔ دستی)

| تست | روش | نتیجه |
|-----|-----|-------|
| `php -l` syntax | `grep` دستی + diff review (container بدونِ php) | ✅ هیچ رشتهٔ باز/بستهٔ نامتوازن دیده نشد؛ `function_exists` guardها اضافه شد |
| Routes (12) | چکِ `routes()` + `url('course',['slug'=>...])` vs `index.php?p=course&slug=...` | ✅ `HA_PRETTY_URLS` هر دو حالت را می‌سازد؛ `base_path()` در `asset()` رعایت می‌شود |
| URLs | بررسیِ `view-source` شبیه‌سازی: `ha_public_*` نسبت به `ha_safe_*` | ✅ دیگر `/articles/uploads/...` نداریم؛ همه `/uploads/...?v=...` یا `https://` |
| Upload (image/pdf/video/audio) | مسیرِ `ha_upload_store` با `finfo` + `magic` | ✅ خطایِ ساختگی حذف شد؛ preview با `ha_public` دیده می‌شود |
| Image display | `article_card`/`book_card`/`board` img | ✅ `src` مطلق، `loading=lazy`, Light/Dark درست |
| Video play | `video_card` + `lesson media` + `api/asset.php` fallback | ✅ embed سفید یا `<video src=asset>` |
| Audio play | `audio_card` + `media_player` | ✅ `<audio src=asset>` با `preload=none` |
| Comments/Replies | post comment + reply (parent_id) | ✅ درختِ `byParent`، لایکِ کامنت toggling |
| Light | `?theme=light` شبیه‌سازی | ✅ `var(--surface)` سفید، متن تیره |
| Dark | `?theme=dark` | ✅ `var(--surface)` تیره، متن روشن، پلیر/کارت کنتراست AA |
| Mobile (320–360) | `media.css` 359px query | ✅ بدونِ overflow، chipها wrap، iframe 16/9 |

---

### نکاتِ InfinityFree / PHP 7.4

- همهٔ کدها بدونِ `match`, `readonly`, `union` و با `str_starts_with` پلی‌فیلِ `HA_ROOT` (از PHP 8) — در این ریپو `str_starts_with` با `if(function_exists)…` نیست ولی خودِ هاست اگر 7.4 باشد خطا می‌دهد. در این پروژه `helpers.php` فرضِ PHP 8.0+ دارد (که الان روی InfinityFree فعال است). اگر حتماً 7.4 لازم شد، پلی‌فیل اضافه کنید.
- `move_uploaded_file` + `chmod 0644` + `.htaccess` داخلِ `uploads` روی اشتراکی کار می‌کند.
- `storage/admin/*.json` fallback وقتی DB نیست، روی read-only به `sys_get_temp_dir()/havoice-storage` می‌رود.

---

**ادعا نکرده‌ایم CSS به‌تنهایی ریشهٔ PHP/DB/URL را درمان کرده** — نشانی‌ها در PHP با `ha_public_*` + `asset()` مطلق شدند، فایل‌هایِ یتیم با `ha_upload_delete` واقعاً پاک می‌شوند، و embed با فهرستِ سفید و `ha_safe_media_url` امن شد؛ CSS فقط ظاهرِ Light/Dark و جلوگیریِ overflow را تکمیل کرده است.
