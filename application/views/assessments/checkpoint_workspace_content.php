<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$checkpoint_workspace = $checkpoint_workspace ?? null;
$assessment           = $assessment ?? null;
if ( ! $checkpoint_workspace || ! $assessment) {
    return;
}
$panels     = $checkpoint_workspace['panels'] ?? [];
$active_id  = (int) ($checkpoint_workspace['active_id'] ?? $assessment->id);
$module_id  = (int) ($checkpoint_workspace['module_id'] ?? 0);
$course_id  = (int) ($checkpoint_workspace['course_id'] ?? 0);
$count      = (int) ($checkpoint_workspace['checkpoint_count'] ?? 0);
$max_cp     = (int) ($checkpoint_workspace['max_checkpoints'] ?? 3);
$can_auto   = ! empty($checkpoint_workspace['can_auto_generate']);
$youtube_id = $checkpoint_workspace['youtube_id'] ?? null;
$completed = 0;
foreach ($panels as $p) {
    if ( ! empty($p['has_question'])) {
        $completed++;
    }
}
$generated_flash = ! empty($_GET['generated']);
$suggested_duration = (int) ($checkpoint_workspace['suggested_video_duration_seconds'] ?? 0);
$max_trigger        = (int) ($checkpoint_workspace['max_trigger_seconds'] ?? 0);
?>

<section class="asx-editor-section <?= empty($panels) ? 'is-active' : '' ?>" data-asx-section="overview" id="asxSectionOverview">
  <div class="asx-editor-card">
    <div class="asx-editor-card-hdr">
      <h2 class="asx-editor-card-title">Video assessment overview</h2>
      <span class="asx-editor-progress"><?= $completed ?>/<?= max(1, $count) ?> checkpoints ready</span>
    </div>
    <div class="asx-editor-card-body asx-editor-overview-grid">
      <div class="asx-stat-tile">
        <span class="asx-stat-tile-val"><?= $count ?></span>
        <span class="asx-stat-tile-lbl">Checkpoints</span>
      </div>
      <div class="asx-stat-tile">
        <span class="asx-stat-tile-val"><?= $completed ?></span>
        <span class="asx-stat-tile-lbl">With questions</span>
      </div>
      <div class="asx-stat-tile">
        <span class="asx-stat-tile-val"><?= max(0, $count - $completed) ?></span>
        <span class="asx-stat-tile-lbl">Need setup</span>
      </div>
    </div>
    <p class="asx-editor-card-lead">Configure each segment checkpoint: set the video timestamp, write one multiple-choice question, and mark the correct answer. Learners see prompts during playback.</p>
  </div>
</section>

