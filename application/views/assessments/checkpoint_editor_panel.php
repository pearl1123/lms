<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$panel      = $panel ?? null;
$active_id  = (int) ($active_id ?? 0);
$youtube_id = $youtube_id ?? null;
if ( ! $panel || empty($panel['assessment'])) {
    return;
}
$a   = $panel['assessment'];
$aid = (int) $a->id;
$is_active = ($aid === $active_id);
$questions = is_array($panel['questions'] ?? null) ? $panel['questions'] : [];
$q0 = ! empty($questions) ? $questions[0] : null;
$ts = (int) ($panel['trigger_seconds'] ?? 0);
$cp_required = ! empty($a->is_required);
$cp_sort = (int) ($a->sort_order ?? 0);
$choices_json = '[]';
$cpw_choices = [];
if ($q0 && ! empty($q0->choices)) {
    foreach ($q0->choices as $c) {
        $cpw_choices[] = [
            'text'       => (string) $c->choice_text,
            'is_correct' => (int) $c->is_correct === 1,
        ];
    }
    $choices_json = json_encode(array_map(static function ($c) {
        return [
            'id'         => (int) $c->id,
            'text'       => (string) $c->choice_text,
            'is_correct' => (int) $c->is_correct,
        ];
    }, $q0->choices));
}
while (count($cpw_choices) < 2) {
    $cpw_choices[] = [
        'text'       => '',
        'is_correct' => count($cpw_choices) === 0,
    ];
}
$cpw_has_correct = false;
foreach ($cpw_choices as $cpw_ch) {
    if ( ! empty($cpw_ch['is_correct'])) {
        $cpw_has_correct = true;
        break;
    }
}
if ( ! $cpw_has_correct && ! empty($cpw_choices)) {
    $cpw_choices[0]['is_correct'] = true;
}
$block_mode = ! empty($block_mode);
$segment_label = (string) ($panel['segment_label'] ?? 'Checkpoint');
?>
<article class="cpw-block cpw-panel <?= $is_active ? 'is-active' : '' ?>"
     id="cpw-panel-<?= $aid ?>"
     data-assessment-id="<?= $aid ?>"
     role="tabpanel"
     aria-hidden="<?= $is_active ? 'false' : 'true' ?>">

  <?php if ($block_mode): ?>
  <header class="cpw-block-hdr">
    <div class="cpw-block-hdr-left">
      <span class="cpw-block-segment"><?= htmlspecialchars($segment_label, ENT_QUOTES) ?></span>
      <?php if ($ts > 0): ?>
      <span class="cpw-ts-pill">▶ <?= (int) $ts ?>s</span>
      <?php else: ?>
      <span class="cpw-ts-pill cpw-ts-pill--muted">Start</span>
      <?php endif; ?>
    </div>
    <div class="cpw-block-hdr-actions">
      <?php if ($youtube_id && $ts > 0): ?>
      <a class="asx-btn asx-btn--ghost asx-btn--sm" href="https://www.youtube.com/watch?v=<?= htmlspecialchars($youtube_id, ENT_QUOTES) ?>&t=<?= (int) $ts ?>s" target="_blank" rel="noopener">Preview ↗</a>
      <?php endif; ?>
    </div>
  </header>
  <?php endif; ?>

  <div class="cpw-panel-grid <?= $block_mode ? 'cpw-panel-grid--block' : '' ?>">
    <div class="cpw-panel-main">
      <form class="cpw-meta-form" data-assessment-id="<?= $aid ?>" autocomplete="off">
        <div class="cpw-meta-row">
          <div class="cpw-field cpw-field--grow">
            <label class="cpw-label" for="cpw-title-<?= $aid ?>">Checkpoint title</label>
            <input type="text" class="cpw-input" id="cpw-title-<?= $aid ?>" name="title"
                   value="<?= htmlspecialchars($a->title ?? '', ENT_QUOTES) ?>" required>
          </div>
          <div class="cpw-field">
            <label class="cpw-label" for="cpw-ts-<?= $aid ?>">Timestamp (sec)</label>
            <input type="number" class="cpw-input cpw-input--badge" id="cpw-ts-<?= $aid ?>" name="trigger_seconds"
                   min="0" step="1" value="<?= $ts > 0 ? (int) $ts : '' ?>"
                   placeholder="0 = start">
          </div>
          <div class="cpw-field">
            <label class="cpw-label" for="cpw-sort-<?= $aid ?>">Order</label>
            <input type="number" class="cpw-input" id="cpw-sort-<?= $aid ?>" name="sort_order"
                   min="0" step="1" value="<?= $cp_sort ?>">
          </div>
        </div>
        <div class="cpw-meta-row cpw-meta-row--secondary">
          <label class="cpw-toggle">
            <input type="checkbox" name="checkpoint_required" value="1" <?= $cp_required ? 'checked' : '' ?>>
            Required — learner must answer to continue
          </label>
          <span class="cpw-ts-badge" data-cpw-ts-badge="<?= $aid ?>">
            <?php if ($ts > 0): ?>
              <span class="cpw-ts-pill">▶ <?= (int) $ts ?>s</span>
            <?php else: ?>
              <span class="cpw-ts-pill cpw-ts-pill--muted">Start of video</span>
            <?php endif; ?>
          </span>
        </div>
        <div class="cpw-meta-actions">
          <button type="submit" class="cpw-btn cpw-btn--primary cpw-save-meta-btn">
            Save checkpoint settings
          </button>
          <a href="<?= base_url('index.php/assessments/delete/' . $aid) ?>"
             class="cpw-btn cpw-btn--ghost cpw-delete-link"
             data-title="<?= htmlspecialchars($a->title ?? '', ENT_QUOTES) ?>"
             onclick="return cpwConfirmDeleteCheckpoint(this);">Delete checkpoint</a>
        </div>
        <p class="cpw-form-msg" data-cpw-meta-msg="<?= $aid ?>" role="status"></p>
      </form>

      <div class="cpw-question-card">
        <div class="cpw-question-hdr">
          <h4 class="cpw-question-title">Question (multiple choice)</h4>
          <span class="cpw-complete-dot <?= ! empty($panel['has_question']) ? 'is-done' : '' ?>"
                data-cpw-complete="<?= $aid ?>"
                title="<?= ! empty($panel['has_question']) ? 'Question added' : 'Question needed' ?>"></span>
        </div>
        <form class="cpw-question-form" data-assessment-id="<?= $aid ?>">
          <input type="hidden" name="question_id" value="<?= $q0 ? (int) $q0->id : 0 ?>">
          <div class="cpw-field">
            <label class="cpw-label" for="cpw-qtext-<?= $aid ?>">Question text <span class="cpw-req">*</span></label>
            <textarea class="cpw-textarea" id="cpw-qtext-<?= $aid ?>" name="question_text" rows="3"
                      placeholder="What should the learner answer when the video pauses?"><?= $q0 ? htmlspecialchars($q0->question_text, ENT_QUOTES) : '' ?></textarea>
          </div>
          <div class="cpw-choices-wrap">
            <label class="cpw-label">Answer choices</label>
            <div class="cpw-choices-list" data-cpw-choices="<?= $aid ?>">
              <?php foreach ($cpw_choices as $cpw_ch): ?>
              <div class="cpw-choice-row">
                <button type="button"
                        class="cpw-choice-mark<?= ! empty($cpw_ch['is_correct']) ? ' is-correct' : '' ?>"
                        title="Mark correct">✓</button>
                <input type="text"
                       class="cpw-input"
                       placeholder="Choice text"
                       value="<?= htmlspecialchars($cpw_ch['text'], ENT_QUOTES) ?>">
                <button type="button" class="cpw-choice-remove" title="Remove">×</button>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="cpw-btn cpw-btn--ghost cpw-add-choice-btn" data-aid="<?= $aid ?>">+ Add choice</button>
            <p class="cpw-help">Click ✓ to mark the correct answer.</p>
          </div>
          <div class="cpw-question-actions">
            <button type="submit" class="cpw-btn cpw-btn--primary cpw-save-question-btn">
              <?= $q0 ? 'Update question' : 'Save question' ?>
            </button>
            <?php if ($q0): ?>
            <button type="button" class="cpw-btn cpw-btn--ghost cpw-delete-question-btn" data-qid="<?= (int) $q0->id ?>" data-aid="<?= $aid ?>">
              Delete question
            </button>
            <?php endif; ?>
          </div>
          <p class="cpw-form-msg" data-cpw-q-msg="<?= $aid ?>" role="status"></p>
        </form>
      </div>
    </div>

    <?php if ( ! $block_mode): ?>
    <aside class="cpw-panel-side">
      <?php if ($youtube_id): ?>
      <div class="cpw-preview-card">
        <div class="cpw-preview-hdr">Video preview</div>
        <div class="cpw-preview-embed">
          <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($youtube_id, ENT_QUOTES) ?>?rel=0"
                  title="Module video preview" loading="lazy" allowfullscreen></iframe>
        </div>
        <?php if ($ts > 0): ?>
        <a class="cpw-preview-jump" href="https://www.youtube.com/watch?v=<?= htmlspecialchars($youtube_id, ENT_QUOTES) ?>&t=<?= (int) $ts ?>s" target="_blank" rel="noopener">
          Jump to <?= (int) $ts ?>s on YouTube ↗
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <div class="cpw-preview-card cpw-preview-card--hint">
        <p>Learners see this question during playback at the timestamp you set. One MCQ per checkpoint.</p>
      </div>
    </aside>
    <?php endif; ?>
  </div>
</article>
<script type="application/json" id="cpw-choices-data-<?= $aid ?>"><?= $choices_json ?></script>
