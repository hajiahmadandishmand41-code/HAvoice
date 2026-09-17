<?php
/**
 * HAvoice — نکته‌های کوتاه و کاربردی، با فیلتر دسته و «نکته‌ی تصادفی».
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$allTips   = tips();
$categories = [];
foreach ($allTips as $tip) {
    $cat = (string) ($tip['category'] ?? 'عمومی');
    $categories[$cat] = ($categories[$cat] ?? 0) + 1;
}
ksort($categories, SORT_STRING | SORT_FLAG_CASE);

$cat = param('category');
$tipsToShow = $allTips;

if ($cat !== '' && isset($categories[$cat])) {
    $tipsToShow = array_values(array_filter($allTips, static function (array $t) use ($cat) {
        return (string) ($t['category'] ?? '') === $cat;
    }));
}
?>

<section class="section section--tight">
    <div class="container">

        <div class="card random-tip" data-random-tip>
            <div>
                <p class="eyebrow">نکته‌ی تصادفی</p>
                <p class="random-tip__text" data-random-text><?= e($allTips[0]['text'] ?? '') ?></p>
                <p class="random-tip__try" data-random-try>
                    <?php if (!empty($allTips[0]['try'])): ?>
                        <strong>همین حالا:</strong> <?= e($allTips[0]['try']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="btn-row btn-row--stack">
                <button class="btn btn--primary btn--sm" type="button" data-random-next>نکته‌ی دیگر</button>
                <button class="btn btn--ghost btn--sm" type="button" data-random-save>ذخیره در این مرورگر</button>
            </div>
        </div>

<?php if (count($categories) > 1): ?>
        <nav class="chip-row" aria-label="فیلتر موضوع نکته‌ها">
            <a class="chip<?= $cat === '' ? ' is-active' : '' ?>" href="<?= e(url('tips')) ?>">همه <span class="chip__n"><?= fa_num(count($allTips)) ?></span></a>
<?php foreach ($categories as $name => $count): ?>
            <a class="chip<?= $cat === $name ? ' is-active' : '' ?>" href="<?= e(url('tips', ['category' => $name])) ?>">
                <?= e($name) ?> <span class="chip__n"><?= fa_num($count) ?></span>
            </a>
<?php endforeach; ?>
        </nav>
<?php endif; ?>

        <?php foreach ($tipsToShow as $tip): $tslug = slugify((string)($tip['id'] ?? $tip['tip_key'] ?? '')); if($tslug!==''): ?>
            <div id="ha-tip-<?= e($tslug) ?>" style="margin-bottom:1rem"><?= ha_interaction_block('tip', $tslug) ?></div>
        <?php endif; endforeach; ?>
        <div class="grid grid--3">
<?php foreach ($tipsToShow as $tip): ?>
            <div class="reveal"><?= tip_card($tip) ?></div>
<?php endforeach; ?>
        </div>

        <div class="saved-tips" data-saved-tips hidden>
            <h2>نکته‌های ذخیره‌شده‌ی شما <span class="badge badge--soft" data-saved-count>۰</span></h2>
            <ul class="saved-tips__list" data-saved-list></ul>
            <p class="muted-sm">این فهرست فقط در همین مرورگر نگه داشته می‌شود.</p>
        </div>

        <p class="tips-footnote">
            <?= fa_num(count($allTips)) ?> نکته در این فهرست است و هر هفته اضافه می‌شود.
            پیشنهاد نکته‌ی خودتان را از <a href="<?= e(url('contact')) ?>">فرم تماس</a> بفرستید.
        </p>
    </div>
</section>

<?php
$tipsPayload = json_encode(array_map(static function (array $t) {
    return [
        'id'       => (string) ($t['id'] ?? ''),
        'text'     => (string) ($t['text'] ?? ''),
        'try'      => (string) ($t['try'] ?? ''),
        'category' => (string) ($t['category'] ?? ''),
    ];
}, $allTips), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<script id="ha-tips" type="application/json"><?= $tipsPayload ?></script>
