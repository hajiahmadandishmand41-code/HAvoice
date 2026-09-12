<?php
/**
 * HAvoice 2.0 — توابع کمکی مشترک (هسته‌ی سبک، بدون وابستگی)
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* ------------------------------------------------------------------ */
/*  داده‌ها                                                            */
/* ------------------------------------------------------------------ */

function data(string $name): array
{
    static $cache = [];
    $name = (string) preg_replace('/[^a-z_]/', '', strtolower($name));
    if (!isset($cache[$name])) {
        $file = HA_ROOT . '/data/' . $name . '.php';
        $cache[$name] = is_file($file) ? (array) require $file : [];
    }
    return $cache[$name];
}

function nav_items(): array
{
    $routes = [
        'home'      => 'خانه',
        'courses'   => 'دوره‌ها',
        'articles'  => 'مقالات',
        'videos'    => 'ویدیو',
        'audios'    => 'پادکست',
        'books'     => 'کتاب‌ها',
        'research'  => 'پژوهش',
        'exercises' => 'تمرین‌ها',
        'about'     => 'درباره مدرس',
        'contact'   => 'تماس',
    ];
    $out = [];
    foreach ($routes as $route => $label) {
        $out[] = ['route' => $route, 'label' => $label, 'url' => url($route)];
    }
    return $out;
}

function nav_mega(): array
{
    $cats = data('categories');
    $groups = [];
    foreach ($cats as $cat) {
        $groups[] = [
            'label' => $cat['title'],
            'url'   => url('category', ['slug' => $cat['slug']]),
            'slug'  => $cat['slug'],
        ];
    }
    return $groups;
}

/* ------------------------------------------------------------------ */
/*  آدرس‌ها                                                            */
/* ------------------------------------------------------------------ */

function base_path(): string
{
    return HA_PRETTY_URLS ? rtrim(HA_BASE_PATH, '/') : '';
}

function url(string $route = 'home', array $params = []): string
{
    $params = array_filter($params, static function ($v) {
        return $v !== null && $v !== '';
    });
    if (!HA_PRETTY_URLS) {
        $query = ['p' => $route] + $params;
        return 'index.php?' . http_build_query($query);
    }
    $slug = isset($params['slug']) ? '/' . rawurlencode((string) $params['slug']) : '';
    unset($params['slug']);
    $prefix = route_meta($route, 'pretty', $route);
    $path   = ($route === 'home') ? '' : '/' . $prefix . $slug;
    $query  = $params ? '?' . http_build_query($params) : '';
    return (base_path() ?: '') . '/' . ltrim($path . $query, '/');
}

function asset(string $file): string
{
    $path = ltrim($file, '/');
    return (HA_PRETTY_URLS ? base_path() . '/' : '') . $path . '?v=' . HA_VERSION;
}

/* ------------------------------------------------------------------ */
/*  متادیتای مسیرها                                                   */
/* ------------------------------------------------------------------ */

function routes(): array
{
    static $table = null;
    if ($table !== null) {
        return $table;
    }
    $table = [
        'home'      => ['file' => 'home.php',      'pretty' => 'home',      'title' => 'خانه'],
        'courses'   => ['file' => 'courses.php',   'pretty' => 'courses',   'title' => 'دوره‌ها'],
        'course'    => ['file' => 'course.php',    'pretty' => 'course',    'title' => 'دوره'],
        'lesson'    => ['file' => 'lesson.php',    'pretty' => 'lesson',    'title' => 'درس'],
        'articles'  => ['file' => 'articles.php',  'pretty' => 'articles',  'title' => 'مقالات'],
        'article'   => ['file' => 'article.php',   'pretty' => 'articles',  'title' => 'مقاله'],
        'videos'    => ['file' => 'videos.php',    'pretty' => 'videos',    'title' => 'ویدیوهای آموزشی'],
        'audios'    => ['file' => 'audios.php',    'pretty' => 'audios',    'title' => 'پادکست و صوت'],
        'books'     => ['file' => 'books.php',     'pretty' => 'books',     'title' => 'کتاب‌ها'],
        'research'  => ['file' => 'research.php',  'pretty' => 'research',  'title' => 'تحقیقات'],
        'category'  => ['file' => 'category.php',  'pretty' => 'category',  'title' => 'حوزه آموزشی'],
        'exercises' => ['file' => 'exercises.php', 'pretty' => 'exercises', 'title' => 'تمرین‌ها'],
        'tips'      => ['file' => 'tips.php',      'pretty' => 'tips',      'title' => 'نکات کوتاه'],
        'about'     => ['file' => 'about.php',     'pretty' => 'about',     'title' => 'درباره مدرس'],
        'contact'   => ['file' => 'contact.php',   'pretty' => 'contact',   'title' => 'تماس با ما', 'session' => true],
        'search'    => ['file' => 'search.php',    'pretty' => 'search',    'title' => 'جستجو'],
    ];
    return $table;
}

