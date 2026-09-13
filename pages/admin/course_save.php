<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
// Course save - courses are managed via data/course.php, not admin panel
auth_require_admin();
flash('error','دوره‌ها از طریق فایل data/course.php مدیریت می‌شوند.');
redirect(url('admin_courses'));
