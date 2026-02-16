<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
  <title>Sign up - LMS</title>

  <!-- CSS files -->
  <link href="<?= base_url('assets/tabler/css/tabler.min.css'); ?>" rel="stylesheet"/>
  <link href="<?= base_url('assets/tabler/css/tabler-flags.min.css'); ?>" rel="stylesheet"/>
  <link href="<?= base_url('assets/tabler/css/tabler-payments.min.css'); ?>" rel="stylesheet"/>
  <link href="<?= base_url('assets/tabler/css/tabler-vendors.min.css'); ?>" rel="stylesheet"/>
  <link href="<?= base_url('assets/tabler/css/demo.min.css'); ?>" rel="stylesheet"/>
  
  <style>
    @import url('https://rsms.me/inter/inter.css');
    :root {
      --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
    }
    body { font-feature-settings: "cv03", "cv04", "cv11"; }
  </style>
</head>
<?php if($this->session->flashdata('error')): ?>
<div class="alert alert-danger">
    <?= $this->session->flashdata('error'); ?>
</div>
<?php endif; ?>

<body class="d-flex flex-column">

<script src="<?= base_url('assets/tabler/js/demo-theme.min.js'); ?>"></script>

<div class="page page-center">
  <div class="container container-tight py-4">

    <!-- Logo -->
    <div class="text-center mb-4">
      <a href="<?= base_url(); ?>" class="navbar-brand navbar-brand-autodark">
        <img src="<?= base_url('assets/tabler/img/logo.svg'); ?>" width="110" height="32" alt="LMS Logo">
      </a>
    </div>

    <!-- Registration Form -->
    <form class="card card-md" id="registerForm" action="<?= base_url('auth/register_process'); ?>" method="post" autocomplete="off" novalidate>
      <div class="card-body">
        <h2 class="card-title text-center mb-4">Create new account</h2>

        <!-- Employee ID -->
        <div class="mb-3">
          <label class="form-label">Employee ID</label>
          <input type="text" name="employee_id" id="employee_id" class="form-control" placeholder="Enter Employee ID" required>
          <small id="employee_status" class="text-muted"></small>
        </div>

        <!-- Name -->
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" name="name" id="name" class="form-control" placeholder="Employee Name" readonly required>
        </div>

        <!-- Password -->
        <div class="mb-3">
          <label class="form-label">Password</label>
          <div class="input-group input-group-flat">
            <input type="password" name="password" id="password" class="form-control" placeholder="Password" autocomplete="off" required>
            <span class="input-group-text">
              <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                  <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                </svg>
              </a>
            </span>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="mb-3">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password" required>
          <small id="password_match" class="text-danger"></small>
        </div>

        <!-- Terms -->
        <div class="mb-3">
          <label class="form-check">
            <input type="checkbox" name="agree_terms" class="form-check-input" required/>
            <span class="form-check-label">
              Agree to the <a href="<?= base_url('terms'); ?>">terms and policy</a>.
            </span>
          </label>
        </div>

        <div class="form-footer">
          <button type="submit" class="btn btn-primary w-100" id="submitBtn">Create new account</button>
        </div>

      </div>
    </form>

    <div class="text-center text-secondary mt-3">
      Already have account? <a href="<?= base_url('index.php/auth/login'); ?>">Sign in</a>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){

  var employeeValid = false;

  $('#employee_id').on('blur', function(){
    var emp_id = $(this).val().trim();
    if(emp_id.length > 0){
      $('#employee_status').text('Checking...');
      $.ajax({
        url: "<?= base_url('auth/check_employee'); ?>",
        type: "POST",
        data: { employee_id: emp_id },
        dataType: "json",
        success: function(resp){
          if(resp.exists && !resp.registered){
            $('#employee_status').text('Employee ID valid. Name loaded.');
            $('#name').val(resp.name);
            employeeValid = true;
          } else if(resp.registered){
            $('#employee_status').text('This Employee ID is already registered!');
            $('#name').val('');
            employeeValid = false;
          } else {
            $('#employee_status').text('Employee ID not found in HRMIS!');
            $('#name').val('');
            employeeValid = false;
          }
        }
      });
    }
  });

  $('#registerForm').on('submit', function(e){
    if($('#password').val() !== $('#confirm_password').val()){
      e.preventDefault();
      $('#password_match').text('Passwords do not match!');
    } else if(!employeeValid){
      e.preventDefault();
      alert('Cannot submit. Employee ID is invalid or already registered.');
    }
  });

});
</script>

<script src="<?= base_url('assets/tabler/js/tabler.min.js'); ?>" defer></script>
<script src="<?= base_url('assets/tabler/js/demo.min.js'); ?>" defer></script>

</body>
</html>