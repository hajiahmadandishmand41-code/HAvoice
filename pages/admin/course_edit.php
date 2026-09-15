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
        <?= admin_status_field($course ?? []) ?>
        <div class="field">
            <label class="check"><input type="checkbox" name="featured" value="1"<?= !empty($course['featured']) ? ' checked' : '' ?>>
                <span>دوره‌ی ویژه (در صفحه‌ی اصلی نمایش داده شود)</span></label>
        </div>
    </div>
</div>

<div class="admin-card">
    <h2>مراحل و درس‌ها</h2>
    <div class="field">
        <label for="c-stages">ساختارِ مراحل (JSON)</label>
        <textarea class="input input--code" id="c-stages" name="stages_json" rows="18" dir="ltr"
                  spellcheck="false"><?= e(json_encode($stages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea>
        <p class="field__help">
            آرایه‌ای از مراحل؛ هر مرحله: <code dir="ltr">id, label, title, summary, outcome, duration, lessons[]</code>.
            هر درس: <code dir="ltr">slug, title, minutes, goal, blocks[]</code>.
            بلوک‌ها همان قالبِ مقاله‌اند: <code dir="ltr">{"type":"p","text":"…"}</code>،
            <code dir="ltr">{"type":"h2","text":"…"}</code>، <code dir="ltr">{"type":"ul","items":["…"]}</code>،
            <code dir="ltr">{"type":"tip","tone":"warn","title":"…","text":"…"}</code>.
        </p>
    </div>
    <p class="muted-sm">نامکِ هر درس باید در کلِ سایت یکتا باشد؛ صفحه‌ی درس با همان نامک ساخته می‌شود.</p>
</div>

<div class="admin-form-actions">
    <button class="btn btn--primary" type="submit"><?= ha_icon('check', 15) ?> ذخیره‌ی دوره</button>
    <a class="btn btn--ghost" href="<?= e(url('admin_courses')) ?>">بازگشت</a>
</div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
