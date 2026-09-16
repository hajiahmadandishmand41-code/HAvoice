<?php
/**
 * HAvoice Admin — ویرایش/ساختِ تمرین
 * هر تمرین می‌تواند به یک «درس» (و از طریق آن، دوره‌ی همان درس) یا
 * مستقیم به یک «دوره» متصل شود تا در صفحه‌ی درس نمایش داده شود.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$id = param('slug');
$item = null;
if ($id !== '') {
    foreach (exercises_all() as $ex) {
        if (slugify((string) ($ex['id'] ?? '')) === slugify($id)) { $item = $ex; break; }
    }
    if ($item === null) { flash('error', 'تمرین پیدا نشد.'); redirect(url('admin_exercises')); }
}

$curLesson = slugify((string) ($item['lesson'] ?? ''));
$curCourse = slugify((string) ($item['course'] ?? ''));
?>
<?= admin_flash() ?>
<form class="admin-form" method="post" action="<?= e(url('admin_exercise_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_id" value="<?= e($id) ?>">

<div class="admin-card">
    <h2>مشخصاتِ تمرین</h2>
    <div class="admin-form-grid">
        <div class="field"><label for="e-title">نام *</label><input class="input" id="e-title" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required maxlength="160"></div>
        <div class="field" style="grid-column:1/-1"><label for="e-goal">توضیح</label><textarea class="input" id="e-goal" name="goal" rows="3" maxlength="300" placeholder="این تمرین چه مهارتی را می‌سازد؟"><?= e($item['goal'] ?? '') ?></textarea></div>
        <div class="field"><label for="e-order">ترتیب</label>
            <input class="input input--num" id="e-order" type="number" name="order" min="0" max="999"
                   value="<?= e((string) (int) ($item['order'] ?? 0)) ?>">
            <p class="field__help">عدد کوچک‌تر زودتر دیده می‌شود. از فهرست تمرین‌ها هم می‌توانید با دکمه‌های بالا/پایین جابه‌جا کنید.</p></div>
        <div class="field"><label for="e-seconds">مدت (ثانیه)</label><input class="input" id="e-seconds" type="number" name="seconds" value="<?= e((string)($item['seconds'] ?? 180)) ?>" min="0" max="36000"></div>
        <?= admin_status_field($item ?? []) ?>
    </div>
    <div class="field"><label for="e-steps">مراحل (هر خط یک مرحله)</label><textarea class="input" id="e-steps" name="steps_text" rows="5"><?= e(implode("\n", array_map('strval', (array) ($item['steps'] ?? [])))) ?></textarea></div>
    <input type="hidden" name="id" value="<?= e($item['id'] ?? $id) ?>">
</div>

<details class="admin-advanced admin-card">
    <summary>تنظیمات بیشتر (اختیاری)</summary>
    <div class="admin-form-grid">
        <div class="field"><label for="e-id">شناسه</label><input class="input" id="e-id" type="text" name="id_override" value="<?= e($item['id'] ?? '') ?>" dir="ltr" maxlength="60" placeholder="خودکار از نام"></div>
        <div class="field"><label for="e-level">سطح</label><input class="input" id="e-level" type="text" name="level" value="<?= e($item['level'] ?? 'عمومی') ?>" maxlength="40"></div>
        <div class="field"><label for="e-focus">تمرکز</label><input class="input" id="e-focus" type="text" name="focus" value="<?= e($item['focus'] ?? '') ?>" maxlength="60"></div>
        <div class="field"><label for="e-success">معیارِ موفقیت</label><input class="input" id="e-success" type="text" name="success" value="<?= e($item['success'] ?? '') ?>" maxlength="200"></div>
        <?= admin_featured_field($item ?? []) ?>
        <div class="field"><label for="e-lesson">درسِ متصل</label>
            <select class="input" id="e-lesson" name="lesson">
                <option value="">— بدون اتصال —</option>
                <?php foreach (courses_all() as $c): ?>
                    <optgroup label="<?= e($c['title'] ?? '') ?>">
                    <?php foreach ((array) ($c['stages'] ?? []) as $st): foreach ((array) ($st['lessons'] ?? []) as $l): $ls = slugify((string) ($l['slug'] ?? '')); ?>
                        <option value="<?= e($ls) ?>"<?= $ls === $curLesson ? ' selected' : '' ?>><?= e($l['title'] ?? $ls) ?></option>
                    <?php endforeach; endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select></div>
        <div class="field"><label for="e-course">دوره‌ی متصل</label>
            <select class="input" id="e-course" name="course">
                <option value="">— بدون اتصال —</option>
                <?php foreach (courses_all() as $c): $cs = slugify((string) ($c['slug'] ?? '')); ?>
                <option value="<?= e($cs) ?>"<?= $cs === $curCourse ? ' selected' : '' ?>><?= e($c['title'] ?? $cs) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="field" style="grid-column:1/-1"><label for="e-topics">موضوعات بداهه (هر خط یک موضوع)</label><textarea class="input" id="e-topics" name="topics_text" rows="3"><?= e(implode("\n", array_map('strval', (array) ($item['topics'] ?? [])))) ?></textarea></div>
    </div>
</details>

<div class="admin-form-actions">
    <button class="btn btn--primary" type="submit"><?= ha_icon('check', 15) ?> ذخیره‌ی تمرین</button>
    <a class="btn btn--ghost" href="<?= e(url('admin_exercises')) ?>">بازگشت</a>
</div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
