<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$assessment = $assessment ?? null;
$questions  = $questions  ?? [];
$modules    = $modules    ?? [];
if ( ! $assessment) return;
$checkpoint_schema_ready = ! empty($checkpoint_schema_ready);
$is_checkpoint = ($assessment->type === 'checkpoint');
$cp_trigger_seconds = '';
if ($is_checkpoint && $checkpoint_schema_ready
    && isset($assessment->trigger_type) && $assessment->trigger_type === 'seconds') {
    $cp_trigger_seconds = (string) max(0, (int) round((float) ($assessment->trigger_value ?? 0)));
}
$cp_required = $is_checkpoint && $checkpoint_schema_ready && ! empty($assessment->is_required);
$cp_sort      = ($is_checkpoint && $checkpoint_schema_ready) ? (int) ($assessment->sort_order ?? 0) : 0;
$q_types = ['multiple_choice'=>'Multiple Choice','essay'=>'Essay','likert'=>'Likert Scale','fill_blank'=>'Fill in the Blank'];
$type_colors = ['multiple_choice'=>'#3b82f6','essay'=>'#f59f00','likert'=>'#22c55e','fill_blank'=>'#6dabcf'];
$asmt_type_colors = ['pre' => '#3b82f6', 'post' => '#22c55e', 'checkpoint' => '#f97316'];
$asmt_type_labels = ['pre' => 'Pre-Assessment', 'post' => 'Post-Assessment', 'checkpoint' => 'Video Checkpoint'];
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/assessments.css') ?>">

<!-- Topbar -->
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;" class="animate__animated animate__fadeIn animate__fast">
  <div>
    <h2 style="font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 3px;letter-spacing:-.02em;">
      Edit Assessment
    </h2>
    <p style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0;">
      <?= htmlspecialchars($assessment->title) ?>
      &nbsp;·&nbsp;
      <?php
        $tl = $asmt_type_labels[$assessment->type] ?? ucfirst($assessment->type);
        $tc = $asmt_type_colors[$assessment->type] ?? '#6dabcf';
      ?>
      <span style="color:<?= $tc ?>;font-weight:600;"><?= htmlspecialchars($tl) ?></span>
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
        <form method="post" action="<?= base_url('index.php/assessments/edit/'.$assessment->id) ?>" id="assessmentMetaForm">
          <input type="hidden" name="<?= $csrf_field_name ?>" value="<?= $csrf_hash ?>">
          <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:.875rem;align-items:end;">
            <div class="edit-form-group" style="margin-bottom:0;">
              <label class="edit-label">Title</label>
              <input type="text" name="title" class="edit-input" value="<?= htmlspecialchars(set_value('title', $assessment->title)) ?>" required>
            </div>
            <div class="edit-form-group" style="margin-bottom:0;">
              <label class="edit-label">Type</label>
              <select name="type" id="editAssessmentType" class="edit-select" required onchange="onEditAssessmentTypeChange()">
                <option value="pre"  <?= set_select('type', 'pre', $assessment->type === 'pre') ?>>Pre-Assessment</option>
                <option value="post" <?= set_select('type', 'post', $assessment->type === 'post') ?>>Post-Assessment</option>
                <?php if ($checkpoint_schema_ready || $is_checkpoint): ?>
                <option value="checkpoint" <?= set_select('type', 'checkpoint', $is_checkpoint) ?>>Video Checkpoint</option>
                <?php endif; ?>
              </select>
            </div>
            <button type="submit" class="edit-save-btn">Save</button>
          </div>
          <div class="edit-form-group" style="margin-top:1rem;margin-bottom:0;">
            <label class="edit-label">Course module <span style="color:#dc2626;">*</span></label>
            <select name="module_id" id="editModuleId" class="edit-select" required>
              <option value="">— Select module —</option>
              <?php
              $cur_course = '';
              foreach ($modules as $m):
                if ($m->course_title !== $cur_course):
                  if ($cur_course !== '') echo '</optgroup>';
                  echo '<optgroup label="' . htmlspecialchars($m->course_title) . '">';
                  $cur_course = $m->course_title;
                endif;
              ?>
              <option value="<?= (int) $m->id ?>" <?= set_select('module_id', (string) $m->id, (int) $assessment->module_id === (int) $m->id) ?>>
                <?= htmlspecialchars($m->module_title) ?>
              </option>
              <?php endforeach; ?>
              <?php if ($cur_course !== '') echo '</optgroup>'; ?>
            </select>
            <?php if ($is_checkpoint && ! $checkpoint_schema_ready): ?>
              <p style="font-size:.6875rem;color:#b45309;margin:.5rem 0 0;">Checkpoint columns are missing on this database; save may not store trigger or required flags until migration is applied.</p>
            <?php endif; ?>
          </div>
          <input type="hidden" name="trigger_percent" id="edit_trigger_percent" value="0">
          <div class="edit-cp-fields <?= $is_checkpoint ? 'visible' : '' ?>" id="editCheckpointFields">
            <div class="edit-form-group">
              <label class="edit-label">Video timestamp (seconds)</label>
              <input type="number" name="trigger_seconds" class="edit-input" min="0" step="1"
                     placeholder="Optional — empty uses playback start"
                     value="<?= htmlspecialchars(set_value('trigger_seconds', $cp_trigger_seconds)) ?>">
            </div>
            <div class="edit-form-group">
              <label class="edit-cp-toggle">
                <input type="checkbox" name="checkpoint_required" value="1" <?= set_checkbox('checkpoint_required', '1', $cp_required) ?>>
                Required — learner must answer before continuing
              </label>
            </div>
            <div class="edit-form-group" style="margin-bottom:0;">
              <label class="edit-label">Display order among checkpoints</label>
              <input type="number" name="sort_order" class="edit-input" min="0" step="1"
                     value="<?= htmlspecialchars((string) set_value('sort_order', (string) $cp_sort)) ?>">
            </div>
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

        <?php $cp_at_max = $is_checkpoint && count($questions) >= 1; ?>
        <button type="button" class="add-q-btn" id="addQuestionBtn" onclick="openModal()"
                <?= $cp_at_max ? 'disabled title="Video checkpoints support one multiple-choice question. Edit or delete the existing question."' : '' ?>>
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Question
        </button>
        <?php if ($is_checkpoint): ?>
          <p style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin:.5rem 0 0;">
            Video checkpoints use a single multiple-choice question, shown in the module video player.
          </p>
        <?php endif; ?>

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
      <div class="mf-row" id="mfQTypeRow">
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

<?php $_jf = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT; ?>
<script>
kaApplyAppContext(<?= json_encode([
  'assessments' => [
    'edit' => [
      'assessmentId' => (int) $assessment->id,
      'csrfFieldName' => $csrf_field_name,
      'csrfHash' => $csrf_hash,
      'saveQuestionUrl' => base_url('index.php/assessments/save_question'),
      'deleteQuestionUrl' => base_url('index.php/assessments/delete_question'),
      'typeColors' => $type_colors,
      'typeLabels' => $q_types,
    ],
  ],
], $_jf) ?>);
</script>
<script src="<?= base_url('assets/js/assessments.js') ?>"></script>