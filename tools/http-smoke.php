<?php
/**
 * Simulated HTTP smoke against routes (guest vs auth).
 */
define('HA_ROOT', dirname(__DIR__));
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = '';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';

require HA_ROOT . '/config/config.php';
require HA_ROOT . '/includes/helpers.php';
require HA_ROOT . '/includes/icons.php';
require HA_ROOT . '/includes/content.php';
require HA_ROOT . '/includes/auth.php';
require HA_ROOT . '/includes/uploads.php';
require HA_ROOT . '/includes/ui.php';
require HA_ROOT . '/includes/meta.php';
require HA_ROOT . '/includes/comments.php';

function ha_sim(string $p, string $slug = '', bool $loggedIn = false): array
{
    $route = slugify($p);
    if ($route === '') {
        $route = 'home';
    }
    if (!route_exists($route)) {
        return ['status' => 404, 'route' => $route];
    }
    $status = 200;
    if ($route === 'lesson' && $slug !== '' && course_find_lesson($slug) === null) {
        $status = 404;
    } elseif ($route === 'course' && $slug !== '' && find_course($slug) === null) {
        $status = 404;
    } elseif ($route === 'article' && $slug !== '' && find_by_slug(all_articles_sorted(), $slug)[1] === null) {
        $status = 404;
    }
    if ($status === 200 && route_meta($route, 'auth', false) === true && !$loggedIn) {
        $status = 302;
    }
    if ($status === 200 && route_meta($route, 'admin', false) === true && !$loggedIn) {
        $status = 302;
    }
    $file = HA_ROOT . '/pages/' . route_meta($route, 'file', '404.php');
    if ($status === 200 && !is_file($file)) {
        $status = 404;
    }
    return ['status' => $status, 'route' => $route, 'file' => $file];
}

$cases = [
    ['home', '', false, 200],
    ['courses', '', false, 200],
    ['videos', '', false, 200],
    ['audios', '', false, 200],
    ['books', '', false, 200],
    ['articles', '', false, 200],
    ['about', '', false, 200],
    ['contact', '', false, 200],
    ['login', '', false, 200],
    ['search', '', false, 200],
    ['course', 'public-speaking-fundamentals', false, 302],
    ['lesson', 'breathing-foundations', false, 302],
    ['exercises', '', false, 302],
    ['admin', '', false, 302],
    ['admin_videos', '', false, 302],
    ['lesson', 'no-such-lesson-xyz', false, 404],
    ['course', 'no-such-course-xyz', false, 404],
    ['course', 'public-speaking-fundamentals', true, 200],
    ['lesson', 'breathing-foundations', true, 200],
    ['lesson', 'impromptu-sixty', true, 200],
    ['exercises', '', true, 200],
];

$fail = 0;
foreach ($cases as [$p, $slug, $li, $want]) {
    $r = ha_sim($p, $slug, $li);
    $label = $p . ($slug !== '' ? "/$slug" : '') . ($li ? ' [auth]' : ' [guest]');
    if ($r['status'] === $want) {
        echo "PASS $label → {$r['status']}\n";
    } else {
        echo "FAIL $label want $want got {$r['status']}\n";
        $fail++;
    }
}
echo $fail ? "HTTP-SIM FAIL $fail\n" : "HTTP-SIM PASS\n";
exit($fail > 0 ? 1 : 0);
