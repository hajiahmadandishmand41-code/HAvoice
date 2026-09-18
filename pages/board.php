<?php
/**
 * HAvoice — تابلوی مجازی
 * نمای ساده: افزودن پست + فهرست پست‌ها. جزئیات هر پست در نمای مستقل خودش.
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');

if (!auth_is_logged_in()) {
    flash('error','برای ورود به تابلوی مجازی ثبت‌نام کنید.');
    redirect(url('register',['next'=>ha_current_request_url()]));
}

$currentUser = auth_current_user();
$myId = $currentUser ? (string)($currentUser['id'] ?? '') : '';
$postId = max(0, (int)param('post','0'));

if ($postId > 0) {
    $post = ha_board_post_find($postId);
    if (!$post || ($post['status'] ?? 'approved') !== 'approved') {
        http_response_code(404);
        ?>
        <section class="section section--tight board-page board-post-page">
            <div class="container board-container">
                <a class="board-back" href="<?= e(url('board')) ?>">← بازگشت به تابلو</a>
                <div class="card board-empty"><h1>پست پیدا نشد</h1><p class="muted-sm">این پست وجود ندارد یا دیگر قابل نمایش نیست.</p></div>
            </div>
        </section>
        <?php
        return;
    }

    ha_board_post_inc_view($postId);
    ha_track_visit('board_post',(string)$postId);
    $myReactions = $myId !== '' ? ha_board_reactions_for_user($myId,[$postId]) : [];
    $comments = ha_board_comments_get($postId,'approved');
    $commentIds = array_map(static fn($c)=>(int)($c['id']??0),$comments);
    $likedComments = $myId !== '' ? ha_board_comment_likes_for_user($myId,$commentIds) : [];
    ?>
    <section class="section section--tight board-page board-post-page">
        <div class="container board-container">
            <a class="board-back" href="<?= e(url('board')) ?>">← بازگشت به تابلو</a>
            <?= ha_board_render_post($post,$myId,$myReactions,$likedComments) ?>
        </div>
    </section>
    <?php
    return;
}

ha_track_visit('board','');
$page = max(1,(int)param('page','1'));
$perPage = 15;
$offset = ($page-1)*$perPage;
$posts = ha_board_posts_get($perPage,$offset,'approved','');
$nextProbe = ha_board_posts_get($perPage+1,$offset,'approved','');
$hasNext = count($nextProbe) > $perPage;
$postIds = array_map(static fn($p)=>(int)($p['id']??0),$posts);
$myReactions = $myId !== '' ? ha_board_reactions_for_user($myId,$postIds) : [];
$flash = flash();
?>
<section class="section section--tight board-page">
    <div class="container board-container">
        <header class="board-simple-head">
            <div>
                <p class="eyebrow">فضای گفتگو</p>
                <h1>تابلوی مجازی</h1>
                <p class="muted-sm">اینجا ایده، تجربه، سؤال یا یک محتوای جالب را با دیگران به اشتراک بگذارید.</p>
            </div>
            <a class="btn btn--primary btn--cta" href="#new-post"><?= ha_icon('plus',15) ?> افزودن پست</a>
        </header>

        <?php if ($flash !== [] && !empty($flash['message'])): ?>
            <div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>" role="<?= $flash['type']==='success'?'status':'alert' ?>">
                <span class="alert__icon"><?= ha_icon($flash['type']==='success'?'check':'alert',13) ?></span><p><?= e($flash['message']) ?></p>
            </div>
        <?php endif; ?>

        <section class="card board-new board-new--simple" id="new-post">
            <div class="board-new__title-row">
                <h2 class="board-new__title"><?= ha_icon('plus',16) ?> افزودن پست</h2>
                <span class="muted-sm">متن، تصویر یا ویدیو</span>
            </div>
            <form method="post" action="<?= e(url('board_post')) ?>" enctype="multipart/form-data" class="board-form">
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e(url('board')) ?>">
                <div class="honeypot" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                <label class="sr-only" for="b-body">متن پست</label>
                <textarea id="b-body" name="body" class="input" maxlength="5000" rows="4" placeholder="چه چیزی می‌خواهید با دیگران به اشتراک بگذارید؟"></textarea>
                <p class="field__help">متن، یا یک تصویر، یا لینک ویدیو/صوت — دست‌کم یکی لازم است.</p>
                <div class="board-upload-row">
                    <label class="board-upload" for="b-image"><span><?= ha_icon('image',15) ?> تصویر</span><input id="b-image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp"></label>
                    <label class="sr-only" for="b-media">لینک ویدیو یا صوت</label>
                    <input id="b-media" type="url" name="media_url" class="input" placeholder="لینک ویدیو یا صوت (اختیاری)" dir="ltr">
                    <button class="btn btn--primary btn--cta" type="submit"><?= ha_icon('plus',14) ?> انتشار</button>
                </div>
            </form>
        </section>

        <section class="board-feed" aria-label="پست‌های تابلو">
            <?php if ($posts === []): ?>
                <div class="card board-empty"><h2>هنوز پستی منتشر نشده</h2><p class="muted-sm">اولین پست را شما منتشر کنید.</p></div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <?php
                    $pid = (int)($post['id']??0);
                    $comments = ha_board_comments_get($pid,'approved');
                    $commentIds = array_map(static fn($c)=>(int)($c['id']??0),$comments);
                    $liked = $myId !== '' ? ha_board_comment_likes_for_user($myId,$commentIds) : [];
                    ?>
                    <div class="board-feed-item">
                        <?= ha_board_render_post($post,$myId,$myReactions,$liked) ?>
                        <a class="board-post-open" href="<?= e(url('board',['post'=>$pid])) ?>" aria-label="مشاهده پست">مشاهده پست و گفتگو ←</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <?php if ($page > 1 || $hasNext): ?>
        <nav class="pager pager--board" aria-label="صفحه‌بندی">
            <?php if ($page > 1): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('board',['page'=>$page-1])) ?>">قبلی</a><?php endif; ?>
            <span class="muted-sm">صفحه <?= fa_num($page) ?></span>
            <?php if ($hasNext): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('board',['page'=>$page+1])) ?>">بعدی</a><?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</section>