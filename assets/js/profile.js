/**
 * My Profile — section nav, password strength, hash routing
 */
(function () {
  'use strict';

  function initTabs() {
    var navLinks = document.querySelectorAll('.prf-nav-link[data-prf-tab]');
    var panels = document.querySelectorAll('.prf-panel[data-prf-tab]');
    if (!navLinks.length || !panels.length) return;

    function activate(name) {
      navLinks.forEach(function (btn) {
        var on = btn.getAttribute('data-prf-tab') === name;
        btn.classList.toggle('is-active', on);
        btn.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      panels.forEach(function (panel) {
        var on = panel.getAttribute('data-prf-tab') === name;
        panel.classList.toggle('is-active', on);
        if (on) {
          panel.removeAttribute('hidden');
        } else {
          panel.setAttribute('hidden', 'hidden');
        }
      });
      try {
        if (history.replaceState) {
          history.replaceState(null, '', '#' + name);
        }
      } catch (e) {}
    }

    navLinks.forEach(function (btn) {
      btn.addEventListener('click', function () {
        activate(btn.getAttribute('data-prf-tab'));
      });
    });

    var initial = 'account';
    var hash = (location.hash || '').replace(/^#/, '');
    if (hash && document.querySelector('.prf-panel[data-prf-tab="' + hash + '"]')) {
      initial = hash;
    }
    activate(initial);
  }

  function initPasswordStrength() {
    var newPwd = document.getElementById('prfNewPassword');
    var fill = document.getElementById('prfPwdStrengthFill');
    var label = document.getElementById('prfPwdStrengthLabel');
    if (!newPwd || !fill) return;

    newPwd.addEventListener('input', function () {
      var v = newPwd.value || '';
      var score = 0;
      if (v.length >= 8) score++;
      if (v.length >= 12) score++;
      if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
      if (/\d/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;

      var pct = Math.min(100, score * 20);
      fill.style.width = pct + '%';
      if (score <= 1) {
        fill.style.background = '#dc2626';
        if (label) label.textContent = 'Weak';
      } else if (score <= 3) {
        fill.style.background = '#d97706';
        if (label) label.textContent = 'Fair';
      } else {
        fill.style.background = '#16a34a';
        if (label) label.textContent = 'Strong';
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initTabs();
      initPasswordStrength();
    });
  } else {
    initTabs();
    initPasswordStrength();
  }
})();
