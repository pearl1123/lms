<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$assessment = $assessment ?? null;
$questions  = $questions  ?? [];
if ( ! $assessment) return;
$total = count($questions);
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/assessments.css') ?>">

<!-- ══ Hero ═════════════════════════════════════════════════ -->
<div class="take-hero animate__animated animate__fadeIn animate__fast">
  <div class="take-hero-body">
    <div class="take-hero-type">
      <?= $assessment->type === 'pre' ? 'Pre-Assessment' : 'Post-Assessment' ?>
      &nbsp;·&nbsp; <?= htmlspecialchars($assessment->course_title ?? '') ?>
    </div>
    <h2 class="take-hero-title"><?= htmlspecialchars($assessment->title) ?></h2>
    <div class="take-hero-meta">
      <div class="take-hero-meta-item">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Module: <?= htmlspecialchars($assessment->module_title ?? '') ?>
      </div>
      <div class="take-hero-meta-item">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
        <?= $total ?> question<?= $total !== 1 ? 's' : '' ?>
      </div>
    </div>
    <div class="take-progress-bar">
      <div class="take-progress-fill" id="takeProgressFill" style="width:0%"></div>
    </div>
  </div>
</div>

<!-- ══ Form ══════════════════════════════════════════════════ -->
<form method="post" action="<?= base_url('index.php/assessments/submit/'.$assessment->id) ?>" id="takeForm">
   <input type="hidden" name="<?= $csrf_field_name ?>" value="<?= $csrf_hash ?>">

  <div class="take-layout">

    <!-- Questions -->
    <div class="take-questions">
      <?php foreach ($questions as $idx => $q): ?>
      <div class="take-q-card" id="qcard-<?= $q->id ?>" data-qid="<?= $q->id ?>">
        <div class="take-q-header">
          <div style="display:flex;align-items:center;gap:.625rem;">
            <div class="take-q-num" id="qnum-<?= $q->id ?>"><?= $idx + 1 ?></div>
            <span class="take-q-type" style="<?php
              $tc = ['multiple_choice'=>'background:#eff6ff;color:#3b82f6','essay'=>'background:#fffbeb;color:#b45309','likert'=>'background:#f0fdf4;color:#15803d','fill_blank'=>'background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf)'];
              echo $tc[$q->question_type] ?? '';
            ?>"><?= ucfirst(str_replace('_',' ',$q->question_type)) ?></span>
            <?php if ($q->is_required): ?>
              <span class="take-q-required">* Required</span>
            <?php endif; ?>
          </div>
          <span style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);">Q<?= $idx + 1 ?> of <?= $total ?></span>
        </div>

        <div class="take-q-body">
          <div class="take-q-text"><?= nl2br(htmlspecialchars($q->question_text)) ?></div>

          <?php if ($q->question_type === 'multiple_choice'): ?>
            <div class="take-choices">
              <?php foreach ($q->choices as $c): ?>
              <label class="take-choice-label">
                <input type="radio" name="answer_<?= $q->id ?>" value="<?= $c->id ?>"
                       onchange="markAnswered(<?= $q->id ?>)"
                       <?= $q->is_required ? 'required' : '' ?>>
                <div class="take-choice-radio"></div>
                <?= htmlspecialchars($c->choice_text) ?>
              </label>
              <?php endforeach; ?>
              <?php if (empty($q->choices)): ?>
                <p style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);font-style:italic;">No choices configured yet.</p>
              <?php endif; ?>
            </div>

          <?php elseif ($q->question_type === 'essay'): ?>
            <textarea class="take-essay"
                      name="answer_<?= $q->id ?>"
                      placeholder="Type your answer here…"
                      <?= $q->is_required ? 'required' : '' ?>
                      <?php if ($q->min_words): ?> data-minwords="<?= $q->min_words ?>"<?php endif; ?>
                      oninput="updateWordCount(this, <?= $q->id ?>); markAnswered(<?= $q->id ?>)"></textarea>
            <div class="take-word-count" id="wc-<?= $q->id ?>">
              0 words<?php if ($q->min_words): ?> (minimum <?= $q->min_words ?>)<?php endif; ?>
            </div>

          <?php elseif ($q->question_type === 'fill_blank'): ?>
            <input type="text" class="take-fill-input"
                   name="answer_<?= $q->id ?>"
                   placeholder="Type your answer…"
                   <?= $q->is_required ? 'required' : '' ?>
                   oninput="markAnswered(<?= $q->id ?>)">

          <?php elseif ($q->question_type === 'likert'): ?>
            <div class="take-likert" id="likert-<?= $q->id ?>">
              <?php for ($n = 1; $n <= 5; $n++): ?>
              <label class="take-likert-label">
                <input type="radio" name="answer_<?= $q->id ?>" value="<?= $n ?>"
                       <?= $q->is_required ? 'required' : '' ?>
                       onchange="markAnswered(<?= $q->id ?>)">
                <div class="take-likert-btn"><?= $n ?></div>
              </label>
              <?php endfor; ?>
            </div>
            <div class="take-likert-labels">
              <span>Strongly Disagree</span>
              <span>Strongly Agree</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Submit (bottom) -->
      <div style="background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;padding:1.25rem;display:flex;align-items:center;gap:1rem;">
        <div style="flex:1;">
          <div style="font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);">Ready to submit?</div>
          <div style="font-size:.75rem;color:var(--ka-text-muted,#64748b);margin-top:2px;" id="bottomAnsweredCount">0 of <?= $total ?> answered</div>
        </div>
        <button type="button" onclick="confirmSubmit()" class="take-submit-btn" style="width:auto;padding:.625rem 1.5rem;">
          Submit Assessment
        </button>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="take-sidebar">

      <!-- Question navigator -->
      <div class="take-side-panel">
        <div class="take-side-hdr">
          <h3 class="take-side-title">Questions</h3>
        </div>
        <div class="take-side-body">
          <div class="take-q-nav">
            <?php foreach ($questions as $idx => $q): ?>
            <button type="button" class="take-q-nav-btn" id="qnav-<?= $q->id ?>"
                    onclick="document.getElementById('qcard-<?= $q->id ?>').scrollIntoView({behavior:'smooth',block:'center'})">
              <?= $idx + 1 ?>
            </button>
            <?php endforeach; ?>
          </div>
          <div style="display:flex;align-items:center;gap:8px;margin-top:.875rem;font-size:.6875rem;color:var(--ka-text-muted,#64748b);">
            <div style="width:12px;height:12px;border-radius:3px;background:var(--ka-primary,#6dabcf);"></div> Answered
            <div style="width:12px;height:12px;border-radius:3px;background:var(--ka-bg,#f8fafc);border:1.5px solid var(--ka-border,#e2e8f0);"></div> Unanswered
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="take-side-panel">
        <div class="take-side-body">
          <div style="font-size:.75rem;color:var(--ka-text-muted,#64748b);margin-bottom:.75rem;" id="sideAnsweredCount">
            <strong style="color:var(--ka-text,#1e293b);" id="answeredNum">0</strong> of <?= $total ?> answered
          </div>
          <button type="button" onclick="confirmSubmit()" class="take-submit-btn">
            Submit Assessment
          </button>
          <a href="<?= base_url('index.php/assessments') ?>"
             style="display:block;text-align:center;margin-top:.625rem;font-size:.75rem;color:var(--ka-text-muted,#64748b);text-decoration:none;">
            Save &amp; return later
          </a>
        </div>
      </div>

    </div>
  </div>
</form>

<?php $_jf = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT; ?>
<script>
kaApplyAppContext(<?= json_encode([
  'assessments' => [
    'take' => ['totalQuestions' => (int) $total],
  ],
], $_jf) ?>);
</script>
<script src="<?= base_url('assets/js/assessments.js') ?>"></script>
