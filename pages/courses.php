<?php
/**
 * HAvoice 2.0 — فهرست دوره‌ها
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$all = courses();
$cats = categories();
$filter = param('category');
if ($filter!=='' && find_category($filter)===null) $filter='';

$filtered = $filter ? array_values(array_filter($all, fn($c)=>($c['category']??'')=== $filter)) : $all;
?>
<section class="section section--tight">
    <div class="container">
        <nav class="filter-tabs" aria-label="فیلتر حوزه">
            <a class="chip<?= $filter===''?' is-active':'' ?>" href="<?= e(url('courses')) ?>">همه‌ی دوره‌ها <span class="chip__n"><?= fa_num(count($all)) ?></span></a>
            <?php foreach($cats as $cat):
                $count = count(array_filter($all, fn($c)=>($c['category']??'')=== $cat['slug']));
                if($count===0) continue;
            ?>
                <a class="chip<?= $filter=== $cat['slug']?' is-active':'' ?>" href="<?= e(url('courses',['category'=>$cat['slug']])) ?>"><?= e($cat['title']) ?> <span class="chip__n"><?= fa_num($count) ?></span></a>
            <?php endforeach; ?>
        </nav>

        <?php if($filtered===[]): ?>
            <?= empty_state('دوره‌ای در این حوزه نیست','حوزه‌ی دیگری انتخاب کنید یا همه‌ی دوره‌ها را ببینید.', url('courses'),'دیدن همه') ?>
        <?php else: ?>
            <div class="grid grid--3">
                <?php foreach($filtered as $course): ?>
                    <div class="reveal"><?= course_card($course) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card" style="margin-top:2rem; background:var(--brand-soft); border-color:transparent">
            <h2>چطور یک حوزه‌ی جدید اضافه می‌شود؟</h2>
            <p class="muted-sm">کافی است یک قلم به <code>data/categories.php</code> و یک دوره به <code>data/course.php</code> اضافه کنید؛ فهرست‌ها، فیلترها، جستجو و نقشه‌ی سایت خودکار به‌روز می‌شوند — بدونِ بازنویسیِ معماری.</p>
        </div>
    </div>
</section>
