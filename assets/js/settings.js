/**
 * Administration / Settings workspace
 */
(function () {
  'use strict';

  function initNav() {
    var links = document.querySelectorAll('.stg-nav-link[data-stg-tab]');
    var panels = document.querySelectorAll('.stg-panel[data-stg-tab]');
    if (!links.length) return;

    function activate(name) {
      links.forEach(function (btn) {
        var on = btn.getAttribute('data-stg-tab') === name;
        btn.classList.toggle('is-active', on);
        btn.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      panels.forEach(function (panel) {
        var on = panel.getAttribute('data-stg-tab') === name;
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

    function bindTabTrigger(el) {
      el.addEventListener('click', function (e) {
        if (el.tagName === 'BUTTON' && el.type !== 'button' && el.type !== 'submit') {
          return;
        }
        if (el.tagName === 'A' && el.getAttribute('href') && el.getAttribute('href') !== '#') {
          return;
        }
        var tab = el.getAttribute('data-stg-tab');
        if (!tab) return;
        e.preventDefault();
        activate(tab);
      });
    }

    links.forEach(bindTabTrigger);
    document.querySelectorAll('[data-stg-tab].stg-inline-tab').forEach(bindTabTrigger);

    var initial = 'general';
    var hash = (location.hash || '').replace(/^#/, '');
    if (hash && document.querySelector('.stg-panel[data-stg-tab="' + hash + '"]')) {
      initial = hash;
    }
    activate(initial);
  }

  function initSearch() {
    var input = document.getElementById('stgSearch');
    var panels = document.querySelectorAll('.stg-panel[data-stg-keywords]');
    if (!input || !panels.length) return;

    input.addEventListener('input', function () {
      var q = (input.value || '').toLowerCase().trim();
      panels.forEach(function (panel) {
        if (!q) {
          panel.classList.remove('stg-hidden-by-search');
          return;
        }
        var keys = (panel.getAttribute('data-stg-keywords') || '').toLowerCase();
        var text = (panel.textContent || '').toLowerCase();
        var match = keys.indexOf(q) !== -1 || text.indexOf(q) !== -1;
        panel.classList.toggle('stg-hidden-by-search', !match);
      });
    });
  }

  function initUnsaved() {
    var form = document.getElementById('stgForm');
    var badge = document.getElementById('stgUnsaved');
    if (!form || !badge) return;

    var snapshot = form.innerHTML;
    form.addEventListener('input', function () {
      badge.classList.add('is-visible');
    });
    form.addEventListener('change', function () {
      badge.classList.add('is-visible');
    });
  }

  function initAccentPreview() {
    var color = document.getElementById('stgAccentColor');
    var chip = document.getElementById('stgAccentChip');
    if (!color || !chip) return;
    function sync() {
      chip.style.background = color.value || '#6dabcf';
    }
    color.addEventListener('input', sync);
    sync();
  }

  function initSerialPreview() {
    var prefix = document.querySelector('[name="settings[certificates][serial_prefix]"]');
    var out = document.getElementById('stgSerialPreview');
    if (!prefix || !out) return;
    function sync() {
      var p = (prefix.value || 'CERT').toUpperCase().replace(/[^A-Z0-9]/g, '') || 'CERT';
      var y = new Date().getFullYear();
      out.textContent = p + '-' + y + '-0001';
    }
    prefix.addEventListener('input', sync);
    sync();
  }

  function boot() {
    initNav();
    initSearch();
    initUnsaved();
    initAccentPreview();
    initSerialPreview();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
