<?php
/**
 * HAvoice 2.0 — ویدیوهای آموزشی
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$all = videos();
$cats = categories();
$filter = param('category');
if($filter!=='' && find_category($filter)===null) $filter='';
$list = $filter ? array_values(array_filter($all, fn($v)=>($v['category']??'')=== $filter)) : $all;
?>
<section class="section section--tight">
    <div class="container">
        <div class="filter-tabs" aria-label="فیلتر حوزه">
            <a class="chip<?= $filter===''?' is-active':'' ?>" href="<?= e(url('videos')) ?>">همه</a>
            <?php foreach($cats as $cat):
                $count = count(array_filter($all, fn($v)=>($v['category']??'')=== $cat['slug']));
                if($count===0) continue;
            ?>
                <a class="chip<?= $filter=== $cat['slug']?' is-active':'' ?>" href="<?= e(url('videos',['category'=>$cat['slug']])) ?>"><?= e($cat['title']) ?> <span class="chip__n"><?= fa_num($count) ?></span></a>
            <?php endforeach; ?>
        </div>

        <?php if($list===[]): ?>
            <?= empty_state('ویدیویی در این حوزه نیست','حوزه‌ی دیگری را ببینید.', url('videos'),'همه‌ی ویدیوها') ?>
        <?php else: ?>
            <div class="grid grid--3">
                <?php foreach($list as $item): ?><div class="reveal"><?= video_card($item) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h2>نحوه‌ی افزودنِ ویدیوی واقعی</h2>
            <p class="muted-sm">در <code>data/media.php</code> مقدارِ <code>url</code> را به لینکِ ویدیو (مثلاً آپارات/یوتیوب یا فایلِ mp4 روی هاست) تغییر دهید؛ کارت خودکار از حالتِ «به‌زودی» به حالتِ پخش تبدیل می‌شود — بدونِ تغییرِ قالب.</p>
            <ul class="rich-list"><li>فرمتِ پیشنهادی: mp4 با حجمِ بهینه (≤ 25MB برای ۵ دقیقه) و پوسترِ سبک.</li><li>برای ویدیوهای آپارات، <code>url</code> را با iframeِ امبد جایگزین کنید و در بلوکِ درس از نوعِ <code>video</code> استفاده کنید.</li></ul>
        </div>
    </div>
</section>
