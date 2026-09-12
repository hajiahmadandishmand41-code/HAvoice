<?php
/**
 * HAvoice — پاورقی مشترک + اسکریپت‌های پایانی.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$site    = data('site');
?>
</main>

<footer class="site-footer">
    <div class="container site-footer__grid">
        <div class="site-footer__col site-footer__about">
            <a class="brand brand--footer" href="<?= e(url('home')) ?>">
                <span class="brand__text"><strong><?= e(HA_NAME) ?></strong></span>
            </a>
            <p><?= e($site['footer_about'] ?? '') ?></p>
            <ul class="social-list">
<?php foreach ((array) ($site['social'] ?? []) as $social): ?>
                <li>
                    <a href="<?= e($social['url']) ?>" rel="noopener noreferrer nofollow" target="_blank"><?= e($social['label']) ?></a>
                </li>
<?php endforeach; ?>
            </ul>
        </div>

        <nav class="site-footer__col" aria-label="دوره و تمرین">
            <h2><?= e($site['footer_col_learn'] ?? 'یادگیری') ?></h2>
            <ul>
                <li><a href="<?= e(url('course')) ?>">مسیر آموزشی</a></li>
                <li><a href="<?= e(url('exercises')) ?>">تمرین‌های عملی</a></li>
                <li><a href="<?= e(url('tips')) ?>">نکات کوتاه</a></li>
                <li><a href="<?= e(url('search')) ?>">جستجوی مقالات</a></li>
            </ul>
        </nav>

        <nav class="site-footer__col" aria-label="مقالات بر اساس دسته">
            <h2><?= e($site['footer_col_topics'] ?? 'موضوعات') ?></h2>
            <ul>
<?php foreach (array_keys(array_slice(article_categories(), 0, 5, true)) as $category): ?>
                <li>
                    <a href="<?= e(url('articles', ['category' => $category])) ?>"><?= e($category) ?></a>
                </li>
<?php endforeach; ?>
            </ul>
        </nav>

        <div class="site-footer__col">
            <h2><?= e($site['footer_col_contact'] ?? 'پشتیبانی') ?></h2>
            <ul class="contact-list">
                <li><span>ایمیل:</span> <a href="mailto:<?= e(HA_EMAIL) ?>"><?= e(HA_EMAIL) ?></a></li>
                <li><span>تلفن:</span> <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', HA_HOTLINE)) ?>"><?= e(HA_PHONE) ?></a></li>
                <li><span>ساعت پاسخ‌گویی:</span> <?= e($site['work_hours'] ?? 'شنبه تا چهارشنبه، ۹ تا ۱۷') ?></li>
            </ul>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('contact')) ?>"><?= e($site['footer_cta'] ?? 'ارسال پیام') ?></a>
        </div>
    </div>

    <div class="container site-footer__bottom">
        <p>© <?= fa_num(date('Y')) ?> <?= e(HA_NAME) ?>. <?= e($site['footer_rights'] ?? 'همه‌ی حقوق محفوظ است.') ?></p>
        <p class="site-footer__note"><?= e($site['footer_disclaimer'] ?? 'محتوای های‌ویس جایگزین درمان تخصصی اضطراب نیست؛ فقط برای آموزش و تمرین است.') ?></p>
    </div>
</footer>

<button class="to-top" type="button" data-to-top aria-label="بازگشت به بالای صفحه" hidden>
    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <path d="M12 19V5M5 12l7-7 7 7"/>
    </svg>
</button>

<div class="toast" data-toast role="status" aria-live="polite" hidden></div>

<?php
/* تنظیمات سمت کلاینت — فقط داده‌های غیرحساس. */
$js_config = [
    'route'      => $GLOBALS['HA_ROUTE'],
    // نامک درس‌ها تا پیشرفت فقط برای درس‌های واقعی شمرده شود
    'lessonKeys' => array_keys(course_lesson_index()),
];
?>
<script id="ha-config" type="application/json"><?= json_encode($js_config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
</body>
</html>
