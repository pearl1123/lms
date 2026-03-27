<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$course       = $course       ?? null;
$modules      = $modules      ?? [];
$categories   = $categories   ?? [];
$modalities   = $modalities   ?? [];
$access_types = $access_types ?? [];
$teachers     = $teachers     ?? [];
$user_role    = strtolower($user->role ?? 'teacher');
$is_admin     = $user_role === 'admin';
if ( ! $course) return;

$content_types = [
    'pdf'            => ['label' => 'PDF Document',    'icon' => '📄', 'color' => '#ef4444'],
    'slides'         => ['label' => 'Slides',          'icon' => '📊', 'color' => '#3b82f6'],
    'video'          => ['label' => 'Video',           'icon' => '🎬', 'color' => '#8b5cf6'],
    'audio'          => ['label' => 'Audio',           'icon' => '🎧', 'color' => '#f59f00'],
    'zoom_recording' => ['label' => 'Zoom Recording',  'icon' => '🎥', 'color' => '#06b6d4'],
];
?>
<?php $this->load->view('layouts/alerts'); ?>
<style>
/* Layout */
.edit-crs-layout { display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start; }
@media(max-width:1099.98px){ .edit-crs-layout{grid-template-columns:1fr;} }
.edit-crs-panel { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;overflow:hidden;margin-bottom:1.25rem; }
.edit-crs-hdr { padding:1rem 1.25rem;border-bottom:1px solid var(--ka-border,#e2e8f0);display:flex;align-items:center;justify-content:space-between; }
.edit-crs-title { font-size:.9rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0; }
.edit-crs-body { padding:1.25rem; }
.ef-group { margin-bottom:1.125rem; }
.ef-label { display:block;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:.425rem; }
.ef-label span { color:#dc2626; }
.ef-input,.ef-select,.ef-textarea { width:100%;padding:.625rem .875rem;border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;font-size:.875rem;color:var(--ka-text,#1e293b);background:var(--ka-bg,#f8fafc);outline:none;font-family:inherit;transition:all .2s; }
.ef-input:focus,.ef-select:focus,.ef-textarea:focus { border-color:var(--ka-primary,#6dabcf);background:#fff;box-shadow:0 0 0 3px rgba(109,171,207,.15); }
.ef-select { height:40px;cursor:pointer; }
.ef-textarea { min-height:90px;resize:vertical; }
.ef-row { display:grid;grid-template-columns:1fr 1fr;gap:.875rem; }
@media(max-width:575.98px){ .ef-row{grid-template-columns:1fr;} }
.ef-error { font-size:.6875rem;color:#dc2626;margin-top:.375rem; }
.ef-help  { font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin-top:.375rem; }
.ef-save-btn { padding:.5625rem 1.25rem;border-radius:8px;background:var(--ka-navy,#1a3a5c);color:#fff;border:none;cursor:pointer;font-size:.8125rem;font-weight:700;transition:all .15s; }
.ef-save-btn:hover { background:#254d75; }

/* Module list */
.mod-list { display:flex;flex-direction:column;gap:.625rem; }
.mod-item { display:flex;align-items:center;gap:.75rem;padding:.875rem 1rem;border:1.5px solid var(--ka-border,#e2e8f0);border-radius:10px;background:#fff;cursor:grab;transition:box-shadow .15s; }
.mod-item:active { cursor:grabbing; }
.mod-item.dragging { opacity:.5;box-shadow:0 8px 24px rgba(0,0,0,.12); }
.mod-item.drag-over { border-color:var(--ka-primary,#6dabcf);background:var(--ka-accent,#e8f4fd); }
.mod-drag-handle { color:var(--ka-border,#e2e8f0);cursor:grab;flex-shrink:0; }
.mod-drag-handle svg { width:16px;height:16px; }
.mod-order { width:26px;height:26px;border-radius:50%;background:var(--ka-navy,#1a3a5c);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.6875rem;font-weight:700;flex-shrink:0; }
.mod-type-icon { font-size:1.25rem;flex-shrink:0; }
.mod-body { flex:1;min-width:0; }
.mod-title { font-size:.875rem;font-weight:700;color:var(--ka-text,#1e293b);margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.mod-meta  { font-size:.6875rem;color:var(--ka-text-muted,#64748b); }
.mod-actions { display:flex;gap:.375rem;flex-shrink:0; }
.mod-action-btn { width:28px;height:28px;border-radius:7px;border:1.5px solid var(--ka-border,#e2e8f0);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--ka-text-muted,#64748b);transition:all .15s; }
.mod-action-btn:hover { background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);border-color:var(--ka-primary,#6dabcf); }
.mod-action-btn.danger:hover { background:#fef2f2;color:#dc2626;border-color:#dc2626; }
.mod-action-btn svg { width:13px;height:13px; }
.mod-asx-badge {
  display:inline-flex;align-items:center;gap:3px;
  padding:2px 7px;border-radius:20px;font-size:.5625rem;font-weight:700;
  white-space:nowrap;text-decoration:none;transition:all .15s;
}
.mod-asx-badge.pre  { background:#eff6ff;color:#185fa5;border:1px solid #bae6fd; }
.mod-asx-badge.post { background:#ecfdf5;color:#065f46;border:1px solid #bbf7d0; }
.mod-asx-badge.add  { background:var(--ka-bg,#f8fafc);color:var(--ka-text-muted,#64748b);border:1.5px dashed var(--ka-border,#e2e8f0); }
.mod-asx-badge:hover { opacity:.8; }
.mod-asx-badges { display:flex;align-items:center;gap:4px;margin-top:4px;flex-wrap:wrap; }

/* Add module button */
.add-mod-btn { display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;padding:.875rem;border-radius:10px;border:2px dashed var(--ka-border,#e2e8f0);background:transparent;cursor:pointer;font-size:.875rem;font-weight:600;color:var(--ka-text-muted,#64748b);transition:all .18s;margin-top:.625rem; }
.add-mod-btn:hover { border-color:var(--ka-primary,#6dabcf);color:var(--ka-primary,#6dabcf);background:var(--ka-accent,#e8f4fd); }

/* Module modal */
.mod-modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:2000;backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:1rem; }
.mod-modal-overlay.open { display:flex; }
.mod-modal { background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.18);animation:modIn .25s ease; }
@keyframes modIn { from{opacity:0;transform:translateY(-14px)} to{opacity:1;transform:translateY(0)} }
.mod-modal-hdr { padding:1.125rem 1.375rem;border-bottom:1px solid var(--ka-border,#e2e8f0);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;border-radius:16px 16px 0 0; }
.mod-modal-title { font-size:1rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0; }
.mod-modal-close { width:30px;height:30px;border-radius:8px;border:1.5px solid var(--ka-border,#e2e8f0);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--ka-text-muted,#64748b);transition:all .15s; }
.mod-modal-close:hover { background:#fef2f2;color:#dc2626;border-color:#dc2626; }
.mod-modal-close svg { width:14px;height:14px; }
.mod-modal-body   { padding:1.375rem; }
.mod-modal-footer { padding:1rem 1.375rem;border-top:1px solid var(--ka-border,#e2e8f0);display:flex;justify-content:flex-end;gap:.625rem;position:sticky;bottom:0;background:#fff;border-radius:0 0 16px 16px; }
.mod-cancel { padding:.5rem 1rem;border-radius:8px;border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;cursor:pointer;font-size:.8125rem;font-weight:600;color:var(--ka-text-muted,#64748b);transition:all .15s; }
.mod-cancel:hover { border-color:var(--ka-text-muted,#64748b); }
.mod-save { padding:.5rem 1.25rem;border-radius:8px;background:var(--ka-navy,#1a3a5c);color:#fff;border:none;cursor:pointer;font-size:.8125rem;font-weight:700;transition:all .15s; }
.mod-save:hover { background:#254d75; }

/* Content type picker */
.ct-grid { display:grid;grid-template-columns:repeat(5,1fr);gap:.5rem;margin-bottom:1.125rem; }
@media(max-width:479.98px){ .ct-grid{grid-template-columns:repeat(3,1fr);} }
.ct-card { border:2px solid var(--ka-border,#e2e8f0);border-radius:10px;padding:.625rem .375rem;text-align:center;cursor:pointer;transition:all .15s; }
.ct-card input { display:none; }
.ct-card .ct-icon  { font-size:1.375rem;margin-bottom:3px;line-height:1; }
.ct-card .ct-label { font-size:.5625rem;font-weight:700;color:var(--ka-text-muted,#64748b);line-height:1.2; }
.ct-card.selected  { border-color:var(--ka-navy,#1a3a5c);background:var(--ka-accent,#e8f4fd); }
.ct-card.selected .ct-label { color:var(--ka-navy,#1a3a5c); }

/* Sidebar panels */
.side-info-item { display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid var(--ka-border,#e2e8f0);font-size:.8125rem; }
.side-info-item:last-child { border-bottom:none; }
.side-info-label { color:var(--ka-text-muted,#64748b);font-weight:500; }
.side-info-value { font-weight:700;color:var(--ka-text,#1e293b); }
</style>

<!-- Page header -->
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;" class="animate__animated animate__fadeIn animate__fast">
  <div>
    <h2 style="font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 3px;letter-spacing:-.02em;">
      Edit Course
    </h2>
    <p style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0;">
      <?= htmlspecialchars($course->title) ?>
      &nbsp;·&nbsp;
      <span style="color:<?= $course->archived ? '#64748b' : '#22c55e' ?>;font-weight:600;">
        <?= $course->archived ? 'Archived' : 'Active' ?>
      </span>
    </p>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
    <a href="<?= base_url('courses/view/'.$course->id) ?>" target="_blank"
       style="display:inline-flex;align-items:center;gap:5px;padding:.5rem .875rem;border-radius:8px;font-size:.8125rem;font-weight:600;text-decoration:none;border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;color:var(--ka-text,#1e293b);">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      Preview
    </a>
    <a href="<?= base_url('manage_courses') ?>"
       style="display:inline-flex;align-items:center;gap:5px;padding:.5rem .875rem;border-radius:8px;font-size:.8125rem;font-weight:600;text-decoration:none;border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;color:var(--ka-text,#1e293b);">
      ← Back
    </a>
  </div>
</div>

<div class="edit-crs-layout animate__animated animate__fadeInUp animate__fast">

  <!-- ── LEFT COLUMN ── -->
  <div>

    <!-- Course details form -->
    <div class="edit-crs-panel">
      <div class="edit-crs-hdr"><h3 class="edit-crs-title">Course Details</h3></div>
      <div class="edit-crs-body">
        <form method="post" action="<?= base_url('manage_courses/edit/'.$course->id) ?>">
          <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">

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

          <div class="ef-row">
            <div class="ef-group">
              <label class="ef-label">Category <span>*</span></label>
              <select name="category_id" class="ef-select" required>
                <option value="">-- Select --</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat->id ?>" <?= ((int)$cat->id === (int)($course->category_id ?? 0)) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat->name) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <?php if (form_error('category_id')): ?><div class="ef-error"><?= form_error('category_id') ?></div><?php endif; ?>
            </div>
            <div class="ef-group">
              <label class="ef-label">Modality <span>*</span></label>
              <select name="modality_id" class="ef-select" required>
                <option value="">-- Select --</option>
                <?php foreach ($modalities as $m): ?>
                <option value="<?= $m->modality_id ?>" <?= ((int)$m->modality_id === (int)($course->modality_id ?? 0)) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($m->modality_desc) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <?php if (form_error('modality_id')): ?><div class="ef-error"><?= form_error('modality_id') ?></div><?php endif; ?>
            </div>
          </div>

          <div class="ef-row">
            <div class="ef-group">
              <label class="ef-label">Access Type</label>
              <select name="access_type_id" class="ef-select">
                <option value="">-- None --</option>
                <?php foreach ($access_types as $at): ?>
                <option value="<?= $at->access_type_id ?>" <?= ((int)$at->access_type_id === (int)($course->access_type_id ?? 0)) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($at->access_type_desc) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="ef-group">
              <label class="ef-label">Expiry Days</label>
              <input type="number" name="expiry_days" class="ef-input"
                     value="<?= htmlspecialchars(set_value('expiry_days', $course->expiry_days ?? '')) ?>"
                     min="1" placeholder="No expiry">
            </div>
          </div>

          <div style="display:flex;justify-content:flex-end;">
            <button type="submit" class="ef-save-btn">Save Details</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Module builder -->
    <div class="edit-crs-panel">
      <div class="edit-crs-hdr">
        <h3 class="edit-crs-title">
          Modules
          <span style="font-size:.75rem;font-weight:500;color:var(--ka-text-muted,#64748b);margin-left:.375rem;" id="modCountLabel">
            <?= count($modules) ?> module<?= count($modules) !== 1 ? 's' : '' ?>
          </span>
        </h3>
        <span style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);">Drag to reorder</span>
      </div>
      <div class="edit-crs-body">

        <div class="mod-list" id="modList">
          <?php if ( ! empty($modules)): ?>
            <?php foreach ($modules as $idx => $mod):
              $ct = $content_types[$mod->content_type] ?? ['icon'=>'📁','label'=>$mod->content_type,'color'=>'#64748b'];
            ?>
            <div class="mod-item" id="moditem-<?= $mod->id ?>"
                 draggable="true"
                 data-id="<?= $mod->id ?>"
                 data-title="<?= htmlspecialchars($mod->title, ENT_QUOTES) ?>"
                 data-desc="<?= htmlspecialchars($mod->description ?? '', ENT_QUOTES) ?>"
                 data-type="<?= $mod->content_type ?>"
                 data-path="<?= htmlspecialchars($mod->content_path ?? '', ENT_QUOTES) ?>"
                 data-weight="<?= $mod->weight_percentage ?? 0 ?>">
              <div class="mod-drag-handle"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="6" r="1" fill="currentColor"/><circle cx="15" cy="6" r="1" fill="currentColor"/><circle cx="9" cy="12" r="1" fill="currentColor"/><circle cx="15" cy="12" r="1" fill="currentColor"/><circle cx="9" cy="18" r="1" fill="currentColor"/><circle cx="15" cy="18" r="1" fill="currentColor"/></svg></div>
              <div class="mod-order"><?= $idx + 1 ?></div>
              <div class="mod-type-icon"><?= $ct['icon'] ?></div>
              <div class="mod-body">
                <div class="mod-title"><?= htmlspecialchars($mod->title) ?></div>
                <div class="mod-meta"><?= $ct['label'] ?><?= $mod->weight_percentage > 0 ? ' · ' . (float)$mod->weight_percentage . '% weight' : '' ?></div>
                <div class="mod-asx-badges">
                  <?php if (($mod->pre_count ?? 0) > 0): ?>
                    <a href="<?= base_url('assessments?module_id='.$mod->id.'&type=pre') ?>"
                       class="mod-asx-badge pre" title="Pre-assessments">
                      ⏱ Pre (<?= $mod->pre_count ?>)
                    </a>
                  <?php else: ?>
                    <a href="<?= base_url('assessments/create?module_id='.$mod->id.'&type=pre') ?>"
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
                    <a href="<?= base_url('assessments/create?module_id='.$mod->id.'&type=post') ?>"
                       class="mod-asx-badge add" title="Add post-assessment">
                      + Post-assessment
                    </a>
                  <?php endif; ?>
                </div>
              </div>
              <div class="mod-actions">
                <button type="button" class="mod-action-btn" title="Edit" onclick="editModule(<?= $mod->id ?>)">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button type="button" class="mod-action-btn danger" title="Delete" onclick="deleteModule(<?= $mod->id ?>)">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                </button>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div id="modEmpty" style="text-align:center;padding:2rem;color:var(--ka-text-muted,#64748b);font-size:.875rem;">
              No modules yet. Add the first module below.
            </div>
          <?php endif; ?>
        </div>

        <button type="button" class="add-mod-btn" onclick="openModModal()">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Module
        </button>
      </div>
    </div>

  </div>

  <!-- ── RIGHT SIDEBAR ── -->
  <div style="position:sticky;top:80px;">

    <!-- Course info -->
    <div class="edit-crs-panel" style="margin-bottom:1rem;">
      <div class="edit-crs-hdr"><h3 class="edit-crs-title">Course Info</h3></div>
      <div class="edit-crs-body" style="padding-top:.25rem;padding-bottom:.25rem;">
        <div class="side-info-item">
          <span class="side-info-label">Modules</span>
          <span class="side-info-value" id="sideModCount"><?= count($modules) ?></span>
        </div>
        <div class="side-info-item">
          <span class="side-info-label">Enrolled</span>
          <span class="side-info-value"><?= $course->enrolled_count ?? 0 ?></span>
        </div>
        <div class="side-info-item">
          <span class="side-info-label">Category</span>
          <span class="side-info-value" style="font-size:.75rem;"><?= htmlspecialchars($course->category_name ?? '—') ?></span>
        </div>
        <div class="side-info-item">
          <span class="side-info-label">Created</span>
          <span class="side-info-value"><?= date('M j, Y', strtotime($course->created_at)) ?></span>
        </div>
        <?php if ($course->expiry_days): ?>
        <div class="side-info-item">
          <span class="side-info-label">Expiry</span>
          <span class="side-info-value"><?= $course->expiry_days ?> days</span>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Admin: reassign -->
    <?php if ($is_admin && ! empty($teachers)): ?>
    <div class="edit-crs-panel" style="margin-bottom:1rem;">
      <div class="edit-crs-hdr"><h3 class="edit-crs-title">Reassign Course</h3></div>
      <div class="edit-crs-body">
        <form method="post" action="<?= base_url('manage_courses/reassign') ?>">
          <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
          <input type="hidden" name="course_id" value="<?= $course->id ?>">
          <div class="ef-group" style="margin-bottom:.875rem;">
            <label class="ef-label">Assign to</label>
            <select name="new_owner_id" class="ef-select" required>
              <?php foreach ($teachers as $t): ?>
              <option value="<?= $t->id ?>" <?= (int)$t->id === (int)$course->created_by ? 'selected' : '' ?>>
                <?= htmlspecialchars($t->fullname) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" style="width:100%;padding:.5rem;border-radius:8px;background:var(--ka-accent,#e8f4fd);color:var(--ka-navy,#1a3a5c);border:none;cursor:pointer;font-size:.8125rem;font-weight:700;transition:all .15s;">
            Reassign
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Danger zone -->
    <div class="edit-crs-panel">
      <div class="edit-crs-hdr"><h3 class="edit-crs-title" style="color:#dc2626;">Danger Zone</h3></div>
      <div class="edit-crs-body">
        <p style="font-size:.75rem;color:var(--ka-text-muted,#64748b);margin:0 0 .875rem;">Archiving the course will hide it from the catalog and prevent new enrollments.</p>
        <button onclick="KA.deleteConfirm('<?= base_url('manage_courses/delete/'.$course->id) ?>', '<?= htmlspecialchars(addslashes($course->title), ENT_QUOTES) ?>')"
                style="width:100%;padding:.5rem;border-radius:8px;border:1.5px solid #fecaca;background:#fef2f2;color:#dc2626;cursor:pointer;font-size:.8125rem;font-weight:700;transition:all .15s;">
          Archive Course
        </button>
      </div>
    </div>

  </div>
</div>

<!-- ══ Module Modal ══════════════════════════════════════════ -->
<div class="mod-modal-overlay" id="modModalOverlay" onclick="closeModModalOutside(event)">
  <div class="mod-modal">
    <div class="mod-modal-hdr">
      <h3 class="mod-modal-title" id="modModalTitle">Add Module</h3>
      <button type="button" class="mod-modal-close" onclick="closeModModal()">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="mod-modal-body">
      <input type="hidden" id="modId" value="0">

      <!-- Content type picker -->
      <div style="margin-bottom:1.125rem;">
        <label style="display:block;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:.625rem;">Content Type <span style="color:#dc2626;">*</span></label>
        <div class="ct-grid">
          <?php foreach ($content_types as $key => $ct): ?>
          <label class="ct-card <?= $key === 'pdf' ? 'selected' : '' ?>" id="ct-<?= $key ?>">
            <input type="radio" name="mod_content_type" value="<?= $key ?>"
                   <?= $key === 'pdf' ? 'checked' : '' ?>
                   onchange="selectContentType('<?= $key ?>')">
            <div class="ct-icon"><?= $ct['icon'] ?></div>
            <div class="ct-label"><?= $ct['label'] ?></div>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Title -->
      <div style="margin-bottom:1rem;">
        <label style="display:block;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:.425rem;">Module Title <span style="color:#dc2626;">*</span></label>
        <input type="text" id="modTitle" class="ef-input" placeholder="e.g. Introduction to Hand Hygiene">
      </div>

      <!-- Description -->
      <div style="margin-bottom:1rem;">
        <label style="display:block;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:.425rem;">Description</label>
        <textarea id="modDesc" class="ef-textarea" rows="2" placeholder="Brief description of this module…"></textarea>
      </div>

      <!-- Content path + weight -->
      <div class="ef-row">
        <div>
          <label style="display:block;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:.425rem;">Content URL / Path</label>
          <input type="text" id="modPath" class="ef-input" placeholder="https://… or /uploads/file.pdf">
        </div>
        <div>
          <label style="display:block;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:.425rem;">Weight (%)</label>
          <input type="number" id="modWeight" class="ef-input" min="0" max="100" step="0.01" placeholder="0">
          <div style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin-top:3px;">Contribution to course grade</div>
        </div>
      </div>
    </div>
    <div class="mod-modal-footer">
      <button type="button" class="mod-cancel" onclick="closeModModal()">Cancel</button>
      <button type="button" class="mod-save" onclick="saveModule()">
        <span id="modSaveText">Add Module</span>
      </button>
    </div>
  </div>
</div>

<script>
var COURSE_ID  = <?= $course->id ?>;
var BASE_URL   = '<?= base_url() ?>';
var CSRF_NAME  = '<?= $this->security->get_csrf_token_name() ?>';
var CSRF_HASH  = '<?= $this->security->get_csrf_hash() ?>';
var CT_DATA    = <?= json_encode($content_types) ?>;

// ── Content type picker ─────────────────────────────────────
function selectContentType(key) {
  document.querySelectorAll('.ct-card').forEach(function(c) { c.classList.remove('selected'); });
  document.getElementById('ct-' + key)?.classList.add('selected');
}

// ── Modal ────────────────────────────────────────────────────
function openModModal(id) {
  document.getElementById('modModalOverlay').classList.add('open');
  document.getElementById('modId').value            = id || 0;
  document.getElementById('modModalTitle').textContent = id ? 'Edit Module' : 'Add Module';
  document.getElementById('modSaveText').textContent   = id ? 'Update Module' : 'Add Module';
  if ( ! id) {
    document.getElementById('modTitle').value  = '';
    document.getElementById('modDesc').value   = '';
    document.getElementById('modPath').value   = '';
    document.getElementById('modWeight').value = '';
    selectContentType('pdf');
  }
}

function editModule(id) {
  var el = document.getElementById('moditem-' + id);
  if ( ! el) return;
  document.getElementById('modTitle').value  = el.dataset.title;
  document.getElementById('modDesc').value   = el.dataset.desc;
  document.getElementById('modPath').value   = el.dataset.path;
  document.getElementById('modWeight').value = el.dataset.weight;
  selectContentType(el.dataset.type);
  // Set radio
  var radio = document.querySelector('input[name="mod_content_type"][value="' + el.dataset.type + '"]');
  if (radio) radio.checked = true;
  openModModal(id);
}

function closeModModal() {
  document.getElementById('modModalOverlay').classList.remove('open');
}
function closeModModalOutside(e) {
  if (e.target === document.getElementById('modModalOverlay')) closeModModal();
}

// ── Save module (AJAX) ───────────────────────────────────────
function saveModule() {
  var id    = parseInt(document.getElementById('modId').value) || 0;
  var title = document.getElementById('modTitle').value.trim();
  var type  = document.querySelector('input[name="mod_content_type"]:checked')?.value || 'pdf';

  if ( ! title) { KA.toast('error', 'Module title is required.'); return; }

  var btn = document.querySelector('.mod-save');
  btn.disabled = true;
  document.getElementById('modSaveText').textContent = 'Saving…';

  var body = CSRF_NAME  + '=' + CSRF_HASH
    + '&course_id='         + COURSE_ID
    + '&module_id='         + id
    + '&title='             + encodeURIComponent(title)
    + '&description='       + encodeURIComponent(document.getElementById('modDesc').value)
    + '&content_type='      + type
    + '&content_path='      + encodeURIComponent(document.getElementById('modPath').value)
    + '&weight_percentage=' + (parseFloat(document.getElementById('modWeight').value) || 0);

  fetch(BASE_URL + 'manage_courses/save_module', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body,
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    btn.disabled = false;
    document.getElementById('modSaveText').textContent = id ? 'Update Module' : 'Add Module';
    if (data.success) {
      closeModModal();
      KA.toast('success', data.message);
      renderModule(data.module, id === 0);
    } else {
      KA.toast('error', data.message || 'Failed to save module.');
    }
  })
  .catch(function() {
    btn.disabled = false;
    document.getElementById('modSaveText').textContent = id ? 'Update Module' : 'Add Module';
    KA.toast('error', 'Network error. Please try again.');
  });
}

// ── Render module in DOM ─────────────────────────────────────
function renderModule(m, isNew) {
  var list  = document.getElementById('modList');
  var empty = document.getElementById('modEmpty');
  if (empty) empty.remove();

  var ct    = CT_DATA[m.content_type] || { icon:'📁', label: m.content_type };
  var count = list.querySelectorAll('.mod-item').length;
  var order = isNew ? count + 1 : null;

  var html = '<div class="mod-item" id="moditem-' + m.id + '"'
    + ' draggable="true"'
    + ' data-id="'     + m.id + '"'
    + ' data-title="'  + escAttr(m.title) + '"'
    + ' data-desc="'   + escAttr(m.description || '') + '"'
    + ' data-type="'   + m.content_type + '"'
    + ' data-path="'   + escAttr(m.content_path || '') + '"'
    + ' data-weight="' + (m.weight_percentage || 0) + '">'
    + '<div class="mod-drag-handle"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="6" r="1" fill="currentColor"/><circle cx="15" cy="6" r="1" fill="currentColor"/><circle cx="9" cy="12" r="1" fill="currentColor"/><circle cx="15" cy="12" r="1" fill="currentColor"/><circle cx="9" cy="18" r="1" fill="currentColor"/><circle cx="15" cy="18" r="1" fill="currentColor"/></svg></div>'
    + '<div class="mod-order" id="modorder-' + m.id + '">' + (order || '?') + '</div>'
    + '<div class="mod-type-icon">' + ct.icon + '</div>'
    + '<div class="mod-body">'
    + '<div class="mod-title">' + escHtml(m.title) + '</div>'
    + '<div class="mod-meta">' + ct.label + (m.weight_percentage > 0 ? ' · ' + m.weight_percentage + '% weight' : '') + '</div>'
    + '<div class="mod-asx-badges">'
    + '<a href="' + BASE_URL + 'assessments/create?module_id=' + m.id + '&type=pre" class="mod-asx-badge add">+ Pre-assessment</a>'
    + '<a href="' + BASE_URL + 'assessments/create?module_id=' + m.id + '&type=post" class="mod-asx-badge add">+ Post-assessment</a>'
    + '</div>'
    + '</div>'
    + '<div class="mod-actions">'
    + '<button type="button" class="mod-action-btn" onclick="editModule(' + m.id + ')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>'
    + '<button type="button" class="mod-action-btn danger" onclick="deleteModule(' + m.id + ')"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>'
    + '</div></div>';

  var existing = document.getElementById('moditem-' + m.id);
  if (existing) {
    existing.outerHTML = html;
  } else {
    list.insertAdjacentHTML('beforeend', html);
    initDrag(document.getElementById('moditem-' + m.id));
  }
  renumberModules();
}

// ── Delete module ────────────────────────────────────────────
function deleteModule(id) {
  KA.confirm({
    title: 'Delete this module?',
    text:  'All student progress for this module will also be removed.',
    confirmText: 'Yes, delete',
    type: 'danger',
    onConfirm: function() {
      fetch(BASE_URL + 'manage_courses/delete_module', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: CSRF_NAME + '=' + CSRF_HASH + '&module_id=' + id,
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          document.getElementById('moditem-' + id)?.remove();
          KA.toast('success', 'Module deleted.');
          renumberModules();
          if (document.querySelectorAll('.mod-item').length === 0) {
            document.getElementById('modList').insertAdjacentHTML('beforeend',
              '<div id="modEmpty" style="text-align:center;padding:2rem;color:var(--ka-text-muted,#64748b);font-size:.875rem;">No modules yet.</div>'
            );
          }
        } else {
          KA.toast('error', data.message || 'Failed to delete.');
        }
      });
    },
  });
}

// ── Renumber & count ─────────────────────────────────────────
function renumberModules() {
  var items = document.querySelectorAll('.mod-item');
  items.forEach(function(el, i) {
    var num = el.querySelector('.mod-order');
    if (num) num.textContent = i + 1;
  });
  var n = items.length;
  document.getElementById('modCountLabel').textContent = n + ' module' + (n !== 1 ? 's' : '');
  document.getElementById('sideModCount').textContent  = n;
}

// ── Drag & drop reorder ──────────────────────────────────────
var dragSrc = null;

function initDrag(el) {
  el.addEventListener('dragstart', function(e) {
    dragSrc = el;
    el.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
  });
  el.addEventListener('dragend', function() {
    el.classList.remove('dragging');
    document.querySelectorAll('.mod-item').forEach(function(i) { i.classList.remove('drag-over'); });
    saveOrder();
  });
  el.addEventListener('dragover', function(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    if (el !== dragSrc) el.classList.add('drag-over');
  });
  el.addEventListener('dragleave', function() { el.classList.remove('drag-over'); });
  el.addEventListener('drop', function(e) {
    e.preventDefault();
    if (dragSrc && dragSrc !== el) {
      var list = document.getElementById('modList');
      var items = Array.from(list.querySelectorAll('.mod-item'));
      var srcIdx = items.indexOf(dragSrc);
      var tgtIdx = items.indexOf(el);
      if (srcIdx < tgtIdx) { list.insertBefore(dragSrc, el.nextSibling); }
      else                  { list.insertBefore(dragSrc, el); }
      renumberModules();
    }
  });
}

// Init drag on existing items
document.querySelectorAll('.mod-item').forEach(initDrag);

function saveOrder() {
  var ids = Array.from(document.querySelectorAll('.mod-item')).map(function(el) { return el.dataset.id; });
  fetch(BASE_URL + 'manage_courses/reorder_modules', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: CSRF_NAME + '=' + CSRF_HASH + '&' + ids.map(function(id, i) { return 'ids[' + i + ']=' + id; }).join('&'),
  }).catch(function() {});
}

function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s) { return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModModal(); });
</script>