<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$adminCats = admin_load('categories');
$allCats = array_merge(categories(), $adminCats);
$srcCount = count(categories());
?>
<a class="btn btn--primary" href="<?= e(url('admin_category_edit')) ?>"><?= ha_icon('compass',16) ?> حوزه جدید</a>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>عنوان</th><th>نامک</th><th>آیکون</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allCats as $i => $c): $isData = $i < $srcCount; ?>
<tr><td><?= e($c['title'] ?? '') ?></td><td dir="ltr"><?= e($c['slug'] ?? '') ?></td><td><?= e($c['icon'] ?? '') ?></td>
<td><span class="admin-badge <?= $isData ? 'admin-badge--info' : 'admin-badge--success' ?>"><?= $isData ? 'فایل' : 'پنل' ?></span></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_category_edit', ['slug' => $c['slug'] ?? ''])) ?>">ویرایش</a>
<?php if (!$isData): ?><form method="post" action="<?= e(url('admin_category_delete')) ?>" style="display:inline" onsubmit="return confirm('حذف شود؟')"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($c['slug'] ?? '') ?>"><button class="btn btn--ghost btn--sm" type="submit" style="color:var(--danger)">حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/category_edit.php << 'EOF'
<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$slug = param('slug'); $item = null;
if ($slug !== '') { foreach (admin_load('categories') as $c) { if (($c['slug'] ?? '') === $slug) { $item = $c; break; } } if (!$item) foreach (categories() as $c) { if (($c['slug'] ?? '') === $slug) { $item = $c; break; } } }
$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<form class="admin-form" method="post" action="<?= e(url('admin_category_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_slug" value="<?= e($slug) ?>">
<div class="field"><label>عنوان *</label><input class="input" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required></div>
<div class="field"><label>نامک (slug) *</label><input class="input" type="text" name="slug" value="<?= e($item['slug'] ?? '') ?>" required dir="ltr"></div>
<div class="field"><label>عنوان کوتاه</label><input class="input" type="text" name="short" value="<?= e($item['short'] ?? '') ?>"></div>
<div class="field"><label>توضیحات</label><textarea class="input" name="description" rows="3"><?= e($item['description'] ?? '') ?></textarea></div>
<div class="field"><label>آیکون</label><input class="input" type="text" name="icon" value="<?= e($item['icon'] ?? 'compass') ?>" dir="ltr"></div>
<div class="field"><label>رنگ اصلی</label><input class="input" type="color" name="color" value="<?= e($item['color'] ?? '#0d5c4d') ?>"></div>
<div class="field"><label>رنگ فرعی</label><input class="input" type="color" name="accent" value="<?= e($item['accent'] ?? '#2ec4a6') ?>"></div>
<div class="admin-form-actions"><button class="btn btn--primary" type="submit">ذخیره</button><a class="btn btn--ghost" href="<?= e(url('admin_categories')) ?>">بازگشت</a></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/category_save.php << 'EOF'
<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_categories'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_categories')); }
$slug = slugify((string)($_POST['slug'] ?? '')); $title = trim((string)($_POST['title'] ?? ''));
if ($title === '' || $slug === '') { flash('error','عنوان و نامک الزامی.'); redirect(url('admin_category_edit')); }
$items = admin_load('categories');
$item = ['slug'=>$slug,'title'=>$title,'short'=>trim((string)($_POST['short']??'')),'description'=>trim((string)($_POST['description']??'')),'icon'=>trim((string)($_POST['icon']??'compass')),'color'=>trim((string)($_POST['color']??'#0d5c4d')),'accent'=>trim((string)($_POST['accent']??'#2ec4a6'))];
$orig = (string)($_POST['original_slug'] ?? ''); $found = false;
foreach ($items as $i => $c) { if (($c['slug'] ?? '') === $orig || ($c['slug'] ?? '') === $slug) { $items[$i] = $item; $found = true; break; } }
if (!$found) $items[] = $item;
admin_store('categories', $items); flash('success','حوزه ذخیره شد.'); redirect(url('admin_categories'));
