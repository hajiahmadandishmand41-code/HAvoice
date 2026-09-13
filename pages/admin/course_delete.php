<?php if (!defined('HA_ROOT')) exit('دسترسی مستقیم ممنوع است.');
// Course delete - courses are managed via data/course.php
auth_require_admin();
flash('error','دوره‌ها از طریق فایل data/course.php مدیریت می‌شوند.');
redirect(url('admin_courses'));
