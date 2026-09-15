<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
require HA_ROOT . '/pages/admin/_layout_start.php';
$settings = admin_settings();
$flash = flash();
?>
<?php if (!empty($flash['message'])): ?><div class="alert alert--<?= e($flash['type']==='success'?'success':'error') ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<form class="admin-form" method="post" action="<?= e(url('admin_settings_save')) ?>">
<?= csrf_field() ?>
<div class="admin-card"><h2>اطلاعات هویتی</h2>
<div class="field"><label>نام مدرس</label><input class="input" type="text" name="name" value="<?= e($settings['name'] ?? ha_site_name()) ?>"></div>
<div class="field"><label>عنوان</label><input class="input" type="text" name="tagline" value="<?= e($settings['tagline'] ?? ha_site_tagline()) ?>"></div>
<div class="field"><label>ایمیل</label><input class="input" type="email" name="email" value="<?= e($settings['email'] ?? HA_EMAIL) ?>" dir="ltr"></div>
<div class="field"><label>تلفن</label><input class="input" type="text" name="phone" value="<?= e($settings['phone'] ?? HA_PHONE) ?>" dir="ltr"></div>
</div>

<div class="admin-card"><h2>توضیحات سایت</h2>
<div class="field"><label>معرفی پاورقی</label><textarea class="input" name="footer_about" rows="3"><?= e($settings['footer_about'] ?? '') ?></textarea></div>
<div class="field"><label>ساعات کاری (نمایش در صفحه‌ی تماس)</label><input class="input" type="text" name="work_hours" value="<?= e($settings['work_hours'] ?? '') ?>"></div>
</div>

<div class="admin-card"><h2>شبکه‌های اجتماعی</h2>
<p class="muted-sm">خالی بماند ⇒ مقدارِ پیش‌فرضِ فایلِ داده استفاده می‌شود.</p>
<div class="field"><label>تلگرام URL</label><input class="input" type="url" name="social_telegram" value="<?= e($settings['social_telegram'] ?? '') ?>" dir="ltr"></div>
<div class="field"><label>اینستاگرام URL</label><input class="input" type="url" name="social_instagram" value="<?= e($settings['social_instagram'] ?? '') ?>" dir="ltr"></div>
<div class="field"><label>فیسبوک URL</label><input class="input" type="url" name="social_facebook" value="<?= e($settings['social_facebook'] ?? '') ?>" dir="ltr"></div>
<div class="field"><label>واتساپ URL</label><input class="input" type="url" name="social_whatsapp" value="<?= e($settings['social_whatsapp'] ?? '') ?>" dir="ltr"></div>
</div>

<div class="admin-form-actions"><button class="btn btn--primary" type="submit">ذخیره تنظیمات</button></div>
</form>
<?php require HA_ROOT . '/pages/admin/_layout_end.php'; ?>
