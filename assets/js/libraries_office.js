(function(global) {
  'use strict';

  var cfg = global.LIBX_OFFICE || {};
  var choicesById = {};

  (cfg.choices || []).forEach(function(c) {
    choicesById[c.id] = c;
  });

  function $(id) { return document.getElementById(id); }

  function openModal(id) {
    var el = $(id);
    if (el) {
      el.classList.add('is-open');
      el.setAttribute('aria-hidden', 'false');
    }
  }

  function closeModal(id) {
    var el = $(id);
    if (el) {
      el.classList.remove('is-open');
      el.setAttribute('aria-hidden', 'true');
    }
  }

  function showError(elId, msg) {
    var el = $(elId);
    if (!el) return;
    if (!msg) {
      el.hidden = true;
      el.textContent = '';
      return;
    }
    el.hidden = false;
    el.textContent = msg;
  }

  function postForm(url, form) {
    var fd = new FormData(form);
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: fd,
    }).then(function(r) { return r.json(); });
  }

  function bindDismiss() {
    document.querySelectorAll('[data-dismiss]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        closeModal(btn.getAttribute('data-dismiss'));
      });
    });
    ['libxModalAdd', 'libxModalUpdate'].forEach(function(id) {
      var overlay = $(id);
      if (overlay) {
        overlay.addEventListener('click', function(ev) {
          if (ev.target === overlay) closeModal(id);
        });
      }
    });
  }

  function prefillAddQuestion() {
    var sel = $('libxAddQuestionId');
    var filterQ = document.querySelector('select[name="question_id"]');
    if (sel && filterQ && filterQ.value && filterQ.value !== '0') {
      sel.value = filterQ.value;
    }
  }

  function bindAdd() {
    var btn = $('libxBtnAdd');
    var form = $('libxFormAdd');
    if (btn) {
      btn.addEventListener('click', function() {
        if (form) form.reset();
        showError('libxAddError', '');
        var order = $('libxAddChoiceOrder');
        if (order) order.value = '1';
        prefillAddQuestion();
        openModal('libxModalAdd');
      });
    }
    if (form) {
      form.addEventListener('submit', function(ev) {
        ev.preventDefault();
        showError('libxAddError', '');
        postForm(cfg.baseUrl + '/create', form).then(function(res) {
          if (!res.success) {
            showError('libxAddError', res.message || 'Save failed');
            return;
          }
          closeModal('libxModalAdd');
          global.location.reload();
        }).catch(function() {
          showError('libxAddError', 'Network error');
        });
      });
    }
  }

  function fillUpdateForm(c) {
    $('libxUpdateId').value = c.id;
    $('libxUpdateQuestionId').value = c.question_id;
    $('libxUpdateChoiceText').value = c.choice_text || '';
    $('libxUpdateIsCorrect').checked = parseInt(c.is_correct, 10) === 1;
    $('libxUpdateChoiceOrder').value = c.choice_order || 1;
  }

  function bindTableActions() {
    document.querySelectorAll('.libx-action-edit').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var c = choicesById[id];
        if (!c) return;
        showError('libxUpdateError', '');
        fillUpdateForm(c);
        openModal('libxModalUpdate');
      });
    });

    document.querySelectorAll('.libx-action-delete').forEach(function(btn) {
      btn.addEventListener('click', function() {
        if (!global.confirm('Archive this choice? It will be hidden from active lists.')) return;
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var fd = new FormData();
        if (cfg.csrfName && cfg.csrfHash) {
          fd.append(cfg.csrfName, cfg.csrfHash);
        }
        fetch(cfg.baseUrl + '/delete/' + id, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: fd,
        }).then(function(r) { return r.json(); }).then(function(res) {
          if (res.success) global.location.reload();
          else global.alert(res.message || 'Archive failed');
        });
      });
    });

    document.querySelectorAll('.libx-action-restore').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        var fd = new FormData();
        if (cfg.csrfName && cfg.csrfHash) {
          fd.append(cfg.csrfName, cfg.csrfHash);
        }
        fetch(cfg.baseUrl + '/restore/' + id, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: fd,
        }).then(function(r) { return r.json(); }).then(function(res) {
          if (res.success) global.location.reload();
          else global.alert(res.message || 'Restore failed');
        });
      });
    });
  }

  function bindUpdate() {
    var form = $('libxFormUpdate');
    if (!form) return;
    form.addEventListener('submit', function(ev) {
      ev.preventDefault();
      var id = $('libxUpdateId').value;
      showError('libxUpdateError', '');
      postForm(cfg.baseUrl + '/update/' + id, form).then(function(res) {
        if (!res.success) {
          showError('libxUpdateError', res.message || 'Update failed');
          return;
        }
        closeModal('libxModalUpdate');
        global.location.reload();
      }).catch(function() {
        showError('libxUpdateError', 'Network error');
      });
    });
  }

  function init() {
    bindDismiss();
    bindAdd();
    bindUpdate();
    bindTableActions();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window);
