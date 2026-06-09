<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>kaBAGA Academy — Forgot Password</title>
  <script>
    (function() {
      var key = "wmis-theme";
      var theme = "light";
      try {
        var stored = window.sessionStorage.getItem(key);
        if (stored === "dark" || stored === "light") theme = stored;
      } catch (e) {}
      document.documentElement.setAttribute("data-theme", theme);
      document.documentElement.setAttribute("data-bs-theme", theme);
    })();
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= base_url('assets/tabler/css/tabler.min.css'); ?>" />
  <link rel="stylesheet" href="<?= base_url('assets/css/custom.css?v=20260317navytheme01'); ?>" />
  <link rel="stylesheet" href="<?= base_url('assets/css/auth-register.css?v=20260601d'); ?>" />
</head>

<body class="wmis-app-shell wmis-login-page wmis-forgot-page">
  <div class="wmis-ambient" aria-hidden="true">
    <span class="ambient-orb ambient-orb-a"></span>
    <span class="ambient-orb ambient-orb-b"></span>
    <span class="ambient-grid"></span>
  </div>

  <div class="page page-center">
    <div class="container container-tight py-3">
      <div class="card card-md login-card-future wmis-forgot-card">
        <div class="card-body">
          <div class="login-brand text-center mb-3">
            <img src="<?= base_url('assets/img/LMS-LOGO.png'); ?>"
                 alt="Lung Center of the Philippines"
                 class="wmis-logo-login"
                 onerror="this.onerror=null;this.src='<?= base_url('assets/img/wmis-logo.svg'); ?>';" />
            <div class="wmis-logo-hospital">LUNG CENTER OF THE PHILIPPINES</div>
          </div>

          <h2 class="h3 text-center mb-1">Forgot your password?</h2>
          <p class="text-center text-muted small mb-3 wmis-forgot-helper">
            Enter your registered Employee ID and we will help you reset your password.
          </p>

          <?php $flash_messages = $flash_messages ?? []; ?>
          <?php if ( ! empty($flash_messages['error'])): ?>
            <div class="alert alert-danger"><?= html_escape($flash_messages['error']); ?></div>
          <?php endif; ?>
          <?php if ( ! empty($flash_messages['success'])): ?>
            <div class="alert alert-success"><?= html_escape($flash_messages['success']); ?></div>
          <?php endif; ?>
          <?php if ( ! empty($reset_dev_url)): ?>
            <div class="alert alert-info small mb-3">
              <strong>Development reset link</strong> (expires in 60 minutes):<br>
              <a href="<?= html_escape($reset_dev_url); ?>"><?= html_escape($reset_dev_url); ?></a>
            </div>
          <?php endif; ?>

          <form method="post"
                action="<?= html_escape($forgot_form_action ?? ''); ?>"
                autocomplete="off"
                id="forgot_password_form">
            <input type="hidden"
                   name="<?= html_escape($csrf_field_name ?? 'csrf_test_name'); ?>"
                   value="<?= html_escape($csrf_hash ?? ''); ?>">

            <div class="mb-3">
              <label class="form-label" for="forgot_employee_id">Employee ID</label>
              <input type="text"
                     name="employee_id"
                     id="forgot_employee_id"
                     class="form-control"
                     value="<?= html_escape($employee_id_value ?? ''); ?>"
                     placeholder="Your employee ID"
                     required
                     autofocus />
            </div>

            <button type="submit" class="btn btn-primary w-100 wmis-forgot-submit" id="forgot_submit_btn">
              <span class="btn-text">Reset Password</span>
              <span class="btn-spinner d-none" aria-hidden="true"></span>
            </button>
          </form>

          <div class="text-center mt-3">
            <a href="<?= html_escape(! empty($login_url) ? $login_url : site_url('auth/login')); ?>" class="login-forgot-link">Back to Sign In</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="<?= base_url('assets/tabler/js/demo-theme.min.js'); ?>"></script>
  <script>
  (function() {
    var form = document.getElementById('forgot_password_form');
    var btn = document.getElementById('forgot_submit_btn');
    if (!form || !btn) return;
    form.addEventListener('submit', function() {
      btn.classList.add('is-loading');
      btn.disabled = true;
      var text = btn.querySelector('.btn-text');
      var spin = btn.querySelector('.btn-spinner');
      if (text) text.classList.add('d-none');
      if (spin) spin.classList.remove('d-none');
    });
  })();
  </script>
</body>
</html>
