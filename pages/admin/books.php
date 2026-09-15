<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$panelSlugs = [];
foreach (admin_load('books') as $b) { $panelSlugs[slugify((string) ($b['slug'] ?? ''))] = true; }
$allBooks = books_all();
?>
<?= admin_flash() ?>
<div class="admin-toolbar">
<a class="btn btn--primary" href="<?= e(url('admin_book_edit')) ?>"><?= ha_icon('book',16) ?> کتاب جدید</a>
<span class="muted-sm"><?= fa_num(count($allBooks)) ?> کتاب · برای هر کتاب می‌توانید فایل (PDF) یا متن کامل درون‌سایتی ثبت کنید.</span>
</div>
<div class="admin-table-wrap" style="margin-top:1rem">
<table class="admin-table"><thead><tr><th>عنوان</th><th>نویسنده</th><th>حوزه</th><th>محتوا</th><th>وضعیت</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allBooks as $b):
    $slug = slugify((string) ($b['slug'] ?? ''));
    $isPanel = isset($panelSlugs[$slug]);
    $hasFull = !empty($b['blocks']) || !empty($b['file']) || !empty($b['link']);
?>
<tr<?= ha_is_published($b) ? '' : ' class="admin-row--draft"' ?>>
<td><a href="<?= e(url('books', ['slug' => $slug])) ?>"><?= e($b['title'] ?? '') ?></a></td>
<td><?= e($b['author'] ?? '') ?></td>
<td><span class="badge badge--soft"><?= e(category_label(ha_item_field_slug($b), (string) ($b['category'] ?? ''))) ?></span></td>
<td class="muted-sm"><?php if (!empty($b['blocks'])): ?>متن کامل<?php endif; ?><?php if (!empty($b['file'])): ?><?= !empty($b['blocks']) ? ' + ' : '' ?>فایل<?php endif; ?><?php if (!$hasFull): ?>خلاصه<?php endif; ?></td>
<td><?= admin_status_badge($b) ?></td>
<td><?= admin_source_badge($isPanel) ?></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_book_edit', ['slug' => $slug])) ?>"><?= ha_icon('edit', 14) ?> ویرایش</a>
<?= admin_status_toggle('book', $slug, $b) ?>
<?php if ($isPanel): ?><form method="post" action="<?= e(url('admin_book_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($slug) ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if ($allBooks === []): ?><tr><td colspan="7" class="admin-empty">کتابی نیست؛ اولین کتاب را ثبت کنید.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
