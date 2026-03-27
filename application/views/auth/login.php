<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>KABAGA Academy</title>
  <script>
    (function() {
      var key = "wmis-theme";
      var theme = "light";
      try {
        var stored = window.sessionStorage.getItem(key);
        if (stored === "dark" || stored === "light") {
          theme = stored;
        }
      } catch (e) {}
      try {
        window.localStorage.removeItem(key);
      } catch (e) {}
      document.documentElement.setAttribute("data-theme", theme);
      document.documentElement.setAttribute("data-bs-theme", theme);
    })();
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= base_url('assets/tabler/css/tabler.min.css'); ?>" />
  <link rel="stylesheet" href="<?= base_url('assets/css/custom.css?v=20260317navytheme01'); ?>" />
</head>

<body class="wmis-app-shell wmis-login-page">
  <div class="wmis-ambient" aria-hidden="true">
    <span class="ambient-orb ambient-orb-a"></span>
    <span class="ambient-orb ambient-orb-b"></span>
    <span class="ambient-grid"></span>
  </div>
  <div class="wmis-login-theme-corner">
    <button type="button" class="btn btn-sm wmis-theme-toggle wmis-icon-button" data-theme-toggle aria-label="Toggle dark mode">
      <span class="wmis-icon-glyph" aria-hidden="true">dark_mode</span>
      <span class="wmis-theme-toggle-label visually-hidden">Dark mode</span>
    </button>
  </div>

  <div class="page page-center">
    <div class="container container-tight py-3">
      <div class="card card-md login-card-future">
        <div class="card-body">
          <div class="login-brand">
            <img src="<?= base_url('assets/img/WMIS%20LOGO.png'); ?>" alt="WMIS logo" class="wmis-logo-login" />
            <div class="wmis-logo-subtext">KABAGA Academy</div>
            <div class="wmis-logo-hospital">LUNG CENTER OF THE PHILIPPINES</div>
          </div>
          <h2 class="h2 text-center mb-1">Welcome Back</h2>
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= html_escape($error); ?></div>
          <?php endif; ?>
          <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger"><?= $this->session->flashdata('error'); ?></div>
          <?php endif; ?>
          <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success"><?= $this->session->flashdata('success'); ?></div>
          <?php endif; ?>
          <?= form_open('auth/login_process'); ?>
          <div class="mb-3">
            <label class="form-label">Employee ID</label>
            <input
              type="text"
              name="employee_id"
              class="form-control"
              value="<?= html_escape(set_value('employee_id', !empty($remembered_employee_id) ? $remembered_employee_id : '')); ?>"
              placeholder="Your employee ID"
              required
              autofocus
              auto />
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required />
          </div>
          <div class="login-utility-row">
            <label class="form-check m-0">
              <input class="form-check-input" type="checkbox" name="remember_me" value="1" <?= !empty($remember_me) ? 'checked' : ''; ?>>
              <span class="form-check-label">Remember me</span>
            </label>
            <a href="<?= site_url('auth/forgot-password'); ?>" class="login-forgot-link">Forgot password?</a>
          </div>
          <div class="form-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Sign in</button>
            <a href="<?= site_url('auth/register'); ?>" class="btn btn-outline-primary w-100">Create Account</a>
          </div>
          <?= form_close(); ?>
        </div>
      </div>
    </div>
  </div>
  <script src="<?= base_url('assets/vendor/tabler/js/tabler.min.js'); ?>"></script>
  <script src="<?= base_url('assets/js/app.js?v=20260319controlroomtheme01'); ?>"></script>
</body>

</html>