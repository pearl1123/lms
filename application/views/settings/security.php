<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $s = $settings['security'] ?? []; ?>
<section class="stg-panel" role="tabpanel" id="stg-panel-security" data-stg-tab="security" data-stg-keywords="security password session login audit domain" hidden>
  <div class="stg-card">
    <div class="stg-card-hdr">
      <h2 class="stg-card-title">Security</h2>
      <p class="stg-card-kicker">Authentication policy and access restrictions for your organization.</p>
    </div>
    <div class="stg-card-body">
      <div class="stg-row-2">
        <div class="stg-field">
          <label class="stg-label" for="stg_min_pass">Minimum password length</label>
          <input type="number" class="stg-input" id="stg_min_pass" name="settings[security][min_password_length]"
                 min="6" max="128" value="<?= htmlspecialchars($s['min_password_length'] ?? '8', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="stg-field">
          <label class="stg-label" for="stg_session_timeout">Session timeout (minutes)</label>
          <input type="number" class="stg-input" id="stg_session_timeout" name="settings[security][session_timeout_mins]"
                 min="5" value="<?= htmlspecialchars($s['session_timeout_mins'] ?? '120', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
      <div class="stg-field">
        <label class="stg-label" for="stg_login_attempts">Max login attempts</label>
        <input type="number" class="stg-input" id="stg_login_attempts" name="settings[security][max_login_attempts]"
               min="3" value="<?= htmlspecialchars($s['max_login_attempts'] ?? '5', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="stg-field">
        <label class="stg-label" for="stg_allowed_domains">Allowed email domains</label>
        <textarea class="stg-input" id="stg_allowed_domains" name="settings[security][allowed_domains]" rows="3"
                  placeholder="example.gov.ph&#10;agency.org"><?= htmlspecialchars($s['allowed_domains'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        <p class="stg-help">One domain per line. Leave empty to allow any domain.</p>
      </div>
      <div class="stg-field">
        <label class="stg-label" for="stg_lockout">Lockout duration (minutes)</label>
        <input type="number" class="stg-input" id="stg_lockout" name="settings[security][lockout_minutes]"
               min="1" value="<?= htmlspecialchars($s['lockout_minutes'] ?? '15', ENT_QUOTES, 'UTF-8') ?>">
      </div>
    </div>
  </div>
</section>
