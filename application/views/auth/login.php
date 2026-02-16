<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
  <title>LMS Login</title>

  <!-- Tabler CSS -->
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
    body {
      font-feature-settings: "cv03", "cv04", "cv11";
    }
  </style>
</head>

<body class="d-flex flex-column bg-white">

  <div class="row g-0 flex-fill">

    <!-- LEFT LOGIN PANEL -->
    <div class="col-12 col-lg-6 col-xl-4 border-top-wide border-primary d-flex flex-column justify-content-center">
      <div class="container container-tight my-5 px-lg-5">

        <!-- LOGO -->
        <div class="text-center mb-4">
          <a href="<?= base_url(); ?>" class="navbar-brand navbar-brand-autodark">
            <img src="<?= base_url('assets/tabler/img/logo.svg'); ?>" height="36" alt="LMS Logo">
          </a>
        </div>

        <h2 class="h3 text-center mb-3">
          Login to your LMS
        </h2>
        <?php if($this->session->flashdata('error')): ?>
        <div class="alert alert-danger">
            <?= $this->session->flashdata('error'); ?>
        </div>
        <?php endif; ?>

        <?php if($this->session->flashdata('success')): ?>
        <div class="alert alert-success">
            <?= $this->session->flashdata('success'); ?>
        </div>
        <?php endif; ?>
        <!-- LOGIN FORM -->
        <form action="<?= base_url('auth/login_process'); ?>" method="post" autocomplete="off" novalidate>

          <div class="mb-3">
            <label class="form-label">Employee ID</label>
            <input type="text" name="employee_id" class="form-control" placeholder="your employee ID" required>
          </div>

          <div class="mb-2">
            <label class="form-label">
              Password
              <span class="form-label-description">
                <a href="<?= base_url('auth/forgot_password'); ?>">I forgot password</a>
              </span>
            </label>

            <div class="input-group input-group-flat">
              <input type="password" name="password" class="form-control" placeholder="Your password" required>
              <span class="input-group-text">
                <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                       stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                    <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                  </svg>
                </a>
              </span>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-check">
              <input type="checkbox" name="remember" class="form-check-input"/>
              <span class="form-check-label">Remember me</span>
            </label>
          </div>

          <div class="form-footer">
            <button type="submit" class="btn btn-primary w-100">
              Sign in
            </button>
          </div>
        </form>

        <div class="text-center text-secondary mt-3">
          Don't have an account yet? 
          <a href="<?= base_url('index.php/auth/register'); ?>">Sign up</a>
        </div>

      </div>
    </div>

    <!-- RIGHT IMAGE PANEL -->
    <div class="col-12 col-lg-6 col-xl-8 d-none d-lg-block">
      <div class="bg-cover h-100 min-vh-100"
           style="background-image: url('<?= base_url('assets/tabler/img/photos/finances-us-dollars-and-bitcoins-currency-money-2.jpg'); ?>');">
      </div>
    </div>

  </div>

  <!-- Tabler JS -->
  <script src="<?= base_url('assets/tabler/js/tabler.min.js'); ?>" defer></script>
  <script src="<?= base_url('assets/tabler/js/demo.min.js'); ?>" defer></script>

</body>
</html>