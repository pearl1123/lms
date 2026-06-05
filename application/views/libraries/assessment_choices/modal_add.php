<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="libx-modal-overlay" id="libxModalAdd" aria-hidden="true">
  <div class="libx-modal" role="dialog" aria-modal="true" aria-labelledby="libxModalAddTitle">
    <header class="libx-modal-head">
      <h2 id="libxModalAddTitle">Add assessment choice</h2>
      <button type="button" class="libx-modal-close" data-dismiss="libxModalAdd" aria-label="Close">&times;</button>
    </header>
    <form id="libxFormAdd" class="libx-modal-body" novalidate>
      <input type="hidden" name="<?= htmlspecialchars($csrf_field_name ?? '', ENT_QUOTES) ?>" value="<?= htmlspecialchars($csrf_hash ?? '', ENT_QUOTES) ?>">

      <label class="libx-label" for="libxAddQuestionId">Question <span class="libx-req">*</span></label>
      <select name="question_id" id="libxAddQuestionId" class="libx-input" required>
        <option value="">Select question…</option>
        <?php foreach (($questions ?? []) as $q): ?>
        <option value="<?= (int) $q->id ?>">
          #<?= (int) $q->id ?> — <?= htmlspecialchars(mb_strimwidth($q->question_text ?? '', 0, 80, '…'), ENT_QUOTES) ?>
          (<?= htmlspecialchars($q->assessment_title ?? '', ENT_QUOTES) ?>)
        </option>
        <?php endforeach; ?>
      </select>

      <label class="libx-label" for="libxAddChoiceText">Choice text <span class="libx-req">*</span></label>
      <textarea name="choice_text" id="libxAddChoiceText" class="libx-input libx-textarea" rows="3" maxlength="500" required placeholder="Answer option shown to learners"></textarea>

      <div class="libx-field-row">
        <div class="libx-toggle-wrap">
          <label class="libx-toggle">
            <input type="checkbox" name="is_correct" id="libxAddIsCorrect" value="1">
            <span class="libx-toggle-slider"></span>
          </label>
          <span class="libx-toggle-label">Mark as correct answer</span>
        </div>
      </div>

      <label class="libx-label" for="libxAddChoiceOrder">Display order</label>
      <input type="number" name="choice_order" id="libxAddChoiceOrder" class="libx-input" min="1" value="1" placeholder="1 = first in dropdown">

      <p class="libx-form-error" id="libxAddError" hidden></p>

      <footer class="libx-modal-foot">
        <button type="button" class="libx-btn libx-btn-ghost" data-dismiss="libxModalAdd">Cancel</button>
        <button type="submit" class="libx-btn libx-btn-primary">Save choice</button>
      </footer>
    </form>
  </div>
</div>
