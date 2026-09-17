<?php
/**
 * HAvoice — سیستم تعامل کامل (Like, Reaction, Comment, Reply, Like Comment)
 * - Reaction متنوع ❤️ 👍 😂 😮 😢 هر کاربر یک Reaction per محتوا
 * - Comment + Reply چندسطحی + Like برای Comment/Reply
 * - ذخیره و نمایش تعداد
 * - امنیت CSRF, XSS, Validation, ضد Spam
 * - Fallback JSON وقتی DB نیست
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  انواع مجاز و نگاشت ایموجی                                          */
/* ------------------------------------------------------------------ */

function ha_reaction_types(): array
{
    return [
        'like'  => ['emoji' => '👍', 'label' => 'پسندیدم', 'color' => '#1A3A7C'],
        'love'  => ['emoji' => '❤️', 'label' => 'عاشقش شدم', 'color' => '#E0245E'],
        'laugh' => ['emoji' => '😂', 'label' => 'خنده‌دار', 'color' => '#FFAD1F'],
        'wow'   => ['emoji' => '😮', 'label' => 'شگفت‌انگیز', 'color' => '#1D9BF0'],
        'sad'   => ['emoji' => '😢', 'label' => 'ناراحت‌کننده', 'color' => '#6B7B8F'],
    ];
}

function ha_content_types(): array
{
    return ['article','video','audio','book','research','course','lesson','exercise','tip','category'];
}

function ha_valid_content_type(string $t): bool
{
    return in_array($t, ha_content_types(), true);
}

function ha_valid_reaction(string $r): bool
{
    return isset(ha_reaction_types()[$r]);
}

/* ------------------------------------------------------------------ */
/*  Fallback file storage helpers                                      */
/* ------------------------------------------------------------------ */

function ha_interaction_storage_dir(string $sub = ''): string
{
    $base = storage_dir('interactions');
    if ($sub !== '') $base .= '/' . trim($sub, '/');
    if (!is_dir($base)) @mkdir($base, 0755, true);
    return $base;
}

function ha_reaction_file(string $cType, string $cSlug): string
{
    $cType = preg_replace('/[^a-z_]/','',$cType);
    $cSlug = slugify($cSlug);
    return ha_interaction_storage_dir('reactions') . '/' . $cType . '__' . $cSlug . '.json';
}

function ha_comment_file(string $cType, string $cSlug): string
{
    $cType = preg_replace('/[^a-z_]/','',$cType);
    $cSlug = slugify($cSlug);
    return ha_interaction_storage_dir('comments') . '/' . $cType . '__' . $cSlug . '.json';
}

function ha_comment_likes_file(): string
{
    return ha_interaction_storage_dir() . '/comment_likes.json';
}

/* ------------------------------------------------------------------ */
/*  Reactions                                                          */
/* ------------------------------------------------------------------ */

function ha_reaction_get_user(string $userId, string $cType, string $cSlug): ?array
{
    if (!ha_valid_content_type($cType) || $cSlug === '' || $userId === '') return null;
    if (db_ready() && db_table_exists('ha_content_reactions')) {
        $row = db_reaction_get_user($userId, $cType, $cSlug);
        return $row ?: null;
    }
    $file = ha_reaction_file($cType, $cSlug);
    if (!is_file($file)) return null;
    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) return null;
    foreach ($data as $r) {
        if (($r['user_id'] ?? '') === $userId) return $r;
    }
    return null;
}

