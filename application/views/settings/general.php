<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $g = $settings['general'] ?? []; ?>
<section class="stg-panel is-active" role="tabpanel" id="stg-panel-general" data-stg-tab="general" data-stg-keywords="general lms name organization timezone maintenance homepage">
  <div class="stg-card">
    <div class="stg-card-hdr">
      <h2 class="stg-card-title">General</h2>
      <p class="stg-card-kicker">Platform-wide basics visible across the LMS.</p>
    </div>
    <div class="stg-card-body">
      <div class="stg-row-2">
        <div class="stg-field">
          <label class="stg-label" for="stg_lms_name">LMS name</label>
          <input type="text" class="stg-input" id="stg_lms_name" name="settings[general][lms_name]"
                 value="<?= htmlspecialchars(set_value('settings[general][lms_name]', $g['lms_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="stg-field">
          <label class="stg-label" for="stg_org_name">Organization name</label>
          <input type="text" class="stg-input" id="stg_org_name" name="settings[general][organization_name]"
                 value="<?= htmlspecialchars(set_value('settings[general][organization_name]', $g['organization_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
      <div class="stg-row-2">
        <div class="stg-field">
          <label class="stg-label" for="stg_support_email">Support email</label>
          <input type="email" class="stg-input" id="stg_support_email" name="settings[general][support_email]"
                 value="<?= htmlspecialchars(set_value('settings[general][support_email]', $g['support_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          <p class="stg-help">Shown in learner-facing help and notification footers.</p>
        </div>
        <div class="stg-field">
          <label class="stg-label" for="stg_timezone">Timezone</label>
          <select class="stg-select" id="stg_timezone" name="settings[general][timezone]">
            <?php
            $tz = set_value('settings[general][timezone]', $g['timezone'] ?? 'Asia/Manila');
            foreach (['Asia/Manila', 'UTC', 'Asia/Singapore', 'Asia/Tokyo'] as $opt):
            ?>
            <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>" <?= $tz === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="stg-row-2">
        <div class="stg-field">
          <label class="stg-label" for="stg_date_format">Date format</label>
          <input type="text" class="stg-input" id="stg_date_format" name="settings[general][date_format]"
                 value="<?= htmlspecialchars(set_value('settings[general][date_format]', $g['date_format'] ?? 'M j, Y'), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="stg-field">
          <label class="stg-label" for="stg_homepage_title">Homepage title</label>
          <input type="text" class="stg-input" id="stg_homepage_title" name="settings[general][homepage_title]"
                 value="<?= htmlspecialchars(set_value('settings[general][homepage_title]', $g['homepage_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
      <label class="stg-toggle">
        <input type="hidden" name="settings[general][maintenance_mode]" value="0">
        <input type="checkbox" name="settings[general][maintenance_mode]" value="1"
               <?= ! empty($g['maintenance_mode']) && $g['maintenance_mode'] !== '0' ? 'checked' : '' ?>>
        <span class="stg-toggle-text">
          <strong>Maintenance mode</strong>
          <span>When enabled, only administrators can access the LMS (enforcement can be wired in a future release).</span>
        </span>
      </label>
    </div>
  </div>
</section>
