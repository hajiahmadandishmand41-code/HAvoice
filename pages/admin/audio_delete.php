<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
auth_require_admin();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_audios'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_audios')); }
$slug = slugify((string)($_POST['slug'] ?? ''));
if ($slug === '') { flash('error','نامک صوت مشخص نیست.'); redirect(url('admin_audios')); }
$old=null; foreach (media_all() as $m) { if ((string)($m['type']??'')==='audio' && slugify((string)($m['slug']??''))===$slug) {$old=$m; break;}}
if (!repo_delete_media('audio', $slug)) { flash('error','حذف صوت ناموفق بود.'); redirect(url('admin_audios')); }
if ($old!==null && !empty($old['url'])) { ha_media_delete((string)$old['url']); ha_upload_delete((string)$old['url']); }
flash('success','صوت حذف شد.'); redirect(url('admin_audios'));
