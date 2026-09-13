<?php
/**
 * HAvoice — خروج از حساب.
 * خروج فقط با POST و توکنِ CSRF انجام می‌شود تا لینکِ جعلی نتواند
 * کاربر را بدونِ خواستِ او خارج کند.
 */

if (!defined('HA_ROOT')) {
    exit('دسترسی مستقیم ممنوع است.');
}

/* فقط POST */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect(url('account'));
}

/* CSRF لازم است حتی برای خروج (CSRF logout). */
if (!csrf_verify()) {
    flash('error', 'نشست شما تمام شده است. لطفاً دوباره تلاش کنید.');
    redirect(url('account'));
}

auth_logout();
redirect(url('home'));
