<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$assessment  = $assessment  ?? null;
$questions   = $questions   ?? [];
$user_answers= $user_answers?? [];
$result      = $result      ?? ['score'=>0,'scored'=>0,'total'=>0,'pending'=>0];
if ( ! $assessment) return;

$score      = (float)($result['score']   ?? 0);
$scored     = (int)($result['scored']    ?? 0);
$total_q    = (int)($result['total']     ?? 0);
$pending    = (int)($result['pending']   ?? 0);
$has_pending= $pending > 0;
$pass_thr   = (float) ka_assessment_pass_threshold();
$passed     = ! $has_pending && $score >= $pass_thr;
$failed     = ! $has_pending && $score < $pass_thr;

$type_colors = ['multiple_choice'=>'#3b82f6','essay'=>'#f59f00','likert'=>'#22c55e','fill_blank'=>'#6dabcf'];
?>
<?php echo $alerts_partial_html ?? ''; ?>

<style>
/* Result hero */
.res-hero {
  border-radius:16px;padding:2rem;margin-bottom:1.75rem;
  position:relative;overflow:hidden;text-align:center;
  box-shadow:0 8px 32px rgba(0,0,0,.12);
}
.res-hero.passed  { background:linear-gradient(135deg,#065f46,#059669,#34d399); }
.res-hero.failed  { background:linear-gradient(135deg,#7f1d1d,#dc2626,#f87171); }
.res-hero.pending { background:linear-gradient(135deg,#78350f,#d97706,#fbbf24); }
.res-hero::before { content:'';position:absolute;top:-60px;right:-60px;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.07);pointer-events:none; }
.res-hero-body { position:relative;z-index:1; }
.res-hero-icon { font-size:3rem;margin-bottom:.625rem;line-height:1; }
.res-hero-label { font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.7);margin-bottom:.375rem; }
.res-hero-score { font-size:3.5rem;font-weight:900;color:#fff;line-height:1;letter-spacing:-.03em; }
.res-hero-sub { font-size:.875rem;color:rgba(255,255,255,.75);margin-top:.375rem; }
.res-hero-meta { display:flex;justify-content:center;gap:2rem;margin-top:1.5rem;flex-wrap:wrap; }
.res-hero-stat { text-align:center; }
.res-hero-stat-val { font-size:1.375rem;font-weight:800;color:#fff;line-height:1; }
.res-hero-stat-lbl { font-size:.6875rem;color:rgba(255,255,255,.6);margin-top:3px;font-weight:500; }

/* Layout */
.res-layout { display:grid;grid-template-columns:1fr 280px;gap:1.25rem;align-items:start; }
@media(max-width:991.98px){ .res-layout { grid-template-columns:1fr; } }

/* Answer cards */
.res-q-card { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;overflow:hidden;margin-bottom:1.125rem; }
.res-q-header {
  padding:.875rem 1.125rem;border-bottom:1px solid var(--ka-border,#e2e8f0);
  display:flex;align-items:center;justify-content:space-between;
  background:var(--ka-bg,#f8fafc);
}
.res-q-num { width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6875rem;font-weight:700;flex-shrink:0; }
.res-q-num.correct  { background:#ecfdf5;color:#065f46; }
.res-q-num.wrong    { background:#fef2f2;color:#991b1b; }
.res-q-num.pending  { background:#fffbeb;color:#92400e; }
.res-q-num.no-answer{ background:var(--ka-bg,#f8fafc);color:var(--ka-text-muted,#64748b);border:1.5px solid var(--ka-border,#e2e8f0); }
.res-q-type { font-size:.625rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:2px 8px;border-radius:20px; }
.res-score-badge { padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700; }
.res-score-badge.correct  { background:#ecfdf5;color:#065f46; }
.res-score-badge.wrong    { background:#fef2f2;color:#991b1b; }
.res-score-badge.pending  { background:#fffbeb;color:#92400e; }
.res-q-body { padding:1.125rem; }
.res-q-text { font-size:.9375rem;font-weight:600;color:var(--ka-text,#1e293b);line-height:1.5;margin-bottom:.875rem; }
.res-answer-block { border-radius:9px;padding:.75rem 1rem;font-size:.875rem;line-height:1.6; }
.res-answer-block.correct  { background:#ecfdf5;color:#065f46;border:1px solid #bbf7d0; }
.res-answer-block.wrong    { background:#fef2f2;color:#7f1d1d;border:1px solid #fecaca; }
.res-answer-block.pending  { background:#fffbeb;color:#78350f;border:1px solid #fde68a; }
.res-answer-block.empty    { background:var(--ka-bg,#f8fafc);color:var(--ka-text-muted,#64748b);border:1px solid var(--ka-border,#e2e8f0); }
.res-correct-answer { margin-top:.625rem;padding:.625rem .875rem;border-radius:7px;background:#ecfdf5;color:#065f46;font-size:.8125rem;font-weight:600; }

/* Choices display */
.res-choice { display:flex;align-items:center;gap:.625rem;padding:.5rem .75rem;border-radius:8px;font-size:.875rem;margin-bottom:.375rem; }
.res-choice.selected.correct { background:#ecfdf5;color:#065f46; }
.res-choice.selected.wrong   { background:#fef2f2;color:#991b1b; }
.res-choice.correct-ans      { background:#ecfdf5;color:#065f46;opacity:.7; }
.res-choice-dot { width:10px;height:10px;border-radius:50%;flex-shrink:0; }

/* Sidebar panels */
.res-panel { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;overflow:hidden;margin-bottom:1rem; }
.res-panel-hdr { padding:.875rem 1rem;border-bottom:1px solid var(--ka-border,#e2e8f0); }
.res-panel-title { font-size:.875rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0; }
.res-panel-body { padding:1rem; }
.res-info-item { display:flex;align-items:center;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--ka-border,#e2e8f0);font-size:.8125rem; }
.res-info-item:last-child { border-bottom:none; }
.res-info-label { color:var(--ka-text-muted,#64748b);font-weight:500; }
.res-info-value { font-weight:700;color:var(--ka-text,#1e293b); }
.res-back-btn {
  display:block;width:100%;padding:.75rem;border-radius:9px;
  background:var(--ka-navy,#1a3a5c);color:#fff;border:none;cursor:pointer;
  font-size:.875rem;font-weight:700;text-decoration:none;text-align:center;transition:all .2s;
}
.res-back-btn:hover { background:#254d75;color:#fff;transform:translateY(-1px); }
</style>

<!-- ══ Hero ═════════════════════════════════════════════════ -->
<div class="res-hero <?= $has_pending ? 'pending' : ($passed ? 'passed' : 'failed') ?> animate__animated animate__fadeIn animate__fast">
  <div class="res-hero-body">
    <div class="res-hero-icon"><?= $has_pending ? '⏳' : ($passed ? '🏆' : '📋') ?></div>
    <div class="res-hero-label">
      <?= $assessment->type === 'pre' ? 'Pre-Assessment' : 'Post-Assessment' ?> Result
    </div>

    <?php if ($has_pending): ?>
      <div class="res-hero-score">Pending</div>
      <div class="res-hero-sub"><?= $pending ?> answer<?= $pending !== 1 ? 's' : '' ?> awaiting instructor review</div>
    <?php else: ?>
      <div class="res-hero-score"><?= number_format($score, 1) ?>%</div>
      <div class="res-hero-sub"><?= $passed ? 'Congratulations! You passed.' : 'Keep studying and try again.' ?></div>
    <?php endif; ?>

    <div class="res-hero-meta">
      <div class="res-hero-stat">
        <div class="res-hero-stat-val"><?= $total_q ?></div>
        <div class="res-hero-stat-lbl">Total Questions</div>
      </div>
      <div class="res-hero-stat">
        <div class="res-hero-stat-val"><?= $scored ?></div>
        <div class="res-hero-stat-lbl">Scored</div>
      </div>
      <div class="res-hero-stat">
        <div class="res-hero-stat-val"><?= $pending ?></div>
        <div class="res-hero-stat-lbl">Pending Review</div>
      </div>
    </div>
  </div>
</div>

<!-- ══ Body ══════════════════════════════════════════════════ -->
<div class="res-layout">

  <!-- Answer review -->
  <div>
    <?php foreach ($questions as $idx => $q):
      $answer    = $user_answers[$q->id] ?? null;
      $ans_text  = $answer->answer_text ?? '';
      $ans_score = isset($answer->score) ? (float)$answer->score : null;

      if ($ans_score === null) {
        $status = 'pending';
        $badge_class = 'pending';
        $badge_text  = 'Pending review';
        $num_class   = 'pending';
      } elseif ($ans_score >= $pass_thr) {
        $status = 'correct';
        $badge_class = 'correct';
        $badge_text  = number_format($ans_score, 0) . '% correct';
        $num_class   = 'correct';
      } else {
        $status = 'wrong';
        $badge_class = 'wrong';
        $badge_text  = number_format($ans_score, 0) . '% correct';
        $num_class   = 'wrong';
      }

      if ($ans_text === '' && $answer === null) {
        $status = 'empty'; $badge_class = 'pending'; $badge_text = 'Not answered'; $num_class = 'no-answer';
      }

      $type_color = $type_colors[$q->question_type] ?? '#6dabcf';
    ?>
    <div class="res-q-card animate__animated animate__fadeInUp" style="animation-delay:<?= $idx * 0.04 ?>s;">
      <div class="res-q-header">
        <div style="display:flex;align-items:center;gap:.625rem;">
          <div class="res-q-num <?= $num_class ?>"><?= $idx + 1 ?></div>
          <span class="res-q-type" style="background:<?= $type_color ?>22;color:<?= $type_color ?>;">
            <?= ucfirst(str_replace('_',' ',$q->question_type)) ?>
          </span>
        </div>
        <span class="res-score-badge <?= $badge_class ?>"><?= $badge_text ?></span>
      </div>
      <div class="res-q-body">
        <div class="res-q-text"><?= nl2br(htmlspecialchars($q->question_text)) ?></div>

        <?php if ($q->question_type === 'multiple_choice' && ! empty($q->choices)): ?>
          <?php foreach ($q->choices as $c):
            $is_selected = (string)$c->id === (string)$ans_text;
            $is_correct  = (int)$c->is_correct === 1;
            $cls = '';
            $dot_color = '#e2e8f0';
            if ($is_selected && $is_correct) { $cls = 'selected correct'; $dot_color = '#22c55e'; }
            elseif ($is_selected && ! $is_correct) { $cls = 'selected wrong'; $dot_color = '#dc2626'; }
            elseif ( ! $is_selected && $is_correct) { $cls = 'correct-ans'; $dot_color = '#22c55e'; }
          ?>
          <div class="res-choice <?= $cls ?>">
            <div class="res-choice-dot" style="background:<?= $dot_color ?>"></div>
            <?= htmlspecialchars($c->choice_text) ?>
            <?php if ($is_selected): ?><span style="margin-left:auto;font-size:.6875rem;font-weight:700;"><?= $is_correct ? '✓ Your answer' : '✗ Your answer' ?></span><?php endif; ?>
            <?php if ( ! $is_selected && $is_correct): ?><span style="margin-left:auto;font-size:.6875rem;font-weight:700;">✓ Correct</span><?php endif; ?>
          </div>
          <?php endforeach; ?>

        <?php else: ?>
          <?php if ($ans_text !== ''): ?>
            <div class="res-answer-block <?= $status ?>">
              <strong>Your answer:</strong><br><?= nl2br(htmlspecialchars($ans_text)) ?>
            </div>
          <?php else: ?>
            <div class="res-answer-block empty">No answer submitted.</div>
          <?php endif; ?>

          <?php if ($q->question_type === 'fill_blank' && ! empty($q->choices)): ?>
            <?php foreach ($q->choices as $c): if ($c->is_correct): ?>
              <div class="res-correct-answer">✓ Correct answer: <?= htmlspecialchars($c->choice_text) ?></div>
            <?php endif; endforeach; ?>
          <?php endif; ?>

          <?php if ($status === 'pending' && $q->question_type === 'essay'): ?>
            <div style="margin-top:.625rem;font-size:.75rem;color:#92400e;font-style:italic;">
              ⏳ This answer is pending instructor review and scoring.
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($answer && $answer->checked_by && $answer->checked_at): ?>
          <div style="margin-top:.75rem;font-size:.6875rem;color:var(--ka-text-muted,#64748b);">
            Graded by instructor on <?= date('M j, Y g:i A', strtotime($answer->checked_at)) ?>
          </div>
        <?php endif; ?>

      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Sidebar -->
  <div style="position:sticky;top:80px;">

    <div class="res-panel animate__animated animate__fadeInUp animate__fast">
      <div class="res-panel-hdr"><h3 class="res-panel-title">Assessment Info</h3></div>
      <div class="res-panel-body">
        <div class="res-info-item">
          <span class="res-info-label">Assessment</span>
          <span class="res-info-value" style="font-size:.75rem;max-width:120px;text-align:right;"><?= htmlspecialchars($assessment->title) ?></span>
        </div>
        <div class="res-info-item">
          <span class="res-info-label">Type</span>
          <span class="res-info-value"><?= $assessment->type === 'pre' ? 'Pre' : 'Post' ?></span>
        </div>
        <div class="res-info-item">
          <span class="res-info-label">Module</span>
          <span class="res-info-value" style="font-size:.75rem;max-width:120px;text-align:right;"><?= htmlspecialchars($assessment->module_title ?? '—') ?></span>
        </div>
        <div class="res-info-item">
          <span class="res-info-label">Questions</span>
          <span class="res-info-value"><?= $total_q ?></span>
        </div>
        <div class="res-info-item">
          <span class="res-info-label">Score</span>
          <span class="res-info-value" style="color:<?= $has_pending ? '#d97706' : ($passed ? '#059669' : '#dc2626') ?>">
            <?= $has_pending ? 'Pending' : number_format($score, 1).'%' ?>
          </span>
        </div>
        <div class="res-info-item">
          <span class="res-info-label">Status</span>
          <span class="res-info-value" style="color:<?= $has_pending ? '#d97706' : ($passed ? '#059669' : '#dc2626') ?>">
            <?= $has_pending ? 'Under Review' : ($passed ? 'Passed' : 'Failed') ?>
          </span>
        </div>
      </div>
    </div>

    <div class="res-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.05s;">
      <div class="res-panel-body">
        <a href="<?= base_url('index.php/assessments') ?>" class="res-back-btn">← Back to Assessments</a>
        <a href="<?= base_url('index.php/courses/view/'.$assessment->course_id) ?>"
           style="display:block;text-align:center;margin-top:.625rem;font-size:.75rem;color:var(--ka-text-muted,#64748b);text-decoration:none;">
          View Course
        </a>
      </div>
    </div>

  </div>
</div>