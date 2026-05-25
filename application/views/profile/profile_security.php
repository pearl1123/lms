<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="prf-panel" role="tabpanel" id="prf-panel-security" data-prf-tab="security" aria-labelledby="prf-nav-security" hidden>
  <div class="prf-card">
    <div class="prf-card-hdr">
      <h2 class="prf-card-title">Security</h2>
      <p class="prf-card-kicker">Update your password. Use at least 8 characters with a mix of letters and numbers.</p>
    </div>
    <div class="prf-card-body">
      <form method="post" action="<?= base_url('profile') ?>" autocomplete="off">
        <input type="hidden" name="<?= html_escape($csrf_field_name ?? '') ?>" value="<?= html_escape($csrf_hash ?? '') ?>">
        <input type="hidden" name="password_submit" value="1">

        <div class="prf-field">
          <label class="prf-label" for="prf_current_password">Current password <span class="req">*</span></label>
          <input type="password" id="prf_current_password" name="current_password" class="prf-input" required autocomplete="current-password">
        </div>

        <div class="prf-row-2">
          <div class="prf-field">
            <label class="prf-label" for="prfNewPassword">New password <span class="req">*</span></label>
            <input type="password" id="prfNewPassword" name="new_password" class="prf-input" required minlength="8" autocomplete="new-password">
            <div class="prf-pwd-strength" aria-hidden="true">
              <div class="prf-pwd-strength-fill" id="prfPwdStrengthFill"></div>
            </div>
            <p class="prf-help">Strength: <strong id="prfPwdStrengthLabel">—</strong></p>
          </div>
          <div class="prf-field">
            <label class="prf-label" for="prf_confirm_password">Confirm new password <span class="req">*</span></label>
            <input type="password" id="prf_confirm_password" name="confirm_password" class="prf-input" required minlength="8" autocomplete="new-password">
          </div>
        </div>

        <div class="prf-sticky-save">
          <p class="prf-help" style="margin:0;">You will stay signed in after changing your password.</p>
          <button type="submit" class="prf-btn-primary">Update password</button>
        </div>
      </form>
    </div>
  </div>

  <div class="prf-card">
    <div class="prf-card-hdr">
      <h2 class="prf-card-title">Future security options</h2>
      <p class="prf-card-kicker">Planned enhancements for enterprise accounts.</p>
    </div>
    <div class="prf-card-body">
      <div class="prf-pref-card" style="margin-bottom:.5rem;">
        <p class="prf-pref-title">Multi-factor authentication (MFA)</p>
        <p class="prf-pref-text">Coming soon — add an extra verification step at sign-in.</p>
      </div>
      <div class="prf-pref-card">
        <p class="prf-pref-title">API tokens</p>
        <p class="prf-pref-text">Coming soon — personal access tokens for integrations.</p>
      </div>
    </div>
  </div>
</section>
