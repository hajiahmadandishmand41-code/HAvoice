<?php
/**
 * HAvoice Admin — ویرایش/ساختِ دوره
 *
 * نسخه‌ی پیشین فقط یک redirect بود که می‌گفت «دوره‌ها را در فایل ویرایش
 * کنید»؛ یعنی ساختِ دوره از پنل ناممکن بود. اکنون فرمِ کامل است:
 * مشخصاتِ دوره به‌صورتِ فیلدِ معمولی، و ساختارِ تودرتویِ مراحل/درس‌ها
 * (stages → lessons) به‌صورتِ JSON — دقیقاً همان الگویی که
 * article_edit.php برای blocks استفاده می‌کند.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$slug   = param('slug');
$course = null;
$fromFile = false;

if ($slug !== '') {
    /* اول در داده‌ی پنل می‌گردیم (قابلِ ویرایش/حذف)، بعد در فایل. */
    foreach (admin_courses() as $c) {
        if (slugify((string) ($c['slug'] ?? '')) === slugify($slug)) { $course = $c; break; }
    }
    if ($course === null) {
        foreach (courses_all() as $c) {
            if (slugify((string) ($c['slug'] ?? '')) === slugify($slug)) { $course = $c; $fromFile = true; break; }
        }
    }
    if ($course === null) { flash('error', 'دوره پیدا نشد.'); redirect(url('admin_courses')); }
}

/* الگوی خالیِ stages برای دوره‌ی تازه — تا مدیر ساختارِ درست را ببیند. */
$stagesTemplate = [
    [
        'id'       => 'stage-1',
        'label'    => 'مرحله‌ی ۱',
        'title'    => 'عنوانِ مرحله',
        'summary'  => 'این مرحله چه چیزی می‌سازد؟',
        'outcome'  => 'خروجیِ قابلِ اندازه‌گیری',
        'duration' => '۳ روز',
        'lessons'  => [
            ['slug' => 'lesson-slug', 'title' => 'عنوانِ درس', 'minutes' => 10, 'goal' => 'هدفِ درس در یک جمله', 'blocks' => [['type' => 'p', 'text' => 'متنِ درس']]],
        ],
    ],
];

$stages = (array) ($course['stages'] ?? ($slug === '' ? $stagesTemplate : []));
$howTo  = (array) ($course['how_to'] ?? []);
$flash  = flash();
$cats   = categories();
$curCat = (string) ($course['category'] ?? '');
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type'] === 'success' ? 'success' : 'error') ?>" role="<?= $flash['type'] === 'success' ? 'status' : 'alert' ?>"><?= e($flash['message']) ?></div><?php endif; ?>

<?php if ($fromFile): ?>
<div class="alert alert--warning" role="status">
    <p>این دوره از فایلِ <code dir="ltr">data/course.php</code> می‌آید و در پنل ذخیره نشده است.
    اگر ذخیره کنید، یک نسخه‌ی پنلی با همین نامک ساخته می‌شود که از این پس <strong>جایگزینِ</strong> نسخه‌ی فایل
    می‌شود (فایلِ اصلی دست‌نخورده می‌ماند). برایِ حذفِ کامل باید فایل را ویرایش کنید.</p>
</div>
<?php endif; ?>

<form class="admin-form" method="post" action="<?= e(url('admin_course_save')) ?>">
<?= csrf_field() ?>
<input type="hidden" name="original_slug" value="<?= e($slug) ?>">

