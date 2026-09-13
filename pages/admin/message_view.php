<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$idx = (int)param('slug');
$messages = admin_load('messages');
$csvMessages = [];
$csvFile = storage_dir('messages') . '/messages.csv';
if (is_file($csvFile)) { $fp = fopen($csvFile, 'r'); if ($fp) { $header = fgetcsv($fp); while (($row = fgetcsv($fp)) !== false) { $csvMessages[] = $row; } fclose($fp); } }
$allMessages = array_merge(
    array_map(function($row) { return ['time'=>$row[0]??'','subject'=>$row[1]??'','name'=>$row[2]??'','email'=>$row[3]??'','message'=>$row[4]??'','ip'=>$row[5]??'','source'=>'csv']; }, $csvMessages),
    array_map(function($m) { $m['source']='panel'; return $m; }, $messages)
);
$msg = $allMessages[$idx] ?? null;
if (!$msg) { echo '<p class="muted-sm">پیام پیدا نشد.</p>'; require HA_ROOT . '/pages/admin/_layout_end.php'; exit; }
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
