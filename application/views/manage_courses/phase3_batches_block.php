<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (empty($is_edit_form) || empty($phase3_batches_ready)) { return; } ?>
<div class="ef-section" style="margin-top:1rem;">
  <div class="ef-section-hdr">
    <h4 class="ef-section-title">Course batches</h4>
    <p class="ef-section-kicker">Optional cohorts for enrollments. Learners can pick a batch when enrolling.</p>
  </div>
  <div id="p3BatchRows">
    <?php if (empty($course_batches)): ?>
    <div class="p3-batch-row ef-row" data-p3-batch-row style="margin-bottom:.5rem;flex-wrap:wrap;">
      <input type="hidden" name="batch_id[]" value="0">
      <div class="ef-group" style="flex:2;min-width:140px;">
        <label class="ef-label">Batch name</label>
        <input type="text" name="batch_name[]" class="ef-input" maxlength="120" placeholder="e.g. Batch 2026-A">
      </div>
      <div class="ef-group" style="flex:1;min-width:120px;">
        <label class="ef-label">Start</label>
        <input type="date" name="batch_start[]" class="ef-input">
      </div>
      <div class="ef-group" style="flex:1;min-width:120px;">
        <label class="ef-label">End</label>
        <input type="date" name="batch_end[]" class="ef-input">
      </div>
      <div class="ef-group" style="flex:1;min-width:100px;">
        <label class="ef-label">Status</label>
        <select name="batch_status[]" class="ef-input">
          <option value="planned">Planned</option>
          <option value="active" selected>Active</option>
          <option value="closed">Closed</option>
        </select>
      </div>
    </div>
    <?php else: ?>
    <?php foreach ($course_batches as $b): ?>
    <div class="p3-batch-row ef-row" data-p3-batch-row style="margin-bottom:.5rem;flex-wrap:wrap;">
      <input type="hidden" name="batch_id[]" value="<?= (int) ($b->id ?? 0) ?>">
      <div class="ef-group" style="flex:2;min-width:140px;">
        <label class="ef-label">Batch name</label>
        <input type="text" name="batch_name[]" class="ef-input" maxlength="120"
               value="<?= htmlspecialchars($b->batch_name ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="ef-group" style="flex:1;min-width:120px;">
        <label class="ef-label">Start</label>
        <input type="date" name="batch_start[]" class="ef-input"
               value="<?= htmlspecialchars($b->start_date ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="ef-group" style="flex:1;min-width:120px;">
        <label class="ef-label">End</label>
        <input type="date" name="batch_end[]" class="ef-input"
               value="<?= htmlspecialchars($b->end_date ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="ef-group" style="flex:1;min-width:100px;">
        <label class="ef-label">Status</label>
        <select name="batch_status[]" class="ef-input">
          <?php foreach (['planned', 'active', 'closed'] as $st): ?>
          <option value="<?= $st ?>" <?= (($b->status ?? '') === $st) ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <button type="button" class="add-mod-btn" id="p3AddBatch">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add batch
  </button>
</div>
