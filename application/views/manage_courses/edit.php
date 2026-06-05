<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$course       = $course       ?? null;
$modules      = is_array($modules ?? null) ? $modules : [];
$categories   = $categories   ?? [];
$modalities   = $modalities   ?? [];
$teachers     = $teachers     ?? [];
$csrf_field_name = $csrf_field_name ?? '';
$csrf_hash       = $csrf_hash ?? '';
$module_count = count($modules);
/** @var array<int, object> $module_rows */
$module_rows  = $modules;
$focus_modules = ! empty($focus_modules);
$checkpoint_schema_ready = ! empty($checkpoint_schema_ready);
$user_role    = strtolower($user->role ?? 'teacher');
$is_admin     = $user_role === 'admin';
if ( ! $course) return;
$phase2 = $course_phase2 ?? null;
$hrmis_connection_ok = ! empty($hrmis_connection_ok);
$hrmis_ready = ! empty($hrmis_ready);
$departments = is_array($departments ?? null) ? $departments : [];
$edit_category_ids = $phase2 ? (array) $phase2->category_ids : [];
if (empty($edit_category_ids) && ! empty($course->category_id)) {
    $edit_category_ids = [(int) $course->category_id];
}
$edit_publish_status = $phase2 ? (string) $phase2->publish_status : (string) ($course->publish_status ?? 'published');

