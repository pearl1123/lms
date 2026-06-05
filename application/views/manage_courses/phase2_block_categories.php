<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $sel_categories = is_array($sel_categories ?? null) ? $sel_categories : []; ?>
<section class="crs-p2-card" aria-labelledby="crs-p2-categories-title">
  <header class="crs-p2-card-hdr">
    <div class="crs-p2-card-icon" aria-hidden="true">🏷</div>
    <div>
      <h4 class="crs-p2-card-title" id="crs-p2-categories-title">Categories</h4>
      <p class="crs-p2-card-sub">Organize how this course appears in the catalog. The first category is the primary label.</p>
    </div>
  </header>
  <div class="crs-p2-card-body">
    <div class="crs-p2-field">
      <label class="crs-p2-label">Categories <span style="color:#dc2626;">*</span></label>
      <select name="category_ids[]" class="ka-select2 ka-cat-select" multiple data-select-kind="cat" data-placeholder="Search categories…">
        <?php foreach ($categories as $cat): ?>
        <option value="<?= (int) $cat->id ?>"
                <?= in_array((int) $cat->id, $sel_categories, true) ? 'selected' : '' ?>
                data-description="<?= htmlspecialchars((string) ($cat->description ?? ''), ENT_QUOTES) ?>"
                <?= ! empty($cat->color_hex) ? 'data-color="' . htmlspecialchars((string) $cat->color_hex, ENT_QUOTES) . '"' : '' ?>>
          <?= htmlspecialchars($cat->name) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <div class="crs-p2-cat-preview" id="crsCatPreview" aria-live="polite"></div>
      <p class="crs-p2-help">Choose every topic this course belongs to. The first category is used as the primary catalog label.</p>
    </div>
  </div>
</section>
