<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_content_reactions'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_content_reactions')); }
auth_require_admin();
$type=(string)($_POST['content_type']??'');
$slug=(string)($_POST['content_slug']??'');
$userId=(string)($_POST['user_id']??'');
if ($type===''||$slug===''||$userId==='') { flash('error','نامعتبر.'); redirect(url('admin_content_reactions')); }
if (db_ready() && db_table_exists('ha_content_reactions')) {
    $ok=db_query('DELETE FROM ha_content_reactions WHERE content_type=? AND content_slug=? AND user_id=?', [$type,$slug,$userId])!==false;
} else {
    $dir=storage_dir('interactions/reactions');
    $ok=false;
    foreach (ha_dir_files($dir,'json') as $file) {
        $raw=@file_get_contents($file);
        $data=json_decode((string)$raw,true);
        if (!is_array($data)) continue;
        $new=[]; $found=false;
        foreach ($data as $r) {
            if (($r['content_type']??'')===$type && ($r['content_slug']??'')===$slug && ($r['user_id']??'')===$userId) { $found=true; continue; }
            $new[]=$r;
        }
        if ($found) {
            $tmp=$file.'.tmp-'.bin2hex(random_bytes(4));
            $ok=@file_put_contents($tmp, json_encode($new, JSON_UNESCAPED_UNICODE), LOCK_EX)!==false && @rename($tmp,$file);
            if ($ok) break;
        }
    }
}
flash($ok?'success':'error', $ok?'حذف شد.':'خطا.');
redirect(url('admin_content_reactions',['type'=>$type,'slug'=>$slug]));
