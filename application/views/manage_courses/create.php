<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$categories   = $categories   ?? [];
$modalities   = $modalities   ?? [];
$teachers     = $teachers     ?? [];
$phase2       = $course_phase2 ?? null;
$user         = $user ?? null;
$user_role    = strtolower(is_object($user) ? ($user->role ?? 'teacher') : 'teacher');
$user_id      = is_object($user) ? (int) ($user->id ?? 0) : 0;
$user_name    = is_object($user) ? (string) ($user->fullname ?? '') : '';
$user_emp_id  = is_object($user) ? (string) ($user->employee_id ?? '') : '';
$is_admin     = $user_role === 'admin';
$phase2_ready = ! empty($phase2_schema_ready);
$hrmis_connection_ok = ! empty($hrmis_connection_ok);
$hrmis_ready  = ! empty($hrmis_ready);
$departments  = is_array($departments ?? null) ? $departments : [];

$posted_categories  = $this->input->post('category_ids');
$posted_instructors = $this->input->post('instructor_ids');
$posted_departments = $this->input->post('department_ids');
$posted_professions = $this->input->post('profession_ids');

$sel_categories = is_array($posted_categories) ? array_map('intval', $posted_categories) : ($phase2 ? (array) $phase2->category_ids : []);
$legacy_category_id = (int) set_value('category_id', 0);
if (empty($sel_categories) && $legacy_category_id > 0) {
    $sel_categories = [$legacy_category_id];
}

$sel_instructors = is_array($posted_instructors) ? array_map('intval', $posted_instructors) : ($phase2 ? (array) $phase2->instructor_ids : []);
if (empty($sel_instructors) && is_object($user ?? null)) {
    $sel_instructors = [$user_id];
}

$sel_departments = is_array($posted_departments) ? array_map('intval', $posted_departments) : ($phase2 ? (array) $phase2->department_ids : []);
$sel_professions = is_array($posted_professions) ? array_map('intval', $posted_professions) : ($phase2 ? (array) $phase2->profession_ids : []);

$instructor_options = $instructor_options ?? ($is_admin ? $teachers : [(object) ['id' => $user_id, 'fullname' => $user_name, 'employee_id' => $user_emp_id]]);
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/manage_courses.css'); ?>">

<div class="crs-page-head animate__animated animate__fadeIn animate__fast">
  <div>
    <h2 class="crs-page-title">Create New Course</h2>
    <p class="crs-page-subtitle">Set up the course profile, access rules, ownership, and certificate settings.</p>
  </div>
  <a href="<?= base_url('manage_courses') ?>" class="crs-back-link">&larr; Back</a>
</div>

