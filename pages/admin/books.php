<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$adminBooks = admin_load('books');
$allBooks = array_merge(books(), $adminBooks);
$srcCount = count(books());
?>
<a class="btn btn--primary" href="<?= e(url('admin_book_edit')) ?>"><?= ha_icon('book',16) ?> کتاب جدید</a>
<div class="admin-table-wrap" style="margin-top:1rem">
<table class="admin-table"><thead><tr><th>عنوان</th><th>نویسنده</th><th>دسته</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allBooks as $i => $b): $isData = $i < $srcCount; ?>
<tr><td><?= e($b['title'] ?? '') ?></td><td><?= e($b['author'] ?? '') ?></td><td><span class="badge badge--soft"><?= e($b['category'] ?? '') ?></span></td>
<td><span class="admin-badge <?= $isData ? 'admin-badge--info' : 'admin-badge--success' ?>"><?= $isData ? 'فایل' : 'پنل' ?></span></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_book_edit', ['slug' => $b['slug'] ?? ''])) ?>">ویرایش</a>
<?php if (!$isData): ?><form method="post" action="<?= e(url('admin_book_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($b['slug'] ?? '') ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
