<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$messages = admin_load('messages');
$csvMessages = [];
$csvFile = storage_dir('messages') . '/messages.csv';
if (is_file($csvFile)) { $fp = fopen($csvFile, 'r'); if ($fp) { $header = fgetcsv($fp); while (($row = fgetcsv($fp)) !== false) { $csvMessages[] = $row; } fclose($fp); } }
$allMessages = array_merge(array_map(function($row) { return ['time'=>$row[0]??'','subject'=>$row[1]??'','name'=>$row[2]??'','email'=>$row[3]??'','message'=>$row[4]??'','ip'=>$row[5]??'','source'=>'csv']; }, $csvMessages), array_map(function($m) { $m['source']='panel'; return $m; }, $messages));
?>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>تاریخ</th><th>نام</th><th>ایمیل</th><th>موضوع</th><th>منبع</th><th>عملیات</th></tr></thead><tbody>
<?php foreach (array_reverse($allMessages) as $i => $msg): ?>
<tr><td class="muted-sm"><?= e($msg['time'] ?? '') ?></td><td><?= e($msg['name'] ?? '') ?></td><td dir="ltr"><?= e($msg['email'] ?? '') ?></td>
<td><?= e($msg['subject'] ?? '') ?></td><td><span class="admin-badge admin-badge--info"><?= e($msg['source'] ?? '') ?></span></td>
<td class="actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('admin_message_view', ['slug' => $i])) ?>">مشاهده</a>
<?php if (($msg['source'] ?? '') === 'panel'): ?><form method="post" action="<?= e(url('admin_message_delete')) ?>" style="display:inline" onsubmit="return confirm('حذف شود؟')"><?= csrf_field() ?><input type="hidden" name="index" value="<?= $i ?>"><button class="btn btn--ghost btn--sm" type="submit" style="color:var(--danger)">حذف</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if ($allMessages === []): ?><tr><td colspan="6" style="text-align:center" class="muted-sm">هنوز پیامی دریافت نشده.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/message_view.php << 'EOF'
<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$idx = (int)param('slug');
$messages = admin_load('messages');
$csvMessages = [];
$csvFile = storage_dir('messages') . '/messages.csv';
if (is_file($csvFile)) { $fp = fopen($csvFile, 'r'); if ($fp) { $header = fgetcsv($fp); while (($row = fgetcsv($fp)) !== false) { $csvMessages[] = $row; } fclose($fp); } }
$allMessages = array_merge(array_map(function($row) { return ['time'=>$row[0]??'','subject'=>$row[1]??'','name'=>$row[2]??'','email'=>$row[3]??'','message'=>$row[4]??'','ip'=>$row[5]??'','source'=>'csv']; }, $csvMessages), array_map(function($m) { $m['source']='panel'; return $m; }, $messages));
$msg = $allMessages[$idx] ?? null;
if (!$msg) { echo '<p>پیام پیدا نشد.</p>'; require HA_ROOT . '/pages/admin/_layout_end.php'; exit; }
?>
<div class="msg-detail">
<p><strong>نام:</strong> <?= e($msg['name'] ?? '') ?></p>
<p><strong>ایمیل:</strong> <a href="mailto:<?= e($msg['email'] ?? '') ?>"><?= e($msg['email'] ?? '') ?></a></p>
<p><strong>موضوع:</strong> <?= e($msg['subject'] ?? '') ?></p>
<p><strong>تاریخ:</strong> <?= e($msg['time'] ?? '') ?></p>
<p><strong>IP:</strong> <span dir="ltr"><?= e($msg['ip'] ?? '') ?></span></p>
<div class="msg-body-text"><?= e($msg['message'] ?? '') ?></div>
</div>
<a class="btn btn--ghost" href="<?= e(url('admin_messages')) ?>">بازگشت به فهرست</a>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/message_delete.php << 'EOF'
<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_messages'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_messages')); }
$idx = (int)($_POST['index'] ?? -1);
$messages = admin_load('messages');
if ($idx >= 0 && $idx < count($messages)) { array_splice($messages, $idx, 1); admin_store('messages', $messages); }
flash('success','پیام حذف شد.'); redirect(url('admin_messages'));
