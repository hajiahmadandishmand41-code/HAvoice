<?php
/**
 * HAvoice — تابلوی مجازی (Virtual Board) v6
 * - Post متنی + تصویر/رسانه اختیاری
 * - Like/Reaction, Comment, Reply, Like Comment
 * - Feed مدرن با الگوریتم Dynamic (new + engagement + decay)
 * - پروفایل ساده
 * - Fallback JSON وقتی DB نیست
 * - امنیت CSRF, XSS, Validation, Spam
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  Storage helpers (fallback)                                        */
/* ------------------------------------------------------------------ */

function ha_board_storage_dir(string $sub = ''): string
{
    $base = storage_dir('board');
    if ($sub !== '') $base .= '/' . trim($sub, '/');
    if (!is_dir($base)) @mkdir($base, 0755, true);
    return $base;
}

function ha_board_posts_file(): string
{
    return ha_board_storage_dir() . '/posts.json';
}

function ha_board_reactions_file(): string
{
    return ha_board_storage_dir() . '/reactions.json';
}

function ha_board_comments_file(): string
{
    return ha_board_storage_dir() . '/comments.json';
}

function ha_board_comment_likes_file(): string
{
    return ha_board_storage_dir() . '/comment_likes.json';
}

function ha_board_visits_file(): string
{
    return ha_board_storage_dir() . '/visits.json';
}

/* ------------------------------------------------------------------ */
/*  Validation                                                        */
/* ------------------------------------------------------------------ */

function ha_board_validate_post(string $body, string $image, string $mediaUrl): array
{
    $body = trim($body);
    $errors = [];
    $len = mb_strlen($body, 'UTF-8');
    if ($len < 3) $errors[] = 'متن پست باید حداقل ۳ نویسه باشد.';
    if ($len > 5000) $errors[] = 'متن پست حداکثر ۵۰۰۰ نویسه می‌تواند باشد.';
    $urlCount = preg_match_all('#https?://#i', $body);
    if ($urlCount > 5) $errors[] = 'ارسال لینک زیاد مجاز نیست.';
    if (preg_match('/(.)\1{12,}/u', $body)) $errors[] = 'متن تکراری زیاد است.';
    if ($image !== '' && !ha_safe_file_url($image)) {
        // image path from upload already validated, but check
        if (!preg_match('#^uploads/#', $image) && !preg_match('#^https?://#i', $image)) {
            $errors[] = 'تصویر نامعتبر.';
        }
    }
    if ($mediaUrl !== '') {
        if (strlen($mediaUrl) > 500) $errors[] = 'نشانی رسانه خیلی طولانی است.';
        // allow embed hosts or https
        if (preg_match('#^[a-z][a-z0-9+\-.]*:#i', $mediaUrl) && !preg_match('#^https?://#i', $mediaUrl)) {
            $errors[] = 'نشانی رسانه باید https باشد.';
        }
    }
    return $errors;
}

/* ------------------------------------------------------------------ */
/*  Posts                                                             */
/* ------------------------------------------------------------------ */

function ha_board_post_add(string $userId, string $body, string $image, string $mediaUrl, ?string $mediaType, string $ip): array
{
    $body = trim($body);
    $image = trim($image);
    $mediaUrl = trim($mediaUrl);
    $val = ha_board_validate_post($body, $image, $mediaUrl);
    if ($val !== []) return ['ok'=>false,'error'=>'validation','messages'=>$val];

    if (db_ready() && db_table_exists('ha_board_posts')) {
        $id = db_board_post_add($userId, $body, $image, $mediaUrl, $mediaType, $ip);
        return $id ? ['ok'=>true,'id'=>$id] : ['ok'=>false,'error'=>'db'];
    }
    // file fallback
    $file = ha_board_posts_file();
    $data = [];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $d = json_decode((string)$raw, true);
        if (is_array($d)) $data = $d;
    }
    $max = 0;
    foreach ($data as $r) $max = max($max, (int)($r['id'] ?? 0));
    $id = $max + 1;
    $now = date('c');
    $data[] = [
        'id'=>$id,
        'user_id'=>$userId,
        'body'=>$body,
        'image'=>$image,
        'media_url'=>$mediaUrl,
        'media_type'=>$mediaType,
        'status'=>'approved',
        'likes_count'=>0,
        'reactions_count'=>0,
        'comments_count'=>0,
        'views_count'=>0,
        'ip'=>$ip,
        'created_at'=>$now,
        'updated_at'=>$now,
    ];
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir,0755,true);
    $tmp = $file.'.tmp-'.bin2hex(random_bytes(4));
    $ok = @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX) !== false && @rename($tmp,$file);
    if (!$ok && is_file($tmp)) @unlink($tmp);
    return $ok ? ['ok'=>true,'id'=>$id] : ['ok'=>false,'error'=>'storage'];
}

