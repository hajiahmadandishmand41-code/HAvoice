<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_content_comments'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_content_comments')); }
auth_require_admin();
$cid=(int)($_POST['comment_id']??0);
$status=(string)($_POST['status']??'approved');
if ($cid<=0) { flash('error','نامعتبر.'); redirect(url('admin_content_comments')); }
if (db_ready() && db_table_exists('ha_content_comments')) {
    $ok=db_query('UPDATE ha_content_comments SET status=?, updated_at=NOW() WHERE id=?', [$status,$cid])!==false;
} else {
    // file fallback: find in interactions/comments/*.json
    $dir=storage_dir('interactions/comments');
    $ok=false;
    foreach (ha_dir_files($dir,'json') as $file) {
        $raw=@file_get_contents($file);
        $data=json_decode((string)$raw,true);
        if (!is_array($data)) continue;
        $changed=false;
        foreach ($data as $i=>$r) if ((int)($r['id']??0)===$cid) { $data[$i]['status']=$status; $data[$i]['updated_at']=date('c'); $changed=true; break; }
        if ($changed) {
            $tmp=$file.'.tmp-'.bin2hex(random_bytes(4));
            $ok=@file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX)!==false && @rename($tmp,$file);
            if ($ok) break;
        }
    }
}
flash($ok?'success':'error', $ok?'وضعیت تغییر کرد.':'خطا.');
redirect(url('admin_content_comments'));
