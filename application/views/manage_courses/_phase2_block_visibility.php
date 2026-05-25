<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$sel_departments = is_array($sel_departments ?? null) ? $sel_departments : [];
$sel_professions = is_array($sel_professions ?? null) ? $sel_professions : [];
$dept_select_enabled = ! empty($dept_select_enabled);
$hrmis_ready = ! empty($hrmis_ready);
?>
<section class="crs-p2-card" aria-labelledby="crs-p2-visibility-title">
  <header class="crs-p2-card-hdr">
    <div class="crs-p2-card-icon" aria-hidden="true">🌐</div>
    <div>
      <h4 class="crs-p2-card-title" id="crs-p2-visibility-title">Department &amp; job visibility</h4>
      <p class="crs-p2-card-sub"><span class="crs-p2-optional-badge">Optional</span> Restrict who can discover this course. Leave empty for organization-wide visibility.</p>
    </div>
  </header>
  <div class="crs-p2-card-body">
    <div class="crs-p2-grid-2">
      <div class="crs-p2-field">
        <label class="crs-p2-label">Department visibility</label>
        <select name="department_ids[]" id="crs_p2_department_ids" class="ka-select2" multiple data-select-kind="dept" data-placeholder="Search departments…"<?= $dept_select_enabled ? '' : ' disabled' ?>>
          <?php foreach ($departments as $d): ?>
          <?php $dept_id = (int) (is_object($d) ? ($d->id ?? 0) : ($d['id'] ?? 0)); ?>
          <?php $dept_name = is_object($d) ? (string) ($d->name ?? '') : (string) ($d['name'] ?? ''); ?>
          <?php if ($dept_id < 1 || $dept_name === '') { continue; } ?>
          <option value="<?= $dept_id ?>" <?= in_array($dept_id, $sel_departments, true) ? 'selected' : '' ?>>
            <?= htmlspecialchars($dept_name, ENT_QUOTES, 'UTF-8') ?>
          </option>
          <?php endforeach; ?>
        </select>
        <p class="crs-p2-help">If no departments are selected, the course is visible to all employees.</p>
        <div id="crs_p2_dept_visibility_hint" class="crs-p2-dept-hint" aria-live="polite">
          <?php if (empty($sel_departments)): ?>
          <span class="crs-p2-dept-hint-all">🌐 Visible to all departments</span>
          <?php else: ?>
          <span class="crs-p2-dept-hint-restricted">Restricted to <?= count($sel_departments) ?> department<?= count($sel_departments) === 1 ? '' : 's' ?></span>
          <?php endif; ?>
        </div>
      </div>
      <div class="crs-p2-field">
        <label class="crs-p2-label">Job title visibility</label>
        <select name="profession_ids[]" class="ka-select2" multiple data-select-kind="prof" data-placeholder="All job titles" <?= $hrmis_ready ? '' : 'disabled' ?>>
          <?php foreach ($professions as $p): ?>
          <option value="<?= (int) $p->id ?>" <?= in_array((int) $p->id, $sel_professions, true) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p->name) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <p class="crs-p2-help">Restrict visibility by job title. Leave empty if every role should see this course.</p>
      </div>
    </div>
  </div>
</section>
