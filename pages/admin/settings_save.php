<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_settings'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_settings')); }

/*
 * تنظیماتِ ضروری — فقط همین‌ها.
 * کلیدهای دیگرِ ذخیره‌شده‌ی قدیمی (مثل متن‌های بنر) حفظ می‌شوند ولی از
 * فرم پنهان‌اند؛ ذخیره با ادغام (merge) انجام می‌شود نه جایگزینی.
 */
$fields = ['name','tagline','email','phone','footer_about','work_hours','social_telegram','social_instagram','social_facebook','social_whatsapp'];
$settings = admin_settings();              // نگه‌داشتِ کلیدهای قدیمی
foreach ($fields as $f) { $settings[$f] = trim((string)($_POST[$f] ?? '')); }

if ($settings['email'] !== '' && !filter_var($settings['email'], FILTER_VALIDATE_EMAIL)) {
    flash('error','ایمیل تنظیمات معتبر نیست.');
    redirect(url('admin_settings'));
}
foreach (['social_telegram','social_instagram','social_facebook','social_whatsapp'] as $sf) {
    if ($settings[$sf] !== '' && !preg_match('#^https?://[^\s]+$#i', $settings[$sf])) {
        flash('error','نشانی شبکه‌ی اجتماعی باید با http:// یا https:// شروع شود.');
        redirect(url('admin_settings'));
    }
}

if (!auth_settings_save($settings)) {
    flash('error','ذخیره‌سازی تنظیمات ناموفق بود.');
    redirect(url('admin_settings'));
}
flash('success','تنظیمات ذخیره شد.');
redirect(url('admin_settings'));