function ha_board_posts_get(int $limit = 50, int $offset = 0, string $status = 'approved', string $userId = ''): array
{
    if (db_ready() && db_table_exists('ha_board_posts')) {
        if ($userId !== '') {
            $sql = 'SELECT bp.*, u.name, u.email FROM ha_board_posts bp LEFT JOIN ha_users u ON u.id = bp.user_id WHERE bp.user_id = ?';
            $params = [$userId];
            if ($status === 'approved' || $status === 'pending' || $status === 'hidden') {
                $sql .= ' AND bp.status = ?';
                $params[] = $status;
            }
            $sql .= ' ORDER BY bp.created_at DESC, bp.id DESC LIMIT '.max(1,min(200,$limit)).' OFFSET '.max(0,$offset);
            return db_all($sql, $params);
        }
        return db_board_posts_all($status, $limit, $offset);
    }
    $file = ha_board_posts_file();
    if (!is_file($file)) return [];
    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) return [];
    if ($status !== '') $data = array_values(array_filter($data, fn($r)=>($r['status']??'approved')===$status));
    if ($userId !== '') $data = array_values(array_filter($data, fn($r)=>($r['user_id']??'')===$userId));
    // sort newest first
    usort($data, fn($a,$b)=> strcmp((string)($b['created_at']??''), (string)($a['created_at']??'')));
    // enrich user name
    $users = [];
    if (function_exists('auth_load_users')) {
        foreach (auth_load_users() as $u) $users[(string)($u['id']??'')] = $u;
    }
    foreach ($data as $i=>$r) {
        $uid = (string)($r['user_id']??'');
        if (isset($users[$uid])) {
            $data[$i]['name'] = $users[$uid]['name'] ?? 'کاربر';
            $data[$i]['email'] = $users[$uid]['email'] ?? '';
        }
    }
    return array_slice($data, $offset, $limit);
}

function ha_board_post_find(int $id): ?array
{
    if ($id <=0) return null;
    if (db_ready() && db_table_exists('ha_board_posts')) {
        return db_board_post_find($id);
    }
    $file = ha_board_posts_file();
    if (!is_file($file)) return null;
    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) return null;
    foreach ($data as $r) if ((int)($r['id']??0)===$id) {
        // enrich
        if (function_exists('auth_load_users')) {
            foreach (auth_load_users() as $u) if ((string)($u['id']??'')=== (string)($r['user_id']??'')) {
                $r['name']=$u['name']??'کاربر';
                $r['email']=$u['email']??'';
                break;
            }
        }
        return $r;
    }
    return null;
}

function ha_board_post_delete(int $id): bool
{
    if (db_ready() && db_table_exists('ha_board_posts')) {
        return db_board_post_delete($id);
    }
    $file = ha_board_posts_file();
    if (!is_file($file)) return true;
    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) return true;
    $new = array_values(array_filter($data, fn($r)=>(int)($r['id']??0)!==$id));
    $tmp = $file.'.tmp-'.bin2hex(random_bytes(4));
    $ok = @file_put_contents($tmp, json_encode($new, JSON_UNESCAPED_UNICODE), LOCK_EX)!==false && @rename($tmp,$file);
    if (!$ok && is_file($tmp)) @unlink($tmp);
    return $ok;
}

function ha_board_post_set_status(int $id, string $status): bool
{
    if (db_ready() && db_table_exists('ha_board_posts')) {
        return db_board_post_set_status($id,$status);
    }
    $file = ha_board_posts_file();
    if (!is_file($file)) return false;
    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) return false;
    $changed=false;
    foreach ($data as $i=>$r) if ((int)($r['id']??0)===$id) {
        $data[$i]['status']=$status;
        $data[$i]['updated_at']=date('c');
        $changed=true;
        break;
    }
    if (!$changed) return false;
    $tmp = $file.'.tmp-'.bin2hex(random_bytes(4));
    $ok = @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX)!==false && @rename($tmp,$file);
    if (!$ok && is_file($tmp)) @unlink($tmp);
    return $ok;
}

function ha_board_post_inc_view(int $id): void
{
    if (db_ready() && db_table_exists('ha_board_posts')) {
        db_board_post_inc('views_count',$id,1);
        return;
    }
    $file = ha_board_posts_file();
    if (!is_file($file)) return;
    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) return;
    foreach ($data as $i=>$r) if ((int)($r['id']??0)===$id) {
        $data[$i]['views_count'] = (int)($r['views_count']??0)+1;
        $data[$i]['updated_at']=date('c');
        break;
    }
    $tmp = $file.'.tmp-'.bin2hex(random_bytes(4));
    @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    @rename($tmp,$file);
    if (is_file($tmp)) @unlink($tmp);
}

/* ------------------------------------------------------------------ */
/*  Feed algorithm — Dynamic, no deletion                             */
/* ------------------------------------------------------------------ */

function ha_board_feed_score(array $post): float
{
    // score = (reactions*2 + comments*3 + views*0.5 + likes) / (hours+2)^1.5 + recency bonus
    $reactions = (int)($post['reactions_count'] ?? 0);
    $comments = (int)($post['comments_count'] ?? 0);
    $views = (int)($post['views_count'] ?? 0);
    $likes = (int)($post['likes_count'] ?? 0);
    $created = strtotime((string)($post['created_at'] ?? ''));
    $hours = $created ? max(0, (time() - $created) / 3600) : 0;
    $eng = $likes + $reactions*2 + $comments*3 + $views*0.2;
    $score = $eng / pow($hours + 2, 1.35);
    // recency boost: newer gets + 100/(hours+1)
    $score += 50 / ($hours + 1);
    // approved posts only get score, pending/hidden zero
    if (($post['status'] ?? 'approved') !== 'approved') $score = 0;
    return $score;
}

