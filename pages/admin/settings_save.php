<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_settings'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_settings')); }
$fields = ['name','tagline','email','phone','footer_about','cta_title','cta_text','work_hours','social_telegram','social_instagram','social_youtube'];
$settings = [];
foreach ($fields as $f) { $settings[$f] = trim((string)($_POST[$f] ?? '')); }
admin_store('settings', $settings);
flash('success','تنظیمات ذخیره شد.'); redirect(url('admin_settings'));
