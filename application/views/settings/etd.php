<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$etd = is_array($settings['etd'] ?? null) ? $settings['etd'] : [];
?>
<section class="stg-panel" id="stg-panel-etd" role="tabpanel" aria-labelledby="stg-nav-etd" hidden>
  <header class="stg-panel-head">
    <h2 class="stg-panel-title">ETD &amp; learner experience</h2>
    <p class="stg-panel-desc">Face-to-Face advisories, HyFlex labeling, and retake rules for managerial vs. standard courses.</p>
  </header>

  <div class="stg-field">
    <label class="stg-label" for="etd_f2f_notice_html">Face-to-Face advisory template (HTML)</label>
    <textarea class="stg-textarea" id="etd_f2f_notice_html" name="settings[etd][f2f_notice_html]" rows="8"><?= htmlspecialchars($etd['f2f_notice_html'] ?? '', ENT_QUOTES) ?></textarea>
    <p class="stg-help">Shown on course detail, enrollment confirmation, and learner cards when modality is Face-to-Face.</p>
  </div>

  <div class="stg-field">
    <label class="stg-label" for="etd_managerial_slugs">Managerial category keywords</label>
    <input type="text" class="stg-input" id="etd_managerial_slugs" name="settings[etd][managerial_category_slugs]"
           value="<?= htmlspecialchars($etd['managerial_category_slugs'] ?? 'management,managerial,leadership', ENT_QUOTES) ?>">
    <p class="stg-help">Comma-separated substrings matched against course category names (case-insensitive).</p>
  </div>

  <div class="stg-field">
    <label class="stg-label" for="etd_hyflex_map">HyFlex label map</label>
    <input type="text" class="stg-input" id="etd_hyflex_map" name="settings[etd][hyflex_label_map]"
           value="<?= htmlspecialchars($etd['hyflex_label_map'] ?? 'hybrid:HyFlex', ENT_QUOTES) ?>">
    <p class="stg-help">Format: <code>db_label:Display</code> (e.g. hybrid:HyFlex). DB values stay unchanged.</p>
  </div>

  <div class="stg-field stg-field--check">
    <label class="stg-check">
      <input type="hidden" name="settings[etd][retake_full_course_enabled]" value="0">
      <input type="checkbox" name="settings[etd][retake_full_course_enabled]" value="1"
        <?= ! isset($etd['retake_full_course_enabled']) || (string) $etd['retake_full_course_enabled'] === '1' ? 'checked' : '' ?>>
      <span>Non-managerial courses require full course retake after failed post-assessment</span>
    </label>
  </div>
</section>
