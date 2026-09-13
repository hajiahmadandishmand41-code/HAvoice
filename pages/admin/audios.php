<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$adminMedia = admin_load('media');
$adminAudios = array_values(array_filter($adminMedia, fn($m) => ($m['type'] ?? '') === 'audio'));
$allAudios = array_merge(audios(), $adminAudios);
$srcCount = count(audios());
?>
<a class="btn btn--primary" href="<?= e(url('admin_audio_edit')) ?>"><?= ha_icon('headphones',16) ?> صوت جدید</a>
<div class="admin-table-wrap" style="margin-top:1rem"><table class="admin-table"><thead><tr><th>عنوان</th><th>دسته</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach ($allAudios as $i => $a): $isData = $i < $srcCount; ?>
<tr><td><?= e($a['title'] ?? '') ?></td><td><span class="badge badge--soft"><?= e($a['category'] ?? '') ?></span></td>
<td><span class="admin-badge <?= $isData ? 'admin-badge--info' : 'admin-badge--success' ?>"><?= $isData ? 'فایل' : 'پنل' ?></span></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_audio_edit', ['slug' => $a['slug'] ?? ''])) ?>">ویرایش</a>
<?php if (!$isData): ?><form method="post" action="<?= e(url('admin_audio_delete')) ?>" style="display:inline" onsubmit="return confirm('حذف شود؟')"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($a['slug'] ?? '') ?>"><button class="btn btn--ghost btn--sm" type="submit" style="color:var(--danger)">حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/audio_edit.php << 'EOF'
<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$slug = param('slug'); $item = null;
if ($slug !== '') { foreach (admin_load('media') as $m) { if (($m['slug'] ?? '') === $slug) { $item = $m; break; } } if (!$item) foreach (media_items() as $m) { if (($m['slug'] ?? '') === $slug) { $item = $m; break; } } }
$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<form class="admin-form" method="post" action="<?= e(url('admin_audio_save')) ?>">
<?= csrf_field() ?><input type="hidden" name="original_slug" value="<?= e($slug) ?>">
<div class="field"><label>عنوان *</label><input class="input" type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required></div>
<div class="field"><label>نامک (slug) *</label><input class="input" type="text" name="slug" value="<?= e($item['slug'] ?? '') ?>" required dir="ltr"></div>
<div class="field"><label>دسته</label><input class="input" type="text" name="category" value="<?= e($item['category'] ?? '') ?>"></div>
<div class="field"><label>چکیده</label><textarea class="input" name="excerpt" rows="3"><?= e($item['excerpt'] ?? '') ?></textarea></div>
<div class="field"><label>URL فایل صوتی</label><input class="input" type="url" name="url" value="<?= e($item['url'] ?? '') ?>" dir="ltr"></div>
<div class="field"><label>مدت (ثانیه)</label><input class="input" type="number" name="seconds" value="<?= e((string)($item['seconds'] ?? 0)) ?>"></div>
<div class="field"><label>تاریخ فارسی</label><input class="input" type="text" name="date_fa" value="<?= e($item['date_fa'] ?? '') ?>"></div>
<input type="hidden" name="type" value="audio">
<div class="admin-form-actions"><button class="btn btn--primary" type="submit">ذخیره</button><a class="btn btn--ghost" href="<?= e(url('admin_audios')) ?>">بازگشت</a></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/audio_save.php << 'EOF'
<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_audios'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_audios')); }
$slug = slugify((string)($_POST['slug'] ?? '')); $title = trim((string)($_POST['title'] ?? ''));
if ($title === '' || $slug === '') { flash('error','عنوان و نامک الزامی.'); redirect(url('admin_audio_edit')); }
$items = admin_load('media');
$item = ['type'=>'audio','slug'=>$slug,'title'=>$title,'category'=>trim((string)($_POST['category']??'')),'excerpt'=>trim((string)($_POST['excerpt']??'')),'url'=>trim((string)($_POST['url']??'')),'seconds'=>max(0,(int)($_POST['seconds']??0)),'date_fa'=>trim((string)($_POST['date_fa']??''))];
$orig = (string)($_POST['original_slug'] ?? ''); $found = false;
foreach ($items as $i => $m) { if (($m['slug'] ?? '') === $orig || ($m['slug'] ?? '') === $slug) { $items[$i] = $item; $found = true; break; } }
if (!$found) $items[] = $item;
admin_store('media', $items); flash('success','صوت ذخیره شد.'); redirect(url('admin_audios'));
