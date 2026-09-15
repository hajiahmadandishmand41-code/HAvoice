# HAvoice — Stage 1 Deep Audit Report

**Date:** 2026-09-15  
**Branch:** `arena/01a0a3b2-havoice`  
**Version in config:** `HA_VERSION = 3.1.0`  
**Scope:** Full repository inspection only. No large new features. Only crash-prevention fixes applied (listed at end).

---

## 0. Executive map

| Layer | Reality |
|---|---|
| Runtime | Pure PHP (no Composer, no Node build) |
| Front entry | `index.php` → `includes/bootstrap.php` |
| Content primary store | PHP arrays under `data/*.php` |
| Content admin overrides | JSON under `storage/admin/*.json` (runtime, gitignored) |
| Users / sessions / contact | File-based under `storage/` |
| Database | **Only** public comments (`ha_comments`) via **mysqli** — credentials empty by default |
| Production target | PHP + MySQL optional + InfinityFree |
| Preview | Vercel PHP runtime (`vercel-php@0.9.0`) — not durable storage |

---

## 1. Repository structure (verified)

```
HAvoice/
├── index.php, sitemap.php, robots.php, robots.txt, .htaccess
├── api/                 Vercel shims (index, asset, sitemap, robots)
├── assets/css|js|fonts|img
├── config/config.php    single deploy config
├── data/                file-based content (PHP return arrays)
├── docs/                reports
├── includes/            bootstrap, helpers, content, auth, ui, meta, comments, uploads, handlers
├── pages/               public pages + pages/admin/* CRUD
├── sql/001-create-comments.sql
├── storage/             messages, sessions, rate-limit, admin JSON (runtime)
├── uploads/             admin uploads (script execution blocked)
├── tools/selfcheck.php
├── vercel.json, .vercelignore, .github/workflows/php.yml
```

**Routing:** whitelist in `includes/helpers.php` → `routes()`. Query mode default: `?p=route&slug=…`. Pretty URLs off (`HA_PRETTY_URLS = false`). Auth-gated routes: `course`, `lesson`, `exercises`. Admin routes flagged `admin => true` and enforced via `auth_require_admin()` inside admin layout.

---

## 2. Content source map (where each type comes from)

| Content type | File (`data/`) | Admin override (`storage/admin/`) | Database | Hybrid merge |
|---|---|---|---|---|
| Categories | `categories.php` | `categories.json` | — | Yes (`ha_merge_overrides`) |
| Courses / Stages / Lessons | `course.php` | `courses.json` | — | Yes (`courses_all`) |
| Exercises | `exercises.php` | `exercises.json` | — | Yes (key=`id`) |
| Tips | `tips.php` | `tips.json` | — | Yes (key=`id`) |
| Articles | `articles.php` | `articles.json` | — | Yes |
| Books | `books.php` | `books.json` | — | Yes |
| Research | `research.php` | `research.json` | — | Yes |
| Videos + Audios | `media.php` (type field) | `media.json` | — | Yes |
| Learning paths | `learning_paths.php` | — | — | **File only** (no admin CRUD) |
| Site copy / FAQ / social | `site.php` + `config.php` | partial via `settings.json` (name/tagline/social) | — | Hybrid for brand name |
| Users | — | `storage/users.json` | — | File |
| Contact messages | — | CSV `storage/messages/` + optional panel JSON | — | Dual file stores |
| Rate limits | — | `storage/rate-limit/` | — | File |
| Sessions | — | `storage/sessions/` | — | File |
| Public comments | — | — | **MySQL `ha_comments`** | DB only |
| Uploads (PDF/cover) | — | files in `uploads/` | — | File |

**Visibility:** `ha_visible()` filters `status === 'draft'`. Admin can hide file-based items by writing a panel override with draft status (file stays intact).

---

## 3. Course architecture (actual)

```
Category (data/categories.php)
  └── Course (data/course.php → courses[])
        └── Stage (stages[])
              ├── assessment? (optional quiz checklist)
              └── Lesson (lessons[])
                    ├── blocks[] (rich content)
                    ├── drill? (inline lesson exercise)
                    ├── prerequisite? (lesson slug)
                    └── refs?
        └── project? (final project)
        └── how_to[]

Standalone Exercise (data/exercises.php)
  └── links via fields: lesson (slug), course (slug)
```

