<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (empty($phase3_ready)) { return; } ?>
<div class="ef-section" style="margin-top:1rem;">
  <div class="ef-section-hdr">
    <h4 class="ef-section-title">Certificate signatories</h4>
    <p class="ef-section-kicker">Add one or more signatories (shown on the PDF). If none are listed, the single signatory fields above are used.</p>
  </div>
  <div id="p3SignatoryRows">
    <?php if (empty($certificate_signatories)): ?>
    <div class="p3-signatory-row ef-row" data-p3-signatory-row>
      <input type="hidden" name="signatory_id[]" value="0">
      <div class="ef-group" style="flex:2;">
        <label class="ef-label">Name</label>
        <input type="text" name="signatory_name_row[]" class="ef-input" maxlength="120" placeholder="Full name">
      </div>
      <div class="ef-group" style="flex:2;">
        <label class="ef-label">Title</label>
        <input type="text" name="signatory_title_row[]" class="ef-input" maxlength="120" placeholder="Job title">
      </div>
      <div class="ef-group" style="flex:0 0 70px;">
        <label class="ef-label">Order</label>
        <input type="number" name="signatory_order[]" class="ef-input" min="1" value="1">
      </div>
    </div>
    <?php else: ?>
    <?php foreach ($certificate_signatories as $i => $sig): ?>
    <div class="p3-signatory-row ef-row" data-p3-signatory-row style="margin-bottom:.5rem;">
      <input type="hidden" name="signatory_id[]" value="<?= (int) ($sig->id ?? 0) ?>">
      <div class="ef-group" style="flex:2;">
        <label class="ef-label">Name</label>
        <input type="text" name="signatory_name_row[]" class="ef-input" maxlength="120"
               value="<?= htmlspecialchars($sig->name ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="ef-group" style="flex:2;">
        <label class="ef-label">Title</label>
        <input type="text" name="signatory_title_row[]" class="ef-input" maxlength="120"
               value="<?= htmlspecialchars($sig->title ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="ef-group" style="flex:0 0 70px;">
        <label class="ef-label">Order</label>
        <input type="number" name="signatory_order[]" class="ef-input" min="1"
               value="<?= (int) ($sig->order_no ?? ($i + 1)) ?>">
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <button type="button" class="add-mod-btn" id="p3AddSignatory">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add signatory
  </button>
</div>
