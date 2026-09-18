<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_audios'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_audios')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
if ($slug === '') { flash('error','نامک صوت مشخص نیست.'); redirect(url('admin_audios')); }
require HA_ROOT . '/pages/admin/_helpers.php';
/* مسیرها را پیش از حذفِ رکورد برمی‌داریم تا بعد از موفقیت، فایلِ آپلودی
   هم پاک شود و در uploads/ بی‌ارجاع نماند. */
$doomed = admin_find_media($slug, 'audio');
$paths  = [admin_existing_path($doomed, 'url')];
if (!repo_delete_media('audio', $slug)) { flash('error','حذف صوت ناموفق بود.'); redirect(url('admin_audios')); }
foreach ($paths as $p) { ha_upload_discard($p); }
flash('success','صوت حذف شد.'); redirect(url('admin_audios'));
