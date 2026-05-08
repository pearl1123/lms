<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$modules   = $modules ?? [];
$user_role = strtolower($user->role ?? 'admin');
$checkpoint_schema_ready = ! empty($checkpoint_schema_ready);
$csrf_field_name         = $csrf_field_name ?? '';
$csrf_hash               = $csrf_hash ?? '';
$preselect_mod           = (int) ($preselect_mod ?? 0);
$checkpoint_auto_checked = (isset($_POST['checkpoint_auto_generate']) && $_POST['checkpoint_auto_generate'] === '1');
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/assessments.css') ?>">

<!-- Page header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;" class="animate__animated animate__fadeIn animate__fast">
  <div>
    <h2 style="font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 3px;letter-spacing:-.02em;">Create Assessment</h2>
    <p style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0;">Set up a pre, post, or video checkpoint assessment for a course module</p>
  </div>
  <a href="<?= base_url('index.php/assessments') ?>"
     style="display:inline-flex;align-items:center;gap:6px;padding:.5rem 1rem;border-radius:8px;font-size:.8125rem;font-weight:600;text-decoration:none;border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;color:var(--ka-text,#1e293b);">
    ← Back
  </a>
</div>

<form method="post" action="<?= base_url('index.php/assessments/create') ?>" id="createForm">
  <input type="hidden" name="<?= html_escape($csrf_field_name) ?>" value="<?= html_escape($csrf_hash) ?>">

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
            <?php
              $sel_cp = (set_value('type') === 'checkpoint')
                || (empty($_POST) && ($preselect_type ?? '') === 'checkpoint' && $checkpoint_schema_ready);
            ?>
            <label class="crt-type-card <?= $sel_cp ? 'selected' : '' ?> <?= $checkpoint_schema_ready ? '' : 'disabled' ?>" id="card-checkpoint">
              <input type="radio" name="type" value="checkpoint"
                     <?= $sel_cp ? 'checked' : '' ?>
                     <?= $checkpoint_schema_ready ? 'onchange="selectType(\'checkpoint\')"' : 'disabled' ?>>
              <div class="crt-type-icon">▶️</div>
              <div class="crt-type-label">Video Checkpoint</div>
              <div class="crt-type-sub">Shown during module video at an optional timestamp</div>
            </label>
          </div>
          <?php if ( ! $checkpoint_schema_ready): ?>
            <p class="crt-help" style="margin-top:.75rem;color:#b45309;">
              Video checkpoints need the unified <code>lib_assessments</code> checkpoint columns (e.g. <code>context</code>) on this server. Pre and post assessments are still available.
            </p>
          <?php endif; ?>
          <?php if (form_error('type')): ?>
            <div class="crt-error" style="margin-top:.5rem;"><?= form_error('type') ?></div>
          <?php endif; ?>

          <div class="crt-checkpoint-panel" id="checkpointFields">
            <input type="hidden" name="trigger_percent" id="trigger_percent" value="<?= htmlspecialchars(set_value('trigger_percent', '0')) ?>">
            <div class="crt-form-group">
              <label class="crt-toggle">
                <input type="checkbox" name="checkpoint_auto_generate" id="checkpoint_auto_generate" value="1"
                  <?= set_checkbox('checkpoint_auto_generate', '1') ?>
                  onchange="toggleCheckpointAuto(this.checked)">
                Auto-generate 3 checkpoints (random times in early, middle, and late video)
              </label>
              <div class="crt-help">Requires whole-video duration in seconds (e.g. from YouTube studio). Module must have room for 3 checkpoints (max 3 per module total).</div>
            </div>
            <div class="crt-form-group" id="checkpointDurationWrap" style="display:<?= $checkpoint_auto_checked ? 'block' : 'none' ?>;">
              <label class="crt-label" for="video_duration_seconds">Whole video duration (seconds) <span>*</span></label>
              <input type="number" id="video_duration_seconds" name="video_duration_seconds" class="crt-input" min="1" step="1"
                     placeholder="e.g. 720 for a 12-minute video"
                     value="<?= htmlspecialchars(set_value('video_duration_seconds', '')) ?>">
              <div class="crt-help">Used only for auto mode. Timestamps are placed at random seconds within 5–30%, 30–70%, and 70–95% of this length.</div>
              <?php if (form_error('video_duration_seconds')): ?>
                <div class="crt-error"><?= form_error('video_duration_seconds') ?></div>
              <?php endif; ?>
            </div>
            <div class="crt-form-group" id="checkpointManualTriggerWrap" style="display:<?= $checkpoint_auto_checked ? 'none' : 'block' ?>;">
              <label class="crt-label" for="trigger_seconds">Video timestamp (seconds)</label>
              <input type="number" id="trigger_seconds" name="trigger_seconds" class="crt-input" min="0" step="1"
                     placeholder="Leave empty to use start of video (0%)"
                     value="<?= htmlspecialchars(set_value('trigger_seconds', '')) ?>">
              <div class="crt-help">Optional. When set, the checkpoint prompt appears after this many seconds of playback.</div>
              <?php if (form_error('trigger_seconds')): ?>
                <div class="crt-error"><?= form_error('trigger_seconds') ?></div>
              <?php endif; ?>
            </div>
            <div class="crt-form-group">
              <label class="crt-toggle">
                <input type="checkbox" name="checkpoint_required" value="1" <?= set_checkbox('checkpoint_required', '1') ?>>
                Required — learner must answer before continuing
              </label>
            </div>
            <div class="crt-form-group" style="margin-bottom:0;">
              <label class="crt-label" for="sort_order">Display order (optional)</label>
              <input type="number" id="sort_order" name="sort_order" class="crt-input" min="0" step="1"
                     placeholder="0 = first among checkpoints in this module"
                     value="<?= htmlspecialchars(set_value('sort_order', '0')) ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div>
      <div class="crt-panel" style="margin-bottom:1rem;">
        <div class="crt-panel-hdr"><h3 class="crt-panel-title">Ready to create?</h3></div>
        <div class="crt-panel-body">
          <p style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0 0 1rem;" id="createSidebarHint">
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

<script src="<?= base_url('assets/js/assessments.js') ?>"></script>