<?php
/**
 * KABAGA Academy — Flash Alert Partial
 * Rendered by ka_merge_layout_vars() as $alerts_partial_html; content views echo that string.
 *
 * Expects $flash_messages (array key => message) from ka_merge_layout_vars().
 * Renders Tabler-style alerts (matching the KABAGA design system).
 * Also injects SweetAlert2 CDN once and exposes window.KA helpers for JS.
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$flash_messages = $flash_messages ?? [];
?>

<!-- SweetAlert2 (loaded once here so every view has it) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<style>
  /* ── KABAGA-branded SweetAlert2 overrides ── */
  .swal2-popup {
    border-radius: 16px !important;
    font-family: var(--tblr-font-sans-serif, 'DM Sans', sans-serif) !important;
    padding: 1.75rem !important;
  }
  .swal2-title {
    font-size: 1.125rem !important;
    font-weight: 700 !important;
    color: var(--ka-text, #1e293b) !important;
  }
  .swal2-html-container {
    font-size: .875rem !important;
    color: var(--ka-text-muted, #64748b) !important;
  }
  .swal2-confirm {
    border-radius: 8px !important;
    font-weight: 600 !important;
    font-size: .875rem !important;
    padding: .5rem 1.25rem !important;
  }
  .swal2-cancel {
    border-radius: 8px !important;
    font-weight: 600 !important;
    font-size: .875rem !important;
    padding: .5rem 1.25rem !important;
    background: var(--ka-bg, #f8fafc) !important;
    color: var(--ka-text-muted, #64748b) !important;
    border: 1.5px solid var(--ka-border, #e2e8f0) !important;
  }
  .swal2-cancel:hover {
    background: var(--ka-border, #e2e8f0) !important;
  }
  .swal2-icon { margin-bottom: 1rem !important; }

  /* ── Inline Tabler alerts (KABAGA colours) ── */
  .ka-alert {
    display: flex; align-items: flex-start; gap: .75rem;
    padding: .875rem 1.125rem; border-radius: 10px;
    margin-bottom: 1.25rem; font-size: .875rem;
    border: 1px solid transparent; position: relative;
    animation: kaAlertIn .25s ease;
  }
  @keyframes kaAlertIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .ka-alert svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }
  .ka-alert-body { flex: 1; min-width: 0; }
  .ka-alert-title { font-weight: 700; margin-bottom: 2px; font-size: .875rem; }
  .ka-alert-msg   { font-size: .8125rem; line-height: 1.5; }
  .ka-alert-close {
    position: absolute; top: .75rem; right: .875rem;
    background: none; border: none; cursor: pointer;
    opacity: .5; transition: opacity .15s; padding: 0; line-height: 1;
  }
  .ka-alert-close:hover { opacity: 1; }
  .ka-alert-close svg { width: 16px; height: 16px; }

  /* Variants */
  .ka-alert-success {
    background: #ecfdf5; border-color: #bbf7d0; color: #065f46;
  }
  .ka-alert-error {
    background: #fef2f2; border-color: #fecaca; color: #991b1b;
  }
  .ka-alert-warning {
    background: #fffbeb; border-color: #fde68a; color: #92400e;
  }
  .ka-alert-info {
    background: var(--ka-accent, #e8f4fd);
    border-color: #bae6fd;
    color: var(--ka-navy, #1a3a5c);
  }
</style>

<?php
// ── Collect all flash messages ────────────────────────────────
$flash_map = [
    'success'                => ['class' => 'ka-alert-success', 'title' => 'Success',             'icon' => '<path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/>'],
    'error'                  => ['class' => 'ka-alert-error',   'title' => 'Error',               'icon' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>'],
    'warning'                => ['class' => 'ka-alert-warning', 'title' => 'Warning',             'icon' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>'],
    'info'                   => ['class' => 'ka-alert-info',    'title' => 'Information',         'icon' => '<circle cx="12" cy="12" r="10"/><path d="M12 9h.01"/><path d="M11 12h1v4h1"/>'],
    'enrollment_notification'=> ['class' => 'ka-alert-error',   'title' => 'Enrollment Update',   'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
];

foreach ($flash_map as $key => $cfg):
    $msg = $flash_messages[$key] ?? null;
    if ( ! $msg) {
        continue;
    }
?>
<div class="ka-alert <?= $cfg['class'] ?>" role="alert" id="kaAlert-<?= $key ?>">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <?= $cfg['icon'] ?>
  </svg>
  <div class="ka-alert-body">
    <div class="ka-alert-title"><?= $cfg['title'] ?></div>
    <div class="ka-alert-msg"><?= $msg /* already escaped in controller */ ?></div>
  </div>
  <button class="ka-alert-close" onclick="this.closest('.ka-alert').remove()" aria-label="Close">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
  </button>
</div>
<?php endforeach; ?>

<script>
// ── Auto-dismiss inline alerts after 6 seconds ───────────────
document.querySelectorAll('.ka-alert').forEach(function(el) {
  setTimeout(function() {
    el.style.transition = 'opacity .4s ease, transform .4s ease';
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(-6px)';
    setTimeout(function() { el.remove(); }, 400);
  }, 6000);
});

// ── Global SweetAlert2 helpers ────────────────────────────────
window.KA = window.KA || {};

/**
 * KA.confirm(options) — branded confirmation dialog
 * @param {object} opts
 *   title       {string}
 *   text        {string}
 *   confirmText {string}  default 'Yes, continue'
 *   cancelText  {string}  default 'Cancel'
 *   type        {string}  'danger' | 'warning' | 'info'  default 'warning'
 *   onConfirm   {function|string}  callback or URL to redirect
 */
KA.confirm = function(opts) {
  const colors = {
    danger:  { btn: '#dc2626', icon: 'warning' },
    warning: { btn: '#f59f00', icon: 'warning' },
    info:    { btn: '#6dabcf', icon: 'question' },
  };
  const c = colors[opts.type || 'warning'];

  Swal.fire({
    title:              opts.title       || 'Are you sure?',
    html:               opts.text        || '',
    icon:               c.icon,
    showCancelButton:   true,
    confirmButtonText:  opts.confirmText || 'Yes, continue',
    cancelButtonText:   opts.cancelText  || 'Cancel',
    confirmButtonColor: c.btn,
    reverseButtons:     true,
    focusCancel:        true,
    customClass: {
      popup:          'swal2-popup',
      confirmButton:  'swal2-confirm',
      cancelButton:   'swal2-cancel',
    },
  }).then(function(result) {
    if (result.isConfirmed) {
      if (typeof opts.onConfirm === 'function') {
        opts.onConfirm();
      } else if (typeof opts.onConfirm === 'string') {
        window.location.href = opts.onConfirm;
      }
    }
  });
};

/**
 * KA.toast(type, message) — small non-blocking toast
 * type: 'success' | 'error' | 'warning' | 'info'
 */
KA.toast = function(type, message) {
  const iconColors = {
    success: '#22c55e',
    error:   '#dc2626',
    warning: '#f59f00',
    info:    '#6dabcf',
  };
  Swal.mixin({
    toast:             true,
    position:          'top-end',
    showConfirmButton: false,
    timer:             4000,
    timerProgressBar:  true,
    didOpen: function(toast) {
      toast.addEventListener('mouseenter', Swal.stopTimer);
      toast.addEventListener('mouseleave', Swal.resumeTimer);
    },
  }).fire({
    icon:              type,
    title:             message,
    iconColor:         iconColors[type] || '#6dabcf',
    background:        '#fff',
    color:             '#1e293b',
    customClass: { popup: 'swal2-popup' },
  });
};

/**
 * KA.deleteConfirm(url, itemName) — standard delete confirmation
 * Redirects to url on confirm.
 */
KA.deleteConfirm = function(url, itemName) {
  KA.confirm({
    title:       'Delete ' + (itemName || 'this item') + '?',
    text:        'This action <strong>cannot be undone</strong>. The record will be permanently removed.',
    confirmText: 'Yes, delete it',
    cancelText:  'Cancel',
    type:        'danger',
    onConfirm:   url,
  });
};

/**
 * KA.enrollConfirm(url, courseName) — enroll confirmation
 */
KA.enrollConfirm = function(url, courseName) {
  KA.confirm({
    title:       'Enroll in this course?',
    text:        'You are about to enroll in <strong>' + courseName + '</strong>. You can start learning immediately.',
    confirmText: 'Yes, enroll me!',
    cancelText:  'Not now',
    type:        'info',
    onConfirm:   url,
  });
};
</script>