<?php
/**
 * HAvoice Admin — فهرستِ مقاله‌ها (علمی/آموزشی)
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$allArticles = articles_all();
$panelSlugs = [];
foreach (admin_load('articles') as $a) { $panelSlugs[slugify((string) ($a['slug'] ?? ''))] = true; }
?>
<?= admin_flash() ?>
<div class="admin-toolbar">
    <a class="btn btn--primary" href="<?= e(url('admin_article_edit')) ?>"><?= ha_icon('article', 16) ?> مقاله‌ی جدید</a>
    <span class="muted-sm"><?= fa_num(count($allArticles)) ?> مقاله · هیچ مقاله‌ای خودکار از بیرون وارد نمی‌شود؛ فقط آنچه مدیر تأیید و ذخیره کند منتشر می‌شود.</span>
</div>
<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>عنوان</th><th>حوزه</th><th>نویسنده</th><th>تاریخ</th><th>وضعیت</th><th>منبع</th><th>عملیات</th></tr></thead>
<tbody>
<?php foreach ($allArticles as $a):
    $slug = slugify((string) ($a['slug'] ?? ''));
    $isPanel = isset($panelSlugs[$slug]);
    $fieldSlug = ha_item_field_slug($a);
?>
<tr<?= ha_is_published($a) ? '' : ' class="admin-row--draft"' ?>>
<td><a href="<?= e(url('article', ['slug' => $slug])) ?>"><?= e($a['title'] ?? '') ?></a></td>
<td><span class="badge badge--soft"><?= e($fieldSlug !== '' ? category_label($fieldSlug, (string) ($a['category'] ?? '')) : (string) ($a['category'] ?? '')) ?></span></td>
<td class="muted-sm"><?= e($a['author'] ?? '—') ?></td>
<td class="muted-sm"><?= e($a['date_fa'] ?? $a['date'] ?? '') ?></td>
<td><?= admin_status_badge($a) ?></td>
<td><?= admin_source_badge($isPanel) ?></td>
<td class="actions">
<a class="btn btn--ghost btn--sm" href="<?= e(url('admin_article_edit', ['slug' => $slug])) ?>"><?= ha_icon('edit', 14) ?> ویرایش</a>
<?= admin_status_toggle('article', $slug, $a) ?>
<?php if ($isPanel): ?>
<form method="post" action="<?= e(url('admin_article_delete')) ?>" class="inline-form" data-confirm="این مورد برای همیشه حذف شود؟"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($slug) ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= ha_icon('trash', 14) ?> حذف</button></form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
<?php if ($allArticles === []): ?><tr><td colspan="7" class="admin-empty">مقاله‌ای نیست؛ اولین مقاله را بسازید.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
