<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$modules   = $modules ?? [];
$user_role = strtolower($user->role ?? 'admin');
?>
<?php echo $alerts_partial_html ?? ''; ?>

<style>
.crt-layout { display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start; }
@media(max-width:991.98px){ .crt-layout { grid-template-columns:1fr; } }
.crt-panel { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;overflow:hidden; }
.crt-panel-hdr { padding:1rem 1.25rem;border-bottom:1px solid var(--ka-border,#e2e8f0); }
.crt-panel-title { font-size:.9rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0; }
.crt-panel-body { padding:1.25rem; }
.crt-form-group { margin-bottom:1.25rem; }
.crt-label { display:block;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:.5rem; }
.crt-label span { color:#dc2626; }
.crt-input, .crt-select, .crt-textarea {
  width:100%;padding:.625rem .875rem;
  border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;
  font-size:.875rem;color:var(--ka-text,#1e293b);
  background:var(--ka-bg,#f8fafc);outline:none;
  font-family:inherit;transition:all .2s;
}
.crt-input:focus, .crt-select:focus, .crt-textarea:focus {
  border-color:var(--ka-primary,#6dabcf);background:#fff;
  box-shadow:0 0 0 3px rgba(109,171,207,.15);
}
.crt-select { height:42px;cursor:pointer; }
.crt-help { font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin-top:.375rem; }
.crt-error { font-size:.6875rem;color:#dc2626;margin-top:.375rem; }
.crt-type-grid { display:grid;grid-template-columns:1fr 1fr;gap:.75rem; }
.crt-type-card {
  border:2px solid var(--ka-border,#e2e8f0);border-radius:10px;
  padding:1rem;cursor:pointer;transition:all .18s;text-align:center;
}
.crt-type-card:hover { border-color:var(--ka-primary,#6dabcf); }
.crt-type-card input { display:none; }
.crt-type-card.selected { border-color:var(--ka-navy,#1a3a5c);background:var(--ka-accent,#e8f4fd); }
.crt-type-icon { font-size:1.5rem;margin-bottom:.375rem; }
.crt-type-label { font-size:.8125rem;font-weight:700;color:var(--ka-text,#1e293b); }
.crt-type-sub   { font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin-top:2px; }
.crt-submit-btn {
  width:100%;padding:.875rem;border-radius:9px;
  background:var(--ka-navy,#1a3a5c);color:#fff;border:none;cursor:pointer;
  font-size:.9375rem;font-weight:700;transition:all .2s;
}
.crt-submit-btn:hover { background:#254d75;transform:translateY(-1px); }

/* Info panel */
.crt-info-item { display:flex;gap:.75rem;margin-bottom:1rem; }
.crt-info-icon { width:32px;height:32px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center; }
.crt-info-icon svg { width:15px;height:15px; }
.crt-info-text h4 { font-size:.8125rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0 0 2px; }
.crt-info-text p  { font-size:.75rem;color:var(--ka-text-muted,#64748b);margin:0;line-height:1.5; }
</style>

<!-- Page header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;" class="animate__animated animate__fadeIn animate__fast">
  <div>
    <h2 style="font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 3px;letter-spacing:-.02em;">Create Assessment</h2>
    <p style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0;">Set up a pre or post assessment for a course module</p>
  </div>
  <a href="<?= base_url('index.php/assessments') ?>"
     style="display:inline-flex;align-items:center;gap:6px;padding:.5rem 1rem;border-radius:8px;font-size:.8125rem;font-weight:600;text-decoration:none;border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;color:var(--ka-text,#1e293b);">
    ← Back
  </a>
</div>

<form method="post" action="<?= base_url('index.php/assessments/create') ?>" id="createForm">
  <input type="hidden" name="<?= $csrf_field_name ?>" value="<?= $csrf_hash ?>">

  <div class="crt-layout animate__animated animate__fadeInUp animate__fast">

    <!-- Main form -->
    <div>
      <div class="crt-panel" style="margin-bottom:1.25rem;">
        <div class="crt-panel-hdr"><h3 class="crt-panel-title">Assessment Details</h3></div>
        <div class="crt-panel-body">

          <!-- Title -->
          <div class="crt-form-group">
            <label class="crt-label" for="title">Assessment Title <span>*</span></label>
            <input type="text" id="title" name="title" class="crt-input"
                   placeholder="e.g. Infection Control Pre-Test"
                   value="<?= set_value('title') ?>" required>
            <?php if (form_error('title')): ?>
              <div class="crt-error"><?= form_error('title') ?></div>
            <?php endif; ?>
          </div>

          <!-- Module -->
          <div class="crt-form-group">
            <label class="crt-label" for="module_id">Course Module <span>*</span></label>
            <select id="module_id" name="module_id" class="crt-select" required>
              <option value="">-- Select a module --</option>
              <?php
              $current_course = '';
              foreach ($modules as $m):
                if ($m->course_title !== $current_course):
                  if ($current_course !== '') echo '</optgroup>';
                  echo '<optgroup label="' . htmlspecialchars($m->course_title) . '">';
                  $current_course = $m->course_title;
                endif;
              ?>
              <option value="<?= $m->id ?>" <?= (set_select('module_id', $m->id) || (!$_POST && $m->id === $preselect_mod)) ? 'selected' : '' ?>>
                <?= htmlspecialchars($m->module_title) ?>
              </option>
              <?php endforeach; ?>
              <?php if ($current_course !== '') echo '</optgroup>'; ?>
            </select>
            <?php if (form_error('module_id')): ?>
              <div class="crt-error"><?= form_error('module_id') ?></div>
            <?php endif; ?>
            <?php if (empty($modules)): ?>
              <div class="crt-help" style="color:#dc2626;">
                No modules available.
                <?php if ($user_role === 'teacher'): ?>
                  You need to create course modules first.
                <?php else: ?>
                  <a href="<?= base_url('index.php/manage_courses') ?>">Create a course module</a> first.
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>

      <!-- Assessment type -->
      <div class="crt-panel">
        <div class="crt-panel-hdr"><h3 class="crt-panel-title">Assessment Type <span style="color:#dc2626;">*</span></h3></div>
        <div class="crt-panel-body">
          <div class="crt-type-grid">
            <label class="crt-type-card <?= set_value('type') === 'pre' ? 'selected' : '' ?>" id="card-pre">
              <input type="radio" name="type" value="pre"
                     <?= set_value('type') === 'pre' ? 'checked' : '' ?>
                     onchange="selectType('pre')" required>
              <div class="crt-type-icon">⏱️</div>
              <div class="crt-type-label">Pre-Assessment</div>
              <div class="crt-type-sub">Taken before the module to measure baseline knowledge</div>
            </label>
            <label class="crt-type-card <?= set_value('type') === 'post' ? 'selected' : '' ?>" id="card-post">
              <input type="radio" name="type" value="post"
                     <?= set_value('type') === 'post' ? 'checked' : '' ?>
                     onchange="selectType('post')">
              <div class="crt-type-icon">🏆</div>
              <div class="crt-type-label">Post-Assessment</div>
              <div class="crt-type-sub">Taken after the module to evaluate learning outcomes</div>
            </label>
          </div>
          <?php if (form_error('type')): ?>
            <div class="crt-error" style="margin-top:.5rem;"><?= form_error('type') ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div>
      <div class="crt-panel" style="margin-bottom:1rem;">
        <div class="crt-panel-hdr"><h3 class="crt-panel-title">Ready to create?</h3></div>
        <div class="crt-panel-body">
          <p style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0 0 1rem;">
            After creating the assessment, you'll be taken to the question builder where you can add multiple choice, essay, fill-in-the-blank, or Likert scale questions.
          </p>
          <button type="submit" class="crt-submit-btn" <?= empty($modules) ? 'disabled' : '' ?>>
            Create &amp; Add Questions →
          </button>
        </div>
      </div>

      <div class="crt-panel">
        <div class="crt-panel-hdr"><h3 class="crt-panel-title">About Question Types</h3></div>
        <div class="crt-panel-body">
          <div class="crt-info-item">
            <div class="crt-info-icon" style="background:#eff6ff;color:#3b82f6;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
            </div>
            <div class="crt-info-text">
              <h4>Multiple Choice</h4>
              <p>Auto-scored. Students pick one answer from your defined choices.</p>
            </div>
          </div>
          <div class="crt-info-item">
            <div class="crt-info-icon" style="background:#fffbeb;color:#b45309;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </div>
            <div class="crt-info-text">
              <h4>Essay</h4>
              <p>Requires manual grading. You can set a minimum word count.</p>
            </div>
          </div>
          <div class="crt-info-item">
            <div class="crt-info-icon" style="background:#ecfdf5;color:#15803d;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18"/><path d="M3 6h18"/><path d="M3 18h18"/></svg>
            </div>
            <div class="crt-info-text">
              <h4>Likert Scale</h4>
              <p>1–5 rating scale. Requires manual interpretation.</p>
            </div>
          </div>
          <div class="crt-info-item" style="margin-bottom:0;">
            <div class="crt-info-icon" style="background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
            </div>
            <div class="crt-info-text">
              <h4>Fill in the Blank</h4>
              <p>Auto-scored by exact match (case-insensitive) against your defined correct answer.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</form>

<script>
function selectType(type) {
  document.getElementById('card-pre').classList.toggle('selected', type === 'pre');
  document.getElementById('card-post').classList.toggle('selected', type === 'post');
}
// Pre-select on page load if returning from validation
(function() {
  var checked = document.querySelector('input[name="type"]:checked');
  if (checked) selectType(checked.value);
})();
</script>