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

<link rel="stylesheet" href="<?= base_url('assets/css/assessments.css') ?>">

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