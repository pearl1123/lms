/**
 * kaBAGA Academy — SweetAlert2 helpers (consistent SaaS dialogs).
 * Requires SweetAlert2 (Swal) loaded before this script.
 */
(function (global) {
  'use strict';

  var COLORS = {
    primary: '#2563eb',
    success: '#22c55e',
    danger: '#dc2626',
    warning: '#f59f00',
    muted: '#64748b',
    info: '#6dabcf',
  };

  function swalReady() {
    return typeof global.Swal !== 'undefined' && typeof global.Swal.fire === 'function';
  }

  function baseOpts(extra) {
    var o = {
      reverseButtons: true,
      focusCancel: true,
      customClass: {
        popup: 'swal2-popup',
        confirmButton: 'swal2-confirm',
        cancelButton: 'swal2-cancel',
      },
    };
    if (extra) {
      Object.keys(extra).forEach(function (k) {
        o[k] = extra[k];
      });
    }
    return o;
  }

  function normalizeHtml(opts) {
    if (opts && opts.text && !opts.html) {
      opts.html = opts.text;
      delete opts.text;
    }
    return opts || {};
  }

  /**
   * @param {object} opts SweetAlert2 options (title, html/text, icon, buttons, etc.)
   * @returns {Promise}
   */
  function SwalConfirm(opts) {
    if (!swalReady()) {
      return Promise.resolve({ isConfirmed: false });
    }
    opts = normalizeHtml(opts);
    return global.Swal.fire(
      baseOpts(
        Object.assign(
          {
            icon: opts.icon || 'warning',
            showCancelButton: true,
            confirmButtonText: opts.confirmButtonText || 'Yes, continue',
            cancelButtonText: opts.cancelButtonText || 'Cancel',
            confirmButtonColor: opts.confirmButtonColor || COLORS.primary,
            cancelButtonColor: opts.cancelButtonColor || COLORS.muted,
          },
          opts
        )
      )
    );
  }

  function SwalSuccess(opts) {
    if (!swalReady()) {
      return Promise.resolve();
    }
    opts = normalizeHtml(opts);
    return global.Swal.fire(
      baseOpts(
        Object.assign(
          {
            icon: 'success',
            confirmButtonText: opts.confirmButtonText || 'OK',
            confirmButtonColor: COLORS.success,
          },
          opts
        )
      )
    );
  }

  function SwalError(opts) {
    if (!swalReady()) {
      return Promise.resolve();
    }
    opts = normalizeHtml(opts);
    return global.Swal.fire(
      baseOpts(
        Object.assign(
          {
            icon: 'error',
            confirmButtonText: opts.confirmButtonText || 'OK',
            confirmButtonColor: COLORS.danger,
          },
          opts
        )
      )
    );
  }

  function SwalWarning(opts) {
    if (!swalReady()) {
      return Promise.resolve();
    }
    opts = normalizeHtml(opts);
    return global.Swal.fire(
      baseOpts(
        Object.assign(
          {
            icon: 'warning',
            confirmButtonText: opts.confirmButtonText || 'OK',
            confirmButtonColor: COLORS.warning,
          },
          opts
        )
      )
    );
  }

  /** Navigate on confirm (used by data-attribute links). */
  function confirmThenGo(url, opts) {
    return SwalConfirm(opts).then(function (result) {
      if (result.isConfirmed && url) {
        global.location.href = url;
      }
    });
  }

  var PRESETS = {
    acceptInvite: {
      title: 'Accept course invitation?',
      html: 'You will be enrolled in this course after accepting.',
      icon: 'question',
      confirmButtonText: 'Yes, accept',
      cancelButtonText: 'Not now',
      confirmButtonColor: COLORS.success,
    },
    declineInvite: {
      title: 'Decline this invitation?',
      html: 'You can ask your instructor to send a new invite if you change your mind.',
      icon: 'warning',
      confirmButtonText: 'Yes, decline',
      cancelButtonText: 'Cancel',
      confirmButtonColor: COLORS.danger,
    },
    enrollOpen: {
      title: 'Enroll in this course?',
      html: 'You can start learning immediately after you enroll.',
      icon: 'question',
      confirmButtonText: 'Yes, enroll me',
      cancelButtonText: 'Not now',
      confirmButtonColor: COLORS.success,
    },
    enrollRequest: {
      title: 'Request enrollment in this course?',
      html: 'Your request will be sent to the instructor for approval.',
      icon: 'question',
      confirmButtonText: 'Yes, request enrollment',
      cancelButtonText: 'Not now',
      confirmButtonColor: COLORS.primary,
    },
    enrollRetry: {
      title: 'Submit a new enrollment request?',
      html: 'A new request will be sent for instructor review.',
      icon: 'question',
      confirmButtonText: 'Yes, submit request',
      cancelButtonText: 'Cancel',
      confirmButtonColor: COLORS.primary,
    },
    archiveLibrary: {
      title: 'Archive this library item?',
      html: 'The item will be hidden but can be restored later.',
      icon: 'warning',
      confirmButtonText: 'Archive',
      cancelButtonText: 'Cancel',
      confirmButtonColor: COLORS.warning,
    },
  };

  function bindConfirmLinks() {
    var handlers = [
      {
        selector: '.js-ka-invite-accept',
        preset: 'acceptInvite',
      },
      {
        selector: '.js-ka-invite-decline',
        preset: 'declineInvite',
      },
      {
        selector: '.js-ka-enroll-open',
        preset: 'enrollOpen',
      },
      {
        selector: '.js-ka-enroll-request',
        preset: 'enrollRequest',
      },
      {
        selector: '.js-ka-enroll-retry',
        preset: 'enrollRetry',
      },
    ];

    handlers.forEach(function (cfg) {
      document.querySelectorAll(cfg.selector).forEach(function (el) {
        if (el.getAttribute('data-ka-bound') === '1') {
          return;
        }
        el.setAttribute('data-ka-bound', '1');
        el.addEventListener('click', function (e) {
          var url = el.getAttribute('href');
          if (!url) {
            return;
          }
          e.preventDefault();
          confirmThenGo(url, PRESETS[cfg.preset]);
        });
      });
    });
  }

  global.KA_SWAL = {
    COLORS: COLORS,
    SwalConfirm: SwalConfirm,
    SwalSuccess: SwalSuccess,
    SwalError: SwalError,
    SwalWarning: SwalWarning,
    confirmThenGo: confirmThenGo,
    PRESETS: PRESETS,
    bindConfirmLinks: bindConfirmLinks,
  };

  function init() {
    bindConfirmLinks();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(typeof window !== 'undefined' ? window : this);
