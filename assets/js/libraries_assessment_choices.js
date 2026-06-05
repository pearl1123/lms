(function(global) {
  'use strict';

  var cfg = global.LIBX_ASSESSMENT_CHOICES || {};
  var N = global.LIBX_NOTIFY || {};
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

  function postForm(url, form) {
    var fd = new FormData(form);
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: fd,
    }).then(N.handleFetchJson || function(r) { return r.json(); });
  }

  function postAction(url) {
    var fd = new FormData();
    if (cfg.csrfName && cfg.csrfHash) {
      fd.append(cfg.csrfName, cfg.csrfHash);
    }
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: fd,
    }).then(N.handleFetchJson || function(r) { return r.json(); });
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
        N.showInlineError('libxAddError', '');
        var order = $('libxAddChoiceOrder');
        if (order) order.value = '1';
        prefillAddQuestion();
        openModal('libxModalAdd');
      });
    }
    if (form) {
      form.addEventListener('submit', function(ev) {
        ev.preventDefault();
        N.showInlineError('libxAddError', '');
        postForm(cfg.baseUrl + '/create', form).then(function(res) {
          if (!res.success) {
            N.handleValidationError(res.message || 'Unable to save assessment choice.', 'libxAddError');
            return;
          }
          closeModal('libxModalAdd');
          N.reloadWithToast('success', res.message || 'Assessment choice added successfully.');
        }).catch(function(err) {
          N.handleAjaxFailure(err, 'libxAddError');
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
        N.showInlineError('libxUpdateError', '');
        fillUpdateForm(c);
        openModal('libxModalUpdate');
      });
    });

    document.querySelectorAll('.libx-action-delete').forEach(function(btn) {
      btn.addEventListener('click', function() {
        N.confirmArchive().then(function(result) {
          if (!result.isConfirmed) return;
          var id = parseInt(btn.getAttribute('data-id'), 10);
          postAction(cfg.baseUrl + '/delete/' + id).then(function(res) {
            if (!res.success) {
              N.toastError(res.message || 'Unable to archive assessment choice.');
              return;
            }
            N.reloadWithToast('success', res.message || 'Assessment choice archived successfully.');
          }).catch(function(err) {
            N.handleAjaxFailure(err);
          });
        });
      });
    });

    document.querySelectorAll('.libx-action-restore').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var id = parseInt(btn.getAttribute('data-id'), 10);
        postAction(cfg.baseUrl + '/restore/' + id).then(function(res) {
          if (!res.success) {
            N.toastError(res.message || 'Unable to restore assessment choice.');
            return;
          }
          N.reloadWithToast('success', res.message || 'Assessment choice restored successfully.');
        }).catch(function(err) {
          N.handleAjaxFailure(err);
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
      N.showInlineError('libxUpdateError', '');
      postForm(cfg.baseUrl + '/update/' + id, form).then(function(res) {
        if (!res.success) {
          N.handleValidationError(res.message || 'Unable to update assessment choice.', 'libxUpdateError');
          return;
        }
        closeModal('libxModalUpdate');
        N.reloadWithToast('success', res.message || 'Assessment choice updated successfully.');
      }).catch(function(err) {
        N.handleAjaxFailure(err, 'libxUpdateError');
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
