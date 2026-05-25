<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * CI3 show_404() template — premium fallback (preserves 404 status & logging).
 * Router 404_override uses Error_pages::not_found() with app shell when logged in.
 */
$ef_title    = 'Page not found';
$ef_subtitle = 'The page you requested is not available.';
$ef_hint     = isset($message) && $message !== ''
    ? strip_tags((string) $message)
    : 'We\'re actively improving this part of the LMS.';
$ef_icon          = 'compass';
$ef_dashboard_url = function_exists('site_url') ? site_url('dashboard') : '/';
$ef_login_url     = function_exists('site_url') ? site_url('auth/login') : '/';
$page_title       = 'Page not found';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?> — KABAGA Academy</title>
  <link rel="stylesheet" href="<?= function_exists('base_url') ? base_url('assets/css/ka-saas-tokens.css') : '' ?>"/>
  <link rel="stylesheet" href="<?= function_exists('base_url') ? base_url('assets/css/error_fallback.css') : '' ?>"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="ef-standalone-body">
<?php include dirname(__DIR__) . DIRECTORY_SEPARATOR . '_fallback_card.php'; ?>
<p class="ef-standalone-foot">
  <a href="<?= html_escape($ef_login_url) ?>">Sign in</a>
</p>
</body>
</html>
