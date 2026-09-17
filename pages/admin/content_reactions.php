<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$flash = flash();
$type = param('type','');
$slug = param('slug','');

$all=[];
if (db_ready() && db_table_exists('ha_content_reactions')) {
    $sql='SELECT r.*, u.name FROM ha_content_reactions r LEFT JOIN ha_users u ON u.id=r.user_id';
    $where=[]; $params=[];
    if ($type!=='' && ha_valid_content_type($type)) { $where[]='r.content_type=?'; $params[]=$type; }
    if ($slug!=='') { $where[]='r.content_slug=?'; $params[]=$slug; }
    if ($where!==[]) $sql.=' WHERE '.implode(' AND ',$where);
    $sql.=' ORDER BY r.created_at DESC LIMIT 300';
    $all=db_all($sql,$params);
} else {
    $dir=storage_dir('interactions/reactions');
    foreach (ha_dir_files($dir,'json') as $file) {
        $raw=@file_get_contents($file);
        $data=json_decode((string)$raw,true);
        if (!is_array($data)) continue;
        foreach ($data as $r) {
            if ($type!=='' && ($r['content_type']??'')!==$type) continue;
            if ($slug!=='' && ($r['content_slug']??'')!==$slug) continue;
            $all[]=$r;
        }
    }
    usort($all, fn($a,$b)=> strcmp((string)($b['created_at']??''), (string)($a['created_at']??'')));
    $all=array_slice($all,0,300);
}
?>
<?php if ($flash !== [] && !empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><p><?= e($flash['message']) ?></p></div><?php endif; ?>

<div class="admin-card">
    <h2>واکنش‌های محتوای آموزشی — <?= fa_num(count($all)) ?></h2>
    <form method="get" class="admin-search" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem">
        <input type="hidden" name="p" value="admin_content_reactions">
        <select name="type" class="input" style="max-width:160px"><option value="">همه نوع‌ها</option><?php foreach (ha_content_types() as $t): ?><option value="<?= e($t) ?>" <?= $type===$t?'selected':'' ?>><?= e($t) ?></option><?php endforeach; ?></select>
        <input class="input" type="text" name="slug" value="<?= e($slug) ?>" placeholder="slug" style="max-width:180px">
        <button class="btn btn--ghost btn--sm" type="submit">فیلتر</button>
    </form>

    <?php if ($all===[]): ?><p class="muted-sm">واکنشی نیست.</p>
    <?php else: ?>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>نوع/اسلاگ</th><th>کاربر</th><th>واکنش</th><th>تاریخ</th><th>عملیات</th></tr></thead><tbody>
    <?php foreach ($all as $r): ?>
        <tr>
            <td><small><?= e($r['content_type']??'') ?>/<?= e($r['content_slug']??'') ?></small></td>
            <td><?= e($r['name']??$r['user_id']??'') ?></td>
            <td><?= e($r['reaction_type']??'') ?></td>
            <td class="muted-sm"><?= e($r['created_at']??'') ?></td>
            <td>
                <form method="post" action="<?= e(url('admin_content_reaction_delete')) ?>" onsubmit="return confirm('حذف؟')"><?= csrf_field() ?><input type="hidden" name="content_type" value="<?= e($r['content_type']??'') ?>"><input type="hidden" name="content_slug" value="<?= e($r['content_slug']??'') ?>"><input type="hidden" name="user_id" value="<?= e($r['user_id']??'') ?>"><button class="btn btn--ghost btn--xs" style="color:#BE123C" type="submit">حذف</button></form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</div>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