function ha_board_feed_sorted(array $posts, string $sort = 'dynamic'): array
{
    if ($sort === 'newest') {
        usort($posts, fn($a,$b)=> strcmp((string)($b['created_at']??''), (string)($a['created_at']??'')));
        return $posts;
    }
    if ($sort === 'popular') {
        usort($posts, fn($a,$b)=> ((int)($b['reactions_count']??0)+(int)($b['comments_count']??0)) <=> ((int)($a['reactions_count']??0)+(int)($a['comments_count']??0)));
        return $posts;
    }
    // dynamic
    foreach ($posts as $i=>$p) $posts[$i]['_score'] = ha_board_feed_score($p);
    usort($posts, fn($a,$b)=> ($b['_score']??0) <=> ($a['_score']??0));
    return $posts;
}

/* ------------------------------------------------------------------ */
/*  Reactions                                                         */
/* ------------------------------------------------------------------ */

function ha_board_reaction_get(int $postId, string $userId): ?array
{
    if (db_ready() && db_table_exists('ha_board_reactions')) {
        return db_board_reaction_get($postId,$userId);
    }
    $file = ha_board_reactions_file();
    if (!is_file($file)) return null;
    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) return null;
    foreach ($data as $r) if ((int)($r['post_id']??0)===$postId && ($r['user_id']??'')===$userId) return $r;
    return null;
}

function ha_board_reaction_set(int $postId, string $userId, string $type): bool
{
    if (!in_array($type, ['like','love','laugh','wow','sad'], true)) $type='like';
    if (db_ready() && db_table_exists('ha_board_reactions')) {
        $existing = db_board_reaction_get($postId,$userId);
        $isNew = $existing === null;
        $ok = db_board_reaction_set($postId,$userId,$type);
        if ($ok) {
            if ($isNew) {
                db_board_post_inc('reactions_count',$postId,1);
                if ($type==='like') db_board_post_inc('likes_count',$postId,1);
            } else {
                // if changed from like to other or vice versa adjust likes_count
                $old = (string)($existing['reaction_type']??'');
                if ($old==='like' && $type!=='like') db_board_post_inc('likes_count',$postId,-1);
                if ($old!=='like' && $type==='like') db_board_post_inc('likes_count',$postId,1);
            }
        }
        return $ok;
    }
    $file = ha_board_reactions_file();
    $data = [];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $d = json_decode((string)$raw, true);
        if (is_array($d)) $data = $d;
    }
    $found=false;
    $oldType='';
    foreach ($data as $i=>$r) if ((int)($r['post_id']??0)===$postId && ($r['user_id']??'')===$userId) {
        $oldType = (string)($r['reaction_type']??'');
        $data[$i]['reaction_type']=$type;
        $data[$i]['updated_at']=date('c');
        $found=true;
        break;
    }
    if (!$found) {
        $data[]=['post_id'=>$postId,'user_id'=>$userId,'reaction_type'=>$type,'created_at'=>date('c'),'updated_at'=>date('c')];
    }
    $tmp = $file.'.tmp-'.bin2hex(random_bytes(4));
    $ok = @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX)!==false && @rename($tmp,$file);
    if (!$ok && is_file($tmp)) @unlink($tmp);
    // update counts in posts file
    if ($ok) {
        $pf = ha_board_posts_file();
        if (is_file($pf)) {
            $raw = @file_get_contents($pf);
            $posts = json_decode((string)$raw, true);
            if (is_array($posts)) {
                foreach ($posts as $i=>$p) if ((int)($p['id']??0)===$postId) {
                    if (!$found) {
                        $posts[$i]['reactions_count'] = (int)($p['reactions_count']??0)+1;
                        if ($type==='like') $posts[$i]['likes_count'] = (int)($p['likes_count']??0)+1;
                    } else {
                        if ($oldType==='like' && $type!=='like') $posts[$i]['likes_count'] = max(0,(int)($p['likes_count']??0)-1);
                        if ($oldType!=='like' && $type==='like') $posts[$i]['likes_count'] = (int)($p['likes_count']??0)+1;
                    }
                    $posts[$i]['updated_at']=date('c');
                    break;
                }
                $tmp2 = $pf.'.tmp-'.bin2hex(random_bytes(4));
                @file_put_contents($tmp2, json_encode($posts, JSON_UNESCAPED_UNICODE), LOCK_EX);
                @rename($tmp2,$pf);
                if (is_file($tmp2)) @unlink($tmp2);
            }
        }
    }
    return $ok;
}

