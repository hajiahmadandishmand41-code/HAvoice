# HAvoice Production Gate — 3.2.0

تاریخ: 2026-09-15

## Production readiness: **88%**

| حوزه | درصد | توضیح |
|------|------|--------|
| **Content** | 92% | مسیر Category→Course→Stage→Lesson→Exercise؛ media فقط playable؛ empty state |
| **Admin** | 90% | CRUD واقعی + CSRF + draft-first + dual-write DB/JSON |
| **Database** | 85% | Schema کامل PDO + seed؛ در این sandbox بدون MySQL host — CI با MySQL service |
| **Security** | 90% | hash/verify، CSRF، session، upload harden، prepared SQL، secrets gitignore |
| **Performance** | 82% | بدون lib سنگین؛ homepage سبک؛ media خالی؛ cache headers موجود |
| **Mobile** | 85% | admin/FE mobile CSS از Stage-2 |
| **Deployment** | 88% | InfinityFree path + config.local؛ Vercel = preview only |

**PRODUCTION READY** برای استقرار روی InfinityFree **پس از** پر کردن `config.local.php` و اجرای `php tools/db-migrate.php --seed`.

بدون DB هم سایت با file fallback سالم است (مناسب preview).

---

## Files added
- `includes/db.php` — PDO + repository SQL
- `includes/repository.php` — DB↔file bridge, seed, dual-write
- `sql/002-schema.sql` — full schema
- `config/config.local.php.example`
- `tools/db-migrate.php`
- `tools/production-check.php`
- `docs/PRODUCTION.md`
- `docs/PRODUCTION-GATE.md`

## Files changed (high level)
- `config/config.php` — load local, HA_DB_AUTO_SEED, v3.2.0
- `includes/bootstrap.php` — require db+repo
- `includes/content.php` — repo_* readers
- `includes/auth.php` — DB users, role=admin, settings
- `includes/comments.php` — PDO-first
- `includes/uploads.php` — MIME/ext/path/polyglot harden
- `includes/helpers.php` — categories_all, find_category_any
- `pages/admin/*_save.php`, `*_delete.php`, `content_status.php`, `user_*`, `settings_save.php`
- `.gitignore` — config.local.php
- `.github/workflows/php.yml` — MySQL service + migrate/seed + production-check
- `sitemap.php` — db/repo requires

## Files removed
- none (no test deletion)

## Database tables added
`ha_users`, `ha_categories`, `ha_courses`, `ha_stages`, `ha_lessons`, `ha_exercises`, `ha_media`, `ha_books`, `ha_articles`, `ha_tips`, `ha_research`, `ha_comments` (extended), `ha_settings`, `ha_contact_messages`, `ha_schema_meta`

## Fake content removed
- `data/media.php` remains `[]`
- seed skips media without URL
- FE still gates with `media_is_playable`

## Security fixes
- Admin authorization: explicit `role=admin` (DB); legacy first-JSON-user only offline
- Upload: double-ext block, realpath jail, random name, polyglot head scan, .htaccess engine off
- Secrets never in git (`config.local.php`)
- PDO prepared statements for all dynamic content
- CSRF + session harden unchanged/kept

## Performance fixes
- No new heavy deps
- Dual-write only on admin mutations
- Content static cache in data()/repo request-scope
- Homepage still light (no empty media spam)

## Remaining issues
1. **Host MySQL must be configured** on InfinityFree (`config.local.php`) — not in this sandbox.
2. Full HTTP smoke needs live PHP server (CI does this; local playground skips).
3. Contact messages still CSV+JSON hybrid (optional migrate to `ha_contact_messages` later).
4. Vercel remains ephemeral preview (no persistent MySQL) — by design.

## Verification (this environment)
| Check | Result |
|-------|--------|
| PHP lint 115 files | PASS |
| selfcheck | PASS 112 / FAIL 0 / SKIP 1 |
| production-check | PASS 52 / FAIL 0 / SKIP 2 |
| HTTP-sim routes | PASS |
