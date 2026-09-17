<?php
/**
 * HAvoice — پروفایل ساده کاربر (برای تابلوی مجازی)
 */

if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');

$slug = slugify((string)($GLOBALS['HA_SLUG'] ?? param('slug')));
if ($slug === '') {
    // اگر slug نیست، پروفایل خودم
    if (!auth_is_logged_in()) {
        flash('error','برای دیدن پروفایل ثبت‌نام کنید.');
        redirect(url('register',['next'=>ha_current_request_url()]));
    }
    $current = auth_current_user();
    $slug = $current ? (string)($current['id'] ?? '') : '';
}

ha_track_visit('profile',$slug);

$profile = ha_user_profile($slug);
if (!$profile) {
    http_response_code(404);
    echo not_found('کاربر');
    return;
}

$currentUser = auth_current_user();
$myId = $currentUser ? (string)($currentUser['id'] ?? '') : '';
$isOwn = $myId !== '' && $myId === $profile['id'];

$postIds = array_map(fn($p)=>(int)($p['id']??0), $profile['posts'] ?? []);
$myReactions = $myId !== '' ? ha_board_reactions_for_user($myId,$postIds) : [];
$allCommentIds = [];
foreach ($profile['posts'] as $p) {
    $c = ha_board_comments_get((int)($p['id']??0),'approved');
    foreach ($c as $cm) $allCommentIds[] = (int)($cm['id']??0);
}
$likedComments = $myId !== '' ? ha_board_comment_likes_for_user($myId,$allCommentIds) : [];

$flash = flash();
?>
<section class="section section--tight profile-page">
    <div class="container container--narrow">
        <?php if ($flash !== [] && !empty($flash['message'])): ?>
            <div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><p><?= e($flash['message']) ?></p></div>
        <?php endif; ?>

        <div class="card profile-card">
            <div class="profile-card__head">
                <span class="ha-avatar" style="width:56px;height:56px;font-size:1.3rem"><?= e(auth_initial($profile['name'])) ?></span>
                <div>
                    <h1><?= e($profile['name']) ?></h1>
                    <p class="muted-sm"><?= e($profile['email']) ?> · <?= e($profile['role']==='admin'?'مدیر':'کاربر') ?> · عضویت: <?= e($profile['created_at'] ? ha_fa_date($profile['created_at']) : '') ?></p>
                    <div class="chip-row" style="margin-top:.5rem">
                        <span class="chip chip--soft"><?= fa_num($profile['stats']['posts'] ?? 0) ?> پست</span>
                        <?php if ($isOwn): ?><a class="chip chip--ghost" href="<?= e(url('account')) ?>">حساب کاربری</a><?php endif; ?>
                        <a class="chip chip--ghost" href="<?= e(url('board')) ?>">تابلوی مجازی</a>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="mt-md">پست‌های <?= e($profile['name']) ?> (<?= fa_num(count($profile['posts'])) ?>)</h2>
        <div class="board-feed">
            <?php if ($profile['posts'] === []): ?>
                <p class="muted-sm">هنوز پستی منتشر نکرده.</p>
            <?php else: ?>
                <?php foreach ($profile['posts'] as $post): ?>
                    <?= ha_board_render_post($post,$myId,$myReactions,$likedComments) ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