$content_types = [
    'pdf'           => ['label' => 'PDF Document',  'icon' => '📄', 'color' => '#ef4444'],
    'slides'        => ['label' => 'Slides',        'icon' => '📊', 'color' => '#3b82f6'],
    'video'         => ['label' => 'Video',         'icon' => '🎬', 'color' => '#8b5cf6'],
    'audio'         => ['label' => 'Audio',         'icon' => '🎧', 'color' => '#f59f00'],
    'meeting_link'  => ['label' => 'Meeting Link',  'icon' => '🎥', 'color' => '#06b6d4'],
];
/** Content types shown but not selectable until feature ships */
$coming_soon_types = ['slides', 'audio'];
?>
<?php echo $alerts_partial_html ?? ''; ?>
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
.mod-asx-badge.cp   { background:#fff7ed;color:#9a3412;border:1px solid #fed7aa; }
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
.ct-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(88px,1fr));gap:.5rem;margin-bottom:1.125rem; }
.ct-card { border:2px solid var(--ka-border,#e2e8f0);border-radius:10px;padding:.625rem .375rem;text-align:center;cursor:pointer;transition:all .15s; }
.ct-card input { display:none; }
.ct-card.is-hidden { display:none !important; }
.mod-weight-budget { margin-bottom:1rem;padding:.625rem .75rem;border:1px solid var(--ka-border,#e2e8f0);border-radius:10px;font-size:.8125rem;color:var(--ka-text-muted,#64748b);background:#f8fafc;display:flex;flex-wrap:wrap;gap:.35rem 1rem; }
.mod-weight-budget strong { color:var(--ka-text,#1e293b); }
.mod-save:disabled { opacity:.55;cursor:not-allowed; }
.ct-card .ct-icon  { font-size:1.375rem;margin-bottom:3px;line-height:1; }
.ct-card .ct-label { font-size:.5625rem;font-weight:700;color:var(--ka-text-muted,#64748b);line-height:1.2; }
.ct-card.selected  { border-color:var(--ka-navy,#1a3a5c);background:var(--ka-accent,#e8f4fd); }
.ct-card.selected .ct-label { color:var(--ka-navy,#1a3a5c); }
.ct-card.is-coming-soon { padding:0;overflow:hidden;display:flex;flex-direction:column;align-items:stretch; }
.ct-card.is-coming-soon .ct-soon-badge { position:static;display:block;width:100%;box-sizing:border-box;padding:3px 2px;font-size:.4375rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;text-align:center;color:#92400e;background:linear-gradient(180deg,#fef9c3,#fde68a);border:none;border-bottom:1px solid #fcd34d;line-height:1.25;flex-shrink:0; }
.ct-card.is-coming-soon .ct-card-body { padding:.45rem .3rem .55rem;text-align:center;flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;min-height:0; }
.ct-card.is-disabled[aria-disabled="true"] { opacity:.58;cursor:not-allowed;pointer-events:none;filter:grayscale(.35);background:#f8fafc;border-color:#e2e8f0;box-shadow:none;transform:none; }
.ct-card.is-disabled[aria-disabled="true"]:hover { border-color:#e2e8f0;background:#f8fafc; }
.ct-card.is-disabled[aria-disabled="true"] .ct-icon { opacity:.8; }

/* PDF upload — SaaS action row */
.mod-pdf-upload { display:none;margin-top:.75rem; }
.mod-pdf-upload.is-visible { display:block; }
.mod-pdf-dropzone { position:relative;border:2px dashed var(--ka-border,#e2e8f0);border-radius:10px;background:var(--ka-bg,#f8fafc);transition:border-color .18s,background .18s,box-shadow .18s;margin-bottom:.5rem; }
.mod-pdf-dropzone.is-dragover { border-color:var(--ka-primary,#6dabcf);background:var(--ka-accent,#e8f4fd);box-shadow:0 0 0 3px rgba(109,171,207,.12); }
.mod-pdf-file-input { position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;z-index:2; }
.mod-pdf-dropzone-inner { display:flex;flex-direction:column;align-items:center;gap:.25rem;padding:.875rem 1rem;text-align:center;pointer-events:none; }
.mod-pdf-dropzone-icon { width:28px;height:28px;color:var(--ka-primary,#6dabcf);opacity:.9; }
.mod-pdf-dropzone-title { font-size:.75rem;font-weight:600;color:var(--ka-text-muted,#64748b); }
.mod-pdf-filename { font-size:.6875rem;font-weight:700;color:var(--ka-navy,#1a3a5c);max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-height:1em; }
.mod-upload-btn { display:inline-flex;align-items:center;justify-content:center;gap:.4rem;padding:.5rem 1rem;border-radius:10px;border:none;background:var(--ka-navy,#1a3a5c);color:#fff;font-size:.8125rem;font-weight:700;cursor:pointer;font-family:inherit;transition:transform .15s,box-shadow .15s,background .15s;box-shadow:0 2px 8px rgba(26,58,92,.22); }
.mod-upload-btn:hover:not(:disabled) { background:#254d75;transform:translateY(-1px);box-shadow:0 4px 12px rgba(26,58,92,.28); }
.mod-upload-btn:active:not(:disabled) { transform:translateY(0); }
.mod-upload-btn:disabled,.mod-upload-btn.is-loading { opacity:.65;cursor:wait;transform:none; }
.mod-upload-btn svg { width:16px;height:16px;flex-shrink:0; }
.mod-upload-btn.is-loading .mod-upload-btn-text { opacity:.85; }
.mod-pdf-msg { display:block;font-size:.72rem;color:var(--ka-text-muted,#64748b);margin-top:.35rem;min-height:1.1em; }
.mod-pdf-msg.is-success { color:#15803d;font-weight:600; }
.mod-pdf-msg.is-error { color:#dc2626;font-weight:600; }

/* Sidebar panels */
.side-info-item { display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid var(--ka-border,#e2e8f0);font-size:.8125rem; }
.side-info-item:last-child { border-bottom:none; }
.side-info-label { color:var(--ka-text-muted,#64748b);font-weight:500; }
.side-info-value { font-weight:700;color:var(--ka-text,#1e293b); }

/* Course edit — tabbed workspace (progressive disclosure) */
.crs-edit-toolbar { display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:1rem;align-items:center;border-bottom:1px solid var(--ka-border,#e2e8f0);padding-bottom:.75rem; }
.crs-edit-tab { display:inline-flex;align-items:center;gap:.35rem;padding:.45rem .7rem;border-radius:8px;border:1.5px solid transparent;background:transparent;font-size:.75rem;font-weight:600;color:var(--ka-text-muted,#64748b);cursor:pointer;font-family:inherit;transition:all .15s; }
.crs-edit-tab:hover { color:var(--ka-text,#1e293b);background:var(--ka-bg,#f8fafc); }
.crs-edit-tab.is-active { color:var(--ka-navy,#1a3a5c);background:var(--ka-accent,#e8f4fd);border-color:var(--ka-primary,#6dabcf); }
.crs-edit-tab .crs-edit-tab-badge { font-size:.5625rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:2px 6px;border-radius:999px;background:rgba(100,116,139,.12);color:var(--ka-text-muted,#64748b); }
.crs-edit-tab-progress { margin-left:auto;font-size:.6875rem;color:var(--ka-text-muted,#64748b);font-weight:500; }
.crs-edit-tab-panels { min-height:200px; }
.crs-edit-panel { display:none;margin-bottom:1rem; }
.crs-edit-panel.is-active { display:block; }
.crs-edit-sticky-save { position:sticky;bottom:0;z-index:50;margin:1rem -0.25rem 0;padding:.875rem 1rem;background:rgba(255,255,255,.92);backdrop-filter:blur(8px);border:1px solid var(--ka-border,#e2e8f0);border-radius:12px;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;box-shadow:0 -4px 24px rgba(15,23,42,.06); }
.crs-edit-sticky-save p { margin:0;font-size:.75rem;color:var(--ka-text-muted,#64748b);flex:1;min-width:140px; }
.crs-edit-workspace-title { font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--ka-text-muted,#64748b);margin:0 0 .5rem; }
</style>

<!-- Page header -->
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;" class="animate__animated animate__fadeIn animate__fast">
  <div>
    <h2 style="font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 3px;letter-spacing:-.02em;">
      Course workspace
    </h2>
    <p style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0;">
      <?= htmlspecialchars($course->title) ?>
      &nbsp;·&nbsp;
      <span style="font-weight:600;color:var(--ka-text-muted,#64748b);"><?= htmlspecialchars(ucfirst($edit_publish_status)) ?></span>
      &nbsp;·&nbsp;
      <span style="color:<?= $course->archived ? '#64748b' : '#22c55e' ?>;font-weight:600;">
        <?= $course->archived ? 'Archived' : 'Active' ?>
      </span>
    </p>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
    <a href="<?= base_url('courses/view/'.$course->id.'?'.ka_lms_return_q('manage_courses')) ?>" target="_blank"
       style="display:inline-flex;align-items:center;gap:5px;padding:.5rem .875rem;border-radius:8px;font-size:.8125rem;font-weight:600;text-decoration:none;border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;color:var(--ka-text,#1e293b);">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      Preview
    </a>
    <a href="<?= htmlspecialchars($lms_edit_back_href ?? base_url('manage_courses'), ENT_QUOTES, 'UTF-8') ?>"
       style="display:inline-flex;align-items:center;gap:5px;padding:.5rem .875rem;border-radius:8px;font-size:.8125rem;font-weight:600;text-decoration:none;border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;color:var(--ka-text,#1e293b);">
      ← Back
    </a>
  </div>
</div>

<div class="edit-crs-layout ka-form-flow animate__animated animate__fadeInUp animate__fast">

  <!-- ── LEFT COLUMN ── -->
  <div>
    <?php $this->load->view('manage_courses/edit_course_workspace', get_defined_vars()); ?>
  </div>

  <!-- ── RIGHT SIDEBAR ── -->
  <div style="position:sticky;top:80px;">

    <!-- Course info -->
    <div class="edit-crs-panel" style="margin-bottom:1rem;">
      <div class="edit-crs-hdr"><h3 class="edit-crs-title">Course Info</h3></div>
      <div class="edit-crs-body" style="padding-top:.25rem;padding-bottom:.25rem;">
        <div class="side-info-item">
          <span class="side-info-label">Modules</span>
          <span class="side-info-value" id="sideModCount"><?= $module_count ?></span>
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
          <span class="side-info-label">Publish</span>
          <span class="side-info-value" style="font-size:.75rem;"><?= htmlspecialchars(ucfirst($edit_publish_status)) ?></span>
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

    <div class="edit-crs-panel" style="margin-bottom:1rem;">
      <div class="edit-crs-hdr"><h3 class="edit-crs-title">Publish Workflow</h3></div>
      <div class="edit-crs-body">
        <p style="font-size:.75rem;color:var(--ka-text-muted,#64748b);margin:0 0 .875rem;">
          Published courses are visible to eligible learners. Draft and unpublished courses stay hidden.
        </p>
        <?php if ($edit_publish_status === 'published'): ?>
        <button type="button"
                onclick="KA.unpublishConfirm('<?= base_url('manage_courses/unpublish/'.$course->id) ?>', '<?= htmlspecialchars(addslashes($course->title), ENT_QUOTES) ?>')"
                style="width:100%;padding:.5rem;border-radius:8px;background:#92400e;color:#fff;border:none;cursor:pointer;font-size:.8125rem;font-weight:700;">
          Unpublish Course
        </button>
        <?php else: ?>
        <button type="button"
                onclick="KA.publishConfirm('<?= base_url('manage_courses/publish/'.$course->id) ?>', '<?= htmlspecialchars(addslashes($course->title), ENT_QUOTES) ?>')"
                style="width:100%;padding:.5rem;border-radius:8px;background:var(--ka-navy,#1a3a5c);color:#fff;border:none;cursor:pointer;font-size:.8125rem;font-weight:700;">
          Publish Course
        </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Admin: reassign -->
    <?php if ($is_admin && ! empty($teachers)): ?>
    <div class="edit-crs-panel" style="margin-bottom:1rem;">
      <div class="edit-crs-hdr"><h3 class="edit-crs-title">Reassign Course</h3></div>
      <div class="edit-crs-body">
        <form method="post" action="<?= base_url('manage_courses/reassign') ?>">
          <input type="hidden" name="<?= $csrf_field_name ?>" value="<?= $csrf_hash ?>">
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

      <div id="modWeightBudget" class="mod-weight-budget">
        <span>Current total: <strong id="modCurrentTotal">0%</strong></span>
        <span>Remaining weight: <strong id="modRemainingWeight">100%</strong></span>
      </div>

      <!-- Content type picker (duplicate types allowed; weight is the only limit) -->
      <div style="margin-bottom:1.125rem;">
        <label style="display:block;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:.625rem;">Content Type <span style="color:#dc2626;">*</span></label>
        <div class="ct-grid" id="modContentTypeGrid">
          <?php foreach ($content_types as $key => $ct):
            $is_coming_soon = in_array($key, $coming_soon_types, true);
          ?>
          <label class="ct-card <?= ($key === 'pdf' && ! $is_coming_soon) ? 'selected' : '' ?><?= $is_coming_soon ? ' is-disabled is-coming-soon' : '' ?>"
                 id="ct-<?= $key ?>"
                 data-content-type="<?= htmlspecialchars($key, ENT_QUOTES) ?>"
                 data-coming-soon="<?= $is_coming_soon ? '1' : '0' ?>"
                 aria-disabled="<?= $is_coming_soon ? 'true' : 'false' ?>"
                 title="<?= $is_coming_soon ? 'Feature currently in development' : '' ?>">
            <input type="radio" name="mod_content_type" value="<?= $key ?>"
                   <?= ($key === 'pdf' && ! $is_coming_soon) ? 'checked' : '' ?>
                   <?= $is_coming_soon ? 'disabled' : '' ?>
                   onchange="selectContentType('<?= $key ?>')">
            <?php if ($is_coming_soon): ?>
            <span class="ct-soon-badge" title="Coming soon — feature in development">Coming soon</span>
            <?php endif; ?>
            <div class="ct-card-body">
              <div class="ct-icon"><?= $ct['icon'] ?></div>
              <div class="ct-label"><?= $ct['label'] ?></div>
            </div>
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
          <div id="modPdfUpload" class="mod-pdf-upload" aria-live="polite">
            <div class="mod-pdf-dropzone" id="modPdfDropzone">
              <input type="file" id="modPdfFile" class="mod-pdf-file-input" accept="application/pdf,.pdf" aria-label="Choose PDF file">
              <div class="mod-pdf-dropzone-inner">
                <svg class="mod-pdf-dropzone-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 12 15 15"/></svg>
                <span class="mod-pdf-dropzone-title">Drop PDF here or browse</span>
                <span id="modPdfFileName" class="mod-pdf-filename"></span>
              </div>
            </div>
            <button type="button" class="mod-upload-btn" id="modPdfUploadBtn">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              <span class="mod-upload-btn-text">Upload learning material</span>
            </button>
            <span id="modPdfUploadMsg" class="mod-pdf-msg" role="status"></span>
          </div>
        </div>
        <div>
          <label style="display:block;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:.425rem;">Weight (%)</label>
          <input type="number" id="modWeight" class="ef-input" min="0" max="100" step="0.01" placeholder="0">
          <div id="modWeightHint" style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin-top:3px;">You can add any module type. Total weight cannot exceed 100%.</div>
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
var BASE_URL   = '<?= base_url('index.php/') ?>';
var CSRF_NAME  = '<?= $csrf_field_name ?>';
var CSRF_HASH  = '<?= $csrf_hash ?>';
var CT_DATA    = <?= json_encode($content_types) ?>;
var CHECKPOINT_SCHEMA_READY = <?= $checkpoint_schema_ready ? 'true' : 'false' ?>;
/** Shown in picker but not selectable for new modules */
var COMING_SOON_TYPES = ['slides', 'audio'];

function isComingSoonType(key) {
  return COMING_SOON_TYPES.indexOf(String(key || '').toLowerCase()) !== -1;
}
var COMING_SOON_TYPES = <?= json_encode($coming_soon_types) ?>;

function isComingSoonType(key) {
  return COMING_SOON_TYPES.indexOf(String(key || '').toLowerCase()) !== -1;
}

/** Video checkpoints: contract uses effective type only (same as PHP course_phase3_effective_module_type). */
function isVideoModuleContent(effType) {
  return String(effType || '').toLowerCase().trim() === 'video';
}

function formatPct(value) {
  var n = Math.round(parseFloat(value) * 100) / 100;
  if (isNaN(n)) n = 0;
  return n.toFixed(2).replace(/\.00$/, '') + '%';
}

function getModuleWeightTotal(excludeId, draftWeight) {
  var total = 0;
  document.querySelectorAll('.mod-item').forEach(function(el) {
    var id = parseInt(el.dataset.id, 10) || 0;
    if (excludeId && id === excludeId) return;
    total += parseFloat(el.dataset.weight || '0') || 0;
  });
  if (typeof draftWeight === 'number' && !isNaN(draftWeight)) total += draftWeight;
  return Math.round(total * 100) / 100;
}

function updateModuleWeightSummary() {
  var total = getModuleWeightTotal();
  var totalEl = document.getElementById('modWeightTotal');
  var remainingEl = document.getElementById('modWeightRemaining');
  var statusEl = document.getElementById('modWeightStatus');
  if (!totalEl || !statusEl) return;

  totalEl.textContent = formatPct(total);
  if (remainingEl) {
    remainingEl.textContent = formatPct(Math.max(0, 100 - total));
  }
  if (total === 100) {
    statusEl.textContent = 'Ready.';
    statusEl.style.color = '#15803d';
  } else if (total > 100) {
    statusEl.textContent = 'Exceeds 100% by ' + (total - 100).toFixed(2).replace(/\.00$/, '') + '%.';
    statusEl.style.color = '#dc2626';
  } else {
    statusEl.textContent = (100 - total).toFixed(2).replace(/\.00$/, '') + '% remaining.';
    statusEl.style.color = '#b45309';
  }
}

function updateDraftWeightHint() {
  var id = parseInt(document.getElementById('modId').value, 10) || 0;
  var weight = parseFloat(document.getElementById('modWeight').value || '0') || 0;
  var existing = getModuleWeightTotal(id);
  var total = getModuleWeightTotal(id, weight);
  var remaining = Math.max(0, Math.round((100 - existing) * 100) / 100);
  var hint = document.getElementById('modWeightHint');
  var currentEl = document.getElementById('modCurrentTotal');
  var remainingEl = document.getElementById('modRemainingWeight');
  var saveBtn = document.querySelector('.mod-save');

  if (currentEl) currentEl.textContent = formatPct(existing);
  if (remainingEl) remainingEl.textContent = formatPct(remaining);

  if (hint) {
    if (total > 100) {
      hint.textContent = 'This save would exceed 100% by ' + formatPct(total - 100) + '.';
      hint.style.color = '#dc2626';
    } else if (total === 100) {
      hint.textContent = 'This save brings the module weights to exactly 100%.';
      hint.style.color = '#15803d';
    } else {
      hint.textContent = 'This save leaves ' + formatPct(100 - total) + ' remaining.';
      hint.style.color = '#b45309';
    }
  }

  if (saveBtn) {
    saveBtn.disabled = total > 100;
  }
}

/** All content types stay visible — never hide types; coming-soon types stay disabled unless editing that type. */
function refreshContentTypePicker() {
  var modId = parseInt(document.getElementById('modId').value, 10) || 0;
  var existingType = '';
  if (modId) {
    var row = document.getElementById('moditem-' + modId);
    if (row) existingType = String(row.dataset.type || '').toLowerCase();
  }
  document.querySelectorAll('#modContentTypeGrid .ct-card').forEach(function(card) {
    card.classList.remove('is-hidden');
    card.style.display = '';
    var key = String(card.getAttribute('data-content-type') || '').toLowerCase();
    var comingSoon = card.getAttribute('data-coming-soon') === '1';
    var input = card.querySelector('input[name="mod_content_type"]');
    if (comingSoon) {
      card.classList.add('is-disabled', 'is-coming-soon');
      var allowExisting = modId > 0 && existingType === key;
      if (input) input.disabled = !allowExisting;
      card.setAttribute('aria-disabled', allowExisting ? 'false' : 'true');
    } else {
      card.classList.remove('is-disabled', 'is-coming-soon');
      card.setAttribute('aria-disabled', 'false');
      if (input) input.disabled = false;
    }
  });
}

function setModPdfMsg(text, kind) {
  var msg = document.getElementById('modPdfUploadMsg');
  if (!msg) return;
  msg.textContent = text || '';
  msg.classList.remove('is-success', 'is-error');
  if (kind === 'success') msg.classList.add('is-success');
  if (kind === 'error') msg.classList.add('is-error');
}

function updateModPdfFileName() {
  var fileInput = document.getElementById('modPdfFile');
  var nameEl = document.getElementById('modPdfFileName');
  if (!nameEl || !fileInput) return;
  var file = fileInput.files && fileInput.files[0];
  nameEl.textContent = file ? file.name : '';
}

function resetModPdfUploadUi() {
  var fileInput = document.getElementById('modPdfFile');
  var btn = document.getElementById('modPdfUploadBtn');
  if (fileInput) fileInput.value = '';
  updateModPdfFileName();
  setModPdfMsg('', '');
  if (btn) {
    btn.classList.remove('is-loading');
    btn.disabled = false;
  }
}

// ── Content type picker ─────────────────────────────────────
function selectContentType(key, force) {
  key = String(key || '').toLowerCase();
  refreshContentTypePicker();
  if (!force && isComingSoonType(key)) {
    var blocked = document.querySelector('input[name="mod_content_type"][value="' + key + '"]');
    if (!blocked || blocked.disabled) return;
  }
  document.querySelectorAll('.ct-card').forEach(function(c) { c.classList.remove('selected'); });
  document.getElementById('ct-' + key)?.classList.add('selected');
  var radio = document.querySelector('input[name="mod_content_type"][value="' + key + '"]');
  if (radio && !radio.disabled) radio.checked = true;
  toggleModPdfUpload();
}

function toggleModPdfUpload() {
  var type = document.querySelector('input[name="mod_content_type"]:checked')?.value || '';
  var box = document.getElementById('modPdfUpload');
  if (box) box.classList.toggle('is-visible', type === 'pdf');
}

function uploadModulePdf() {
  var fileInput = document.getElementById('modPdfFile');
  var msg = document.getElementById('modPdfUploadMsg');
  var btn = document.getElementById('modPdfUploadBtn');
  if (!fileInput || !fileInput.files || !fileInput.files[0]) {
    setModPdfMsg('Choose a PDF file first.', 'error');
    return;
  }
  var fd = new FormData();
  fd.append('module_file', fileInput.files[0]);
  fd.append('course_id', COURSE_ID);
  fd.append(CSRF_NAME, CSRF_HASH);
  setModPdfMsg('Uploading…', '');
  if (btn) {
    btn.classList.add('is-loading');
    btn.disabled = true;
  }
  fetch(BASE_URL + 'manage_courses/upload_module_file', { method: 'POST', body: fd, credentials: 'same-origin' })
    .then(parseJsonResponse)
    .then(function(res) {
      if (res.success && res.content_path) {
        document.getElementById('modPath').value = res.content_path;
        setModPdfMsg('PDF uploaded successfully.', 'success');
      } else {
        setModPdfMsg(res.message || 'Upload failed.', 'error');
      }
    })
    .catch(function() {
      setModPdfMsg('Upload failed.', 'error');
    })
    .finally(function() {
      if (btn) {
        btn.classList.remove('is-loading');
        btn.disabled = false;
      }
    });
}

document.getElementById('modPdfUploadBtn')?.addEventListener('click', uploadModulePdf);
document.getElementById('modPdfFile')?.addEventListener('change', updateModPdfFileName);

(function initModPdfDropzone() {
  var zone = document.getElementById('modPdfDropzone');
  var fileInput = document.getElementById('modPdfFile');
  if (!zone || !fileInput) return;
  ['dragenter', 'dragover'].forEach(function(ev) {
    zone.addEventListener(ev, function(e) {
      e.preventDefault();
      e.stopPropagation();
      zone.classList.add('is-dragover');
    });
  });
  ['dragleave', 'drop'].forEach(function(ev) {
    zone.addEventListener(ev, function(e) {
      e.preventDefault();
      e.stopPropagation();
      zone.classList.remove('is-dragover');
    });
  });
  zone.addEventListener('drop', function(e) {
    var files = e.dataTransfer && e.dataTransfer.files;
    if (!files || !files.length) return;
    try {
      fileInput.files = files;
    } catch (err) {
      return;
    }
    updateModPdfFileName();
  });
})();

document.getElementById('modContentTypeGrid')?.addEventListener('click', function(e) {
  var card = e.target.closest('.ct-card');
  if (!card || card.getAttribute('data-coming-soon') !== '1') return;
  if (card.getAttribute('aria-disabled') === 'true') {
    e.preventDefault();
    e.stopPropagation();
  }
});

// ── Modal ────────────────────────────────────────────────────
function openModModal(id) {
  document.getElementById('modModalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
  document.getElementById('modId').value            = id || 0;
  document.getElementById('modModalTitle').textContent = id ? 'Edit Module' : 'Add Module';
  document.getElementById('modSaveText').textContent   = id ? 'Update Module' : 'Add Module';
  refreshContentTypePicker();
  if ( ! id) {
    document.getElementById('modTitle').value  = '';
    document.getElementById('modDesc').value   = '';
    document.getElementById('modPath').value   = '';
    document.getElementById('modWeight').value = '';
    resetModPdfUploadUi();
    selectContentType('pdf', true);
  }
  updateDraftWeightHint();
}

function editModule(id) {
  var el = document.getElementById('moditem-' + id);
  if ( ! el) return;
  openModModal(id);
  document.getElementById('modTitle').value  = el.dataset.title;
  document.getElementById('modDesc').value   = el.dataset.desc;
  document.getElementById('modPath').value   = el.dataset.path;
  document.getElementById('modWeight').value = el.dataset.weight;
  resetModPdfUploadUi();
  // data-type is server-computed effective type (modal preselect only; not a type resolver).
  var modType = String(el.dataset.type || 'pdf').toLowerCase();
  selectContentType(modType || 'pdf', true);
  var radio = document.querySelector('input[name="mod_content_type"][value="' + modType + '"]');
  if (radio && !radio.disabled) radio.checked = true;
  updateDraftWeightHint();
}

function closeModModal() {
  document.getElementById('modModalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}
function closeModModalOutside(e) {
  if (e.target === document.getElementById('modModalOverlay')) closeModModal();
}

function parseJsonResponse(r) {
  return r.text().then(function(text) {
    try {
      return JSON.parse(text);
    } catch (e) {
      return {
        success: false,
        message: r.status === 401 ? 'Session expired. Please log in again.' : 'Unexpected server response.',
      };
    }
  });
}

// ── Save module (AJAX) ───────────────────────────────────────
function saveModule() {
  var id    = parseInt(document.getElementById('modId').value) || 0;
  var title = document.getElementById('modTitle').value.trim();
  var type  = document.querySelector('input[name="mod_content_type"]:checked')?.value || 'pdf';

  if ( ! title) { KA.toast('error', 'Module title is required.'); return; }

  var weight = parseFloat(document.getElementById('modWeight').value || '0') || 0;
  if (weight < 0 || weight > 100) {
    KA.toast('error', 'Module weight must be between 0 and 100%.');
    return;
  }
  var projected = getModuleWeightTotal(id, weight);
  if (projected > 100) {
    KA.toast('error', 'Module weights cannot exceed 100%. Current save would total ' + formatPct(projected) + '.');
    updateDraftWeightHint();
    return;
  }

  var btn = document.querySelector('.mod-save');
  btn.disabled = true;
  document.getElementById('modSaveText').textContent = 'Saving…';

  var body = '';
  if (CSRF_NAME) {
    body = CSRF_NAME + '=' + encodeURIComponent(CSRF_HASH || '') + '&';
  }
  body += 'course_id='      + COURSE_ID
    + '&module_id='         + id
    + '&title='             + encodeURIComponent(title)
    + '&description='       + encodeURIComponent(document.getElementById('modDesc').value)
    + '&content_type='      + type
    + '&content_path='      + encodeURIComponent(document.getElementById('modPath').value)
    + '&weight_percentage=' + (parseFloat(document.getElementById('modWeight').value) || 0);

  fetch(BASE_URL + 'manage_courses/save_module', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: body,
  })
  .then(parseJsonResponse)
  .then(function(data) {
    btn.disabled = false;
    document.getElementById('modSaveText').textContent = id ? 'Update Module' : 'Add Module';
    if (data.success) {
      closeModModal();
      KA.toast('success', data.message);
      renderModule(data.module, id === 0);
      updateModuleWeightSummary();
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

  var effType = (m.content_type_effective !== undefined && m.content_type_effective !== null && m.content_type_effective !== '')
    ? m.content_type_effective
    : m.content_type;
  var ct    = CT_DATA[effType] || { icon:'📁', label: effType };
  var count = list.querySelectorAll('.mod-item').length;
  var order = isNew ? count + 1 : null;

  var cpMax = 3;
  var cpBadge = '';
  if (isVideoModuleContent(effType)) {
    var cpN = parseInt(m.checkpoint_count, 10) || 0;
    if (cpN >= cpMax) {
      cpBadge = '<a href="' + BASE_URL + 'assessments?module_id=' + m.id + '&type=checkpoint" class="mod-asx-badge cp" title="Video checkpoints for this module (maximum reached)">▶ Checkpoint (' + cpN + ')</a>';
    } else if (CHECKPOINT_SCHEMA_READY) {
      cpBadge = '<a href="' + BASE_URL + 'assessments/create?course_id=' + COURSE_ID + '&module_id=' + m.id + '&type=checkpoint" class="mod-asx-badge add" title="Add video progress checkpoint for this module">+ Video Checkpoint</a>';
    } else {
      cpBadge = '<span class="mod-asx-badge add" title="Video checkpoints require a database migration (lib_assessments context column)." style="opacity:.65;cursor:not-allowed;">+ Video Checkpoint</span>';
    }
  }

  var html = '<div class="mod-item" id="moditem-' + m.id + '"'
    + ' draggable="true"'
    + ' data-id="'     + m.id + '"'
    + ' data-title="'  + escAttr(m.title) + '"'
    + ' data-desc="'   + escAttr(m.description || '') + '"'
    + ' data-type="'   + escAttr(effType) + '"'
    + ' data-path="'   + escAttr(m.content_path || '') + '"'
    + ' data-weight="' + (m.weight_percentage || 0) + '">'
    + '<div class="mod-drag-handle"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="6" r="1" fill="currentColor"/><circle cx="15" cy="6" r="1" fill="currentColor"/><circle cx="9" cy="12" r="1" fill="currentColor"/><circle cx="15" cy="12" r="1" fill="currentColor"/><circle cx="9" cy="18" r="1" fill="currentColor"/><circle cx="15" cy="18" r="1" fill="currentColor"/></svg></div>'
    + '<div class="mod-order" id="modorder-' + m.id + '">' + (order || '?') + '</div>'
    + '<div class="mod-type-icon">' + ct.icon + '</div>'
    + '<div class="mod-body">'
    + '<div class="mod-title">' + escHtml(m.title) + '</div>'
    + '<div class="mod-meta">' + ct.label + (m.weight_percentage > 0 ? ' · ' + m.weight_percentage + '% weight' : '') + '</div>'
    + '<div class="mod-asx-badges">'
    + '<a href="' + BASE_URL + 'assessments/create?course_id=' + COURSE_ID + '&module_id=' + m.id + '&type=pre" class="mod-asx-badge add">+ Pre-assessment</a>'
    + '<a href="' + BASE_URL + 'assessments/create?course_id=' + COURSE_ID + '&module_id=' + m.id + '&type=post" class="mod-asx-badge add">+ Post-assessment</a>'
    + cpBadge
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
  updateModuleWeightSummary();
}

// ── Delete module ────────────────────────────────────────────
function deleteModule(id) {
  KA.confirm({
    title: 'Delete this module?',
    text:  'All student progress for this module will also be removed.',
    confirmText: 'Yes, delete',
    type: 'danger',
    onConfirm: function() {
      var delBody = '';
      if (CSRF_NAME) {
        delBody = CSRF_NAME + '=' + encodeURIComponent(CSRF_HASH || '') + '&';
      }
      delBody += 'module_id=' + id;
      fetch(BASE_URL + 'manage_courses/delete_module', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: delBody,
      })
      .then(parseJsonResponse)
      .then(function(data) {
        if (data.success) {
          document.getElementById('moditem-' + id)?.remove();
          KA.toast('success', 'Module deleted.');
          renumberModules();
          updateModuleWeightSummary();
          if (document.querySelectorAll('.mod-item').length === 0) {
            document.getElementById('modList').insertAdjacentHTML('beforeend',
              '<div class="ka-empty ka-empty--wide" id="modEmpty"><div class="ka-empty-icon" aria-hidden="true">📦</div>' +
              '<h3 class="ka-empty-title">No modules yet</h3>' +
              '<p class="ka-empty-text">Add a module from the button above to build this course.</p></div>'
            );
          }
        } else {
          KA.toast('error', data.message || 'Failed to delete.');
        }
      })
      .catch(function() {
        KA.toast('error', 'Network error. Please try again.');
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
  updateModuleWeightSummary();
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
refreshContentTypePicker();
updateModuleWeightSummary();
document.getElementById('modWeight')?.addEventListener('input', updateDraftWeightHint);

function saveOrder() {
  var ids = Array.from(document.querySelectorAll('.mod-item')).map(function(el) { return el.dataset.id; });
  var body = ids.map(function(id, i) { return 'ids[' + i + ']=' + id; }).join('&');
  if (CSRF_NAME) {
    body = CSRF_NAME + '=' + encodeURIComponent(CSRF_HASH || '') + '&' + body;
  }
  fetch(BASE_URL + 'manage_courses/reorder_modules', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: body,
  }).catch(function() {});
}

function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s) { return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModModal(); });

<?php if ($focus_modules): ?>
document.addEventListener('DOMContentLoaded', function() {
  var panel = document.getElementById('courseModulesPanel');
  if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
<?php endif; ?>
</script>