function ha_reaction_set(string $userId, string $cType, string $cSlug, string $reaction): bool
{
    if (!ha_valid_content_type($cType) || $cSlug === '' || $userId === '' || !ha_valid_reaction($reaction)) return false;
    $cSlug = slugify($cSlug);
    if (db_ready() && db_table_exists('ha_content_reactions')) {
        return db_reaction_set($userId, $cType, $cSlug, $reaction);
    }
    $file = ha_reaction_file($cType, $cSlug);
    $data = [];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $d = json_decode((string)$raw, true);
        if (is_array($d)) $data = $d;
    }
    $now = date('c');
    $found = false;
    foreach ($data as $i => $r) {
        if (($r['user_id'] ?? '') === $userId) {
            $data[$i]['reaction_type'] = $reaction;
            $data[$i]['updated_at'] = $now;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $data[] = ['user_id'=>$userId,'content_type'=>$cType,'content_slug'=>$cSlug,'reaction_type'=>$reaction,'created_at'=>$now,'updated_at'=>$now];
    }
    // atomic save
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $tmp = $file.'.tmp-'.bin2hex(random_bytes(4));
    $ok = @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX) !== false && @rename($tmp, $file);
    if (!$ok && is_file($tmp)) @unlink($tmp);
    return $ok;
}

function ha_reaction_delete(string $userId, string $cType, string $cSlug): bool
{
    if (!ha_valid_content_type($cType) || $cSlug === '' || $userId === '') return false;
    $cSlug = slugify($cSlug);
    if (db_ready() && db_table_exists('ha_content_reactions')) {
        return db_reaction_delete($userId, $cType, $cSlug);
    }
    $file = ha_reaction_file($cType, $cSlug);
    if (!is_file($file)) return true;
    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) return true;
    $new = array_values(array_filter($data, fn($r) => ($r['user_id'] ?? '') !== $userId));
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $tmp = $file.'.tmp-'.bin2hex(random_bytes(4));
    $ok = @file_put_contents($tmp, json_encode($new, JSON_UNESCAPED_UNICODE), LOCK_EX) !== false && @rename($tmp, $file);
    if (!$ok && is_file($tmp)) @unlink($tmp);
    return $ok;
}

function ha_reaction_counts(string $cType, string $cSlug): array
{
    if (!ha_valid_content_type($cType) || $cSlug === '') return ['total'=>0,'like'=>0,'love'=>0,'laugh'=>0,'wow'=>0,'sad'=>0];
    $cSlug = slugify($cSlug);
    if (db_ready() && db_table_exists('ha_content_reactions')) {
        return db_reaction_counts($cType, $cSlug);
    }
    $file = ha_reaction_file($cType, $cSlug);
    $out = ['total'=>0,'like'=>0,'love'=>0,'laugh'=>0,'wow'=>0,'sad'=>0];
    if (!is_file($file)) return $out;
    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) return $out;
    foreach ($data as $r) {
        $t = (string)($r['reaction_type'] ?? '');
        if (isset($out[$t])) $out[$t]++;
        $out['total']++;
    }
    return $out;
}

/* ------------------------------------------------------------------ */
/*  Comments                                                           */
/* ------------------------------------------------------------------ */

function ha_comment_validate(string $body): array
{
    $body = trim($body);
    $errors = [];
    $len = mb_strlen($body, 'UTF-8');
    if ($len < 3) $errors[] = 'نظر باید حداقل ۳ نویسه باشد.';
    if ($len > 2000) $errors[] = 'نظر حداکثر ۲۰۰۰ نویسه می‌تواند باشد.';
    // ضد اسپم ساده: تعداد لینک
    $urlCount = preg_match_all('#https?://#i', $body);
    if ($urlCount > 2) $errors[] = 'ارسال لینک زیاد مجاز نیست.';
    // تکراری: کاراکتر تکراری زیاد
    if (preg_match('/(.)\1{9,}/u', $body)) $errors[] = 'متن تکراری زیاد است.';
    return $errors;
}

