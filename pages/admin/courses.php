<?php
/**
 * HAvoice Admin — فهرستِ دوره‌ها
 *
 * دوره‌های فایل (data/course.php) و پنل با هم نمایش داده می‌شوند؛
 * ویرایش/مخفی‌کردنِ موردِ فایل، نسخه‌ی پنلیِ جایگزین می‌سازد و
 * فایلِ پایه محفوظ می‌ماند.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$allCourses = courses_all();
$panelSlugs = [];
foreach (admin_courses() as $c) { $panelSlugs[slugify((string) ($c['slug'] ?? ''))] = true; }
?>
<?= admin_flash() ?>

<div class="admin-toolbar">
    <a class="btn btn--primary" href="<?= e(url('admin_course_edit')) ?>"><?= ha_icon('plus', 16) ?> دوره‌ی جدید</a>
    <span class="muted-sm"><?= fa_num(count($allCourses)) ?> دوره · <?= fa_num(count($panelSlugs)) ?> از پنل</span>
</div>

<div class="admin-table-wrap"><table class="admin-table"><thead>
<tr><th>عنوان</th><th>حوزه</th><th>سطح</th><th>درس</th><th>وضعیت</th><th>منبع</th><th>ویژه</th><th>عملیات</th></tr>
</thead><tbody>
<?php foreach ($allCourses as $c):
    $slug = slugify((string) ($c['slug'] ?? ''));
    $lessons = 0; $minutes = 0;
    foreach ((array) ($c['stages'] ?? []) as $st) {
        foreach ((array) ($st['lessons'] ?? []) as $l) { $lessons++; $minutes += (int) ($l['minutes'] ?? 0); }
    }
    $isPanel = isset($panelSlugs[$slug]);
?>
<tr<?= ha_is_published($c) ? '' : ' class="admin-row--draft"' ?>>
<td><a href="<?= e(url('course', ['slug' => $slug])) ?>"><?= e($c['title'] ?? '') ?></a></td>
<td><span class="badge badge--soft"><?= e(category_label((string) ($c['category'] ?? ''), (string) ($c['category'] ?? ''))) ?></span></td>
<td><?= e($c['level'] ?? '') ?></td>
<td title="<?= e(minutes_label($minutes)) ?>"><?= fa_num($lessons) ?></td>
<td><?= admin_status_badge($c) ?></td>
<td><?= admin_source_badge($isPanel) ?></td>
<td><?= !empty($c['featured']) ? ha_icon('star', 14) : '<span class="muted-sm">—</span>' ?></td>
<td class="actions">
    <a class="btn btn--ghost btn--sm" href="<?= e(url('admin_course_edit', ['slug' => $slug])) ?>"><?= ha_icon('edit', 14) ?> ویرایش</a>
    <?= admin_status_toggle('course', $slug, $c) ?>
    <?php if ($isPanel): ?>
    <form method="post" action="<?= e(url('admin_course_delete')) ?>" class="inline-form"
          data-confirm="دوره «<?= e($c['title'] ?? '') ?>» برای همیشه حذف شود؟">
        <?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($slug) ?>">
        <button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button>
    </form>
    <?php else: ?>
    <span class="muted-sm" title="این دوره از data/course.php می‌آید؛ برای مخفی‌کردن از «مخفی کردن» استفاده کنید">از فایل</span>
    <?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
<?php if ($allCourses === []): ?><tr><td colspan="8" class="admin-empty">دوره‌ای تعریف نشده. با «دوره‌ی جدید» اولین دوره را بسازید.</td></tr><?php endif; ?>
</tbody></table></div>

<div class="admin-card mt-md">
    <h2>درباره‌ی منبعِ دوره‌ها</h2>
    <p class="muted-sm">دوره‌هایی که برچسبِ «فایل» دارند از <code dir="ltr">data/course.php</code> می‌آیند.
    ویرایشِ آن‌ها در پنل یک نسخه‌ی پنلی می‌سازد که از آن پس جایگزینِ نسخه‌ی فایل می‌شود (فایلِ اصلی دست‌نخورده می‌ماند)؛
    حذفِ کاملِ آن‌ها فقط با ویرایشِ فایل ممکن است ولی «مخفی کردن» از همین‌جا انجام می‌شود. دوره‌های «پنل» در
    <code dir="ltr">storage/admin/courses.json</code> ذخیره می‌شوند.</p>
    <p class="muted-sm">ساختارِ هر دوره: مراحل (<code dir="ltr">stages</code>) و در هر مرحله درس‌ها
    (<code dir="ltr">lessons</code>) با <code dir="ltr">slug, title, minutes, goal, blocks</code>.</p>
</div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
