<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$allCourses = courses();
?>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>عنوان</th><th>دسته</th><th>سطح</th><th>تعداد درس</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allCourses as $c):
$lessons = 0; foreach (($c['stages'] ?? []) as $st) $lessons += count($st['lessons'] ?? []);
?>
<tr><td><?= e($c['title'] ?? '') ?></td><td><span class="badge badge--soft"><?= e($c['category'] ?? '') ?></span></td>
<td><?= e($c['level'] ?? '') ?></td><td><?= fa_num($lessons) ?> درس</td>
<td class="actions"><span class="muted-sm">از فایل</span></td></tr>
<?php endforeach; ?>
<?php if ($allCourses === []): ?><tr><td colspan="5" class="muted-sm" style="text-align:center">دوره‌ای تعریف نشده.</td></tr><?php endif; ?>
</tbody></table></div>
<div class="admin-card" style="margin-top:1.5rem">
    <h2>راهنما</h2>
    <p class="muted-sm">دوره‌ها و درس‌ها در فایل <code dir="ltr">data/course.php</code> تعریف می‌شوند. برای افزودن یا ویرایش دوره، فایل data/course.php را ویرایش کنید. ساختار دوره‌ها شامل مراحل (stages) و درس‌ها (lessons) است.</p>
    <p class="muted-sm">مقالات، کتاب‌ها، ویدیوها، صوتها، تمرین‌ها و نکته‌ها از طریق همین پنل قابل مدیریت هستند.</p>
</div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
