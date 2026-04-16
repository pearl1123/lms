<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$assessment = $assessment ?? null;
$questions  = $questions  ?? [];
$modules    = $modules    ?? [];
if ( ! $assessment) return;
$q_types = ['multiple_choice'=>'Multiple Choice','essay'=>'Essay','likert'=>'Likert Scale','fill_blank'=>'Fill in the Blank'];
$type_colors = ['multiple_choice'=>'#3b82f6','essay'=>'#f59f00','likert'=>'#22c55e','fill_blank'=>'#6dabcf'];
?>
<?php echo $alerts_partial_html ?? ''; ?>

<style>
/* Layout */
.edit-layout { display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start; }
@media(max-width:1099.98px){ .edit-layout { grid-template-columns:1fr; } }

/* Panel */
.edit-panel { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;overflow:hidden;margin-bottom:1.25rem; }
.edit-panel-hdr { padding:1rem 1.25rem;border-bottom:1px solid var(--ka-border,#e2e8f0);display:flex;align-items:center;justify-content:space-between; }
.edit-panel-title { font-size:.9rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0; }
.edit-panel-body { padding:1.25rem; }

/* Form fields */
.edit-form-group { margin-bottom:1.125rem; }
.edit-label { display:block;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:.425rem; }
.edit-input, .edit-select {
  width:100%;height:40px;padding:0 .875rem;
  border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;
  font-size:.875rem;color:var(--ka-text,#1e293b);background:var(--ka-bg,#f8fafc);
  outline:none;transition:all .2s;
}
.edit-input:focus, .edit-select:focus { border-color:var(--ka-primary,#6dabcf);background:#fff;box-shadow:0 0 0 3px rgba(109,171,207,.15); }
.edit-select { cursor:pointer; }
.edit-save-btn { padding:.5625rem 1.125rem;border-radius:8px;background:var(--ka-navy,#1a3a5c);color:#fff;border:none;cursor:pointer;font-size:.8125rem;font-weight:700;transition:all .15s; }
.edit-save-btn:hover { background:#254d75; }

/* Question list */
.q-list { display:flex;flex-direction:column;gap:.75rem; }
.q-item {
  border:1.5px solid var(--ka-border,#e2e8f0);border-radius:12px;overflow:hidden;
  transition:box-shadow .15s;
}
.q-item:hover { box-shadow:0 4px 16px rgba(0,0,0,.07); }
.q-item-hdr {
  padding:.75rem 1rem;background:var(--ka-bg,#f8fafc);
  border-bottom:1.5px solid var(--ka-border,#e2e8f0);
  display:flex;align-items:center;gap:.625rem;
}
.q-item-num { width:26px;height:26px;border-radius:50%;background:var(--ka-navy,#1a3a5c);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.6875rem;font-weight:700;flex-shrink:0; }
.q-item-type { font-size:.625rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:2px 8px;border-radius:20px; }
.q-item-actions { margin-left:auto;display:flex;gap:.375rem; }
.q-action-btn { width:28px;height:28px;border-radius:7px;border:1.5px solid var(--ka-border,#e2e8f0);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--ka-text-muted,#64748b);transition:all .15s; }
.q-action-btn:hover { background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);border-color:var(--ka-primary,#6dabcf); }
.q-action-btn.danger:hover { background:#fef2f2;color:#dc2626;border-color:#dc2626; }
.q-action-btn svg { width:13px;height:13px; }
.q-item-body { padding:.875rem 1rem; }
.q-text { font-size:.875rem;font-weight:600;color:var(--ka-text,#1e293b);line-height:1.4;margin-bottom:.375rem; }
.q-meta { font-size:.6875rem;color:var(--ka-text-muted,#64748b);display:flex;align-items:center;gap:.625rem; }
.q-choices-preview { margin-top:.5rem;display:flex;flex-wrap:wrap;gap:.375rem; }
.q-choice-chip { padding:2px 8px;border-radius:20px;font-size:.625rem;font-weight:600; }
.q-choice-chip.correct { background:#ecfdf5;color:#065f46; }
.q-choice-chip.wrong   { background:var(--ka-bg,#f8fafc);color:var(--ka-text-muted,#64748b); }

/* Add question button */
.add-q-btn {
  display:flex;align-items:center;justify-content:center;gap:.5rem;
  width:100%;padding:.875rem;border-radius:10px;
  border:2px dashed var(--ka-border,#e2e8f0);background:transparent;
  cursor:pointer;font-size:.875rem;font-weight:600;color:var(--ka-text-muted,#64748b);
  transition:all .18s;margin-top:.625rem;
}
.add-q-btn:hover { border-color:var(--ka-primary,#6dabcf);color:var(--ka-primary,#6dabcf);background:var(--ka-accent,#e8f4fd); }

/* Modal overlay */
.q-modal-overlay {
  display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);
  z-index:2000;backdrop-filter:blur(3px);
  align-items:center;justify-content:center;padding:1rem;
}
.q-modal-overlay.open { display:flex; }
.q-modal {
  background:#fff;border-radius:16px;width:100%;max-width:620px;
  max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.18);
  animation:modalIn .25s ease;
}
@keyframes modalIn { from{opacity:0;transform:translateY(-16px)} to{opacity:1;transform:translateY(0)} }
.q-modal-hdr {
  padding:1.25rem 1.5rem;border-bottom:1px solid var(--ka-border,#e2e8f0);
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;background:#fff;z-index:1;border-radius:16px 16px 0 0;
}
.q-modal-title { font-size:1rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0; }
.q-modal-close { width:32px;height:32px;border-radius:8px;border:1.5px solid var(--ka-border,#e2e8f0);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--ka-text-muted,#64748b);transition:all .15s; }
.q-modal-close:hover { background:#fef2f2;color:#dc2626;border-color:#dc2626; }
.q-modal-close svg { width:15px;height:15px; }
.q-modal-body { padding:1.5rem; }
.q-modal-footer { padding:1rem 1.5rem;border-top:1px solid var(--ka-border,#e2e8f0);display:flex;align-items:center;justify-content:flex-end;gap:.625rem;position:sticky;bottom:0;background:#fff;border-radius:0 0 16px 16px; }
.q-modal-cancel { padding:.5rem 1.125rem;border-radius:8px;border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;cursor:pointer;font-size:.8125rem;font-weight:600;color:var(--ka-text-muted,#64748b);transition:all .15s; }
.q-modal-cancel:hover { border-color:var(--ka-text-muted,#64748b); }
.q-modal-save { padding:.5rem 1.25rem;border-radius:8px;background:var(--ka-navy,#1a3a5c);color:#fff;border:none;cursor:pointer;font-size:.8125rem;font-weight:700;transition:all .15s; }
.q-modal-save:hover { background:#254d75; }

/* Modal form fields */
.mf-group { margin-bottom:1.125rem; }
.mf-label { display:block;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:.425rem; }
.mf-input, .mf-select, .mf-textarea {
  width:100%;padding:.625rem .875rem;
  border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;
  font-size:.875rem;color:var(--ka-text,#1e293b);background:var(--ka-bg,#f8fafc);
  outline:none;font-family:inherit;transition:all .2s;
}
.mf-input:focus, .mf-select:focus, .mf-textarea:focus { border-color:var(--ka-primary,#6dabcf);background:#fff;box-shadow:0 0 0 3px rgba(109,171,207,.15); }
.mf-select { height:40px;cursor:pointer; }
.mf-textarea { min-height:90px;resize:vertical; }
.mf-check-label { display:flex;align-items:center;gap:.5rem;font-size:.8125rem;cursor:pointer;color:var(--ka-text,#1e293b); }
.mf-row { display:grid;grid-template-columns:1fr 1fr;gap:.875rem; }

/* Choices builder */
.choices-list { display:flex;flex-direction:column;gap:.5rem;margin-bottom:.625rem; }
.choice-row { display:flex;align-items:center;gap:.5rem; }
.choice-text-input { flex:1;height:36px;padding:0 .75rem;border:1.5px solid var(--ka-border,#e2e8f0);border-radius:7px;font-size:.8125rem;outline:none;transition:all .2s; }
.choice-text-input:focus { border-color:var(--ka-primary,#6dabcf);box-shadow:0 0 0 3px rgba(109,171,207,.15); }
.choice-correct-btn { width:28px;height:28px;border-radius:7px;border:1.5px solid var(--ka-border,#e2e8f0);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--ka-text-muted,#64748b);transition:all .15s;flex-shrink:0; }
.choice-correct-btn.active { background:#ecfdf5;border-color:#22c55e;color:#059669; }
.choice-correct-btn svg { width:14px;height:14px; }
.choice-del-btn { width:28px;height:28px;border-radius:7px;border:1.5px solid var(--ka-border,#e2e8f0);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--ka-text-muted,#64748b);transition:all .15s;flex-shrink:0; }
.choice-del-btn:hover { background:#fef2f2;border-color:#dc2626;color:#dc2626; }
.add-choice-btn { font-size:.75rem;font-weight:600;color:var(--ka-primary,#6dabcf);border:none;background:none;cursor:pointer;padding:.25rem 0;text-decoration:underline; }
</style>

<!-- Topbar -->
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;" class="animate__animated animate__fadeIn animate__fast">
  <div>
    <h2 style="font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 3px;letter-spacing:-.02em;">
      Edit Assessment
    </h2>
    <p style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0;">
      <?= htmlspecialchars($assessment->title) ?>
      &nbsp;·&nbsp;
      <span style="color:<?= $assessment->type === 'pre' ? '#3b82f6' : '#22c55e' ?>;font-weight:600;">
        <?= $assessment->type === 'pre' ? 'Pre-Assessment' : 'Post-Assessment' ?>
      </span>
    </p>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
    <a href="<?= base_url('index.php/assessments/review/'.$assessment->id) ?>"
       style="display:inline-flex;align-items:center;gap:6px;padding:.5rem 1rem;border-radius:8px;font-size:.8125rem;font-weight:600;text-decoration:none;border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;color:var(--ka-text,#1e293b);">
      View Submissions
    </a>
    <a href="<?= base_url('index.php/assessments') ?>"
       style="display:inline-flex;align-items:center;gap:6px;padding:.5rem 1rem;border-radius:8px;font-size:.8125rem;font-weight:600;text-decoration:none;border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;color:var(--ka-text,#1e293b);">
      ← Back
    </a>
  </div>
</div>

<div class="edit-layout animate__animated animate__fadeInUp animate__fast">

  <!-- LEFT: Questions builder -->
  <div>

    <!-- Assessment info edit form -->
    <div class="edit-panel" style="margin-bottom:1.25rem;">
      <div class="edit-panel-hdr">
        <h3 class="edit-panel-title">Assessment Info</h3>
      </div>
      <div class="edit-panel-body">
        <form method="post" action="<?= base_url('index.php/assessments/edit/'.$assessment->id) ?>">
          <input type="hidden" name="<?= $csrf_field_name ?>" value="<?= $csrf_hash ?>">
          <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:.875rem;align-items:end;">
            <div class="edit-form-group" style="margin-bottom:0;">
              <label class="edit-label">Title</label>
              <input type="text" name="title" class="edit-input" value="<?= htmlspecialchars($assessment->title) ?>" required>
            </div>
            <div class="edit-form-group" style="margin-bottom:0;">
              <label class="edit-label">Type</label>
              <select name="type" class="edit-select" required>
                <option value="pre"  <?= $assessment->type === 'pre'  ? 'selected' : '' ?>>Pre-Assessment</option>
                <option value="post" <?= $assessment->type === 'post' ? 'selected' : '' ?>>Post-Assessment</option>
              </select>
            </div>
            <button type="submit" class="edit-save-btn">Save</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Questions -->
    <div class="edit-panel">
      <div class="edit-panel-hdr">
        <h3 class="edit-panel-title">
          Questions
          <span style="font-size:.75rem;font-weight:500;color:var(--ka-text-muted,#64748b);margin-left:.375rem;" id="qCountLabel">
            <?= count($questions) ?> question<?= count($questions) !== 1 ? 's' : '' ?>
          </span>
        </h3>
      </div>
      <div class="edit-panel-body">

        <div class="q-list" id="qList">
          <?php if ( ! empty($questions)): ?>
            <?php foreach ($questions as $idx => $q):
              $tc = $type_colors[$q->question_type] ?? '#6dabcf';
            ?>
            <?= render_q_item($idx + 1, $q, $tc, $q_types) ?>
            <?php endforeach; ?>
          <?php else: ?>
            <div id="qEmpty" style="text-align:center;padding:2rem;color:var(--ka-text-muted,#64748b);font-size:.875rem;">
              No questions yet. Add your first question below.
            </div>
          <?php endif; ?>
        </div>

        <button type="button" class="add-q-btn" onclick="openModal()">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Question
        </button>

      </div>
    </div>
  </div>

  <!-- RIGHT: Summary sidebar -->
  <div style="position:sticky;top:80px;">
    <div class="edit-panel" style="margin-bottom:1rem;">
      <div class="edit-panel-hdr"><h3 class="edit-panel-title">Question Summary</h3></div>
      <div class="edit-panel-body">
        <?php
        $type_counts = array_fill_keys(array_keys($q_types), 0);
        foreach ($questions as $q) $type_counts[$q->question_type] = ($type_counts[$q->question_type] ?? 0) + 1;
        ?>
        <?php foreach ($q_types as $key => $label):
          $cnt   = $type_counts[$key] ?? 0;
          $color = $type_colors[$key];
        ?>
        <div style="display:flex;align-items:center;gap:.625rem;margin-bottom:.625rem;" id="typeSummary-<?= $key ?>">
          <div style="width:10px;height:10px;border-radius:3px;background:<?= $color ?>;flex-shrink:0;"></div>
          <span style="flex:1;font-size:.8125rem;color:var(--ka-text,#1e293b);"><?= $label ?></span>
          <span style="font-size:.8125rem;font-weight:700;color:var(--ka-text-muted,#64748b);" id="typeCount-<?= $key ?>"><?= $cnt ?></span>
        </div>
        <?php endforeach; ?>
        <div style="border-top:1px solid var(--ka-border,#e2e8f0);padding-top:.75rem;margin-top:.375rem;display:flex;justify-content:space-between;font-size:.8125rem;font-weight:700;color:var(--ka-text,#1e293b);">
          <span>Total</span>
          <span id="typeCountTotal"><?= count($questions) ?></span>
        </div>
      </div>
    </div>

    <div class="edit-panel">
      <div class="edit-panel-body">
        <a href="<?= base_url('index.php/assessments/review/'.$assessment->id) ?>"
           style="display:block;width:100%;padding:.75rem;border-radius:9px;background:var(--ka-navy,#1a3a5c);color:#fff;border:none;cursor:pointer;font-size:.875rem;font-weight:700;text-decoration:none;text-align:center;transition:all .2s;margin-bottom:.5rem;">
          View Submissions
        </a>
        <button onclick="KA.deleteConfirm('<?= base_url('index.php/assessments/delete/'.$assessment->id) ?>', '<?= htmlspecialchars(addslashes($assessment->title)) ?>')"
                style="display:block;width:100%;padding:.625rem;border-radius:9px;border:1.5px solid #fecaca;background:#fef2f2;color:#dc2626;font-size:.8125rem;font-weight:700;cursor:pointer;transition:all .15s;">
          Delete Assessment
        </button>
      </div>
    </div>
  </div>

</div>

<!-- ══ Question Modal ════════════════════════════════════════ -->
<div class="q-modal-overlay" id="qModalOverlay" onclick="closeModalOutside(event)">
  <div class="q-modal" id="qModal">
    <div class="q-modal-hdr">
      <h3 class="q-modal-title" id="modalTitle">Add Question</h3>
      <button type="button" class="q-modal-close" onclick="closeModal()">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="q-modal-body">
      <input type="hidden" id="modalQuestionId" value="0">

      <!-- Question text -->
      <div class="mf-group">
        <label class="mf-label" for="mfQText">Question Text <span style="color:#dc2626;">*</span></label>
        <textarea id="mfQText" class="mf-textarea" placeholder="Enter your question here…" rows="3"></textarea>
      </div>

      <!-- Type + settings row -->
      <div class="mf-row">
        <div class="mf-group">
          <label class="mf-label" for="mfQType">Question Type</label>
          <select id="mfQType" class="mf-select" onchange="onTypeChange()">
            <option value="multiple_choice">Multiple Choice</option>
            <option value="essay">Essay</option>
            <option value="likert">Likert Scale</option>
            <option value="fill_blank">Fill in the Blank</option>
          </select>
        </div>
        <div class="mf-group" id="mfMinWordsGroup" style="display:none;">
          <label class="mf-label" for="mfMinWords">Min. Words (optional)</label>
          <input type="number" id="mfMinWords" class="mf-input" min="0" placeholder="e.g. 50">
        </div>
      </div>

      <!-- Required checkbox -->
      <div class="mf-group">
        <label class="mf-check-label">
          <input type="checkbox" id="mfRequired" checked>
          Required question
        </label>
      </div>

      <!-- Choices builder (MC and fill blank) -->
      <div id="mfChoicesSection" class="mf-group">
        <label class="mf-label" id="mfChoicesLabel">Answer Choices</label>
        <div class="choices-list" id="choicesList"></div>
        <button type="button" class="add-choice-btn" onclick="addChoice()">+ Add choice</button>
        <div style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin-top:.375rem;" id="choiceHint">
          Click ✓ on a choice to mark it as the correct answer.
        </div>
      </div>

      <!-- Likert note -->
      <div id="mfLikertNote" style="display:none;background:var(--ka-accent,#e8f4fd);border-radius:8px;padding:.75rem 1rem;font-size:.8125rem;color:var(--ka-navy,#1a3a5c);">
        Likert scale presents students with a 1–5 rating. No choices needed.
      </div>

      <!-- Essay note -->
      <div id="mfEssayNote" style="display:none;background:#fffbeb;border-radius:8px;padding:.75rem 1rem;font-size:.8125rem;color:#78350f;">
        Essay answers require manual grading. Set a minimum word count if needed.
      </div>
    </div>
    <div class="q-modal-footer">
      <button type="button" class="q-modal-cancel" onclick="closeModal()">Cancel</button>
      <button type="button" class="q-modal-save" onclick="saveQuestion()">
        <span id="modalSaveText">Add Question</span>
      </button>
    </div>
  </div>
</div>

<?php
function render_q_item($num, $q, $tc, $q_types) {
  $label = $q_types[$q->question_type] ?? ucfirst($q->question_type);
  ob_start();
?>
<div class="q-item" id="qitem-<?= $q->id ?>"
     data-id="<?= $q->id ?>"
     data-text="<?= htmlspecialchars($q->question_text, ENT_QUOTES) ?>"
     data-type="<?= $q->question_type ?>"
     data-required="<?= $q->is_required ? '1' : '0' ?>"
     data-minwords="<?= $q->min_words ?? '' ?>"
     data-choices="<?= htmlspecialchars(json_encode(array_map(function($c) { return ['id' => $c->id, 'text' => $c->choice_text, 'is_correct' => $c->is_correct]; }, $q->choices ?? [])), ENT_QUOTES) ?>">
  <div class="q-item-hdr">
    <div class="q-item-num"><?= $num ?></div>
    <span class="q-item-type" style="background:<?= $tc ?>22;color:<?= $tc ?>"><?= $label ?></span>
    <?php if ($q->is_required): ?><span style="font-size:.625rem;font-weight:700;color:#dc2626;">* Required</span><?php endif; ?>
    <div class="q-item-actions">
      <button class="q-action-btn" title="Edit" onclick="editQuestion(<?= $q->id ?>)">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      </button>
      <button class="q-action-btn danger" title="Delete" onclick="deleteQuestion(<?= $q->id ?>)">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
      </button>
    </div>
  </div>
  <div class="q-item-body">
    <div class="q-text"><?= nl2br(htmlspecialchars($q->question_text)) ?></div>
    <div class="q-meta">
      <?php if ($q->min_words): ?>
        <span>Min <?= $q->min_words ?> words</span>
      <?php endif; ?>
    </div>
    <?php if ( ! empty($q->choices)): ?>
    <div class="q-choices-preview">
      <?php foreach ($q->choices as $c): ?>
        <span class="q-choice-chip <?= $c->is_correct ? 'correct' : 'wrong' ?>">
          <?= $c->is_correct ? '✓ ' : '' ?><?= htmlspecialchars($c->choice_text) ?>
        </span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php
  return ob_get_clean();
}
?>

<script>
var ASSESSMENT_ID = <?= $assessment->id ?>;
var BASE_URL      = '<?= base_url() ?>';
var CSRF_NAME     = '<?= $csrf_field_name ?>';
var CSRF_HASH     = '<?= $csrf_hash ?>';

// Question type colours for JS rendering
var TYPE_COLORS = <?= json_encode($type_colors) ?>;
var TYPE_LABELS = <?= json_encode($q_types) ?>;

// ── Modal open/close ─────────────────────────────────────────
function openModal(qid) {
  document.getElementById('qModalOverlay').classList.add('open');
  document.getElementById('modalQuestionId').value = qid || 0;
  document.getElementById('modalTitle').textContent = qid ? 'Edit Question' : 'Add Question';
  document.getElementById('modalSaveText').textContent = qid ? 'Update Question' : 'Add Question';

  if ( ! qid) {
    // Reset form
    document.getElementById('mfQText').value   = '';
    document.getElementById('mfQType').value   = 'multiple_choice';
    document.getElementById('mfRequired').checked = true;
    document.getElementById('mfMinWords').value = '';
    document.getElementById('choicesList').innerHTML = '';
    addChoice(); addChoice(); // Start with 2 empty choices
    onTypeChange();
  }
}

function editQuestion(qid) {
  var el = document.getElementById('qitem-' + qid);
  if ( ! el) return;

  document.getElementById('mfQText').value           = el.dataset.text;
  document.getElementById('mfQType').value           = el.dataset.type;
  document.getElementById('mfRequired').checked      = el.dataset.required === '1';
  document.getElementById('mfMinWords').value        = el.dataset.minwords || '';

  var choices = JSON.parse(el.dataset.choices || '[]');
  document.getElementById('choicesList').innerHTML = '';
  if (choices.length > 0) {
    choices.forEach(function(c) { addChoice(c.text, c.is_correct); });
  } else {
    addChoice(); addChoice();
  }

  onTypeChange();
  openModal(qid);
}

function closeModal() {
  document.getElementById('qModalOverlay').classList.remove('open');
}

function closeModalOutside(e) {
  if (e.target === document.getElementById('qModalOverlay')) closeModal();
}

// ── Type change handler ──────────────────────────────────────
function onTypeChange() {
  var type         = document.getElementById('mfQType').value;
  var choicesEl    = document.getElementById('mfChoicesSection');
  var minWEl       = document.getElementById('mfMinWordsGroup');
  var likertEl     = document.getElementById('mfLikertNote');
  var essayEl      = document.getElementById('mfEssayNote');
  var choicesLabel = document.getElementById('mfChoicesLabel');
  var choiceHint   = document.getElementById('choiceHint');

  choicesEl.style.display = 'none';
  minWEl.style.display    = 'none';
  likertEl.style.display  = 'none';
  essayEl.style.display   = 'none';

  if (type === 'multiple_choice') {
    choicesEl.style.display  = '';
    choicesLabel.textContent = 'Answer Choices (mark the correct one ✓)';
    choiceHint.textContent   = 'Click ✓ on a choice to mark it as the correct answer. Only one correct answer allowed.';
  } else if (type === 'fill_blank') {
    choicesEl.style.display  = '';
    choicesLabel.textContent = 'Accepted Answer';
    choiceHint.textContent   = 'Add the correct answer(s). Auto-scored by exact match (case-insensitive).';
  } else if (type === 'likert') {
    likertEl.style.display = '';
  } else if (type === 'essay') {
    essayEl.style.display  = '';
    minWEl.style.display   = '';
  }
}
onTypeChange();

// ── Choices builder ──────────────────────────────────────────
function addChoice(text, isCorrect) {
  var list  = document.getElementById('choicesList');
  var row   = document.createElement('div');
  row.className = 'choice-row';

  var inp = document.createElement('input');
  inp.type      = 'text';
  inp.className = 'choice-text-input';
  inp.placeholder = 'Choice text…';
  inp.value     = text || '';

  var correctBtn = document.createElement('button');
  correctBtn.type      = 'button';
  correctBtn.className = 'choice-correct-btn' + (isCorrect ? ' active' : '');
  correctBtn.title     = 'Mark as correct';
  correctBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>';
  correctBtn.onclick   = function() {
    // Only one correct for MC
    if (document.getElementById('mfQType').value === 'multiple_choice') {
      list.querySelectorAll('.choice-correct-btn').forEach(function(b) { b.classList.remove('active'); });
    }
    correctBtn.classList.toggle('active');
  };

  var delBtn = document.createElement('button');
  delBtn.type      = 'button';
  delBtn.className = 'choice-del-btn';
  delBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
  delBtn.onclick   = function() { row.remove(); };

  row.appendChild(inp);
  row.appendChild(correctBtn);
  row.appendChild(delBtn);
  list.appendChild(row);
  inp.focus();
}

// ── Save question (AJAX) ─────────────────────────────────────
function saveQuestion() {
  var qid  = parseInt(document.getElementById('modalQuestionId').value) || 0;
  var text = document.getElementById('mfQText').value.trim();
  var type = document.getElementById('mfQType').value;

  if ( ! text) { KA.toast('error', 'Please enter the question text.'); return; }

  // Build choices array
  var choices = [];
  document.querySelectorAll('#choicesList .choice-row').forEach(function(row) {
    var t  = row.querySelector('.choice-text-input').value.trim();
    var ok = row.querySelector('.choice-correct-btn').classList.contains('active');
    if (t) choices.push({ text: t, is_correct: ok ? 1 : 0 });
  });

  // Validate MC has a correct answer
  if (type === 'multiple_choice') {
    if (choices.length < 2) { KA.toast('error', 'Add at least 2 choices.'); return; }
    if ( ! choices.some(function(c) { return c.is_correct; })) {
      KA.toast('error', 'Mark one choice as the correct answer.'); return;
    }
  }
  if (type === 'fill_blank' && choices.length === 0) {
    KA.toast('error', 'Add at least one accepted answer.'); return;
  }

  var saveBtn = document.querySelector('.q-modal-save');
  saveBtn.disabled = true;
  document.getElementById('modalSaveText').textContent = 'Saving…';

  // Build form body
  var body = CSRF_NAME + '=' + CSRF_HASH
    + '&assessment_id=' + ASSESSMENT_ID
    + '&question_id='   + qid
    + '&question_text=' + encodeURIComponent(text)
    + '&question_type=' + type
    + '&is_required='   + (document.getElementById('mfRequired').checked ? 1 : 0)
    + '&min_words='     + (parseInt(document.getElementById('mfMinWords').value) || 0);

  choices.forEach(function(c, i) {
    body += '&choices[' + i + '][text]='       + encodeURIComponent(c.text);
    body += '&choices[' + i + '][is_correct]=' + c.is_correct;
  });

  fetch(BASE_URL + 'index.php/assessments/save_question', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body,
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    saveBtn.disabled = false;
    document.getElementById('modalSaveText').textContent = qid ? 'Update Question' : 'Add Question';

    if (data.success) {
      closeModal();
      KA.toast('success', data.message);
      renderQuestion(data.question, qid === 0);
    } else {
      KA.toast('error', data.message || 'Failed to save question.');
    }
  })
  .catch(function() {
    saveBtn.disabled = false;
    document.getElementById('modalSaveText').textContent = qid ? 'Update Question' : 'Add Question';
    KA.toast('error', 'Network error. Please try again.');
  });
}

// ── Render a question item into the DOM ──────────────────────
function renderQuestion(q, isNew) {
  var list    = document.getElementById('qList');
  var empty   = document.getElementById('qEmpty');
  if (empty) empty.remove();

  var existing = document.getElementById('qitem-' + q.id);
  var tc       = TYPE_COLORS[q.question_type] || '#6dabcf';
  var label    = TYPE_LABELS[q.question_type] || q.question_type;

  // Build choices preview HTML
  var choicesHtml = '';
  if (q.choices && q.choices.length > 0) {
    choicesHtml = '<div class="q-choices-preview">';
    q.choices.forEach(function(c) {
      var cls = c.is_correct ? 'correct' : 'wrong';
      var prefix = c.is_correct ? '✓ ' : '';
      choicesHtml += '<span class="q-choice-chip ' + cls + '">' + prefix + escHtml(c.choice_text) + '</span>';
    });
    choicesHtml += '</div>';
  }

  var choicesData = JSON.stringify((q.choices || []).map(function(c) {
    return { id: c.id, text: c.choice_text, is_correct: c.is_correct };
  }));

  var html = '<div class="q-item" id="qitem-' + q.id + '"'
    + ' data-id="'       + q.id + '"'
    + ' data-text="'     + escAttr(q.question_text) + '"'
    + ' data-type="'     + q.question_type + '"'
    + ' data-required="' + (q.is_required ? '1' : '0') + '"'
    + ' data-minwords="' + (q.min_words || '') + '"'
    + ' data-choices="'  + escAttr(choicesData) + '">'
    + '<div class="q-item-hdr">'
    + '<div class="q-item-num">?</div>'
    + '<span class="q-item-type" style="background:' + tc + '22;color:' + tc + '">' + label + '</span>'
    + (q.is_required ? '<span style="font-size:.625rem;font-weight:700;color:#dc2626;">* Required</span>' : '')
    + '<div class="q-item-actions">'
    + '<button class="q-action-btn" onclick="editQuestion(' + q.id + ')">'
    + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>'
    + '<button class="q-action-btn danger" onclick="deleteQuestion(' + q.id + ')">'
    + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>'
    + '</div></div>'
    + '<div class="q-item-body">'
    + '<div class="q-text">' + escHtml(q.question_text).replace(/\n/g,'<br>') + '</div>'
    + (q.min_words ? '<div class="q-meta"><span>Min ' + q.min_words + ' words</span></div>' : '')
    + choicesHtml
    + '</div></div>';

  if (existing) {
    existing.outerHTML = html;
  } else {
    list.insertAdjacentHTML('beforeend', html);
  }

  // Re-number all items
  updateNumbers();
  updateSummary();
}

function updateNumbers() {
  document.querySelectorAll('.q-item').forEach(function(el, i) {
    var num = el.querySelector('.q-item-num');
    if (num) num.textContent = i + 1;
  });
  var total = document.querySelectorAll('.q-item').length;
  document.getElementById('qCountLabel').textContent = total + ' question' + (total !== 1 ? 's' : '');
}

function updateSummary() {
  var counts = {};
  Object.keys(TYPE_LABELS).forEach(function(k) { counts[k] = 0; });
  document.querySelectorAll('.q-item').forEach(function(el) {
    var t = el.dataset.type;
    if (counts[t] !== undefined) counts[t]++;
  });
  Object.keys(counts).forEach(function(k) {
    var el = document.getElementById('typeCount-' + k);
    if (el) el.textContent = counts[k];
  });
  var total = document.querySelectorAll('.q-item').length;
  var totEl = document.getElementById('typeCountTotal');
  if (totEl) totEl.textContent = total;
}

// ── Delete question ──────────────────────────────────────────
function deleteQuestion(qid) {
  KA.confirm({
    title:       'Delete this question?',
    text:        'This will remove the question and all associated answers.',
    confirmText: 'Yes, delete',
    type:        'danger',
    onConfirm:   function() {
      fetch(BASE_URL + 'index.php/assessments/delete_question', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: CSRF_NAME + '=' + CSRF_HASH + '&question_id=' + qid,
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          var el = document.getElementById('qitem-' + qid);
          if (el) el.remove();
          KA.toast('success', 'Question deleted.');
          updateNumbers();
          updateSummary();
          if (document.querySelectorAll('.q-item').length === 0) {
            document.getElementById('qList').insertAdjacentHTML('beforeend',
              '<div id="qEmpty" style="text-align:center;padding:2rem;color:var(--ka-text-muted,#64748b);font-size:.875rem;">No questions yet. Add your first question below.</div>'
            );
          }
        } else {
          KA.toast('error', data.message || 'Failed to delete.');
        }
      });
    },
  });
}

// ── Escape helpers ───────────────────────────────────────────
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s) {
  return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// Close modal on Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeModal();
});
</script>