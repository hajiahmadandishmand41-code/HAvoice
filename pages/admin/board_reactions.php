<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$flash = flash();

$postId = (int)param('post_id','0');
$allPosts = ha_board_posts_get(200,0,'','');
$reactions = [];
if ($postId>0) {
    if (db_ready() && db_table_exists('ha_board_reactions')) {
        $reactions = db_all('SELECT r.*, u.name FROM ha_board_reactions r LEFT JOIN ha_users u ON u.id=r.user_id WHERE r.post_id=? ORDER BY r.created_at DESC', [$postId]);
    } else {
        $file = storage_dir('board').'/reactions.json';
        if (is_file($file)) {
            $raw=@file_get_contents($file);
            $data=json_decode((string)$raw,true);
            if (is_array($data)) {
                foreach ($data as $r) if ((int)($r['post_id']??0)===$postId) $reactions[]=$r;
            }
        }
    }
}
?>
<?php if ($flash !== [] && !empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><p><?= e($flash['message']) ?></p></div><?php endif; ?>

<div class="admin-card">
    <h2>واکنش‌های تابلوی مجازی</h2>
    <p class="muted-sm">برای دیدن واکنش‌های یک پست، از صفحه <a href="<?= e(url('admin_board')) ?>">پست‌ها</a> روی «مشاهده» کلیک کنید یا post_id را وارد کنید.</p>
    <form method="get" action="<?= e(url('admin_board').'/reactions') ?>" class="admin-search">
        <input type="hidden" name="p" value="admin_board_reactions">
        <input class="input" type="number" name="post_id" value="<?= $postId>0?e((string)$postId):'' ?>" placeholder="ID پست">
        <button class="btn btn--ghost btn--sm" type="submit">نمایش</button>
    </form>

    <?php if ($postId>0): ?>
        <h3>واکنش‌های پست #<?= fa_num($postId) ?> — <?= fa_num(count($reactions)) ?> مورد</h3>
        <?php if ($reactions===[]): ?><p class="muted-sm">واکنشی نیست.</p>
        <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>کاربر</th><th>نوع</th><th>تاریخ</th><th>عملیات</th></tr></thead>
                <tbody>
                <?php foreach ($reactions as $r): ?>
                    <tr>
                        <td><?= e($r['name']??$r['user_id']??'') ?></td>
                        <td><?= e($r['reaction_type']??'') ?></td>
                        <td class="muted-sm"><?= e($r['created_at']??'') ?></td>
                        <td>
                            <form method="post" action="<?= e(url('board_reaction')) ?>" onsubmit="return confirm('حذف واکنش؟')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="post_id" value="<?= $postId ?>">
                                <input type="hidden" name="reaction_type" value="remove">
                                <input type="hidden" name="next" value="<?= e(url('admin_board_reactions',['post_id'=>$postId])) ?>">
                                <!-- override user_id via admin? We need admin delete -->
                                <!-- For admin, we will directly delete via DB -->
                                <button class="btn btn--ghost btn--xs" type="button" data-admin-del-reaction data-post="<?= $postId ?>" data-user="<?= e($r['user_id']??'') ?>">حذف</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// admin delete reaction via fetch to admin endpoint
document.addEventListener('click', function(e){
  var btn=e.target.closest('[data-admin-del-reaction]');
  if(!btn) return;
  if(!confirm('حذف واکنش این کاربر؟')) return;
  var post=btn.getAttribute('data-post');
  var user=btn.getAttribute('data-user');
  var fd=new FormData();
  fd.append('post_id',post);
  fd.append('user_id',user);
  fd.append('csrf_token','<?= e(csrf_token()) ?>');
  fetch('<?= e(url('admin_board')) ?>/reaction-del', {method:'POST', body:fd})
    .then(()=> location.reload());
});
</script>

<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