**Notes:**
- Hierarchy is **in-memory arrays**, not relational DB tables.
- `course_lesson_index()` flattens all lessons by unique lesson slug.
- Neighbours (`course_neighbours`) stay **inside the same course** (good).
- Admin course editor stores nested stages/lessons as **raw JSON textarea** (powerful but fragile UX).
- Legacy single-course keys (`title`, `stages` at root of `course.php`) still present for backward compatibility; `courses()` prefers `courses[]`.

---

## 4. Inventory: Courses / Stages / Lessons / Exercises

### 4.1 Categories (12)

| slug | title | Has course? | Notes |
|---|---|---|---|
| public-speaking | فن بیان و سخنوری | Yes | Primary full curriculum |
| communication | ارتباط مؤثر | Yes | |
| psychology | روانشناسی و خودشناسی | Yes | |
| success | موفقیت و رشد فردی | Yes | |
| life-skills | مهارت‌های زندگی | **No course** | 1 empty-url video only |
| goals-time | هدف‌گذاری و مدیریت زمان | Yes | |
| career | مهارت‌های کاری و حرفه‌ای | **No course, no media** | Empty shell |
| negotiation | مذاکره و متقاعدسازی | Yes | |
| books | کتاب و خلاصه کتاب | Nav alias → books page | Not a course category |
| research | تحقیقات و مقالات | Nav alias → research | |
| podcast | پادکست | Nav alias → audios | |
| video | ویدیو | Nav alias → videos | |

### 4.2 Courses (6)

| # | slug | title | level | category | stages | lessons | featured | minutes field |
|---|---|---|---|---|---|---|---|---|
| 1 | `public-speaking-fundamentals` | فن بیان و سخنوری — مسیرِ پایه | مقدماتی تا متوسط | public-speaking | 3 | **10** | yes | 113 |
| 2 | `communication-essentials` | ارتباط مؤثر — شنیدن و گفتنِ دقیق | مقدماتی | communication | 2 | **3** | yes | 29 |
| 3 | `psychology-self-awareness` | روانشناسیِ کاربردی — خودشناسی و هیجان | مقدماتی | psychology | 1 | **3** | no | 29 |
| 4 | `habits-success-foundation` | موفقیتِ پایدار — عادت و تمرکز | مقدماتی | success | 1 | **2** | no | 18 |
| 5 | `goals-time-management` | هدف‌گذاری و مدیریتِ زمانِ واقع‌گرا | مقدماتی | goals-time | 1 | **2** | no | 18 |
| 6 | `negotiation-persuasion` | مذاکره و متقاعدسازیِ اخلاقی | متوسط | negotiation | 1 | **2** | no | 22 |

**Total lessons: 22** (all have `drill`).

### 4.3 Stages detail

**Course 1 — public-speaking-fundamentals**

| stage id | title | lessons (order) | outcome focus |
|---|---|---|---|
| stage-1 | پایه: نفس، صدا، بدن | breathing-foundations → resonance-and-power → body-supports-words → rhythm-and-pause | 10 min voice without fatigue |
| stage-2 | ساختار | opening-hook → three-point-body → ending-and-ask | 3-min structured talk |
| stage-3 | اجرا | nerves-into-energy → hard-questions → impromptu-sixty | stay in rhythm under stress |

**Course 2 — communication-essentials**

| stage | lessons |
|---|---|
| comm-1 شنیدنِ فعال | active-listening-foundations, feedback-sbi |
| comm-2 گفت‌وگوی سخت | hard-conversations |

**Course 3 — psychology-self-awareness**

| stage | lessons |
|---|---|
| psy-1 | labeling-emotion, thought-feeling-behavior, reframe-self-talk |

**Course 4 — habits-success-foundation**

| stage | lessons |
|---|---|
| suc-1 | atomic-habit-two-min, deep-work-block-lesson |

**Course 5 — goals-time-management**

| stage | lessons |
|---|---|
| gt-1 | smart-goal-lite, time-blocking-mit |

**Course 6 — negotiation-persuasion**

| stage | lessons |
|---|---|
| neg-1 | batna-preparation, anchoring-objective-criteria |

### 4.4 Lessons (22) — title / slug / minutes / goal / educational gap

