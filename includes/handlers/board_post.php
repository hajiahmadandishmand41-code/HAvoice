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

$imagePath = '';
if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $kindMap = ha_upload_kinds()['image'] ?? ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $up = ha_upload_store('image', $kindMap, 'image');
    if (!$up['ok']) { flash('error', $up['error'] ?? 'خطا در آپلود تصویر.'); redirect($backUrl.'#new-post'); }
    $imagePath = $up['path'] ?? '';
}

if ($body === '' && $imagePath === '' && $mediaUrl === '') {
    flash('error','حداقل یکی از متن، تصویر یا رسانه را اضافه کنید.');
    redirect($backUrl.'#new-post');
}

if ($body === '' && ($imagePath !== '' || $mediaUrl !== '')) {
    if ($imagePath !== '' && ha_safe_file_url($imagePath) === '') {
        flash('error','تصویر نامعتبر.'); redirect($backUrl.'#new-post');
    }
    if ($mediaUrl !== '' && (strlen($mediaUrl) > 500 || ha_safe_media_url($mediaUrl) === '')) {
        flash('error','لینک رسانه نامعتبر یا غیرمجاز است.'); redirect($backUrl.'#new-post');
    }
    if (db_ready() && db_table_exists('ha_board_posts')) {
        $id = db_board_post_add($userId, '', $imagePath, $mediaUrl, $mediaType, $ip);
        $res = $id ? ['ok'=>true,'id'=>$id] : ['ok'=>false,'error'=>'db'];
    } else {
        $file = ha_board_posts_file(); $data = [];
        if (is_file($file)) { $d=json_decode((string)@file_get_contents($file),true); if (is_array($d)) $data=$d; }
        $max=0; foreach ($data as $r) $max=max($max,(int)($r['id']??0));
        $id=$max+1; $now=date('c');
        $data[]=['id'=>$id,'user_id'=>$userId,'body'=>'','image'=>$imagePath,'media_url'=>$mediaUrl,'media_type'=>$mediaType,'status'=>'approved','likes_count'=>0,'reactions_count'=>0,'comments_count'=>0,'views_count'=>0,'ip'=>$ip,'created_at'=>$now,'updated_at'=>$now];
        $tmp=$file.'.tmp-'.bin2hex(random_bytes(4));
        $ok=@file_put_contents($tmp,json_encode($data,JSON_UNESCAPED_UNICODE),LOCK_EX)!==false && @rename($tmp,$file);
        if (!$ok && is_file($tmp)) @unlink($tmp);
        $res=$ok?['ok'=>true,'id'=>$id]:['ok'=>false,'error'=>'storage'];
    }
} else {
    $res=ha_board_post_add($userId,$body,$imagePath,$mediaUrl,$mediaType,$ip);
}

if (!$res['ok']) {
    $msg='خطا در ثبت پست.';
    if (($res['error']??'')==='validation') $msg=implode(' ',(array)($res['messages']??[]));
    flash('error',$msg); redirect($backUrl.'#new-post');
}

flash('success','پست شما منتشر شد.');
redirect(url('board',['post'=>(int)($res['id']??0)]));