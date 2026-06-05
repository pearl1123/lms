<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>kaBAGA Academy — Register</title>
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
  <link rel="stylesheet" href="<?= base_url('assets/css/auth-register.css?v=20260601c'); ?>" />
</head>

<body class="wmis-app-shell wmis-login-page wmis-register-page">
  <div class="page page-center">
    <div class="container container-tight py-3">
      <div class="card card-md login-card-future wmis-register-card">
        <div class="card-body">
          <div class="login-brand text-center mb-3">
            <img src="<?= base_url('assets/img/LMS-LOGO.png'); ?>"
                 alt="Lung Center of the Philippines"
                 class="wmis-logo-login"
                 onerror="this.onerror=null;this.src='<?= base_url('assets/img/wmis-logo.svg'); ?>';" />
            <div class="wmis-logo-subtext">kaBAGA Academy</div>
            <div class="wmis-logo-hospital">LUNG CENTER OF THE PHILIPPINES</div>
          </div>
          <h2 class="h3 text-center mb-1">Create Your Account</h2>
          <p class="text-center text-muted small mb-3">Employee ID must exist in HRMIS (format <strong>LCP######</strong>).</p>

          <?php
          $show_error_alert = ! empty($error)
              && empty($auth_toast_error)
              && stripos((string) $error, 'session expired') === false;
          ?>
          <?php if ($show_error_alert): ?>
            <div class="alert alert-danger"><?= html_escape($error); ?></div>
          <?php endif; ?>
          <?php if ( ! empty($success)): ?>
            <div class="alert alert-success"><?= html_escape($success); ?></div>
          <?php endif; ?>

          <?= form_open('auth/register_process'); ?>
          <input type="hidden"
                 id="auth_csrf_field"
                 name="<?= html_escape($csrf_field_name ?? 'csrf_test_name'); ?>"
                 value="<?= html_escape($csrf_hash ?? ''); ?>">
          <div class="mb-3">
            <label class="form-label" for="employee_id">Employee ID</label>
            <div class="input-group">
              <input type="text"
                     name="employee_id"
                     id="employee_id"
                     class="form-control text-uppercase"
                     placeholder="LCP880201"
                     pattern="LCP[0-9]{6}"
                     title="Format: LCP followed by 6 digits"
                     autocomplete="username"
                     value="<?= html_escape($employee_id_value ?? ''); ?>"
                     required />
              <button class="btn btn-primary" type="button" id="checkEmployeeBtn">
                <span class="btn-label">Verify in HRMIS</span>
                <span class="btn-spinner d-none" aria-hidden="true">…</span>
              </button>
            </div>
            <small id="employee_status" class="form-text d-block mt-1"></small>
          </div>

          <div class="mb-2">
            <label class="form-label">Name</label>
            <div id="emp_name_display" class="form-control-plaintext border rounded px-3 py-2 bg-light text-muted">Verify Employee ID in HRMIS first</div>
          </div>
          <div class="mb-2">
            <label class="form-label">Department</label>
            <div id="department_display" class="form-control-plaintext border rounded px-3 py-2 bg-light text-muted">—</div>
          </div>
          <div class="mb-2">
            <label class="form-label">Position</label>
            <div id="position_display" class="form-control-plaintext border rounded px-3 py-2 bg-light text-muted">—</div>
          </div>

          <div class="mb-2">
            <label class="form-label" for="password">New Password</label>
            <input type="password" name="password" id="password" class="form-control" minlength="8" autocomplete="new-password" disabled required />
          </div>
          <div class="mb-3">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="8" autocomplete="new-password" disabled required />
          </div>

          <div class="mb-3">
            <label class="form-check">
              <input type="checkbox" name="agree_terms" id="terms_checkbox" class="form-check-input" disabled required />
              <span class="form-check-label">I agree to the <a href="#" id="openTermsPanel">terms and policy</a>.</span>
            </label>
          </div>

          <button type="submit" id="register_submit" class="btn btn-primary w-100 wmis-register-submit" disabled>Create Account</button>
          <?= form_close(); ?>

          <div class="text-center mt-3">
            <a href="<?= site_url('auth/login'); ?>" class="login-forgot-link">Back to Sign In</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php $this->load->view('auth/terms_panel'); ?>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <script>
  (function($) {
    var csrfName = <?= json_encode($csrf_field_name ?? '') ?>;
    var csrfHash = <?= json_encode($csrf_hash ?? '') ?>;
    var hrmisBlockMsg = <?= json_encode(HRMIS_REGISTRATION_BLOCK_MESSAGE) ?>;
    var sessionExpiredMsg = <?= json_encode('Session expired. Please refresh the page.') ?>;
    var authToastError = <?= json_encode($auth_toast_error ?? '') ?>;
    var verified = false;
    var termsAccepted = false;

    function applyCsrfToken(name, hash) {
      if (!name || !hash) return;
      csrfName = name;
      csrfHash = hash;
      $('#auth_csrf_field').attr('name', name).val(hash);
    }

    function showSessionToast(message) {
      if (!window.Swal) return;
      Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true
      }).fire({
        icon: 'warning',
        title: message || sessionExpiredMsg
      });
    }

    if (authToastError) {
      showSessionToast(authToastError);
    }

    $('#openTermsPanel').on('click', function(e) {
      e.preventDefault();
      $('#termsPanel').addClass('show').attr('aria-hidden', 'false');
      $('#termsPanelBackdrop').addClass('show').attr('aria-hidden', 'false');
    });

    $('#closeTermsPanel, #termsPanelBackdrop').on('click', function() {
      $('#termsPanel').removeClass('show').attr('aria-hidden', 'true');
      $('#termsPanelBackdrop').removeClass('show').attr('aria-hidden', 'true');
    });

    function syncTermsCheckbox() {
      var $cb = $('#terms_checkbox');
      if (!termsAccepted) {
        return;
      }
      $cb.prop('checked', true);
      if (verified) {
        $cb.prop('disabled', false);
      }
    }

    $('#acceptTermsBtn, #agree_terms_button').on('click', function() {
      termsAccepted = true;
      syncTermsCheckbox();
      $('#termsPanel').removeClass('show').attr('aria-hidden', 'true');
      $('#termsPanelBackdrop').removeClass('show').attr('aria-hidden', 'true');
    });

    $('#terms_checkbox').on('change', function() {
      if ($(this).is(':checked')) {
        termsAccepted = true;
      }
    });

    $('.terms-toc a').on('click', function(e) {
      e.preventDefault();
      var target = $(this).attr('href');
      var $content = $('#termsContent');
      var $section = $content.find(target);
      if ($section.length) {
        $content.animate({
          scrollTop: $section.position().top + $content.scrollTop()
        }, 400);
      }
    });

    function setLoading(on) {
      $('#checkEmployeeBtn').prop('disabled', on);
      $('#checkEmployeeBtn .btn-label').toggleClass('d-none', on);
      $('#checkEmployeeBtn .btn-spinner').toggleClass('d-none', !on);
    }

    function lockRegistration() {
      verified = false;
      $('#password, #confirm_password, #terms_checkbox, #register_submit').prop('disabled', true);
      if (!termsAccepted) {
        $('#terms_checkbox').prop('checked', false);
      }
    }

    function unlockRegistration() {
      verified = true;
      $('#password, #confirm_password, #terms_checkbox, #register_submit').prop('disabled', false);
      syncTermsCheckbox();
    }

    $('#employee_id').on('input', function() {
      this.value = this.value.toUpperCase().replace(/\s/g, '');
      lockRegistration();
      $('#employee_status').text('');
    });

    $('#register_submit').closest('form').on('submit', function(e) {
      if (!verified) {
        e.preventDefault();
        $('#employee_status').text('Verify your Employee ID in HRMIS before registering.').css('color', '#dc2626');
        return;
      }
      if (!$('#terms_checkbox').is(':checked')) {
        e.preventDefault();
        $('#employee_status').text('You must agree to the terms and policy.').css('color', '#dc2626');
      }
    });

    $('#checkEmployeeBtn').on('click', function() {
      var employeeId = $('#employee_id').val().trim().toUpperCase();
      $('#employee_id').val(employeeId);
      lockRegistration();

      if (!employeeId) {
        $('#employee_status').text('Please enter an Employee ID.').css('color', '#dc2626');
        return;
      }

      if (!/^LCP[0-9]{6}$/.test(employeeId)) {
        $('#employee_status').text('Employee ID must be LCP###### (e.g. LCP880201).').css('color', '#dc2626');
        return;
      }

      setLoading(true);
      var postData = { employee_id: employeeId };
      if (csrfName && csrfHash) {
        postData[csrfName] = csrfHash;
      }

      $.ajax({
        url: <?= json_encode(site_url('auth/check_employee')) ?>,
        type: 'POST',
        data: postData,
        dataType: 'json'
      }).done(function(response) {
        if (response.csrf_error) {
          showSessionToast(response.message || sessionExpiredMsg);
          $('#employee_status').text('').css('color', '');
          return;
        }

        if (response.csrf_field_name && response.csrf_hash) {
          applyCsrfToken(response.csrf_field_name, response.csrf_hash);
        }

        if (response.success && !response.registered) {
          $('#emp_name_display').text(response.name || '—').removeClass('text-muted');
          $('#department_display').text(response.department || '—').removeClass('text-muted');
          $('#position_display').text(response.position || '—').removeClass('text-muted');
          $('#employee_status').text('Employee verified in HRMIS. You may complete registration.').css('color', '#16a34a');
          unlockRegistration();
        } else if (response.success && response.registered) {
          $('#employee_status').text('This Employee ID is already registered.').css('color', '#dc2626');
          $('#emp_name_display, #department_display, #position_display').addClass('text-muted');
        } else {
          $('#employee_status').text(response.message || hrmisBlockMsg).css('color', '#dc2626');
          $('#emp_name_display, #department_display, #position_display').text('—').addClass('text-muted');
        }
      }).fail(function() {
        $('#employee_status').text('Unable to verify Employee ID. Please try again.').css('color', '#dc2626');
      }).always(function() {
        setLoading(false);
      });
    });

    var restoredEmployeeId = $('#employee_id').val().trim();
    if (restoredEmployeeId && /^LCP[0-9]{6}$/.test(restoredEmployeeId)) {
      $('#checkEmployeeBtn').trigger('click');
    }
  })(jQuery);
  </script>
</body>
</html>
