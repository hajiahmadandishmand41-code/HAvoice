# HAvoice Stage 2 — Learning System Hardening

## Done

### Learning path
- Lesson page: goal, content, tips/mistakes extraction, drill + success criteria, linked exercises, prev/next **within course only**
- Last lesson shows **«پایان دوره»** CTA (course / exercises / other courses) — no jump to another course
- Progress bars scoped to current course (`data-course-lessons` + JS `scopeKeys`)

### Fake media removed
- `data/media.php` emptied (`return []`)
- Public `videos()` / `audios()` only return **playable** items (`url` non-empty + safe)
- Videos/Audios pages: professional empty state, no admin how-to text
- Homepage: no empty video/audio/research noise; stats use real counts

### Video admin (single path)
- Form: Title, Description, Thumbnail (URL/upload), URL, Course, Lesson, Status, Featured
- Advanced: seconds, date_fa, display category
- Save writes `storage/admin/media.json` with `created_at` / `updated_at`
- New items default to **draft** until admin publishes

### Cards / homepage
- Standard cards kept (course, lesson row, exercise, video, audio, book, article)
- Homepage: Featured courses + recent articles + playable media only + books + exercises

### Admin / defaults
- course/video/audio: `created_at`, `updated_at`, draft-first for new
- Mobile: admin tables stack; forms full-width; lesson pager / actions touch-friendly

## Tests (PHP 8.3 via wp-playground)
| Suite | Result |
|---|---|
| Syntax lint (111 PHP files) | PASS |
| `tools/selfcheck.php` | PASS 102 / FAIL 0 |
| Content + neighbour smoke | PASS |
| Auth/route HTTP-sim | PASS |
| CI workflow | unchanged (no tests removed) |

## Not in this stage (by design)
- No heavy libraries
- No artificial lesson inflation
- No full DB migration (still file + admin JSON + comments mysqli)
- Vercel remains preview-only
