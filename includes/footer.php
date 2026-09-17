<?php
/**
 * HAvoice 2.0 — پاورقی
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
$site = ha_site();
$adminCfg = admin_settings();
if (!empty($adminCfg['footer_about'])) $site['footer_about'] = $adminCfg['footer_about'];
if (!empty($adminCfg['work_hours'])) $site['work_hours'] = $adminCfg['work_hours'];
if (!empty($adminCfg['cta_title'])) $site['cta_band']['title'] = $adminCfg['cta_title'];
if (!empty($adminCfg['cta_text'])) $site['cta_band']['text'] = $adminCfg['cta_text'];
$cats = categories();
$currentUser = auth_current_user();
?>
</main>

<?php if (($GLOBALS['HA_ROUTE'] ?? '') === 'home'):
    $homeBoardPosts = ha_board_posts_get(3,0,'approved','');
?>
<section class="section section--tight home-board-teaser" aria-label="تابلوی مجازی">
    <div class="container">
        <div class="home-board-teaser__head">
            <div>
                <p class="eyebrow">گفتگو و تجربه</p>
                <h2>از تابلوی مجازی چه خبر؟</h2>
            </div>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('board')) ?>">مشاهده تابلو</a>
        </div>
        <?php if ($homeBoardPosts !== []): ?>
            <div class="home-board-teaser__list">
                <?php foreach ($homeBoardPosts as $bp):
                    $bpId=(int)($bp['id']??0); $bpBody=trim((string)($bp['body']??''));
                ?>
                <a class="home-board-teaser__item" href="<?= e(url('board',['post'=>$bpId])) ?>">
                    <span class="ha-avatar"><?= e(auth_initial((string)($bp['name']??'کاربر'))) ?></span>
                    <span class="home-board-teaser__content">
                        <strong><?= e((string)($bp['name']??'کاربر')) ?></strong>
                        <span><?= e($bpBody !== '' ? function_exists('mb_substr') ? mb_substr($bpBody,0,110,'UTF-8') : substr($bpBody,0,110) : 'یک پست تصویری یا رسانه‌ای منتشر کرده است.') ?><?= $bpBody !== '' && (function_exists('mb_strlen') ? mb_strlen($bpBody,'UTF-8') : strlen($bpBody)) > 110 ? '…' : '' ?></span>
                    </span>
                    <span class="home-board-teaser__arrow">←</span>
                </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card home-board-teaser__empty"><span>هنوز پستی منتشر نشده است.</span><a href="<?= e(url('board')) ?>">اولین پست را منتشر کنید</a></div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<footer class="site-footer">
    <div class="container site-footer__grid">
        <div class="site-footer__col site-footer__about">
            <a class="brand brand--footer" href="<?= e(url('home')) ?>">
                <span class="brand__text"><strong><?= e(ha_site_name()) ?></strong><small><?= e(ha_site_tagline()) ?></small></span>
            </a>
            <p><?= e($site['footer_about'] ?? '') ?></p>
            <ul class="social-list">
<?php foreach (ha_social_links() as $s): ?>
                <li><a href="<?= e($s['url']) ?>" rel="noopener noreferrer nofollow" target="_blank"><?= ha_icon($s['icon'], 16) ?><span><?= e($s['label']) ?></span></a></li>
<?php endforeach; ?>
            </ul>
        </div>
        <nav class="site-footer__col" aria-label="یادگیری">
            <h2><?= e($site['footer_col_learn'] ?? 'یادگیری') ?></h2>
            <ul>
                <li><a href="<?= e(url('courses')) ?>">دوره‌ها</a></li><li><a href="<?= e(url('articles')) ?>">مقالات</a></li><li><a href="<?= e(url('videos')) ?>">ویدیوها</a></li><li><a href="<?= e(url('audios')) ?>">پادکست</a></li><li><a href="<?= e(url('books')) ?>">کتاب‌ها</a></li><li><a href="<?= e(url('research')) ?>">پژوهش</a></li><li><a href="<?= e(url('exercises')) ?>">تمرین‌ها</a></li><li><a href="<?= e(url('comments')) ?>">نظرات کاربران</a></li>
            </ul>
        </nav>
        <nav class="site-footer__col" aria-label="حوزه‌ها">
            <h2><?= e($site['footer_col_topics'] ?? 'حوزه‌ها') ?></h2>
            <ul><?php foreach (array_slice($cats,0,6) as $cat): ?><li><a href="<?= e(url('category',['slug'=>$cat['slug']])) ?>"><?= e($cat['title']) ?></a></li><?php endforeach; ?></ul>
        </nav>
        <div class="site-footer__col">
            <h2><?= e($site['footer_col_contact'] ?? 'پشتیبانی') ?></h2>
            <ul class="contact-list">
                <li><span>ایمیل:</span> <a href="mailto:<?= e(!empty($adminCfg['email']) ? $adminCfg['email'] : HA_EMAIL) ?>"><?= e(!empty($adminCfg['email']) ? $adminCfg['email'] : HA_EMAIL) ?></a></li>
                <li><span>تلفن:</span> <a href="tel:<?= e(preg_replace('/[^0-9+]/','',!empty($adminCfg['phone']) ? $adminCfg['phone'] : HA_HOTLINE)) ?>"><?= e(!empty($adminCfg['phone']) ? $adminCfg['phone'] : HA_PHONE) ?></a></li>
                <li><span>ساعت:</span> <?= e($site['work_hours'] ?? 'شنبه تا چهارشنبه، ۹ تا ۱۷') ?></li>
<?php if ($currentUser !== null): ?>
                <li><span>حساب:</span> <a href="<?= e(url('account')) ?>">حساب کاربری</a> · <a href="<?= e(url('logout')) ?>">خروج</a></li>
<?php else: ?>
                <li><span>حساب:</span> <a href="<?= e(url('login')) ?>">ورود</a> · <a href="<?= e(url('register')) ?>">ثبت‌نام</a></li>
<?php endif; ?>
            </ul>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('contact')) ?>"><?= e($site['footer_cta'] ?? 'ارسال پیام') ?></a>
        </div>
    </div>
    <div class="container site-footer__bottom">
        <p>© <?= fa_num(date('Y')) ?> <?= e(ha_site_name()) ?> · HAvoice. <?= e($site['footer_rights'] ?? 'همه‌ی حقوق محفوظ است.') ?></p>
        <p class="site-footer__note"><?= e($site['footer_disclaimer'] ?? '') ?></p>
    </div>
</footer>
<button class="to-top" type="button" data-to-top aria-label="بازگشت به بالای صفحه" hidden><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg></button>
<?= !empty($GLOBALS['HA_MEDIA_MODAL']) ? media_modal_shell() : '' ?>
<?= ha_icon_sprite() ?>
<div class="toast" data-toast role="status" aria-live="polite" hidden></div>
<?php $js_config=['route'=>$GLOBALS['HA_ROUTE'],'lessonKeys'=>array_keys(course_lesson_index())]; ?>
<script id="ha-config" type="application/json"><?= json_encode($js_config,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP) ?></script>
<script>
window.$ = window.$ || function(selector,root){return (root||document).querySelector(selector);};
window.$$ = window.$$ || function(selector,root){return Array.prototype.slice.call((root||document).querySelectorAll(selector));};
</script>
<script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/interactions.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/board.js')) ?>" defer></script>
</body>
</html>