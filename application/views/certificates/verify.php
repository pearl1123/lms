<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$cert = $cert ?? null;
$code = $code ?? '';
?>
<?php echo $alerts_partial_html ?? ''; ?>
<style>
.verify-wrap { max-width:560px;margin:0 auto; }
.verify-hero {
  background:linear-gradient(135deg,var(--ka-navy,#1a3a5c) 0%,#254d75 60%,#2d6a9f 100%);
  border-radius:16px;padding:2rem;text-align:center;margin-bottom:1.5rem;
  box-shadow:0 8px 32px rgba(26,58,92,.18);
}
.verify-hero h2 { font-size:1.25rem;font-weight:800;color:#fff;margin:0 0 4px;letter-spacing:-.02em; }
.verify-hero p  { font-size:.875rem;color:rgba(255,255,255,.65);margin:0; }
.verify-form { display:flex;gap:.625rem;margin-top:1.25rem; }
.verify-input {
  flex:1;height:44px;padding:0 1rem;
  border:none;border-radius:9px;font-size:.875rem;
  outline:none;font-family:inherit;text-transform:uppercase;letter-spacing:.05em;
}
.verify-btn {
  padding:0 1.375rem;height:44px;border-radius:9px;
  background:#c9a84c;color:var(--ka-navy,#1a3a5c);border:none;
  font-size:.875rem;font-weight:700;cursor:pointer;white-space:nowrap;transition:all .18s;
}
.verify-btn:hover { background:#e8d5a3; }

/* Result cards */
.verify-valid {
  background:#fff;border:2px solid #22c55e;border-radius:16px;overflow:hidden;
  box-shadow:0 4px 20px rgba(34,197,94,.1);
}
.verify-valid-header {
  background:linear-gradient(135deg,#065f46,#059669);
  padding:1.5rem;text-align:center;
}
.verify-valid-header .check { font-size:2.5rem;margin-bottom:.375rem; }
.verify-valid-header h3 { font-size:1.125rem;font-weight:800;color:#fff;margin:0; }
.verify-valid-body { padding:1.5rem; }
.verify-field { display:flex;justify-content:space-between;align-items:center;padding:.625rem 0;border-bottom:1px solid var(--ka-border,#e2e8f0);font-size:.875rem; }
.verify-field:last-child { border-bottom:none; }
.verify-field-label { color:var(--ka-text-muted,#64748b);font-weight:500; }
.verify-field-value { font-weight:700;color:var(--ka-text,#1e293b);text-align:right;max-width:220px; }
.verify-code-badge {
  display:block;text-align:center;margin-top:1rem;padding:.75rem;
  background:var(--ka-bg,#f8fafc);border-radius:9px;
  font-family:monospace;font-size:.875rem;font-weight:700;
  color:var(--ka-navy,#1a3a5c);letter-spacing:.06em;
}

.verify-invalid {
  background:#fff;border:2px solid #dc2626;border-radius:16px;overflow:hidden;
}
.verify-invalid-header { background:linear-gradient(135deg,#7f1d1d,#dc2626);padding:1.5rem;text-align:center; }
.verify-invalid-header .x { font-size:2.5rem;margin-bottom:.375rem; }
.verify-invalid-header h3 { font-size:1.125rem;font-weight:800;color:#fff;margin:0; }
.verify-invalid-body { padding:1.5rem;text-align:center;font-size:.875rem;color:var(--ka-text-muted,#64748b); }
</style>

<div class="verify-wrap animate__animated animate__fadeIn animate__fast">

  <!-- Hero + search form -->
  <div class="verify-hero">
    <h2>Certificate Verification</h2>
    <p>Enter a certificate code to verify its authenticity</p>
    <form method="get" action="<?= base_url('index.php/certificates/verify') ?>" class="verify-form">
      <input type="text" name="code" class="verify-input"
             placeholder="e.g. KBGA-20241201-AB12CD"
             value="<?= htmlspecialchars($code) ?>" autocomplete="off" required>
      <button type="submit" class="verify-btn">Verify</button>
    </form>
  </div>

  <!-- Results -->
  <?php if ($code !== '' && $cert): ?>
  <!-- ✓ Valid certificate -->
  <div class="verify-valid animate__animated animate__fadeInUp animate__fast">
    <div class="verify-valid-header">
      <div class="check">✅</div>
      <h3>Certificate Verified</h3>
    </div>
    <div class="verify-valid-body">
      <div class="verify-field">
        <span class="verify-field-label">Recipient</span>
        <span class="verify-field-value"><?= htmlspecialchars($cert->student_name) ?></span>
      </div>
      <div class="verify-field">
        <span class="verify-field-label">Employee ID</span>
        <span class="verify-field-value"><?= htmlspecialchars($cert->employee_id ?? '—') ?></span>
      </div>
      <div class="verify-field">
        <span class="verify-field-label">Course Completed</span>
        <span class="verify-field-value"><?= htmlspecialchars($cert->course_title) ?></span>
      </div>
      <div class="verify-field">
        <span class="verify-field-label">Category</span>
        <span class="verify-field-value"><?= htmlspecialchars($cert->category_name ?? '—') ?></span>
      </div>
      <div class="verify-field">
        <span class="verify-field-label">Issue Date</span>
        <span class="verify-field-value"><?= date('F j, Y', strtotime($cert->issued_at)) ?></span>
      </div>
      <div class="verify-field">
        <span class="verify-field-label">Issuing Authority</span>
        <span class="verify-field-value">KABAGA Academy, Lung Center of the Philippines</span>
      </div>
      <div class="verify-code-badge"><?= htmlspecialchars($cert->certificate_code) ?></div>
    </div>
  </div>

  <?php elseif ($code !== '' && ! $cert): ?>
  <!-- ✗ Invalid certificate -->
  <div class="verify-invalid animate__animated animate__fadeInUp animate__fast">
    <div class="verify-invalid-header">
      <div class="x">❌</div>
      <h3>Certificate Not Found</h3>
    </div>
    <div class="verify-invalid-body">
      <p>No valid certificate was found with code:</p>
      <div style="font-family:monospace;font-weight:700;font-size:.875rem;color:var(--ka-text,#1e293b);margin:.5rem 0 1rem;">
        <?= htmlspecialchars($code) ?>
      </div>
      <p>This certificate may not exist, may have been revoked, or the code may be incorrect. Please double-check the code and try again.</p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Info note -->
  <div style="text-align:center;margin-top:1.25rem;font-size:.75rem;color:var(--ka-text-muted,#64748b);">
    This verification service is provided by KABAGA Academy, Lung Center of the Philippines.<br>
    Certificates are issued automatically upon successful completion of all course requirements.
  </div>

</div>