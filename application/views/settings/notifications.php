<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $n = $settings['notifications'] ?? []; ?>
<section class="stg-panel" role="tabpanel" id="stg-panel-notifications" data-stg-tab="notifications" data-stg-keywords="notifications smtp email invitation reminder approval certificate" hidden>
  <div class="stg-card">
    <div class="stg-card-hdr">
      <h2 class="stg-card-title">Notifications</h2>
      <p class="stg-card-kicker">Outbound email and template toggles for platform events.</p>
    </div>
    <div class="stg-card-body">
      <div class="stg-row-2">
        <div class="stg-field">
          <label class="stg-label" for="stg_smtp_host">SMTP host</label>
          <input type="text" class="stg-input" id="stg_smtp_host" name="settings[notifications][smtp_host]"
                 value="<?= htmlspecialchars($n['smtp_host'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="stg-field">
          <label class="stg-label" for="stg_smtp_port">SMTP port</label>
          <input type="number" class="stg-input" id="stg_smtp_port" name="settings[notifications][smtp_port]"
                 value="<?= htmlspecialchars($n['smtp_port'] ?? '587', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
      <div class="stg-row-2">
        <div class="stg-field">
          <label class="stg-label" for="stg_smtp_user">SMTP username</label>
          <input type="text" class="stg-input" id="stg_smtp_user" name="settings[notifications][smtp_user]"
                 value="<?= htmlspecialchars($n['smtp_user'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="stg-field">
          <label class="stg-label" for="stg_smtp_pass">SMTP password</label>
          <input type="password" class="stg-input" id="stg_smtp_pass" name="settings[notifications][smtp_pass]"
                 placeholder="<?= ! empty($n['smtp_pass']) ? '••••••••' : '' ?>" autocomplete="new-password">
          <p class="stg-help">Leave blank to keep the current password.</p>
        </div>
      </div>
      <div class="stg-field">
        <label class="stg-label" for="stg_from_email">From email</label>
        <input type="email" class="stg-input" id="stg_from_email" name="settings[notifications][from_email]"
               value="<?= htmlspecialchars($n['from_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <hr style="border:0;border-top:1px solid var(--stg-border,#e8ecf0);margin:1.25rem 0;">
      <p class="stg-card-kicker" style="margin-bottom:.75rem;">Email templates</p>
      <?php
      $templates = [
        'invite_email'      => 'Course invitations',
        'approval_email'    => 'Enrollment approvals',
        'certificate_email' => 'Certificate issued',
      ];
      foreach ($templates as $key => $label):
      ?>
      <label class="stg-toggle">
        <input type="hidden" name="settings[notifications][<?= $key ?>]" value="0">
        <input type="checkbox" name="settings[notifications][<?= $key ?>]" value="1"
               <?= ! empty($n[$key]) && $n[$key] !== '0' ? 'checked' : '' ?>>
        <span class="stg-toggle-text">
          <strong><?= htmlspecialchars($label) ?></strong>
          <span>Send automated emails when this event occurs.</span>
        </span>
      </label>
      <?php endforeach; ?>
      <button type="button" class="stg-btn-outline" disabled title="Configure SMTP and save settings first">Send test email</button>
    </div>
  </div>
</section>
