/**
 * HAvoice — Interactions JS (Reaction, Comment Reply toggle, a11y)
 * سبک، بدون وابستگی، Progressive Enhancement
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    var root = document.querySelector('[data-ha-interactions]');
    if (!root) return;

    // Reply toggle
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-ha-reply]');
      if (btn) {
        var id = btn.getAttribute('data-ha-reply');
        var form = document.querySelector('[data-ha-reply-form="' + id + '"]');
        if (form) {
          var hidden = form.hasAttribute('hidden');
          // close all other reply forms
          document.querySelectorAll('[data-ha-reply-form]').forEach(function (f) { f.setAttribute('hidden', ''); });
          if (hidden) {
            form.removeAttribute('hidden');
            var ta = form.querySelector('textarea');
            if (ta) { ta.focus(); }
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        }
        return;
      }
      var cancel = e.target.closest('[data-ha-cancel-reply]');
      if (cancel) {
        var rf = cancel.closest('[data-ha-reply-form]');
        if (rf) rf.setAttribute('hidden', '');
      }
    });

    // Auto-resize textarea
    document.querySelectorAll('.ha-comment-form__textarea, .ha-comment-form--reply textarea').forEach(function (ta) {
      ta.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 240) + 'px';
      });
    });

    // Character count hint (optional)
    var mainTA = document.getElementById('ha-comment-body');
    if (mainTA) {
      var help = mainTA.parentElement ? mainTA.parentElement.querySelector('.field__help') : null;
      if (help) {
        var orig = help.textContent;
        mainTA.addEventListener('input', function () {
          var len = this.value.length;
          if (len > 1500) {
            help.textContent = orig + ' — ' + (2000 - len) + ' نویسه باقی مانده';
          } else {
            help.textContent = orig;
          }
        });
      }
    }
  });
})();