<div class="admin-card">
    <h2>مشخصاتِ دوره</h2>
    <div class="admin-form-grid">
        <div class="field"><label for="c-title">عنوان *</label>
            <input class="input" id="c-title" type="text" name="title" required maxlength="200"
                   value="<?= e($course['title'] ?? '') ?>"></div>
        <div class="field"><label for="c-slug">نامک (slug) *</label>
            <input class="input" id="c-slug" type="text" name="slug" required maxlength="100" dir="ltr"
                   value="<?= e($course['slug'] ?? '') ?>"
                   placeholder="public-speaking-fundamentals">
            <p class="field__help">حروفِ لاتین، عدد و خطِ تیره. در نشانیِ صفحه‌ی دوره استفاده می‌شود.</p></div>
        <div class="field"><label for="c-category">حوزه *</label>
            <select class="input" id="c-category" name="category" required>
                <option value="">— انتخاب کنید —</option>
                <?php foreach ($cats as $c): $cs = (string) ($c['slug'] ?? ''); ?>
                <option value="<?= e($cs) ?>"<?= $cs === $curCat ? ' selected' : '' ?>><?= e($c['title'] ?? $cs) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="field__help">برایِ رنگِ کارت و فیلترِ صفحه‌ی حوزه استفاده می‌شود.</p></div>
        <div class="field"><label for="c-level">سطح</label>
            <input class="input" id="c-level" type="text" name="level" maxlength="80"
                   value="<?= e($course['level'] ?? 'مقدماتی تا متوسط') ?>"></div>
        <div class="field"><label for="c-excerpt">توضیحِ کوتاه (کارت)</label>
            <textarea class="input" id="c-excerpt" name="excerpt" rows="2" maxlength="300"><?= e($course['excerpt'] ?? '') ?></textarea>
            <p class="field__help">روی کارتِ دوره نمایش داده می‌شود؛ کوتاه نگهش دارید. اگر خالی بماند، از رویِ «معرفیِ کامل» ساخته می‌شود.</p></div>
        <div class="field"><label for="c-intro">معرفیِ کامل</label>
            <textarea class="input" id="c-intro" name="intro" rows="4"><?= e($course['intro'] ?? '') ?></textarea></div>
        <div class="field"><label for="c-howto">«چطور پیش برویم؟» — هر خط یک مورد</label>
            <textarea class="input" id="c-howto" name="how_to_text" rows="4"><?= e(implode("\n", array_map('strval', $howTo))) ?></textarea></div>
        <div class="field"><label for="c-prereq">پیش‌نیازِ دوره (اختیاری)</label>
            <input class="input" id="c-prereq" type="text" name="prereq" maxlength="200"
                   value="<?= e($course['prereq'] ?? '') ?>">
            <p class="field__help">یادداشتِ کوتاهِ پیش‌نیاز؛ زیرِ عنوانِ دوره نمایش داده می‌شود.</p></div>
        <?= admin_status_field($course ?? [], $slug === '') ?>
        <div class="field">
            <label class="check"><input type="checkbox" name="featured" value="1"<?= !empty($course['featured']) ? ' checked' : '' ?>>
                <span>دوره‌ی ویژه (در صفحه‌ی اصلی نمایش داده شود)</span></label>
        </div>
    </div>
</div>

