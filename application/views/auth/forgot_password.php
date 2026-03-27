<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
  <title>Forgot Password - LMS</title>

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

    <!-- Card -->
    <form class="card card-md"
          action="<?= base_url('auth/forgot_password_process'); ?>"
          method="post"
          autocomplete="off"
          novalidate>

      <div class="card-body">

        <h2 class="card-title text-center mb-4">
          Forgot your password?
        </h2>

        <p class="text-secondary text-center mb-4">
          Enter your Employee ID and we will help you reset your password.
        </p>

        <!-- Flash Messages -->
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

        <!-- Employee ID -->
        <div class="mb-3">
          <label class="form-label">Employee ID</label>
          <input type="text"
                 name="employee_id"
                 class="form-control"
                 placeholder="Enter your Employee ID"
                 required>
        </div>

        <div class="form-footer">
          <button type="submit" class="btn btn-primary w-100">
            Reset Password
          </button>
        </div>

      </div>
    </form>

    <div class="text-center text-secondary mt-3">
      Remember your password?
      <a href="<?= base_url('auth/login'); ?>">Back to login</a>
    </div>

  </div>
</div>

<!-- JS -->
<script src="<?= base_url('assets/tabler/js/tabler.min.js'); ?>" defer></script>
<script src="<?= base_url('assets/tabler/js/demo.min.js'); ?>" defer></script>

</body>
</html>
