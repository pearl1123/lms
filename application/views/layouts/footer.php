<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$year = date('Y');
?>

<!-- ============================================================
     KABAGA ACADEMY — Footer
     Premium LMS Layout — Lung Center of the Philippines
============================================================ -->

</div><!-- /ka-page-content -->

<style>
  /* ── Footer ── */
  .ka-footer {
    background: #ffffff;
    border-top: 1px solid var(--ka-border, #e2e8f0);
    padding: 1.25rem 1.5rem;
    margin-top: auto;
  }
  .ka-footer-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
  }
  .ka-footer-brand {
    display: flex; align-items: center; gap: 10px;
    text-decoration: none;
  }
  .ka-footer-logo {
    width: 28px; height: 28px;
    border-radius: 7px;
    background: var(--ka-navy, #1a3a5c);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
  }
  .ka-footer-logo img { width: 100%; height: 100%; object-fit: contain; padding: 3px; filter: brightness(0) invert(1); }
  .ka-footer-brand-text { display: flex; flex-direction: column; }
  .ka-footer-brand-name {
    font-size: 0.8125rem; font-weight: 700;
    color: var(--ka-text, #1e293b); line-height: 1.2;
  }
  .ka-footer-brand-org {
    font-size: 0.6875rem;
    color: var(--ka-text-muted, #64748b);
    line-height: 1.3;
  }

  .ka-footer-links {
    display: flex; align-items: center; gap: 1rem;
    flex-wrap: wrap;
  }
  .ka-footer-links a {
    font-size: 0.75rem; font-weight: 500;
    color: var(--ka-text-muted, #64748b);
    text-decoration: none;
    transition: color 0.15s;
    white-space: nowrap;
  }
  .ka-footer-links a:hover { color: var(--ka-primary, #6dabcf); }

  .ka-footer-copy {
    font-size: 0.6875rem;
    color: var(--ka-text-muted, #64748b);
    text-align: right;
    white-space: nowrap;
  }
  .ka-footer-copy span { color: var(--ka-primary, #6dabcf); font-weight: 600; }

  @media (max-width: 767.98px) {
    .ka-footer-inner { flex-direction: column; align-items: flex-start; gap: 1rem; }
    .ka-footer-copy { text-align: left; }
  }
</style>

<footer class="ka-footer">
  <div class="ka-footer-inner">

    <!-- Brand -->
    <a href="<?= base_url('dashboard'); ?>" class="ka-footer-brand">
      <div class="ka-footer-logo">
        <img src="<?= base_url('assets/tabler/img/logo.png'); ?>" alt="KABAGA Academy">
      </div>
      <div class="ka-footer-brand-text">
        <span class="ka-footer-brand-name">KABAGA Academy</span>
        <span class="ka-footer-brand-org">Lung Center of the Philippines</span>
      </div>
    </a>

    <!-- Quick links -->
    <nav class="ka-footer-links">
      <a href="<?= base_url('courses'); ?>">Courses</a>
      <a href="<?= base_url('certificates'); ?>">Certificates</a>
      <a href="<?= base_url('announcements'); ?>">Announcements</a>
      <a href="<?= base_url('help'); ?>">Help Center</a>
      <a href="<?= base_url('privacy'); ?>">Privacy Policy</a>
    </nav>

    <!-- Copyright -->
    <div class="ka-footer-copy">
      &copy; <?= $year ?> <span>Lung Center of the Philippines</span>. All rights reserved.
    </div>

  </div>
</footer>

</div><!-- /ka-main-wrap -->

<!-- ══ SCRIPTS ═══════════════════════════════════════════════ -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Tabler JS -->
<script src="<?= base_url('assets/tabler/js/tabler.min.js'); ?>" defer></script>
<script src="<?= base_url('assets/tabler/js/demo.min.js'); ?>" defer></script>

<script>
// ── Sidebar toggle (mobile) ──────────────────────────────────
function kaToggleSidebar() {
  const sidebar  = document.getElementById('kaSidebar');
  const overlay  = document.getElementById('kaSidebarOverlay');
  const isOpen   = sidebar.classList.contains('open');
  sidebar.classList.toggle('open', !isOpen);
  overlay.classList.toggle('show', !isOpen);
  document.body.style.overflow = !isOpen ? 'hidden' : '';
}

// Close on Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    const sidebar = document.getElementById('kaSidebar');
    if (sidebar && sidebar.classList.contains('open')) kaToggleSidebar();
  }
});

// ── Global search shortcut (Ctrl/Cmd + K) ──────────────────
document.addEventListener('keydown', function(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault();
    const input = document.getElementById('kaGlobalSearch');
    if (input) { input.focus(); input.select(); }
  }
});

// ── Auto-dismiss flash alerts ────────────────────────────────
document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
  setTimeout(function() {
    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
    if (bsAlert) bsAlert.close();
  }, 5000);
});

// ── Active nav highlight (fallback for PHP logic) ────────────
(function() {
  const path    = window.location.pathname;
  const links   = document.querySelectorAll('.ka-nav-link');
  links.forEach(function(link) {
    const href = link.getAttribute('href') || '';
    if (href && href !== '#' && path.includes(href.split('/').pop())) {
      link.classList.add('active');
    }
  });
})();

// ── Smooth page transitions ──────────────────────────────────
document.querySelectorAll('a[href]:not([href="#"]):not([data-bs-toggle]):not([target="_blank"])').forEach(function(link) {
  link.addEventListener('click', function(e) {
    const href = link.getAttribute('href');
    if (href && !href.startsWith('#') && !href.startsWith('javascript') && !e.ctrlKey && !e.metaKey) {
      document.body.style.opacity = '0.92';
      document.body.style.transition = 'opacity 0.15s ease';
    }
  });
});
window.addEventListener('pageshow', function() {
  document.body.style.opacity = '1';
});
</script>

</body>
</html>