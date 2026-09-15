-- =====================================================================
--  HAvoice — ارتقای schema از v2 به v3
--  امن برای اجرای تکراری (idempotent) — مناسب phpMyAdmin و db.php.
--
--  تغییرها:
--   1) ha_comments: افزودن وضعیت 'hidden' (پنهان‌سازی واقعیِ نظرها)
--   2) ha_contact_messages: ستون read_at (صندوق ورودیِ پیام‌ها)
--   3) ha_exercises: lesson_id + FK آبشاری به ha_lessons (جلوگیری از
--      orphan شدن تمرین — تمرین همیشه زیرمجموعه‌ی یک درس است)
--   4) ha_stages: UNIQUE (course_id, stage_key)
-- =====================================================================

SET NAMES utf8mb4;

-- 1) وضعیت 'hidden' برای نظرها (تکرار-پذیر)
ALTER TABLE ha_comments
    MODIFY status ENUM('pending','approved','hidden') NOT NULL DEFAULT 'pending';

-- 2) ستون read_at برای پیام‌ها — فقط اگر نباشد
SET @has_read_at := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ha_contact_messages' AND COLUMN_NAME = 'read_at'
);
SET @sql := IF(@has_read_at = 0,
    'ALTER TABLE ha_contact_messages ADD COLUMN read_at DATETIME NULL AFTER ip',
    'SELECT 1'
);
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- 3) ستون lesson_id برای تمرین‌ها — فقط اگر نباشد
SET @has_lesson_id := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ha_exercises' AND COLUMN_NAME = 'lesson_id'
);
SET @sql := IF(@has_lesson_id = 0,
    'ALTER TABLE ha_exercises ADD COLUMN lesson_id INT UNSIGNED NULL AFTER note_text',
    'SELECT 1'
);
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- ایندکس روی lesson_id (FK به آن نیاز دارد) — فقط اگر نباشد
SET @has_idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ha_exercises' AND INDEX_NAME = 'idx_exercises_lesson_id'
);
SET @sql := IF(@has_idx = 0,
    'ALTER TABLE ha_exercises ADD KEY idx_exercises_lesson_id (lesson_id)',
    'SELECT 1'
);
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- لینک‌کردن ردیف‌های slug-محور قدیمی به درس‌ها (قبل از افزودن FK)
UPDATE ha_exercises e
LEFT JOIN ha_lessons l ON l.slug = e.lesson_slug
SET e.lesson_id = l.id
WHERE e.lesson_id IS NULL;

-- FK آبشاری — فقط اگر نباشد
SET @has_fk := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ha_exercises'
      AND CONSTRAINT_NAME = 'fk_exercises_lesson'
);
SET @sql := IF(@has_fk = 0,
    'ALTER TABLE ha_exercises ADD CONSTRAINT fk_exercises_lesson FOREIGN KEY (lesson_id) REFERENCES ha_lessons(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- 4) UNIQUE برای stage_key در هر دوره — فقط اگر نباشد.
--    ابتدا مقادیر خالی را با کلیدِ مشخص پر می‌کنیم تا برخورد نخوریم.
UPDATE ha_stages SET stage_key = CONCAT('stage-', id) WHERE stage_key = '';

SET @has_uq := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ha_stages' AND INDEX_NAME = 'uq_stages_key'
);
SET @sql := IF(@has_uq = 0,
    'ALTER TABLE ha_stages ADD UNIQUE KEY uq_stages_key (course_id, stage_key)',
    'SELECT 1'
);
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- نسخه‌ی schema
INSERT INTO ha_schema_meta (meta_key, meta_value) VALUES ('version', '3')
ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value);