function ha_board_reaction_delete(int $postId, string $userId): bool
{
    if (db_ready() && db_table_exists('ha_board_reactions')) {
        $existing = db_board_reaction_get($postId,$userId);
        $ok = db_board_reaction_delete($postId,$userId);
        if ($ok && $existing) {
            db_board_post_inc('reactions_count',$postId,-1);
            if ((string)($existing['reaction_type']??'')==='like') db_board_post_inc('likes_count',$postId,-1);
        }
        return $ok;
    }
    $file = ha_board_reactions_file();
    if (!is_file($file)) return true;
    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) return true;
    $old=null;
    $new=[];
    foreach ($data as $r) {
        if ((int)($r['post_id']??0)===$postId && ($r['user_id']??'')===$userId) { $old=$r; continue; }
        $new[]=$r;
    }
    $tmp = $file.'.tmp-'.bin2hex(random_bytes(4));
    $ok = @file_put_contents($tmp, json_encode($new, JSON_UNESCAPED_UNICODE), LOCK_EX)!==false && @rename($tmp,$file);
    if (!$ok && is_file($tmp)) @unlink($tmp);
    if ($ok && $old) {
        $pf = ha_board_posts_file();
        if (is_file($pf)) {
            $raw = @file_get_contents($pf);
            $posts = json_decode((string)$raw, true);
            if (is_array($posts)) {
                foreach ($posts as $i=>$p) if ((int)($p['id']??0)===$postId) {
                    $posts[$i]['reactions_count']=max(0,(int)($p['reactions_count']??0)-1);
                    if ((string)($old['reaction_type']??'')==='like') $posts[$i]['likes_count']=max(0,(int)($p['likes_count']??0)-1);
                    $posts[$i]['updated_at']=date('c');
                    break;
                }
                $tmp2=$pf.'.tmp-'.bin2hex(random_bytes(4));
                @file_put_contents($tmp2, json_encode($posts, JSON_UNESCAPED_UNICODE), LOCK_EX);
                @rename($tmp2,$pf);
                if (is_file($tmp2)) @unlink($tmp2);
            }
        }
    }
    return $ok;
}

function ha_board_reaction_counts(int $postId): array
{
    if (db_ready() && db_table_exists('ha_board_reactions')) {
        return db_board_reaction_counts($postId);
    }
    $file = ha_board_reactions_file();
    $out=['total'=>0,'like'=>0,'love'=>0,'laugh'=>0,'wow'=>0,'sad'=>0];
    if (!is_file($file)) return $out;
    $raw=@file_get_contents($file);
    $data=json_decode((string)$raw,true);
    if (!is_array($data)) return $out;
    foreach ($data as $r) if ((int)($r['post_id']??0)===$postId) {
        $t=(string)($r['reaction_type']??'');
        if (isset($out[$t])) $out[$t]++;
        $out['total']++;
    }
    return $out;
}

function ha_board_reactions_for_user(string $userId, array $postIds): array
{
    if (db_ready() && db_table_exists('ha_board_reactions')) {
        return db_board_reactions_for_user($userId,$postIds);
    }
    $file = ha_board_reactions_file();
    if (!is_file($file)) return [];
    $raw=@file_get_contents($file);
    $data=json_decode((string)$raw,true);
    if (!is_array($data)) return [];
    $out=[];
    foreach ($postIds as $pid) {
        foreach ($data as $r) if ((int)($r['post_id']??0)===(int)$pid && ($r['user_id']??'')===$userId) {
            $out[(int)$pid]=(string)($r['reaction_type']??'');
            break;
        }
    }
    return $out;
}

/* ------------------------------------------------------------------ */
/*  Comments                                                          */
/* ------------------------------------------------------------------ */

function ha_board_comment_add(int $postId, string $userId, ?int $parentId, string $body, string $ip): array
{
    $body=trim($body);
    if (mb_strlen($body,'UTF-8')<2) return ['ok'=>false,'error'=>'short'];
    if (mb_strlen($body,'UTF-8')>2000) return ['ok'=>false,'error'=>'long'];
    if (preg_match_all('#https?://#i',$body) > 2) return ['ok'=>false,'error'=>'links'];

    if (db_ready() && db_table_exists('ha_board_comments')) {
        if ($parentId) {
            $p = db_board_comment_find($parentId);
            if (!$p || (int)($p['post_id']??0)!==$postId) return ['ok'=>false,'error'=>'parent'];
        } else $parentId=null;
        $id = db_board_comment_add($postId,$userId,$parentId,$body,$ip);
        if ($id) {
            db_board_post_inc('comments_count',$postId,1);
            return ['ok'=>true,'id'=>$id];
        }
        return ['ok'=>false,'error'=>'db'];
    }
    // file fallback
    $file = ha_board_comments_file();
    $data=[];
    if (is_file($file)) {
        $raw=@file_get_contents($file);
        $d=json_decode((string)$raw,true);
        if (is_array($d)) $data=$d;
    }
    if ($parentId) {
        $found=false;
        foreach ($data as $r) if ((int)($r['id']??0)===$parentId && (int)($r['post_id']??0)===$postId) {$found=true;break;}
        if (!$found) return ['ok'=>false,'error'=>'parent'];
    } else $parentId=null;
    $max=0;
    foreach ($data as $r) $max=max($max,(int)($r['id']??0));
    $id=$max+1;
    $now=date('c');
    $data[]=['id'=>$id,'user_id'=>$userId,'post_id'=>$postId,'parent_id'=>$parentId,'body'=>$body,'status'=>'approved','likes_count'=>0,'ip'=>$ip,'created_at'=>$now,'updated_at'=>$now];
    $tmp=$file.'.tmp-'.bin2hex(random_bytes(4));
    $ok=@file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX)!==false && @rename($tmp,$file);
    if (!$ok && is_file($tmp)) @unlink($tmp);
    if ($ok) {
        $pf=ha_board_posts_file();
        if (is_file($pf)) {
            $raw=@file_get_contents($pf);
            $posts=json_decode((string)$raw,true);
            if (is_array($posts)) {
                foreach ($posts as $i=>$p) if ((int)($p['id']??0)===$postId) {
                    $posts[$i]['comments_count']=(int)($p['comments_count']??0)+1;
                    $posts[$i]['updated_at']=date('c');
                    break;
                }
                $tmp2=$pf.'.tmp-'.bin2hex(random_bytes(4));
                @file_put_contents($tmp2, json_encode($posts, JSON_UNESCAPED_UNICODE), LOCK_EX);
                @rename($tmp2,$pf);
                if (is_file($tmp2)) @unlink($tmp2);
            }
        }
    }
    return $ok ? ['ok'=>true,'id'=>$id] : ['ok'=>false,'error'=>'storage'];
}

