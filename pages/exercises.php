<?php
/**
 * HAvoice — تمرین‌های عملی: کارت‌ها + اجراکننده‌ی تمرین (تایمر، موضوع تصادفی، شمارنده).
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$exercises = exercises();
$level     = param('level');
$lessonFlt = slugify(param('lesson'));
$courseFlt = slugify(param('course'));
$levels    = array_keys(exercises_by_level());
$filtered  = $exercises;

/* فیلترِ درس: وقتی از صفحه‌ی یک درس به اینجا می‌آییم، فقط تمرین‌های
   همان درس نشان داده می‌شوند (زنجیره‌ی درس → تمرین).
   فیلترِ دوره: از پنلِ «مسیرِ یادگیری» و صفحه‌ی دوره می‌آییم؛ ترتیب هم
   همان ترتیبِ درس‌هاست تا «تمرینِ بعدی» قابلِ پیش‌بینی بماند. */
$lessonInfo = $lessonFlt !== '' ? course_find_lesson($lessonFlt) : null;
$courseInfo = $courseFlt !== '' ? find_course($courseFlt) : null;
if ($lessonFlt !== '' && $lessonInfo !== null) {
    $filtered = exercises_for_lesson($lessonFlt);
} elseif ($courseFlt !== '' && $courseInfo !== null) {
    $filtered = exercises_for_course_ordered($courseFlt);
} elseif ($level !== '' && in_array($level, $levels, true)) {
    $filtered = array_values(array_filter($exercises, static function (array $ex) use ($level) {
        return (string) ($ex['level'] ?? '') === $level;
    }));
}

/* خلاصه‌ی وضعیتِ همین فهرست (انجام‌شده / کل) — سمتِ سرور، بدونِ JS */
$listDone = 0;
foreach ($filtered as $ex) {
    if (progress_exercise_state((string) ($ex['id'] ?? '')) === 'done') {
        $listDone++;
    }
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

<?php if ($lessonFlt !== '' && $lessonInfo !== null): ?>
        <div class="card side-card mb-md">
            <div class="side-card__top">
                <h2><?= ha_icon('steps', 15) ?> تمرین‌های درسِ «<?= e($lessonInfo['lesson']['title'] ?? '') ?>»</h2>
                <?= status_pill($listDone === count($filtered) && $listDone > 0 ? 'done' : ($listDone > 0 ? 'started' : 'todo'), fa_num($listDone) . ' از ' . fa_num(count($filtered)) . ' انجام‌شده') ?>
            </div>
            <p class="muted-sm">این تمرین‌ها مخصوص همین درس‌اند؛ برای دیدنِ همه، فیلتر را بردارید.</p>
            <div class="btn-row">
                <a class="btn btn--ghost btn--sm" href="<?= e(url('exercises')) ?>">همه‌ی تمرین‌ها</a>
                <?php if (!empty($lessonInfo['course']['slug'])): ?>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('course', ['slug' => (string) $lessonInfo['course']['slug']])) ?>"><?= ha_icon('arrow-right', 14) ?> بازگشت به دوره</a>
                <?php endif; ?>
            </div>
        </div>
<?php elseif ($courseFlt !== '' && $courseInfo !== null): ?>
        <div class="card side-card mb-md">
            <div class="side-card__top">
                <h2><?= ha_icon('timer', 15) ?> تمرین‌های دوره‌ی «<?= e($courseInfo['title'] ?? '') ?>»</h2>
                <?= status_pill($listDone === count($filtered) && $listDone > 0 ? 'done' : ($listDone > 0 ? 'started' : 'todo'), fa_num($listDone) . ' از ' . fa_num(count($filtered)) . ' انجام‌شده') ?>
            </div>
            <p class="muted-sm">ترتیبِ تمرین‌ها همان ترتیبِ درس‌هاست؛ هر تمرین را که کامل کردید، «انجام شد» را بزنید تا قدمِ بعدی روشن بماند.</p>
            <div class="btn-row">
                <a class="btn btn--primary btn--sm" href="<?= e(url('course', ['slug' => $courseFlt])) ?>"><?= ha_icon('arrow-right', 14) ?> بازگشت به دوره</a>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('exercises')) ?>">همه‌ی تمرین‌ها</a>
            </div>
        </div>
<?php elseif (count($levels) > 1): ?>
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
        <?php if ($courseFlt !== '' && $courseInfo !== null): ?>
            <?= empty_state('این دوره هنوز تمرینی ندارد', 'درس‌های دوره را دنبال کنید؛ به‌زودی تمرینِ هر درس همین‌جا اضافه می‌شود.', url('course', ['slug' => $courseFlt]), 'بازگشت به دوره', 'timer') ?>
        <?php else: ?>
            <?= empty_state('تمرینی در این سطح نیست', 'سطح دیگری را انتخاب کنید یا همه‌ی تمرین‌ها را ببینید.', url('exercises'), 'دیدن همه') ?>
        <?php endif; ?>
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
