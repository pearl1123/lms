<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= htmlspecialchars($page_title ?? 'Coming soon', ENT_QUOTES, 'UTF-8') ?> — KABAGA Academy</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/ka-saas-tokens.css'); ?>"/>
  <link rel="stylesheet" href="<?= base_url('assets/css/error_fallback.css'); ?>"/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body class="ef-standalone-body">
  <?php
  if (empty($ef_dashboard_url)) {
      $ef_dashboard_url = site_url('auth/login');
  }
  $this->load->view('errors/_fallback_card', get_defined_vars());
  ?>
  <p class="ef-standalone-foot">
    <a href="<?= html_escape($ef_login_url ?? site_url('auth/login')) ?>">Sign in</a>
    <span aria-hidden="true">·</span>
    <a href="<?= html_escape(site_url('auth/login')) ?>">KABAGA Academy</a>
  </p>
</body>
</html>
