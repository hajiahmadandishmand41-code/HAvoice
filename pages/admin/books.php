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
<?php if (!$isData): ?><form method="post" action="<?= e(url('admin_book_delete')) ?>" style="display:inline" onsubmit="return confirm('حذف شود؟')"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($b['slug'] ?? '') ?>"><button class="btn btn--ghost btn--sm" type="submit" style="color:var(--danger)">حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/book_edit.php << 'EOF'
<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$slug = param('slug'); $book = null;
if ($slug !== '') { foreach (admin_load('books') as $b) { if (($b['slug'] ?? '') === $slug) { $book = $b; break; } } if (!$book) foreach (books() as $b) { if (($b['slug'] ?? '') === $slug) { $book = $b; break; } } }
$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<form class="admin-form" method="post" action="<?= e(url('admin_book_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_slug" value="<?= e($slug) ?>">
<div class="field"><label>عنوان *</label><input class="input" type="text" name="title" value="<?= e($book['title'] ?? '') ?>" required></div>
<div class="field"><label>نامک (slug) *</label><input class="input" type="text" name="slug" value="<?= e($book['slug'] ?? '') ?>" required dir="ltr"></div>
<div class="field"><label>نویسنده</label><input class="input" type="text" name="author" value="<?= e($book['author'] ?? '') ?>"></div>
<div class="field"><label>دسته</label><input class="input" type="text" name="category" value="<?= e($book['category'] ?? '') ?>"></div>
<div class="field"><label>چکیده</label><textarea class="input" name="excerpt" rows="3"><?= e($book['excerpt'] ?? '') ?></textarea></div>
<div class="field"><label>خلاصه و برداشت</label><textarea class="input" name="summary" rows="6"><?= e($book['summary'] ?? '') ?></textarea></div>
<div class="field"><label>تاریخ فارسی</label><input class="input" type="text" name="date_fa" value="<?= e($book['date_fa'] ?? '') ?>"></div>
<div class="field"><label>زمان مطالعه (دقیقه)</label><input class="input" type="number" name="minutes" value="<?= e((string)($book['minutes'] ?? 10)) ?>"></div>
<div class="field"><label>برچسب‌ها (کاما)</label><input class="input" type="text" name="tags" value="<?= e(implode(', ', $book['tags'] ?? [])) ?>"></div>
<div class="admin-form-actions"><button class="btn btn--primary" type="submit">ذخیره</button><a class="btn btn--ghost" href="<?= e(url('admin_books')) ?>">بازگشت</a></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/book_save.php << 'EOF'
<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_books'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_books')); }
$slug = slugify((string)($_POST['slug'] ?? '')); $title = trim((string)($_POST['title'] ?? ''));
if ($title === '' || $slug === '') { flash('error','عنوان و نامک الزامی.'); redirect(url('admin_book_edit')); }
$items = admin_load('books');
$item = ['slug'=>$slug,'title'=>$title,'author'=>trim((string)($_POST['author']??'')),'category'=>trim((string)($_POST['category']??'')),'excerpt'=>trim((string)($_POST['excerpt']??'')),'summary'=>trim((string)($_POST['summary']??'')),'date_fa'=>trim((string)($_POST['date_fa']??'')),'minutes'=>max(1,(int)($_POST['minutes']??10)),'tags'=>array_map('trim',array_filter(explode(',',(string)($_POST['tags']??''))))];
$orig = (string)($_POST['original_slug'] ?? ''); $found = false;
foreach ($items as $i => $b) { if (($b['slug'] ?? '') === $orig || ($b['slug'] ?? '') === $slug) { $items[$i] = $item; $found = true; break; } }
if (!$found) $items[] = $item;
admin_store('books', $items); flash('success','کتاب ذخیره شد.'); redirect(url('admin_books'));
