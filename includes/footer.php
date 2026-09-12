<?php
/**
 * HAvoice 2.0 — پاورقی
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$site = data('site');
$cats = categories();
?>
</main>

<footer class="site-footer">
    <div class="container site-footer__grid">
        <div class="site-footer__col site-footer__about">
            <a class="brand brand--footer" href="<?= e(url('home')) ?>">
                <span class="brand__text"><strong><?= e(HA_NAME) ?></strong><small><?= e(HA_TAGLINE) ?></small></span>
            </a>
            <p><?= e($site['footer_about'] ?? '') ?></p>
            <ul class="social-list">
<?php foreach ((array)($site['social'] ?? []) as $social): ?>
                <li><a href="<?= e($social['url']) ?>" rel="noopener noreferrer nofollow" target="_blank"><?= e($social['label']) ?></a></li>
<?php endforeach; ?>
            </ul>
        </div>

        <nav class="site-footer__col" aria-label="یادگیری">
            <h2><?= e($site['footer_col_learn'] ?? 'یادگیری') ?></h2>
            <ul>
                <li><a href="<?= e(url('courses')) ?>">دوره‌ها</a></li>
                <li><a href="<?= e(url('articles')) ?>">مقالات</a></li>
                <li><a href="<?= e(url('videos')) ?>">ویدیوها</a></li>
                <li><a href="<?= e(url('audios')) ?>">پادکست</a></li>
                <li><a href="<?= e(url('books')) ?>">کتاب‌ها</a></li>
                <li><a href="<?= e(url('research')) ?>">پژوهش</a></li>
                <li><a href="<?= e(url('exercises')) ?>">تمرین‌ها</a></li>
            </ul>
        </nav>

        <nav class="site-footer__col" aria-label="حوزه‌ها">
            <h2><?= e($site['footer_col_topics'] ?? 'حوزه‌ها') ?></h2>
            <ul>
<?php foreach (array_slice($cats,0,6) as $cat): ?>
                <li><a href="<?= e(url('category',['slug'=>$cat['slug']])) ?>"><?= e($cat['title']) ?></a></li>
<?php endforeach; ?>
            </ul>
        </nav>

        <div class="site-footer__col">
            <h2><?= e($site['footer_col_contact'] ?? 'پشتیبانی') ?></h2>
            <ul class="contact-list">
                <li><span>ایمیل:</span> <a href="mailto:<?= e(HA_EMAIL) ?>"><?= e(HA_EMAIL) ?></a></li>
                <li><span>تلفن:</span> <a href="tel:<?= e(preg_replace('/[^0-9+]/','',HA_HOTLINE)) ?>"><?= e(HA_PHONE) ?></a></li>
                <li><span>ساعت:</span> <?= e($site['work_hours'] ?? 'شنبه تا چهارشنبه، ۹ تا ۱۷') ?></li>
            </ul>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('contact')) ?>"><?= e($site['footer_cta'] ?? 'ارسال پیام') ?></a>
        </div>
    </div>

    <div class="container site-footer__bottom">
        <p>© <?= fa_num(date('Y')) ?> <?= e(HA_NAME) ?> · HAvoice. <?= e($site['footer_rights'] ?? 'همه‌ی حقوق محفوظ است.') ?></p>
        <p class="site-footer__note"><?= e($site['footer_disclaimer'] ?? '') ?></p>
    </div>
</footer>

<button class="to-top" type="button" data-to-top aria-label="بازگشت به بالای صفحه" hidden>
    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
</button>

<div class="toast" data-toast role="status" aria-live="polite" hidden></div>

<?php
$js_config = [
    'route'      => $GLOBALS['HA_ROUTE'],
    'lessonKeys' => array_keys(course_lesson_index()),
];
?>
<script id="ha-config" type="application/json"><?= json_encode($js_config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
</body>
</html>
