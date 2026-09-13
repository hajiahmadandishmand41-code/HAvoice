<?php
/**
 * HAvoice 2.0 — جستجو در تمام محتوا
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$raw   = param('q');
$query = trim($raw);
$limit = 20;
$articleResults = [];
$lessonResults  = [];
$bookResults    = [];
$researchResults= [];
$mediaResults   = [];

if ($query!=='') {
    $articleResults = search_articles($query, data('articles'), $limit);
    // درس‌ها
    foreach(course_lesson_index() as $item){
        $hay = normalize_persian($item['lesson']['title'].' '.($item['lesson']['goal']??'').' '.article_text_index(['blocks'=>(array)($item['lesson']['blocks']??[])]));
        $words = preg_split('/\s+/', normalize_persian($query)) ?: [];
        $hit=false;
        foreach($words as $w){ if(mb_strlen($w,'UTF-8')>=2 && mb_strpos($hay,$w,0,'UTF-8')!==false){ $hit=true; break; } }
        if($hit) $lessonResults[] = ['type'=>'lesson','slug'=>$item['lesson']['slug'],'title'=>$item['lesson']['title'],'text'=>(string)($item['lesson']['goal']??''),'tag'=>$item['course']['title']?? $item['stage']['title']??''];
        if(count($lessonResults)>=5) break;
    }
    // کتاب‌ها
    foreach(books() as $b){
        $hay = normalize_persian($b['title'].' '.$b['author'].' '.$b['excerpt'].' '.implode(' ',$b['tags']??[]));
        $hit=false; foreach(preg_split('/\s+/', normalize_persian($query))?:[] as $w){ if(mb_strlen($w,'UTF-8')>=2 && mb_strpos($hay,$w,0,'UTF-8')!==false){ $hit=true; break; } }
        if($hit) $bookResults[]=$b;
        if(count($bookResults)>=4) break;
    }
    // پژوهش
    foreach(research_items() as $r){
        $hay = normalize_persian($r['title'].' '.$r['summary']);
        $hit=false; foreach(preg_split('/\s+/', normalize_persian($query))?:[] as $w){ if(mb_strlen($w,'UTF-8')>=2 && mb_strpos($hay,$w,0,'UTF-8')!==false){ $hit=true; break; } }
        if($hit) $researchResults[]=$r;
        if(count($researchResults)>=4) break;
    }
    // مدیا
    foreach(media_items() as $m){
        $hay = normalize_persian($m['title'].' '.$m['excerpt']);
        $hit=false; foreach(preg_split('/\s+/', normalize_persian($query))?:[] as $w){ if(mb_strlen($w,'UTF-8')>=2 && mb_strpos($hay,$w,0,'UTF-8')!==false){ $hit=true; break; } }
        if($hit) $mediaResults[]=$m;
        if(count($mediaResults)>=4) break;
    }
}

$total = count($articleResults)+count($lessonResults)+count($bookResults)+count($researchResults)+count($mediaResults);
$foundParts=[];
if($query!==''){
    if($articleResults) $foundParts[]= fa_num(count($articleResults)).' مقاله';
    if($lessonResults) $foundParts[]= fa_num(count($lessonResults)).' درس';
    if($bookResults) $foundParts[]= fa_num(count($bookResults)).' کتاب';
    if($researchResults) $foundParts[]= fa_num(count($researchResults)).' پژوهش';
    if($mediaResults) $foundParts[]= fa_num(count($mediaResults)).' ویدیو/صوت';
}
$foundLabel = $foundParts===[] ? 'هیچ نتیجه‌ای' : implode('، ',$foundParts);
$suggestions = ['مکث','تنفس','تکیه‌کلام','زبان بدن','اضطراب','مذاکره','هدف‌گذاری','ارتباط بدون خشونت','کار عمیق'];
?>

<section class="section section--tight">
    <div class="container">
        <form class="search-form" method="get" action="index.php" role="search">
            <input type="hidden" name="p" value="search">
            <label class="sr-only" for="q">عبارت جستجو</label>
            <div class="search-form__field">
                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.7-3.7"/></svg>
                <input id="q" type="search" name="q" value="<?= e($query) ?>" placeholder="کلیدواژه، موضوع یا برچسب… (جستجو در همه‌ی محتوا)" autocomplete="off" autofocus maxlength="100">
                <button class="btn btn--primary" type="submit">جستجو</button>
            </div>
        </form>

        <?php if($query===''): ?>
            <div class="search-help card">
                <h2>دنبال چه چیزی هستید؟</h2>
                <p>جستجو در عنوان، متن و برچسبِ همه‌ی محتوا: مقاله، درس، کتاب، پژوهش، ویدیو و صوت.</p>
                <ul class="chip-row chip-row--inline">
                    <?php foreach($suggestions as $s): ?><li><a class="chip" href="<?= e(url('search',['q'=>$s])) ?>"><?= e($s) ?></a></li><?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <p class="search-result-info"><strong><?= e($foundLabel) ?></strong> برای «<strong><?= e($query) ?></strong>» پیدا شد.</p>
            <?php if($total===0): ?>
                <?= empty_state('چیزی پیدا نشد','املای کوتاه‌تر یا کلمه‌ی دیگری را امتحان کنید. مثلاً «مکث»، «تنفس» یا «مذاکره».', url('articles'),'دیدن همه‌ی مقاله‌ها') ?>
            <?php endif; ?>

            <?php if($articleResults!==[]): ?>
                <div class="search-results mt-md">
                    <h2>مقالات</h2>
                    <?php foreach($articleResults as $item): ?>
                        <article class="result">
                            <a class="result__title" href="<?= e(url('article',['slug'=>$item['slug']])) ?>"><?= e($item['title']) ?></a>
                            <p class="result__kind"><span class="badge"><?= e($item['category']??'مقاله') ?></span> <span class="muted-sm"><?= e(minutes_label((int)($item['minutes']??5))) ?></span></p>
                            <p class="result__text"><?= excerpt_of($item,170) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if($lessonResults!==[]): ?>
                <div class="search-lessons sub-section">
                    <h2>درس‌ها</h2>
                    <ol class="lesson-list">
                        <?php foreach($lessonResults as $g): ?>
                            <li class="lesson-item">
                                <a class="lesson-item__link" href="<?= e(url('lesson',['slug'=>$g['slug']])) ?>">
                                    <span class="lesson-item__num">درس</span>
                                    <span class="lesson-item__body"><span class="lesson-item__title"><?= e($g['title']) ?></span><span class="lesson-item__goal"><?= e($g['text']) ?></span></span>
                                    <span class="lesson-item__side"><span class="chip chip--ghost"><?= e($g['tag']) ?></span></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            <?php endif; ?>

            <?php if($bookResults!==[]): ?>
                <div class="sub-section">
                    <h2 class="sub-section__title">کتاب‌ها</h2>
                    <div class="grid grid--2">
                        <?php foreach($bookResults as $b): ?><div><?= book_card($b) ?></div><?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($researchResults!==[]): ?>
                <div class="sub-section">
                    <h2 class="sub-section__title">پژوهش‌ها</h2>
                    <div class="grid grid--2">
                        <?php foreach($researchResults as $r): ?><div><?= research_card($r) ?></div><?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($mediaResults!==[]): ?>
                <div class="sub-section">
                    <h2 class="sub-section__title">ویدیو و صوت</h2>
                    <div class="grid grid--3">
                        <?php foreach($mediaResults as $m): ?>
                            <div><?= ($m['type']==='video')? video_card($m): audio_card($m) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
