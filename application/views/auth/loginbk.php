<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
  <title>KABAGA Academy</title>

  <!-- Tabler CSS -->
  <link href="<?= base_url('assets/tabler/css/tabler.min.css'); ?>" rel="stylesheet"/>
  <link href="<?= base_url('assets/tabler/css/tabler-flags.min.css'); ?>" rel="stylesheet"/>
  <link href="<?= base_url('assets/tabler/css/tabler-payments.min.css'); ?>" rel="stylesheet"/>
  <link href="<?= base_url('assets/tabler/css/tabler-vendors.min.css'); ?>" rel="stylesheet"/>
  <link href="<?= base_url('assets/tabler/css/demo.min.css'); ?>" rel="stylesheet"/>

  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

  <style>
    @import url('https://rsms.me/inter/inter.css');

    body {
      font-family: 'Inter Var', sans-serif;
      height: 100vh;
      overflow: hidden;
      background-color: #f0f4f8;
    }

    /* Login card animation */
    .login-card {
      background: #ffffff;
      padding: 40px 30px;
      border-radius: 16px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.08);
      width: 100%;
      max-width: 400px;
      transition: all 0.3s ease;
      opacity: 0;
      transform: translateY(20px);
    }

    .login-card.animate__animated.animate__fadeInUp {
      opacity: 1;
      transform: translateY(0);
    }

    .login-card:hover { 
      transform: translateY(-3px); 
      box-shadow: 0 25px 50px rgba(0,0,0,0.12); 
    }

    /* Input lift */
    input.form-control {
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    input.form-control:focus, input.form-control:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.12);
    }

    /* Button hover + pulse */
    .btn-pastel-blue {
      background-color: #6dabcf; 
      border-color: #6dabcf; 
      color: white; 
      border-radius: 8px; 
      font-weight: 600; 
      padding: 10px; 
      transition: all 0.2s ease; 
    }
    .btn-pastel-blue:hover { 
      background-color: #5a9ec1; 
      border-color: #5a9ec1; 
      transform: translateY(-2px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.15);
    }

    /* Right panel */
    .right-panel { position: relative; background-size: cover; background-position: center; }
    .right-panel::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(0,0,0,0.3), rgba(0,0,0,0.6)); z-index: 1; }
    .right-panel-content { position: relative; z-index: 2; text-align: center; color: #ffffff; padding: 20px; }
    .right-panel-content h2, .right-panel-content p { opacity: 0; }
    .right-panel-content.animate h2 { animation: fadeInUp 0.8s forwards 0.5s; }
    .right-panel-content.animate p { animation: fadeInUp 0.8s forwards 1s; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>

<body>

<div class="row g-0 flex-fill h-100">

  <!-- LEFT LOGIN PANEL -->
  <div class="col-12 col-lg-6 col-xl-4 d-flex align-items-center justify-content-center min-vh-100">
    <div class="login-card">

      <!-- LOGO -->
      <a href="<?= base_url(''); ?>" class="navbar-brand navbar-brand-autodark login-logo d-flex justify-content-center">
        <img src="<?= base_url('assets/tabler/img/logo.png'); ?>" height="160" alt="KABAGA Academy Logo">
      </a>

      <!-- FLASH & VALIDATION MESSAGES -->
      <?php if($error = $this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show w-100" role="alert">
          <?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if($success = $this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show w-100" role="alert">
          <?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (validation_errors()): ?>
        <div class="alert alert-danger alert-dismissible fade show w-100" role="alert">
          <?= validation_errors(); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- LOGIN FORM -->
      <form action="<?= site_url('auth/login_process'); ?>" method="post" autocomplete="off" novalidate class="w-100">

        <div class="mb-3 animate__animated animate__bounceIn" style="animation-delay: 0.2s;">
          <label class="form-label">Employee ID</label>
          <input type="text" name="employee_id" value="<?= set_value('employee_id'); ?>" class="form-control" placeholder="Your employee ID" required>
        </div>

        <div class="mb-3 animate__animated animate__bounceIn" style="animation-delay: 0.4s;">
          <label class="form-label">
            Password
            <span class="form-label-description">
              <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">I forgot password</a>
            </span>
          </label>
          <div class="input-group input-group-flat">
            <input type="password" name="password" class="form-control" placeholder="Your password" required>
          </div>
        </div>

        <div class="mb-3 animate__animated animate__bounceIn" style="animation-delay: 0.6s;">
          <label class="form-check">
            <input type="checkbox" name="remember" class="form-check-input"/>
            <span class="form-check-label">Remember me</span>
          </label>
        </div>

        <div class="form-footer mb-3 animate__animated animate__bounceIn" style="animation-delay: 0.8s;">
          <button type="submit" class="btn btn-pastel-blue w-100">Sign in</button>
        </div>

      </form>

      <div class="text-center text-secondary mt-3 w-100 animate__animated animate__fadeInUp animate__delay-1s">
          Don't have an account yet? 
          <a href="#" data-bs-toggle="modal" data-bs-target="#signUpModal">Sign up</a>
      </div>

    </div>
  </div>

  <!-- RIGHT IMAGE PANEL -->
  <div class="col-12 col-lg-6 col-xl-8 d-none d-lg-flex right-panel justify-content-center align-items-center min-vh-100"
       style="background-image: url('<?= base_url('assets/tabler/img/KA_bg.jpg'); ?>');">
    <div class="right-panel-content">
      <h2>Welcome to KABAGA Academy</h2>
      <p>Empowering learners with the skills of tomorrow.</p>
    </div>
  </div>
</div>

<!-- FORGOT PASSWORD MODAL -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Forgot Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="mb-3">
            <label class="form-label">Enter your email address</label>
            <input type="email" class="form-control" placeholder="you@example.com" required>
          </div>
          <div class="text-end">
            <button type="submit" class="btn btn-pastel-blue">Reset Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function(){
  // Animate login card
  $('.login-card').addClass('animate__animated animate__fadeInUp');

  // Animate right panel text
  $('.right-panel-content').addClass('animate');
});
</script>

<!-- Tabler JS -->
<script src="<?= base_url('assets/tabler/js/tabler.min.js'); ?>" defer></script>
<script src="<?= base_url('assets/tabler/js/demo.min.js'); ?>" defer></script>
<!-- Include Sign Up Modal -->
<?php $this->load->view('auth/register_modal'); ?>
</body>
</html>