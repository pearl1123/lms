<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$assessment = $assessment ?? null;
$questions  = $questions  ?? [];
$modules    = $modules    ?? [];
$csrf_field_name = $csrf_field_name ?? '';
$csrf_hash       = $csrf_hash ?? '';
if ( ! $assessment) return;
$checkpoint_schema_ready = ! empty($checkpoint_schema_ready);
$is_checkpoint = ($assessment->type === 'checkpoint');
$use_workspace = ! empty($use_checkpoint_workspace) && ! empty($checkpoint_workspace);
$asmt_type_colors = ['pre' => '#3b82f6', 'post' => '#22c55e', 'checkpoint' => '#f97316'];
$asmt_type_labels = ['pre' => 'Pre-Assessment', 'post' => 'Post-Assessment', 'checkpoint' => 'Video Checkpoint'];
$q_types = ['multiple_choice'=>'Multiple Choice','essay'=>'Essay','likert'=>'Likert Scale','fill_blank'=>'Fill in the Blank'];
$type_colors = ['multiple_choice'=>'#3b82f6','essay'=>'#f59f00','likert'=>'#22c55e','fill_blank'=>'#6dabcf'];

$course_id = (int) ($assessment->course_id ?? 0);
$back_url  = $course_id > 0
    ? base_url('index.php/manage_courses/edit/' . $course_id)
    : base_url('index.php/assessments');
$back_label = $course_id > 0 ? 'Back to course' : 'All assessments';

if ( ! function_exists('render_q_item')) {
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
    <?php if ( ! empty($q->choices)): ?>
    <div class="q-choices-preview">
      <?php foreach ($q->choices as $c): ?>
        <span class="q-choice-chip <?= (int) $c->is_correct === 1 ? 'correct' : 'wrong' ?>">
          <?= (int) $c->is_correct === 1 ? '✓ ' : '' ?><?= htmlspecialchars($c->choice_text) ?>
        </span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
        <?php
        return ob_get_clean();
    }
}
?>
<?php echo $alerts_partial_html ?? ''; ?>

<?php
$shell_editor = [
    'mode'           => $use_workspace ? 'checkpoint' : 'exam',
    'title'          => $use_workspace
        ? 'Video checkpoint workspace'
        : ($assessment->title ?? 'Assessment'),
    'subtitle'       => $use_workspace
        ? (($checkpoint_workspace['course_title'] ?? '') . ' · ' . ($checkpoint_workspace['module_title'] ?? ''))
        : (($assessment->course_title ?? '') . ' · ' . ($assessment->module_title ?? '')),
    'type_label'     => $asmt_type_labels[$assessment->type] ?? ucfirst($assessment->type),
    'type_color'     => $asmt_type_colors[$assessment->type] ?? '#6dabcf',
    'status'         => 'Draft',
    'back_url'       => $back_url,
    'back_label'     => $back_label,
    'assessment_id'  => (int) $assessment->id,
    'save_form_id'   => $use_workspace ? '' : 'assessmentMetaForm',
    'save_label'     => 'Save assessment',
];

if ($use_workspace) {
    $has_cp_panels = ! empty($checkpoint_workspace['panels']);
    $shell_editor['nav'] = [
        ['id' => 'overview', 'label' => 'Overview', 'icon' => '◆', 'active' => ! $has_cp_panels],
        ['id' => 'content', 'label' => 'Checkpoints', 'icon' => '▶', 'active' => $has_cp_panels],
        ['id' => 'video', 'label' => 'Video preview', 'icon' => '▣', 'active' => false, 'hidden' => empty($checkpoint_workspace['youtube_id'])],
        ['id' => 'settings', 'label' => 'Settings', 'icon' => '⚙', 'active' => false],
        ['id' => 'publish', 'label' => 'Publish', 'icon' => '↗', 'active' => false],
    ];
    $shell_partial      = 'assessments/_checkpoint_workspace_content';
    $shell_rail_partial = ! empty($checkpoint_workspace['panels']) ? 'assessments/_checkpoint_navigator_rail' : '';
} else {
    $shell_editor['nav'] = [
        ['id' => 'overview', 'label' => 'Overview', 'icon' => '◆', 'active' => true],
        ['id' => 'content', 'label' => 'Questions', 'icon' => '✎', 'active' => false],
        ['id' => 'settings', 'label' => 'Settings', 'icon' => '⚙', 'active' => false],
        ['id' => 'publish', 'label' => 'Publish', 'icon' => '↗', 'active' => false],
    ];
    $shell_partial      = 'assessments/_edit_exam_content';
    $shell_rail_partial = '';
}

