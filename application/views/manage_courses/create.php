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

$posted_categories  = $this->input->post('category_ids');
$sel_categories = is_array($posted_categories) ? array_map('intval', $posted_categories) : ($phase2 ? (array) $phase2->category_ids : []);
$legacy_category_id = (int) set_value('category_id', 0);
if (empty($sel_categories) && $legacy_category_id > 0) {
    $sel_categories = [$legacy_category_id];
}

$default_owner_id = $user_id;
$posted_owner = (int) $this->input->post('created_by');
if ($is_admin && $posted_owner > 0) {
    $default_owner_id = $posted_owner;
}
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/manage_courses.css'); ?>">

<div class="crs-page-head animate__animated animate__fadeIn animate__fast">
  <div>
    <h2 class="crs-page-title">Create draft course</h2>
    <p class="crs-page-subtitle">Add a title, modality, and categories. Everything else—access, certificates, modules—is configured after the draft is created.</p>
  </div>
  <a href="<?= base_url('manage_courses') ?>" class="crs-back-link">&larr; Back</a>
</div>

<form method="post" action="<?= base_url('manage_courses/create') ?>" class="ka-form-flow animate__animated animate__fadeInUp animate__fast">
  <input type="hidden" name="<?= html_escape($csrf_field_name ?? '') ?>" value="<?= html_escape($csrf_hash ?? '') ?>">

  <?php if ($phase2_ready): ?>
  <input type="hidden" id="category_id" name="category_id" value="<?= (int) ($sel_categories[0] ?? 0) ?>">
  <input type="hidden" name="access_type" value="approval_required">
  <input type="hidden" name="instructor_ids[]" id="create_primary_instructor_id" value="<?= (int) $default_owner_id ?>">
  <?php endif; ?>

  <div class="crs-layout">
    <div>
      <div class="crs-panel">
        <div class="crs-panel-hdr">
          <h3 class="crs-panel-title">Quick create</h3>
          <p class="crs-panel-kicker">Only the essentials. You will land on the course workspace to finish setup.</p>
        </div>
        <div class="crs-panel-body">
          <div class="crs-form-group">
            <label class="crs-label" for="title">Course title <span>*</span></label>
            <input type="text" id="title" name="title" class="crs-input"
                   placeholder="e.g. Infection Control and Prevention"
                   value="<?= htmlspecialchars(set_value('title'), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php if (form_error('title')): ?><div class="crs-error"><?= form_error('title') ?></div><?php endif; ?>
          </div>

          <div class="crs-form-group">
            <label class="crs-label" for="description">Description</label>
            <textarea id="description" name="description" class="crs-textarea"
                      placeholder="Brief overview of what this course covers…"><?= htmlspecialchars(set_value('description'), ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>

          <div class="crs-form-group">
            <label class="crs-label" for="modality_id">Modality <span>*</span></label>
            <select id="modality_id" name="modality_id" class="crs-select" required>
              <option value="">-- Select --</option>
              <?php foreach ($modalities as $m): ?>
              <option value="<?= (int) $m->modality_id ?>" <?= set_select('modality_id', $m->modality_id) ?>>
                <?= htmlspecialchars(etd_modality_display_label($m->modality_desc ?? ''), ENT_QUOTES, 'UTF-8') ?>
              </option>
              <?php endforeach; ?>
            </select>
            <?php if (form_error('modality_id')): ?><div class="crs-error"><?= form_error('modality_id') ?></div><?php endif; ?>
          </div>

          <?php if ($phase2_ready): ?>
          <?php $is_edit_form = false; include APPPATH . 'views/manage_courses/phase2_init.php'; ?>
          <?php $this->load->view('manage_courses/phase2_assets_links', get_defined_vars()); ?>
          <div class="crs-p2-stack crs-create-quick-p2">
            <?php $this->load->view('manage_courses/phase2_alerts_block', get_defined_vars()); ?>
            <?php $this->load->view('manage_courses/phase2_block_categories', get_defined_vars()); ?>
          </div>
          <?php $this->load->view('manage_courses/phase2_scripts', get_defined_vars()); ?>
          <?php if (form_error('category_id')): ?><div class="crs-error"><?= form_error('category_id') ?></div><?php endif; ?>
          <?php else: ?>
          <div class="crs-form-group">
            <label class="crs-label" for="legacy_category_id">Category <span>*</span></label>
            <select id="legacy_category_id" name="category_id" class="crs-select" required>
              <option value="">-- Select --</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= (int) $cat->id ?>" <?= set_select('category_id', $cat->id) ?>>
                <?= htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8') ?>
              </option>
              <?php endforeach; ?>
            </select>
            <?php if (form_error('category_id')): ?><div class="crs-error"><?= form_error('category_id') ?></div><?php endif; ?>
          </div>
          <div class="crs-note crs-note-warning">Run <code>application/sql/migration_phase2_course_features.sql</code> to enable multi-category selection and advanced course options.</div>
          <?php endif; ?>

          <?php if ($is_admin && ! empty($teachers)): ?>
          <div class="crs-form-group">
            <label class="crs-label" for="created_by">Primary owner</label>
            <select id="created_by" name="created_by" class="crs-select">
              <option value="<?= $user_id ?>">Myself (<?= htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') ?>)</option>
              <?php foreach ($teachers as $t): ?>
              <option value="<?= (int) $t->id ?>" <?= set_select('created_by', $t->id) ?>>
                <?= htmlspecialchars($t->fullname, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($t->employee_id, ENT_QUOTES, 'UTF-8') ?>)
              </option>
              <?php endforeach; ?>
            </select>
            <div class="crs-help">This user becomes the course owner and primary instructor until you change it on the edit screen.</div>
          </div>
          <?php endif; ?>

          <div class="crs-note" style="margin-top:1rem;">
            <strong>Thumbnail &amp; uploads:</strong> you can attach a course image and module files after the draft is created.
          </div>
        </div>
      </div>
    </div>

    <div>
      <div class="crs-panel">
        <div class="crs-panel-hdr"><h3 class="crs-panel-title">Next step</h3></div>
        <div class="crs-panel-body">
          <p class="crs-page-subtitle" style="margin-top:0;">After creating the draft, you will open the <strong>course workspace</strong> to add modules, visibility, certificates, and batches.</p>
          <button type="submit" class="crs-submit-btn">Create draft course</button>
          <a href="<?= base_url('manage_courses') ?>" class="crs-cancel-link">Cancel</a>
        </div>
      </div>
    </div>
  </div>
</form>

<?php if ($phase2_ready && $is_admin && ! empty($teachers)): ?>
<script>
(function () {
  var sel = document.getElementById('created_by');
  var hid = document.getElementById('create_primary_instructor_id');
  if (!sel || !hid) return;
  function sync() {
    var v = parseInt(sel.value, 10) || 0;
    if (v > 0) hid.value = String(v);
  }
  sel.addEventListener('change', sync);
  sync();
})();
</script>
<?php endif; ?>
