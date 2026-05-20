<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$assessments    = $assessments ?? [];
$stats_defaults = ['total' => 0, 'pre_count' => 0, 'post_count' => 0, 'checkpoint_count' => 0];
$stats_raw      = $assessment_stats ?? [];
$stats          = array_merge($stats_defaults, is_array($stats_raw) ? $stats_raw : []);
$is_manager  = ! empty($is_manager);

$type_labels = [
  'pre'         => 'Pre-Assessment',
  'post'        => 'Post-Assessment',
  'checkpoint'  => 'Video Checkpoint',
];
$type_colors = [
  'pre'         => '#3b82f6',
  'post'        => '#22c55e',
  'checkpoint'  => '#f97316',
];
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/assessments.css') ?>">

<!-- ══ Top Bar ═══════════════════════════════════════════════ -->
<div class="asx-topbar animate__animated animate__fadeIn animate__fast">
  <div class="asx-topbar-left">
    <h2>Assessments</h2>
    <p><?= $is_manager ? 'Manage pre, post, and video checkpoint assessments for your course modules' : 'Your assigned assessments' ?></p>
  </div>
  <?php if ($is_manager): ?>
  <a href="<?= base_url('index.php/assessments/create') ?>" class="asx-btn asx-btn-primary">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Assessment
  </a>
  <?php endif; ?>
</div>

<!-- ══ Stats ════════════════════════════════════════════════ -->
<div class="asx-stats animate__animated animate__fadeInUp animate__fast <?= $is_manager ? 'cols-4' : 'cols-3' ?>">
  <div class="asx-stat">
    <div class="asx-stat-icon asx-stat-icon--blue">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    </div>
    <div><div class="asx-stat-val"><?= (int) $stats['total'] ?></div><div class="asx-stat-lbl">Total Assessments</div></div>
  </div>
  <div class="asx-stat">
    <div class="asx-stat-icon asx-stat-icon--blue">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div><div class="asx-stat-val"><?= (int) $stats['pre_count'] ?></div><div class="asx-stat-lbl">Pre-Assessments</div></div>
  </div>
  <div class="asx-stat">
    <div class="asx-stat-icon asx-stat-icon--green">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
    </div>
    <div><div class="asx-stat-val"><?= (int) $stats['post_count'] ?></div><div class="asx-stat-lbl">Post-Assessments</div></div>
  </div>
  <?php if ($is_manager): ?>
  <div class="asx-stat">
    <div class="asx-stat-icon asx-stat-icon--orange">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
    </div>
    <div><div class="asx-stat-val"><?= (int) ($stats['checkpoint_count'] ?? 0) ?></div><div class="asx-stat-lbl">Video Checkpoints</div></div>
  </div>
  <?php endif; ?>
</div>

<!-- ══ Filters ══════════════════════════════════════════════ -->
<div class="asx-filters animate__animated animate__fadeInUp animate__fast asx-animate-delay-05">
  <div class="asx-search-wrap">
    <svg class="asx-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="asx-search" id="asxSearch" placeholder="Search assessments, courses, modules…">
  </div>
  <select class="asx-select" id="asxFilterType">
    <option value="">All Types</option>
    <option value="pre">Pre-Assessment</option>
    <option value="post">Post-Assessment</option>
    <?php if ($is_manager): ?>
    <option value="checkpoint">Video Checkpoint</option>
    <?php endif; ?>
  </select>
  <span class="asx-filter-count" id="asxCount"><?= (int) $stats['total'] ?> assessment<?= (int) $stats['total'] !== 1 ? 's' : '' ?></span>
</div>

<!-- ══ Assessment Grid ══════════════════════════════════════ -->
<div class="asx-grid animate__animated animate__fadeInUp animate__fast asx-animate-delay-1" id="asxGrid">

  <?php if ( ! empty($assessments)): ?>
    <?php foreach ($assessments as $i => $a):
      $type_color = $type_colors[$a->type] ?? '#6dabcf';
      $type_label = $type_labels[$a->type] ?? ucfirst($a->type);
      $q_count    = (int)($a->question_count ?? 0);
    ?>
    <div class="asx-card animate__animated animate__fadeInUp asx-stagger-<?= (int)($i % 8) ?>"
         data-title="<?= htmlspecialchars(strtolower($a->title ?? '')) ?>"
         data-type="<?= $a->type ?>"
         data-module-id="<?= (int) ($a->module_id ?? 0) ?>">

      <div class="asx-card-header">
        <span class="asx-card-type" style="background:<?= $type_color ?>22;color:<?= $type_color ?>;">
          <?= $type_label ?>
        </span>
        <?php if ($is_manager): ?>
        <div class="asx-card-actions">
          <a href="<?= base_url('index.php/assessments/edit/'.$a->id) ?>"
             class="asx-card-action-btn" title="Edit">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </a>
          <a href="<?= base_url('index.php/assessments/review/'.$a->id) ?>"
             class="asx-card-action-btn" title="Review Submissions">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </a>
          <button onclick="KA.deleteConfirm('<?= base_url('index.php/assessments/delete/'.$a->id) ?>', '<?= htmlspecialchars(addslashes($a->title ?? 'Assessment'), ENT_QUOTES) ?>')"
                  class="asx-card-action-btn danger" title="Delete">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
          </button>
        </div>
        <?php endif; ?>
      </div>

      <div class="asx-card-body">
        <div class="asx-card-title"><?= htmlspecialchars($a->title ?? 'Untitled Assessment') ?></div>
        <div class="asx-card-course"><?= htmlspecialchars($a->course_title ?? '—') ?></div>
        <div class="asx-card-module">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          <?= htmlspecialchars($a->module_title ?? '—') ?>
        </div>
        <div class="asx-card-meta">
          <div class="asx-card-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
            <?= $q_count ?> question<?= $q_count !== 1 ? 's' : '' ?>
          </div>
          <div class="asx-card-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <?= htmlspecialchars($a->created_at_display ?? '') ?>
          </div>
        </div>

        <?php if ( ! $is_manager && ! empty($a->score_chip_class) && ! empty($a->score_chip_text)): ?>
            <div class="asx-score-chip-wrap">
              <span class="asx-score-chip <?= htmlspecialchars($a->score_chip_class) ?>">
                <?= htmlspecialchars($a->score_chip_text) ?>
              </span>
            </div>
        <?php endif; ?>
      </div>

      <div class="asx-card-footer">
        <?php if ($is_manager): ?>
          <a href="<?= base_url('index.php/assessments/edit/'.$a->id) ?>"
             class="asx-cta asx-cta-edit">Edit Questions</a>
          <a href="<?= base_url('index.php/assessments/review/'.$a->id) ?>"
             class="asx-cta asx-cta-review">Review</a>
        <?php else: ?>
          <?php if (isset($a->already_answered) && $a->already_answered): ?>
            <a href="<?= base_url('index.php/assessments/result/'.$a->id) ?>"
               class="asx-cta asx-cta-retake">View Result</a>
          <?php else: ?>
            <a href="<?= base_url('index.php/assessments/take/'.$a->id) ?>"
               class="asx-cta asx-cta-take">Take Assessment</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

  <?php else: ?>
    <div class="asx-empty">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      <h4>No assessments yet</h4>
      <p><?= $is_manager ? 'Create your first assessment to get started.' : 'No assessments have been assigned to you yet.' ?></p>
      <?php if ($is_manager): ?>
        <a href="<?= base_url('index.php/assessments/create') ?>" class="asx-btn asx-btn-primary">Create Assessment</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>

<script src="<?= base_url('assets/js/assessments.js') ?>"></script>
