<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="libx-modal-overlay" id="libxModalUpdate" aria-hidden="true">
  <div class="libx-modal" role="dialog" aria-modal="true" aria-labelledby="libxModalUpdateTitle">
    <header class="libx-modal-head">
      <h2 id="libxModalUpdateTitle">Update <?= htmlspecialchars($lib['title'] ?? 'record', ENT_QUOTES) ?></h2>
      <button type="button" class="libx-modal-close" data-dismiss="libxModalUpdate" aria-label="Close">&times;</button>
    </header>
    <form id="libxFormUpdate" class="libx-modal-body" novalidate>
      <input type="hidden" name="<?= htmlspecialchars($csrf_field_name ?? '', ENT_QUOTES) ?>" value="<?= htmlspecialchars($csrf_hash ?? '', ENT_QUOTES) ?>">
      <input type="hidden" name="<?= htmlspecialchars($pk, ENT_QUOTES) ?>" id="libxUpdatePk" value="">

      <?php foreach ($lib['form_fields'] ?? [] as $field):
        $fname = $field['field'];
        $ftype = $field['type'] ?? 'text';
        $req   = ! empty($field['required']);
      ?>
      <label class="libx-label" for="libxUpd_<?= htmlspecialchars($fname, ENT_QUOTES) ?>">
        <?= htmlspecialchars($field['label'], ENT_QUOTES) ?><?= $req ? ' <span class="libx-req">*</span>' : '' ?>
      </label>

      <?php if ($ftype === 'textarea'): ?>
      <textarea name="<?= htmlspecialchars($fname, ENT_QUOTES) ?>" id="libxUpd_<?= htmlspecialchars($fname, ENT_QUOTES) ?>"
                class="libx-input libx-textarea" rows="3"<?= $req ? ' required' : '' ?>></textarea>

      <?php elseif ($ftype === 'select'): ?>
      <select name="<?= htmlspecialchars($fname, ENT_QUOTES) ?>" id="libxUpd_<?= htmlspecialchars($fname, ENT_QUOTES) ?>" class="libx-input"<?= $req ? ' required' : '' ?>>
        <option value="">Select…</option>
        <?php foreach (($select_opts[$fname] ?? []) as $optId => $optLabel): ?>
        <option value="<?= (int) $optId ?>"><?= htmlspecialchars($optLabel, ENT_QUOTES) ?></option>
        <?php endforeach; ?>
      </select>

      <?php elseif ($ftype === 'enum'): ?>
      <select name="<?= htmlspecialchars($fname, ENT_QUOTES) ?>" id="libxUpd_<?= htmlspecialchars($fname, ENT_QUOTES) ?>" class="libx-input"<?= $req ? ' required' : '' ?>>
        <?php foreach ($field['options'] ?? [] as $opt): ?>
        <option value="<?= htmlspecialchars($opt, ENT_QUOTES) ?>"><?= htmlspecialchars($opt, ENT_QUOTES) ?></option>
        <?php endforeach; ?>
      </select>

      <?php elseif ($ftype === 'checkbox'): ?>
      <div class="libx-toggle-wrap">
        <label class="libx-toggle">
          <input type="checkbox" name="<?= htmlspecialchars($fname, ENT_QUOTES) ?>" id="libxUpd_<?= htmlspecialchars($fname, ENT_QUOTES) ?>" value="1">
          <span class="libx-toggle-slider"></span>
        </label>
        <span class="libx-toggle-label"><?= htmlspecialchars($field['label'], ENT_QUOTES) ?></span>
      </div>

      <?php elseif ($ftype === 'number'): ?>
      <input type="number" name="<?= htmlspecialchars($fname, ENT_QUOTES) ?>" id="libxUpd_<?= htmlspecialchars($fname, ENT_QUOTES) ?>" class="libx-input"<?= $req ? ' required' : '' ?>>

      <?php else: ?>
      <input type="text" name="<?= htmlspecialchars($fname, ENT_QUOTES) ?>" id="libxUpd_<?= htmlspecialchars($fname, ENT_QUOTES) ?>"
             class="libx-input"<?= $req ? ' required' : '' ?><?= ! empty($field['maxlength']) ? ' maxlength="' . (int) $field['maxlength'] . '"' : '' ?>>
      <?php endif; ?>
      <?php endforeach; ?>

      <p class="libx-form-error" id="libxUpdateError" hidden></p>

      <footer class="libx-modal-foot">
        <button type="button" class="libx-btn libx-btn-ghost" data-dismiss="libxModalUpdate">Cancel</button>
        <button type="submit" class="libx-btn libx-btn-primary">Update</button>
      </footer>
    </form>
  </div>
</div>
