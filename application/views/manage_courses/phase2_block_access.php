<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$access_type = isset($access_type) ? (string) $access_type : 'approval_required';
$access_options = is_array($access_options ?? null) ? $access_options : [];
$is_edit = ! empty($is_edit);
$publish_pill_class = $publish_pill_class ?? 'crs-p2-publish-pill--draft';
$publish_label = $publish_label ?? 'Draft';
?>
<section class="crs-p2-card" aria-labelledby="crs-p2-access-title">
  <header class="crs-p2-card-hdr">
    <div class="crs-p2-card-icon crs-p2-card-icon--access" aria-hidden="true">🔐</div>
    <div>
      <h4 class="crs-p2-card-title" id="crs-p2-access-title">Enrollment access &amp; status</h4>
      <p class="crs-p2-card-sub">Control how learners enroll. Publishing is handled from the sidebar.</p>
    </div>
  </header>
  <div class="crs-p2-card-body">
    <input type="hidden" name="access_type" id="crs_p2_access_type" value="<?= htmlspecialchars($access_type) ?>">

    <div class="crs-p2-field">
      <label class="crs-p2-label">Enrollment access</label>
      <div class="crs-p2-access-grid" role="radiogroup" aria-label="Enrollment access">
        <?php foreach (course_phase2_access_types() as $at): ?>
        <?php $meta = $access_options[$at] ?? ['dot' => 'hidden', 'title' => course_phase2_access_label($at), 'desc' => '']; ?>
        <label class="crs-p2-access-opt">
          <input type="radio" name="crs_p2_access_radio" value="<?= htmlspecialchars($at) ?>"
                 data-label="<?= htmlspecialchars($meta['title']) ?>"
                 <?= $access_type === $at ? 'checked' : '' ?>>
          <div class="crs-p2-access-card">
            <div class="crs-p2-access-head">
              <span class="crs-p2-access-dot crs-p2-access-dot--<?= htmlspecialchars($meta['dot']) ?>"></span>
              <span class="crs-p2-access-name"><?= htmlspecialchars($meta['title']) ?></span>
            </div>
            <p class="crs-p2-access-desc"><?= htmlspecialchars($meta['desc']) ?></p>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="crs-p2-field">
      <label class="crs-p2-label">Publish state</label>
      <?php if ( ! $is_edit): ?>
      <input type="hidden" name="publish_status" value="draft">
      <span class="crs-p2-publish-pill crs-p2-publish-pill--draft">Draft</span>
      <p class="crs-p2-help">New courses start as draft. Publish from the edit screen once modules and settings are ready.</p>
      <?php else: ?>
      <span class="crs-p2-publish-pill <?= htmlspecialchars($publish_pill_class) ?>"><?= htmlspecialchars($publish_label) ?></span>
      <p class="crs-p2-help">Use the <strong>Publish Workflow</strong> panel in the sidebar to publish or unpublish this course.</p>
      <?php endif; ?>
    </div>

    <div class="crs-p2-summary" aria-live="polite">
      <span class="crs-p2-summary-label">Visibility at a glance</span>
      <span class="crs-p2-summary-chip">Access: <strong data-summary-access><?= htmlspecialchars($access_options[$access_type]['title'] ?? $access_type) ?></strong></span>
      <span class="crs-p2-summary-chip crs-p2-summary-chip--publish">Status: <strong><?= htmlspecialchars($publish_label) ?></strong></span>
      <span class="crs-p2-summary-chip crs-p2-summary-chip--muted" data-summary-cat>No categories</span>
      <span class="crs-p2-summary-chip crs-p2-summary-chip--muted" data-summary-dept>All departments</span>
      <span class="crs-p2-summary-chip crs-p2-summary-chip--muted" data-summary-prof>All job titles</span>
    </div>
  </div>
</section>
