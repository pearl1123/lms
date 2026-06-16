<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$enrollment_guard = $enrollment_guard ?? [];
$blocks_delete    = ! empty($enrollment_guard['blocks_delete']);
$blocks_structure = ! empty($enrollment_guard['blocks_structure']);
$approved         = (int) ($enrollment_guard['approved'] ?? 0);
$pending          = (int) ($enrollment_guard['pending'] ?? 0);

if ( ! $blocks_delete && ! $blocks_structure) {
    return;
}
?>
<div class="mc-enroll-guard-alert" role="status">
  <?php if ($blocks_structure): ?>
  <p><strong><?= $approved ?> enrolled learner<?= $approved === 1 ? '' : 's' ?></strong> — module add, delete, and reorder are locked. Course details and existing module content can still be updated.</p>
  <?php elseif ($blocks_delete): ?>
  <p>
    <?php if ($approved > 0): ?><strong><?= $approved ?> enrolled learner<?= $approved === 1 ? '' : 's' ?></strong><?php endif; ?>
    <?php if ($approved > 0 && $pending > 0): ?> and <?php endif; ?>
    <?php if ($pending > 0): ?><strong><?= $pending ?> pending request<?= $pending === 1 ? '' : 's' ?></strong><?php endif; ?>
    — this course cannot be deleted or archived.
  </p>
  <?php endif; ?>
</div>