function route_meta(string $route, string $key, $default = null)
{
    $table = routes();
    return isset($table[$route][$key]) ? $table[$route][$key] : $default;
}

function route_exists(string $route): bool
{
    return isset(routes()[$route]);
}

/* ------------------------------------------------------------------ */
/*  امن‌سازی و خروجی                                                   */
/* ------------------------------------------------------------------ */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify($value): string
{
    $value = strtolower((string) $value);
    $value = preg_replace('/[^a-z0-9_\-]/', '', $value);
    return (string) $value;
}

function param(string $key, string $default = ''): string
{
    if (!isset($_GET[$key]) || !is_string($_GET[$key])) {
        return $default;
    }
    return trim(substr($_GET[$key], 0, 200)) ?: $default;
}

function fa_num($number): string
{
    return str_replace(
        ['0','1','2','3','4','5','6','7','8','9'],
        ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'],
        (string) $number
    );
}

function minutes_label(int $minutes): string
{
    return fa_num($minutes) . ' دقیقه';
}

function seconds_label(int $seconds): string
{
    if ($seconds < 60) return fa_num($seconds) . ' ثانیه';
    $m = intdiv($seconds, 60);
    $s = $seconds % 60;
    return $s ? fa_num($m) . ':' . str_pad(fa_num($s), 2, '۰', STR_PAD_LEFT) : fa_num($m) . ' دقیقه';
}

/* ------------------------------------------------------------------ */
/*  CSRF                                                             */
/* ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) return '';
    if (empty($_SESSION['ha_csrf'])) $_SESSION['ha_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['ha_csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
function csrf_verify(): bool
{
    $sent = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    return $sent !== '' && session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['ha_csrf']) && hash_equals($_SESSION['ha_csrf'], $sent);
}

/* ------------------------------------------------------------------ */
/*  رندر محتوا                                                        */
/* ------------------------------------------------------------------ */

function inline(string $text): string
{
    $text = e($text);
    $text = preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $text);
    $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/su', '<em>$1</em>', $text);
    $text = preg_replace('/`(.+?)`/su', '<code>$1</code>', $text);
    return $text;
}

function render_blocks(array $blocks, int $startLevel = 2): string
{
    if ($blocks === []) return '';
    $html = [];
    $heading = 0;
    foreach ($blocks as $block) {
        if (!is_array($block) || empty($block['type'])) continue;
        switch ($block['type']) {
            case 'h2':
            case 'h3':
                $level = $block['type'] === 'h3' ? 3 : max(2, $startLevel);
                $heading++;
                $html[] = '<h' . $level . ' id="sec-' . $heading . '">' . inline((string)($block['text'] ?? '')) . '</h' . $level . '>';
                break;
            case 'p':
                $html[] = '<p>' . inline((string)($block['text'] ?? '')) . '</p>';
                break;
            case 'lead':
                $html[] = '<p class="lead">' . inline((string)($block['text'] ?? '')) . '</p>';
                break;
            case 'ul':
                $html[] = '<ul class="rich-list">' . list_items((array)($block['items'] ?? [])) . '</ul>';
                break;
            case 'ol':
                $html[] = '<ol class="rich-list rich-list--num">' . list_items((array)($block['items'] ?? [])) . '</ol>';
                break;
            case 'quote':
                $html[] = '<blockquote class="quote">' . inline((string)($block['text'] ?? '')) . (empty($block['by']) ? '' : '<cite>' . e($block['by']) . '</cite>') . '</blockquote>';
                break;
            case 'tip':
                $html[] = '<aside class="callout callout--' . e($block['tone'] ?? 'tip') . '"><span class="callout__icon" aria-hidden="true">' . e(icon_name($block['tone'] ?? 'tip')) . '</span><div><h3>' . e($block['title'] ?? 'نکته') . '</h3><p>' . inline((string)($block['text'] ?? '')) . '</p></div></aside>';
                break;
            case 'drill':
                $html[] = render_drill($block);
                break;
            case 'table':
                $html[] = render_table((array)($block['rows'] ?? []), (array)($block['head'] ?? []));
                break;
            case 'audio':
                $html[] = render_audio_block($block);
                break;
            case 'video':
                $html[] = render_video_block($block);
                break;
            default:
                break;
        }
    }
    return implode("\n", $html);
}