| slug | title | min | goal (summary) | course | educational gap |
|---|---|---|---|---|---|
| breathing-foundations | تنفس دیافراگمی… | 10 | diaphragmatic breath, long phrases | PS | Strong; no media embed |
| resonance-and-power | رسونانس… | 12 | resonance without shout | PS | Strong |
| body-supports-words | زبان بدن… | 11 | stance/eye/hands | PS | Strong |
| rhythm-and-pause | ریتم و مکث… | 11 | designed pauses | PS | Strong |
| opening-hook | شصت ثانیه اول… | 10 | 3 opening patterns | PS | Strong |
| three-point-body | بدنه‌ی سه‌نقطه‌ای… | 13 | three pillars | PS | Strong |
| ending-and-ask | پایان‌بندی… | 9 | ask-based close | PS | Strong |
| nerves-into-energy | استرس… | 12 | 3-min pre-protocol | PS | Strong; clinical boundary noted |
| hard-questions | سؤال دشوار… | 14 | bridge formula | PS | Strong |
| impromptu-sixty | بداهه‌گویی… | 11 | two frameworks | PS | Strong |
| active-listening-foundations | گوش دادن فعال | 9 | restatement + open Q | Comm | Good; shorter depth |
| feedback-sbi | بازخورد SBI | 8 | SBI feedback | Comm | Good |
| hard-conversations | گفت‌وگوی سخت | 12 | hard talk script | Comm | Only 1 lesson in stage 2 |
| labeling-emotion | برچسب‌گذاری هیجان | 10 | name emotion | Psy | Good; not clinical |
| thought-feeling-behavior | مثلث شناختی | 9 | CBT triangle lite | Psy | Good |
| reframe-self-talk | گفت‌وگوی درونی | 10 | reframe self-talk | Psy | Good |
| atomic-habit-two-min | قانون دو دقیقه | 8 | 2-min habit | Suc | Thin course (2 lessons) |
| deep-work-block-lesson | کار عمیق | 10 | 90-min block | Suc | Thin |
| smart-goal-lite | هدف SMART | 9 | SMART + if-then | GT | Thin |
| time-blocking-mit | MIT + time block | 9 | daily MIT design | GT | Thin |
| batna-preparation | BATNA | 11 | BATNA + walk-away | Neg | Thin; prereq text only |
| anchoring-objective-criteria | لنگر + معیار | 11 | ethical anchor | Neg | Thin |

**Curriculum imbalance (HIGH educational):** Public speaking is a full path (10 lessons + assessments + final project). Other five courses are **stubs relative to promise** (2–3 lessons). Categories `career` and `life-skills` have **no course**.

### 4.5 Exercises (24) — all linked OK

All 24 exercises in `data/exercises.php` reference existing lesson + course slugs (verified). Tools: `timer`, `timer+topics`, `steps`.

| course | exercise count |
|---|---|
| public-speaking-fundamentals | 12 |
| communication-essentials | 3 |
| psychology-self-awareness | 3 |
| habits-success-foundation | 2 |
| goals-time-management | 2 |
| negotiation-persuasion | 2 |

**Note:** Each lesson also has an inline `drill`. Standalone exercises duplicate/extend those drills — intentional hybrid, but UI can feel redundant (MEDIUM).

### 4.6 Other content counts

| Type | Count | Real media? |
|---|---|---|
| Articles | 11 | Text-only blocks (real educational copy) |
| Books | 6 | Summaries only; **no PDF/file/image** |
| Research | 4 | Text + refs (secondary review style) |
| Tips | 16 | Real short tips |
| Learning paths | 3 | File-only composites |
| Videos | 6 | **All `url: ''` → placeholder «به‌زودی»** |
| Audios | 4 | **All `url: ''` → placeholder** |

---

## 5. Fake / Demo / Placeholder / bogus URL findings

| Item | Status |
|---|---|
| All 6 videos | Placeholder (empty url) — intentional, no fake YouTube links |
| All 4 audios | Placeholder |
| Books | No downloadable files; summary cards only |
| Instructor image / banner | Real local assets (`instructor.jpg`, `fanbayan-banner.webp`) |
| Social Instagram URL | Had tracking junk `?stkn=…` — **fixed** to clean profile URL |
| Contact phones | **Inconsistent identity:** `HA_PHONE` / `HA_HOTLINE` look Iran-style (`076…` / `+98…`) while WhatsApp is Afghanistan (`+93 798…`) |
| FAQ / course UI | Claimed “no registration required” while routes gate course/lesson/exercises — **copy fixed** |
| Admin instructional box on public videos/audios pages | Developer/admin instructions leaked to public — **removed** |
| Learning path minutes | Approximate marketing numbers, not computed |

