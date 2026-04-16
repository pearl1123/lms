<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$assessment = $assessment ?? null;
$questions  = $questions  ?? [];
if ( ! $assessment) return;
$total = count($questions);
?>
<?php echo $alerts_partial_html ?? ''; ?>

<style>
.take-hero {
  background:linear-gradient(135deg,var(--ka-navy,#1a3a5c) 0%,#254d75 60%,#2d6a9f 100%);
  border-radius:16px;padding:1.75rem 2rem;margin-bottom:1.75rem;
  position:relative;overflow:hidden;box-shadow:0 8px 32px rgba(26,58,92,.18);
}
.take-hero::before { content:'';position:absolute;top:-60px;right:-60px;width:220px;height:220px;border-radius:50%;background:rgba(109,171,207,.1);pointer-events:none; }
.take-hero-body { position:relative;z-index:1; }
.take-hero-type { font-size:.6875rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.5);margin-bottom:.375rem; }
.take-hero-title { font-size:1.5rem;font-weight:800;color:#fff;margin:0 0 .375rem;letter-spacing:-.02em; }
.take-hero-meta { display:flex;gap:1.25rem;flex-wrap:wrap; }
.take-hero-meta-item { display:flex;align-items:center;gap:5px;font-size:.75rem;color:rgba(255,255,255,.65); }
.take-hero-meta-item svg { width:13px;height:13px; }

/* Progress bar at top */
.take-progress-bar {
  height:4px;background:rgba(255,255,255,.15);border-radius:2px;margin-top:1.25rem;overflow:hidden;
}
.take-progress-fill { height:100%;background:var(--ka-primary,#6dabcf);border-radius:2px;transition:width .3s ease; }

/* Layout */
.take-layout { display:grid;grid-template-columns:1fr 280px;gap:1.25rem;align-items:start; }
@media(max-width:991.98px){ .take-layout { grid-template-columns:1fr; } }

/* Question cards */
.take-questions { display:flex;flex-direction:column;gap:1.125rem; }
.take-q-card {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;overflow:hidden;
  transition:box-shadow .2s;
}
.take-q-card.answered { border-color:var(--ka-primary,#6dabcf); }
.take-q-header {
  padding:.875rem 1.125rem;
  border-bottom:1px solid var(--ka-border,#e2e8f0);
  display:flex;align-items:center;justify-content:space-between;
  background:var(--ka-bg,#f8fafc);
}
.take-q-num {
  width:28px;height:28px;border-radius:50%;
  background:var(--ka-navy,#1a3a5c);color:#fff;
  display:flex;align-items:center;justify-content:center;
  font-size:.6875rem;font-weight:700;flex-shrink:0;
}
.take-q-num.answered { background:var(--ka-primary,#6dabcf); }
.take-q-type {
  font-size:.625rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
  padding:2px 8px;border-radius:20px;
}
.take-q-required { font-size:.625rem;font-weight:700;color:#dc2626; }
.take-q-body { padding:1.125rem; }
.take-q-text { font-size:.9375rem;font-weight:600;color:var(--ka-text,#1e293b);line-height:1.5;margin-bottom:1rem; }

/* Multiple choice */
.take-choices { display:flex;flex-direction:column;gap:.5rem; }
.take-choice-label {
  display:flex;align-items:center;gap:.75rem;
  padding:.75rem 1rem;border-radius:9px;
  border:1.5px solid var(--ka-border,#e2e8f0);
  cursor:pointer;transition:all .15s;
  font-size:.875rem;font-weight:500;color:var(--ka-text,#1e293b);
}
.take-choice-label:hover { border-color:var(--ka-primary,#6dabcf);background:var(--ka-accent,#e8f4fd); }
.take-choice-label input { display:none; }
.take-choice-radio {
  width:18px;height:18px;border-radius:50%;border:2px solid var(--ka-border,#e2e8f0);
  flex-shrink:0;transition:all .15s;display:flex;align-items:center;justify-content:center;
}
.take-choice-label input:checked ~ .take-choice-radio { border-color:var(--ka-primary,#6dabcf);background:var(--ka-primary,#6dabcf); }
.take-choice-label input:checked ~ .take-choice-radio::after {
  content:'';width:6px;height:6px;border-radius:50%;background:#fff;
}
.take-choice-label:has(input:checked) { border-color:var(--ka-primary,#6dabcf);background:var(--ka-accent,#e8f4fd); }

/* Essay */
.take-essay {
  width:100%;min-height:120px;padding:.75rem;
  border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;
  font-size:.875rem;color:var(--ka-text,#1e293b);resize:vertical;
  font-family:inherit;transition:border-color .2s;outline:none;
}
.take-essay:focus { border-color:var(--ka-primary,#6dabcf);box-shadow:0 0 0 3px rgba(109,171,207,.15); }
.take-word-count { font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin-top:4px;text-align:right; }

/* Fill blank */
.take-fill-input {
  width:100%;height:42px;padding:0 .875rem;
  border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;
  font-size:.875rem;color:var(--ka-text,#1e293b);outline:none;transition:border-color .2s;
}
.take-fill-input:focus { border-color:var(--ka-primary,#6dabcf);box-shadow:0 0 0 3px rgba(109,171,207,.15); }

/* Likert scale */
.take-likert { display:flex;gap:.5rem;flex-wrap:wrap; }
.take-likert-label {
  flex:1;min-width:52px;text-align:center;cursor:pointer;
}
.take-likert-label input { display:none; }
.take-likert-btn {
  display:block;padding:.625rem .5rem;border-radius:8px;
  border:1.5px solid var(--ka-border,#e2e8f0);
  font-size:.875rem;font-weight:600;color:var(--ka-text-muted,#64748b);
  transition:all .15s;
}
.take-likert-label input:checked + .take-likert-btn,
.take-likert-label:has(input:checked) .take-likert-btn {
  border-color:var(--ka-navy,#1a3a5c);background:var(--ka-navy,#1a3a5c);color:#fff;
}
.take-likert-labels { display:flex;justify-content:space-between;margin-top:.375rem; }
.take-likert-labels span { font-size:.5625rem;color:var(--ka-text-muted,#64748b); }

/* Sidebar */
.take-sidebar { position:sticky;top:80px; }
.take-side-panel {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;overflow:hidden;margin-bottom:1rem;
}
.take-side-hdr { padding:.875rem 1rem;border-bottom:1px solid var(--ka-border,#e2e8f0); }
.take-side-title { font-size:.875rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0; }
.take-side-body { padding:1rem; }
.take-q-nav { display:grid;grid-template-columns:repeat(5,1fr);gap:5px; }
.take-q-nav-btn {
  width:100%;aspect-ratio:1;border-radius:7px;border:1.5px solid var(--ka-border,#e2e8f0);
  background:var(--ka-bg,#f8fafc);font-size:.6875rem;font-weight:700;
  cursor:pointer;transition:all .15s;color:var(--ka-text-muted,#64748b);
}
.take-q-nav-btn.answered { background:var(--ka-primary,#6dabcf);border-color:var(--ka-primary,#6dabcf);color:#fff; }
.take-q-nav-btn:hover { border-color:var(--ka-primary,#6dabcf);color:var(--ka-primary,#6dabcf); }

.take-submit-btn {
  display:block;width:100%;padding:.875rem;border-radius:9px;
  background:var(--ka-navy,#1a3a5c);color:#fff;border:none;cursor:pointer;
  font-size:.9375rem;font-weight:700;transition:all .2s;
}
.take-submit-btn:hover { background:#254d75;transform:translateY(-1px); }
.take-submit-btn:disabled { opacity:.6;cursor:not-allowed;transform:none; }
</style>

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

<script>
var answeredSet = new Set();
var total       = <?= $total ?>;

function markAnswered(qid) {
  answeredSet.add(qid);
  document.getElementById('qnum-' + qid)?.classList.add('answered');
  document.getElementById('qnav-' + qid)?.classList.add('answered');
  document.getElementById('qcard-' + qid)?.classList.add('answered');
  updateCounts();
}

function updateCounts() {
  var n = answeredSet.size;
  var pct = total > 0 ? Math.round((n / total) * 100) : 0;
  document.getElementById('takeProgressFill').style.width = pct + '%';
  document.getElementById('answeredNum').textContent        = n;
  document.getElementById('sideAnsweredCount').querySelector('strong').textContent = n;
  document.getElementById('bottomAnsweredCount').textContent = n + ' of ' + total + ' answered';
}

function updateWordCount(el, qid) {
  var words   = el.value.trim() === '' ? 0 : el.value.trim().split(/\s+/).length;
  var minW    = parseInt(el.dataset.minwords) || 0;
  var el2     = document.getElementById('wc-' + qid);
  if (el2) {
    el2.textContent = words + ' word' + (words !== 1 ? 's' : '')
                    + (minW ? ' (minimum ' + minW + ')' : '');
    el2.style.color = (minW && words < minW) ? '#dc2626' : '#64748b';
  }
}

function confirmSubmit() {
  var unanswered = total - answeredSet.size;
  var msg = unanswered > 0
    ? unanswered + ' question(s) are still unanswered. Submit anyway?'
    : 'Are you sure you want to submit? You cannot change your answers after submission.';

  KA.confirm({
    title:       'Submit Assessment?',
    text:        msg,
    confirmText: 'Yes, submit now',
    cancelText:  'Review answers',
    type:        unanswered > 0 ? 'warning' : 'info',
    onConfirm:   function() { document.getElementById('takeForm').submit(); },
  });
}
</script>