function list_items(array $items): string
{
    $out = '';
    foreach ($items as $item) $out .= '<li>' . inline((string)$item) . '</li>';
    return $out;
}

function render_table(array $rows, array $head = []): string
{
    if ($rows === []) return '';
    $html = '<div class="table-wrap"><table class="compare"><thead><tr>';
    foreach ($head as $cell) $html .= '<th>' . inline((string)$cell) . '</th>';
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ((array)$row as $cell) $html .= '<td>' . inline((string)$cell) . '</td>';
        $html .= '</tr>';
    }
    return $html . '</tbody></table></div>';
}

function render_drill(array $block): string
{
    $items = '';
    foreach ((array)($block['items'] ?? []) as $item) $items .= '<li><span class="tick" aria-hidden="true">✓</span><span>' . inline((string)$item) . '</span></li>';
    return '<section class="drill"><header class="drill__head"><h3>' . e($block['title'] ?? 'تمرین') . '</h3>' . (empty($block['time']) ? '' : '<span class="chip chip--ghost">' . minutes_label((int)$block['time']) . '</span>') . '</header><ul class="drill__list">' . $items . '</ul>' . (empty($block['note']) ? '' : '<p class="drill__note">' . inline((string)$block['note']) . '</p>') . '</section>';
}

function render_audio_block(array $block): string
{
    $src = $block['src'] ?? '';
    $title = $block['title'] ?? 'فایل صوتی';
    if ($src === '') return '<div class="media-placeholder media-placeholder--audio"><span>🎧 ' . e($title) . '</span><small>فایل صوتی به‌زودی افزوده می‌شود</small></div>';
    return '<div class="audio-block"><p class="audio-block__title">' . e($title) . '</p><audio controls preload="none" src="' . e($src) . '"></audio></div>';
}

function render_video_block(array $block): string
{
    $src = $block['src'] ?? '';
    $title = $block['title'] ?? 'ویدیو';
    if ($src === '') return '<div class="media-placeholder media-placeholder--video"><span>▶ ' . e($title) . '</span><small>ویدیو به‌زودی افزوده می‌شود — ساختار آماده است</small></div>';
    // simple embed
    if (strpos($src, 'iframe') !== false) return '<div class="video-embed">' . $src . '</div>';
    return '<div class="video-block"><video controls preload="metadata" src="' . e($src) . '"></video><p class="muted-sm">' . e($title) . '</p></div>';
}

function icon_name(string $tone): string
{
    $map = ['tip'=>'✦','warn'=>'!','check'=>'✓','idea'=>'◎'];
    return $map[$tone] ?? '✦';
}

/* ------------------------------------------------------------------ */
/*  دسته‌ها                                                           */
/* ------------------------------------------------------------------ */

function categories(): array { return data('categories'); }
function find_category(string $slug): ?array {
    $slug = slugify($slug);
    foreach (categories() as $cat) if (slugify($cat['slug']??'')=== $slug) return $cat;
    return null;
}
function category_label(string $slug, string $fallback=''): string {
    $c = find_category($slug);
    return $c ? $c['title'] : ($fallback ?: $slug);
}

/* ------------------------------------------------------------------ */
/*  جستجو                                                            */
/* ------------------------------------------------------------------ */

function article_text_index(array $article): string
{
    $parts = [$article['title'] ?? '', $article['excerpt'] ?? '', $article['category'] ?? ''];
    $parts = array_merge($parts, (array)($article['tags'] ?? []));
    foreach ((array)($article['blocks'] ?? []) as $block) {
        if (!is_array($block)) continue;
        if (isset($block['text'])) $parts[] = $block['text'];
        $parts = array_merge($parts, (array)($block['items'] ?? []));
    }
    return mb_strtolower(strip_tags(implode(' ', $parts)), 'UTF-8');
}

