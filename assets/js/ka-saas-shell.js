/**
 * KABAGA Academy — Phase 4 shell UX (Escape closes top modal)
 */
(function () {
  'use strict';

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') {
      return;
    }
    var mod = document.getElementById('modModalOverlay');
    if (mod && mod.classList.contains('open')) {
      if (typeof window.closeModModal === 'function') {
        window.closeModModal();
      } else {
        mod.classList.remove('open');
      }
      e.preventDefault();
      return;
    }
    var q = document.getElementById('qModalOverlay');
    if (q && q.classList.contains('open')) {
      if (typeof window.closeModal === 'function') {
        window.closeModal();
      } else {
        q.classList.remove('open');
      }
      e.preventDefault();
    }
  });
})();
