<?php
/**
 * HAvoice — تابلوی مجازی (Virtual Board) — صفحه مستقل از آموزش
 * - Feed مدرن RTL، Mobile-First
 * - Post متنی + تصویر/رسانه
 * - Like/Reaction/Comment/Reply/Like Comment
 * - الگوریتم Dynamic: جدیدتر + تعامل
 * - Guest → Register
 */

if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');

// Guest guard → Register (not Login) per requirement
if (!auth_is_logged_in()) {
    flash('error','برای ورود به تابلوی مجازی ثبت‌نام کنید — رایگان و کمتر از ۳۰ ثانیه. پس از ثبت‌نام به همین صفحه برمی‌گردید.');
    redirect(url('register',['next'=>ha_current_request_url()]));
}

// track visit
ha_track_visit('board','');

$currentUser = auth_current_user();
$myId = $currentUser ? (string)($currentUser['id'] ?? '') : '';

$sort = param('sort','dynamic');
if (!in_array($sort, ['dynamic','newest','popular'], true)) $sort='dynamic';

$page = max(1, (int)param('page','1'));
$perPage = 20;
$offset = ($page-1)*$perPage;

$allPosts = ha_board_posts_get(500,0,'approved',''); // get many for sorting
$sorted = ha_board_feed_sorted($allPosts, $sort);
$total = count($sorted);
$posts = array_slice($sorted, $offset, $perPage);
$totalPages = max(1, (int)ceil($total / $perPage));

$postIds = array_map(fn($p)=>(int)($p['id']??0), $posts);
$myReactions = $myId !== '' ? ha_board_reactions_for_user($myId,$postIds) : [];

// for liked comments map, collect all comment ids
$allCommentIds = [];
foreach ($posts as $p) {
    $c = ha_board_comments_get((int)($p['id']??0),'approved');
    foreach ($c as $cm) $allCommentIds[] = (int)($cm['id']??0);
}
$likedComments = $myId !== '' ? ha_board_comment_likes_for_user($myId,$allCommentIds) : [];

$flash = flash();
?>
<section class="section section--tight board-page">
    <div class="container board-container">
        <header class="board-hero">
            <div class="board-hero__top">
                <span class="board-hero__icon"><?= ha_icon('sparkle',26) ?></span>
                <div>
                    <p class="eyebrow">فضای تعامل آزاد</p>
                    <h1>تابلوی مجازی HAvoice</h1>
                    <p class="muted-sm">ایده، تجربه، سوال یا انگیزه‌ات را بنویس — متن، تصویر یا ویدیو. محتوای قدیمی حذف نمی‌شود و با الگوریتم پویا، مطالب تازه و پرتعامل بالاتر می‌آیند.</p>
                </div>
            </div>
            <div class="board-hero__stats">
                <span class="chip chip--soft"><?= ha_icon('chat',12) ?> <?= fa_num($total) ?> پست</span>
                <span class="chip chip--ghost">مرتب‌سازی: <?= $sort==='dynamic'?'پویا ⭐':($sort==='newest'?'جدیدترین 🕒':'محبوب 🔥') ?></span>
            </div>
            <nav class="chip-row" aria-label="مرتب‌سازی">
                <a class="chip <?= $sort==='dynamic'?'is-active':'' ?>" href="<?= e(url('board',['sort'=>'dynamic'])) ?>">پویا ⭐</a>
                <a class="chip <?= $sort==='newest'?'is-active':'' ?>" href="<?= e(url('board',['sort'=>'newest'])) ?>">جدیدترین</a>
                <a class="chip <?= $sort==='popular'?'is-active':'' ?>" href="<?= e(url('board',['sort'=>'popular'])) ?>">محبوب</a>
                <a class="chip chip--ghost" href="<?= e(url('profile',['slug'=>$myId])) ?>"><?= ha_icon('user',12) ?> پروفایل من</a>
            </nav>
        </header>

        <?php if ($flash !== [] && !empty($flash['message'])): ?>
            <div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>" role="<?= $flash['type']==='success'?'status':'alert' ?>">
                <span class="alert__icon"><?= ha_icon($flash['type']==='success'?'check':'alert',13) ?></span>
                <p><?= e($flash['message']) ?></p>
            </div>
        <?php endif; ?>

        <!-- New Post Form -->
        <section class="card board-new" id="new-post">
            <h2 class="board-new__title"><?= ha_icon('plus',16) ?> پست جدید</h2>
            <form method="post" action="<?= e(url('board_post')) ?>" enctype="multipart/form-data" class="board-form">
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e(ha_current_request_url()) ?>">
                <div class="honeypot" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                <div class="field">
                    <label for="b-body">متن پست <span class="req">*</span></label>
                    <textarea id="b-body" name="body" class="input" required minlength="3" maxlength="5000" rows="4" placeholder="چی تو ذهنت هست؟ تجربه، سوال، انگیزه..."></textarea>
                    <p class="field__help">۳ تا ۵۰۰۰ نویسه. لینک زیاد نه.</p>
                </div>
                <div class="grid grid--2">
                    <div class="field">
                        <label for="b-image">تصویر (اختیاری)</label>
                        <input type="file" id="b-image" name="image" accept=".jpg,.jpeg,.png,.webp" class="input">
                        <p class="field__help">JPG/PNG/WebP تا <?= fa_num(round(ha_upload_max_bytes('image')/1048576,1)) ?> مگ</p>
                    </div>
                    <div class="field">
                        <label for="b-media">لینک رسانه (اختیاری)</label>
                        <input type="url" id="b-media" name="media_url" class="input" placeholder="https://... (یوتیوب، آپارات، ...)" dir="ltr">
                        <p class="field__help">ویدیو/صوت بیرونی — فقط https و میزبان مجاز</p>
                    </div>
                </div>
                <button class="btn btn--primary btn--cta" type="submit"><?= ha_icon('sparkle',14) ?> انتشار پست</button>
            </form>
        </section>

        <!-- Feed -->
        <section class="board-feed" aria-label="فید تابلوی مجازی">
            <?php if ($posts === []): ?>
                <div class="card"><p class="muted-sm">هنوز پستی نیست — اولین نفر باشید!</p></div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <?= ha_board_render_post($post,$myId,$myReactions,$likedComments) ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <?php if ($totalPages > 1): ?>
        <nav class="pager pager--board" aria-label="صفحه‌بندی">
            <?php if ($page > 1): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('board',['sort'=>$sort,'page'=>($page-1)])) ?>">قبلی</a><?php endif; ?>
            <span class="muted-sm">صفحه <?= fa_num($page) ?> از <?= fa_num($totalPages) ?> — <?= fa_num($total) ?> پست</span>
            <?php if ($page < $totalPages): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('board',['sort'=>$sort,'page'=>($page+1)])) ?>">بعدی</a><?php endif; ?>
        </nav>
        <?php endif; ?>

        <aside class="board-info card">
            <h3><?= ha_icon('info',15) ?> درباره الگوریتم پویا</h3>
            <p class="muted-sm">امتیاز هر پست = (واکنش×۲ + نظر×۳ + بازدید×۰.۲ + لایک) ÷ (ساعت از انتشار+۲)^۱.۳۵ + ۵۰/(ساعت+۱). یعنی تازه‌ها و پرتعامل‌ها بالاتر، ولی قدیمی‌ها هرگز حذف نمی‌شوند.</p>
        </aside>
    </div>
</section>
