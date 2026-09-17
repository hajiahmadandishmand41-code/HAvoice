/**
 * HAvoice — Board JS — Mobile-First, no console errors
 */
(function(){
  'use strict';
  function ready(fn){ if(document.readyState!=='loading') fn(); else document.addEventListener('DOMContentLoaded',fn); }
  ready(function(){
    var board = document.querySelector('.board-page');
    if(!board) return;

    // image preview
    var imgInput = document.getElementById('b-image');
    if(imgInput){
      imgInput.addEventListener('change', function(){
        var f=this.files && this.files[0];
        var existing = document.getElementById('b-image-preview');
        if(existing) existing.remove();
        if(!f) return;
        if(f.size > 8*1024*1024){
          alert('حجم تصویر زیاد است (حداکثر ۸ مگ)');
          this.value='';
          return;
        }
        var url = URL.createObjectURL(f);
        var img = document.createElement('img');
        img.id='b-image-preview';
        img.src=url;
        img.style.cssText='max-width:100%;max-height:240px;border-radius:12px;margin-top:.6rem;display:block';
        img.alt='پیش‌نمایش تصویر انتخاب‌شده';
        this.parentElement.appendChild(img);
        img.onload=function(){ URL.revokeObjectURL(url); };
      });
    }

    // Prevent empty posts before the request reaches the server.
    var postForm = board.querySelector('.board-form');
    if(postForm){
      postForm.addEventListener('submit', function(e){
        var body = (document.getElementById('b-body') || {}).value || '';
        var media = (document.getElementById('b-media') || {}).value || '';
        var hasImage = !!(imgInput && imgInput.files && imgInput.files.length);
        body = body.trim();
        media = media.trim();
        if(!body && !hasImage && !media){
          e.preventDefault();
          alert('حداقل یکی از متن، تصویر یا لینک رسانه را اضافه کنید.');
          var bodyInput = document.getElementById('b-body');
          if(bodyInput) bodyInput.focus();
          return;
        }
        if(media && !/^https:\/\//i.test(media)){
          e.preventDefault();
          alert('لینک رسانه باید با https:// شروع شود.');
          var mediaInput = document.getElementById('b-media');
          if(mediaInput) mediaInput.focus();
        }
      });
    }

    // reply toggle already handled by interactions.js, but ensure for board too
    document.addEventListener('click', function(e){
      var btn=e.target.closest('[data-ha-reply]');
      if(btn){
        var id=btn.getAttribute('data-ha-reply');
        var form=document.querySelector('[data-ha-reply-form="'+id+'"]');
        if(form){
          var hidden=form.hasAttribute('hidden');
          document.querySelectorAll('[data-ha-reply-form]').forEach(function(f){ f.setAttribute('hidden',''); });
          if(hidden){
            form.removeAttribute('hidden');
            var ta=form.querySelector('textarea');
            if(ta) ta.focus();
            form.scrollIntoView({behavior:'smooth',block:'center'});
          }
        }
      }
      var cancel=e.target.closest('[data-ha-cancel-reply]');
      if(cancel){
        var rf=cancel.closest('[data-ha-reply-form]');
        if(rf) rf.setAttribute('hidden','');
      }
    });

    // char counter for board post
    var ta=document.getElementById('b-body');
    if(ta){
      var help=ta.parentElement ? ta.parentElement.querySelector('.field__help') : null;
      if(help){
        var orig=help.textContent;
        ta.addEventListener('input', function(){
          var len=this.value.length;
          if(len>0){
            help.textContent=orig+' — '+len+' / 5000';
          } else help.textContent=orig;
        });
      }
    }
  });
})();