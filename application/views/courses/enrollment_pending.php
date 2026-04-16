<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$course = $course ?? null;
if ( ! $course) {
    return;
}
?>
<?php echo $alerts_partial_html ?? ''; ?>

<div style="max-width:560px;margin:2rem auto;padding:2rem;background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;text-align:center;">
  <div style="font-size:2.5rem;margin-bottom:.75rem;">⏳</div>
  <h1 style="font-size:1.125rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 .5rem;">Pending approval</h1>
  <p style="font-size:.875rem;color:var(--ka-text-muted,#64748b);line-height:1.6;margin:0 0 1.25rem;">
    Your enrollment in <strong><?= htmlspecialchars($course->title) ?></strong> is waiting for instructor approval.
    You will be able to open course modules once it is approved.
  </p>
  <a href="<?= base_url('index.php/courses/view/'.$course->id) ?>" class="btn btn-primary" style="margin-right:.5rem;">Back to course</a>
  <a href="<?= base_url('index.php/courses') ?>" class="btn btn-outline-secondary">Course catalog</a>
</div>
