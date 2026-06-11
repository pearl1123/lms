<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?php
  $ka_brand = function_exists('ka_branding_settings') ? ka_branding_settings() : [];
  $ka_lms_name = trim((string) ($ka_brand['lms_name'] ?? 'kaBAGA Academy'));
  $ka_org_name = trim((string) ($ka_brand['org_name'] ?? 'Lung Center of the Philippines'));
  $ka_logo_url = trim((string) ($ka_brand['logo_url'] ?? ''));
  if ($ka_logo_url === '') {
      $ka_logo_url = base_url('assets/img/LMS-LOGO.png');
  }
  ?>
  <title><?= htmlspecialchars($ka_lms_name, ENT_QUOTES, 'UTF-8') ?></title>
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
  <link rel="stylesheet" href="<?= base_url('assets/css/ka-saas-tokens.css'); ?>" />
  <link rel="stylesheet" href="<?= base_url('assets/css/ka-auth.css'); ?>" />
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
            <img src="<?= htmlspecialchars($ka_logo_url, ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($ka_lms_name, ENT_QUOTES, 'UTF-8') ?>"
                 class="wmis-logo-login"
                 onerror="this.onerror=null;this.src='<?= base_url('assets/img/LMS-LOGO.png'); ?>';" />
            <?php if ($ka_org_name !== ''): ?>
            <div class="wmis-logo-hospital"><?= htmlspecialchars(strtoupper($ka_org_name), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>
          <h4 class="h4 text-center mb-1">Empowering learners with the skills of tomorrow.</h4>
          </br>
          <?php
          $flash_messages    = $flash_messages ?? [];
          $csrf_field_name   = $csrf_field_name ?? '';
          $csrf_hash         = $csrf_hash ?? '';
          $error             = $error ?? '';
          ?>
          <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= html_escape($error); ?></div>
          <?php endif; ?>
          <?php if ( ! empty($flash_messages['error'])): ?>
            <div class="alert alert-danger"><?= $flash_messages['error']; ?></div>
          <?php endif; ?>
          <?php if ( ! empty($flash_messages['success'])): ?>
            <div class="alert alert-success"><?= $flash_messages['success']; ?></div>
          <?php endif; ?>
          <form method="post" action="<?= html_escape($login_form_action ?? '') ?>" autocomplete="off">
          <?php if ($csrf_field_name !== '' && $csrf_hash !== ''): ?>
          <input type="hidden" name="<?= html_escape($csrf_field_name); ?>" value="<?= html_escape($csrf_hash); ?>">
          <?php endif; ?>
          <div class="mb-3">
            <label class="form-label">Employee ID</label>
            <input
              type="text"
              name="employee_id"
              class="form-control"
              value="<?= html_escape($employee_id_value ?? ''); ?>"
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
              <input class="form-check-input" type="checkbox" name="remember_me" value="1" <?= ! empty($remember_me_checked ?? false) ? 'checked' : ''; ?>>
              <span class="form-check-label">Remember me</span>
            </label>
            <a href="<?= html_escape(! empty($forgot_password_url) ? $forgot_password_url : site_url('auth/forgot-password')); ?>" class="login-forgot-link">Forgot password?</a>
          </div>
          <div class="form-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Sign in</button>
            <a href="<?= html_escape($register_url ?? ''); ?>" class="btn btn-outline-primary w-100">Create Account</a>
          </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <script src="<?= base_url('assets/tabler/js/demo-theme.min.js'); ?>"></script>
</body>

</html>