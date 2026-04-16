<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$certificates       = $certificates ?? [];
$courses            = $courses ?? [];
$stats_defaults     = [
    'total' => 0, 'primary_label' => 'Certificates Earned', 'unique_courses' => 0,
    'unique_recipients' => 0, 'ready_download' => 0, 'latest_earned_label' => '—',
];
$stats_raw          = $certificate_stats ?? [];
$stats              = array_merge($stats_defaults, is_array($stats_raw) ? $stats_raw : []);
$is_manager         = ! empty($is_manager);
$is_admin           = ! empty($is_admin);
?>
<?php echo $alerts_partial_html ?? ''; ?>
<style>
.cert-topbar { display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem; }
.cert-topbar h2 { font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 2px;letter-spacing:-.02em; }
.cert-topbar p  { font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0; }

/* Stats */
.cert-stats { display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem; }
@media(max-width:767.98px){ .cert-stats{grid-template-columns:1fr;} }
.cert-stat { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:12px;padding:.875rem 1rem;display:flex;align-items:center;gap:.75rem; }
.cert-stat-icon { width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center; }
.cert-stat-icon svg { width:18px;height:18px; }
.cert-stat-val { font-size:1.5rem;font-weight:800;color:var(--ka-text,#1e293b);line-height:1; }
.cert-stat-lbl { font-size:.6875rem;color:var(--ka-text-muted,#64748b);font-weight:500;margin-top:2px; }

/* Filters */
.cert-filters { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:12px;padding:.875rem 1rem;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem; }
.cert-search-wrap { position:relative;flex:1;min-width:200px; }
.cert-search-icon { position:absolute;left:.75rem;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--ka-text-muted,#64748b);pointer-events:none; }
.cert-search { width:100%;height:36px;padding:0 .75rem 0 2.25rem;border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;font-size:.8125rem;background:var(--ka-bg,#f8fafc);outline:none;transition:all .2s; }
.cert-search:focus { border-color:var(--ka-primary,#6dabcf);background:#fff;box-shadow:0 0 0 3px rgba(109,171,207,.15); }
.cert-select { height:36px;padding:0 .75rem;border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;font-size:.8125rem;background:var(--ka-bg,#f8fafc);outline:none;cursor:pointer; }

/* Certificate cards (employee view) */
.cert-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.125rem;margin-bottom:1.5rem; }
.cert-card {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;overflow:hidden;
  display:flex;flex-direction:column;transition:box-shadow .2s,transform .2s;
}
.cert-card:hover { box-shadow:0 8px 28px rgba(0,0,0,.09);transform:translateY(-3px); }
.cert-card-banner {
  height:100px;position:relative;overflow:hidden;
  background:linear-gradient(135deg,var(--ka-navy,#1a3a5c) 0%,#254d75 55%,#2d6a9f 100%);
  display:flex;align-items:center;justify-content:center;flex-direction:column;
}
.cert-card-seal {
  width:56px;height:56px;border-radius:50%;
  background:rgba(255,255,255,.12);border:2px solid rgba(201,168,76,.6);
  display:flex;align-items:center;justify-content:center;
  font-size:.5rem;font-weight:800;color:#c9a84c;text-align:center;line-height:1.2;
  letter-spacing:.04em;
}
.cert-card-ribbon {
  position:absolute;top:10px;right:10px;
  background:#c9a84c;color:var(--ka-navy,#1a3a5c);
  font-size:.5625rem;font-weight:800;padding:2px 8px;border-radius:20px;
  letter-spacing:.04em;text-transform:uppercase;
}
.cert-card-body { padding:1rem;flex:1;display:flex;flex-direction:column; }
.cert-card-course { font-size:.9375rem;font-weight:700;color:var(--ka-text,#1e293b);margin-bottom:.25rem;line-height:1.3; }
.cert-card-category { font-size:.6875rem;color:var(--ka-primary,#6dabcf);font-weight:600;margin-bottom:.625rem; }
.cert-card-meta { display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:.875rem; }
.cert-card-meta-item { display:flex;align-items:center;gap:4px;font-size:.6875rem;color:var(--ka-text-muted,#64748b); }
.cert-card-meta-item svg { width:12px;height:12px; }
.cert-card-code {
  font-family:monospace;font-size:.6875rem;font-weight:700;
  background:var(--ka-bg,#f8fafc);border:1px solid var(--ka-border,#e2e8f0);
  border-radius:6px;padding:4px 8px;color:var(--ka-text,#1e293b);
  margin-bottom:.875rem;letter-spacing:.05em;
}
.cert-card-footer { display:flex;gap:.5rem;margin-top:auto;padding-top:.75rem;border-top:1px solid var(--ka-border,#e2e8f0); }
.cert-btn { flex:1;padding:.4375rem .5rem;border-radius:7px;text-align:center;font-size:.8125rem;font-weight:700;text-decoration:none;transition:all .15s;border:none;cursor:pointer; }
.cert-btn-view     { background:var(--ka-navy,#1a3a5c);color:#fff; }
.cert-btn-view:hover { background:#254d75;color:#fff; }
.cert-btn-download { background:var(--ka-accent,#e8f4fd);color:var(--ka-primary-deep,#4a8eb0); }
.cert-btn-download:hover { background:var(--ka-primary,#6dabcf);color:#fff; }

/* Manager table view */
.cert-panel { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;overflow:hidden; }
.cert-panel-hdr { padding:1rem 1.25rem;border-bottom:1px solid var(--ka-border,#e2e8f0);display:flex;align-items:center;justify-content:space-between; }
.cert-panel-title { font-size:.9rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0; }
.cert-table { width:100%;border-collapse:collapse; }
.cert-table th { padding:.75rem 1rem;text-align:left;font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--ka-text-muted,#64748b);border-bottom:1px solid var(--ka-border,#e2e8f0);background:var(--ka-bg,#f8fafc);white-space:nowrap; }
.cert-table td { padding:.875rem 1rem;border-bottom:1px solid var(--ka-border,#e2e8f0);font-size:.8125rem;color:var(--ka-text,#1e293b);vertical-align:middle; }
.cert-table tr:last-child td { border-bottom:none; }
.cert-table tbody tr:hover td { background:var(--ka-accent,#e8f4fd); }
.cert-avatar { width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--ka-primary,#6dabcf),#4a8eb0);display:flex;align-items:center;justify-content:center;font-size:.625rem;font-weight:700;color:#fff;flex-shrink:0; }

/* Empty */
.cert-empty { text-align:center;padding:3.5rem 1rem;background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px; }
.cert-empty svg { width:52px;height:52px;margin:0 auto 1rem;opacity:.15;display:block; }
.cert-empty h4 { font-size:1rem;font-weight:700;color:var(--ka-text,#1e293b);margin-bottom:.375rem; }
.cert-empty p  { font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0; }
</style>

<!-- ══ Topbar ════════════════════════════════════════════════ -->
<div class="cert-topbar animate__animated animate__fadeIn animate__fast">
  <div>
    <h2>Certificates</h2>
    <p><?= $is_manager ? 'Manage and review all issued certificates' : 'Your earned certificates' ?></p>
  </div>
</div>

<!-- ══ Stats ════════════════════════════════════════════════ -->
<div class="cert-stats animate__animated animate__fadeInUp animate__fast">
  <div class="cert-stat">
    <div class="cert-stat-icon" style="background:#fffbeb;color:#c9a84c;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
    </div>
    <div>
      <div class="cert-stat-val"><?= (int) $stats['total'] ?></div>
      <div class="cert-stat-lbl"><?= htmlspecialchars($stats['primary_label']) ?></div>
    </div>
  </div>
  <?php if ($is_manager): ?>
  <div class="cert-stat">
    <div class="cert-stat-icon" style="background:#ecfdf5;color:#22c55e;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
    <div>
      <div class="cert-stat-val"><?= (int) $stats['unique_courses'] ?></div>
      <div class="cert-stat-lbl">Courses Covered</div>
    </div>
  </div>
  <div class="cert-stat">
    <div class="cert-stat-icon" style="background:#eff6ff;color:#3b82f6;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    </div>
    <div>
      <div class="cert-stat-val"><?= (int) $stats['unique_recipients'] ?></div>
      <div class="cert-stat-lbl">Unique Recipients</div>
    </div>
  </div>
  <?php else: ?>
  <div class="cert-stat">
    <div class="cert-stat-icon" style="background:#ecfdf5;color:#22c55e;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div>
      <div class="cert-stat-val"><?= (int) $stats['ready_download'] ?></div>
      <div class="cert-stat-lbl">Ready to Download</div>
    </div>
  </div>
  <div class="cert-stat">
    <div class="cert-stat-icon" style="background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    </div>
    <div>
      <div class="cert-stat-val"><?= htmlspecialchars($stats['latest_earned_label']) ?></div>
      <div class="cert-stat-lbl">Latest Earned</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ══ Filters (manager only) ════════════════════════════════ -->
<?php if ($is_manager): ?>
<div class="cert-filters animate__animated animate__fadeInUp animate__fast" style="animation-delay:.05s;">
  <div class="cert-search-wrap">
    <svg class="cert-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="cert-search" id="certSearch" placeholder="Search student, course, certificate code…">
  </div>
  <select class="cert-select" id="certFilterCourse">
    <option value="">All Courses</option>
    <?php foreach ($courses as $course): ?>
    <option value="<?= $course->id ?>"><?= htmlspecialchars($course->title) ?></option>
    <?php endforeach; ?>
  </select>
  <span style="font-size:.8125rem;font-weight:600;color:var(--ka-text-muted,#64748b);" id="certCount">
    <?= (int) $stats['total'] ?> certificate<?= (int) $stats['total'] !== 1 ? 's' : '' ?>
  </span>
</div>
<?php endif; ?>

<!-- ══ Employee: Card Grid ═══════════════════════════════════ -->
<?php if ( ! $is_manager): ?>
  <?php if ( ! empty($certificates)): ?>
  <div class="cert-grid animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s;">
    <?php foreach ($certificates as $i => $cert): ?>
    <div class="cert-card" style="animation-delay:<?= $i * 0.05 ?>s;">
      <div class="cert-card-banner">
        <div class="cert-card-seal">KABAGA<br>ACADEMY<br>LCP</div>
        <div class="cert-card-ribbon">✓ Verified</div>
      </div>
      <div class="cert-card-body">
        <div class="cert-card-course"><?= htmlspecialchars($cert->course_title) ?></div>
        <div class="cert-card-category"><?= htmlspecialchars($cert->category_name ?? '—') ?></div>
        <div class="cert-card-meta">
          <div class="cert-card-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <?= htmlspecialchars($cert->issued_at_display ?? '') ?>
          </div>
          <?php if ( ! empty($cert->modality_name)): ?>
          <div class="cert-card-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/></svg>
            <?= htmlspecialchars($cert->modality_name) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="cert-card-code"><?= htmlspecialchars($cert->certificate_code ?? '') ?></div>
        <div class="cert-card-footer">
          <a href="<?= base_url('index.php/certificates/view/'.$cert->id) ?>" class="cert-btn cert-btn-view">View</a>
          <a href="<?= base_url('index.php/certificates/download/'.$cert->id) ?>" class="cert-btn cert-btn-download">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:3px;vertical-align:middle;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download PDF
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="cert-empty animate__animated animate__fadeInUp animate__fast">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
    <h4>No certificates yet</h4>
    <p>Complete all modules and post-assessments in a course to earn your certificate.</p>
  </div>
  <?php endif; ?>

<!-- ══ Manager: Table View ════════════════════════════════════ -->
<?php else: ?>
<div class="cert-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s;">
  <div class="cert-panel-hdr">
    <h3 class="cert-panel-title">All Issued Certificates</h3>
    <span style="font-size:.75rem;color:var(--ka-text-muted,#64748b);"><?= (int) $stats['total'] ?> total</span>
  </div>

  <?php if ( ! empty($certificates)): ?>
  <table class="cert-table" id="certTable">
    <thead>
      <tr>
        <th>Student</th>
        <th>Course</th>
        <th>Certificate Code</th>
        <th>Issued On</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($certificates as $cert): ?>
      <tr data-title="<?= htmlspecialchars(strtolower(($cert->student_name ?? '') . ' ' . ($cert->course_title ?? '') . ' ' . ($cert->certificate_code ?? ''))) ?>">
        <td>
          <div style="display:flex;align-items:center;gap:.625rem;">
            <div class="cert-avatar"><?= htmlspecialchars($cert->student_initials ?? '') ?></div>
            <div>
              <div style="font-weight:600;"><?= htmlspecialchars($cert->student_name ?? '—') ?></div>
              <div style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);"><?= htmlspecialchars($cert->employee_id ?? '') ?></div>
            </div>
          </div>
        </td>
        <td>
          <div style="font-weight:600;"><?= htmlspecialchars($cert->course_title ?? '—') ?></div>
          <div style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);"><?= htmlspecialchars($cert->category_name ?? '') ?></div>
        </td>
        <td><span style="font-family:monospace;font-size:.75rem;font-weight:700;"><?= htmlspecialchars($cert->certificate_code ?? '') ?></span></td>
        <td style="color:var(--ka-text-muted,#64748b);"><?= htmlspecialchars($cert->issued_at_display ?? '') ?></td>
        <td>
          <div style="display:flex;gap:.375rem;">
            <a href="<?= base_url('index.php/certificates/view/'.$cert->id) ?>"
               style="padding:4px 10px;border-radius:7px;border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;font-size:.75rem;font-weight:600;text-decoration:none;color:var(--ka-text,#1e293b);">
              View
            </a>
            <a href="<?= base_url('index.php/certificates/download/'.$cert->id) ?>"
               style="padding:4px 10px;border-radius:7px;background:var(--ka-accent,#e8f4fd);border:none;font-size:.75rem;font-weight:600;text-decoration:none;color:var(--ka-primary-deep,#4a8eb0);">
              PDF
            </a>
            <?php if ($is_admin): ?>
            <button type="button"
                    class="cert-revoke-btn"
                    data-revoke-form="revokeForm_<?= (int) $cert->id ?>"
                    style="padding:4px 10px;border-radius:7px;border:1.5px solid #fecaca;background:#fef2f2;font-size:.75rem;font-weight:600;color:#dc2626;cursor:pointer;">
              Revoke
            </button>
            <form id="revokeForm_<?= $cert->id ?>" method="post" action="<?= base_url('index.php/certificates/revoke/'.$cert->id) ?>" style="display:none;">
              <input type="hidden" name="<?= html_escape($csrf_field_name ?? '') ?>" value="<?= html_escape($csrf_hash ?? '') ?>">
              <input type="hidden" name="remarks" value="Revoked by administrator.">
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="cert-empty" style="border:none;">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
    <h4>No certificates yet</h4>
    <p>Certificates are auto-issued when employees complete all course requirements.</p>
  </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var certSearchInput = document.getElementById('certSearch');
  var certCountSpan = document.getElementById('certCount');
  if (!certSearchInput) {
    return;
  }
  var tableRows = document.querySelectorAll('#certTable tbody tr');
  certSearchInput.addEventListener('input', function () {
    var kw = this.value.toLowerCase();
    var visible = 0;
    for (var i = 0; i < tableRows.length; i++) {
      var row = tableRows[i];
      var title = row.getAttribute('data-title') || '';
      var show = !kw || title.indexOf(kw) !== -1;
      row.style.display = show ? '' : 'none';
      if (show) {
        visible++;
      }
    }
    if (certCountSpan) {
      certCountSpan.textContent = visible + ' certificate' + (visible !== 1 ? 's' : '');
    }
  });

  var revokeBtns = document.querySelectorAll('.cert-revoke-btn');
  for (var j = 0; j < revokeBtns.length; j++) {
    revokeBtns[j].addEventListener('click', function () {
      var formId = this.getAttribute('data-revoke-form');
      KA.confirm({
        title: 'Revoke Certificate?',
        text: 'This will notify the student and cannot be undone easily.',
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