$this->load->view('assessments/_assessment_editor_shell', get_defined_vars());
?>

<?php if ( ! $use_workspace): ?>
<!-- Question modal (pre/post exams) -->
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
      <div id="questionFormStack">
      <div class="question-form-block is-active" id="questionFormBlock0" data-q-block="0">
      <div class="mf-group">
        <label class="mf-label" for="mfQText">Question Text <span style="color:#dc2626;">*</span></label>
        <textarea id="mfQText" class="mf-textarea" placeholder="Enter your question here…" rows="3"></textarea>
      </div>
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
      <div class="mf-group">
        <label class="mf-check-label">
          <input type="checkbox" id="mfRequired" checked> Required question
        </label>
      </div>
      <div id="mfChoicesSection" class="mf-group">
        <label class="mf-label" id="mfChoicesLabel">Answer Choices</label>
        <div class="choices-list" id="choicesList"></div>
        <button type="button" class="add-choice-btn" onclick="addChoice()">+ Add choice</button>
      </div>
      <div id="mfLikertNote" style="display:none;background:var(--ka-accent,#e8f4fd);border-radius:8px;padding:.75rem 1rem;font-size:.8125rem;">Likert scale: 1–5 rating.</div>
      <div id="mfEssayNote" style="display:none;background:#fffbeb;border-radius:8px;padding:.75rem 1rem;font-size:.8125rem;">Essay answers require manual grading.</div>
      </div>
      </div>
      <div id="batchQueuePanel" class="batch-queue-panel" style="display:none;">
        <div class="batch-queue-hdr">Queued questions</div>
        <ul id="batchQueueList" class="batch-queue-list"></ul>
      </div>
    </div>
    <div class="q-modal-footer">
      <span id="batchQueueSummary" class="batch-queue-summary" style="display:none;"></span>
      <div class="q-modal-footer-actions">
        <button type="button" class="q-modal-cancel" onclick="closeModal()">Cancel</button>
        <button type="button" class="q-modal-secondary" id="addAnotherBtn" style="display:none;">Add Another Question</button>
        <button type="button" class="q-modal-save" id="modalSaveBtn" onclick="saveQuestion()">
          <span class="q-save-spinner" id="modalSaveSpinner" style="display:none;" aria-hidden="true"></span>
          <span id="modalSaveText">Add Question</span>
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

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
      'useWorkspace' => $use_workspace,
    ],
    'checkpointWorkspace' => $use_workspace ? [
      'activeId' => (int) ($checkpoint_workspace['active_id'] ?? $assessment->id),
      'moduleId' => (int) ($checkpoint_workspace['module_id'] ?? 0),
      'courseId' => (int) ($checkpoint_workspace['course_id'] ?? 0),
      'suggestedVideoDurationSeconds' => (int) ($checkpoint_workspace['suggested_video_duration_seconds'] ?? 0),
      'maxTriggerSeconds' => (int) ($checkpoint_workspace['max_trigger_seconds'] ?? 0),
      'csrfFieldName' => $csrf_field_name,
      'csrfHash' => $csrf_hash,
      'saveQuestionUrl' => base_url('index.php/assessments/save_question'),
      'deleteQuestionUrl' => base_url('index.php/assessments/delete_question'),
      'saveMetaUrl' => base_url('index.php/assessments/save_checkpoint_meta'),
      'autoGenerateUrl' => base_url('index.php/assessments/ajax_auto_generate_checkpoints'),
      'generated' => ! empty($_GET['generated']),
    ] : null,
  ],
], $_jf) ?>);
</script>
<script src="<?= base_url('assets/js/assessments.js') ?>"></script>
<?php if ($use_workspace): ?>
<script src="<?= base_url('assets/js/assessment_workspace.js') ?>"></script>
<?php endif; ?>
