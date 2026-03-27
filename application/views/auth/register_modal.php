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
    <div class="container py-3">
      <div class="card card-md login-card-future">
        <div class="card-body">
          <div class="login-brand">
            <img src="<?= base_url('assets/img/WMIS%20LOGO.png'); ?>" alt="WMIS logo" class="wmis-logo-login" />
            <div class="wmis-logo-subtext">KABAGA Academy</div>
            <div class="wmis-logo-hospital">LUNG CENTER OF THE PHILIPPINES</div>
          </div>
          <h2 class="h3 text-center mb-1">Create Your Account</h2>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error; ?></div>
          <?php endif; ?>
          <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success; ?></div>
          <?php endif; ?>

          <div class="card">

            <div class="forgot-panel">
              <?= form_open('auth/register_process'); ?>
              <div class="mb-3">
                <label class="form-label">Employee ID</label>

                <div class="input-group">
                  <input type="text"
                    name="employee_id"
                    id="employee_id"
                    class="form-control"
                    placeholder="Enter ID"
                    required />

                  <button class="btn btn-primary"
                    type="button"
                    id="checkEmployeeBtn">
                    Check ID
                  </button>
                </div>

                <small id="employee_status" class="form-text d-block mt-1"></small>
              </div>
              <div class="mb-2">
                <label class="form-label">Name</label>
                <input type="text" name="emp_name" id="emp_name" class="form-control" required />
              </div>
              <div class="mb-2">
                <label class="form-label">Department</label>
                <input type="text" name="department" id="department" class="form-control" required />
              </div>
              <div class="mb-2">
                <label class="form-label">New Password</label>
                <input type="password" name="password" id="password" class="form-control" minlength="8" autocomplete="one-time-code" readonly
                  onfocus="this.removeAttribute('readonly');" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="8" autocomplete="one-time-code" readonly
                  onfocus="this.removeAttribute('readonly');" required />
              </div>

              <div class="col-12 animate__animated animate__bounceIn animate__faster animate__delay-0-5s">
                <label class="form-check">
                  <input type="checkbox" name="agree_terms" class="form-check-input" required />
                  <span class="form-check-label">
                    Agree to the <a href="#" id="openTermsPanel">terms and policy</a>.
                  </span>
                </label>
              </div>

              <button type="submit" class="btn btn-primary w-100">Register</button>
              <?= form_close(); ?>
            </div>
          </div>

          <div class="text-center mt-3">
            <a href="<?= site_url('auth/login'); ?>" class="login-forgot-link">Back to Sign In</a>
          </div>
        </div>
      </div>
    </div>
  </div>


  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Tabler JS -->
  <script src="<?= base_url('assets/tabler/js/tabler.min.js'); ?>" defer></script>
  <script src="<?= base_url('assets/tabler/js/demo.min.js'); ?>" defer></script>
</body>
<script>
  $(document).ready(function() {
    $('#checkEmployeeBtn').on('click', function() {

      var employeeId = $('#employee_id').val();

      if (!employeeId) {
         $('#employee_status').text('Please enter Employee ID').css('color', 'red');
        return;
      }

      $.ajax({
        url: '<?= base_url("Auth/check_employee"); ?>',
        type: 'POST',
        data: {
          employee_id: employeeId
        },
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            $('#emp_name').val(response.name);
            $('#department').val(response.department);
            if (response.registered) {
              $('#employee_status').text('Employee ID is already registered').css('color', 'red');
              // $('#employee_icon').html('✗').css('color', 'red').show();
              //  disable form fields and submit button but not employee ID field and checkEmployeeBtn
              $('#emp_name, #department, #password, #confirm_password, #agree_terms, #register_submit').prop('disabled', true);
            } else {
              $('#employee_status').text('').css('color', 'red');
              $('#password, #confirm_password, #agree_terms, #register_submit').prop('disabled', false);
            }
            // $('#employee_icon').html('✓').css('color', 'green').show();
          } else {
            $('#employee_status').text('').css('color', 'red');
          }
        },
        error: function() {
          $('#employee_status').text('Error checking employee ID').css('color', 'red');
        }
      });
    });
  });
</script>

</html>