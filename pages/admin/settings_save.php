<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_settings'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_settings')); }
$fields = ['name','tagline','email','phone','footer_about','banner_lead','cta_title','cta_text','work_hours','social_telegram','social_instagram','social_facebook','social_whatsapp'];
$settings = [];
foreach ($fields as $f) { $settings[$f] = trim((string)($_POST[$f] ?? '')); }
if ($settings['email'] !== '' && !filter_var($settings['email'], FILTER_VALIDATE_EMAIL)) {
    flash('error','ایمیل تنظیمات معتبر نیست.');
    redirect(url('admin_settings'));
}
/* URLهای اجتماعی — فقط http/https پذیرفته می‌شود */
foreach (['social_telegram','social_instagram','social_facebook','social_whatsapp'] as $sf) {
    if ($settings[$sf] !== '' && !preg_match('#^https?://[^\s]+$#i', $settings[$sf])) {
        flash('error','نشانی شبکه‌ی اجتماعی باید با http:// یا https:// شروع شود.');
        redirect(url('admin_settings'));
    }
}
if (!admin_store('settings', $settings)) {
    flash('error','ذخیره‌سازی تنظیمات ناموفق بود؛ storage قابل نوشتن نیست.');
    redirect(url('admin_settings'));
}
flash('success','تنظیمات ذخیره شد.'); redirect(url('admin_settings')); 
