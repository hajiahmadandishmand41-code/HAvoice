<?php
/**
 * HAvoice — ویدیوهای آموزشی (فقط محتوای واقعی و قابل پخش)
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$all = videos();
$cats = categories();
$filter = param('category');
if ($filter !== '' && find_category($filter) === null) {
    $filter = '';
}
$list = $filter
    ? array_values(array_filter($all, static fn($v) => ha_item_field_slug($v) === $filter || ($v['category'] ?? '') === $filter))
    : $all;
?>
<section class="section section--tight">
    <div class="container">
        <?php if ($all === []): ?>
            <?= empty_state(
                'محتوای این بخش هنوز منتشر نشده است.',
                'ویدیوی آموزشی قابل پخش هنوز اضافه نشده. به‌زودی از همین‌جا در دسترس خواهد بود.',
                url('courses'),
                'مشاهده‌ی دوره‌ها',
                'play'
            ) ?>
        <?php else: ?>
            <div class="filter-tabs" aria-label="فیلتر حوزه">
                <a class="chip<?= $filter === '' ? ' is-active' : '' ?>" href="<?= e(url('videos')) ?>">همه</a>
                <?php foreach ($cats as $cat):
                    $count = count(array_filter($all, static fn($v) => ha_item_field_slug($v) === ($cat['slug'] ?? '') || ($v['category'] ?? '') === ($cat['slug'] ?? '')));
                    if ($count === 0) {
                        continue;
                    }
                ?>
                    <a class="chip<?= $filter === ($cat['slug'] ?? '') ? ' is-active' : '' ?>" href="<?= e(url('videos', ['category' => $cat['slug']])) ?>"><?= e($cat['title']) ?> <span class="chip__n"><?= fa_num($count) ?></span></a>
                <?php endforeach; ?>
            </div>

            <?php if ($list === []): ?>
                <?= empty_state('ویدیویی در این حوزه نیست', 'حوزه‌ی دیگری را ببینید یا همه‌ی ویدیوها را باز کنید.', url('videos'), 'همه‌ی ویدیوها', 'play') ?>
            <?php else: ?>
                <div class="grid grid--3">
                    <?php foreach ($list as $item): ?><div class="reveal"><?= video_card($item) ?></div><?php endforeach; ?>
                </div>
                <div class="ha-interactions-list" style="margin-top:2rem">
                <?php foreach ($list as $item): 
                    $s = slugify((string)($item['slug'] ?? ''));
                    if ($s === '') continue;
                ?>
                    <div id="ha-video-<?= e($s) ?>" style="margin-top:1.5rem"><?= ha_interaction_block('video', $s) ?></div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
