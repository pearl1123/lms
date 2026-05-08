<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$assessment = $assessment ?? null;
$student    = $student    ?? null;
$answers    = $answers    ?? [];
$result     = $result     ?? ['score'=>0,'scored'=>0,'total'=>0,'pending'=>0];
$csrf_field_name = $csrf_field_name ?? '';
$csrf_hash       = $csrf_hash ?? '';
if ( ! $assessment || ! $student) return;

$score   = (float)($result['score']   ?? 0);
$pending = (int)($result['pending']   ?? 0);
$total_q = (int)($result['total']     ?? 0);
$pass_thr = (float) ka_assessment_pass_threshold();

$type_colors = ['multiple_choice'=>'#3b82f6','essay'=>'#f59f00','likert'=>'#22c55e','fill_blank'=>'#6dabcf'];

// Initials for student avatar
$initials = '';
foreach (explode(' ', trim($student->fullname ?? '')) as $w) {
  if ($w !== '') $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/assessments.css') ?>">

<!-- ══ Topbar ════════════════════════════════════════════════ -->
<div class="grd-topbar animate__animated animate__fadeIn animate__fast">
  <div>
    <h2 class="grd-topbar-title">Grading: <?= htmlspecialchars($student->fullname) ?></h2>
    <p class="grd-topbar-sub">
      <?= htmlspecialchars($assessment->title) ?>
      &nbsp;·&nbsp; <?= htmlspecialchars($assessment->course_title ?? '—') ?>
    </p>
  </div>
  <a href="<?= base_url('index.php/assessments/review/'.$assessment->id) ?>" class="grd-btn">
    ← Back to Review
  </a>
</div>

<!-- ══ Student card ══════════════════════════════════════════ -->
<div class="grd-student-card animate__animated animate__fadeInUp animate__fast">
  <div class="grd-student-avatar"><?= $initials ?></div>
  <div>
    <div class="grd-student-name"><?= htmlspecialchars($student->fullname) ?></div>
    <div class="grd-student-meta">
      <?= htmlspecialchars($student->employee_id ?? '') ?>
      &nbsp;·&nbsp; <?= ucfirst($student->role) ?>
    </div>
  </div>
  <div class="grd-score-summary">
    <?php if ($pending > 0): ?>
      <div class="grd-score-val" style="color:#d97706;font-size:1.25rem;">Pending</div>
      <div class="grd-score-lbl"><?= $pending ?> answer<?= $pending !== 1 ? 's' : '' ?> to review</div>
    <?php else: ?>
      <div class="grd-score-val" style="color:<?= $score >= $pass_thr ? '#059669' : '#dc2626' ?>">
        <?= number_format($score, 1) ?>%
      </div>
      <div class="grd-score-lbl"><?= $score >= $pass_thr ? '✓ Passed' : '✗ Failed' ?></div>
    <?php endif; ?>
  </div>
</div>

<!-- ══ Answer cards ══════════════════════════════════════════ -->
<?php foreach ($answers as $idx => $a):
  $q_type      = $a->question_type ?? 'essay';
  $type_color  = $type_colors[$q_type] ?? '#6dabcf';
  $ans_text    = $a->answer_text ?? '';
  $ans_score   = $a->score !== null ? (float)$a->score : null;
  $is_graded   = $ans_score !== null;
  $is_auto     = in_array($q_type, ['multiple_choice', 'fill_blank']);
  $needs_grade = ! $is_auto && ! $is_graded;
?>
<div class="grd-a-card animate__animated animate__fadeInUp" style="animation-delay:<?= $idx * 0.04 ?>s;" id="acard-<?= $a->id ?>">
  <div class="grd-a-header">
    <div style="display:flex;align-items:center;gap:.625rem;">
      <div class="grd-a-num"><?= $idx + 1 ?></div>
      <span class="grd-a-type" style="background:<?= $type_color ?>22;color:<?= $type_color ?>;">
        <?= ucfirst(str_replace('_',' ',$q_type)) ?>
      </span>
    </div>
    <div style="display:flex;align-items:center;gap:.5rem;">
      <?php if ($is_auto): ?>
        <span class="grd-scored-badge auto">⚡ Auto-scored</span>
      <?php elseif ($is_graded): ?>
        <span class="grd-scored-badge graded">✓ Graded</span>
      <?php else: ?>
        <span class="grd-scored-badge pending">⏳ Needs grading</span>
      <?php endif; ?>
      <?php if ($is_graded): ?>
        <span style="font-size:.875rem;font-weight:700;color:<?= (float)$ans_score >= $pass_thr ? '#059669' : '#dc2626' ?>">
          <?= number_format((float)$ans_score, 0) ?>%
        </span>
      <?php endif; ?>
    </div>
  </div>

  <div class="grd-a-body">
    <div class="grd-q-text"><?= nl2br(htmlspecialchars($a->question_text ?? '')) ?></div>

    <?php if ($q_type === 'multiple_choice' && ! empty($a->choices)): ?>
      <?php foreach ($a->choices as $c):
        $is_selected = (string)$c->id === (string)$ans_text;
        $is_correct  = (int)$c->is_correct === 1;
        $cls = $dot = '';
        if ($is_selected && $is_correct) { $cls = 'selected correct'; $dot = '#22c55e'; }
        elseif ($is_selected && ! $is_correct) { $cls = 'selected wrong'; $dot = '#dc2626'; }
        elseif (! $is_selected && $is_correct) { $cls = 'correct-ans'; $dot = '#22c55e'; }
        else $dot = '#e2e8f0';
      ?>
      <div class="grd-choice <?= $cls ?>">
        <div class="grd-choice-dot" style="background:<?= $dot ?>"></div>
        <?= htmlspecialchars($c->choice_text) ?>
        <?php if ($is_selected): ?> <span style="margin-left:auto;font-size:.6875rem;font-weight:700;"><?= $is_correct ? '✓ Correct' : '✗ Wrong' ?></span><?php endif; ?>
        <?php if (! $is_selected && $is_correct): ?> <span style="margin-left:auto;font-size:.6875rem;font-weight:700;">✓ Correct answer</span><?php endif; ?>
      </div>
      <?php endforeach; ?>

    <?php else: ?>
      <?php if ($ans_text !== ''): ?>
        <div class="grd-answer-box"><?= nl2br(htmlspecialchars($ans_text)) ?></div>
      <?php else: ?>
        <div class="grd-answer-box" style="color:var(--ka-text-muted,#64748b);font-style:italic;">No answer submitted.</div>
      <?php endif; ?>

      <?php if ($q_type === 'fill_blank' && ! empty($a->choices)): ?>
        <?php foreach ($a->choices as $c): if ($c->is_correct): ?>
          <div style="font-size:.8125rem;font-weight:600;color:#059669;margin-bottom:.875rem;">
            ✓ Correct answer: <?= htmlspecialchars($c->choice_text) ?>
          </div>
        <?php endif; endforeach; ?>
      <?php endif; ?>

      <!-- Score input (for essay / likert that need manual scoring) -->
      <?php if ( ! $is_auto): ?>
      <div class="grd-score-row" id="scorerow-<?= $a->id ?>">
        <span class="grd-score-label">Score (0–100):</span>
        <input type="number" class="grd-score-input" id="scoreinput-<?= $a->id ?>"
               min="0" max="100" step="1"
               value="<?= $is_graded ? (int)$ans_score : '' ?>"
               placeholder="—">
        <button type="button" class="grd-save-btn" onclick="saveScore(<?= $a->id ?>)">
          <?= $is_graded ? 'Update' : 'Save Score' ?>
        </button>
        <div class="grd-saved-chip" id="savedchip-<?= $a->id ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
          Saved!
        </div>
      </div>
      <?php if ($a->checker_name && $a->checked_at): ?>
        <div style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin-top:.5rem;">
          Last graded by <?= htmlspecialchars($a->checker_name) ?> on <?= date('M j, Y g:i A', strtotime($a->checked_at)) ?>
        </div>
      <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<?php if (empty($answers)): ?>
<div style="text-align:center;padding:2.5rem;background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;">
  <p style="color:var(--ka-text-muted,#64748b);font-size:.875rem;margin:0;">No answers found for this student.</p>
</div>
<?php endif; ?>

<?php $_jf = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT; ?>
<script>
kaApplyAppContext(<?= json_encode([
  'assessments' => [
    'grade' => [
      'saveGradeUrl' => base_url('index.php/assessments/save_grade'),
      'csrfFieldName' => $csrf_field_name,
      'csrfHash' => $csrf_hash,
    ],
  ],
], $_jf) ?>);
</script>
<script src="<?= base_url('assets/js/assessments.js') ?>"></script>