No inventing of fake video CDN URLs was found; placeholders are honest empty strings.

---

## 6. Frontend “how to add video” (admin-facing text)

| Location | Was | Action |
|---|---|---|
| `pages/videos.php` info-box «نحوه‌ی افزودنِ ویدیوی واقعی» + `data/media.php` instructions | Public-facing admin manual | **REMOVED** (essential) |
| `pages/audios.php` info-box telling to edit `data/media.php` | Public-facing | **REMOVED** |
| Admin `video_edit.php` field help (Aparat/YouTube/mp4) | Correct admin UX | **KEEP** |
| Placeholder UI strings in `helpers.php` / `ui.php` («به‌زودی») | Correct empty state | **KEEP** until real media |

---

## 7. Dual video add paths

| Path | Mechanism | Should remain? |
|---|---|---|
| **A. Admin panel** `admin_video_edit` → `admin_video_save` → `storage/admin/media.json` | Official runtime CRUD | **KEEP (primary)** |
| **B. File edit** `data/media.php` | Seed/default content for deploy | **KEEP as seed only** (not as public instruction) |
| **C. Lesson block** `type: video` inside course lesson `blocks` | Inline lesson media via `render_video_block()` | **KEEP** for curriculum embeds |
| **D. Upload field** | Book/article file/image upload to `uploads/`; video form is URL-only (no file upload field) | Video upload file path is **missing** — LATER if needed |

**Decision:** One public product path = **Admin URL form**. File `data/media.php` is seed. Do not document file editing on the public site.

---

## 8. Admin audit

| Area | Status | Notes |
|---|---|---|
| Login | File users + session | `password_hash` / `password_verify`, CSRF, rate limit |
| Authorization | `auth_is_admin()` | **First registered user is always admin** OR `role=admin`. Risk if first signup is random visitor on fresh host |
| Dashboard | Fixed this stage | Was using wrong message ids + incomplete counts |
| CRUD | Courses, articles, videos, audios, books, research, exercises, tips, categories, users, messages, comments, settings | Present |
| Course form | JSON stages textarea | High power, high error risk |
| Forms | CSRF on POSTs | Good |
| Upload | `includes/uploads.php` whitelist MIME + random name | Books/articles; not videos |
| Content status | `admin_content_status` registry | Can draft file-based items via override |
| Settings | name/tagline/social | Applied via `ha_site_name()` / `ha_site()` |
| Messages | Dual CSV + panel JSON | List page uses stable `csv-N`/`pan-N` refs; dashboard was broken (fixed) |
| Comments admin | Needs DB configured | Graceful empty if DB empty |

---

## 9. Database audit

### Is DB

| Table | Purpose | Engine |
|---|---|---|
| `ha_comments` | Public comments moderation | mysqli prepared statements |

Config keys empty by default: `HA_DB_HOST/NAME/USER/PASS`. Site stays up without DB.

### Still file-based (and should stay or migrate later)

| Data | Store | Recommendation |
|---|---|---|
| Courses/lessons | PHP + admin JSON | **DATABASE later** if multi-editor / scale |
| Articles/books/research/media/exercises/tips/categories | PHP + admin JSON | **DATABASE later** |
| Users | `users.json` | **DATABASE** for production multi-server / durability |
| Contact messages | CSV + JSON | **DATABASE later** (or single store) |
| Settings | JSON | OK file or DB |
| Progress | browser localStorage | Optional server progress **LATER** |
| Sessions | files | OK for InfinityFree |

### Relationships (logical, not FK)

- Category.slug ← Course.category  
- Course.stages[].lessons[].slug ← unique global  
- Exercise.lesson → Lesson.slug  
- Exercise.course → Course.slug  
- Media.category → Category.slug  
- Learning_paths.steps → mixed type/slug references  

### Duplicate / orphan risks

