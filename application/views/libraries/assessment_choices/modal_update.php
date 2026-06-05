<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="libx-modal-overlay" id="libxModalUpdate" aria-hidden="true">
  <div class="libx-modal" role="dialog" aria-modal="true" aria-labelledby="libxModalUpdateTitle">
    <header class="libx-modal-head">
      <h2 id="libxModalUpdateTitle">Update assessment choice</h2>
      <button type="button" class="libx-modal-close" data-dismiss="libxModalUpdate" aria-label="Close">&times;</button>
    </header>
    <form id="libxFormUpdate" class="libx-modal-body" novalidate>
      <input type="hidden" name="<?= htmlspecialchars($csrf_field_name ?? '', ENT_QUOTES) ?>" value="<?= htmlspecialchars($csrf_hash ?? '', ENT_QUOTES) ?>">
      <input type="hidden" name="id" id="libxUpdateId" value="">

      <label class="libx-label" for="libxUpdateQuestionId">Question <span class="libx-req">*</span></label>
      <select name="question_id" id="libxUpdateQuestionId" class="libx-input" required>
        <option value="">Select question…</option>
        <?php foreach (($questions ?? []) as $q): ?>
        <option value="<?= (int) $q->id ?>">
          #<?= (int) $q->id ?> — <?= htmlspecialchars(mb_strimwidth($q->question_text ?? '', 0, 80, '…'), ENT_QUOTES) ?>
        </option>
        <?php endforeach; ?>
      </select>

      <label class="libx-label" for="libxUpdateChoiceText">Choice text <span class="libx-req">*</span></label>
      <textarea name="choice_text" id="libxUpdateChoiceText" class="libx-input libx-textarea" rows="3" maxlength="500" required></textarea>

      <div class="libx-field-row">
        <div class="libx-toggle-wrap">
          <label class="libx-toggle">
            <input type="checkbox" name="is_correct" id="libxUpdateIsCorrect" value="1">
            <span class="libx-toggle-slider"></span>
          </label>
          <span class="libx-toggle-label">Mark as correct answer</span>
        </div>
      </div>

      <label class="libx-label" for="libxUpdateChoiceOrder">Display order</label>
      <input type="number" name="choice_order" id="libxUpdateChoiceOrder" class="libx-input" min="1" value="1">

      <p class="libx-form-error" id="libxUpdateError" hidden></p>

      <footer class="libx-modal-foot">
        <button type="button" class="libx-btn libx-btn-ghost" data-dismiss="libxModalUpdate">Cancel</button>
        <button type="submit" class="libx-btn libx-btn-primary">Update choice</button>
      </footer>
    </form>
  </div>
</div>
