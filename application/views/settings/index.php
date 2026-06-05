<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/settings.css'); ?>">

<div class="stg-page-head animate__animated animate__fadeIn animate__fast">
  <div>
    <h1 class="stg-page-title">Settings</h1>
    <p class="stg-page-subtitle">Manage platform behavior, branding, integrations, and learning configuration.</p>
  </div>
  <div class="stg-search-wrap">
    <label class="visually-hidden" for="stgSearch">Search settings</label>
    <input type="search" class="stg-search" id="stgSearch" placeholder="Search settings…" autocomplete="off">
  </div>
</div>

<?php if (empty($settings_ready)): ?>
<div class="alert alert-warning mb-3" role="alert">
  Platform settings table is not installed. Run <code>application/sql/migration_lms_settings.sql</code> to enable saving.
</div>
<?php endif; ?>

<form id="stgForm" method="post" action="<?= site_url('settings') ?>" enctype="multipart/form-data" class="stg-form ka-form-flow animate__animated animate__fadeInUp animate__fast">
  <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
  <input type="hidden" name="settings_save" value="1">

  <div class="stg-layout">
    <?php $this->load->view('settings/settings_sidebar'); ?>

    <div class="stg-main">
      <?php
      $this->load->view('settings/general', get_defined_vars());
      $this->load->view('settings/branding', get_defined_vars());
      $this->load->view('settings/learning', get_defined_vars());
      $this->load->view('settings/etd', get_defined_vars());
      $this->load->view('settings/certificates', get_defined_vars());
      $this->load->view('settings/notifications', get_defined_vars());
      $this->load->view('settings/hrmis', get_defined_vars());
      $this->load->view('settings/security', get_defined_vars());
      $this->load->view('settings/storage', get_defined_vars());
      $this->load->view('settings/integrations', get_defined_vars());
      $this->load->view('settings/advanced', get_defined_vars());
      ?>
    </div>
  </div>

  <div class="stg-sticky-save" id="stgSaveBar">
    <span class="stg-unsaved" id="stgUnsaved" aria-live="polite">Unsaved changes</span>
    <button type="submit" class="stg-btn-primary" <?= empty($settings_ready) ? 'disabled' : '' ?>>Save settings</button>
  </div>
</form>

<script defer src="<?= base_url('assets/js/settings.js'); ?>"></script>
