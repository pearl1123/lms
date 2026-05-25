<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $a = $settings['advanced'] ?? []; ?>
<section class="stg-panel" role="tabpanel" id="stg-panel-advanced" data-stg-tab="advanced" data-stg-keywords="advanced debug cache maintenance danger" hidden>
  <div class="stg-card">
    <div class="stg-card-hdr">
      <h2 class="stg-card-title">Advanced</h2>
      <p class="stg-card-kicker">Diagnostics and maintenance tools for platform operators.</p>
    </div>
    <div class="stg-card-body">
      <label class="stg-toggle">
        <input type="hidden" name="settings[advanced][debug_mode]" value="0">
        <input type="checkbox" name="settings[advanced][debug_mode]" value="1"
               <?= ! empty($a['debug_mode']) && $a['debug_mode'] !== '0' ? 'checked' : '' ?>>
        <span class="stg-toggle-text">
          <strong>Debug mode</strong>
          <span>Show extended errors in development environments only.</span>
        </span>
      </label>
      <label class="stg-toggle">
        <input type="hidden" name="settings[advanced][verbose_logging]" value="0">
        <input type="checkbox" name="settings[advanced][verbose_logging]" value="1"
               <?= ! empty($a['verbose_logging']) && $a['verbose_logging'] !== '0' ? 'checked' : '' ?>>
        <span class="stg-toggle-text">
          <strong>Verbose application logging</strong>
          <span>Write additional detail to application/logs (use sparingly in production).</span>
        </span>
      </label>
    </div>
  </div>
  <div class="stg-card stg-danger-zone">
    <div class="stg-card-hdr">
      <h2 class="stg-card-title">Danger zone</h2>
      <p class="stg-card-kicker">Destructive actions require confirmation in a future release.</p>
    </div>
    <div class="stg-card-body">
      <div class="stg-danger-actions">
        <button type="button" class="stg-btn-outline" disabled>Clear application cache</button>
        <button type="button" class="stg-btn-outline" disabled>Rebuild search indexes</button>
      </div>
      <p class="stg-help">These tools will be enabled when maintenance endpoints are added. Saving settings above does not run maintenance jobs.</p>
    </div>
  </div>
</section>
