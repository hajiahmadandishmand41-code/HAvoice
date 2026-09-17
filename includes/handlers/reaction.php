<?php
/**
 * HAvoice — پردازش Reaction
 * امنیت: CSRF, Validation, Rate Limit, یک Reaction per کاربر
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
    redirect($backUrl);
}

if (!auth_is_logged_in()) {
    flash('error', 'برای واکنش ابتدا ثبت‌نام کنید.');
    redirect(url('register', ['next'=>$next]));
}

// rate limit
$limit = ha_rate_limit_acquire('reaction', (string)($_SERVER['REMOTE_ADDR'] ?? ''), 60, 60, 1);
if (!$limit['ok']) {
    flash('error', 'تعداد درخواست زیاد است. کمی صبر کنید.');
    redirect($backUrl);
}

$user = auth_current_user();
$userId = $user ? (string)($user['id'] ?? '') : '';
$cType = strtolower(trim((string)($_POST['content_type'] ?? '')));
$cSlug = slugify((string)($_POST['content_slug'] ?? ''));
$rType = strtolower(trim((string)($_POST['reaction_type'] ?? '')));

if (!ha_valid_content_type($cType) || $cSlug === '') {
    flash('error', 'محتوای نامعتبر.');
    redirect($backUrl);
}

if ($rType === 'remove') {
    ha_reaction_delete($userId, $cType, $cSlug);
    flash('success', 'واکنش شما حذف شد.');
    redirect($backUrl . '#comments');
}

if (!ha_valid_reaction($rType)) {
    flash('error', 'نوع واکنش نامعتبر است.');
    redirect($backUrl);
}

$existing = ha_reaction_get_user($userId, $cType, $cSlug);
if ($existing && (string)($existing['reaction_type'] ?? '') === $rType) {
    // toggle off if same
    ha_reaction_delete($userId, $cType, $cSlug);
    flash('success', 'واکنش شما حذف شد.');
} else {
    ha_reaction_set($userId, $cType, $cSlug, $rType);
    $label = ha_reaction_types()[$rType]['label'] ?? $rType;
    flash('success', 'واکنش «'.$label.'» ثبت شد.');
}

redirect($backUrl . '#comments');
