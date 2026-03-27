<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$assessment = $assessment ?? null;
$student    = $student    ?? null;
$answers    = $answers    ?? [];
$result     = $result     ?? ['score'=>0,'scored'=>0,'total'=>0,'pending'=>0];
if ( ! $assessment || ! $student) return;

$score   = (float)($result['score']   ?? 0);
$pending = (int)($result['pending']   ?? 0);
$total_q = (int)($result['total']     ?? 0);

$type_colors = ['multiple_choice'=>'#3b82f6','essay'=>'#f59f00','likert'=>'#22c55e','fill_blank'=>'#6dabcf'];

// Initials for student avatar
$initials = '';
foreach (explode(' ', trim($student->fullname ?? '')) as $w) {
  if ($w !== '') $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);
?>
<?php $this->load->view('layouts/alerts'); ?>

<style>
.grd-topbar { display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem; }
.grd-topbar-title { font-size:1.125rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 3px;letter-spacing:-.02em; }
.grd-topbar-sub { font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0; }
.grd-btn { display:inline-flex;align-items:center;gap:6px;padding:.5rem 1rem;border-radius:8px;font-size:.8125rem;font-weight:600;text-decoration:none;border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;color:var(--ka-text,#1e293b);cursor:pointer;transition:all .15s; }
.grd-btn:hover { border-color:var(--ka-primary,#6dabcf);color:var(--ka-primary,#6dabcf); }

/* Student card */
.grd-student-card {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;
  padding:1.125rem;margin-bottom:1.5rem;
  display:flex;align-items:center;gap:1rem;flex-wrap:wrap;
}
.grd-student-avatar {
  width:48px;height:48px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,var(--ka-primary,#6dabcf),#4a8eb0);
  display:flex;align-items:center;justify-content:center;
  font-size:1rem;font-weight:800;color:#fff;
}
.grd-student-name { font-size:1rem;font-weight:700;color:var(--ka-text,#1e293b);margin-bottom:2px; }
.grd-student-meta { font-size:.75rem;color:var(--ka-text-muted,#64748b); }
.grd-score-summary { margin-left:auto;text-align:right; }
.grd-score-val { font-size:1.75rem;font-weight:900;letter-spacing:-.02em;line-height:1; }
.grd-score-lbl { font-size:.6875rem;color:var(--ka-text-muted,#64748b);font-weight:500; }

/* Answer cards */
.grd-a-card { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;overflow:hidden;margin-bottom:1.125rem; }
.grd-a-header { padding:.875rem 1.125rem;border-bottom:1px solid var(--ka-border,#e2e8f0);display:flex;align-items:center;justify-content:space-between;background:var(--ka-bg,#f8fafc); }
.grd-a-num { width:28px;height:28px;border-radius:50%;background:var(--ka-navy,#1a3a5c);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.6875rem;font-weight:700;flex-shrink:0; }
.grd-a-type { font-size:.625rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:2px 8px;border-radius:20px; }
.grd-a-body { padding:1.125rem; }
.grd-q-text { font-size:.9375rem;font-weight:600;color:var(--ka-text,#1e293b);line-height:1.5;margin-bottom:.875rem; }

/* Answer display */
.grd-answer-box { border-radius:9px;padding:.875rem 1rem;font-size:.875rem;line-height:1.6;background:var(--ka-bg,#f8fafc);border:1px solid var(--ka-border,#e2e8f0);margin-bottom:.875rem; }

/* Score input */
.grd-score-row { display:flex;align-items:center;gap:.875rem;flex-wrap:wrap; }
.grd-score-label { font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);white-space:nowrap; }
.grd-score-input {
  width:90px;height:38px;padding:0 .75rem;
  border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;
  font-size:.9375rem;font-weight:700;color:var(--ka-text,#1e293b);
  outline:none;text-align:center;transition:all .2s;
}
.grd-score-input:focus { border-color:var(--ka-primary,#6dabcf);box-shadow:0 0 0 3px rgba(109,171,207,.15); }
.grd-save-btn {
  padding:.5rem 1.125rem;border-radius:8px;background:var(--ka-navy,#1a3a5c);
  color:#fff;border:none;cursor:pointer;font-size:.8125rem;font-weight:700;transition:all .15s;
}
.grd-save-btn:hover { background:#254d75; }
.grd-saved-chip { display:none;align-items:center;gap:4px;font-size:.75rem;font-weight:600;color:#059669; }

/* Already scored indicator */
.grd-scored-badge { display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:.6875rem;font-weight:700; }
.grd-scored-badge.graded  { background:#ecfdf5;color:#065f46; }
.grd-scored-badge.pending { background:#fffbeb;color:#92400e; }
.grd-scored-badge.auto    { background:var(--ka-accent,#e8f4fd);color:var(--ka-navy,#1a3a5c); }

/* MC choices display */
.grd-choice { display:flex;align-items:center;gap:.625rem;padding:.5rem .75rem;border-radius:7px;font-size:.875rem;margin-bottom:.25rem; }
.grd-choice.selected.correct { background:#ecfdf5;color:#065f46; }
.grd-choice.selected.wrong   { background:#fef2f2;color:#991b1b; }
.grd-choice.correct-ans      { background:#ecfdf5;color:#065f46;opacity:.7; }
.grd-choice-dot              { width:10px;height:10px;border-radius:50%;flex-shrink:0; }
</style>

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
      <div class="grd-score-val" style="color:<?= $score >= 75 ? '#059669' : '#dc2626' ?>">
        <?= number_format($score, 1) ?>%
      </div>
      <div class="grd-score-lbl"><?= $score >= 75 ? '✓ Passed' : '✗ Failed' ?></div>
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
        <span style="font-size:.875rem;font-weight:700;color:<?= (float)$ans_score >= 75 ? '#059669' : '#dc2626' ?>">
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

<script>
function saveScore(answerId) {
  var input   = document.getElementById('scoreinput-' + answerId);
  var score   = parseFloat(input.value);
  var chip    = document.getElementById('savedchip-' + answerId);

  if (isNaN(score) || score < 0 || score > 100) {
    KA.toast('error', 'Score must be between 0 and 100.');
    return;
  }

  var btn = input.closest('.grd-score-row').querySelector('.grd-save-btn');
  btn.disabled    = true;
  btn.textContent = 'Saving…';

  fetch('<?= base_url('index.php/assessments/save_grade') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: '<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>'
        + '&answer_id=' + answerId
        + '&score='     + score,
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    btn.disabled    = false;
    btn.textContent = 'Update';
    if (data.success) {
      chip.style.display = 'flex';
      setTimeout(function() { chip.style.display = 'none'; }, 3000);
      KA.toast('success', 'Score saved successfully.');
    } else {
      KA.toast('error', data.message || 'Failed to save score.');
    }
  })
  .catch(function() {
    btn.disabled    = false;
    btn.textContent = 'Update';
    KA.toast('error', 'Network error. Please try again.');
  });
}
</script>