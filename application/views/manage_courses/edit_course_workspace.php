<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$is_edit_form = true;
$focus_modules = ! empty($focus_modules);
$phase2_ready_ui = ! empty($phase2_schema_ready);
$csrf_field_name = $csrf_field_name ?? '';
$csrf_hash       = $csrf_hash ?? '';
if ($csrf_field_name === '' && config_item('csrf_protection')) {
    $SEC =& load_class('Security', 'core');
    $csrf_field_name = (string) $SEC->get_csrf_token_name();
    $csrf_hash       = (string) $SEC->get_csrf_hash();
}
$course = $course ?? null;
if ( ! $course) {
    return;
}
if ( ! isset($modules) || ! is_array($modules)) {
    $modules = [];
}
if ( ! isset($module_rows) || ! is_array($module_rows)) {
    $module_rows = $modules;
}
$module_count = isset($module_count) ? (int) $module_count : count($modules);
if ( ! isset($modalities) || ! is_array($modalities)) {
    $modalities = [];
}
if ( ! isset($categories) || ! is_array($categories)) {
    $categories = [];
}
if ( ! isset($edit_category_ids) || ! is_array($edit_category_ids)) {
    $edit_category_ids = [];
    if ( ! empty($course->category_id)) {
        $edit_category_ids = [(int) $course->category_id];
    }
}
$checkpoint_schema_ready = isset($checkpoint_schema_ready) ? (bool) $checkpoint_schema_ready : false;
$phase3_ready            = isset($phase3_ready) ? (bool) $phase3_ready : false;
$phase3_batches_ready    = isset($phase3_batches_ready) ? (bool) $phase3_batches_ready : false;
$course_structure_locked = ! empty($course_structure_locked) || ! empty($enrollment_guard['blocks_structure']);
$lms_return_target     = $lms_return_target ?? ka_lms_default_return_target_for_role($user ?? null);
?>
<form method="post" action="<?= base_url('manage_courses/edit/'.$course->id) ?>" id="course-edit-form" enctype="multipart/form-data" data-initial-edit-tab="<?= ! empty($focus_modules) ? 'modules' : '' ?>">
  <input type="hidden" name="<?= htmlspecialchars($csrf_field_name, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($csrf_hash, ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="return_url" value="<?= htmlspecialchars($lms_return_target ?? '', ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="edit_return_tab" id="editReturnTab" value="">
  <?php if ($phase2_ready_ui): ?>
  <input type="hidden" id="edit_category_id" name="category_id" value="<?= (int) ($edit_category_ids[0] ?? 0) ?>">
  <?php endif; ?>

  <?php if ($phase2_ready_ui): ?>
  <?php include APPPATH . 'views/manage_courses/phase2_init.php'; ?>
  <?php $this->load->view('manage_courses/phase2_assets_links', get_defined_vars()); ?>
  <?php endif; ?>

  <nav class="crs-edit-toolbar" role="tablist" aria-label="Course workspace sections">
    <button type="button" class="crs-edit-tab is-active" role="tab" aria-selected="true" aria-controls="crs-edit-panel-overview" id="crs-edit-tab-overview" data-edit-tab="overview">
      Overview
    </button>
    <button type="button" class="crs-edit-tab" role="tab" aria-selected="false" aria-controls="crs-edit-panel-modules" id="crs-edit-tab-modules" data-edit-tab="modules">
      Modules <span class="crs-edit-tab-badge"><?= (int) $module_count ?></span>
    </button>
    <button type="button" class="crs-edit-tab" role="tab" aria-selected="false" aria-controls="crs-edit-panel-access" id="crs-edit-tab-access" data-edit-tab="access">
      Access <span class="crs-edit-tab-badge">Optional</span>
    </button>
    <button type="button" class="crs-edit-tab" role="tab" aria-selected="false" aria-controls="crs-edit-panel-people" id="crs-edit-tab-people" data-edit-tab="people">
      Instructors
    </button>
    <button type="button" class="crs-edit-tab" role="tab" aria-selected="false" aria-controls="crs-edit-panel-certificates" id="crs-edit-tab-certificates" data-edit-tab="certificates">
      Certificates <span class="crs-edit-tab-badge">Optional</span>
    </button>
    <button type="button" class="crs-edit-tab" role="tab" aria-selected="false" aria-controls="crs-edit-panel-batches" id="crs-edit-tab-batches" data-edit-tab="batches">
      Batches <span class="crs-edit-tab-badge">Optional</span>
    </button>
    <span class="crs-edit-tab-progress" id="crsEditTabProgress" aria-hidden="true"></span>
  </nav>

  <?php if ($phase2_ready_ui): ?>
  <div class="crs-p2-stack crs-edit-p2-root">
  <?php $this->load->view('manage_courses/phase2_alerts_block', get_defined_vars()); ?>
  <?php endif; ?>

  <div class="crs-edit-tab-panels">

    <section class="crs-edit-panel is-active" role="tabpanel" id="crs-edit-panel-overview" aria-labelledby="crs-edit-tab-overview" data-edit-tab="overview">
      <p class="crs-edit-workspace-title">Overview</p>
      <div class="edit-crs-panel">
        <div class="edit-crs-hdr"><h3 class="edit-crs-title">Basics</h3></div>
        <div class="edit-crs-body">
          <div class="ef-group">
            <label class="ef-label" for="title">Title <span>*</span></label>
            <input type="text" id="title" name="title" class="ef-input"
                   value="<?= htmlspecialchars(set_value('title', $course->title)) ?>" required>
            <?php if (form_error('title')): ?><div class="ef-error"><?= form_error('title') ?></div><?php endif; ?>
          </div>
          <div class="ef-group">
            <label class="ef-label" for="description">Description</label>
            <textarea id="description" name="description" class="ef-textarea"><?= htmlspecialchars(set_value('description', $course->description ?? '')) ?></textarea>
          </div>
          <div class="ef-group">
            <label class="ef-label">Modality <span>*</span></label>
            <select name="modality_id" class="ef-select" required>
              <option value="">-- Select --</option>
              <?php foreach ($modalities as $m): ?>
              <option value="<?= $m->modality_id ?>" <?= ((int) $m->modality_id === (int) ($course->modality_id ?? 0)) ? 'selected' : '' ?>>
                <?= htmlspecialchars(etd_modality_display_label($m->modality_desc ?? '')) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <?php if (form_error('modality_id')): ?><div class="ef-error"><?= form_error('modality_id') ?></div><?php endif; ?>
          </div>
          <?php if ( ! $phase2_ready_ui): ?>
          <div class="ef-group">
            <label class="ef-label">Category <span>*</span></label>
            <select name="category_id" id="edit_legacy_category_id" class="ef-select" required>
              <option value="">-- Select --</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= (int) $cat->id ?>" <?= ((int) ($course->category_id ?? 0) === (int) $cat->id) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat->name) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <?php if (form_error('category_id')): ?><div class="ef-error"><?= form_error('category_id') ?></div><?php endif; ?>
          </div>
          <?php endif; ?>
          <div class="ef-row" style="margin-top:.75rem;">
            <div class="ef-group">
              <label class="ef-label" for="pass_threshold_pct">Pass threshold (%)</label>
              <input type="number" id="pass_threshold_pct" name="pass_threshold_pct" class="ef-input" min="1" max="100" step="0.1"
                     placeholder="75 (platform default)"
                     value="<?= htmlspecialchars(set_value('pass_threshold_pct', $course->pass_threshold_pct ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              <p class="ef-help" style="font-size:.72rem;color:var(--ka-text-muted,#64748b);margin-top:.25rem;">Leave blank to use platform default (75%).</p>
            </div>
            <div class="ef-group">
              <label class="ef-label">Sequential modules</label>
              <label class="ef-toggle" style="display:flex;align-items:center;gap:.5rem;margin-top:.35rem;">
                <input type="hidden" name="enforce_sequential_modules" value="0">
                <input type="checkbox" name="enforce_sequential_modules" value="1"
                       <?= ! isset($course->enforce_sequential_modules) || (int) $course->enforce_sequential_modules === 1 ? 'checked' : '' ?>>
                <span>Learners must complete modules in order</span>
              </label>
            </div>
          </div>
          <?php
          $modality_label = function_exists('etd_modality_display_label')
              ? etd_modality_display_label($course->modality_name ?? '') : '';
          $is_f2f_course = function_exists('etd_is_face_to_face_modality')
              && etd_is_face_to_face_modality($course->modality_name ?? '');
          ?>
          <?php if ($is_f2f_course): ?>
          <div class="ef-section" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--ka-border,#e2e8f0);">
            <h4 class="ef-section-title" style="font-size:.9rem;margin-bottom:.75rem;">Face-to-face schedule</h4>
            <div class="ef-row">
              <div class="ef-group">
                <label class="ef-label" for="schedule_date">Date</label>
                <input type="date" id="schedule_date" name="schedule_date" class="ef-input"
                       value="<?= htmlspecialchars(set_value('schedule_date', $course->schedule_date ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="ef-group">
                <label class="ef-label" for="schedule_time">Time</label>
                <input type="time" id="schedule_time" name="schedule_time" class="ef-input"
                       value="<?= htmlspecialchars(set_value('schedule_time', $course->schedule_time ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
            <div class="ef-row">
              <div class="ef-group">
                <label class="ef-label" for="venue">Venue</label>
                <input type="text" id="venue" name="venue" class="ef-input" maxlength="255"
                       placeholder="e.g. EMG Auditorium"
                       value="<?= htmlspecialchars(set_value('venue', $course->venue ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="ef-group">
                <label class="ef-label" for="max_capacity">Capacity</label>
                <input type="number" id="max_capacity" name="max_capacity" class="ef-input" min="1"
                       placeholder="Optional"
                       value="<?= htmlspecialchars(set_value('max_capacity', $course->max_capacity ?? ''), ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
          </div>
          <?php endif; ?>
          <div class="ef-row" style="margin-top:.75rem;">
            <div class="ef-group">
              <label class="ef-label" for="enrollment_deadline">Enrollment deadline</label>
              <input type="datetime-local" id="enrollment_deadline" name="enrollment_deadline" class="ef-input"
                     value="<?php
                       $dl = $course->enrollment_deadline ?? '';
                       echo $dl ? htmlspecialchars(date('Y-m-d\TH:i', strtotime((string) $dl)), ENT_QUOTES, 'UTF-8') : '';
                     ?>">
            </div>
          </div>
        </div>
      </div>
      <?php if ($phase2_ready_ui): ?>
      <?php $this->load->view('manage_courses/phase2_block_categories', get_defined_vars()); ?>
      <?php endif; ?>
    </section>

    <section class="crs-edit-panel" role="tabpanel" id="crs-edit-panel-modules" aria-labelledby="crs-edit-tab-modules" data-edit-tab="modules" hidden>
      <p class="crs-edit-workspace-title">Modules &amp; assessments</p>
      <div id="courseModulesPanel" class="edit-crs-panel">
        <div class="edit-crs-hdr">
          <h3 class="edit-crs-title">
            Modules
            <span style="font-size:.75rem;font-weight:500;color:var(--ka-text-muted,#64748b);margin-left:.375rem;" id="modCountLabel">
              <?= $module_count ?> module<?= $module_count !== 1 ? 's' : '' ?>
            </span>
          </h3>
          <span style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);">Drag to reorder</span>
        </div>
        <div class="edit-crs-body">
          <div id="modWeightSummary" style="margin-bottom:.75rem;padding:.625rem .75rem;border:1px solid var(--ka-border,#e2e8f0);border-radius:10px;font-size:.8125rem;color:var(--ka-text-muted,#64748b);background:#f8fafc;">
            Current total: <strong id="modWeightTotal">0%</strong>
            · Remaining: <strong id="modWeightRemaining">100%</strong>
            <span id="modWeightStatus" style="margin-left:.35rem;"></span>
          </div>
          <div class="mod-list" id="modList">
            <?php if ( ! empty($module_rows)): ?>
              <?php foreach ($module_rows as $idx => $mod):
                $eff_type = course_phase3_effective_module_type_for_row($mod, 'manage edit list mod_id=' . (int) $mod->id);
                $ct = $content_types[$eff_type] ?? ['icon'=>'📁','label'=>course_phase3_module_type_label($eff_type),'color'=>'#64748b'];
              ?>
              <div class="mod-item" id="moditem-<?= $mod->id ?>"
                   draggable="true"
                   data-id="<?= $mod->id ?>"
                   data-title="<?= htmlspecialchars($mod->title, ENT_QUOTES) ?>"
                   data-desc="<?= htmlspecialchars($mod->description ?? '', ENT_QUOTES) ?>"
                   data-type="<?= htmlspecialchars($eff_type, ENT_QUOTES, 'UTF-8') ?>"
                   data-path="<?= htmlspecialchars($mod->content_path ?? '', ENT_QUOTES) ?>"
                   data-weight="<?= $mod->weight_percentage ?? 0 ?>">
                <div class="mod-drag-handle"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="6" r="1" fill="currentColor"/><circle cx="15" cy="6" r="1" fill="currentColor"/><circle cx="9" cy="12" r="1" fill="currentColor"/><circle cx="15" cy="12" r="1" fill="currentColor"/><circle cx="9" cy="18" r="1" fill="currentColor"/><circle cx="15" cy="18" r="1" fill="currentColor"/></svg></div>
                <div class="mod-order"><?= $idx + 1 ?></div>
                <div class="mod-type-icon"><?= $ct['icon'] ?></div>
                <div class="mod-body">
                  <div class="mod-title"><?= htmlspecialchars($mod->title) ?></div>
                  <div class="mod-meta"><?= $ct['label'] ?><?= $mod->weight_percentage > 0 ? ' · ' . (float) $mod->weight_percentage . '% weight' : '' ?></div>
                  <div class="mod-asx-badges">
                    <?php if (($mod->pre_count ?? 0) > 0): ?>
                      <a href="<?= base_url('assessments?module_id='.$mod->id.'&type=pre') ?>"
                         class="mod-asx-badge pre" title="Pre-assessments">
                        ⏱ Pre (<?= $mod->pre_count ?>)
                      </a>
                    <?php else: ?>
                      <a href="<?= htmlspecialchars(ka_lms_append_return_to_url(base_url('assessments/create?course_id='.(int) $course->id.'&module_id='.$mod->id.'&type=pre'), $lms_return_target), ENT_QUOTES) ?>"
                         class="mod-asx-badge add" title="Add pre-assessment">
                        + Pre-assessment
                      </a>
                    <?php endif; ?>
                    <?php if (($mod->post_count ?? 0) > 0): ?>
                      <a href="<?= base_url('assessments?module_id='.$mod->id.'&type=post') ?>"
                         class="mod-asx-badge post" title="Post-assessments">
                        🏆 Post (<?= $mod->post_count ?>)
                      </a>
                    <?php else: ?>
                      <a href="<?= htmlspecialchars(ka_lms_append_return_to_url(base_url('assessments/create?course_id='.(int) $course->id.'&module_id='.$mod->id.'&type=post'), $lms_return_target), ENT_QUOTES) ?>"
                         class="mod-asx-badge add" title="Add post-assessment">
                        + Post-assessment
                      </a>
                    <?php endif; ?>
                    <?php
                      $cp_max = 3;
                      if ($eff_type === 'video'):
                        $cp_n = (int) ($mod->checkpoint_count ?? 0);
                    ?>
                      <?php if ($cp_n >= $cp_max): ?>
                      <a href="<?= base_url('assessments?module_id='.(int) $mod->id.'&type=checkpoint') ?>"
                         class="mod-asx-badge cp" title="Video checkpoints for this module (maximum reached)">
                        ▶ Checkpoint (<?= $cp_n ?>)
                      </a>
                      <?php elseif ($checkpoint_schema_ready): ?>
                      <a href="<?= htmlspecialchars(ka_lms_append_return_to_url(base_url('assessments/create?course_id='.(int) $course->id.'&module_id='.$mod->id.'&type=checkpoint'), $lms_return_target), ENT_QUOTES) ?>"
                         class="mod-asx-badge add" title="Add video progress checkpoint for this module">
                        + Video Checkpoint
                      </a>
                      <?php else: ?>
                      <span class="mod-asx-badge add" title="Video checkpoints require a database migration (lib_assessments context column). Contact your administrator."
                            style="opacity:.65;cursor:not-allowed;">
                        + Video Checkpoint
                      </span>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="mod-actions">
                  <button type="button" class="mod-action-btn" title="Edit" onclick="editModule(<?= $mod->id ?>)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <?php if ( ! $course_structure_locked): ?>
                  <button type="button" class="mod-action-btn danger" title="Delete" onclick="deleteModule(<?= $mod->id ?>)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                  </button>
                  <?php endif; ?>
                </div>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <?php $this->load->view('components/empty_state', [
                  'id'          => 'modEmpty',
                  'emoji'       => '📦',
                  'title'       => 'No modules yet',
                  'description' => 'Add the first module using the button below. You can choose content type, path or upload, and weight.',
                  'modifier'    => 'ka-empty--wide',
              ]); ?>
            <?php endif; ?>
          </div>
          <?php if ($course_structure_locked): ?>
          <button type="button" class="add-mod-btn is-disabled" disabled title="Locked while learners are enrolled">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Module (locked)
          </button>
          <?php else: ?>
          <button type="button" class="add-mod-btn" onclick="openModModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Module
          </button>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="crs-edit-panel" role="tabpanel" id="crs-edit-panel-access" aria-labelledby="crs-edit-tab-access" data-edit-tab="access" hidden>
      <p class="crs-edit-workspace-title">Access &amp; visibility</p>
      <div class="edit-crs-panel">
        <div class="edit-crs-hdr"><h3 class="edit-crs-title">Enrollment &amp; catalog</h3></div>
        <div class="edit-crs-body">
          <div class="ef-group">
            <label class="ef-label">Expiry days</label>
            <input type="number" name="expiry_days" class="ef-input"
                   value="<?= htmlspecialchars(set_value('expiry_days', $course->expiry_days ?? '')) ?>"
                   min="1" placeholder="No expiry">
            <div class="ef-help" style="margin-top:.35rem;font-size:.72rem;color:var(--ka-text-muted);">
              After this many days from course creation, published courses can be auto-unpublished per your policy.
            </div>
          </div>
        </div>
      </div>
      <?php if ($phase2_ready_ui): ?>
      <?php $this->load->view('manage_courses/phase2_block_visibility', get_defined_vars()); ?>
      <?php $this->load->view('manage_courses/phase2_block_access', get_defined_vars()); ?>
      <?php else: ?>
      <div class="edit-crs-panel" style="margin-top:1rem;">
        <div class="edit-crs-body">
          <div class="crs-p2-alert crs-p2-alert--warn" style="margin:0;">
            Run <code>application/sql/migration_phase2_course_features.sql</code> for department visibility, access types, and invitations.
          </div>
        </div>
      </div>
      <?php endif; ?>
    </section>

    <section class="crs-edit-panel" role="tabpanel" id="crs-edit-panel-people" aria-labelledby="crs-edit-tab-people" data-edit-tab="people" hidden>
      <p class="crs-edit-workspace-title">Instructors &amp; invitations</p>
      <?php if ($phase2_ready_ui): ?>
      <?php $this->load->view('manage_courses/phase2_block_instructors', get_defined_vars()); ?>
      <?php $this->load->view('manage_courses/phase2_block_invitations', get_defined_vars()); ?>
      <?php else: ?>
      <div class="edit-crs-panel">
        <div class="edit-crs-body">
          <p style="font-size:.875rem;color:var(--ka-text-muted,#64748b);margin:0;">Advanced instructor assignment and invitations require Phase 2 schema.</p>
        </div>
      </div>
      <?php endif; ?>
    </section>

    <section class="crs-edit-panel" role="tabpanel" id="crs-edit-panel-certificates" aria-labelledby="crs-edit-tab-certificates" data-edit-tab="certificates" hidden>
      <p class="crs-edit-workspace-title">Certificates</p>
      <div class="edit-crs-panel">
        <div class="edit-crs-hdr"><h3 class="edit-crs-title">Serial &amp; signatories</h3></div>
        <div class="edit-crs-body">
          <div class="ef-row">
            <div class="ef-group">
              <label class="ef-label">Certificate Prefix <span>*</span></label>
              <input type="text" name="certificate_prefix" class="ef-input"
                     value="<?= htmlspecialchars(set_value('certificate_prefix', $course->certificate_prefix ?? '')) ?>"
                     maxlength="12" placeholder="e.g. UIUX">
              <?php if (form_error('certificate_prefix')): ?><div class="ef-error"><?= form_error('certificate_prefix') ?></div><?php endif; ?>
              <div class="ef-help" style="margin-top:.35rem;font-size:.72rem;color:var(--ka-text-muted,#64748b);">
                Serial format: {PREFIX}-<?= date('Y') ?>-0001
              </div>
            </div>
          </div>
          <?php if ( ! empty($phase3_ready)): ?>
          <?php $this->load->view('manage_courses/phase3_signatories_block', get_defined_vars()); ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="crs-edit-panel" role="tabpanel" id="crs-edit-panel-batches" aria-labelledby="crs-edit-tab-batches" data-edit-tab="batches" hidden>
      <p class="crs-edit-workspace-title">Batches</p>
      <?php if ( ! empty($phase3_batches_ready)): ?>
      <div class="edit-crs-panel">
        <div class="edit-crs-body">
          <?php $this->load->view('manage_courses/phase3_batches_block', get_defined_vars()); ?>
        </div>
      </div>
      <?php else: ?>
      <div class="edit-crs-panel">
        <div class="edit-crs-body">
          <p style="font-size:.875rem;color:var(--ka-text-muted,#64748b);margin:0;">Course batches are available after the Phase 3 batches migration is applied.</p>
        </div>
      </div>
      <?php endif; ?>
    </section>

  </div>

  <?php if ($phase2_ready_ui): ?>
  </div>
  <?php endif; ?>

  <?php if ( ! empty($phase3_ready) || ! empty($phase3_batches_ready)): ?>
  <?php $this->load->view('manage_courses/phase3_clone_rows_script', get_defined_vars()); ?>
  <?php endif; ?>

  <?php if ($phase2_ready_ui): ?>
  <?php $this->load->view('manage_courses/phase2_scripts', get_defined_vars()); ?>
  <?php endif; ?>

  <div class="crs-edit-sticky-save">
    <p>Save applies to every section (except <strong>Send invitations</strong>, which sends immediately).</p>
    <button type="submit" class="ef-save-btn">Save changes</button>
  </div>
</form>

<script>
(function () {
  var tabs = document.querySelectorAll('.crs-edit-tab[data-edit-tab]');
  var panels = document.querySelectorAll('.crs-edit-panel[data-edit-tab]');
  var progress = document.getElementById('crsEditTabProgress');
  var total = tabs.length;

  function setTab(name) {
    var i = 0;
    tabs.forEach(function (btn, idx) {
      var on = btn.getAttribute('data-edit-tab') === name;
      if (on) i = idx + 1;
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    panels.forEach(function (panel) {
      var on = panel.getAttribute('data-edit-tab') === name;
      panel.classList.toggle('is-active', on);
      if (on) {
        panel.removeAttribute('hidden');
      } else {
        panel.setAttribute('hidden', 'hidden');
      }
    });
    if (progress) {
      progress.textContent = 'Step ' + i + ' / ' + total;
    }
    var tabField = document.getElementById('editReturnTab');
    if (tabField) {
      tabField.value = name || '';
    }
    try {
      if (history.replaceState) {
        history.replaceState(null, '', '#' + name);
      }
    } catch (e) {}
  }

  tabs.forEach(function (btn) {
    btn.addEventListener('click', function () {
      setTab(btn.getAttribute('data-edit-tab'));
    });
  });

  var initial = 'overview';
  var formEl = document.getElementById('course-edit-form');
  if (formEl && formEl.getAttribute('data-initial-edit-tab') === 'modules') {
    initial = 'modules';
  } else {
    var h = (location.hash || '').replace(/^#/, '');
    if (h && document.querySelector('.crs-edit-panel[data-edit-tab="' + h + '"]')) {
      initial = h;
    }
  }
  setTab(initial);

  if (formEl) {
    formEl.addEventListener('submit', function (e) {
      if (e.submitter && e.submitter.formNoValidate) {
        return;
      }
      panels.forEach(function (panel) {
        if ( ! panel.hasAttribute('hidden')) {
          return;
        }
        panel.querySelectorAll('input, select, textarea').forEach(function (el) {
          el.required = false;
          el.removeAttribute('min');
          el.removeAttribute('max');
        });
      });
    });
  }
})();
</script>
