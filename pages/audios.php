<?php
/**
 * HAvoice 2.0 — پادکست و صوت
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$all = audios();
$cats = categories();
$filter = param('category');
if($filter!=='' && find_category($filter)===null) $filter='';
$list = $filter ? array_values(array_filter($all, fn($a)=>($a['category']??'')=== $filter)) : $all;
?>
<section class="section section--tight">
    <div class="container">
        <div class="filter-tabs" aria-label="فیلتر حوزه">
            <a class="chip<?= $filter===''?' is-active':'' ?>" href="<?= e(url('audios')) ?>">همه</a>
            <?php foreach($cats as $cat):
                $count = count(array_filter($all, fn($a)=>($a['category']??'')=== $cat['slug']));
                if($count===0) continue;
            ?>
                <a class="chip<?= $filter=== $cat['slug']?' is-active':'' ?>" href="<?= e(url('audios',['category'=>$cat['slug']])) ?>"><?= e($cat['title']) ?> <span class="chip__n"><?= fa_num($count) ?></span></a>
            <?php endforeach; ?>
        </div>

        <?php if($list===[]): ?>
            <?= empty_state('فایلِ صوتی در این حوزه نیست','حوزه‌ی دیگری را ببینید.', url('audios'),'همه‌ی صوت‌ها') ?>
        <?php else: ?>
            <div class="grid grid--2">
                <?php foreach($list as $item): ?><div class="reveal"><?= audio_card($item) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h2>پخش‌کننده‌ی حرفه‌ای</h2>
            <p class="muted-sm">فایل‌های صوتی با تگِ <code>&lt;audio&gt;</code> پخش می‌شوند؛ نیازی به سرویسِ خارجی نیست. برای افزودنِ فایلِ واقعی، در <code>data/media.php</code> مسیرِ فایل (مثلاً <code>/uploads/audio/xxx.mp3</code>) را در <code>url</code> بگذارید.</p>
        </div>
    </div>
</section>
