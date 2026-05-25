<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/profile.css'); ?>">

<div class="prf-page-head animate__animated animate__fadeIn animate__fast">
  <div>
    <h1 class="prf-page-title">My Profile</h1>
    <p class="prf-page-subtitle">Manage your account, security, and learning preferences in one place.</p>
  </div>
</div>

<?php $this->load->view('profile/profile_header', get_defined_vars()); ?>

<div class="prf-layout ka-form-flow animate__animated animate__fadeInUp animate__fast">
  <nav class="prf-nav" role="tablist" aria-label="Profile sections">
    <button type="button" class="prf-nav-link is-active" role="tab" id="prf-nav-account" data-prf-tab="account" aria-selected="true" aria-controls="prf-panel-account">
      Account
    </button>
    <button type="button" class="prf-nav-link" role="tab" id="prf-nav-security" data-prf-tab="security" aria-selected="false" aria-controls="prf-panel-security">
      Security
    </button>
    <button type="button" class="prf-nav-link" role="tab" id="prf-nav-activity" data-prf-tab="activity" aria-selected="false" aria-controls="prf-panel-activity">
      Activity
    </button>
    <button type="button" class="prf-nav-link" role="tab" id="prf-nav-preferences" data-prf-tab="preferences" aria-selected="false" aria-controls="prf-panel-preferences">
      Preferences
      <span class="prf-nav-badge">Soon</span>
    </button>
  </nav>

  <div class="prf-main">
    <?php $this->load->view('profile/profile_account', get_defined_vars()); ?>
    <?php $this->load->view('profile/profile_security', get_defined_vars()); ?>
    <?php $this->load->view('profile/profile_activity', get_defined_vars()); ?>
    <?php $this->load->view('profile/profile_preferences', get_defined_vars()); ?>
  </div>
</div>

<script defer src="<?= base_url('assets/js/profile.js'); ?>"></script>
