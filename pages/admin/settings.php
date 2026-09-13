<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$settings = admin_settings();
$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<form class="admin-form" method="post" action="<?= e(url('admin_settings_save')) ?>">
<?= csrf_field() ?>
<div class="admin-card"><h2>اطلاعات هویتی</h2>
<div class="field"><label>نام مدرس</label><input class="input" type="text" name="name" value="<?= e($settings['name'] ?? HA_NAME) ?>"></div>
<div class="field"><label>عنوان</label><input class="input" type="text" name="tagline" value="<?= e($settings['tagline'] ?? HA_TAGLINE) ?>"></div>
<div class="field"><label>ایمیل</label><input class="input" type="email" name="email" value="<?= e($settings['email'] ?? HA_EMAIL) ?>" dir="ltr"></div>
<div class="field"><label>تلفن</label><input class="input" type="text" name="phone" value="<?= e($settings['phone'] ?? HA_PHONE) ?>" dir="ltr"></div>
</div>

<div class="admin-card"><h2>متن‌های سایت</h2>
<div class="field"><label>معرفی پاورقی</label><textarea class="input" name="footer_about" rows="3"><?= e($settings['footer_about'] ?? '') ?></textarea></div>
<div class="field"><label>متن بنر CTA — عنوان</label><input class="input" type="text" name="cta_title" value="<?= e($settings['cta_title'] ?? '') ?>"></div>
<div class="field"><label>متن بنر CTA — متن</label><textarea class="input" name="cta_text" rows="2"><?= e($settings['cta_text'] ?? '') ?></textarea></div>
<div class="field"><label>ساعات کاری</label><input class="input" type="text" name="work_hours" value="<?= e($settings['work_hours'] ?? '') ?>"></div>
</div>

<div class="admin-card"><h2>شبکه‌های اجتماعی</h2>
<div class="field"><label>تلگرام URL</label><input class="input" type="url" name="social_telegram" value="<?= e($settings['social_telegram'] ?? '') ?>" dir="ltr"></div>
<div class="field"><label>اینستاگرام URL</label><input class="input" type="url" name="social_instagram" value="<?= e($settings['social_instagram'] ?? '') ?>" dir="ltr"></div>
<div class="field"><label>یوتیوب URL</label><input class="input" type="url" name="social_youtube" value="<?= e($settings['social_youtube'] ?? '') ?>" dir="ltr"></div>
</div>

<div class="admin-form-actions"><button class="btn btn--primary" type="submit">ذخیره تنظیمات</button></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>

cat > pages/admin/settings_save.php << 'EOF'
<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin(); if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_settings'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_settings')); }
$fields = ['name','tagline','email','phone','footer_about','cta_title','cta_text','work_hours','social_telegram','social_instagram','social_youtube'];
$settings = [];
foreach ($fields as $f) { $settings[$f] = trim((string)($_POST[$f] ?? '')); }
admin_store('settings', $settings);
flash('success','تنظیمات ذخیره شد.'); redirect(url('admin_settings'));
