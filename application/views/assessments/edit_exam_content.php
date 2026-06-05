<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$assessment = $assessment ?? null;
$questions  = $questions ?? [];
$modules    = $modules ?? [];
$csrf_field_name = $csrf_field_name ?? '';
$csrf_hash       = $csrf_hash ?? '';
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
$randomize_column_ready = ! empty($randomize_column_ready);
$randomize_enabled = $randomize_column_ready
    && etd_assessment_type_supports_randomize($assessment)
    && etd_assessment_randomize_enabled($assessment);
$q_types = ['multiple_choice'=>'Multiple Choice','essay'=>'Essay','likert'=>'Likert Scale','fill_blank'=>'Fill in the Blank'];
$type_colors = ['multiple_choice'=>'#3b82f6','essay'=>'#f59f00','likert'=>'#22c55e','fill_blank'=>'#6dabcf'];
$asmt_type_labels = ['pre' => 'Pre-Assessment', 'post' => 'Post-Assessment', 'checkpoint' => 'Video Checkpoint'];
$q_count = count($questions);
?>

<section class="asx-editor-section is-active" data-asx-section="overview" id="asxSectionOverview">
  <div class="asx-editor-card">
    <div class="asx-editor-card-hdr">
      <h2 class="asx-editor-card-title">Assessment overview</h2>
    </div>
    <div class="asx-editor-card-body asx-editor-overview-grid">
      <div class="asx-stat-tile">
        <span class="asx-stat-tile-val"><?= $q_count ?></span>
        <span class="asx-stat-tile-lbl">Questions</span>
      </div>
      <div class="asx-stat-tile">
        <span class="asx-stat-tile-val"><?= htmlspecialchars($asmt_type_labels[$assessment->type] ?? $assessment->type, ENT_QUOTES) ?></span>
        <span class="asx-stat-tile-lbl">Type</span>
      </div>
      <div class="asx-stat-tile">
        <span class="asx-stat-tile-val"><?= htmlspecialchars($assessment->module_title ?? '—', ENT_QUOTES) ?></span>
        <span class="asx-stat-tile-lbl">Module</span>
      </div>
    </div>
    <p class="asx-editor-card-lead">Build your <?= strtolower($asmt_type_labels[$assessment->type] ?? 'assessment') ?> in the Questions section. Settings holds title, type, and module assignment.</p>
  </div>
</section>

<section class="asx-editor-section" data-asx-section="content" id="asxSectionContent">
  <div class="asx-editor-card">
    <div class="asx-editor-card-hdr">
      <h2 class="asx-editor-card-title">Questions</h2>
      <span class="asx-editor-progress" id="qCountLabel"><?= $q_count ?> question<?= $q_count !== 1 ? 's' : '' ?></span>
    </div>
    <div class="asx-editor-card-body">
      <div class="q-list" id="qList">
        <?php if ( ! empty($questions)): ?>
          <?php foreach ($questions as $idx => $q):
            $tc = $type_colors[$q->question_type] ?? '#6dabcf';
          ?>
          <?= render_q_item($idx + 1, $q, $tc, $q_types) ?>
          <?php endforeach; ?>
        <?php else: ?>
          <?php $this->load->view('components/empty_state', [
              'id'          => 'qEmpty',
              'emoji'       => '❓',
              'title'       => 'No questions yet',
              'description' => 'Add your first question using the button below.',
              'modifier'    => 'ka-empty--wide',
          ]); ?>
        <?php endif; ?>
      </div>
      <?php $cp_at_max = $is_checkpoint && $q_count >= 1; ?>
      <button type="button" class="add-q-btn" id="addQuestionBtn" onclick="openModal()"
              <?= $cp_at_max ? 'disabled title="Video checkpoints support one multiple-choice question."' : '' ?>>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add question
      </button>
    </div>
  </div>
</section>

