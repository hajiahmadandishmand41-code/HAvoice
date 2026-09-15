-- =====================================================================
--  HAvoice — جدولِ «نظرات عمومی» (MySQL 5.6+ / MariaDB — InfinityFree)
--
--  دو راه برای ساخت:
--   ۱) خودکار: همین CREATE TABLE در نخستین اتصالِ موفق از سمتِ
--      includes/comments.php اجرا می‌شود (روی InfinityFree بدونِ shell).
--   ۲) دستی: این فایل را در phpMyAdmin هاست درون‌ریزی کنید.
--
--  نکته‌ها:
--   • utf8mb4 برای پشتیبانیِ کاملِ فارسی/ایموجی.
--   • status: نظرات به‌صورتِ پیش‌فرض «pending»اند و فقط پس از تأییدِ
--     مدیر در پنل (admin/comments) عمومی می‌شوند. «hidden» یعنی مدیر
--     آن را از دیدِ عمومی پنهان کرده بدونِ این‌که حذف شود.
--   • ip فقط برای مدیریتِ سوءاستفاده نگه داشته می‌شود؛ در نمایشِ
--     عمومی هرگز چاپ نمی‌شود.
-- =====================================================================

CREATE TABLE IF NOT EXISTS ha_comments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(60) NOT NULL,
    email VARCHAR(190) NOT NULL DEFAULT '',
    body TEXT NOT NULL,
    status ENUM('pending','approved','hidden') NOT NULL DEFAULT 'pending',
    ip VARCHAR(45) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
