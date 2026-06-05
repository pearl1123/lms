/**
 * kaBAGA Academy — Libraries module notifications (SweetAlert2 toasts + confirmations).
 * Requires SweetAlert2, KA.toast (layouts/alerts.php), and optionally KA_SWAL.
 */
(function(global) {
  'use strict';

  var STORAGE_KEY = 'libx_pending_toast';
  var TOAST_TIMER = 3000;

  function notify() {
    return global.LIBX_NOTIFY || {};
  }

  function toast(type, message) {
    if (global.KA && typeof global.KA.toast === 'function') {
      global.KA.toast(type, message, { timer: TOAST_TIMER });
      return;
    }
    if (global.Swal && typeof global.Swal.fire === 'function') {
      global.Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: TOAST_TIMER,
        timerProgressBar: true,
      }).fire({ icon: type, title: message });
    }
  }

  function toastSuccess(message) {
    toast('success', message);
  }

  function toastError(message) {
    toast('error', message);
  }

  function toastWarning(message) {
    toast('warning', message);
  }

  function confirmArchive() {
    if (global.KA_SWAL && global.KA_SWAL.PRESETS && global.KA_SWAL.PRESETS.archiveLibrary) {
      return global.KA_SWAL.SwalConfirm(global.KA_SWAL.PRESETS.archiveLibrary);
    }
    if (global.KA_SWAL && typeof global.KA_SWAL.SwalConfirm === 'function') {
      return global.KA_SWAL.SwalConfirm({
        title: 'Archive this library item?',
        html: 'The item will be hidden but can be restored later.',
        icon: 'warning',
        confirmButtonText: 'Archive',
        cancelButtonText: 'Cancel',
      });
    }
    return Promise.resolve({ isConfirmed: global.confirm('Archive this library item?') });
  }

  function reloadWithToast(type, message) {
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ type: type, message: message }));
    } catch (e) { /* ignore */ }
    global.location.reload();
  }

  function consumePendingToast() {
    try {
      var raw = sessionStorage.getItem(STORAGE_KEY);
      if (!raw) {
        return;
      }
      sessionStorage.removeItem(STORAGE_KEY);
      var data = JSON.parse(raw);
      if (data && data.message) {
        toast(data.type || 'success', data.message);
      }
    } catch (e) { /* ignore */ }
  }

  function isLibraryPage() {
    return /\/libraries(\/|$)/.test(global.location.pathname);
  }

  function flashAlertsToToast() {
    if (!isLibraryPage()) {
      return;
    }
    document.querySelectorAll('.ka-alert').forEach(function(el) {
      var msgEl = el.querySelector('.ka-alert-msg');
      if (!msgEl) {
        return;
      }
      var text = msgEl.textContent.trim();
      if (!text) {
        return;
      }
      var type = 'info';
      if (el.classList.contains('ka-alert-success')) {
        type = 'success';
      } else if (el.classList.contains('ka-alert-error')) {
        type = 'error';
      } else if (el.classList.contains('ka-alert-warning')) {
        type = 'warning';
      }
      toast(type, text);
      el.remove();
    });
  }

  function handleFetchJson(response) {
    if (!response.ok) {
      return response.text().then(function(body) {
        var err = new Error('HTTP ' + response.status);
        err.status = response.status;
        err.body = body;
        throw err;
      });
    }
    return response.json();
  }

  function showInlineError(elId, msg) {
    var el = global.document.getElementById(elId);
    if (!el) {
      return;
    }
    if (!msg) {
      el.hidden = true;
      el.textContent = '';
      return;
    }
    el.hidden = false;
    el.textContent = msg;
  }

  function handleAjaxFailure(err, inlineErrorId) {
    var msg = 'Unable to complete the request. Please check your connection and try again.';
    if (err && err.status >= 500) {
      msg = 'Server error. Please try again in a moment.';
    } else if (err && err.status === 404) {
      msg = 'Record not found. It may have been removed.';
    }
    toastError(msg);
    if (inlineErrorId) {
      showInlineError(inlineErrorId, msg);
    }
  }

  function handleValidationError(message, inlineErrorId) {
    var msg = message || 'Please check the form and try again.';
    toastError(msg);
    if (inlineErrorId) {
      showInlineError(inlineErrorId, msg);
    }
  }

  function init() {
    consumePendingToast();
    flashAlertsToToast();
  }

  global.LIBX_NOTIFY = {
    toast: toast,
    toastSuccess: toastSuccess,
    toastError: toastError,
    toastWarning: toastWarning,
    confirmArchive: confirmArchive,
    reloadWithToast: reloadWithToast,
    consumePendingToast: consumePendingToast,
    flashAlertsToToast: flashAlertsToToast,
    handleFetchJson: handleFetchJson,
    handleAjaxFailure: handleAjaxFailure,
    handleValidationError: handleValidationError,
    showInlineError: showInlineError,
    init: init,
  };

  if (global.document.readyState === 'loading') {
    global.document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(typeof window !== 'undefined' ? window : this);