function ha_board_comments_get(int $postId, string $status='approved'): array
{
    if (db_ready() && db_table_exists('ha_board_comments')) {
        return db_board_comments_all($postId,$status);
    }
    $file = ha_board_comments_file();
    if (!is_file($file)) return [];
    $raw=@file_get_contents($file);
    $data=json_decode((string)$raw,true);
    if (!is_array($data)) return [];
    $data=array_values(array_filter($data, fn($r)=>(int)($r['post_id']??0)===$postId && ($r['status']??'approved')===$status));
    usort($data, fn($a,$b)=> strcmp((string)($a['created_at']??''), (string)($b['created_at']??'')));
    $users=[];
    if (function_exists('auth_load_users')) foreach (auth_load_users() as $u) $users[(string)($u['id']??'')]=$u;
    foreach ($data as $i=>$r) {
        $uid=(string)($r['user_id']??'');
        if (isset($users[$uid])) {
            $data[$i]['name']=$users[$uid]['name']??'کاربر';
            $data[$i]['email']=$users[$uid]['email']??'';
        }
    }
    return $data;
}

function ha_board_comment_counts(int $postId): array
{
    if (db_ready() && db_table_exists('ha_board_comments')) {
        return db_board_comment_counts($postId);
    }
    $rows=ha_board_comments_get($postId,'approved');
    $total=count($rows);
    $replies=0;
    foreach ($rows as $r) if (!empty($r['parent_id'])) $replies++;
    return ['total'=>$total,'comments'=>$total-$replies,'replies'=>$replies];
}

function ha_board_comment_like_toggle(string $userId, int $commentId): array
{
    if (db_ready() && db_table_exists('ha_board_comment_likes')) {
        $exists=db_board_comment_like_exists($userId,$commentId);
        if ($exists) {
            $ok=db_board_comment_like_remove($userId,$commentId);
            return ['ok'=>$ok,'liked'=>false];
        } else {
            $ok=db_board_comment_like_add($userId,$commentId);
            return ['ok'=>$ok,'liked'=>true];
        }
    }
    $file=ha_board_comment_likes_file();
    $data=[];
    if (is_file($file)) {
        $raw=@file_get_contents($file);
        $d=json_decode((string)$raw,true);
        if (is_array($d)) $data=$d;
    }
    $key=$userId.'_'.$commentId;
    $liked=false;
    if (isset($data[$key])) { unset($data[$key]); $liked=false; }
    else { $data[$key]=['user_id'=>$userId,'comment_id'=>$commentId,'created_at'=>date('c')]; $liked=true; }
    $tmp=$file.'.tmp-'.bin2hex(random_bytes(4));
    $ok=@file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX)!==false && @rename($tmp,$file);
    if (!$ok && is_file($tmp)) @unlink($tmp);
    // update likes_count in comments file
    if ($ok) {
        $cf=ha_board_comments_file();
        if (is_file($cf)) {
            $raw=@file_get_contents($cf);
            $arr=json_decode((string)$raw,true);
            if (is_array($arr)) {
                foreach ($arr as $i=>$c) if ((int)($c['id']??0)===$commentId) {
                    $arr[$i]['likes_count']=max(0,(int)($c['likes_count']??0)+($liked?1:-1));
                    $arr[$i]['updated_at']=date('c');
                    break;
                }
                $tmp2=$cf.'.tmp-'.bin2hex(random_bytes(4));
                @file_put_contents($tmp2, json_encode($arr, JSON_UNESCAPED_UNICODE), LOCK_EX);
                @rename($tmp2,$cf);
                if (is_file($tmp2)) @unlink($tmp2);
            }
        }
    }
    return ['ok'=>$ok,'liked'=>$liked];
}

function ha_board_comment_likes_for_user(string $userId, array $commentIds): array
{
    if (db_ready() && db_table_exists('ha_board_comment_likes')) {
        return db_board_comment_likes_for_user($userId,$commentIds);
    }
    $file=ha_board_comment_likes_file();
    if (!is_file($file)) return [];
    $raw=@file_get_contents($file);
    $data=json_decode((string)$raw,true);
    if (!is_array($data)) return [];
    $out=[];
    foreach ($commentIds as $cid) {
        $key=$userId.'_'.$cid;
        if (isset($data[$key])) $out[(int)$cid]=true;
    }
    return $out;
}

/* ------------------------------------------------------------------ */
/*  Visits / Views                                                    */
/* ------------------------------------------------------------------ */