| Risk | Severity |
|---|---|
| Duplicate lesson slugs across courses silently overwrite in `course_lesson_index()` | HIGH if admin creates collision (save warns) |
| Panel override same slug replaces file item in merge | By design |
| Contact dual stores (CSV vs panel) | Mitigated by ref system; dashboard was still wrong → fixed |
| Categories without content (career, life-skills thin) | MEDIUM orphan nav |
| Learning paths not in admin | Orphan feature surface |
| README outdated (“no database”, single course) | MEDIUM docs drift |

**No PDO usage.** Comments use **mysqli** (correct for InfinityFree). CI PHP extensions list does **not** include `mysqli` (HIGH for CI realism).

---

## 10. InfinityFree compatibility

| Check | Result |
|---|---|
| PHP plain files, no Composer | OK |
| `.htaccess` with `<IfModule>` guards | OK |
| Sensitive dirs denied | OK (config/data/includes/storage/pages/tools) |
| uploads no script exec | OK |
| Sessions fallback to `storage/sessions` | OK |
| `mail()` default off | OK (`HA_SEND_MAIL false`) |
| Pretty URLs off by default | OK |
| Writable `storage/` required | Must be 755/writable on host |
| MySQL comments optional | OK |
| `mysqli` (not PDO) | OK for shared hosting |
| Path `HA_BASE_PATH` | Empty = domain root; set if subdirectory |
| `HA_SITE_URL` empty = auto host | Prefer hardcode in production |
| `HA_FORCE_HTTPS` false | Enable after SSL |
| File-based users on free host | Works single-node; no multi-instance sync |
| Upload max 8MB | May hit host PHP limits |

---

## 11. Vercel (preview only)

| Item | Note |
|---|---|
| `vercel.json` routes all to `api/index.php` (vercel-php) | Preview OK |
| `storage` in `.vercelignore` | Runtime uses temp via `storage_dir()` |
| Uploads / admin writes | **Not durable** on serverless |
| Sessions / users.json | Ephemeral |
| **Do not force Vercel as production** | Correct — production = InfinityFree PHP/MySQL |

---

## 12. Performance audit

| Issue | Severity | Note |
|---|---|---|
| `assets/css/style.css` ~172 KB | MEDIUM | Single large CSS; no purge |
| `assets/js/main.js` ~40 KB | LOW | Acceptable vanilla |
| Font woff2 ~111 KB | LOW | Local Vazirmatn — good |
| Images | LOW | Few, small (banner 7.5KB, instructor 9KB) |
| No npm deps | GOOD | |
| Every request loads all data PHP files via `data()` | MEDIUM | Fine at current size; will hurt if content grows |
| `course_lesson_index()` rebuild static cache per request | LOW | OK |
| Fake empty media still rendered as cards | MEDIUM | UX noise; consider hide empty-url or mark clearly |
| Dashboard previously incomplete counts | FIXED | |
| Query extras | N/A | Almost no SQL |

---

## 13. Mobile UI audit

| Area | Status |
|---|---|
| Header + hamburger | Dedicated `mobile-layout.css`; drawer nav ≤1119px; OK design |
| Overflow-x clip | Present |
| Cards / grids | Collapse to 1 col ≤767px |
| Course / lesson / exercise | `min-width:0` guards; OK |
| Admin | Sidebar → drawer with toggle; tables in `admin-table-wrap` |
| Forms | Full-width buttons on home hero |
| Risk | Very small screens hide brand text / auth icons (≤419px) — intentional but discoverability drops |
| Admin tables | Horizontal scroll needed on phone — acceptable |
| Course JSON editor | Unusable on phone for real editing — admin desktop assumption |

No emergency mobile crash fix required beyond existing layout CSS.

---

## 14. CRITICAL / HIGH / MEDIUM / LOW

### CRITICAL
1. ~~Admin dashboard message “مشاهده” used reverse index `$i` instead of stable `ref` → wrong/missing message~~ **FIXED**
2. First-user-auto-admin on empty `users.json` — any first registrant owns the site (ops risk on fresh deploy)
3. Vercel must not be treated as durable production (data loss for users/messages/uploads)

