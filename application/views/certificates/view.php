<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$cert      = $cert      ?? null;
$logs      = $logs      ?? [];
$user_role = strtolower($user->role ?? 'employee');
if ( ! $cert) return;
?>
<?php echo $alerts_partial_html ?? ''; ?>
<style>
.certv-layout { display:grid;grid-template-columns:1fr 280px;gap:1.5rem;align-items:start; }
@media(max-width:991.98px){ .certv-layout{grid-template-columns:1fr;} }
.certv-preview {
  background:linear-gradient(135deg,var(--ka-navy,#1a3a5c) 0%,#254d75 55%,#2d6a9f 100%);
  border-radius:16px;padding:2.5rem 2rem;text-align:center;position:relative;overflow:hidden;
  box-shadow:0 12px 40px rgba(26,58,92,.2);
}
.certv-preview::before { content:'';position:absolute;top:-80px;right:-80px;width:260px;height:260px;border-radius:50%;background:rgba(201,168,76,.08); }
.certv-preview::after  { content:'';position:absolute;bottom:-60px;left:-60px;width:200px;height:200px;border-radius:50%;background:rgba(109,171,207,.06); }
.certv-body { position:relative;z-index:1; }
.certv-label { font-size:.625rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:rgba(255,255,255,.5);margin-bottom:.5rem; }
.certv-cert-title { font-size:1rem;font-weight:800;color:#c9a84c;margin-bottom:1.5rem;letter-spacing:.05em; }
.certv-by { font-size:.8125rem;color:rgba(255,255,255,.6);margin-bottom:.5rem; }
.certv-name { font-size:1.75rem;font-weight:900;color:#fff;margin-bottom:.375rem;letter-spacing:-.02em;line-height:1.2; }
.certv-empid { font-size:.75rem;color:rgba(255,255,255,.5);margin-bottom:1.5rem; }
.certv-completed { font-size:.875rem;color:rgba(255,255,255,.7);margin-bottom:.5rem; }
.certv-course { font-size:1.125rem;font-weight:800;color:#fff;font-style:italic;margin-bottom:1.75rem;line-height:1.3; }
.certv-divider { height:1px;background:rgba(201,168,76,.3);margin:1.25rem 0; }
.certv-seal {
  width:70px;height:70px;border-radius:50%;margin:0 auto 1.25rem;
  background:rgba(201,168,76,.15);border:2px solid rgba(201,168,76,.5);
  display:flex;align-items:center;justify-content:center;
  font-size:.5rem;font-weight:800;color:#c9a84c;text-align:center;line-height:1.3;letter-spacing:.04em;
}
.certv-issued { font-size:.75rem;color:rgba(255,255,255,.6);margin-bottom:.375rem; }
.certv-code {
  font-family:monospace;font-size:.875rem;font-weight:700;
  color:#c9a84c;letter-spacing:.08em;
}
.certv-actions { display:flex;flex-direction:column;gap:.625rem;margin-top:1.5rem; }
.certv-btn {
  padding:.75rem;border-radius:9px;font-size:.875rem;font-weight:700;
  text-align:center;text-decoration:none;cursor:pointer;border:none;
  transition:all .2s;display:block;
}
.certv-btn-download { background:#c9a84c;color:var(--ka-navy,#1a3a5c); }
.certv-btn-download:hover { background:#e8d5a3;color:var(--ka-navy,#1a3a5c);transform:translateY(-1px); }
.certv-btn-verify { background:rgba(255,255,255,.1);color:rgba(255,255,255,.85); }
.certv-btn-verify:hover { background:rgba(255,255,255,.18);color:#fff; }

/* Info panels */
.certv-panel { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;overflow:hidden;margin-bottom:1rem; }
.certv-panel-hdr { padding:.875rem 1.125rem;border-bottom:1px solid var(--ka-border,#e2e8f0);display:flex;align-items:center;justify-content:space-between; }
.certv-panel-title { font-size:.875rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0; }
.certv-panel-body { padding:1.125rem; }
.certv-info-row { display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid var(--ka-border,#e2e8f0);font-size:.8125rem; }
.certv-info-row:last-child { border-bottom:none; }
.certv-info-label { color:var(--ka-text-muted,#64748b);font-weight:500; }
.certv-info-value { font-weight:700;color:var(--ka-text,#1e293b);text-align:right;max-width:180px;font-size:.75rem; }

/* Audit log */
.certv-log-item { display:flex;gap:.875rem;padding:.625rem 0;border-bottom:1px solid var(--ka-border,#e2e8f0); }
.certv-log-item:last-child { border-bottom:none; }
.certv-log-dot { width:10px;height:10px;border-radius:50%;flex-shrink:0;margin-top:4px; }
.certv-log-dot.issued  { background:#22c55e; }
.certv-log-dot.revoked { background:#dc2626; }
.certv-log-dot.printed { background:var(--ka-primary,#6dabcf); }
.certv-log-action { font-size:.8125rem;font-weight:700;color:var(--ka-text,#1e293b);text-transform:capitalize; }
.certv-log-meta   { font-size:.6875rem;color:var(--ka-text-muted,#64748b); }
</style>

<!-- Back + revoke bar -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;" class="animate__animated animate__fadeIn animate__fast">
  <a href="<?= base_url('index.php/certificates') ?>"
     style="display:inline-flex;align-items:center;gap:5px;font-size:.8125rem;font-weight:600;text-decoration:none;color:var(--ka-text-muted,#64748b);">
    ← Back to Certificates
  </a>
  <?php if (in_array($user_role, ['admin', 'teacher'])): ?>
  <button type="button"
          class="certv-revoke-btn"
          data-revoke-form="revokeFormTop"
          style="padding:.5rem 1rem;border-radius:8px;border:1.5px solid #fecaca;background:#fef2f2;color:#dc2626;font-size:.8125rem;font-weight:700;cursor:pointer;">
    Revoke Certificate
  </button>
  <form id="revokeFormTop" method="post" action="<?= base_url('index.php/certificates/revoke/'.$cert->id) ?>" style="display:none;">
    <input type="hidden" name="<?= html_escape($csrf_field_name ?? '') ?>" value="<?= html_escape($csrf_hash ?? '') ?>">
    <input type="hidden" name="remarks" value="Revoked by <?= htmlspecialchars($user->fullname) ?>.">
  </form>
  <?php endif; ?>
</div>

<div class="certv-layout animate__animated animate__fadeInUp animate__fast">

  <!-- Certificate Preview -->
  <div class="certv-preview">
    <div class="certv-body">
      <div class="certv-label">KABAGA Academy · Lung Center of the Philippines</div>
      <div class="certv-cert-title">CERTIFICATE OF COMPLETION</div>

      <div class="certv-seal">KABAGA<br>ACADEMY<br>LCP</div>

      <div class="certv-by">This certifies that</div>
      <div class="certv-name"><?= htmlspecialchars(strtoupper($cert->student_name)) ?></div>
      <?php if ( ! empty($cert->employee_id)): ?>
      <div class="certv-empid">Employee ID: <?= htmlspecialchars($cert->employee_id) ?></div>
      <?php endif; ?>

      <div class="certv-completed">has successfully completed</div>
      <div class="certv-course">"<?= htmlspecialchars($cert->course_title) ?>"</div>

      <div class="certv-divider"></div>

      <div class="certv-issued">Issued on <?= date('F j, Y', strtotime($cert->issued_at)) ?></div>
      <div class="certv-code"><?= htmlspecialchars($cert->certificate_code) ?></div>

      <div class="certv-actions">
        <a href="<?= base_url('index.php/certificates/download/'.$cert->id) ?>"
           class="certv-btn certv-btn-download">
          ⬇ Download PDF Certificate
        </a>
        <a href="<?= base_url('index.php/certificates/verify/'.$cert->certificate_code) ?>"
           class="certv-btn certv-btn-verify" target="_blank">
          🔗 Verify Certificate
        </a>
      </div>
    </div>
  </div>

  <!-- Right sidebar -->
  <div>

    <!-- Certificate Info -->
    <div class="certv-panel animate__animated animate__fadeInUp animate__fast">
      <div class="certv-panel-hdr"><h3 class="certv-panel-title">Certificate Details</h3></div>
      <div class="certv-panel-body" style="padding-top:.25rem;padding-bottom:.25rem;">
        <div class="certv-info-row">
          <span class="certv-info-label">Recipient</span>
          <span class="certv-info-value"><?= htmlspecialchars($cert->student_name) ?></span>
        </div>
        <div class="certv-info-row">
          <span class="certv-info-label">Course</span>
          <span class="certv-info-value"><?= htmlspecialchars($cert->course_title) ?></span>
        </div>
        <?php if ( ! empty($cert->category_name)): ?>
        <div class="certv-info-row">
          <span class="certv-info-label">Category</span>
          <span class="certv-info-value"><?= htmlspecialchars($cert->category_name) ?></span>
        </div>
        <?php endif; ?>
        <div class="certv-info-row">
          <span class="certv-info-label">Issue Date</span>
          <span class="certv-info-value"><?= date('F j, Y', strtotime($cert->issued_at)) ?></span>
        </div>
        <div class="certv-info-row">
          <span class="certv-info-label">Certificate Code</span>
          <span class="certv-info-value" style="font-family:monospace;color:var(--ka-navy,#1a3a5c);"><?= htmlspecialchars($cert->certificate_code) ?></span>
        </div>
        <div class="certv-info-row">
          <span class="certv-info-label">Status</span>
          <span style="display:inline-flex;padding:2px 8px;border-radius:20px;font-size:.625rem;font-weight:700;background:#ecfdf5;color:#065f46;">
            ✓ Valid
          </span>
        </div>
      </div>
    </div>

    <!-- Audit Log -->
    <?php if ( ! empty($logs)): ?>
    <div class="certv-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.05s;">
      <div class="certv-panel-hdr"><h3 class="certv-panel-title">Activity Log</h3></div>
      <div class="certv-panel-body" style="padding-top:.25rem;padding-bottom:.25rem;">
        <?php foreach ($logs as $log): ?>
        <div class="certv-log-item">
          <div class="certv-log-dot <?= $log->action ?>"></div>
          <div>
            <div class="certv-log-action"><?= ucfirst($log->action) ?></div>
            <div class="certv-log-meta">
              <?= date('M j, Y g:i A', strtotime($log->action_at)) ?>
              <?php if ( ! empty($log->action_by_name)): ?>
                · <?= htmlspecialchars($log->action_by_name) ?>
              <?php endif; ?>
            </div>
            <?php if ( ! empty($log->remarks)): ?>
            <div style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin-top:2px;font-style:italic;">
              <?= htmlspecialchars($log->remarks) ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- How to verify -->
    <div class="certv-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s;">
      <div class="certv-panel-hdr"><h3 class="certv-panel-title">How to Verify</h3></div>
      <div class="certv-panel-body" style="font-size:.8125rem;color:var(--ka-text-muted,#64748b);line-height:1.6;">
        <p style="margin:0 0 .5rem;">Anyone can verify this certificate by visiting:</p>
        <div style="font-family:monospace;font-size:.6875rem;background:var(--ka-bg,#f8fafc);padding:.5rem .75rem;border-radius:6px;word-break:break-all;color:var(--ka-navy,#1a3a5c);">
          <?= base_url('index.php/certificates/verify/') ?><?= htmlspecialchars($cert->certificate_code) ?>
        </div>
        <p style="margin:.625rem 0 0;">Or by entering the code <strong><?= htmlspecialchars($cert->certificate_code) ?></strong> on the verification page.</p>
      </div>
    </div>

  </div>
</div>

<?php if (in_array($user_role, ['admin', 'teacher'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var btns = document.querySelectorAll('.certv-revoke-btn');
  for (var i = 0; i < btns.length; i++) {
    btns[i].addEventListener('click', function () {
      var formId = this.getAttribute('data-revoke-form');
      KA.confirm({
        title: 'Revoke Certificate?',
        text: 'This will notify the student and mark the certificate as invalid.',
        confirmText: 'Yes, revoke',
        type: 'danger',
        onConfirm: function () {
          var f = formId ? document.getElementById(formId) : null;
          if (f) {
            f.submit();
          }
        }
      });
    });
  }
});
</script>
<?php endif; ?>