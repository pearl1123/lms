<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$choices   = $choices ?? [];
$questions = $questions ?? [];
$filters   = $filters ?? [];
$stats     = $stats ?? ['total' => 0, 'active' => 0, 'archived' => 0, 'correct' => 0];
$csrf_name = $csrf_field_name ?? '';
$csrf_hash = $csrf_hash ?? '';
$base_choices = site_url('libraries/assessment_choices');
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/libraries_assessment_choices.css') ?>">

<div class="libx-workspace animate__animated animate__fadeIn animate__fast">
  <header class="libx-hero-card">
    <div class="libx-hero-main">
      <p class="libx-eyebrow">Admin Library</p>
      <h1 class="libx-title">Assessment Choices</h1>
      <p class="libx-subtitle">Manage multiple-choice options used in assessments, checkpoints, and grading. Records are archived — never hard-deleted.</p>
    </div>
    <div class="libx-hero-stats">
      <div class="libx-stat"><span class="libx-stat-val"><?= (int) $stats['total'] ?></span><span class="libx-stat-lbl">Listed</span></div>
      <div class="libx-stat"><span class="libx-stat-val"><?= (int) $stats['active'] ?></span><span class="libx-stat-lbl">Active</span></div>
      <div class="libx-stat"><span class="libx-stat-val"><?= (int) $stats['correct'] ?></span><span class="libx-stat-lbl">Correct flags</span></div>
      <div class="libx-stat"><span class="libx-stat-val"><?= (int) $stats['archived'] ?></span><span class="libx-stat-lbl">Archived</span></div>
    </div>
  </header>

  <div class="libx-toolbar-card">
    <form method="get" action="<?= $base_choices ?>" class="libx-toolbar" id="libxFilterForm">
      <div class="libx-search-wrap">
        <svg class="libx-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="search" name="q" class="libx-input libx-search" placeholder="Search choice or question text…"
               value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES) ?>">
      </div>
      <select name="question_id" class="libx-select" aria-label="Filter by question">
        <option value="0">All questions</option>
        <?php foreach ($questions as $q): ?>
        <option value="<?= (int) $q->id ?>" <?= (int) ($filters['question_id'] ?? 0) === (int) $q->id ? 'selected' : '' ?>>
          #<?= (int) $q->id ?> — <?= htmlspecialchars(mb_strimwidth($q->question_text ?? '', 0, 60, '…'), ENT_QUOTES) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <label class="libx-check">
        <input type="checkbox" name="show_archived" value="1" <?= ! empty($filters['include_archived']) ? 'checked' : '' ?>>
        Show archived
      </label>
      <button type="submit" class="libx-btn libx-btn-ghost">Apply</button>
      <button type="button" class="libx-btn libx-btn-primary" id="libxBtnAdd">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Choice
      </button>
    </form>
  </div>

  <div class="libx-table-card">
    <div class="libx-table-wrap">
      <table class="libx-table" id="libxTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Question</th>
            <th>Choice text</th>
            <th>Correct</th>
            <th>Order</th>
            <th>Status</th>
            <th class="libx-th-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($choices)): ?>
          <tr>
            <td colspan="7" class="libx-empty">No choices found. Add a choice or adjust filters.</td>
          </tr>
          <?php else: ?>
          <?php foreach ($choices as $c):
            $is_archived = (int) ($c->archived ?? 0) === 1;
            $is_correct  = (int) ($c->is_correct ?? 0) === 1;
          ?>
          <tr class="<?= $is_archived ? 'libx-row-archived' : '' ?>" data-id="<?= (int) $c->id ?>">
            <td><span class="libx-id">#<?= (int) $c->id ?></span></td>
            <td>
              <span class="libx-qid">Q<?= (int) $c->question_id ?></span>
              <span class="libx-q-preview"><?= htmlspecialchars(mb_strimwidth($c->question_text ?? '', 0, 48, '…'), ENT_QUOTES) ?></span>
            </td>
            <td class="libx-choice-text <?= $is_correct ? 'libx-choice-text--correct' : '' ?>">
              <?= htmlspecialchars($c->choice_text ?? '', ENT_QUOTES) ?>
            </td>
            <td>
              <?php if ($is_correct): ?>
              <span class="libx-badge libx-badge--correct">✔ Correct</span>
              <?php else: ?>
              <span class="libx-badge libx-badge--neutral">—</span>
              <?php endif; ?>
            </td>
            <td><span class="libx-order"><?= (int) ($c->choice_order ?? 1) ?></span></td>
            <td>
              <?php if ($is_archived): ?>
              <span class="libx-badge libx-badge--archived">Archived</span>
              <?php else: ?>
              <span class="libx-badge libx-badge--active">Active</span>
              <?php endif; ?>
            </td>
            <td class="libx-actions">
              <?php if ( ! $is_archived): ?>
              <button type="button" class="libx-action libx-action-edit" data-id="<?= (int) $c->id ?>">Edit</button>
              <button type="button" class="libx-action libx-action-delete" data-id="<?= (int) $c->id ?>">Archive</button>
              <?php else: ?>
              <button type="button" class="libx-action libx-action-restore" data-id="<?= (int) $c->id ?>">Restore</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
$this->load->view('libraries/assessment_choices/modal_add', get_defined_vars());
$this->load->view('libraries/assessment_choices/modal_update', get_defined_vars());
?>

<script>
window.LIBX_ASSESSMENT_CHOICES = <?= json_encode([
    'baseUrl'   => $base_choices,
    'csrfName'  => $csrf_name,
    'csrfHash'  => $csrf_hash,
    'choices'   => array_map(static function ($c) {
        return [
            'id'            => (int) $c->id,
            'question_id'   => (int) $c->question_id,
            'choice_text'   => (string) ($c->choice_text ?? ''),
            'is_correct'    => (int) ($c->is_correct ?? 0),
            'choice_order'  => (int) ($c->choice_order ?? 1),
            'archived'      => (int) ($c->archived ?? 0),
        ];
    }, $choices),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= base_url('assets/js/libraries_notify.js') ?>"></script>
<script src="<?= base_url('assets/js/libraries_assessment_choices.js') ?>"></script>
