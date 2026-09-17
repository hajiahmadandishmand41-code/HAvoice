<?php
/**
 * HAvoice Admin — مدیریت تابلوی مجازی (Posts)
 */
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';

$flash = flash();
$statusFilter = param('status','');
$search = param('q','');
$all = ha_board_posts_get(500,0,$statusFilter!==''?$statusFilter:'','');
if ($search !== '') {
    $needle = mb_strtolower($search,'UTF-8');
    $all = array_values(array_filter($all, fn($p)=> mb_strpos(mb_strtolower((string)($p['body']??''),'UTF-8'), $needle)!==false || mb_strpos(mb_strtolower((string)($p['name']??''),'UTF-8'), $needle)!==false));
}
$stats = ha_site_stats();
?>
<?php if ($flash !== [] && !empty($flash['message'])): ?>
    <div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><p><?= e($flash['message']) ?></p></div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card__head">
        <h2>پست‌های تابلوی مجازی — <?= fa_num(count($all)) ?> مورد</h2>
        <div class="btn-row">
            <a class="chip <?= $statusFilter===''?'is-active':'' ?>" href="<?= e(url('admin_board')) ?>">همه</a>
            <a class="chip <?= $statusFilter==='approved'?'is-active':'' ?>" href="<?= e(url('admin_board',['status'=>'approved'])) ?>">منتشرشده</a>
            <a class="chip <?= $statusFilter==='pending'?'is-active':'' ?>" href="<?= e(url('admin_board',['status'=>'pending'])) ?>">در انتظار</a>
            <a class="chip <?= $statusFilter==='hidden'?'is-active':'' ?>" href="<?= e(url('admin_board',['status'=>'hidden'])) ?>">مخفی</a>
        </div>
    </div>

    <form method="get" action="<?= e(url('admin_board')) ?>" class="admin-search" style="margin-bottom:1rem">
        <input type="hidden" name="p" value="admin_board">
        <input class="input" type="search" name="q" value="<?= e($search) ?>" placeholder="جستجو در متن پست یا نام کاربر...">
        <button class="btn btn--ghost btn--sm" type="submit">جستجو</button>
    </form>

    <?php if ($all===[]): ?>
        <p class="muted-sm">پستی یافت نشد.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>ID</th><th>کاربر</th><th>متن</th><th>وضعیت</th><th>واکنش/نظر/بازدید</th><th>تاریخ</th><th>عملیات</th></tr></thead>
                <tbody>
                <?php foreach ($all as $post):
                    $id=(int)($post['id']??0);
                ?>
                    <tr>
                        <td><?= fa_num($id) ?></td>
                        <td><strong><?= e($post['name']??'کاربر') ?></strong><br><small class="muted-sm"><?= e($post['user_id']??'') ?></small></td>
                        <td style="max-width:320px"><div style="white-space:pre-wrap;word-break:break-word;font-size:.9rem"><?= e(mb_strimwidth((string)($post['body']??''),0,160,'…','UTF-8')) ?></div>
                            <?php if (!empty($post['image'])): ?><small>🖼️ <?= e($post['image']) ?></small><?php endif; ?>
                            <?php if (!empty($post['media_url'])): ?><br><small>🔗 <?= e($post['media_url']) ?></small><?php endif; ?>
                        </td>
                        <td><span class="badge badge--soft"><?= e($post['status']??'') ?></span></td>
                        <td><small>❤️ <?= fa_num($post['reactions_count']??0) ?> · 💬 <?= fa_num($post['comments_count']??0) ?> · 👁️ <?= fa_num($post['views_count']??0) ?></small></td>
                        <td class="muted-sm"><?= e($post['created_at']??'') ?></td>
                        <td>
                            <div class="btn-row" style="flex-wrap:wrap">
                                <form method="post" action="<?= e(url('admin_board_status')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="post_id" value="<?= $id ?>">
                                    <input type="hidden" name="status" value="approved">
                                    <button class="btn btn--ghost btn--xs" type="submit">تأیید</button>
                                </form>
                                <form method="post" action="<?= e(url('admin_board_status')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="post_id" value="<?= $id ?>">
                                    <input type="hidden" name="status" value="hidden">
                                    <button class="btn btn--ghost btn--xs" type="submit">مخفی</button>
                                </form>
                                <form method="post" action="<?= e(url('admin_board_delete')) ?>" onsubmit="return confirm('حذف پست؟')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="post_id" value="<?= $id ?>">
                                    <button class="btn btn--ghost btn--xs" style="color:#BE123C" type="submit">حذف</button>
                                </form>
                                <a class="btn btn--ghost btn--xs" href="<?= e(url('board')) ?>#post-<?= $id ?>" target="_blank">مشاهده</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h3>آمار تابلو</h3>
    <p class="muted-sm">پست: <?= fa_num($stats['board_posts']??0) ?> · واکنش: <?= fa_num($stats['board_reactions']??0) ?> · نظر: <?= fa_num($stats['board_comments']??0) ?></p>
</div>

<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
