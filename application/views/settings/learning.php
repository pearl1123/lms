<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $l = $settings['learning'] ?? []; ?>
<section class="stg-panel" role="tabpanel" id="stg-panel-learning" data-stg-tab="learning" data-stg-keywords="learning enrollment approval completion expiry publish" hidden>
  <div class="stg-card">
    <div class="stg-card-hdr">
      <h2 class="stg-card-title">Learning behavior</h2>
      <p class="stg-card-kicker">Defaults applied when creating courses and managing enrollments.</p>
    </div>
    <div class="stg-card-body">
      <div class="stg-field">
        <label class="stg-label" for="stg_enroll_mode">Default enrollment mode</label>
        <select class="stg-select" id="stg_enroll_mode" name="settings[learning][default_enrollment_mode]">
          <?php
          $modes = ['open' => 'Open', 'approval_required' => 'Approval required', 'invitation_only' => 'Invitation only', 'hidden' => 'Hidden'];
          $cur = $l['default_enrollment_mode'] ?? 'approval_required';
          foreach ($modes as $val => $label):
          ?>
          <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>" <?= $cur === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <label class="stg-toggle">
        <input type="hidden" name="settings[learning][require_approval]" value="0">
        <input type="checkbox" name="settings[learning][require_approval]" value="1"
               <?= ! empty($l['require_approval']) && $l['require_approval'] !== '0' ? 'checked' : '' ?>>
        <span class="stg-toggle-text">
          <strong>Require instructor approval by default</strong>
          <span>New courses suggest approval-required enrollment unless overridden per course.</span>
        </span>
      </label>
      <div class="stg-row-2">
        <div class="stg-field">
          <label class="stg-label" for="stg_completion">Completion threshold (%)</label>
          <input type="number" class="stg-input" id="stg_completion" name="settings[learning][completion_threshold]"
                 min="1" max="100" value="<?= htmlspecialchars($l['completion_threshold'] ?? '100', ENT_QUOTES, 'UTF-8') ?>">
          <p class="stg-help">Module weight total expected for course completion.</p>
        </div>
        <div class="stg-field">
          <label class="stg-label" for="stg_expiry">Default expiry (days)</label>
          <input type="number" class="stg-input" id="stg_expiry" name="settings[learning][default_expiry_days]"
                 min="1" placeholder="No default"
                 value="<?= htmlspecialchars($l['default_expiry_days'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
      <label class="stg-toggle">
        <input type="hidden" name="settings[learning][auto_unpublish_expired]" value="0">
        <input type="checkbox" name="settings[learning][auto_unpublish_expired]" value="1"
               <?= ! empty($l['auto_unpublish_expired']) && $l['auto_unpublish_expired'] !== '0' ? 'checked' : '' ?>>
        <span class="stg-toggle-text">
          <strong>Auto-unpublish expired courses</strong>
          <span>When course expiry is reached, unpublish from the learner catalog (policy hook).</span>
        </span>
      </label>
    </div>
  </div>
</section>
