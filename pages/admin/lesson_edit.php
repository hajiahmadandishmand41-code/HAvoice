<?php
/**
 * HAvoice Admin — فرم درس (ساخت/ویرایش) — داخلِ دوره
 *
 * فقط فیلدهای لازمِ مدیر: عنواد درس، محتوا، مرحله‌ی مقصد، تمرین، وضعیت.
 * نامک، زمان، order و لینک‌ها خودکار ساخته/محاسبه می‌شوند.
 * «ذخیره همه» = جمعِ چند درس پیاپی با همان فرم (بازگشت به همان صفحه).
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$courseSlug = slugify(param('course', ''));
$stageKey   = trim(param('stage_key', ''));
$slug       = slugify(param('slug', ''));
$editSlug   = $slug; // نامکِ فعلی برای ویرایش (اگر هست)

$course = repo_course_find($courseSlug);
if ($course === null) {
    /* بدونِ دوره‌ی معلوم: انتخاب‌گرِ دوره (لینکِ +Lesson داشبورد) */
    $allC = repo_courses();
    if (count($allC) === 1) {
        redirect(url('admin_lesson_edit', ['course' => (string) ($allC[0]['slug'] ?? '')]));
    }
    ?>
    <?= admin_flash() ?>
    <div class="admin-card">
        <h2 class="admin-card__title mb-sm">درسِ جدید در کدام دوره؟</h2>
        <p class="muted-sm mb-sm"><?= $allC === [] ? 'هنوز دوره‌ای ساخته نشده — اول یک دوره بسازید:' : 'یکی از دوره‌ها را انتخاب کنید تا فرمِ درس باز شود:' ?></p>
        <div class="admin-quick">
        <?php if ($allC === []): ?>
            <a href="<?= e(url('admin_course_edit')) ?>"><?= ha_icon('plus', 16) ?> +Course (دوره‌ی جدید)</a>
        <?php endif; ?>
        <?php foreach ($allC as $c): ?>
            <a href="<?= e(url('admin_lesson_edit', ['course' => (string) ($c['slug'] ?? ''), 'stage_key' => trim(param('stage_key', ''))])) ?>"><?= ha_icon('steps', 16) ?> <?= e((string) ($c['title'] ?? $c['slug'] ?? '')) ?></a>
        <?php endforeach; ?>
        </div>
    </div>
    <?php
    require HA_ROOT . '/pages/admin/_layout_end.php';
    return;
}

/* اگر slug داده شده، درسِ فعلی را پیدا کن */
$existingLesson = null;
if ($editSlug !== '') {
    foreach ((array) ($course['stages'] ?? []) as $st) {
        foreach ((array) ($st['lessons'] ?? []) as $l) {
            if (slugify((string) ($l['slug'] ?? '')) === $editSlug) {
                $existingLesson = $l;
                $existingLesson['_stage_key'] = (string) ($st['id'] ?? '');
                break 2;
            }
        }
    }
}

$isNew   = $existingLesson === null;
$default = [
    'title'        => '',
    'goal'         => '',
    'minutes'      => 0,
    'blocks'       => [],
    'drill'        => [],
    'prerequisite' => '',
    'refs'         => [],
    'status'       => 'draft',
];
$cur = $existingLesson !== null ? array_merge($default, $existingLesson) : $default;

$stages     = array_values((array) ($course['stages'] ?? []));
$statusNow  = ha_is_published($cur) ? 'published' : 'draft';
$actionUrl  = url('admin_lesson_save');
?>
<?= admin_flash() ?>

<div class="admin-toolbar">
    <a class="btn btn--ghost" href="<?= e(url('admin_course_view', ['slug' => $courseSlug])) ?>"><?= ha_icon('arrow-right', 15) ?> بازگشت به داشبورد دوره: «<?= e((string) ($course['title'] ?? $courseSlug)) ?>»</a>
    <span class="muted-sm"><?= $isNew ? 'درسِ جدید' : 'ویرایش: ' . e((string) ($cur['title'] ?? '')) ?></span>
</div>