### HIGH
1. Auth gate on course/lesson/exercises **contradicted** public copy (“بدون ثبت‌نام”) — **copy fixed**; product decision still needed (gate vs open)
2. All video/audio are empty placeholders while homepage teasers sell them
3. Curriculum imbalance: 1 full course vs 5 thin courses; empty categories career / thin life-skills
4. Dual contact message stores complexity (CSV + JSON) — dashboard fixed, but long-term consolidate
5. CI workflow lacks `mysqli` extension while comments depend on it
6. README still describes “بدون دیتابیس” / old single-course structure — docs drift
7. Users file-based — not suitable if moving to multi-instance production

### MEDIUM
1. Course admin via JSON textarea — error-prone
2. Learning paths have no admin CRUD and weak surface area
3. Books without files/images
4. Phone/country inconsistency (IR config vs AF WhatsApp)
5. Large monolithic CSS
6. Progress only in localStorage (lost cross-device; OK for now)
7. Inline lesson drills + standalone exercises overlap
8. `HA_PRETTY_URLS` false — uglier URLs on production until rewrite enabled carefully
9. Article categories are free-text labels, not always category slugs (filter inconsistency risk)

### LOW
1. Legacy root keys in `course.php` (title/stages duplicate of first course)
2. Instagram stkn param — **fixed**
3. Inline style on old dashboard more-link — replaced with class
4. Tips/exercises use `id` not `slug` — fine but inconsistent keys
5. Selfcheck HTTP patch in CI mutates file at runtime (works but brittle)

---

## 15. KEEP / FIX / REMOVE / DATABASE / LATER

### KEEP
- Pure PHP architecture, no framework
- File seed content in `data/*.php`
- Admin JSON override merge pattern
- Auth + CSRF + rate limits
- Comments mysqli layer (optional DB)
- Public speaking full curriculum quality
- Honest empty media placeholders (no fake URLs)
- InfinityFree-oriented `.htaccess` hardening
- Mobile layout CSS isolation
- Vercel as preview only

### FIX (done this stage)
- Dashboard message links + counts
- Public admin instructions on videos/audios pages
- Contradictory “no registration” copy
- Instagram dirty URL

### FIX (next stages, not done now)
- Decide auth policy vs open curriculum and align product
- Seed or hide empty media; wire real Aparat/YouTube or files via admin only
- Flesh thin courses or mark them “coming”
- Remove/hide empty career category or add content
- Unify contact message storage
- Add mysqli to CI
- Update README to match v3 reality
- Soft-lock first admin via config secret / invite

### REMOVE
- Public “how to edit data/media.php” boxes — **done**
- Eventually: legacy single-course root keys after full multi-course confidence
- Placeholder media cards from homepage teasers if still empty (product choice)
- Outdated README claims

### DATABASE (target when rebuilding)
1. users (+ roles)  
2. courses / stages / lessons / lesson_blocks  
3. exercises (FK lesson_id, course_id)  
4. media (video/audio)  
5. articles / books / research / tips  
6. categories  
7. contact_messages  
8. comments (already)  
9. settings key-value  
10. optional: lesson_progress per user  

### LATER
- Pretty URLs enablement with tested rewrite
- Server-side progress
- Video file upload (not only URL)
- Visual course curriculum builder (replace JSON textarea)
- Learning paths admin
- CDN/cache strategy if traffic grows
- Full PDO abstraction only if needed — mysqli is fine on InfinityFree

---

## 16. Essential fixes applied in Stage 1

| File | Change |
|---|---|
| `pages/admin/dashboard.php` | Use `admin_messages_all()` + stable `ref`; counts from `*_all()` / media_all |
| `pages/videos.php` | Remove public admin how-to box |
| `pages/audios.php` | Remove public file-edit instructions |
| `pages/course.php` | Remove “بدون ثبت‌نام” claim on progress card |
| `data/site.php` | Align FAQ/why copy with auth gate; clean Instagram URL |

No large features added.

---

## 17. Recommended rebuild order (for later stages)

1. Freeze content source of truth (file seed → DB migration plan)  
2. Auth/admin hardening (explicit admin bootstrap)  
3. Real media pipeline (admin-only)  
4. Curriculum completion for thin courses / empty categories  
5. Message + user storage consolidation  
6. UI polish mobile admin tables  
7. Production InfinityFree checklist (SSL, HA_SITE_URL, DB comments, writable storage)

---

*End of Stage 1 audit. All claims above were derived from reading the repository files, not speculation.*
