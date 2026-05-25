<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$hr = $hrmis ?? [];
$status = $hr['status'] ?? 'disconnected';
$statusClass = 'stg-status--disconnected';
$statusLabel = $hr['status_label'] ?? 'Unknown';
if ($status === 'connected') {
  $statusClass = 'stg-status--connected';
} elseif ($status === 'disconnected') {
  $statusClass = 'stg-status--disconnected';
} elseif ($status === 'delayed') {
  $statusClass = 'stg-status--delayed';
}
$lastSync = $hr['last_sync'] ?? null;
if ($lastSync) {
  $lastSync = is_numeric($lastSync) ? date('M j, Y g:i A', (int) $lastSync) : $lastSync;
} else {
  $lastSync = '—';
}
?>
<section class="stg-panel" role="tabpanel" id="stg-panel-hrmis" data-stg-tab="hrmis" data-stg-keywords="hrmis integration sync departments employees" hidden>
  <div class="stg-card">
    <div class="stg-card-hdr">
      <h2 class="stg-card-title">HRMIS integration</h2>
      <p class="stg-card-kicker">Employee directory sync for invitations and eligibility rules.</p>
    </div>
    <div class="stg-card-body">
      <div class="stg-metric-grid">
        <div class="stg-metric">
          <div class="stg-metric-value"><span class="stg-status <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span></div>
          <div class="stg-metric-label">Connection</div>
        </div>
        <div class="stg-metric">
          <div class="stg-metric-value"><?= htmlspecialchars($lastSync) ?></div>
          <div class="stg-metric-label">Last sync</div>
        </div>
        <div class="stg-metric">
          <div class="stg-metric-value"><?= (int) ($hr['department_count'] ?? 0) ?></div>
          <div class="stg-metric-label">Mapped departments</div>
        </div>
        <div class="stg-metric">
          <div class="stg-metric-value"><?= (int) ($hr['employee_count'] ?? 0) ?></div>
          <div class="stg-metric-label">Eligible employees</div>
        </div>
      </div>
      <p class="stg-help">Connection credentials and sync schedules are managed in your HRMIS configuration. Course-level invitations use Phase 2 HRMIS rules from the course workspace.</p>
      <a href="<?= site_url('manage_courses') ?>" class="stg-btn-outline">Open course workspace</a>
    </div>
  </div>
</section>