<section class="asx-editor-section" data-asx-section="settings" id="asxSectionSettings">
  <div class="asx-editor-card">
    <div class="asx-editor-card-hdr"><h2 class="asx-editor-card-title">Assessment settings</h2></div>
    <div class="asx-editor-card-body">
      <form method="post" action="<?= base_url('index.php/assessments/edit/'.$assessment->id) ?>" id="assessmentMetaForm" class="asx-settings-form">
        <input type="hidden" name="<?= $csrf_field_name ?>" value="<?= $csrf_hash ?>">
        <div class="asx-settings-grid">
          <div class="edit-form-group">
            <label class="edit-label">Title</label>
            <input type="text" name="title" class="edit-input" id="asxMetaTitle"
                   value="<?= htmlspecialchars(set_value('title', $assessment->title)) ?>" required>
          </div>
          <div class="edit-form-group">
            <label class="edit-label">Type</label>
            <select name="type" id="editAssessmentType" class="edit-select" required onchange="onEditAssessmentTypeChange()">
              <option value="pre"  <?= set_select('type', 'pre', $assessment->type === 'pre') ?>>Pre-Assessment</option>
              <option value="post" <?= set_select('type', 'post', $assessment->type === 'post') ?>>Post-Assessment</option>
              <?php if ($checkpoint_schema_ready || $is_checkpoint): ?>
              <option value="checkpoint" <?= set_select('type', 'checkpoint', $is_checkpoint) ?>>Video Checkpoint</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="edit-form-group asx-settings-grid--full">
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
          </div>
        </div>
        <input type="hidden" name="trigger_percent" id="edit_trigger_percent" value="0">
        <div class="edit-cp-fields <?= $is_checkpoint ? 'visible' : '' ?>" id="editCheckpointFields">
          <div class="asx-settings-grid asx-settings-grid--cp">
            <div class="edit-form-group">
              <label class="edit-label">Video timestamp (seconds)</label>
              <input type="number" name="trigger_seconds" class="edit-input" min="0" step="1"
                     placeholder="Optional"
                     value="<?= htmlspecialchars(set_value('trigger_seconds', $cp_trigger_seconds)) ?>">
            </div>
            <div class="edit-form-group">
              <label class="edit-label">Whole video duration (seconds)</label>
              <input type="number" name="video_duration_seconds" id="edit_video_duration_seconds" class="edit-input" min="1" step="1"
                     placeholder="e.g. 300"
                     value="<?= htmlspecialchars(set_value('video_duration_seconds', '')) ?>">
            </div>
            <div class="edit-form-group">
              <label class="edit-label">Display order</label>
              <input type="number" name="sort_order" class="edit-input" min="0" step="1"
                     value="<?= htmlspecialchars((string) set_value('sort_order', (string) $cp_sort)) ?>">
            </div>
          </div>
          <label class="edit-cp-toggle" style="margin-top:.75rem;display:inline-flex;">
            <input type="checkbox" name="checkpoint_required" value="1" <?= set_checkbox('checkpoint_required', '1', $cp_required) ?>>
            Required for learners
          </label>
        </div>
        <?php if ($randomize_column_ready): ?>
        <div class="edit-form-group asx-settings-grid--full" id="editRandomizeField" style="margin-top:1rem;<?= $is_checkpoint ? 'display:none;' : '' ?>">
          <label class="edit-cp-toggle" style="display:inline-flex;align-items:flex-start;gap:.5rem;">
            <input type="checkbox" name="randomize_questions" id="editRandomizeQuestions" value="1"
                   <?= $is_checkpoint ? 'disabled' : '' ?>
                   <?= set_checkbox('randomize_questions', '1', $randomize_enabled) ?>>
            <span>
              <strong>Randomize question order</strong><br>
              <span style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);line-height:1.4;">
                Shuffles questions (and multiple-choice answers) for each learner attempt. Editor order is unchanged.
                <?php if ($is_checkpoint): ?>
                Not available for video checkpoints — sequence is fixed by timestamp.
                <?php endif; ?>
              </span>
            </span>
          </label>
        </div>
        <?php endif; ?>
      </form>
    </div>
  </div>
</section>

<section class="asx-editor-section" data-asx-section="publish" id="asxSectionPublish">
  <div class="asx-editor-card">
    <div class="asx-editor-card-hdr"><h2 class="asx-editor-card-title">Publish &amp; review</h2></div>
    <div class="asx-editor-card-body asx-publish-actions">
      <p class="asx-editor-card-lead">Save settings above, then use submissions to review learner answers.</p>
      <a href="<?= base_url('index.php/assessments/review/'.$assessment->id) ?>" class="asx-btn asx-btn--primary">View submissions</a>
      <button type="button" class="asx-btn asx-btn--danger"
              onclick="KA.deleteConfirm('<?= base_url('index.php/assessments/delete/'.$assessment->id) ?>', '<?= htmlspecialchars(addslashes($assessment->title)) ?>')">
        Delete assessment
      </button>
    </div>
  </div>
  <div class="asx-editor-card">
    <div class="asx-editor-card-hdr"><h2 class="asx-editor-card-title">Question breakdown</h2></div>
    <div class="asx-editor-card-body">
      <?php
      $type_counts = array_fill_keys(array_keys($q_types), 0);
      foreach ($questions as $q) {
          $type_counts[$q->question_type] = ($type_counts[$q->question_type] ?? 0) + 1;
      }
      ?>
      <?php foreach ($q_types as $key => $label):
        $cnt   = $type_counts[$key] ?? 0;
        $color = $type_colors[$key];
      ?>
      <div class="asx-type-row" id="typeSummary-<?= $key ?>">
        <span class="asx-type-swatch" style="background:<?= $color ?>"></span>
        <span class="asx-type-row-label"><?= $label ?></span>
        <span class="asx-type-row-count" id="typeCount-<?= $key ?>"><?= $cnt ?></span>
      </div>
      <?php endforeach; ?>
      <div class="asx-type-total">
        <span>Total</span>
        <strong id="typeCountTotal"><?= $q_count ?></strong>
      </div>
    </div>
  </div>
</section>