<section class="asx-editor-section <?= ! empty($panels) ? 'is-active' : '' ?>" data-asx-section="content" id="asxSectionContent">
  <?php if (empty($panels)): ?>
  <div class="asx-editor-card asx-editor-card--empty" id="cpwEmptyState">
    <?php $this->load->view('components/empty_state', [
        'emoji'       => '▶️',
        'title'       => 'No checkpoints yet',
        'description' => 'Generate three checkpoints with randomized timestamps, or add one manually.',
        'modifier'    => 'ka-empty--wide',
    ]); ?>
    <div class="cpw-empty-actions">
      <?php if ($can_auto): ?>
      <button type="button" class="asx-btn asx-btn--primary" id="cpwAutoGenerateBtn">
        <span class="asx-btn-spinner" id="cpwAutoGenerateSpinner" hidden></span>
        Auto-generate 3 checkpoints
      </button>
      <?php endif; ?>
      <a href="<?= base_url('index.php/assessments/create?course_id=' . $course_id . '&module_id=' . $module_id . '&type=checkpoint') ?>"
         class="asx-btn asx-btn--ghost">Add manually</a>
    </div>
    <div class="cpw-auto-fields" id="cpwAutoFields" style="<?= $can_auto ? '' : 'display:none' ?>">
      <label class="cpw-label" for="cpwAutoDuration">Whole video duration (seconds)</label>
      <input type="number" class="cpw-input" id="cpwAutoDuration" min="1" step="1" placeholder="e.g. 720">
    </div>
  </div>
  <?php else: ?>

  <div class="cpw-workspace <?= $generated_flash ? 'cpw-workspace--generated' : '' ?>" id="cpwWorkspace">
    <div class="cpw-workspace-toolbar">
      <div class="cpw-seg-tabs" role="tablist" aria-label="Checkpoint segments">
        <?php foreach ($panels as $i => $panel):
          $aid = (int) $panel['assessment']->id;
          $is_active = ($aid === $active_id);
          $done = ! empty($panel['has_question']);
        ?>
        <button type="button"
                class="cpw-seg-tab <?= $is_active ? 'is-active' : '' ?> <?= $done ? 'is-complete' : '' ?> <?= $generated_flash ? 'cpw-glow-in' : '' ?>"
                role="tab"
                data-cpw-tab="<?= $aid ?>"
                id="cpw-tab-<?= $aid ?>">
          <span class="cpw-seg-tab-label"><?= htmlspecialchars($panel['segment_label'] ?? ('Segment ' . ($i + 1)), ENT_QUOTES) ?></span>
          <?php if (! empty($panel['trigger_seconds'])): ?>
          <span class="cpw-seg-tab-ts"><?= (int) $panel['trigger_seconds'] ?>s</span>
          <?php endif; ?>
          <?php if ($done): ?><span class="cpw-seg-tab-done">✓</span><?php endif; ?>
        </button>
        <?php endforeach; ?>
      </div>
      <div class="cpw-workspace-actions">
        <?php if ($can_auto && $count < $max_cp): ?>
        <button type="button" class="asx-btn asx-btn--ghost asx-btn--sm" id="cpwAutoGenerateMoreBtn">
          <span class="asx-btn-spinner" id="cpwAutoMoreSpinner" hidden></span>
          Auto-generate 3 more
        </button>
        <?php endif; ?>
        <a href="<?= base_url('index.php/assessments/create?course_id=' . $course_id . '&module_id=' . $module_id . '&type=checkpoint') ?>"
           class="asx-btn asx-btn--ghost asx-btn--sm">+ Add</a>
      </div>
    </div>

    <div class="asx-editor-card asx-editor-card--inline" id="cpwSharedDurationCard">
      <label class="cpw-label" for="cpwSharedDuration">Whole video duration (seconds) <span class="cpw-label-req">*</span></label>
      <input type="number"
             class="cpw-input"
             id="cpwSharedDuration"
             name="whole_video_duration_seconds"
             min="1"
             step="1"
             placeholder="e.g. 94 or 1000"
             value="<?= $suggested_duration > 0 ? (int) $suggested_duration : '' ?>"
             data-module-id="<?= (int) $module_id ?>"
             data-max-trigger="<?= (int) $max_trigger ?>">
      <p class="cpw-help">Full length of the video (not the checkpoint timestamp). Required when saving timestamps.<?php if ($max_trigger > 0): ?> Longest checkpoint on this module: <?= (int) $max_trigger ?>s.<?php endif; ?></p>
    </div>

    <div class="cpw-blocks" id="cpwBlocks">
      <?php foreach ($panels as $panel):
        $this->load->view('assessments/checkpoint_editor_panel', [
            'panel'       => $panel,
            'active_id'   => $active_id,
            'youtube_id'  => $youtube_id,
            'block_mode'  => true,
        ]);
      endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>

<section class="asx-editor-section" data-asx-section="video" id="asxSectionVideo" <?= $youtube_id ? '' : 'hidden' ?>>
  <?php if ($youtube_id): ?>
  <div class="asx-editor-card">
    <div class="asx-editor-card-hdr"><h2 class="asx-editor-card-title">Video preview</h2></div>
    <div class="asx-editor-card-body">
      <div class="cpw-preview-embed cpw-preview-embed--large">
        <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($youtube_id, ENT_QUOTES) ?>?rel=0"
                title="Module video" loading="lazy" allowfullscreen></iframe>
      </div>
    </div>
  </div>
  <?php endif; ?>
</section>

<section class="asx-editor-section" data-asx-section="settings" id="asxSectionSettings">
  <div class="asx-editor-card">
    <div class="asx-editor-card-hdr"><h2 class="asx-editor-card-title">Module &amp; workspace</h2></div>
    <div class="asx-editor-card-body">
      <p class="asx-editor-card-lead">This workspace edits all video checkpoints for the selected module. Use the Checkpoints section to configure each segment.</p>
      <a href="<?= base_url('index.php/manage_courses/edit/' . $course_id . '#modules') ?>" class="asx-btn asx-btn--ghost">Open course modules</a>
    </div>
  </div>
</section>

<section class="asx-editor-section" data-asx-section="publish" id="asxSectionPublish">
  <div class="asx-editor-card">
    <div class="asx-editor-card-hdr"><h2 class="asx-editor-card-title">Review &amp; publish</h2></div>
    <div class="asx-editor-card-body">
      <p class="asx-editor-card-lead">Checkpoints are active for enrolled learners once questions and timestamps are saved. Review submissions per checkpoint from the assessments list.</p>
      <a href="<?= base_url('index.php/assessments') ?>" class="asx-btn asx-btn--primary">Back to assessments</a>
    </div>
  </div>
</section>
