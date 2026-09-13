<?php
/**
 * HAvoice 2.0 — 404
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$latest = latest_articles(3);
$cats = array_slice(categories(),0,4);
?>

<section class="section">
    <div class="container container--narrow not-found">
        <p class="not-found__code">۴۰۴</p>
        <h1>صفحه‌ای که دنبال آن بودید اینجا نیست</h1>
        <p class="lead">ممکن است نشانی را اشتباه تایپ کرده باشید یا محتوا جابه‌جا شده باشد.</p>

        <div class="btn-row btn-row--center">
            <a class="btn btn--primary" href="<?= e(url('home')) ?>">صفحه‌ی اصلی</a>
            <a class="btn btn--ghost" href="<?= e(url('courses')) ?>">دوره‌ها</a>
            <a class="btn btn--ghost" href="<?= e(url('search')) ?>">جستجو</a>
        </div>

        <div class="card mt-lg text-start">
            <h2>حوزه‌ها را ببینید</h2>
            <div class="chip-row mt-sm">
                <?php foreach($cats as $cat): ?><a class="chip" href="<?= e(url('category',['slug'=>$cat['slug']])) ?>"><?= e($cat['title']) ?></a><?php endforeach; ?>
                <a class="chip chip--soft" href="<?= e(url('courses')) ?>">همه‌ی دوره‌ها</a>
            </div>
        </div>

        <?php if($latest!==[]): ?>
            <div class="not-found__latest">
                <h2>مقاله‌های تازه</h2>
                <ul class="link-list">
                    <?php foreach($latest as $a): ?>
                        <li><a href="<?= e(url('article',['slug'=>$a['slug']])) ?>"><?= e($a['title']) ?></a><span class="muted-sm"><?= e(minutes_label((int)($a['minutes']??5))) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>
