<?php
/**
 * HAvoice — صفحه‌ی ۴۰۴ (نشانی پیدا نشد).
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$latest = latest_articles(3);
?>

<section class="section">
    <div class="container container--narrow not-found">
        <p class="not-found__code">۴۰۴</p>
        <h1>صفحه‌ای که دنبال آن بودید اینجا نیست</h1>
        <p class="lead">ممکن است نشانی را اشتباه تایپ کرده باشید یا محتوا جابه‌جا شده باشد. از راه‌های پایین مسیرتان را پیدا کنید.</p>

        <div class="btn-row btn-row--center">
            <a class="btn btn--primary" href="<?= e(url('home')) ?>">صفحه‌ی اصلی</a>
            <a class="btn btn--ghost" href="<?= e(url('course')) ?>">مسیر آموزشی</a>
            <a class="btn btn--ghost" href="<?= e(url('search')) ?>">جستجو در مقاله‌ها</a>
        </div>

<?php if ($latest !== []): ?>
        <div class="not-found__latest">
            <h2>مقاله‌های تازه</h2>
            <ul class="link-list">
<?php foreach ($latest as $a): ?>
                <li>
                    <a href="<?= e(url('article', ['slug' => $a['slug']])) ?>"><?= e($a['title']) ?></a>
                    <span class="muted-sm"><?= e(minutes_label((int) ($a['minutes'] ?? 5))) ?></span>
                </li>
<?php endforeach; ?>
            </ul>
        </div>
<?php endif; ?>
    </div>
</section>
