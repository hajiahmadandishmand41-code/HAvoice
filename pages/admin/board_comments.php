<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$flash = flash();
$postFilter = (int)param('post_id','0');
$statusFilter = param('status','');

// gather comments
$allPosts = ha_board_posts_get(200,0,'','');
$allComments = [];
foreach ($allPosts as $post) {
    $pid = (int)($post['id']??0);
    if ($postFilter>0 && $pid!==$postFilter) continue;
    $comments = ha_board_comments_get($pid, $statusFilter!==''?$statusFilter:'approved');
    if ($statusFilter==='' ) {
        // also get pending for same post
        $pending = ha_board_comments_get($pid,'pending');
        $comments = array_merge($comments,$pending);
    }
    foreach ($comments as $c) {
        $c['_post_id']=$pid;
        $c['_post_body']=mb_strimwidth((string)($post['body']??''),0,60,'…','UTF-8');
        $allComments[]=$c;
    }
}
usort($allComments, fn($a,$b)=> strcmp((string)($b['created_at']??''), (string)($a['created_at']??'')));
?>
<?php if ($flash !== [] && !empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><p><?= e($flash['message']) ?></p></div><?php endif; ?>

<div class="admin-card">
    <h2>نظرات تابلوی مجازی — <?= fa_num(count($allComments)) ?></h2>
    <div class="chip-row">
        <a class="chip <?= $statusFilter===''?'is-active':'' ?>" href="<?= e(url('admin_board_comments',['post_id'=>$postFilter>0?$postFilter:''])) ?>">همه</a>
        <a class="chip <?= $statusFilter==='approved'?'is-active':'' ?>" href="<?= e(url('admin_board_comments',['status'=>'approved','post_id'=>$postFilter>0?$postFilter:''])) ?>">تأییدشده</a>
        <a class="chip <?= $statusFilter==='pending'?'is-active':'' ?>" href="<?= e(url('admin_board_comments',['status'=>'pending','post_id'=>$postFilter>0?$postFilter:''])) ?>">در انتظار</a>
    </div>

    <?php if ($allComments===[]): ?><p class="muted-sm">نظری نیست.</p>
    <?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>ID</th><th>پست</th><th>کاربر</th><th>متن</th><th>والد</th><th>لایک</th><th>وضعیت</th><th>عملیات</th></tr></thead>
            <tbody>
            <?php foreach ($allComments as $c):
                $cid=(int)($c['id']??0);
                $pid=(int)($c['_post_id']??0);
            ?>
                <tr>
                    <td><?= fa_num($cid) ?></td>
                    <td><a href="<?= e(url('board')) ?>#post-<?= $pid ?>">#<?= fa_num($pid) ?></a><br><small><?= e($c['_post_body']??'') ?></small></td>
                    <td><?= e($c['name']??'کاربر') ?></td>
                    <td style="max-width:280px;white-space:pre-wrap"><?= e($c['body']??'') ?></td>
                    <td><?= $c['parent_id'] ? fa_num((int)$c['parent_id']) : '—' ?></td>
                    <td><?= fa_num($c['likes_count']??0) ?></td>
                    <td><span class="badge badge--soft"><?= e($c['status']??'') ?></span></td>
                    <td>
                        <div class="btn-row" style="flex-wrap:wrap">
                            <form method="post" action="<?= e(url('admin_board_comment_status')) ?>"><?= csrf_field() ?><input type="hidden" name="comment_id" value="<?= $cid ?>"><input type="hidden" name="status" value="approved"><button class="btn btn--ghost btn--xs" type="submit">تأیید</button></form>
                            <form method="post" action="<?= e(url('admin_board_comment_status')) ?>"><?= csrf_field() ?><input type="hidden" name="comment_id" value="<?= $cid ?>"><input type="hidden" name="status" value="pending"><button class="btn btn--ghost btn--xs" type="submit">انتظار</button></form>
                            <form method="post" action="<?= e(url('admin_board_comment_delete')) ?>" onsubmit="return confirm('حذف نظر؟')"><?= csrf_field() ?><input type="hidden" name="comment_id" value="<?= $cid ?>"><button class="btn btn--ghost btn--xs" style="color:#BE123C" type="submit">حذف</button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
