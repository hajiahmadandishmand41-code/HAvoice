<?php
/**
 * HAvoice 2.0 — نقشه‌ی سایت (XML) — تمام محتوا
 */

define('HA_ROOT', __DIR__);
require HA_ROOT . '/config/config.php';
require HA_ROOT . '/includes/helpers.php';
require HA_ROOT . '/includes/content.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^A-Za-z0-9.\-:_]/','', (string)$_SERVER['HTTP_HOST']) : 'localhost';
$root   = $scheme . '://' . $host . rtrim(HA_BASE_PATH,'/');

$urls=[];
$abs = static function(string $route, array $params=[]) use($root): string {
    $local = url($route,$params);
    if($local===''||$local[0]==='/') return $root.'/'.ltrim($local,'/');
    return $root.'/'.$local;
};

foreach (['home','courses','articles','videos','audios','books','research','exercises','tips','about','contact'] as $static){
    $priority = $static==='home'?'1.0':($static==='courses'?'0.9':'0.7');
    $urls[] = [$abs($static),$priority];
}
foreach (categories() as $cat) $urls[] = [$abs('category',['slug'=>$cat['slug']]),'0.7'];
foreach (courses() as $c) $urls[] = [$abs('course',['slug'=>$c['slug']]),'0.85'];
foreach (course_lesson_index() as $slug=>$item) $urls[] = [$abs('lesson',['slug'=>$slug]),'0.8'];
foreach (data('articles') as $article) $urls[] = [$abs('article',['slug'=>(string)$article['slug']]),'0.65'];
foreach (books() as $b) $urls[] = [$abs('books',['slug'=>$b['slug']]),'0.6'];
foreach (research_items() as $r) $urls[] = [$abs('research',['slug'=>$r['slug']]),'0.6'];

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$loc,$priority]){
    echo '  <url><loc>'.htmlspecialchars($loc,ENT_QUOTES,'UTF-8').'</loc><changefreq>weekly</changefreq><priority>'.$priority.'</priority></url>'."\n";
}
echo '</urlset>';