function ha_track_visit(string $route, string $slug = ''): void
{
    // rate limit: 1 visit per IP per 30 sec for same route
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ip === '') return;
    $limit = ha_rate_limit_acquire('visit_'.$route, $ip, 100, 60, 0);
    if (!$limit['ok']) return; // too many

    $user = function_exists('auth_current_user') ? auth_current_user() : null;
    $userId = $user ? (string)($user['id'] ?? '') : null;
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');

    // simple bot filter
    if (preg_match('/bot|crawl|spider|slurp|mediapartners|baidu|yandex|sogou|exabot|facebot|ia_archiver/i', $ua)) {
        return;
    }

    if (db_ready() && db_table_exists('ha_site_visits')) {
        db_site_visit_add($userId, $ip, $route, $slug, $ua);
        if ($route !== '' && $slug !== '') {
            db_content_view_inc($route, $slug);
        }
        return;
    }
    // file fallback — append
    $file = ha_board_visits_file();
    $data = [];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $d = json_decode((string)$raw, true);
        if (is_array($d)) $data = $d;
        // keep only last 5000 to avoid bloat
        if (count($data) > 5000) $data = array_slice($data, -4000);
    }
    $data[] = ['user_id'=>$userId,'ip'=>$ip,'route'=>$route,'slug'=>$slug,'ua'=>substr($ua,0,200),'created_at'=>date('c')];
    $tmp = $file.'.tmp-'.bin2hex(random_bytes(4));
    @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    @rename($tmp,$file);
    if (is_file($tmp)) @unlink($tmp);
}

function ha_site_stats(): array
{
    if (db_ready() && db_table_exists('ha_site_visits')) {
        $total = db_site_visits_count('all');
        $today = db_site_visits_count('today');
        $week = db_site_visits_count('week');
        $month = db_site_visits_count('month');
        $contentViews = db_content_views_count();
        $board = db_board_stats();
        return [
            'visits_total'=>$total,
            'visits_today'=>$today,
            'visits_week'=>$week,
            'visits_month'=>$month,
            'content_views'=>$contentViews,
            'board_posts'=>$board['posts'] ?? 0,
            'board_reactions'=>$board['board_reactions'] ?? 0,
            'board_comments'=>$board['board_comments'] ?? 0,
            'board_likes'=>$board['board_likes'] ?? 0,
            'content_reactions'=>$board['content_reactions'] ?? 0,
            'content_comments'=>$board['content_comments'] ?? 0,
        ];
    }
    // file fallback
    $file = ha_board_visits_file();
    $visits = 0;
    if (is_file($file)) {
        $raw=@file_get_contents($file);
        $data=json_decode((string)$raw,true);
        if (is_array($data)) $visits=count($data);
    }
    $posts = ha_board_posts_get(1000,0,'approved','');
    $postsCount = count($posts);
    // count reactions/comments from files
    $reactions=0;
    $rf=ha_board_reactions_file();
    if (is_file($rf)) {
        $raw=@file_get_contents($rf);
        $d=json_decode((string)$raw,true);
        if (is_array($d)) $reactions=count($d);
    }
    $comments=0;
    $cf=ha_board_comments_file();
    if (is_file($cf)) {
        $raw=@file_get_contents($cf);
        $d=json_decode((string)$raw,true);
        if (is_array($d)) $comments=count($d);
    }
    return [
        'visits_total'=>$visits,
        'visits_today'=>$visits,
        'visits_week'=>$visits,
        'visits_month'=>$visits,
        'content_views'=>0,
        'board_posts'=>$postsCount,
        'board_reactions'=>$reactions,
        'board_comments'=>$comments,
        'board_likes'=>0,
        'content_reactions'=>0,
        'content_comments'=>0,
    ];
}

/* ------------------------------------------------------------------ */
/*  User profile                                                      */
/* ------------------------------------------------------------------ */

function ha_user_profile(string $userId): ?array
{
    if ($userId==='') return null;
    $user = null;
    if (function_exists('auth_load_users')) {
        foreach (auth_load_users() as $u) if ((string)($u['id']??'')===$userId) {$user=$u;break;}
    }
    if (!$user && db_ready() && db_table_exists('ha_users')) {
        $user = db_user_find_id($userId);
    }
    if (!$user) return null;
    $posts = ha_board_posts_get(50,0,'approved',$userId);
    $stats = [
        'posts'=>count($posts),
    ];
    return [
        'id'=>(string)($user['id']??$userId),
        'name'=>(string)($user['name']??'کاربر'),
        'email'=>(string)($user['email']??''),
        'role'=>(string)($user['role']??'user'),
        'created_at'=>(string)($user['created_at']??''),
        'posts'=>$posts,
        'stats'=>$stats,
    ];
}

/* ------------------------------------------------------------------ */
/*  Render helpers                                                    */
/* ------------------------------------------------------------------ */

