<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$pending_rows = $pending_rows ?? [];
?>
<?php echo $alerts_partial_html ?? ''; ?>

<div style="max-width:960px;margin:0 auto;">
  <h2 style="font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 .5rem;">Enrollment requests</h2>
  <p style="font-size:.875rem;color:var(--ka-text-muted,#64748b);margin:0 0 1.5rem;">
    Approve or reject employee enrollment requests for your courses.
  </p>

  <?php if (empty($pending_rows)): ?>
    <div style="background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:12px;padding:2rem;text-align:center;color:var(--ka-text-muted,#64748b);">
      No pending enrollment requests.
    </div>
  <?php else: ?>
    <div style="background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:12px;overflow:hidden;">
      <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
        <thead>
          <tr style="background:var(--ka-bg,#f8fafc);text-align:left;">
            <th style="padding:.75rem 1rem;font-weight:700;">Course</th>
            <th style="padding:.75rem 1rem;font-weight:700;">Employee</th>
            <th style="padding:.75rem 1rem;font-weight:700;">Requested</th>
            <th style="padding:.75rem 1rem;font-weight:700;width:200px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pending_rows as $r): ?>
          <tr style="border-top:1px solid var(--ka-border,#e2e8f0);">
            <td style="padding:.75rem 1rem;font-weight:600;"><?= htmlspecialchars($r->course_title ?? '') ?></td>
            <td style="padding:.75rem 1rem;">
              <div><?= htmlspecialchars($r->student_name ?? '') ?></div>
              <div style="font-size:.75rem;color:var(--ka-text-muted,#64748b);"><?= htmlspecialchars($r->employee_id ?? '') ?></div>
            </td>
            <td style="padding:.75rem 1rem;color:var(--ka-text-muted,#64748b);">
              <?= htmlspecialchars($r->enrolled_at ?? '') ?>
            </td>
            <td style="padding:.75rem 1rem;">
              <form method="post" action="<?= base_url('index.php/enrollments/approve/'.$r->enrollment_id) ?>" style="display:inline;">
                <input type="hidden" name="<?= html_escape($csrf_field_name ?? '') ?>" value="<?= html_escape($csrf_hash ?? '') ?>">
                <button type="submit" class="btn btn-sm btn-primary" style="margin-right:.35rem;">Approve</button>
              </form>
              <form method="post" action="<?= base_url('index.php/enrollments/reject/'.$r->enrollment_id) ?>" style="display:inline;">
                <input type="hidden" name="<?= html_escape($csrf_field_name ?? '') ?>" value="<?= html_escape($csrf_hash ?? '') ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
