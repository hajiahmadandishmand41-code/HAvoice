<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_board'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_board')); }
auth_require_admin();
$postId = (int)($_POST['post_id'] ?? 0);
$userId = (string)($_POST['user_id'] ?? '');
if ($postId<=0 || $userId==='') { flash('error','نامعتبر.'); redirect(url('admin_board')); }
if (db_ready() && db_table_exists('ha_board_reactions')) {
    $existing = db_board_reaction_get($postId,$userId);
    $ok = db_board_reaction_delete($postId,$userId);
    if ($ok && $existing) {
        db_board_post_inc('reactions_count',$postId,-1);
        if ((string)($existing['reaction_type']??'')==='like') db_board_post_inc('likes_count',$postId,-1);
    }
} else {
    $file = storage_dir('board').'/reactions.json';
    $ok=false;
    $old=null;
    if (is_file($file)) {
        $raw=@file_get_contents($file);
        $data=json_decode((string)$raw,true);
        if (is_array($data)) {
            $new=[];
            foreach ($data as $r) {
                if ((int)($r['post_id']??0)===$postId && ($r['user_id']??'')===$userId) { $old=$r; continue; }
                $new[]=$r;
            }
            $tmp=$file.'.tmp-'.bin2hex(random_bytes(4));
            $ok=@file_put_contents($tmp, json_encode($new, JSON_UNESCAPED_UNICODE), LOCK_EX)!==false && @rename($tmp,$file);
        }
    }
    if ($ok && $old) {
        $pf = storage_dir('board').'/posts.json';
        if (is_file($pf)) {
            $raw=@file_get_contents($pf);
            $posts=json_decode((string)$raw,true);
            if (is_array($posts)) {
                foreach ($posts as $i=>$p) if ((int)($p['id']??0)===$postId) {
                    $posts[$i]['reactions_count']=max(0,(int)($p['reactions_count']??0)-1);
                    if ((string)($old['reaction_type']??'')==='like') $posts[$i]['likes_count']=max(0,(int)($p['likes_count']??0)-1);
                    break;
                }
                $tmp=$pf.'.tmp-'.bin2hex(random_bytes(4));
                @file_put_contents($tmp, json_encode($posts, JSON_UNESCAPED_UNICODE), LOCK_EX);
                @rename($tmp,$pf);
            }
        }
    }
}
flash($ok?'success':'error', $ok?'واکنش حذف شد.':'خطا.');
redirect(url('admin_board_reactions',['post_id'=>$postId]));
