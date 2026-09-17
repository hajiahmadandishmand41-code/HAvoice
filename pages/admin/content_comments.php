<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$flash = flash();
$type = param('type','');
$slug = param('slug','');
$status = param('status','');

$all = [];
if (db_ready() && db_table_exists('ha_content_comments')) {
    $sql = 'SELECT cc.*, u.name FROM ha_content_comments cc LEFT JOIN ha_users u ON u.id=cc.user_id';
    $where=[];
    $params=[];
    if ($type!=='' && ha_valid_content_type($type)) { $where[]='cc.content_type=?'; $params[]=$type; }
    if ($slug!=='') { $where[]='cc.content_slug=?'; $params[]=$slug; }
    if ($status==='approved' || $status==='pending') { $where[]='cc.status=?'; $params[]=$status; }
    if ($where!==[]) $sql.=' WHERE '.implode(' AND ',$where);
    $sql.=' ORDER BY cc.created_at DESC LIMIT 300';
    $all = db_all($sql,$params);
} else {
    // file fallback
    $dir = storage_dir('interactions/comments');
    foreach (ha_dir_files($dir,'json') as $file) {
        $raw=@file_get_contents($file);
        $data=json_decode((string)$raw,true);
        if (!is_array($data)) continue;
        foreach ($data as $r) {
            if ($type!=='' && ($r['content_type']??'')!==$type) continue;
            if ($slug!=='' && ($r['content_slug']??'')!==$slug) continue;
            if ($status!=='' && ($r['status']??'')!==$status) continue;
            $all[]=$r;
        }
    }
    usort($all, fn($a,$b)=> strcmp((string)($b['created_at']??''), (string)($a['created_at']??'')));
    $all = array_slice($all,0,300);
}
?>
<?php if ($flash !== [] && !empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><p><?= e($flash['message']) ?></p></div><?php endif; ?>

<div class="admin-card">
    <h2>نظرات محتوای آموزشی — <?= fa_num(count($all)) ?></h2>
    <form method="get" class="admin-search" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem">
        <input type="hidden" name="p" value="admin_content_comments">
        <select name="type" class="input" style="max-width:160px"><option value="">همه نوع‌ها</option><?php foreach (ha_content_types() as $t): ?><option value="<?= e($t) ?>" <?= $type===$t?'selected':'' ?>><?= e($t) ?></option><?php endforeach; ?></select>
        <input class="input" type="text" name="slug" value="<?= e($slug) ?>" placeholder="slug محتوا" style="max-width:180px">
        <select name="status" class="input" style="max-width:140px"><option value="">همه وضعیت‌ها</option><option value="approved" <?= $status==='approved'?'selected':'' ?>>تأییدشده</option><option value="pending" <?= $status==='pending'?'selected':'' ?>>در انتظار</option></select>
        <button class="btn btn--ghost btn--sm" type="submit">فیلتر</button>
    </form>

    <?php if ($all===[]): ?><p class="muted-sm">نظری نیست.</p>
    <?php else: ?>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>ID</th><th>نوع/اسلاگ</th><th>کاربر</th><th>متن</th><th>والد</th><th>لایک</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
    <?php foreach ($all as $c): $cid=(int)($c['id']??0); ?>
        <tr>
            <td><?= fa_num($cid) ?></td>
            <td><small><?= e($c['content_type']??'') ?>/<?= e($c['content_slug']??'') ?></small></td>
            <td><?= e($c['name']??$c['user_id']??'') ?></td>
            <td style="max-width:280px;white-space:pre-wrap"><?= e(mb_strimwidth((string)($c['body']??''),0,160,'…','UTF-8')) ?></td>
            <td><?= !empty($c['parent_id']) ? fa_num((int)$c['parent_id']) : '—' ?></td>
            <td><?= fa_num((int)($c['likes_count']??0)) ?></td>
            <td><span class="badge badge--soft"><?= e($c['status']??'') ?></span></td>
            <td>
                <div class="btn-row" style="flex-wrap:wrap">
                    <form method="post" action="<?= e(url('admin_content_comment_status')) ?>"><?= csrf_field() ?><input type="hidden" name="comment_id" value="<?= $cid ?>"><input type="hidden" name="status" value="approved"><button class="btn btn--ghost btn--xs" type="submit">تأیید</button></form>
                    <form method="post" action="<?= e(url('admin_content_comment_status')) ?>"><?= csrf_field() ?><input type="hidden" name="comment_id" value="<?= $cid ?>"><input type="hidden" name="status" value="pending"><button class="btn btn--ghost btn--xs" type="submit">انتظار</button></form>
                    <form method="post" action="<?= e(url('admin_content_comment_delete')) ?>" onsubmit="return confirm('حذف؟')"><?= csrf_field() ?><input type="hidden" name="comment_id" value="<?= $cid ?>"><button class="btn btn--ghost btn--xs" style="color:#BE123C" type="submit">حذف</button></form>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
