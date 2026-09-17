<?php
/**
 * HAvoice — پادکست و صوت (فقط فایل قابل پخش)
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$all = audios();
$cats = categories();
$filter = param('category');
if ($filter !== '' && find_category($filter) === null) {
    $filter = '';
}
$list = $filter
    ? array_values(array_filter($all, static fn($a) => ha_item_field_slug($a) === $filter || ($a['category'] ?? '') === $filter))
    : $all;
?>
<section class="section section--tight">
    <div class="container">
        <?php if ($all === []): ?>
            <?= empty_state(
                'محتوای این بخش هنوز منتشر نشده است.',
                'فایل صوتی یا پادکست قابل پخش هنوز اضافه نشده است.',
                url('courses'),
                'مشاهده‌ی دوره‌ها',
                'headphones'
            ) ?>
        <?php else: ?>
            <div class="filter-tabs" aria-label="فیلتر حوزه">
                <a class="chip<?= $filter === '' ? ' is-active' : '' ?>" href="<?= e(url('audios')) ?>">همه</a>
                <?php foreach ($cats as $cat):
                    $count = count(array_filter($all, static fn($a) => ha_item_field_slug($a) === ($cat['slug'] ?? '') || ($a['category'] ?? '') === ($cat['slug'] ?? '')));
                    if ($count === 0) {
                        continue;
                    }
                ?>
                    <a class="chip<?= $filter === ($cat['slug'] ?? '') ? ' is-active' : '' ?>" href="<?= e(url('audios', ['category' => $cat['slug']])) ?>"><?= e($cat['title']) ?> <span class="chip__n"><?= fa_num($count) ?></span></a>
                <?php endforeach; ?>
            </div>

            <?php if ($list === []): ?>
                <?= empty_state('فایل صوتی در این حوزه نیست', 'حوزه‌ی دیگری را ببینید.', url('audios'), 'همه‌ی صوت‌ها', 'headphones') ?>
            <?php else: ?>
                <div class="grid grid--2">
                    <?php foreach ($list as $item): ?><div class="reveal"><?= audio_card($item) ?></div><?php endforeach; ?>
                </div>
                <div class="ha-interactions-list" style="margin-top:2rem">
                <?php foreach ($list as $item): 
                    $s = slugify((string)($item['slug'] ?? ''));
                    if ($s === '') continue;
                ?>
                    <div id="ha-audio-<?= e($s) ?>" style="margin-top:1.5rem"><?= ha_interaction_block('audio', $s) ?></div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
