<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$categories   = $categories   ?? [];
$modalities   = $modalities   ?? [];
$access_types = $access_types ?? [];
$teachers     = $teachers     ?? [];
$user_role    = strtolower($user->role ?? 'teacher');
$is_admin     = $user_role === 'admin';
?>
<?php $this->load->view('layouts/alerts'); ?>
<style>
.crs-layout { display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start; }
@media(max-width:991.98px){ .crs-layout{grid-template-columns:1fr;} }
.crs-panel { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;overflow:hidden;margin-bottom:1.25rem; }
.crs-panel-hdr { padding:1rem 1.25rem;border-bottom:1px solid var(--ka-border,#e2e8f0); }
.crs-panel-title { font-size:.9rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0; }
.crs-panel-body { padding:1.25rem; }
.crs-form-group { margin-bottom:1.125rem; }
.crs-label { display:block;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:.425rem; }
.crs-label span { color:#dc2626; }
.crs-input, .crs-select, .crs-textarea {
  width:100%;padding:.625rem .875rem;
  border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;
  font-size:.875rem;color:var(--ka-text,#1e293b);background:var(--ka-bg,#f8fafc);
  outline:none;font-family:inherit;transition:all .2s;
}
.crs-input:focus,.crs-select:focus,.crs-textarea:focus { border-color:var(--ka-primary,#6dabcf);background:#fff;box-shadow:0 0 0 3px rgba(109,171,207,.15); }
.crs-select { height:42px;cursor:pointer; }
.crs-textarea { min-height:100px;resize:vertical; }
.crs-row { display:grid;grid-template-columns:1fr 1fr;gap:.875rem; }
@media(max-width:575.98px){ .crs-row{grid-template-columns:1fr;} }
.crs-help  { font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin-top:.375rem; }
.crs-error { font-size:.6875rem;color:#dc2626;margin-top:.375rem; }
.crs-submit-btn { width:100%;padding:.875rem;border-radius:9px;background:var(--ka-navy,#1a3a5c);color:#fff;border:none;cursor:pointer;font-size:.9375rem;font-weight:700;transition:all .2s; }
.crs-submit-btn:hover { background:#254d75;transform:translateY(-1px); }
</style>

<!-- Page header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;" class="animate__animated animate__fadeIn animate__fast">
  <div>
    <h2 style="font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 3px;letter-spacing:-.02em;">Create New Course</h2>
    <p style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0;">Fill in the course details. You'll add modules on the next screen.</p>
  </div>
  <a href="<?= base_url('manage_courses') ?>" style="display:inline-flex;align-items:center;gap:6px;padding:.5rem 1rem;border-radius:8px;font-size:.8125rem;font-weight:600;text-decoration:none;border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;color:var(--ka-text,#1e293b);">← Back</a>
</div>

<form method="post" action="<?= base_url('manage_courses/create') ?>">
  <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

  <div class="crs-layout animate__animated animate__fadeInUp animate__fast">
    <div>
      <!-- Basic info -->
      <div class="crs-panel">
        <div class="crs-panel-hdr"><h3 class="crs-panel-title">Course Details</h3></div>
        <div class="crs-panel-body">

          <div class="crs-form-group">
            <label class="crs-label" for="title">Course Title <span>*</span></label>
            <input type="text" id="title" name="title" class="crs-input"
                   placeholder="e.g. Infection Control and Prevention"
                   value="<?= set_value('title') ?>" required>
            <?php if (form_error('title')): ?><div class="crs-error"><?= form_error('title') ?></div><?php endif; ?>
          </div>

          <div class="crs-form-group">
            <label class="crs-label" for="description">Description</label>
            <textarea id="description" name="description" class="crs-textarea"
                      placeholder="Brief overview of what this course covers…"><?= set_value('description') ?></textarea>
          </div>

          <div class="crs-row">
            <div class="crs-form-group">
              <label class="crs-label" for="category_id">Category <span>*</span></label>
              <select id="category_id" name="category_id" class="crs-select" required>
                <option value="">-- Select --</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat->id ?>" <?= set_select('category_id', $cat->id) ?>>
                  <?= htmlspecialchars($cat->name) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <?php if (form_error('category_id')): ?><div class="crs-error"><?= form_error('category_id') ?></div><?php endif; ?>
            </div>
            <div class="crs-form-group">
              <label class="crs-label" for="modality_id">Modality <span>*</span></label>
              <select id="modality_id" name="modality_id" class="crs-select" required>
                <option value="">-- Select --</option>
                <?php foreach ($modalities as $m): ?>
                <option value="<?= $m->modality_id ?>" <?= set_select('modality_id', $m->modality_id) ?>>
                  <?= htmlspecialchars($m->modality_desc) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <?php if (form_error('modality_id')): ?><div class="crs-error"><?= form_error('modality_id') ?></div><?php endif; ?>
            </div>
          </div>

          <div class="crs-row">
            <div class="crs-form-group">
              <label class="crs-label" for="access_type_id">Access Type</label>
              <select id="access_type_id" name="access_type_id" class="crs-select">
                <option value="">-- None --</option>
                <?php foreach ($access_types as $at): ?>
                <option value="<?= $at->access_type_id ?>" <?= set_select('access_type_id', $at->access_type_id) ?>>
                  <?= htmlspecialchars($at->access_type_desc) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="crs-form-group">
              <label class="crs-label" for="expiry_days">Expiry Days</label>
              <input type="number" id="expiry_days" name="expiry_days" class="crs-input"
                     placeholder="e.g. 365" min="1" value="<?= set_value('expiry_days') ?>">
              <div class="crs-help">Days before enrollment expires (leave blank = no expiry)</div>
            </div>
          </div>

          <?php if ($is_admin && ! empty($teachers)): ?>
          <div class="crs-form-group">
            <label class="crs-label" for="created_by">Assign to Instructor</label>
            <select id="created_by" name="created_by" class="crs-select">
              <option value="<?= $user->id ?>">Myself (<?= htmlspecialchars($user->fullname) ?>)</option>
              <?php foreach ($teachers as $t): ?>
              <option value="<?= $t->id ?>" <?= set_select('created_by', $t->id) ?>>
                <?= htmlspecialchars($t->fullname) ?> (<?= htmlspecialchars($t->employee_id) ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div>
      <div class="crs-panel">
        <div class="crs-panel-hdr"><h3 class="crs-panel-title">Publish Course</h3></div>
        <div class="crs-panel-body">
          <p style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0 0 1rem;">
            After creating the course you'll be taken to the module builder to add content.
          </p>
          <button type="submit" class="crs-submit-btn">Create &amp; Add Modules →</button>
          <a href="<?= base_url('manage_courses') ?>"
             style="display:block;text-align:center;margin-top:.625rem;font-size:.75rem;color:var(--ka-text-muted,#64748b);text-decoration:none;">
            Cancel
          </a>
        </div>
      </div>
      <div class="crs-panel">
        <div class="crs-panel-hdr"><h3 class="crs-panel-title">Tips</h3></div>
        <div class="crs-panel-body" style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);line-height:1.6;">
          <p style="margin:0 0 .625rem;">✏️ <strong>Title</strong> — Keep it clear and descriptive.</p>
          <p style="margin:0 0 .625rem;">📁 <strong>Category</strong> — Helps employees find courses in the catalog.</p>
          <p style="margin:0 0 .625rem;">🎯 <strong>Modality</strong> — Indicates the delivery method (online, classroom, etc.).</p>
          <p style="margin:0;">⏱️ <strong>Expiry</strong> — Limits how long an enrollment is valid.</p>
        </div>
      </div>
    </div>
  </div>
</form>