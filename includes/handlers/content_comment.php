<?php
/**
 * HAvoice — پردازش Comment + Reply
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

$next = ha_safe_next((string)($_POST['next'] ?? ha_current_request_url()));
$backUrl = $next !== '' ? $next : url('home');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect($backUrl);
}

if (!csrf_verify()) {
    flash('error', 'نشست شما تمام شده است. دوباره تلاش کنید.');
    redirect($backUrl);
}

if (trim((string)($_POST['website'] ?? '')) !== '') {
    // honeypot — spam
    redirect($backUrl);
}

if (!auth_is_logged_in()) {
    flash('error', 'برای ثبت نظر ابتدا ثبت‌نام کنید.');
    redirect(url('register', ['next'=>$next]));
}

// rate limit: 10 comments per minute, min interval 5 sec
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$limit = ha_rate_limit_acquire('content_comment', $ip, 10, 60, 5);
if (!$limit['ok']) {
    flash('error', 'تعداد نظرات زیاد است. '.fa_num($limit['retry']).' ثانیه صبر کنید.');
    redirect($backUrl . '#comments');
}

$user = auth_current_user();
$userId = $user ? (string)($user['id'] ?? '') : '';
$cType = strtolower(trim((string)($_POST['content_type'] ?? '')));
$cSlug = slugify((string)($_POST['content_slug'] ?? ''));
$parentIdRaw = trim((string)($_POST['parent_id'] ?? '0'));
$parentId = $parentIdRaw !== '' && $parentIdRaw !== '0' ? (int)$parentIdRaw : null;
$body = trim((string)($_POST['body'] ?? ''));

if (!ha_valid_content_type($cType) || $cSlug === '') {
    flash('error', 'محتوای نامعتبر.');
    redirect($backUrl);
}

if ($body === '') {
    flash('error', 'متن نظر خالی است.');
    redirect($backUrl . '#comments');
}

$res = ha_comment_add($userId, $cType, $cSlug, $parentId, $body, $ip);
if (!$res['ok']) {
    $msg = 'خطا در ثبت نظر.';
    if (($res['error'] ?? '') === 'validation') {
        $msg = implode(' ', (array)($res['messages'] ?? []));
    } elseif (($res['error'] ?? '') === 'duplicate') {
        $msg = 'نظر تکراری است — همین نظر را چند دقیقه پیش ثبت کرده‌اید.';
    } elseif (($res['error'] ?? '') === 'parent') {
        $msg = 'پاسخی که به آن جواب می‌دهید یافت نشد.';
    }
    flash('error', $msg);
    redirect($backUrl . '#comments');
}

flash('success', $parentId ? 'پاسخ شما ثبت شد.' : 'نظر شما ثبت شد.');
redirect($backUrl . '#comments');