<div class="admin-card">
    <h2>مراحل و درس‌ها</h2>
    <p class="muted-sm">
        هر «مرحله» یک بخشِ دوره است و داخلِ هر مرحله، درس‌ها با فیلدِ <strong>ترتیب</strong> جابه‌جا می‌شوند
        (۱ = اولین درس). با دکمه‌های <?= ha_icon('arrow-up', 12) ?>/<?= ha_icon('chevron-down', 12) ?> هم می‌توانید
        بدونِ نوشتنِ عدد، جابه‌جا کنید. نامکِ هر درس باید در کلِ سایت یکتا باشد؛ اگر خالی بگذارید، از رویِ عنوان ساخته می‌شود.
    </p>

    <div class="builder" data-builder>
        <?php
        /* ---- آماده‌سازیِ داده‌ی سازنده ---- */
        $blankLesson = ['slug' => '', 'title' => '', 'minutes' => 10, 'goal' => '', 'text' => '', 'lossy' => false, 'blocks_json' => '[]', 'advanced' => false];
        $stageIndex = 0;
        foreach ($stages as $stage):
            if (!is_array($stage)) { continue; }
            $stageLessons = [];
            foreach ((array) ($stage['lessons'] ?? []) as $lesson) {
                if (!is_array($lesson)) { continue; }
                $blocks = is_array($lesson['blocks'] ?? null) ? $lesson['blocks'] : [];
                [$lessonText, $lessonLossy] = admin_lesson_blocks_to_text($blocks);
                $stageLessons[] = [
                    'slug'         => (string) ($lesson['slug'] ?? ''),
                    'title'        => (string) ($lesson['title'] ?? ''),
                    'minutes'      => (int) ($lesson['minutes'] ?? 10),
                    'goal'         => (string) ($lesson['goal'] ?? ''),
                    'text'         => $lessonLossy ? '' : $lessonText,
                    'lossy'        => $lessonLossy,
                    'blocks_json'  => (string) json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'advanced'     => $lessonLossy || !empty($lesson['drill']) || !empty($lesson['refs']) || (string) ($lesson['prerequisite'] ?? '') !== '',
                ];
            }
            $si = $stageIndex++;
        ?>
        <fieldset class="builder__stage" data-sort-item>
            <legend class="builder__legend">
                <span class="builder__n" aria-hidden="true"><?= fa_num($si + 1) ?></span>
                مرحله —
                <span class="builder__legend-title"><?= e((string) ($stage['title'] ?? '') !== '' ? (string) $stage['title'] : 'بدونِ عنوان') ?></span>
            </legend>

            <div class="builder__tools">
                <label class="builder__order">ترتیب
                    <input class="input input--num" type="number" min="1" max="99"
                           name="stages[<?= $si ?>][order]" value="<?= $si + 1 ?>" data-sort-input>
                </label>
                <button class="btn btn--ghost btn--xs" type="button" data-sort-up title="یک مرحله بالاتر" aria-label="انتقالِ این مرحله به بالا"><?= ha_icon('arrow-up', 13) ?></button>
                <button class="btn btn--ghost btn--xs" type="button" data-sort-down title="یک مرحله پایین‌تر" aria-label="انتقالِ این مرحله به پایین"><?= ha_icon('chevron-down', 13) ?></button>
                <label class="check builder__remove"><input type="checkbox" name="stages[<?= $si ?>][remove]" value="1">
                    <span>این مرحله حذف شود (با همه‌ی درس‌هایش)</span></label>
            </div>

            <div class="admin-form-grid">
                <div class="field"><label for="st-<?= $si ?>-title">عنوانِ مرحله *</label>
                    <input class="input" id="st-<?= $si ?>-title" type="text" name="stages[<?= $si ?>][title]" maxlength="160"
                           value="<?= e((string) ($stage['title'] ?? '')) ?>" placeholder="مثلاً: پایه‌های شنیدن"></div>
                <div class="field"><label for="st-<?= $si ?>-label">برچسبِ کوتاه</label>
                    <input class="input" id="st-<?= $si ?>-label" type="text" name="stages[<?= $si ?>][label]" maxlength="60"
                           value="<?= e((string) ($stage['label'] ?? ('مرحله‌ی ' . fa_num($si + 1)))) ?>"></div>
                <div class="field" style="grid-column:1/-1"><label for="st-<?= $si ?>-summary">خلاصه‌ی مرحله</label>
                    <textarea class="input" id="st-<?= $si ?>-summary" name="stages[<?= $si ?>][summary]" rows="2" maxlength="400"><?= e((string) ($stage['summary'] ?? '')) ?></textarea></div>
                <div class="field"><label for="st-<?= $si ?>-outcome">خروجیِ مرحله</label>
                    <input class="input" id="st-<?= $si ?>-outcome" type="text" name="stages[<?= $si ?>][outcome]" maxlength="200"
                           value="<?= e((string) ($stage['outcome'] ?? '')) ?>" placeholder="بعد از این مرحله چه کاری می‌توانید بکنید؟"></div>
                <div class="field"><label for="st-<?= $si ?>-duration">مدتِ پیشنهادی</label>
                    <input class="input" id="st-<?= $si ?>-duration" type="text" name="stages[<?= $si ?>][duration]" maxlength="40"
                           value="<?= e((string) ($stage['duration'] ?? '')) ?>" placeholder="۳ روز"></div>
            </div>

            <div class="builder__lessons" data-sortable>
                <?php
                $li = 0;
                $rows = array_merge($stageLessons, [$blankLesson, $blankLesson]);
                foreach ($rows as $lessonRow):
                    $isBlank = $lessonRow === $blankLesson;
                ?>
                <div class="builder__lesson<?= $isBlank ? ' builder__lesson--blank' : '' ?>" data-sort-item>
                    <div class="builder__lesson-head">
                        <span class="builder__n" aria-hidden="true"><?= fa_num($li + 1) ?></span>
                        <strong class="builder__lesson-title"><?= $isBlank ? 'درسِ جدید (اختیاری)' : e((string) ($lessonRow['title'] !== '' ? $lessonRow['title'] : 'بدونِ عنوان')) ?></strong>
                        <label class="builder__order">ترتیب
                            <input class="input input--num" type="number" min="1" max="99"
                                   name="stages[<?= $si ?>][lessons][<?= $li ?>][order]" value="<?= $li + 1 ?>" data-sort-input>
                        </label>
                        <button class="btn btn--ghost btn--xs" type="button" data-sort-up aria-label="انتقالِ این درس به بالا"><?= ha_icon('arrow-up', 13) ?></button>
                        <button class="btn btn--ghost btn--xs" type="button" data-sort-down aria-label="انتقالِ این درس به پایین"><?= ha_icon('chevron-down', 13) ?></button>
                        <?php if (!$isBlank): ?>
                        <label class="check builder__remove"><input type="checkbox" name="stages[<?= $si ?>][lessons][<?= $li ?>][remove]" value="1"><span>حذف</span></label>
                        <?php endif; ?>
                    </div>

                    <div class="admin-form-grid">
                        <div class="field"><label for="ls-<?= $si ?>-<?= $li ?>-title">عنوانِ درس<?= $isBlank ? '' : ' *' ?></label>
                            <input class="input" id="ls-<?= $si ?>-<?= $li ?>-title" type="text" maxlength="200"
                                   name="stages[<?= $si ?>][lessons][<?= $li ?>][title]" value="<?= e((string) $lessonRow['title']) ?>"
                                   placeholder="گوش دادنِ فعال؛ سه ابزار"></div>
                        <div class="field"><label for="ls-<?= $si ?>-<?= $li ?>-slug">نامکِ درس (خودکار اگر خالی)</label>
                            <input class="input" id="ls-<?= $si ?>-<?= $li ?>-slug" type="text" maxlength="100" dir="ltr"
                                   name="stages[<?= $si ?>][lessons][<?= $li ?>][slug]" value="<?= e((string) $lessonRow['slug']) ?>"
                                   placeholder="active-listening"></div>
                        <div class="field"><label for="ls-<?= $si ?>-<?= $li ?>-minutes">مدتِ مطالعه (دقیقه)</label>
                            <input class="input input--num" id="ls-<?= $si ?>-<?= $li ?>-minutes" type="number" min="0" max="600"
                                   name="stages[<?= $si ?>][lessons][<?= $li ?>][minutes]" value="<?= (int) $lessonRow['minutes'] ?>"></div>
                        <div class="field" style="grid-column:1/-1"><label for="ls-<?= $si ?>-<?= $li ?>-goal">هدفِ درس در یک جمله</label>
                            <input class="input" id="ls-<?= $si ?>-<?= $li ?>-goal" type="text" maxlength="240"
                                   name="stages[<?= $si ?>][lessons][<?= $li ?>][goal]" value="<?= e((string) $lessonRow['goal']) ?>"></div>
                        <div class="field" style="grid-column:1/-1"><label for="ls-<?= $si ?>-<?= $li ?>-text">متنِ درس (هر خط یک پاراگراف)</label>
                            <textarea class="input" id="ls-<?= $si ?>-<?= $li ?>-text" rows="<?= $isBlank ? 3 : 8 ?>"
                                      name="stages[<?= $si ?>][lessons][<?= $li ?>][text]"
                                      placeholder="## عنوانِ بخش&#10;متنِ پاراگراف…&#10;- نکته‌ی اول&#10;- نکته‌ی دوم&#10;> یک یادآوری مهم"><?= e((string) $lessonRow['text']) ?></textarea>
                            <?php if (!$isBlank && !empty($lessonRow['lossy'])): ?>
                            <p class="field__help field__help--warn">این درس محتوای پیشرفته (تمرین، جدول یا رسانه) دارد؛ برایِ ویرایشِ متن، کادرِ «پیشرفته» پایین را باز کنید. اگر این کادر و کادرِ پیشرفته هر دو خالی بمانند، محتوای فعلی دست‌نخورده می‌ماند.</p>
                            <?php else: ?>
                            <p class="field__help"><code dir="ltr">##</code> عنوانِ بخش · <code dir="ltr">-</code> فهرست · <code dir="ltr">1.</code> فهرستِ شماره‌دار · <code dir="ltr">&gt;</code> جعبه‌ی نکته · خطِ ساده = پاراگراف</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <details class="admin-advanced builder__advanced"<?= !$isBlank && !empty($lessonRow['advanced']) ? ' open' : '' ?>>
                        <summary>پیشرفته — محتوای بلوکیِ همین درس (JSON)، تمرین و منابع</summary>
                        <div class="field">
                            <label for="ls-<?= $si ?>-<?= $li ?>-json">بلوک‌های محتوا (JSON)</label>
                            <textarea class="input input--code" id="ls-<?= $si ?>-<?= $li ?>-json" rows="6" dir="ltr" spellcheck="false"
                                      name="stages[<?= $si ?>][lessons][<?= $li ?>][blocks_json]"><?= $isBlank ? '[]' : e((string) $lessonRow['blocks_json']) ?></textarea>
                            <p class="field__help">اگر پر باشد، بر متنِ ساده اولویت دارد. نوع‌ها: <code dir="ltr">p, h2, h3, ul, ol, tip, quote, drill, table, audio, video</code>.</p>
                        </div>
                        <p class="field__help">ویدیو، پادکست و فایلِ PDF این درس را از «ویدیوها / صوت‌ها / کتاب‌ها» بسازید و در همان فرم، «درسِ مرتبط» را این درس انتخاب کنید — در صفحه‌ی درس، داخلِ سایت پخش می‌شود.</p>
                    </details>
                </div>
                <?php $li++; endforeach; ?>

                <?php /* الگوی درسِ جدید — JS از روی آن کپی می‌کند (بدونِ JS هم دو ردیفِ خالیِ بالا هست) */ ?>
                <div class="builder__lesson" data-lesson-template hidden>
                    <div class="builder__lesson-head">
                        <span class="builder__n" aria-hidden="true">+</span>
                        <strong class="builder__lesson-title">درسِ جدید</strong>
                        <label class="builder__order">ترتیب
                            <input class="input input--num" type="number" min="1" max="99"
                                   name="stages[<?= $si ?>][lessons][__IDX__][order]" value="1" data-sort-input>
                        </label>
                    </div>
                    <div class="admin-form-grid">
                        <div class="field"><label>عنوانِ درس</label>
                            <input class="input" type="text" maxlength="200" name="stages[<?= $si ?>][lessons][__IDX__][title]" value="" placeholder="عنوانِ درس"></div>
                        <div class="field"><label>نامکِ درس (خودکار اگر خالی)</label>
                            <input class="input" type="text" maxlength="100" dir="ltr" name="stages[<?= $si ?>][lessons][__IDX__][slug]" value=""></div>
                        <div class="field"><label>مدتِ مطالعه (دقیقه)</label>
                            <input class="input input--num" type="number" min="0" max="600" name="stages[<?= $si ?>][lessons][__IDX__][minutes]" value="10"></div>
                        <div class="field" style="grid-column:1/-1"><label>هدفِ درس در یک جمله</label>
                            <input class="input" type="text" maxlength="240" name="stages[<?= $si ?>][lessons][__IDX__][goal]" value=""></div>
                        <div class="field" style="grid-column:1/-1"><label>متنِ درس (هر خط یک پاراگراف)</label>
                            <textarea class="input" rows="4" name="stages[<?= $si ?>][lessons][__IDX__][text]"></textarea></div>
                    </div>
                </div>
                <button class="btn btn--ghost btn--sm" type="button" data-sort-add><?= ha_icon('plus', 14) ?> افزودنِ درس به این مرحله</button>
            </div>
        </fieldset>
        <?php endforeach; ?>

        <?php /* یک مرحله‌ی خالیِ آماده — بدونِ JS هم می‌شود مرحله اضافه کرد */ ?>
        <details class="builder__new-stage">
            <summary><?= ha_icon('plus', 14) ?> افزودنِ مرحله‌ی جدید</summary>
            <fieldset class="builder__stage builder__stage--blank" data-sort-item>
                <legend class="builder__legend"><span class="builder__n" aria-hidden="true"><?= fa_num($stageIndex + 1) ?></span> مرحله‌ی جدید</legend>
                <div class="builder__tools">
                    <label class="builder__order">ترتیب
                        <input class="input input--num" type="number" min="1" max="99"
                               name="stages[<?= $stageIndex ?>][order]" value="<?= $stageIndex + 1 ?>" data-sort-input>
                    </label>
                </div>
                <div class="admin-form-grid">
                    <div class="field"><label for="st-new-title">عنوانِ مرحله</label>
                        <input class="input" id="st-new-title" type="text" name="stages[<?= $stageIndex ?>][title]" maxlength="160" placeholder="مثلاً: تمرینِ روزانه"></div>
                    <div class="field"><label for="st-new-label">برچسبِ کوتاه</label>
                        <input class="input" id="st-new-label" type="text" name="stages[<?= $stageIndex ?>][label]" maxlength="60" placeholder="مرحله‌ی <?= fa_num($stageIndex + 1) ?>"></div>
                    <div class="field" style="grid-column:1/-1"><label for="st-new-summary">خلاصه‌ی مرحله</label>
                        <textarea class="input" id="st-new-summary" name="stages[<?= $stageIndex ?>][summary]" rows="2" maxlength="400"></textarea></div>
                </div>
                <div class="builder__lessons" data-sortable>
                    <?php foreach ([0, 1] as $nli): ?>
                    <div class="builder__lesson builder__lesson--blank" data-sort-item>
                        <div class="builder__lesson-head">
                            <span class="builder__n" aria-hidden="true"><?= fa_num($nli + 1) ?></span>
                            <strong class="builder__lesson-title">درسِ جدید (اختیاری)</strong>
                            <label class="builder__order">ترتیب
                                <input class="input input--num" type="number" min="1" max="99"
                                       name="stages[<?= $stageIndex ?>][lessons][<?= $nli ?>][order]" value="<?= $nli + 1 ?>" data-sort-input>
                            </label>
                        </div>
                        <div class="admin-form-grid">
                            <div class="field"><label for="ls-new-<?= $nli ?>-title">عنوانِ درس</label>
                                <input class="input" id="ls-new-<?= $nli ?>-title" type="text" maxlength="200" name="stages[<?= $stageIndex ?>][lessons][<?= $nli ?>][title]" placeholder="عنوانِ درس"></div>
                            <div class="field"><label for="ls-new-<?= $nli ?>-slug">نامکِ درس (خودکار اگر خالی)</label>
                                <input class="input" id="ls-new-<?= $nli ?>-slug" type="text" maxlength="100" dir="ltr" name="stages[<?= $stageIndex ?>][lessons][<?= $nli ?>][slug]"></div>
                            <div class="field"><label for="ls-new-<?= $nli ?>-minutes">مدتِ مطالعه (دقیقه)</label>
                                <input class="input input--num" id="ls-new-<?= $nli ?>-minutes" type="number" min="0" max="600" name="stages[<?= $stageIndex ?>][lessons][<?= $nli ?>][minutes]" value="10"></div>
                            <div class="field" style="grid-column:1/-1"><label for="ls-new-<?= $nli ?>-goal">هدفِ درس در یک جمله</label>
                                <input class="input" id="ls-new-<?= $nli ?>-goal" type="text" maxlength="240" name="stages[<?= $stageIndex ?>][lessons][<?= $nli ?>][goal]"></div>
                            <div class="field" style="grid-column:1/-1"><label for="ls-new-<?= $nli ?>-text">متنِ درس (هر خط یک پاراگراف)</label>
                                <textarea class="input" id="ls-new-<?= $nli ?>-text" rows="3" name="stages[<?= $stageIndex ?>][lessons][<?= $nli ?>][text]"></textarea>
                                <p class="field__help"><code dir="ltr">##</code> عنوانِ بخش · <code dir="ltr">-</code> فهرست · <code dir="ltr">&gt;</code> نکته</p></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        </details>
    </div>

    <details class="admin-advanced">
        <summary>پیشرفته — ویرایشِ کلِ ساختار با JSON</summary>
        <div class="field">
            <label for="c-stages">ساختارِ مراحل (JSON)</label>
            <textarea class="input input--code" id="c-stages" name="stages_json" rows="14" dir="ltr"
                      spellcheck="false"><?= e(json_encode($stages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea>
            <p class="field__help">
                آرایه‌ای از مراحل؛ هر مرحله: <code dir="ltr">id, label, title, summary, outcome, duration, lessons[]</code>.
                هر درس: <code dir="ltr">slug, title, minutes, goal, blocks[], drill, prerequisite, refs</code>.
            </p>
        </div>
        <p class="field__help field__help--warn">
            دکمه‌ی «ذخیره از روی JSON» فقط همین JSON را ملاک می‌گیرد و فرمِ بالا را نادیده می‌گیرد.
            برایِ کارِ روزمره از همان فرمِ ساختاریافته استفاده کنید.
        </p>
        <button class="btn btn--ghost btn--sm" type="submit" name="save_from_json" value="1"><?= ha_icon('edit', 14) ?> ذخیره از روی JSON</button>
    </details>
</div>

<div class="admin-form-actions">
    <button class="btn btn--primary" type="submit"><?= ha_icon('check', 15) ?> ذخیره‌ی دوره</button>
    <a class="btn btn--ghost" href="<?= e(url('admin_courses')) ?>">بازگشت</a>
</div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
