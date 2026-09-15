<?php
/**
 * HAvoice Admin — داشبورد دوره (مرحله‌ها، درس‌ها، تمرین‌ها)
 *
 * نقطه‌ی تمرکزِ مدیریت یک دوره: مراحل و درس‌ها را به‌صورت درخت می‌بینید،
 * مرحله و درس می‌سازید/ویرایش می‌کنید و وضعیتِ انتشار را کنترل می‌کنید.
 * تمرین‌های هر درس با شمارش و لینکِ سریعِ +تمرین نشان داده می‌شوند.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$slug = slugify(param('slug', ''));
$course = repo_course_find($slug);

if ($course === null) {
    flash('دوره پیدا نشد: ' . ($slug !== '' ? $slug : '(بدون نامک)'), 'error');
    ?>
    <?= admin_flash() ?>
    <p><a class="btn btn--ghost" href="<?= e(url('admin_courses')) ?>"><?= ha_icon('arrow-right', 15) ?> بازگشت به فهرست دوره‌ها</a></p>
    <?php
    require HA_ROOT . '/pages/admin/_layout_end.php';
    return;
}

$published = ha_is_published($course);
$stages    = array_values((array) ($course['stages'] ?? []));

/* شمارشِ تمرین‌ها برای هر درس (یک بار برای کلِ سایت) */
$exerciseCount = [];
foreach (exercises_all() as $ex) {
    $ls = slugify((string) ($ex['lesson'] ?? ''));
    if ($ls !== '') {
        $exerciseCount[$ls] = ($exerciseCount[$ls] ?? 0) + 1;
    }
}

$statusLabel = ['published' => 'منتشرشده', 'draft' => 'پیش‌نویس'];
$haveExercises = exercises_all();
?>
<?= admin_flash() ?>

<div class="admin-toolbar">
    <a class="btn btn--primary" href="<?= e(url('admin_lesson_edit', ['course' => $slug, 'stage_key' => (string) ($stages[0]['id'] ?? '')])) ?>"><?= ha_icon('plus', 16) ?> درسِ جدید در این دوره</a>
    <a class="btn btn--ghost" href="<?= e(url('admin_course_edit', ['slug' => $slug])) ?>"><?= ha_icon('edit', 16) ?> ویرایش دوره</a>
    <a class="btn btn--ghost" href="<?= e(url('admin_courses')) ?>"><?= ha_icon('arrow-right', 16) ?> همه‌ی دوره‌ها</a>
    <a class="btn btn--ghost" href="<?= e(url('course', ['slug' => $slug])) ?>"><?= ha_icon('external', 16) ?> نمایش عمومی</a>
</div>

<div class="admin-card admin-card--headline mb-md">
    <div class="admin-card__row">
        <div>
            <p class="admin-card__eyebrow">داشبورد دوره</p>
            <h2 class="admin-card__title"><?= e((string) ($course['title'] ?? $slug)) ?></h2>
            <p class="muted-sm">
                <span class="badge badge--soft"><?= e(category_label((string) ($course['category'] ?? ''), (string) ($course['category'] ?? ''))) ?></span>
                سطح: <?= e((string) ($course['level'] ?? '—')) ?> ·
                نامک: <code><?= e($slug) ?></code>
            </p>
        </div>
        <div class="admin-card__aside">
            <?= admin_status_badge($course) ?>
        </div>
    </div>
</div>

<?php foreach ($stages as $si => $st):
    $stageKey   = (string) ($st['id'] ?? ('stage-' . ($si + 1)));
    $stageStat  = ha_is_published($st) ? 'published' : 'draft';
    $stageState = $stageStat === 'published' ? 'published' : 'draft';
    $lessons    = array_values((array) ($st['lessons'] ?? []));