<form method="post" action="<?= base_url('manage_courses/create') ?>">
  <input type="hidden" name="<?= html_escape($csrf_field_name ?? '') ?>" value="<?= html_escape($csrf_hash ?? '') ?>">
  <input type="hidden" id="category_id" name="category_id" value="<?= (int) ($sel_categories[0] ?? 0) ?>">

  <div class="crs-layout animate__animated animate__fadeInUp animate__fast">
    <div>
      <div class="crs-panel">
        <div class="crs-panel-hdr">
          <h3 class="crs-panel-title">Basic Course Information</h3>
          <p class="crs-panel-kicker">Core details learners and instructors will see first.</p>
        </div>
        <div class="crs-panel-body">
          <div class="crs-form-group">
            <label class="crs-label" for="title">Course Title <span>*</span></label>
            <input type="text" id="title" name="title" class="crs-input"
                   placeholder="e.g. Infection Control and Prevention"
                   value="<?= htmlspecialchars(set_value('title'), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (form_error('title')): ?><div class="crs-error"><?= form_error('title') ?></div><?php endif; ?>
          </div>

          <div class="crs-form-group">
            <label class="crs-label" for="description">Description</label>
            <textarea id="description" name="description" class="crs-textarea"
                      placeholder="Brief overview of what this course covers..."><?= htmlspecialchars(set_value('description'), ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="crs-form-group">
            <label class="crs-label" for="modality_id">Modality <span>*</span></label>
            <select id="modality_id" name="modality_id" class="crs-select" required>
              <option value="">-- Select --</option>
              <?php foreach ($modalities as $m): ?>
              <option value="<?= (int) $m->modality_id ?>" <?= set_select('modality_id', $m->modality_id) ?>>
                <?= htmlspecialchars($m->modality_desc, ENT_QUOTES, 'UTF-8') ?>
              </option>
              <?php endforeach; ?>
            </select>
            <?php if (form_error('modality_id')): ?><div class="crs-error"><?= form_error('modality_id') ?></div><?php endif; ?>
          </div>

          <?php if ( ! $phase2_ready): ?>
          <div class="crs-form-group">
            <label class="crs-label" for="legacy_category_id">Category <span>*</span></label>
            <select id="legacy_category_id" name="category_ids[]" class="crs-select" onchange="document.getElementById('category_id').value=this.value||'';">
              <option value="">-- Select --</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= (int) $cat->id ?>" <?= in_array((int) $cat->id, $sel_categories, true) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8') ?>
              </option>
              <?php endforeach; ?>
            </select>
            <?php if (form_error('category_id')): ?><div class="crs-error"><?= form_error('category_id') ?></div><?php endif; ?>
          </div>
          <div class="crs-note crs-note-warning">Run <code>application/sql/migration_phase2_course_features.sql</code> to enable Phase 2 course options.</div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($phase2_ready): ?>
      <?php $this->load->view('manage_courses/_phase2_course_fields', get_defined_vars()); ?>
      <?php endif; ?>

      <div class="crs-panel">
        <div class="crs-panel-hdr">
          <h3 class="crs-panel-title">Course Management</h3>
          <p class="crs-panel-kicker">Set enrollment lifespan and ownership.</p>
        </div>
        <div class="crs-panel-body">
          <?php if ($is_admin && ! empty($teachers)): ?>
          <div class="crs-form-group">
            <label class="crs-label" for="created_by">Primary Owner</label>
            <select id="created_by" name="created_by" class="crs-select">
              <option value="<?= $user_id ?>">Myself (<?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?>)</option>
              <?php foreach ($teachers as $t): ?>
              <option value="<?= (int) $t->id ?>" <?= set_select('created_by', $t->id) ?>>
                <?= htmlspecialchars($t->fullname, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($t->employee_id, ENT_QUOTES, 'UTF-8') ?>)
              </option>
              <?php endforeach; ?>
            </select>
            <div class="crs-help">Legacy owner record. Assign instructors in the course settings section above.</div>
          </div>
          <?php elseif ( ! $phase2_ready): ?>
          <input type="hidden" name="instructor_ids[]" value="<?= $user_id ?>">
          <?php endif; ?>

          <div class="crs-form-group">
              <label class="crs-label" for="expiry_days">Expiry Days</label>
              <input type="number" id="expiry_days" name="expiry_days" class="crs-input"
                     placeholder="e.g. 365" min="1" value="<?= htmlspecialchars(set_value('expiry_days'), ENT_QUOTES, 'UTF-8') ?>">
              <div class="crs-help">Days before enrollment expires. Leave blank for no expiry.</div>
          </div>

          <div class="crs-form-group">
            <label class="crs-label">Invitations</label>
            <div class="crs-note">After creating the course, open the edit screen to invite learners by name, department, or email.</div>
          </div>
        </div>
      </div>

      <div class="crs-panel">
        <div class="crs-panel-hdr">
          <h3 class="crs-panel-title">Certificate Settings</h3>
          <p class="crs-panel-kicker">Configure certificate serials and signatory display.</p>
        </div>
        <div class="crs-panel-body">
          <div class="crs-row">
            <div class="crs-form-group">
              <label class="crs-label" for="certificate_prefix">Certificate Prefix <span>*</span></label>
              <input type="text" id="certificate_prefix" name="certificate_prefix" class="crs-input"
                     placeholder="e.g. UIUX" maxlength="12" value="<?= htmlspecialchars(set_value('certificate_prefix'), ENT_QUOTES, 'UTF-8') ?>" required>
              <div class="crs-help">Used in certificate serial: KABAGA-{PREFIX}-<?= date('Y') ?>-0001</div>
              <?php if (form_error('certificate_prefix')): ?><div class="crs-error"><?= form_error('certificate_prefix') ?></div><?php endif; ?>
            </div>

            <div class="crs-form-group">
              <label class="crs-label" for="signatory_name">Signatory Name</label>
              <input type="text" id="signatory_name" name="signatory_name" class="crs-input"
                     placeholder="e.g. Maria L. Santos" maxlength="120" value="<?= htmlspecialchars(set_value('signatory_name'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>

          <div class="crs-row">
            <div class="crs-form-group">
              <label class="crs-label" for="signatory_title">Signatory Title</label>
              <input type="text" id="signatory_title" name="signatory_title" class="crs-input"
                     placeholder="e.g. Learning & Development Manager" maxlength="120" value="<?= htmlspecialchars(set_value('signatory_title'), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="crs-form-group">
              <label class="crs-label">Future Signatories</label>
              <div class="crs-note">Additional signatories and signature previews can be added here later without changing the course workflow.</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div>
      <div class="crs-panel">
        <div class="crs-panel-hdr"><h3 class="crs-panel-title">Create Course</h3></div>
        <div class="crs-panel-body">
          <p class="crs-page-subtitle">New courses are saved as <strong>draft</strong>. Publish from the edit screen after adding modules.</p>
          <button type="submit" class="crs-submit-btn">Create &amp; Add Modules &rarr;</button>
          <a href="<?= base_url('manage_courses') ?>" class="crs-cancel-link">Cancel</a>
        </div>
      </div>

      <div class="crs-panel">
        <div class="crs-panel-hdr"><h3 class="crs-panel-title">Tips</h3></div>
        <div class="crs-panel-body crs-tip-list">
          <p><strong>Title</strong> - Keep it clear and descriptive.</p>
          <p><strong>Categories</strong> - Use multiple categories when a course spans topics.</p>
          <p><strong>Visibility</strong> - Leave department/profession fields empty for all learners.</p>
          <p><strong>Draft</strong> - Draft courses stay out of the learner catalog until published.</p>
        </div>
      </div>
    </div>
  </div>
</form>