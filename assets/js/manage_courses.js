/**
 * Manage Courses — grid / list (table) view persistence and BFCache-safe restore.
 * Single source of truth: localStorage key manage_courses_view = "grid" | "list"
 * Default when missing/invalid: grid
 */
(function () {
  'use strict';

  var LS_KEY = 'manage_courses_view';

  var PAGE_CONFIGS = [
    {
      toggleId: 'mcViewToggle',
      gridId: 'mcGridView',
      tableId: 'mcTableView',
      btnSelector: '.mc-toggle-btn',
    },
    {
      toggleId: 'icViewToggle',
      gridId: 'icGridView',
      tableId: 'icTableView',
      btnSelector: '.ic-toggle-btn',
    },
  ];

  function readStoredView() {
    try {
      var v = String(localStorage.getItem(LS_KEY) || '').toLowerCase().trim();
      if (v === 'list' || v === 'table') {
        return 'list';
      }
      if (v === 'grid') {
        return 'grid';
      }
    } catch (e) { /* ignore */ }
    return 'grid';
  }

  function writeStoredView(isList) {
    try {
      localStorage.setItem(LS_KEY, isList ? 'list' : 'grid');
    } catch (e) { /* ignore */ }
  }

  function ensureChromeVisible(toggle) {
    if (!toggle) {
      return;
    }
    toggle.removeAttribute('hidden');
    toggle.style.display = 'flex';
    toggle.style.visibility = 'visible';
    toggle.style.opacity = '1';
    var bar = toggle.closest('.mc-topbar-right, .ic-topbar-right');
    if (bar) {
      bar.removeAttribute('hidden');
      bar.style.display = '';
      bar.style.visibility = 'visible';
      bar.style.opacity = '1';
    }
  }

  /**
   * @param {'grid'|'table'} layoutMode  matches data-view on buttons (table = list layout)
   */
  function applyView(layoutMode, opts) {
    var toggle = document.getElementById(opts.toggleId);
    var grid = document.getElementById(opts.gridId);
    var table = document.getElementById(opts.tableId);
    var btnSel = opts.btnSelector || '.mc-toggle-btn';
    if (!toggle || !grid || !table) {
      return;
    }

    ensureChromeVisible(toggle);

    var isGrid = layoutMode === 'grid';
    if (isGrid) {
      grid.style.display = '';
      grid.removeAttribute('hidden');
      table.style.display = 'none';
    } else {
      grid.style.display = 'none';
      table.style.display = '';
      table.removeAttribute('hidden');
    }

    toggle.querySelectorAll(btnSel).forEach(function (btn) {
      var dv = btn.getAttribute('data-view');
      var on = (dv === 'grid' && isGrid) || (dv === 'table' && !isGrid);
      btn.classList.toggle('active', on);
    });

    document.dispatchEvent(
      new CustomEvent('ka-mc-view-applied', { detail: { layout: layoutMode, opts: opts } })
    );
  }

  function bindPage(opts) {
    var toggle = document.getElementById(opts.toggleId);
    if (!toggle) {
      return;
    }

    if (toggle.getAttribute('data-ka-mc-bound') !== '1') {
      toggle.setAttribute('data-ka-mc-bound', '1');
      toggle.addEventListener('click', function (ev) {
        var btn = ev.target.closest(opts.btnSelector);
        if (!btn || !toggle.contains(btn)) {
          return;
        }
        var mode = btn.getAttribute('data-view') === 'table' ? 'table' : 'grid';
        applyView(mode, opts);
        writeStoredView(mode === 'table');
      });
    }

    var stored = readStoredView();
    applyView(stored === 'list' ? 'table' : 'grid', opts);
  }

  function initManageCoursesViews() {
    PAGE_CONFIGS.forEach(bindPage);
  }

  document.addEventListener('DOMContentLoaded', initManageCoursesViews);
  window.addEventListener('pageshow', function (ev) {
    initManageCoursesViews();
  });
})();