<div class="admin-card admin-card--headline mb-md">
    <div class="admin-card__row">
        <div>
            <p class="admin-card__eyebrow"><?= $isNew ? 'ساخت درس' : 'ویرایش درس' ?></p>
            <h2 class="admin-card__title"><?= $isNew ? 'درسِ تازه در «' . e((string) ($course['title'] ?? '')) . '»' : e((string) ($cur['title'] ?? '')) ?></h2>
        </div>
        <div class="admin-card__aside"><?= admin_status_badge($cur) ?></div>
    </div>
</div>

<form method="post" action="<?= e($actionUrl) ?>" class="admin-form">
    <?= csrf_field() ?>
    <input type="hidden" name="course" value="<?= e($courseSlug) ?>">
    <input type="hidden" name="orig_key" value="<?= e($isNew ? '' : $editSlug) ?>">

    <section class="admin-card mb-md">
        <div class="field-row field-row--2">
            <div class="field">
                <label for="lesson-title">عنوان درس <span class="req">*</span></label>
                <input class="input" id="lesson-title" name="title" maxlength="200" required
                       value="<?= e((string) ($cur['title'] ?? '')) ?>"
                       placeholder="صحبت بدون مکث — همکلامی عادی">
                <p class="field__help">نامِ این درس. برای ساختِ پی‌درپی، همان نام را بازنویسی کنید.</p>
            </div>
            <div class="field">
                <label for="lesson-stage">مرحله‌ی مقصد <span class="req">*</span></label>
                <select class="input" id="lesson-stage" name="stage_key">
                    <?php foreach ($stages as $i => $st):
                        $sK = (string) ($st['id'] ?? ('stage-' . ($i + 1)));
                        $sL = (string) ($st['label'] ?? ('مرحله‌ی ' . fa_num($i + 1)));
                        $sT = trim((string) ($st['title'] ?? '')) !== '' ? ' — ' . $st['title'] : '';
                        $selected = '';
                        if (($stageKey !== '' && $stageKey === $sK) || ($stageKey === '' && isset($cur['_stage_key']) && $cur['_stage_key'] === $sK)) {
                            $selected = ' selected';
                        }
                    ?>
                    <option value="<?= e($sK) ?>"<?= $selected ?>><?= e($sL . $sT) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="field">
            <label for="lesson-goal">هدف/محتوای درس <span class="req">*</span></label>
            <textarea class="input textarea" id="lesson-goal" name="goal" rows="4"
                      placeholder="هدف این درس در یک جمله. مثال: قادر شوی ۳ کاپا را با مکثِ غیراجباری پشت سر هم بگویی."><?= e((string) ($cur['goal'] ?? '')) ?></textarea>
        </div>

        <div class="field-row field-row--2">
            <div class="field">
                <label for="lesson-minutes">مدت تقریبی (دقیقه)</label>
                <input class="input input--sm" id="lesson-minutes" name="minutes" type="number" min="0" max="600"
                       value="<?= e((string) max(1, (int) ($cur['minutes'] ?? 0))) ?>">
            </div>
            <div class="field">
                <label for="lesson-refs">منابع (هر خط یک منبع — اختیاری)</label>
                <textarea class="input textarea textarea--sm" id="lesson-refs" name="refs" rows="3"
                          placeholder="https://..."><?= e(implode("\n", (array) ($cur['refs'] ?? []))) ?></textarea>
            </div>
        </div>
    </section>

    <section class="admin-card mb-md">
        <h3 class="admin-card__title mb-sm">محتوا — بلوک‌های درس</h3>
        <p class="muted-sm mb-sm">هر بلوک نوعِ دلخواه دارد (متن، تمرین، تبصره، ویدیو/صوت). در فرمِ ساده فقط متن آزاد وارد کنید؛ برای ساختارِ پیشرفته از JSON زیر استفاده کنید.</p>
        <div class="field">
            <label for="lesson-blocks">بلوک‌های JSON (اختیاری — فرمت پیشرفته)</label>
            <textarea class="input textarea textarea--mono" id="lesson-blocks" name="blocks_json" rows="10"
                      placeholder='[{"type":"text","text":"متن آزاد..."}]'><?= e(($cur['blocks'] ?? []) !== [] ? (string) json_encode($cur['blocks'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '') ?></textarea>
            <p class="field__help">خالی = از عنوان/محتوای بالا استفاده می‌شود تا محتوای حداقلیِ درس ساخته شود.</p>
        </div>
        <?php if (($cur['drill'] ?? []) !== []): ?>
        <div class="field">
            <label for="lesson-drill">تمرینِ ساختاری درس (JSON — اختیاری)</label>
            <textarea class="input textarea textarea--mono" id="lesson-drill" name="drill_json" rows="4"><?= e((string) json_encode($cur['drill'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></textarea>
        </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <h3 class="admin-card__title mb-sm">تمرین متصل به این درس</h3>
        <?php if ($isNew): ?>
            <p class="muted-sm">تمرین‌ها را بعد از ساختِ درس از <a href="<?= e(url('admin_course_view', ['slug' => $courseSlug])) ?>#lessons">داشبورد دوره</a> اضافه می‌کنید (یا از دکمه‌ی «+ تمرین» کنار هر درس).</p>
            <input type="hidden" name="exercise_ids" value="">
        <?php else:
            $linked = [];
            foreach (exercises_all() as $ex) {
                if (slugify((string) ($ex['lesson'] ?? '')) === $slug) {
                    $linked[] = $ex;
                }
            }
        ?>
            <?php if ($linked === []): ?>
                <p class="muted-sm">هنوز تمرینی به این درس متصل نیست. با دکمه‌ی «+ تمرین» در داشبورد دوره بسازید؛ یا <a href="<?= e(url('admin_exercise_edit', ['lesson' => $slug, 'course' => $courseSlug, 'stage_key' => $stageKey])) ?>">اینجا</a> مستقیم اضافه کنید.</p>
            <?php else: ?>
                <p class="muted-sm mb-sm">تمرین‌های متصل: <?= fa_num(count($linked)) ?> — برای افزودنِ پیاپی در همان فرم ذخیره کنید تا به اینجا برگردد.</p>
                <ul class="admin-list">
                <?php foreach ($linked as $ex): ?>
                    <li>
                        <span><?= e((string) ($ex['title'] ?? $ex['id'] ?? '—')) ?></span>
                        <a class="btn btn--ghost btn--sm" href="<?= e(url('admin_exercise_edit', ['slug' => slugify((string) ($ex['id'] ?? ''))])) ?>">ویرایش</a>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="admin-card mb-md">
        <h3 class="admin-card__title mb-sm">وضعیت</h3>
        <?= admin_status_field($cur, $isNew) ?>
        <div class="field-row field-row--2 mt-sm">
            <div class="field"><label for="lesson-featured">ویژه (نمایش در صفحه‌ی نخست)</label>
                <label class="check"><input type="checkbox" name="featured" value="1"<?= !empty($cur['featured']) ? ' checked' : '' ?>><span>منتخب — در صفحه‌ی نخست نمایش داده شود</span></label></div>
            <div class="field"><label for="lesson-prereq">پیش‌نیاز (نامکِ درسِ قبلی — اختیاری)</label>
                <input class="input" id="lesson-prereq" name="prerequisite" maxlength="120" value="<?= e((string) ($cur['prerequisite'] ?? '')) ?>" placeholder="weight-control-intro">
            </div>
        </div>
    </section>

    <div class="admin-actions">
        <button class="btn btn--primary btn--lg" type="submit" name="save_val" value="1"><?= ha_icon('check', 15) ?> ذخیره و برگشت</button>
        <button class="btn btn--ghost btn--lg" type="submit" name="save_val" value="stay"><?= ha_icon('plus', 15) ?> ذخیره و درسِ بعدی»</button>
        <?php if ($isNew && count($stages) > 0): ?>
        <span class="muted-sm mt-sm">با «ذخیره و درسِ بعدی» هر بار همان فرمِ این مرحله باز می‌شود تا چند درس را پیاپی ساخته و آخرش یک‌جا (همان «ذخیره همه») ثبت کنید.</span>
        <?php endif; ?>
    </div>
</form>

<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
