<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
$next = ha_safe_next((string)($_POST['next'] ?? ha_current_request_url()));
$backUrl = $next !== '' ? $next : url('board');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect($backUrl);
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect($backUrl); }
if (trim((string)($_POST['website'] ?? '')) !== '') redirect($backUrl);
if (!auth_is_logged_in()) { flash('error','برای نظر ثبت‌نام کنید.'); redirect(url('register',['next'=>$next])); }

$limit = ha_rate_limit_acquire('board_comment', (string)($_SERVER['REMOTE_ADDR'] ?? ''), 30, 60, 3);
if (!$limit['ok']) { flash('error','نظرات زیاد است. '.fa_num($limit['retry']).' ثانیه صبر.'); redirect($backUrl); }

$user = auth_current_user();
$userId = $user ? (string)($user['id'] ?? '') : '';
$postId = (int)($_POST['post_id'] ?? 0);
$parentIdRaw = trim((string)($_POST['parent_id'] ?? '0'));
$parentId = $parentIdRaw !== '' && $parentIdRaw !== '0' ? (int)$parentIdRaw : null;
$body = trim((string)($_POST['body'] ?? ''));
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');

if ($postId <=0) { flash('error','پست نامعتبر.'); redirect($backUrl); }
if ($body === '') { flash('error','متن نظر خالی است.'); redirect($backUrl); }

$post = ha_board_post_find($postId);
if (!$post) { flash('error','پست یافت نشد.'); redirect($backUrl); }

$res = ha_board_comment_add($postId,$userId,$parentId,$body,$ip);
if (!$res['ok']) {
    $msg='خطا در ثبت نظر.';
    if (($res['error'] ?? '')==='parent') $msg='پاسخی که به آن جواب می‌دهید یافت نشد.';
    if (($res['error'] ?? '')==='links') $msg='لینک زیاد مجاز نیست.';
    flash('error',$msg);
    redirect($backUrl.'#post-'.$postId);
}

flash('success',$parentId ? 'پاسخ ثبت شد.' : 'نظر ثبت شد.');
redirect($backUrl.'#post-'.$postId);
