<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');

$next = ha_safe_next((string)($_POST['next'] ?? ha_current_request_url()));
$backUrl = $next !== '' ? $next : url('board');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect($backUrl);
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect($backUrl); }
if (trim((string)($_POST['website'] ?? '')) !== '') redirect($backUrl);
if (!auth_is_logged_in()) { flash('error','برای انتشار پست ثبت‌نام کنید.'); redirect(url('register',['next'=>$next])); }

$limit = ha_rate_limit_acquire('board_post', (string)($_SERVER['REMOTE_ADDR'] ?? ''), 20, 60, 5);
if (!$limit['ok']) { flash('error','تعداد پست زیاد است. '.fa_num($limit['retry']).' ثانیه صبر کنید.'); redirect($backUrl); }

$user = auth_current_user();
$userId = $user ? (string)($user['id'] ?? '') : '';
$body = trim((string)($_POST['body'] ?? ''));
$mediaUrl = trim((string)($_POST['media_url'] ?? ''));
$mediaType = trim((string)($_POST['media_type'] ?? '')) ?: null;
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');

// handle image upload (optional)
$imagePath = '';
if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $kindMap = ha_upload_kinds()['image'] ?? ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $up = ha_upload_store('image', $kindMap, 'image');
    if (!$up['ok']) {
        flash('error', $up['error'] ?? 'خطا در آپلود تصویر.');
        redirect($backUrl.'#new-post');
    }
    $imagePath = $up['path'] ?? '';
}

if ($body === '' && $imagePath === '' && $mediaUrl === '') {
    flash('error','متن یا تصویر یا رسانه الزامی است.');
    redirect($backUrl.'#new-post');
}

$res = ha_board_post_add($userId, $body, $imagePath, $mediaUrl, $mediaType, $ip);
if (!$res['ok']) {
    $msg = 'خطا در ثبت پست.';
    if (($res['error'] ?? '') === 'validation') $msg = implode(' ', (array)($res['messages'] ?? []));
    flash('error',$msg);
    redirect($backUrl.'#new-post');
}

flash('success','پست شما منتشر شد.');
redirect($backUrl.'#post-'.(int)($res['id'] ?? 0));