function ha_comment_is_duplicate(string $userId, string $cType, string $cSlug, string $body): bool
{
    $body = trim($body);
    if (db_ready() && db_table_exists('ha_content_comments')) {
        $rows = db_content_comments_all($cType, $cSlug, 'approved');
        $now = time();
        foreach ($rows as $r) {
            if (($r['user_id'] ?? '') === $userId && trim((string)($r['body'] ?? '')) === $body) {
                $ts = strtotime((string)($r['created_at'] ?? ''));
                if ($ts && ($now - $ts) < 600) return true; // 10 دقیقه
            }
        }
        return false;
    }
    $file = ha_comment_file($cType, $cSlug);
    if (!is_file($file)) return false;
    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) return false;
    $now = time();
    foreach ($data as $r) {
        if (($r['user_id'] ?? '') === $userId && trim((string)($r['body'] ?? '')) === $body) {
            $ts = strtotime((string)($r['created_at'] ?? ''));
            if ($ts && ($now - $ts) < 600) return true;
        }
    }
    return false;
}

function ha_comment_add(string $userId, string $cType, string $cSlug, ?int $parentId, string $body, string $ip): array
{
    if (!ha_valid_content_type($cType) || $cSlug === '' || $userId === '') {
        return ['ok'=>false,'error'=>'type'];
    }
    $cSlug = slugify($cSlug);
    $body = trim($body);
    $valErrors = ha_comment_validate($body);
    if ($valErrors !== []) return ['ok'=>false,'error'=>'validation','messages'=>$valErrors];
    if (ha_comment_is_duplicate($userId, $cType, $cSlug, $body)) {
        return ['ok'=>false,'error'=>'duplicate'];
    }
    // parent check
    if ($parentId !== null && $parentId > 0) {
        if (db_ready() && db_table_exists('ha_content_comments')) {
            $p = db_content_comment_find($parentId);
            if (!$p || (string)($p['content_type'] ?? '') !== $cType || slugify((string)($p['content_slug'] ?? '')) !== $cSlug) {
                return ['ok'=>false,'error'=>'parent'];
            }
        } else {
            $file = ha_comment_file($cType, $cSlug);
            $raw = @file_get_contents($file);
            $data = json_decode((string)$raw, true);
            $found = false;
            if (is_array($data)) {
                foreach ($data as $r) if ((int)($r['id'] ?? 0) === $parentId) { $found = true; break; }
            }
            if (!$found) return ['ok'=>false,'error'=>'parent'];
        }
    } else {
        $parentId = null;
    }

    if (db_ready() && db_table_exists('ha_content_comments')) {
        $id = db_content_comment_add($userId, $cType, $cSlug, $parentId, $body, $ip);
        return $id ? ['ok'=>true,'id'=>$id] : ['ok'=>false,'error'=>'db'];
    }
    // file fallback
    $file = ha_comment_file($cType, $cSlug);
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
        'id'=> $id,
        'user_id'=> $userId,
        'content_type'=> $cType,
        'content_slug'=> $cSlug,
        'parent_id'=> $parentId,
        'body'=> $body,
        'status'=> 'approved',
        'likes_count'=> 0,
        'ip'=> $ip,
        'created_at'=> $now,
        'updated_at'=> $now,
    ];
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $tmp = $file.'.tmp-'.bin2hex(random_bytes(4));
    $ok = @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX) !== false && @rename($tmp, $file);
    if (!$ok && is_file($tmp)) @unlink($tmp);
    return $ok ? ['ok'=>true,'id'=>$id] : ['ok'=>false,'error'=>'storage'];
}

function ha_comments_get(string $cType, string $cSlug, string $status = 'approved'): array
{
    if (!ha_valid_content_type($cType) || $cSlug === '') return [];
    $cSlug = slugify($cSlug);
    if (db_ready() && db_table_exists('ha_content_comments')) {
        return db_content_comments_all($cType, $cSlug, $status);
    }
    $file = ha_comment_file($cType, $cSlug);
    if (!is_file($file)) return [];
    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) return [];
    // filter status
    $data = array_values(array_filter($data, fn($r) => ($r['status'] ?? 'approved') === $status));
    // enrich with user name if available from auth file
    usort($data, fn($a,$b) => strcmp((string)($a['created_at'] ?? ''), (string)($b['created_at'] ?? '')));
    // attach user name
    $users = [];
    if (function_exists('auth_load_users')) {
        foreach (auth_load_users() as $u) $users[(string)($u['id'] ?? '')] = $u;
    }
    foreach ($data as $i => $r) {
        $uid = (string)($r['user_id'] ?? '');
        if (isset($users[$uid])) {
            $data[$i]['name'] = $users[$uid]['name'] ?? 'کاربر';
            $data[$i]['email'] = $users[$uid]['email'] ?? '';
        } else {
            $data[$i]['name'] = $data[$i]['name'] ?? 'کاربر';
        }
    }
    return $data;
}

