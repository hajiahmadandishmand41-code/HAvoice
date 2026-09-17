<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
$next = ha_safe_next((string)($_POST['next'] ?? ha_current_request_url()));
$backUrl = $next !== '' ? $next : url('board');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect($backUrl);
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect($backUrl); }
if (trim((string)($_POST['website'] ?? '')) !== '') redirect($backUrl);
if (!auth_is_logged_in()) { flash('error','برای واکنش ثبت‌نام کنید.'); redirect(url('register',['next'=>$next])); }

$limit = ha_rate_limit_acquire('board_reaction', (string)($_SERVER['REMOTE_ADDR'] ?? ''), 60, 60, 1);
if (!$limit['ok']) { flash('error','درخواست زیاد.'); redirect($backUrl); }

$user = auth_current_user();
$userId = $user ? (string)($user['id'] ?? '') : '';
$postId = (int)($_POST['post_id'] ?? 0);
$rType = strtolower(trim((string)($_POST['reaction_type'] ?? '')));

if ($postId <=0) { flash('error','پست نامعتبر.'); redirect($backUrl); }

$post = ha_board_post_find($postId);
if (!$post) { flash('error','پست یافت نشد.'); redirect($backUrl); }

if ($rType === 'remove') {
    ha_board_reaction_delete($postId,$userId);
    flash('success','واکنش حذف شد.');
    redirect($backUrl.'#post-'.$postId);
}

if (!in_array($rType, ['like','love','laugh','wow','sad'], true)) { flash('error','نوع واکنش نامعتبر.'); redirect($backUrl); }

$existing = ha_board_reaction_get($postId,$userId);
if ($existing && (string)($existing['reaction_type']??'') === $rType) {
    ha_board_reaction_delete($postId,$userId);
    flash('success','واکنش حذف شد.');
} else {
    ha_board_reaction_set($postId,$userId,$rType);
    flash('success','واکنش ثبت شد.');
}
redirect($backUrl.'#post-'.$postId);
