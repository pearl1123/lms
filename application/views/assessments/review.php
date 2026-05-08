<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$assessment = $assessment ?? null;
$questions  = $questions  ?? [];
$attempts   = $attempts   ?? [];
if ( ! $assessment) return;
$total_q    = count($questions);
$pass_thr   = (float) ka_assessment_pass_threshold();
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/assessments.css') ?>">

<!-- ══ Topbar ════════════════════════════════════════════════ -->
<div class="rev-topbar animate__animated animate__fadeIn animate__fast">
  <div>
    <div style="display:flex;align-items:center;gap:.625rem;margin-bottom:.375rem;">
      <h2 class="rev-topbar-title"><?= htmlspecialchars($assessment->title) ?></h2>
      <span class="rev-badge rev-badge-<?= $assessment->type ?>">
        <?= $assessment->type === 'pre' ? 'Pre-Assessment' : 'Post-Assessment' ?>
      </span>
    </div>
    <p class="rev-topbar-sub">
      <?= htmlspecialchars($assessment->course_title ?? '—') ?>
      &nbsp;·&nbsp;
      <?= htmlspecialchars($assessment->module_title ?? '—') ?>
    </p>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
    <a href="<?= base_url('index.php/assessments/edit/'.$assessment->id) ?>" class="rev-btn rev-btn-outline">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Edit Assessment
    </a>
    <a href="<?= base_url('index.php/assessments') ?>" class="rev-btn rev-btn-outline">
      ← Back
    </a>
  </div>
</div>

<!-- ══ Stats ════════════════════════════════════════════════ -->
<?php
$total_attempts  = count($attempts);
$total_passed    = count(array_filter($attempts, function($a) use ($pass_thr) {
    return (float)($a->avg_score ?? 0) >= $pass_thr && (int)($a->pending ?? 0) === 0;
}));
$total_pending   = count(array_filter($attempts, function($a) { return (int)($a->pending ?? 0) > 0; }));
$avg_all         = $total_attempts > 0
  ? round(array_sum(array_column(array_map('get_object_vars', $attempts), 'avg_score')) / $total_attempts, 1)
  : 0;
?>
<div class="rev-stats animate__animated animate__fadeInUp animate__fast">
  <div class="rev-stat">
    <div class="rev-stat-val"><?= $total_attempts ?></div>
    <div class="rev-stat-lbl">Submissions</div>
  </div>
  <div class="rev-stat">
    <div class="rev-stat-val" style="color:#22c55e;"><?= $total_passed ?></div>
    <div class="rev-stat-lbl">Passed ≥ <?= number_format($pass_thr, 0) ?>%</div>
  </div>
  <div class="rev-stat">
    <div class="rev-stat-val" style="color:#f59f00;"><?= $total_pending ?></div>
    <div class="rev-stat-lbl">Pending Review</div>
  </div>
  <div class="rev-stat">
    <div class="rev-stat-val"><?= $avg_all ?>%</div>
    <div class="rev-stat-lbl">Avg. Score</div>
  </div>
</div>

<!-- ══ Submissions Table ══════════════════════════════════════ -->
<div class="rev-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s;">
  <div class="rev-panel-hdr">
    <h3 class="rev-panel-title">
      Student Submissions
      <span style="font-size:.75rem;font-weight:500;color:var(--ka-text-muted,#64748b);margin-left:.5rem;"><?= $total_q ?> question<?= $total_q !== 1 ? 's' : '' ?></span>
    </h3>
    <span style="font-size:.75rem;font-weight:600;color:var(--ka-text-muted,#64748b);"><?= $total_attempts ?> student<?= $total_attempts !== 1 ? 's' : '' ?></span>
  </div>

  <?php if ( ! empty($attempts)): ?>
  <table class="rev-table">
    <thead>
      <tr>
        <th>Student</th>
        <th>Answered</th>
        <th>Score</th>
        <th>Status</th>
        <th>Submitted</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($attempts as $a):
        $avg      = $a->avg_score !== null ? round((float)$a->avg_score, 1) : null;
        $pending  = (int)($a->pending ?? 0);
        $is_pass  = $avg !== null && $avg >= $pass_thr && $pending === 0;
        $initials = '';
        foreach (explode(' ', trim($a->student_name ?? '')) as $w) {
          if ($w !== '') $initials .= strtoupper(substr($w, 0, 1));
        }
        $initials = substr($initials, 0, 2);
      ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:.625rem;">
            <div class="rev-avatar"><?= $initials ?></div>
            <div>
              <div style="font-weight:600;"><?= htmlspecialchars($a->student_name ?? '—') ?></div>
              <div style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);"><?= htmlspecialchars($a->employee_id ?? '') ?></div>
            </div>
          </div>
        </td>
        <td><?= (int)($a->answered ?? 0) ?> / <?= $total_q ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:.625rem;">
            <?php if ($pending > 0): ?>
              <span style="font-size:.8125rem;color:#92400e;font-weight:600;">Pending</span>
            <?php elseif ($avg !== null): ?>
              <div class="rev-prog-bar">
                <div class="rev-prog-fill <?= $is_pass ? 'pass' : 'fail' ?>" style="width:<?= $avg ?>%"></div>
              </div>
              <span style="font-size:.8125rem;font-weight:700;color:<?= $is_pass ? '#059669' : '#dc2626' ?>">
                <?= $avg ?>%
              </span>
            <?php else: ?>
              <span style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);">—</span>
            <?php endif; ?>
          </div>
        </td>
        <td>
          <?php if ($pending > 0): ?>
            <span class="rev-chip rev-chip-pending">⏳ <?= $pending ?> pending</span>
          <?php elseif ($is_pass): ?>
            <span class="rev-chip rev-chip-pass">✓ Passed</span>
          <?php else: ?>
            <span class="rev-chip rev-chip-fail">✗ Failed</span>
          <?php endif; ?>
        </td>
        <td style="color:var(--ka-text-muted,#64748b);">
          <?= $a->submitted_at ? date('M j, Y', strtotime($a->submitted_at)) : '—' ?>
        </td>
        <td>
          <a href="<?= base_url('index.php/assessments/grade/'.$assessment->id.'/'.$a->user_id) ?>"
             class="rev-action-btn">
            <?= $pending > 0 ? 'Grade' : 'View' ?>
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="rev-empty">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    <p>No submissions yet. Students haven't taken this assessment.</p>
  </div>
  <?php endif; ?>
</div>