function ha_board_render_post(array $post, string $myUserId = '', array $myReactions = [], array $likedComments = []): string
{
    $id = (int)($post['id'] ?? 0);
    $name = (string)($post['name'] ?? 'کاربر');
    $body = (string)($post['body'] ?? '');
    $image = (string)($post['image'] ?? '');
    $mediaUrl = (string)($post['media_url'] ?? '');
    $mediaType = (string)($post['media_type'] ?? '');
    $created = (string)($post['created_at'] ?? '');
    $dateFa = $created !== '' ? ha_fa_date($created) : '';
    $reactions = ha_board_reaction_counts($id);
    $myReaction = $myReactions[$id] ?? '';
    $comments = ha_board_comments_get($id,'approved');
    $commentCounts = ha_board_comment_counts($id);

    // build tree for comments
    $byParent = [];
    foreach ($comments as $c) {
        $pid = $c['parent_id'] ?? null;
        $key = $pid ? (int)$pid : 0;
        $byParent[$key][] = $c;
    }

    $reactionTypes = ha_reaction_types();

    ob_start();
    ?>
    <article class="ha-board-post" data-board-post="<?= $id ?>" id="post-<?= $id ?>">
        <header class="ha-board-post__head">
            <span class="ha-avatar"><?= e(auth_initial($name)) ?></span>
            <div class="ha-board-post__meta">
                <strong class="ha-board-post__name"><?= e($name) ?></strong>
                <span class="ha-board-post__date"><?= e($dateFa) ?></span>
            </div>
            <span class="ha-board-post__score" title="امتیاز الگوریتم پویا"><?= fa_num(number_format(ha_board_feed_score($post),1)) ?></span>
        </header>
        <div class="ha-board-post__body">
            <p class="ha-board-post__text"><?= nl2br(e($body)) ?></p>
            <?php if ($image !== ''): ?>
                <?php $safeImg = ha_public_file_url($image); if ($safeImg !== ''): ?>
                <figure class="ha-board-post__media"><img src="<?= e($safeImg) ?>" alt="" loading="lazy" decoding="async"></figure>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($mediaUrl !== ''): ?>
                <?php $safeMedia = ha_public_media_url($mediaUrl); $embed = ha_embed_url($mediaUrl); ?>
                <?php if ($embed !== ''): ?>
                    <div class="video-embed"><iframe src="<?= e($embed) ?>" title="رسانه" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>
                <?php elseif ($safeMedia !== ''): ?>
                    <?php if ($mediaType === 'video' || str_ends_with(strtolower($safeMedia), '.mp4')): ?>
                        <video controls preload="metadata" src="<?= e($safeMedia) ?>" style="width:100%;border-radius:12px"></video>
                    <?php elseif ($mediaType === 'audio' || str_ends_with(strtolower($safeMedia), '.mp3')): ?>
                        <audio controls preload="none" src="<?= e($safeMedia) ?>" style="width:100%"></audio>
                    <?php else: ?>
                        <a href="<?= e($safeMedia) ?>" target="_blank" rel="noopener" class="btn btn--ghost btn--xs">مشاهده رسانه</a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <footer class="ha-board-post__foot">
            <div class="ha-board-post__stats">
                <span>❤️ <?= fa_num($reactions['total']) ?></span>
                <span>💬 <?= fa_num($commentCounts['total']) ?></span>
                <span>👁️ <?= fa_num((int)($post['views_count'] ?? 0)) ?></span>
            </div>
            <div class="ha-reactions__bar ha-reactions__bar--compact">
                <?php foreach ($reactionTypes as $key=>$meta): 
                    $cnt = (int)($reactions[$key] ?? 0);
                    $active = $myReaction === $key;
                ?>
                <form method="post" action="<?= e(url('board_reaction')) ?>" class="ha-reaction-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="post_id" value="<?= $id ?>">
                    <input type="hidden" name="reaction_type" value="<?= e($key) ?>">
                    <input type="hidden" name="next" value="<?= e(ha_current_request_url()) ?>">
                    <div class="honeypot" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                    <button type="submit" class="ha-reaction ha-reaction--sm <?= $active ? 'is-active' : '' ?>" data-reaction="<?= e($key) ?>">
                        <span><?= $meta['emoji'] ?></span><span><?= $cnt>0?fa_num($cnt):'' ?></span>
                    </button>
                </form>
                <?php endforeach; ?>
                <?php if ($myReaction !== ''): ?>
                <form method="post" action="<?= e(url('board_reaction')) ?>" class="ha-reaction-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="post_id" value="<?= $id ?>">
                    <input type="hidden" name="reaction_type" value="remove">
                    <input type="hidden" name="next" value="<?= e(ha_current_request_url()) ?>">
                    <button type="submit" class="ha-reaction ha-reaction--remove ha-reaction--sm">✕</button>
                </form>
                <?php endif; ?>
            </div>
        </footer>

        <!-- Comments for this post — مرتب: عنوان + واکنش‌ها + لیست + فرم -->
        <div class="ha-board-comments" id="comments-post-<?= $id ?>">
            <div class="ha-comments__title" style="margin:0 0 .75rem;display:flex;align-items:center;gap:.45rem;font-size:var(--fs-base);font-weight:var(--fw-extra);color:var(--text)">
                <?= ha_icon('chat', 16) ?> گفتگو
                <span class="badge badge--soft" style="font-variant-numeric:tabular-nums"><?= fa_num($commentCounts['total']) ?> نظر</span>
                <?php if ((int)$commentCounts['replies']>0): ?><span class="muted-sm" style="font-size:var(--fs-xs)">↩️ <?= fa_num((int)$commentCounts['replies']) ?> پاسخ</span><?php endif; ?>
            </div>
            <?php
            $render = function(array $list, int $depth=0) use (&$render, $byParent, $likedComments, $id) {
                foreach ($list as $c) {
                    $cid = (int)($c['id']??0);
                    $cName = (string)($c['name']??'کاربر');
                    $cBody = (string)($c['body']??'');
                    $cDate = (string)($c['created_at']??'');
                    $cDateFa = $cDate!==''?ha_fa_date($cDate):'';
                    $cLikes = (int)($c['likes_count']??0);
                    $isLiked = isset($likedComments[$cid]);
                    $replies = $byParent[$cid] ?? [];
                    ?>
                    <div class="ha-comment <?= $depth>0?'ha-comment--reply':'' ?>" data-comment-id="<?= $cid ?>">
                        <div class="ha-comment__main">
                            <div class="ha-comment__avatar"><?= e(auth_initial($cName)) ?></div>
                            <div class="ha-comment__body">
                                <div class="ha-comment__meta"><strong class="ha-comment__name"><?= e($cName) ?></strong><span class="ha-comment__date"><?= e($cDateFa) ?></span><?php if($depth>0): ?><span class="badge badge--soft">پاسخ</span><?php endif; ?></div>
                                <p class="ha-comment__text"><?= nl2br(e($cBody)) ?></p>
                                <div class="ha-comment__actions">
                                    <form method="post" action="<?= e(url('board_comment_like')) ?>" class="ha-inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="comment_id" value="<?= $cid ?>">
                                        <input type="hidden" name="post_id" value="<?= $id ?>">
                                        <input type="hidden" name="next" value="<?= e(ha_current_request_url()) ?>">
                                        <button type="submit" class="ha-action-btn <?= $isLiked?'is-liked':'' ?>" aria-pressed="<?= $isLiked?'true':'false' ?>"><?= ha_icon($isLiked?'heart-filled':'heart',14) ?> <?= $cLikes>0?fa_num($cLikes):'پسند' ?></button>
                                    </form>
                                    <button type="button" class="ha-action-btn" data-ha-reply="<?= $cid ?>"><?= ha_icon('reply',14) ?> پاسخ</button>
                                </div>
                                <form method="post" action="<?= e(url('board_comment')) ?>" class="ha-comment-form ha-comment-form--reply" data-ha-reply-form="<?= $cid ?>" hidden>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="post_id" value="<?= $id ?>">
                                    <input type="hidden" name="parent_id" value="<?= $cid ?>">
                                    <input type="hidden" name="next" value="<?= e(ha_current_request_url()) ?>">
                                    <div class="honeypot" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                                    <textarea name="body" class="input" required minlength="2" maxlength="2000" rows="2" placeholder="پاسخ به <?= e($cName) ?>..."></textarea>
                                    <div class="btn-row" style="margin-top:.5rem">
                                        <button class="btn btn--primary btn--xs" type="submit">ثبت پاسخ</button>
                                        <button class="btn btn--ghost btn--xs" type="button" data-ha-cancel-reply>انصراف</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php if ($replies!==[]): ?>
                            <div class="ha-comment__replies"><?php $render($replies,$depth+1); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php
                }
            };
            $roots = $byParent[0] ?? [];
            if ($roots === []) {
                echo '<p class="muted-sm" style="color:var(--text-mute);font-size:var(--fs-sm);padding:.4rem 0">هنوز نظری ثبت نشده — اولین نفر باشید.</p>';
            } else {
                $render($roots,0);
            }
            ?>
            <?php $currentUser = auth_current_user(); if ($currentUser): ?>
            <form method="post" action="<?= e(url('board_comment')) ?>" class="ha-comment-form" style="margin-top:.75rem">
                <?= csrf_field() ?>
                <input type="hidden" name="post_id" value="<?= $id ?>">
                <input type="hidden" name="parent_id" value="0">
                <input type="hidden" name="next" value="<?= e(ha_current_request_url()) ?>">
                <div class="honeypot" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                <div class="field" style="margin:0">
                    <label for="ha-board-comment-<?= $id ?>" class="sr-only">متن نظر</label>
                    <textarea id="ha-board-comment-<?= $id ?>" name="body" class="input" required minlength="2" maxlength="2000" rows="3" placeholder="نظر خود را بنویسید..."></textarea>
                    <p class="field__help">حداقل ۲ نویسه، حداکثر ۲۰۰۰ — از لینکِ زیاد پرهیز کنید.</p>
                </div>
                <button class="btn btn--primary btn--sm" type="submit" style="margin-top:.4rem"><?= ha_icon('chat',14) ?> ثبت نظر</button>
            </form>
            <?php else: ?>
            <div class="ha-guest-cta" style="margin:.6rem 0 0">
                <div class="ha-guest-cta__icon"><?= ha_icon('sparkle', 20) ?></div>
                <div class="ha-guest-cta__body">
                    <h4>برای گفتگو وارد شوید</h4>
                    <p class="muted-sm">ثبت‌نام کمتر از ۳۰ ثانیه است و پس از ورود به همین پست برمی‌گردید.</p>
                    <div class="btn-row">
                        <a class="btn btn--primary btn--sm" href="<?= e(url('register',['next'=>ha_current_request_url()])) ?>">ثبت‌نام</a>
                        <a class="btn btn--ghost btn--sm" href="<?= e(url('login',['next'=>ha_current_request_url()])) ?>">ورود</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </article>
    <?php
    return (string)ob_get_clean();
}
