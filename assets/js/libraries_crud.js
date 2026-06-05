(function(global) {
  'use strict';

  var cfg = global.LIBX_CRUD || {};
  var N = global.LIBX_NOTIFY || {};
  var pk = cfg.primaryKey || 'id';
  var rowsById = {};

  (cfg.rows || []).forEach(function(r) {
    var id = r[pk];
    if (id !== undefined && id !== null) {
      rowsById[id] = r;
    }
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

  function fillUpdateForm(row) {
    $('libxUpdatePk').value = row[pk];
    document.querySelectorAll('#libxFormUpdate [name]').forEach(function(el) {
      var name = el.getAttribute('name');
      if (!name || name === cfg.csrfName) return;
      if (name === pk) return;
      if (el.type === 'checkbox') {
        el.checked = parseInt(row[name], 10) === 1 || row[name] === true;
      } else if (row[name] !== undefined && row[name] !== null) {
        el.value = row[name];
      }
    });
  }

  function bindAdd() {
    var btn = $('libxBtnAdd');
    var form = $('libxFormAdd');
    if (btn) {
      btn.addEventListener('click', function() {
        if (form) form.reset();
        N.showInlineError('libxAddError', '');
        openModal('libxModalAdd');
      });
    }
    if (form) {
      form.addEventListener('submit', function(ev) {
        ev.preventDefault();
        N.showInlineError('libxAddError', '');
        postForm(cfg.baseUrl + '/create', form).then(function(res) {
          if (!res.success) {
            N.handleValidationError(res.message || 'Unable to save library item.', 'libxAddError');
            return;
          }
          closeModal('libxModalAdd');
          N.reloadWithToast('success', res.message || 'Library item added successfully.');
        }).catch(function(err) {
          N.handleAjaxFailure(err, 'libxAddError');
        });
      });
    }
  }

  function bindUpdate() {
    var form = $('libxFormUpdate');
    if (!form) return;
    form.addEventListener('submit', function(ev) {
      ev.preventDefault();
      var id = $('libxUpdatePk').value;
      N.showInlineError('libxUpdateError', '');
      postForm(cfg.baseUrl + '/update/' + id, form).then(function(res) {
        if (!res.success) {
          N.handleValidationError(res.message || 'Unable to update library item.', 'libxUpdateError');
          return;
        }
        closeModal('libxModalUpdate');
        N.reloadWithToast('success', res.message || 'Library item updated successfully.');
      }).catch(function(err) {
        N.handleAjaxFailure(err, 'libxUpdateError');
      });
    });
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

  function bindTableActions() {
    document.querySelectorAll('.libx-action-edit').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var id = btn.getAttribute('data-id');
        var row = rowsById[id];
        if (!row) return;
        N.showInlineError('libxUpdateError', '');
        fillUpdateForm(row);
        openModal('libxModalUpdate');
      });
    });

    if (cfg.canArchive) {
      document.querySelectorAll('.libx-action-delete').forEach(function(btn) {
        btn.addEventListener('click', function() {
          N.confirmArchive().then(function(result) {
            if (!result.isConfirmed) return;
            var id = btn.getAttribute('data-id');
            postAction(cfg.baseUrl + '/delete/' + id).then(function(res) {
              if (!res.success) {
                N.toastError(res.message || 'Unable to archive library item.');
                return;
              }
              N.reloadWithToast('success', res.message || 'Library item archived successfully.');
            }).catch(function(err) {
              N.handleAjaxFailure(err);
            });
          });
        });
      });

      document.querySelectorAll('.libx-action-restore').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var id = btn.getAttribute('data-id');
          postAction(cfg.baseUrl + '/restore/' + id).then(function(res) {
            if (!res.success) {
              N.toastError(res.message || 'Unable to restore library item.');
              return;
            }
            N.reloadWithToast('success', res.message || 'Library item restored successfully.');
          }).catch(function(err) {
            N.handleAjaxFailure(err);
          });
        });
      });
    }
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