function search_articles(string $query, array $articles, int $limit = 0): array
{
    $needle = normalize_persian($query);
    $words = array_values(array_filter(preg_split('/\s+/', $needle) ?: [], static function($w){ return mb_strlen($w,'UTF-8')>=2; }));
    if ($words===[]) return [];
    $results=[];
    foreach ($articles as $index=>$article) {
        $haystack = normalize_persian(article_text_index($article));
        $title = normalize_persian((string)($article['title']??''));
        $excerpt = normalize_persian((string)($article['excerpt']??''));
        $score=0; $matched=0;
        foreach ($words as $word) {
            $hit=false;
            if (mb_strpos($title,$word,0,'UTF-8')!==false){ $score+=12; $hit=true; }
            if (mb_strpos($excerpt,$word,0,'UTF-8')!==false){ $score+=6; $hit=true; }
            if (mb_strpos($haystack,$word,0,'UTF-8')!==false){ $score+=2; $hit=true; }
            if ($hit) $matched++;
        }
        if ($matched===0) continue;
        if ($matched===count($words)) $score+=5;
        $article['_score']=$score; $article['_index']=$index; $results[]=$article;
    }
    usort($results, static function(array $a,array $b){
        if ($a['_score']===$b['_score']) return $b['_index']<=>$a['_index'];
        return $b['_score']<=>$a['_score'];
    });
    return $limit>0?array_slice($results,0,$limit):$results;
}

function normalize_persian(string $text): string
{
    $text = mb_strtolower($text,'UTF-8');
    $map = ["\u{200c}"=>' ', 'ي'=>'ی','ك'=>'ک','َ'=>'','ِ'=>'','ُ'=>'',"\u{00A0}"=>' ',"\u{200f}"=>'' ];
    $text = strtr($text,$map);
    $text = preg_replace('/[^\p{L}\p{N}\s\-_]/u',' ',$text);
    return trim((string)preg_replace('/\s+/u',' ',(string)$text));
}

function excerpt_of(array $article, int $limit=150): string
{
    $text = (string)($article['excerpt'] ?? '');
    if ($text==='') foreach ((array)($article['blocks']??[]) as $block) if(isset($block['text'])){ $text=(string)$block['text']; break; }
    $text = strip_tags($text);
    return e(mb_strlen($text,'UTF-8')>$limit ? rtrim(mb_substr($text,0,$limit,'UTF-8')).'…' : $text);
}

function find_by_slug(array $items, string $slug): array
{
    $slug = slugify($slug);
    foreach ($items as $index=>$item) if (slugify((string)($item['slug']??''))=== $slug) return [(int)$index,$item];
    return [-1,null];
}

/* ------------------------------------------------------------------ */
/*  ابزارهای خرد                                                      */
/* ------------------------------------------------------------------ */

function active_route(): string { return $GLOBALS['HA_ROUTE'] ?? 'home'; }

function is_current(string $route): bool
{
    $current = active_route();
    if ($current=== $route) return true;
    return ($route==='articles' && $current==='article')
        || ($route==='courses' && $current==='course')
        || ($route==='courses' && $current==='lesson')
        || (($route==='videos' || $route==='audios' || $route==='books' || $route==='research') && $current==='category');
}

function fa_ordinal(int $n, int $total=0): string
{
    return $total>0 ? fa_num($n).' از '.fa_num($total) : fa_num($n);
}

function storage_dir(string $sub=''): string
{
    $dir = HA_ROOT . '/storage' . ($sub!=='' ? '/'.trim($sub,'/') : '');
    return $dir;
}

function redirect(string $to): void { if(!headers_sent()) header('Location: '.$to); exit; }

function flash(string $type='', string $message=''): array
{
    if (session_status()!==PHP_SESSION_ACTIVE) return [];
    if ($type!==''){ $_SESSION['ha_flash']=['type'=>$type,'message'=>$message]; return []; }
    $flash = $_SESSION['ha_flash'] ?? null; unset($_SESSION['ha_flash']); return is_array($flash)?$flash:[];
}
function old(string $key, string $default=''): string { return isset($_SESSION['ha_old'][$key])?(string)$_SESSION['ha_old'][$key]:$default; }
function old_set(array $values): void { if(session_status()===PHP_SESSION_ACTIVE) $_SESSION['ha_old']=$values; }
function old_clear(): void { if(session_status()===PHP_SESSION_ACTIVE) unset($_SESSION['ha_old']); }
function field_error(string $field, array $errors): string { return isset($errors[$field])?' is-invalid':''; }

function format_duration(int $seconds): string
{
    if ($seconds < 60) return fa_num($seconds) . ' ثانیه';
    $m = intdiv($seconds,60); $s = $seconds % 60;
    return $s ? fa_num($m) . ':' . str_pad(fa_num($s),2,'۰',STR_PAD_LEFT) : fa_num($m) . ' دقیقه';
}
