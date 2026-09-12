<?php
/**
 * HAvoice — تمرین‌های عملی: کارت‌ها + اجراکننده‌ی تمرین (تایمر، موضوع تصادفی، شمارنده).
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$exercises = exercises();
$level     = param('level');
$levels    = array_keys(exercises_by_level());
$filtered  = $exercises;

if ($level !== '' && in_array($level, $levels, true)) {
    $filtered = array_values(array_filter($exercises, static function (array $ex) use ($level) {
        return (string) ($ex['level'] ?? '') === $level;
    }));
}

/* موضوع‌های تصادفی همه‌ی تمرین‌ها، برای تولیدگر عمومی بالای صفحه */
$allTopics = [];
foreach ($exercises as $ex) {
    foreach ((array) ($ex['topics'] ?? []) as $topic) {
        $allTopics[] = (string) $topic;
    }
}
$allTopics = array_values(array_unique($allTopics));
?>

<section class="section section--tight">
    <div class="container">

        <div class="card topic-machine" data-topic-machine>
            <div class="topic-machine__text">
                <p class="eyebrow">تولیدگر موضوع بداهه</p>
                <p class="topic-machine__topic" data-topic-text aria-live="polite">
                    دکمه را بزنید و یک موضوع تصادفی بگیرید؛ شصت ثانیه صحبت کنید.
                </p>
            </div>
            <div class="topic-machine__actions">
                <button class="btn btn--primary" type="button" data-topic-next>موضوع بعدی</button>
                <button class="btn btn--ghost" type="button" data-topic-copy>کپی موضوع</button>
            </div>
        </div>

<?php if (count($levels) > 1): ?>
        <nav class="chip-row" aria-label="فیلتر سطح">
            <a class="chip<?= $level === '' ? ' is-active' : '' ?>" href="<?= e(url('exercises')) ?>">همه سطوح</a>
<?php foreach ($levels as $name): ?>
            <a class="chip<?= $level === $name ? ' is-active' : '' ?>" href="<?= e(url('exercises', ['level' => $name])) ?>"><?= e($name) ?></a>
<?php endforeach; ?>
        </nav>
<?php endif; ?>

        <div class="grid grid--2">
<?php foreach ($filtered as $ex): ?>
            <div class="reveal"><?= exercise_card($ex) ?></div>
<?php endforeach; ?>
        </div>

<?php if ($filtered === []): ?>
        <?= empty_state('تمرینی در این سطح نیست', 'سطح دیگری را انتخاب کنید یا همه‌ی تمرین‌ها را ببینید.', url('exercises'), 'دیدن همه') ?>
<?php endif; ?>

        <div class="rules-card card">
            <h2>سه قانون تمرین</h2>
            <ol class="rich-list rich-list--num">
                <li><strong>کم اما هر روز.</strong> ده دقیقه‌ی هر روز، از نود دقیقه‌ی جمعه‌ها مؤثرتر است.</li>
                <li><strong>ضبط، بازخورد، تکرار.</strong> بدون ضبط، تمرین فقط حرف‌زدن است.</li>
                <li><strong>یک متغیر را عوض کنید.</strong> هر هفته فقط روی یک مهارت کار کنید؛ مغز با ده تغییر هم‌زمان نمی‌سازد.</li>
            </ol>
        </div>
    </div>
</section>

<?php
/* اجرای‌کننده‌ی تمرین (مودال) — یک نمونه‌ی مشترک که JS با داده‌ی هر کارت پرش می‌کند. */
$topicsJson = json_encode(['topics' => $allTopics], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<div class="modal" data-modal hidden>
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <header class="modal__head">
            <h2 id="modal-title" class="modal__title" data-modal-title>تمرین</h2>
            <button class="icon-btn" type="button" data-modal-close aria-label="بستن پنجره‌ی تمرین"><?= ha_icon('close', 18) ?></button>
        </header>

        <div class="modal__body">
            <p class="modal__goal" data-modal-goal></p>

            <div class="timer" data-timer hidden>
                <span class="timer__digits" data-timer-digits aria-live="polite">۰۰:۰۰</span>
                <span class="timer__phase" data-timer-phase>آماده</span>
                <div class="timer__bar"><span data-timer-bar></span></div>
                <div class="btn-row">
                    <button class="btn btn--primary btn--sm" type="button" data-timer-toggle>شروع</button>
                    <button class="btn btn--ghost btn--sm" type="button" data-timer-reset>از نو</button>
                </div>
            </div>

            <div class="modal__topic" data-modal-topic hidden>
                <p class="muted-sm">موضوع تمرین:</p>
                <p class="modal__topic-text" data-modal-topic-text></p>
                <button class="btn btn--ghost btn--sm" type="button" data-modal-topic-next>موضوع بعدی</button>
            </div>

            <ol class="modal__steps" data-modal-steps></ol>

            <p class="modal__success" data-modal-success hidden></p>
        </div>

        <footer class="modal__foot">
            <button class="btn btn--primary" type="button" data-modal-done>انجام شد <?= ha_icon('check', 16) ?></button>
        </footer>
    </div>
</div>

<script id="ha-topics" type="application/json"><?= $topicsJson /* json_encode با JSON_HEX_* — خروجی امن برای تگ script */ ?></script>
