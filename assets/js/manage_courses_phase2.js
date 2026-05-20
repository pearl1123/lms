/**
 * Manage Courses — Phase 2 premium UI (Select2, access cards, summaries)
 */
(function () {
  'use strict';

  var AVATAR_COLORS = [
    '#1a3a5c', '#2563eb', '#7c3aed', '#db2777', '#059669', '#d97706', '#0891b2', '#4f46e5'
  ];

  function initials(name) {
    var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
      return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }
    return String(name || '?').substring(0, 2).toUpperCase();
  }

  function avatarColor(seed) {
    var n = 0;
    var s = String(seed || '');
    for (var i = 0; i < s.length; i++) {
      n += s.charCodeAt(i);
    }
    return AVATAR_COLORS[n % AVATAR_COLORS.length];
  }

  function avatarHtml(name, seed) {
    var ini = initials(name);
    var bg = avatarColor(seed || name);
    return '<span class="crs-p2-avatar" style="background:' + bg + '">' + ini + '</span>';
  }

  function readOptionData(el) {
    if (!el) return {};
    return {
      id: el.value,
      name: el.getAttribute('data-name') || (el.textContent || '').trim(),
      sub: el.getAttribute('data-sub') || '',
      initials: el.getAttribute('data-initials') || initials(el.getAttribute('data-name') || el.textContent)
    };
  }

  function instructorTemplate(data, container) {
    if (!data.id) {
      return data.text;
    }
    var el = data.element;
    var meta = readOptionData(el);
    var sub = meta.sub ? '<div class="select2-instructor-option-sub">' + meta.sub + '</div>' : '';
    return (
      '<div class="select2-instructor-option">' +
        avatarHtml(meta.name, meta.id) +
        '<div><div class="select2-instructor-option-name">' + meta.name + '</div>' + sub + '</div>' +
      '</div>'
    );
  }

  function plainChipTemplate(data) {
    if (!data.id) {
      return data.text;
    }
    return data.text;
  }

  function applyChipClass($select, prefix) {
    var cls = 'crs-p2-chip--' + prefix;
    $select.on('select2:select select2:unselect change', function () {
      var $container = $select.next('.select2-container');
      $container.find('.select2-selection__choice').addClass(cls);
    });
    setTimeout(function () {
      $select.next('.select2-container').find('.select2-selection__choice').addClass(cls);
    }, 0);
  }

  function initSelect2() {
    if (!window.jQuery || !jQuery.fn.select2) {
      return;
    }

    jQuery('.crs-p2-stack .ka-select2').each(function () {
      var $el = jQuery(this);
      if ($el.data('select2')) {
        return;
      }

      var kind = $el.data('select-kind') || 'default';
      var placeholder = $el.data('placeholder') || 'Search and select…';
      var opts = {
        width: '100%',
        placeholder: placeholder,
        allowClear: true,
        closeOnSelect: kind !== 'instructor'
      };

      if (kind === 'instructor' || kind === 'user') {
        opts.templateResult = instructorTemplate;
        opts.templateSelection = instructorTemplate;
        opts.escapeMarkup = function (m) { return m; };
      } else {
        opts.templateResult = plainChipTemplate;
        opts.templateSelection = plainChipTemplate;
      }

      $el.select2(opts);

      if (kind === 'cat') applyChipClass($el, 'cat');
      else if (kind === 'dept') applyChipClass($el, 'dept');
      else if (kind === 'prof') applyChipClass($el, 'prof');
      else if (kind === 'user') applyChipClass($el, 'user');
      else if (kind === 'instructor') applyChipClass($el, 'instructor');
    });
  }

  function syncPrimaryCategory() {
    var categories = document.querySelector('.crs-p2-stack select[name="category_ids[]"]');
    var primary = document.getElementById('edit_category_id') || document.getElementById('category_id');
    if (!categories || !primary) {
      return;
    }
    var sync = function () {
      var selected = Array.from(categories.selectedOptions || []).map(function (opt) { return opt.value; });
      primary.value = selected.length ? selected[0] : '';
    };
    if (window.jQuery) {
      jQuery(categories).on('change', sync);
    } else {
      categories.addEventListener('change', sync);
    }
    sync();
  }

  function bindAccessCards() {
    var hidden = document.getElementById('crs_p2_access_type');
    var cards = document.querySelectorAll('.crs-p2-access-opt input[type="radio"]');
    if (!hidden || !cards.length) {
      return;
    }
    cards.forEach(function (radio) {
      radio.addEventListener('change', function () {
        if (radio.checked) {
          hidden.value = radio.value;
          updateVisibilitySummary();
        }
      });
    });
  }

  function bindInstructorPreview() {
    var select = document.querySelector('.crs-p2-stack select[name="instructor_ids[]"]');
    var preview = document.getElementById('crs_p2_instructor_preview');
    if (!select || !preview) {
      return;
    }

    var render = function () {
      var html = '';
      Array.from(select.selectedOptions || []).forEach(function (opt, idx) {
        var meta = readOptionData(opt);
        var isPrimary = idx === 0;
        html +=
          '<div class="crs-p2-instructor-chip' + (isPrimary ? ' is-primary' : '') + '">' +
            avatarHtml(meta.name, meta.id) +
            '<div class="crs-p2-instructor-meta">' +
              '<span class="crs-p2-instructor-name">' + meta.name + '</span>' +
              '<span class="crs-p2-role-badge' + (isPrimary ? ' is-primary' : '') + '">' +
                (isPrimary ? 'Primary instructor' : 'Co-instructor') +
              '</span>' +
            '</div>' +
          '</div>';
      });
      preview.innerHTML = html;
    };

    if (window.jQuery) {
      jQuery(select).on('change', render);
    } else {
      select.addEventListener('change', render);
    }
    render();
  }

  function selectedLabels(select) {
    if (!select) return [];
    return Array.from(select.selectedOptions || []).map(function (o) { return o.textContent.trim(); });
  }

  function updateVisibilitySummary() {
    var root = document.querySelector('.crs-p2-stack');
    if (!root) return;

    var accessHidden = document.getElementById('crs_p2_access_type');
    var accessLabel = root.querySelector('[data-summary-access]');
    var deptSelect = root.querySelector('select[name="department_ids[]"]');
    var profSelect = root.querySelector('select[name="profession_ids[]"]');
    var catSelect = root.querySelector('select[name="category_ids[]"]');
    var deptChip = root.querySelector('[data-summary-dept]');
    var profChip = root.querySelector('[data-summary-prof]');
    var catChip = root.querySelector('[data-summary-cat]');

    if (accessLabel && accessHidden) {
      var checked = root.querySelector('.crs-p2-access-opt input[type="radio"]:checked');
      accessLabel.textContent = checked
        ? checked.getAttribute('data-label') || accessHidden.value
        : accessHidden.value;
    }

    function setChip(el, items, emptyText, allDeptMode) {
      if (!el) return;
      if (!items.length) {
        el.textContent = emptyText;
        el.classList.add('crs-p2-summary-chip--muted');
        el.classList.remove('crs-p2-summary-chip--all-dept');
      } else if (allDeptMode) {
        el.textContent = items.length > 2 ? items.slice(0, 2).join(', ') + ' +' + (items.length - 2) : items.join(', ');
        el.classList.remove('crs-p2-summary-chip--muted', 'crs-p2-summary-chip--all-dept');
      } else {
        el.textContent = items.length > 2 ? items.slice(0, 2).join(', ') + ' +' + (items.length - 2) : items.join(', ');
        el.classList.remove('crs-p2-summary-chip--muted');
        el.classList.remove('crs-p2-summary-chip--all-dept');
      }
    }

    function updateDeptVisibilityHint() {
      var hint = document.getElementById('crs_p2_dept_visibility_hint');
      var deptSelect = root.querySelector('select[name="department_ids[]"]');
      if (!hint || !deptSelect) return;

      var labels = selectedLabels(deptSelect);
      if (!labels.length) {
        hint.innerHTML = '<span class="crs-p2-dept-hint-all">🌐 Visible to all departments</span>';
      } else {
        hint.innerHTML = '<span class="crs-p2-dept-hint-restricted">Restricted to ' + labels.length + ' department' + (labels.length === 1 ? '' : 's') + '</span>';
      }
    }

    setChip(deptChip, selectedLabels(deptSelect), '🌐 All departments', false);
    if (!selectedLabels(deptSelect).length && deptChip) {
      deptChip.textContent = '🌐 All departments';
      deptChip.classList.add('crs-p2-summary-chip--all-dept');
      deptChip.classList.remove('crs-p2-summary-chip--muted');
    } else {
      setChip(deptChip, selectedLabels(deptSelect), 'All departments', false);
    }
    setChip(profChip, selectedLabels(profSelect), 'All job titles', false);
    setChip(catChip, selectedLabels(catSelect), 'No categories', false);
    updateDeptVisibilityHint();
  }

  function bindSummaryUpdates() {
    var root = document.querySelector('.crs-p2-stack');
    if (!root) return;

    root.querySelectorAll('select').forEach(function (sel) {
      if (window.jQuery) {
        jQuery(sel).on('change', function () {
          updateVisibilitySummary();
        });
      } else {
        sel.addEventListener('change', updateVisibilitySummary);
      }
    });

    updateVisibilitySummary();
  }

  function init() {
    if (!window.jQuery || !jQuery.fn.select2) {
      return false;
    }
    initSelect2();
    syncPrimaryCategory();
    bindAccessCards();
    bindInstructorPreview();
    bindSummaryUpdates();
    return true;
  }

  function bootSelect2(attempt) {
    if (init()) {
      return;
    }
    if ((attempt || 0) >= 30) {
      return;
    }
    setTimeout(function () {
      bootSelect2((attempt || 0) + 1);
    }, 100);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { bootSelect2(0); });
  } else {
    bootSelect2(0);
  }
  window.addEventListener('pageshow', function () { bootSelect2(0); });
})();
