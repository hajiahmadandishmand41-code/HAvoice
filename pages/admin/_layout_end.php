<?php
/**
 * HAvoice Admin — پایان لایه‌ی مشترک پنل مدیریت
 */
?>
    </div><!-- /.admin-main -->
</div><!-- /.admin-layout -->
<script>
(function(){
    'use strict';
    var toggle = document.querySelector('[data-admin-toggle]');
    var sidebar = document.getElementById('admin-sidebar');
    var overlay = document.getElementById('admin-overlay');
    if (!toggle || !sidebar) return;
    var setOpen = function(open) {
        document.body.classList.toggle('admin-nav-open', open);
        if (overlay) overlay.hidden = !open;
    };
    toggle.addEventListener('click', function(){ setOpen(!document.body.classList.contains('admin-nav-open')); });
    if (overlay) overlay.addEventListener('click', function(){ setOpen(false); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') setOpen(false); });
    window.addEventListener('resize', function(){ if(window.innerWidth>940) setOpen(false); });
})();
</script>