?>
<section class="admin-card mb-md">
    <div class="admin-card__head">
        <div>
            <h3 class="admin-card__title">
                <?= e((string) ($st['label'] ?? ('مرحله‌ی ' . fa_num($si + 1)))) ?>
                <?php if (trim((string) ($st['title'] ?? '')) !== ''): ?> — <?= e((string) $st['title']) ?><?php endif; ?>
            </h3>
            <?php if (trim((string) ($st['summary'] ?? '')) !== ''): ?>
                <p class="muted-sm"><?= e((string) $st['summary']) ?></p>
            <?php endif; ?>
        </div>
        <div class="admin-card__aside actions">
            <span class="admin-badge <?= $st && ha_is_published($st) ? 'admin-badge--success' : 'admin-badge--warn' ?>"><?= e($statusLabel[$stageStat]) ?></span>
            <form method="post" action="<?= e(url('admin_content_status')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="stage">
                <input type="hidden" name="key" value="<?= e($stageKey) ?>">
                <input type="hidden" name="status" value="<?= $stageStat === 'published' ? 'draft' : 'published' ?>">
                <input type="hidden" name="course" value="<?= e($slug) ?>">
                <button class="btn btn--ghost btn--sm" type="submit"><?= $stageStat === 'published' ? ha_icon('close', 13) . ' مخفی کردن' : ha_icon('check', 13) . ' انتشار' ?></button>
            </form>
            <form method="post" action="<?= e(url('admin_stage_delete')) ?>" class="inline-form"
                  data-confirm="مرحله‌ی «<?= e((string) ($st['label'] ?? $stageKey)) ?>» و همه‌ی درس‌ها و تمرین‌های آن برای همیشه حذف شوند؟">
                <?= csrf_field() ?>
                <input type="hidden" name="stage_key" value="<?= e($stageKey) ?>">
                <input type="hidden" name="course" value="<?= e($slug) ?>">
                <button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف مرحله</button>
            </form>
        </div>
    </div>

    <div class="admin-table-wrap"><table class="admin-table"><thead>
        <tr><th>درس</th><th>زمان</th><th>تمرین</th><th>وضعیت</th><th>عملیات</th></tr>
    </thead><tbody>
    <?php foreach ($lessons as $li => $l):
        $ls       = slugify((string) ($l['slug'] ?? ''));
        $ltitle   = (string) ($l['title'] ?? $ls);
        $lStat    = ha_is_published($l) ? 'published' : 'draft';
        $minutes  = (int) ($l['minutes'] ?? 0);
        $exN      = $exerciseCount[$ls] ?? 0;
    ?>
    <tr<?= $lStat === 'published' ? '' : ' class="admin-row--draft"' ?>>
        <td><a href="<?= e(url('admin_lesson_edit', ['course' => $slug, 'stage_key' => $stageKey, 'slug' => $ls])) ?>"><strong><?= e($ltitle) ?></strong></a>
            <?php if ($ls !== ''): ?><span class="muted-sm"><code><?= e($ls) ?></code></span><?php endif; ?></td>
        <td><?= $minutes > 0 ? e(minutes_label($minutes)) : '<span class="muted-sm">—</span>' ?></td>
        <td><?= $exN > 0 ? fa_num($exN) . ' تمرین' : '<span class="muted-sm">—</span>' ?></td>
        <td><?= admin_status_badge($l) ?></td>
        <td class="actions">
            <a class="btn btn--ghost btn--sm" href="<?= e(url('admin_lesson_edit', ['course' => $slug, 'stage_key' => $stageKey, 'slug' => $ls])) ?>"><?= ha_icon('edit', 14) ?> ویرایش</a>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('admin_exercise_edit', ['lesson' => $ls, 'course' => $slug, 'stage_key' => $stageKey])) ?>"><?= ha_icon('plus', 14) ?> تمرین</a>
            <form method="post" action="<?= e(url('admin_content_status')) ?>" class="inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="type" value="lesson">
                <input type="hidden" name="key" value="<?= e($ls) ?>">
                <input type="hidden" name="status" value="<?= $lStat === 'published' ? 'draft' : 'published' ?>">
                <input type="hidden" name="course" value="<?= e($slug) ?>">
                <button class="btn btn--ghost btn--sm" type="submit"><?= $lStat === 'published' ? ha_icon('close', 13) . ' مخفی کردن' : ha_icon('check', 13) . ' انتشار' ?></button>
            </form>
            <form method="post" action="<?= e(url('admin_lesson_delete')) ?>" class="inline-form"
                  data-confirm="درس «<?= e($ltitle) ?>» و همه‌ی تمرین‌های متصل به آن حذف شوند؟ (تمرینِ بدونِ درس مجاز نیست)">
                <?= csrf_field() ?>
                <input type="hidden" name="slug" value="<?= e($ls) ?>">
                <input type="hidden" name="course" value="<?= e($slug) ?>">
                <input type="hidden" name="stage_key" value="<?= e($stageKey) ?>">
                <button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if ($lessons === []): ?>
    <tr><td colspan="5" class="admin-empty">این مرحله هنوز درسی ندارد. با «+ درسِ جدید» شروع کنید.</td></tr>
    <?php endif; ?>
    </tbody></table></div>

    <div class="admin-toolbar admin-toolbar--card">
        <a class="btn btn--ghost btn--sm" href="<?= e(url('admin_lesson_edit', ['course' => $slug, 'stage_key' => $stageKey])) ?>"><?= ha_icon('plus', 14) ?> درسِ جدید در این مرحله</a>
    </div>
</section>
<?php endforeach; ?>

<section class="admin-card" id="add-stage">
    <h3 class="admin-card__title mb-sm"><?= ha_icon('plus', 15) ?> مرحله‌ی جدید</h3>
    <form method="post" action="<?= e(url('admin_stage_save')) ?>" class="admin-inline-form">
        <?= csrf_field() ?>
        <input type="hidden" name="course" value="<?= e($slug) ?>">
        <div class="field"><label for="stage-label">برچسب مرحله</label>
            <input class="input" id="stage-label" name="label" value="<?= e('مرحله‌ی ' . fa_num(count($stages) + 1)) ?>" maxlength="40"></div>
        <div class="field"><label for="stage-title">عنوان مرحله</label>
            <input class="input" id="stage-title" name="title" maxlength="200" required placeholder="مثل: آشنایی و وارم‌آپ"></div>
        <div class="field"><label for="stage-summary">خلاصه‌ی کوتاه (اختیاری)</label>
            <input class="input" id="stage-summary" name="summary" maxlength="200"></div>
        <div class="actions"><button class="btn btn--primary" type="submit"><?= ha_icon('check', 14) ?> افزودن مرحله</button></div>
    </form>
</section>

<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
