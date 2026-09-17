<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_board_comments'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_board_comments')); }
auth_require_admin();
$cid = (int)($_POST['comment_id'] ?? 0);
$status = (string)($_POST['status'] ?? 'approved');
if ($cid<=0) { flash('error','نظر نامعتبر.'); redirect(url('admin_board_comments')); }
if (db_ready() && db_table_exists('ha_board_comments')) {
    $ok = db_board_comment_set_status($cid,$status);
} else {
    // file fallback
    $file = storage_dir('board').'/comments.json';
    $ok=false;
    if (is_file($file)) {
        $raw=@file_get_contents($file);
        $data=json_decode((string)$raw,true);
        if (is_array($data)) {
            foreach ($data as $i=>$r) if ((int)($r['id']??0)===$cid) {
                $data[$i]['status']=$status;
                $data[$i]['updated_at']=date('c');
                $ok=true;
                break;
            }
            if ($ok) {
                $tmp=$file.'.tmp-'.bin2hex(random_bytes(4));
                @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
                @rename($tmp,$file);
            }
        }
    }
}
flash($ok?'success':'error', $ok?'وضعیت تغییر کرد.':'خطا.');
redirect(url('admin_board_comments'));