function ha_comment_counts(string $cType, string $cSlug): array
{
    if (!ha_valid_content_type($cType) || $cSlug === '') return ['total'=>0,'comments'=>0,'replies'=>0];
    $cSlug = slugify($cSlug);
    if (db_ready() && db_table_exists('ha_content_comments')) {
        return db_content_comment_counts($cType, $cSlug);
    }
    $rows = ha_comments_get($cType, $cSlug, 'approved');
    $total = count($rows);
    $replies = 0;
    foreach ($rows as $r) if (!empty($r['parent_id'])) $replies++;
    return ['total'=>$total,'comments'=>$total-$replies,'replies'=>$replies];
}

/* comment likes */

function ha_comment_like_toggle(string $userId, int $commentId): array
{
    if ($userId === '' || $commentId <= 0) return ['ok'=>false];
    if (db_ready() && db_table_exists('ha_comment_likes')) {
        $exists = db_comment_like_exists($userId, $commentId);
        if ($exists) {
            $ok = db_comment_like_remove($userId, $commentId);
            return ['ok'=>$ok,'liked'=>false];
        } else {
            $ok = db_comment_like_add($userId, $commentId);
            return ['ok'=>$ok,'liked'=>true];
        }
    }
    // file fallback
    $file = ha_comment_likes_file();
    $data = [];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $d = json_decode((string)$raw, true);
        if (is_array($d)) $data = $d;
    }
    $key = $userId.'_'.$commentId;
    $liked = false;
    if (isset($data[$key])) {
        unset($data[$key]);
        $liked = false;
    } else {
        $data[$key] = ['user_id'=>$userId,'comment_id'=>$commentId,'created_at'=>date('c')];
        $liked = true;
    }
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $tmp = $file.'.tmp-'.bin2hex(random_bytes(4));
    $ok = @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX) !== false && @rename($tmp, $file);
    if (!$ok && is_file($tmp)) @unlink($tmp);
    // update likes_count in comment file (all contents)
    // we need to find comment file containing this id — iterate all comment files (expensive but fallback)
    if ($ok) {
        $cDir = ha_interaction_storage_dir('comments');
        foreach (glob($cDir.'/*.json') ?: [] as $cf) {
            $raw = @file_get_contents($cf);
            $arr = json_decode((string)$raw, true);
            if (!is_array($arr)) continue;
            $changed = false;
            foreach ($arr as $i => $c) {
                if ((int)($c['id'] ?? 0) === $commentId) {
                    $arr[$i]['likes_count'] = max(0, (int)($c['likes_count'] ?? 0) + ($liked ? 1 : -1));
                    $arr[$i]['updated_at'] = date('c');
                    $changed = true;
                    break;
                }
            }
            if ($changed) {
                $tmp2 = $cf.'.tmp-'.bin2hex(random_bytes(4));
                @file_put_contents($tmp2, json_encode($arr, JSON_UNESCAPED_UNICODE), LOCK_EX);
                @rename($tmp2, $cf);
                if (is_file($tmp2)) @unlink($tmp2);
                break;
            }
        }
    }
    return ['ok'=>$ok,'liked'=>$liked];
}

function ha_comment_likes_for_user(string $userId, array $commentIds): array
{
    if ($userId === '' || $commentIds === []) return [];
    if (db_ready() && db_table_exists('ha_comment_likes')) {
        return db_comment_likes_for_user($userId, $commentIds);
    }
    $file = ha_comment_likes_file();
    if (!is_file($file)) return [];
    $raw = @file_get_contents($file);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) return [];
    $out = [];
    foreach ($commentIds as $cid) {
        $key = $userId.'_'.$cid;
        if (isset($data[$key])) $out[(int)$cid] = true;
    }
    return $out;
}

