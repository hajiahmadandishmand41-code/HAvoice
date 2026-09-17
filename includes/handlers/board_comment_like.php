<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
$next = ha_safe_next((string)($_POST['next'] ?? ha_current_request_url()));
$backUrl = $next !== '' ? $next : url('board');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect($backUrl);
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect($backUrl); }
if (!auth_is_logged_in()) { flash('error','برای پسند ثبت‌نام کنید.'); redirect(url('register',['next'=>$next])); }

$limit = ha_rate_limit_acquire('board_comment_like', (string)($_SERVER['REMOTE_ADDR'] ?? ''), 100, 60, 1);
if (!$limit['ok']) { flash('error','درخواست زیاد.'); redirect($backUrl); }

$user = auth_current_user();
$userId = $user ? (string)($user['id'] ?? '') : '';
$commentId = (int)($_POST['comment_id'] ?? 0);
$postId = (int)($_POST['post_id'] ?? 0);

if ($commentId <=0) { flash('error','نظر نامعتبر.'); redirect($backUrl); }

$res = ha_board_comment_like_toggle($userId,$commentId);
if (!$res['ok']) flash('error','خطا در ثبت پسند.');
else flash('success', ($res['liked']??false)?'پسندیدید.':'حذف شد.');

$anchor = $postId>0 ? '#post-'.$postId : '#comments';
redirect($backUrl.$anchor);
