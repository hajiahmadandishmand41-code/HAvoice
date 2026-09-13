<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$adminArticles = admin_load('articles');
$allArticles = array_merge(data('articles'), $adminArticles);
?>
<a class="btn btn--primary" href="<?= e(url('admin_article_edit')) ?>"><?= ha_icon('article',16) ?> مقاله جدید</a>
<div class="admin-table-wrap" style="margin-top:1rem">
<table class="admin-table">
<thead><tr><th>عنوان</th><th>دسته</th><th>تاریخ</th><th>منبع</th><th>عملیات</th></tr></thead>
<tbody>
<?php foreach ($allArticles as $i => $a): $isData = $i < count(data('articles')); ?>
<tr>
<td><?= e($a['title'] ?? '') ?></td>
<td><span class="badge badge--soft"><?= e($a['category'] ?? '') ?></span></td>
<td class="muted-sm"><?= e($a['date_fa'] ?? $a['date'] ?? '') ?></td>
<td><span class="admin-badge <?= $isData ? 'admin-badge--info' : 'admin-badge--success' ?>"><?= $isData ? 'فایل' : 'پنل' ?></span></td>
<td class="actions">
<a class="btn btn--ghost btn--sm" href="<?= e(url('admin_article_edit', ['slug' => $a['slug'] ?? ''])) ?>">ویرایش</a>
<?php if (!$isData): ?>
<form method="post" action="<?= e(url('admin_article_delete')) ?>" style="display:inline" onsubmit="return confirm('حذف شود؟')"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($a['slug'] ?? '') ?>"><button class="btn btn--ghost btn--sm" type="submit" style="color:var(--danger)">حذف</button></form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
