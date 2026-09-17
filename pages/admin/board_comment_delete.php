<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_board_comments'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_board_comments')); }
auth_require_admin();
$cid = (int)($_POST['comment_id'] ?? 0);
if ($cid<=0) { flash('error','نظر نامعتبر.'); redirect(url('admin_board_comments')); }
if (db_ready() && db_table_exists('ha_board_comments')) {
    $c = db_board_comment_find($cid);
    $postId = $c ? (int)($c['post_id']??0) : 0;
    $ok = db_board_comment_delete($cid);
    if ($ok && $postId>0) db_board_post_inc('comments_count',$postId,-1);
} else {
    $file = storage_dir('board').'/comments.json';
    $ok=false;
    $postId=0;
    if (is_file($file)) {
        $raw=@file_get_contents($file);
        $data=json_decode((string)$raw,true);
        if (is_array($data)) {
            $new=[];
            foreach ($data as $r) {
                if ((int)($r['id']??0)===$cid) { $postId=(int)($r['post_id']??0); continue; }
                $new[]=$r;
            }
            $tmp=$file.'.tmp-'.bin2hex(random_bytes(4));
            $ok=@file_put_contents($tmp, json_encode($new, JSON_UNESCAPED_UNICODE), LOCK_EX)!==false && @rename($tmp,$file);
        }
    }
    if ($ok && $postId>0) {
        $pf = storage_dir('board').'/posts.json';
        if (is_file($pf)) {
            $raw=@file_get_contents($pf);
            $posts=json_decode((string)$raw,true);
            if (is_array($posts)) {
                foreach ($posts as $i=>$p) if ((int)($p['id']??0)===$postId) {
                    $posts[$i]['comments_count']=max(0,(int)($p['comments_count']??0)-1);
                    break;
                }
                $tmp=$pf.'.tmp-'.bin2hex(random_bytes(4));
                @file_put_contents($tmp, json_encode($posts, JSON_UNESCAPED_UNICODE), LOCK_EX);
                @rename($tmp,$pf);
            }
        }
    }
}
flash($ok?'success':'error', $ok?'نظر حذف شد.':'خطا.');
redirect(url('admin_board_comments'));
