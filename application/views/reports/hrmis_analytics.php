<?php defined('BASEPATH') OR exit('No direct script access allowed');
$hr = $report['hrmis'] ?? [];
$connected = ! empty($hr['connected']);
?>
<section class="rpt-section" id="rpt-section-hrmis" data-rpt-section="hrmis" data-rpt-keywords="hrmis department compliance training coverage division" hidden>
  <div class="rpt-metric-grid">
    <div class="rpt-metric">
      <span class="rpt-metric-label">Connection</span>
      <span class="rpt-status <?= $connected ? 'rpt-status--ok' : 'rpt-status--err' ?>"><?= $connected ? 'Connected' : 'Disconnected' ?></span>
    </div>
    <div class="rpt-metric">
      <span class="rpt-metric-label">Departments</span>
      <strong class="rpt-metric-value"><?= (int) ($hr['department_count'] ?? 0) ?></strong>
    </div>
    <div class="rpt-metric">
      <span class="rpt-metric-label">Active employees</span>
      <strong class="rpt-metric-value"><?= number_format((int) ($hr['employee_count'] ?? 0)) ?></strong>
    </div>
  </div>

  <?php if ( ! $connected): ?>
    <?php $this->load->view('components/empty_state', [
      'emoji' => '🔗',
      'title' => 'HRMIS not connected',
      'description' => 'Configure the HRMIS database connection to unlock department-level training analytics.',
      'modifier' => 'ka-empty--wide',
    ]); ?>
  <?php else: ?>
  <article class="rpt-card" style="margin-top:1rem;">
    <header class="rpt-card-hdr">
      <h2 class="rpt-card-title">Training coverage matrix</h2>
      <p class="rpt-card-desc">Department participation overview (enrollment mapping coming soon)</p>
    </header>
    <div class="rpt-card-body">
      <?php $matrix = $hr['compliance_matrix'] ?? []; ?>
      <?php if (empty($matrix)): ?>
        <p class="rpt-help">No departments returned from HRMIS.</p>
      <?php else: ?>
      <div class="rpt-table-wrap">
        <table class="rpt-table">
          <thead>
            <tr><th>Department</th><th>Learners</th><th>Coverage</th><th>Compliance</th></tr>
          </thead>
          <tbody>
            <?php foreach ($matrix as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['learners'] ?? '—') ?></td>
              <td><?= htmlspecialchars($row['coverage'] ?? '—') ?></td>
              <td><span class="rpt-pill"><?= htmlspecialchars($row['compliance'] ?? '—') ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </article>

  <?php if ( ! empty($hr['departments'])): ?>
  <article class="rpt-card" style="margin-top:1rem;">
    <header class="rpt-card-hdr">
      <h2 class="rpt-card-title">Mapped departments</h2>
    </header>
    <div class="rpt-card-body">
      <div class="rpt-chips">
        <?php foreach ($hr['departments'] as $dept): ?>
        <span class="rpt-chip"><?= htmlspecialchars($dept->name ?? '') ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </article>
  <?php endif; ?>
  <?php endif; ?>
</section>