/* ------------------------------------------------------------------ */
/*  Rendering helpers                                                 */
/* ------------------------------------------------------------------ */

function ha_interaction_block(string $cType, string $cSlug, string $title = ''): string
{
    $cType = strtolower(trim($cType));
    $cSlug = slugify($cSlug);
    if (!ha_valid_content_type($cType) || $cSlug === '') return '';
    $user = function_exists('auth_current_user') ? auth_current_user() : null;
    $userId = $user ? (string)($user['id'] ?? '') : '';
    $counts = ha_reaction_counts($cType, $cSlug);
    $myReaction = $userId !== '' ? ha_reaction_get_user($userId, $cType, $cSlug) : null;
    $myType = $myReaction ? (string)($myReaction['reaction_type'] ?? '') : '';
    $commentCounts = ha_comment_counts($cType, $cSlug);
    $comments = ha_comments_get($cType, $cSlug, 'approved');

    // build tree
    $byParent = [];
    $byId = [];
    foreach ($comments as $c) {
        $byId[(int)($c['id'] ?? 0)] = $c;
        $pid = $c['parent_id'] ?? null;
        $pidKey = $pid ? (int)$pid : 0;
        $byParent[$pidKey][] = $c;
    }
    $likedMap = [];
    if ($userId !== '') {
        $ids = array_map(fn($c) => (int)($c['id'] ?? 0), $comments);
        $likedMap = ha_comment_likes_for_user($userId, $ids);
    }

    $reactionTypes = ha_reaction_types();

    ob_start();
    ?>
    <section class="ha-interactions" data-ha-interactions data-content-type="<?= e($cType) ?>" data-content-slug="<?= e($cSlug) ?>">
        <div class="ha-interactions__head">
            <h2 class="ha-interactions__title"><?= ha_icon('chat', 18) ?> تعامل و نظرات</h2>
            <div class="ha-interactions__stats">
                <span class="ha-stat"><span class="ha-stat__icon">❤️</span><span class="ha-stat__num"><?= fa_num($counts['total']) ?></span><span class="ha-stat__label">واکنش</span></span>
                <span class="ha-stat"><span class="ha-stat__icon">💬</span><span class="ha-stat__num"><?= fa_num($commentCounts['total']) ?></span><span class="ha-stat__label">نظر</span></span>
                <span class="ha-stat"><span class="ha-stat__icon">↩️</span><span class="ha-stat__num"><?= fa_num($commentCounts['replies']) ?></span><span class="ha-stat__label">پاسخ</span></span>
            </div>
        </div>

        <!-- Reactions -->
        <div class="ha-reactions">
            <div class="ha-reactions__bar" role="group" aria-label="واکنش‌ها">
                <?php foreach ($reactionTypes as $key => $meta): 
                    $cnt = (int)($counts[$key] ?? 0);
                    $active = $myType === $key;
                ?>
                <form method="post" action="<?= e(url('reaction')) ?>" class="ha-reaction-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="content_type" value="<?= e($cType) ?>">
                    <input type="hidden" name="content_slug" value="<?= e($cSlug) ?>">
                    <input type="hidden" name="reaction_type" value="<?= e($key) ?>">
                    <input type="hidden" name="next" value="<?= e(ha_current_request_url()) ?>">
                    <div class="honeypot" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                    <button type="submit" class="ha-reaction <?= $active ? 'is-active' : '' ?>" data-reaction="<?= e($key) ?>" aria-pressed="<?= $active ? 'true' : 'false' ?>" title="<?= e($meta['label']) ?>">
                        <span class="ha-reaction__emoji"><?= $meta['emoji'] ?></span>
                        <span class="ha-reaction__label"><?= e($meta['label']) ?></span>
                        <span class="ha-reaction__count"><?= $cnt > 0 ? fa_num($cnt) : '' ?></span>
                    </button>
                </form>
                <?php endforeach; ?>
                <?php if ($myType !== ''): ?>
                <form method="post" action="<?= e(url('reaction')) ?>" class="ha-reaction-form ha-reaction-form--remove">
                    <?= csrf_field() ?>
                    <input type="hidden" name="content_type" value="<?= e($cType) ?>">
                    <input type="hidden" name="content_slug" value="<?= e($cSlug) ?>">
                    <input type="hidden" name="reaction_type" value="remove">
                    <input type="hidden" name="next" value="<?= e(ha_current_request_url()) ?>">
                    <button type="submit" class="ha-reaction ha-reaction--remove" title="حذف واکنش">✕ حذف</button>
                </form>
                <?php endif; ?>
            </div>
            <p class="ha-reactions__hint muted-sm">هر کاربر فقط یک واکنش می‌تواند ثبت کند — با کلیک روی واکنش دیگر، واکنش شما تغییر می‌کند.</p>
        </div>

        <!-- Comments -->
        <div class="ha-comments" id="comments">
            <h3 class="ha-comments__title">نظرات (<?= fa_num($commentCounts['total']) ?>)</h3>

            <?php if ($user === null): ?>
                <div class="ha-guest-cta card">
                    <div class="ha-guest-cta__icon"><?= ha_icon('sparkle', 24) ?></div>
                    <div class="ha-guest-cta__body">
                        <h4>برای تعامل و ثبت نظر، ثبت‌نام کنید</h4>
                        <p class="muted-sm">ثبت‌نام ساده، رایگان و کمتر از ۳۰ ثانیه است. پس از ثبت‌نام به همین صفحه برمی‌گردید.</p>
                        <div class="btn-row">
                            <a class="btn btn--primary btn--cta" href="<?= e(url('register', ['next'=>ha_current_request_url()])) ?>"><?= ha_icon('plus', 14) ?> ثبت‌نام رایگان</a>
                            <a class="btn btn--ghost btn--sm" href="<?= e(url('login', ['next'=>ha_current_request_url()])) ?>">قبلاً حساب دارم — ورود</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <form method="post" action="<?= e(url('content_comment')) ?>" class="ha-comment-form" data-ha-comment-form>
                    <?= csrf_field() ?>
                    <input type="hidden" name="content_type" value="<?= e($cType) ?>">
                    <input type="hidden" name="content_slug" value="<?= e($cSlug) ?>">
                    <input type="hidden" name="parent_id" value="0">
                    <input type="hidden" name="next" value="<?= e(ha_current_request_url()) ?>">
                    <div class="honeypot" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                    <div class="ha-comment-form__head">
                        <span class="ha-avatar"><?= e(auth_initial((string)($user['name'] ?? ''))) ?></span>
                        <strong><?= e((string)($user['name'] ?? '')) ?></strong>
                    </div>
                    <div class="field">
                        <label for="ha-comment-body" class="sr-only">متن نظر</label>
                        <textarea id="ha-comment-body" name="body" class="input ha-comment-form__textarea" required minlength="3" maxlength="2000" rows="3" placeholder="نظر یا تجربه‌ات را بنویس..."></textarea>
                        <p class="field__help">حداقل ۳ نویسه، حداکثر ۲۰۰۰ نویسه. از اسپم و لینک زیاد پرهیز کنید.</p>
                    </div>
                    <button class="btn btn--primary btn--sm btn--cta" type="submit"><?= ha_icon('chat', 14) ?> ثبت نظر</button>
                </form>
            <?php endif; ?>

            <div class="ha-comment-list">
                <?php if ($comments === []): ?>
                    <p class="muted-sm ha-empty">هنوز نظری ثبت نشده — اولین نفر باشید!</p>
                <?php else: ?>
                    <?php
                    // recursive render
                    $renderComments = function(array $list, int $depth = 0) use (&$renderComments, $byParent, $likedMap, $user, $cType, $cSlug) {
                        foreach ($list as $c) {
                            $id = (int)($c['id'] ?? 0);
                            $isLiked = isset($likedMap[$id]);
                            $isOwn = $user && (string)($c['user_id'] ?? '') === (string)($user['id'] ?? '');
                            $name = (string)($c['name'] ?? ($c['user_id'] ? 'کاربر' : 'ناشناس'));
                            $created = (string)($c['created_at'] ?? '');
                            $dateFa = $created !== '' ? ha_fa_date($created) : '';
                            $likes = (int)($c['likes_count'] ?? 0);
                            $body = (string)($c['body'] ?? '');
                            $replies = $byParent[$id] ?? [];
                            ?>
                            <div class="ha-comment <?= $depth > 0 ? 'ha-comment--reply' : '' ?>" data-comment-id="<?= $id ?>" style="--depth: <?= $depth ?>">
                                <div class="ha-comment__main">
                                    <div class="ha-comment__avatar"><?= e(auth_initial($name)) ?></div>
                                    <div class="ha-comment__body">
                                        <div class="ha-comment__meta">
                                            <strong class="ha-comment__name"><?= e($name) ?></strong>
                                            <?php if ($dateFa !== ''): ?><span class="ha-comment__date"><?= e($dateFa) ?></span><?php endif; ?>
                                            <?php if ($depth > 0): ?><span class="badge badge--soft">پاسخ</span><?php endif; ?>
                                        </div>
                                        <p class="ha-comment__text"><?= nl2br(e($body)) ?></p>
                                        <div class="ha-comment__actions">
                                            <form method="post" action="<?= e(url('comment_like')) ?>" class="ha-inline-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="comment_id" value="<?= $id ?>">
                                                <input type="hidden" name="content_type" value="<?= e($cType) ?>">
                                                <input type="hidden" name="content_slug" value="<?= e($cSlug) ?>">
                                                <input type="hidden" name="next" value="<?= e(ha_current_request_url()) ?>">
                                                <button type="submit" class="ha-action-btn <?= $isLiked ? 'is-liked' : '' ?>" aria-pressed="<?= $isLiked ? 'true' : 'false' ?>">
                                                    <?= ha_icon($isLiked ? 'heart-filled' : 'heart', 14) ?> <?= $likes > 0 ? fa_num($likes) : 'پسند' ?>
                                                </button>
                                            </form>
                                            <?php if ($user): ?>
                                                <button type="button" class="ha-action-btn" data-ha-reply="<?= $id ?>"><?= ha_icon('reply', 14) ?> پاسخ</button>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($user): ?>
                                        <form method="post" action="<?= e(url('content_comment')) ?>" class="ha-comment-form ha-comment-form--reply" data-ha-reply-form="<?= $id ?>" hidden>
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="content_type" value="<?= e($cType) ?>">
                                            <input type="hidden" name="content_slug" value="<?= e($cSlug) ?>">
                                            <input type="hidden" name="parent_id" value="<?= $id ?>">
                                            <input type="hidden" name="next" value="<?= e(ha_current_request_url()) ?>">
                                            <div class="honeypot" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                                            <textarea name="body" class="input" required minlength="3" maxlength="2000" rows="2" placeholder="پاسخ به <?= e($name) ?>..."></textarea>
                                            <div class="btn-row" style="margin-top:.5rem">
                                                <button class="btn btn--primary btn--xs btn--cta" type="submit">ثبت پاسخ</button>
                                                <button class="btn btn--ghost btn--xs" type="button" data-ha-cancel-reply>انصراف</button>
                                            </div>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($replies !== []): ?>
                                    <div class="ha-comment__replies">
                                        <?php $renderComments($replies, $depth + 1); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                    };
                    $roots = $byParent[0] ?? [];
                    $renderComments($roots, 0);
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
    return (string)ob_get_clean();
}
