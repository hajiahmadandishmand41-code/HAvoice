<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
require HA_ROOT . '/pages/admin/_helpers.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_tips'));
if (!csrf_verify()) { flash('error', 'نشست تمام شده.'); redirect(url('admin_tips')); }

$id = slugify((string) ($_POST['id'] ?? ''));
$text = trim((string) ($_POST['text'] ?? ''));
if ($id === '' || $text === '') { flash('error', 'شناسه و متن الزامی.'); redirect(url('admin_tip_edit')); }

$item = [
    'id'       => $id,
    'text'     => $text,
    'try'      => trim((string) ($_POST['try'] ?? '')),
    'category' => trim((string) ($_POST['category'] ?? 'عمومی')),
    'status'   => admin_post_status(),
];

$items = admin_load('tips');
$orig  = slugify((string) ($_POST['original_id'] ?? ''));
$found = false;
foreach ($items as $i => $t) {
    $tId = slugify((string) ($t['id'] ?? ''));
    if (($orig !== '' && $tId === $orig) || $tId === $id) { $items[$i] = $item; $found = true; break; }
}
if (!$found) $items[] = $item;

if (!admin_store('tips', $items)) { flash('error', 'ذخیره‌سازی نکته ناموفق بود؛ storage قابل نوشتن نیست.'); redirect(url('admin_tips')); }
flash('success', $item['status'] === 'published' ? 'نکته ذخیره و منتشر شد.' : 'نکته به‌عنوان پیش‌نویس ذخیره شد (مخفی).');
redirect(url('admin_tips'));
