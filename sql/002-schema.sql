-- =====================================================================
--  HAvoice — schema v5 (MySQL 5.6+ / MariaDB — InfinityFree)
--
--  نصب:
--   1) phpMyAdmin → Import فایل database_import.sql (ریشه‌ی پروژه)
--   2) خودکار: includes/db.php در نخستین اتصال موفق (CREATE IF NOT EXISTS)
--
--  اصول: InnoDB, utf8mb4, PK, FK, index, status, created_at, updated_at
--  رمز عبور هرگز plaintext نیست (فقط pass_hash).
--  هیچ کاربرِ پیش‌فرضی اینجا ساخته نمی‌شود — اولین ثبت‌نام مدیر می‌شود.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS ha_schema_meta (
    meta_key   VARCHAR(40) NOT NULL,
    meta_value VARCHAR(120) NOT NULL,
    PRIMARY KEY (meta_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_roles (
    role_key   VARCHAR(20)  NOT NULL,
    label      VARCHAR(80)  NOT NULL,
    created_at DATETIME     NOT NULL,
    PRIMARY KEY (role_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_users (
    id            VARCHAR(32)  NOT NULL,
    name          VARCHAR(60)  NOT NULL,
    email         VARCHAR(190) NOT NULL,
    pass_hash     VARCHAR(255) NOT NULL,
    role          VARCHAR(20)  NOT NULL DEFAULT 'user',
    last_login    DATETIME NULL,
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role),
    CONSTRAINT fk_users_role FOREIGN KEY (role) REFERENCES ha_roles(role_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_categories (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug          VARCHAR(80)  NOT NULL,
    title         VARCHAR(160) NOT NULL,
    short_title   VARCHAR(80)  NOT NULL DEFAULT '',
    description   TEXT NULL,
    icon          VARCHAR(40)  NOT NULL DEFAULT '',
    color         VARCHAR(20)  NOT NULL DEFAULT '',
    accent        VARCHAR(20)  NOT NULL DEFAULT '',
    status        ENUM('published','draft') NOT NULL DEFAULT 'published',
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    KEY idx_categories_status_order (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_courses (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug          VARCHAR(120) NOT NULL,
    title         VARCHAR(200) NOT NULL,
    category_slug VARCHAR(80)  NULL DEFAULT NULL,
    level         VARCHAR(80)  NOT NULL DEFAULT '',
    excerpt       TEXT NULL,
    intro         TEXT NULL,
    how_to_json   MEDIUMTEXT NULL,
    project_json  MEDIUMTEXT NULL,
    prereq        VARCHAR(200) NOT NULL DEFAULT '',
    featured      TINYINT(1) NOT NULL DEFAULT 0,
    status        ENUM('published','draft') NOT NULL DEFAULT 'draft',
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_courses_slug (slug),
    KEY idx_courses_cat_status (category_slug, status),
    KEY idx_courses_featured (featured, status),
    KEY idx_courses_order (sort_order),
    CONSTRAINT fk_courses_category FOREIGN KEY (category_slug) REFERENCES ha_categories(slug) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_stages (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    course_id     INT UNSIGNED NOT NULL,
    stage_key     VARCHAR(80)  NOT NULL DEFAULT '',
    label         VARCHAR(120) NOT NULL DEFAULT '',
    title         VARCHAR(200) NOT NULL,
    summary       TEXT NULL,
    outcome       TEXT NULL,
    duration      VARCHAR(80)  NOT NULL DEFAULT '',
    assessment_json MEDIUMTEXT NULL,
    sort_order    INT NOT NULL DEFAULT 0,
    status        ENUM('published','draft') NOT NULL DEFAULT 'published',
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_stages_course (course_id, sort_order),
    CONSTRAINT fk_stages_course FOREIGN KEY (course_id) REFERENCES ha_courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_lessons (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    course_id     INT UNSIGNED NOT NULL,
    stage_id      INT UNSIGNED NOT NULL,
    slug          VARCHAR(120) NOT NULL,
    title         VARCHAR(200) NOT NULL,
    minutes       INT UNSIGNED NOT NULL DEFAULT 0,
    goal          TEXT NULL,
    blocks_json   MEDIUMTEXT NULL,
    drill_json    MEDIUMTEXT NULL,
    prerequisite  VARCHAR(120) NOT NULL DEFAULT '',
    refs_json     MEDIUMTEXT NULL,
    sort_order    INT NOT NULL DEFAULT 0,
    status        ENUM('published','draft') NOT NULL DEFAULT 'published',
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_lessons_slug (slug),
    KEY idx_lessons_course (course_id, sort_order),
    KEY idx_lessons_stage (stage_id, sort_order),
    KEY idx_lessons_status (status),
    CONSTRAINT fk_lessons_course FOREIGN KEY (course_id) REFERENCES ha_courses(id) ON DELETE CASCADE,
    CONSTRAINT fk_lessons_stage  FOREIGN KEY (stage_id)  REFERENCES ha_stages(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_exercises (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ex_key        VARCHAR(80)  NOT NULL,
    title         VARCHAR(200) NOT NULL,
    level         VARCHAR(80)  NOT NULL DEFAULT '',
    focus         VARCHAR(120) NOT NULL DEFAULT '',
    goal          TEXT NULL,
    tool          VARCHAR(40)  NOT NULL DEFAULT 'timer',
    seconds       INT UNSIGNED NOT NULL DEFAULT 0,
    steps_json    MEDIUMTEXT NULL,
    topics_json   MEDIUMTEXT NULL,
    success_text  TEXT NULL,
    note_text     TEXT NULL,
    lesson_slug   VARCHAR(120) NOT NULL DEFAULT '',
    course_slug   VARCHAR(120) NOT NULL DEFAULT '',
    course_id     INT UNSIGNED NULL DEFAULT NULL,
    lesson_id     INT UNSIGNED NULL DEFAULT NULL,
    field_slug    VARCHAR(80)  NOT NULL DEFAULT '',
    featured      TINYINT(1) NOT NULL DEFAULT 0,
    status        ENUM('published','draft') NOT NULL DEFAULT 'published',
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_exercises_key (ex_key),
    KEY idx_exercises_lesson (lesson_slug),
    KEY idx_exercises_course (course_slug),
    KEY idx_exercises_status (status, sort_order),
    KEY idx_exercises_course_id (course_id),
    KEY idx_exercises_lesson_id (lesson_id),
    CONSTRAINT fk_exercises_course FOREIGN KEY (course_id) REFERENCES ha_courses(id) ON DELETE SET NULL,
    CONSTRAINT fk_exercises_lesson FOREIGN KEY (lesson_id) REFERENCES ha_lessons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_media (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    type          ENUM('video','audio') NOT NULL,
    slug          VARCHAR(120) NOT NULL,
    title         VARCHAR(200) NOT NULL,
    excerpt       TEXT NULL,
    url           VARCHAR(500) NOT NULL DEFAULT '',
    thumbnail     VARCHAR(500) NOT NULL DEFAULT '',
    category      VARCHAR(120) NOT NULL DEFAULT '',
    field_slug    VARCHAR(80)  NOT NULL DEFAULT '',
    course_slug   VARCHAR(120) NOT NULL DEFAULT '',
    lesson_slug   VARCHAR(120) NOT NULL DEFAULT '',
    course_id     INT UNSIGNED NULL DEFAULT NULL,
    lesson_id     INT UNSIGNED NULL DEFAULT NULL,
    seconds       INT UNSIGNED NOT NULL DEFAULT 0,
    date_fa       VARCHAR(40)  NOT NULL DEFAULT '',
    featured      TINYINT(1) NOT NULL DEFAULT 0,
    status        ENUM('published','draft') NOT NULL DEFAULT 'draft',
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_media_type_slug (type, slug),
    KEY idx_media_status_type (status, type),
    KEY idx_media_course (course_slug),
    KEY idx_media_featured (featured, status),
    KEY idx_media_course_id (course_id),
    KEY idx_media_lesson_id (lesson_id),
    CONSTRAINT fk_media_course FOREIGN KEY (course_id) REFERENCES ha_courses(id) ON DELETE SET NULL,
    CONSTRAINT fk_media_lesson FOREIGN KEY (lesson_id) REFERENCES ha_lessons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_books (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug          VARCHAR(120) NOT NULL,
    title         VARCHAR(200) NOT NULL,
    author        VARCHAR(160) NOT NULL DEFAULT '',
    category      VARCHAR(80)  NOT NULL DEFAULT '',
    field_slug    VARCHAR(80)  NOT NULL DEFAULT '',
    excerpt       TEXT NULL,
    summary       TEXT NULL,
    minutes       INT UNSIGNED NOT NULL DEFAULT 0,
    date_fa       VARCHAR(40)  NOT NULL DEFAULT '',
    tags_json     TEXT NULL,
    lessons_json  TEXT NULL,
    cover         VARCHAR(500) NOT NULL DEFAULT '',
    file_url      VARCHAR(500) NOT NULL DEFAULT '',
    featured      TINYINT(1) NOT NULL DEFAULT 0,
    status        ENUM('published','draft') NOT NULL DEFAULT 'published',
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_books_slug (slug),
    KEY idx_books_status (status),
    KEY idx_books_category (category),
    KEY idx_books_field (field_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_articles (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug          VARCHAR(120) NOT NULL,
    title         VARCHAR(200) NOT NULL,
    excerpt       TEXT NULL,
    category      VARCHAR(120) NOT NULL DEFAULT '',
    field_slug    VARCHAR(80)  NOT NULL DEFAULT '',
    tags_json     TEXT NULL,
    date_iso      VARCHAR(20)  NOT NULL DEFAULT '',
    date_fa       VARCHAR(40)  NOT NULL DEFAULT '',
    minutes       INT UNSIGNED NOT NULL DEFAULT 0,
    blocks_json   MEDIUMTEXT NULL,
    featured      TINYINT(1) NOT NULL DEFAULT 0,
    status        ENUM('published','draft') NOT NULL DEFAULT 'published',
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_articles_slug (slug),
    KEY idx_articles_status_date (status, date_iso),
    KEY idx_articles_field (field_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_tips (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tip_key       VARCHAR(80)  NOT NULL,
    category      VARCHAR(80)  NOT NULL DEFAULT '',
    body_text     TEXT NOT NULL,
    try_text      TEXT NULL,
    field_slug    VARCHAR(80)  NOT NULL DEFAULT '',
    status        ENUM('published','draft') NOT NULL DEFAULT 'published',
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tips_key (tip_key),
    KEY idx_tips_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_research (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug          VARCHAR(120) NOT NULL,
    title         VARCHAR(200) NOT NULL,
    summary       TEXT NULL,
    category      VARCHAR(80)  NOT NULL DEFAULT '',
    field_slug    VARCHAR(80)  NOT NULL DEFAULT '',
    date_fa       VARCHAR(40)  NOT NULL DEFAULT '',
    blocks_json   MEDIUMTEXT NULL,
    featured      TINYINT(1) NOT NULL DEFAULT 0,
    status        ENUM('published','draft') NOT NULL DEFAULT 'published',
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_research_slug (slug),
    KEY idx_research_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_comments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id VARCHAR(32) NULL DEFAULT NULL,
    name VARCHAR(60) NOT NULL,
    email VARCHAR(190) NOT NULL DEFAULT '',
    body TEXT NOT NULL,
    kind ENUM('comment','experience') NOT NULL DEFAULT 'comment',
    status ENUM('pending','approved') NOT NULL DEFAULT 'pending',
    ip VARCHAR(45) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_status_created (status, created_at),
    KEY idx_comments_user (user_id),
    KEY idx_comments_kind (kind, status),
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_settings (
    setting_key   VARCHAR(80) NOT NULL,
    setting_value MEDIUMTEXT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_contact_messages (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(120) NOT NULL DEFAULT '',
    email         VARCHAR(190) NOT NULL DEFAULT '',
    subject       VARCHAR(200) NOT NULL DEFAULT '',
    message       TEXT NOT NULL,
    status        ENUM('unread','read') NOT NULL DEFAULT 'unread',
    read_at       DATETIME NULL DEFAULT NULL,
    ip            VARCHAR(45)  NOT NULL DEFAULT '',
    created_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_messages_created (created_at),
    KEY idx_messages_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_progress (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       VARCHAR(32)  NOT NULL,
    item_type     ENUM('lesson','exercise') NOT NULL DEFAULT 'lesson',
    item_key      VARCHAR(120) NOT NULL,
    state         ENUM('started','done') NOT NULL DEFAULT 'started',
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_progress_item (user_id, item_type, item_key),
    KEY idx_progress_user (user_id),
    CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== NEW: Interactions v5 ==========

CREATE TABLE IF NOT EXISTS ha_content_reactions (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       VARCHAR(32) NOT NULL,
    content_type  ENUM('article','video','audio','book','research','course','lesson','exercise','tip','category') NOT NULL,
    content_slug  VARCHAR(120) NOT NULL,
    reaction_type ENUM('like','love','laugh','wow','sad') NOT NULL DEFAULT 'like',
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reaction_user_content (user_id, content_type, content_slug),
    KEY idx_reaction_content (content_type, content_slug),
    KEY idx_reaction_type (reaction_type),
    CONSTRAINT fk_reaction_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_content_comments (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       VARCHAR(32) NOT NULL,
    content_type  ENUM('article','video','audio','book','research','course','lesson','exercise','tip','category') NOT NULL,
    content_slug  VARCHAR(120) NOT NULL,
    parent_id     INT UNSIGNED NULL DEFAULT NULL,
    body          TEXT NOT NULL,
    status        ENUM('pending','approved') NOT NULL DEFAULT 'approved',
    likes_count   INT UNSIGNED NOT NULL DEFAULT 0,
    ip            VARCHAR(45) NOT NULL DEFAULT '',
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_cc_content (content_type, content_slug, status, created_at),
    KEY idx_cc_parent (parent_id),
    KEY idx_cc_user (user_id),
    CONSTRAINT fk_cc_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cc_parent FOREIGN KEY (parent_id) REFERENCES ha_content_comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ha_comment_likes (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       VARCHAR(32) NOT NULL,
    comment_id    INT UNSIGNED NOT NULL,
    created_at    DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_comment_like (user_id, comment_id),
    KEY idx_cl_comment (comment_id),
    CONSTRAINT fk_cl_user FOREIGN KEY (user_id) REFERENCES ha_users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cl_comment FOREIGN KEY (comment_id) REFERENCES ha_content_comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

INSERT IGNORE INTO ha_schema_meta (meta_key, meta_value) VALUES ('version', '5');

INSERT IGNORE INTO ha_roles (role_key, label, created_at) VALUES
    ('user',  'کاربر', NOW()),
    ('admin', 'مدیر',  NOW());

INSERT IGNORE INTO ha_categories
    (slug, title, short_title, description, icon, color, accent, status, sort_order, created_at, updated_at)
VALUES
('public-speaking','فن بیان و سخنوری','فن بیان','تنفس، صدا، زبان بدن، ساختار کلام و اجرای زنده؛ از پایه تا بداهه‌گویی.','mic','#1A3A7C','#5B87E8','published',0,NOW(),NOW()),
('communication','ارتباط مؤثر','ارتباط','گوش دادن فعال، همدلی، بازخورد سازنده و ارتباطات میان‌فردی و سازمانی.','chat','#1B5488','#4AA6E0','published',1,NOW(),NOW()),
('psychology','روانشناسی و خودشناسی','روانشناسی','هیجان، اضطراب، خودآگاهی، عزت‌نفس و الگوهای ذهنی با رویکردی کاربردی.','brain','#5B3AA8','#9E7FF2','published',2,NOW(),NOW()),
('success','موفقیت و رشد فردی','رشد فردی','عادت‌ها، انضباط، تاب‌آوری و نقشه‌ی رشد شخصی بدون شعارهای انگیزشی توخالی.','growth','#3730A3','#7173F2','published',3,NOW(),NOW()),
('life-skills','مهارت‌های زندگی','زندگی','حل مسئله، تصمیم‌گیری، مدیریت هیجان و مهارت‌های روزمره‌ی زندگی.','life','#14625C','#33C2AC','published',4,NOW(),NOW()),
('goals-time','هدف‌گذاری و مدیریت زمان','هدف و زمان','اهداف روشن، برنامه‌ریزی واقع‌گرا و مدیریت زمانِ مبتنی بر انرژی.','target','#8F5410','#E8A63F','published',5,NOW(),NOW()),
('career','مهارت‌های کاری و حرفه‌ای','مهارت کاری','ارائه، گزارش‌نویسی، کار تیمی، رهبری و مهارت‌های محیط کار.','briefcase','#26456E','#6F92CC','published',6,NOW(),NOW()),
('negotiation','مذاکره و متقاعدسازی','مذاکره','اصول مذاکره، متقاعدسازی اخلاقی، مدیریت تعارض و پاسخ به سؤال دشوار.','handshake','#96324E','#E67393','published',7,NOW(),NOW()),
('books','کتاب و خلاصه کتاب','کتاب‌ها','معرفی، خلاصه و برداشت‌های کاربردی از کتاب‌های شاخص هر حوزه.','book','#7A4A22','#D49C5D','published',8,NOW(),NOW()),
('research','تحقیقات و مقالات','پژوهش','یادداشت‌های پژوهشی، مرور منابع و مقالات تحلیلی با ذکر منبع.','research','#2E3A63','#7F8DC7','published',9,NOW(),NOW()),
('podcast','پادکست و آموزش صوتی','پادکست','فایل‌های صوتی کوتاه و متمرکز برای یادگیری در مسیر و مرور روزانه.','podcast','#6D2E9B','#BA7EE2','published',10,NOW(),NOW()),
('video','ویدیوهای آموزشی','ویدیو','ویدیوهای کوتاه، تمرین‌محور و قابل اجرا؛ هر ویدیو یک مهارت.','video','#175470','#43B9DB','published',11,NOW(),NOW());
