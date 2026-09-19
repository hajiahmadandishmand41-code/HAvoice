<?php
if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect(url('admin_board'));
if (!csrf_verify()) { flash('error','نشست تمام شده.'); redirect(url('admin_board')); }
auth_require_admin();
$postId = (int)($_POST['post_id'] ?? 0);
if ($postId <=0) { flash('error','پست نامعتبر.'); redirect(url('admin_board')); }
$old=null; try { if (function_exists('ha_board_post_find')) $old=ha_board_post_find($postId); } catch (Throwable $e) {}
$ok = ha_board_post_delete($postId);
if ($ok && is_array($old)) { if (!empty($old['image'])) ha_upload_delete((string)$old['image']); if (!empty($old['media_url'])) { ha_media_delete((string)$old['media_url']); ha_upload_delete((string)$old['media_url']); } }
flash($ok?'success':'error', $ok?'پست حذف شد.':'خطا در حذف.');
redirect(url('admin_board'));
