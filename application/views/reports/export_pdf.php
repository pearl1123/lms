<?php defined('BASEPATH') OR exit('No direct script access allowed');
$meta      = $meta ?? ($bundle['meta'] ?? []);
$workspace = $bundle['workspace'] ?? [];
$kpis      = $workspace['kpis'] ?? [];
$hr        = $workspace['hrmis'] ?? [];
$top       = $workspace['top_courses'] ?? [];
$insights  = $workspace['insights'] ?? [];
$charts    = $workspace['charts'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; }
  body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11px;
    color: #1e293b;
    margin: 0;
    padding: 24px 28px;
    line-height: 1.45;
  }
  .hdr {
    border-bottom: 3px solid #6dabcf;
    padding-bottom: 14px;
    margin-bottom: 20px;
  }
  .brand {
    font-size: 10px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #64748b;
    margin: 0 0 4px;
  }
  h1 {
    margin: 0;
    font-size: 22px;
    color: #1a3a5c;
    font-weight: bold;
  }
  .sub {
    margin: 6px 0 0;
    color: #64748b;
    font-size: 11px;
  }
  .meta-row {
    margin-top: 10px;
    font-size: 9px;
    color: #94a3b8;
  }
  .kpi-grid {
    width: 100%;
    border-collapse: separate;
    border-spacing: 8px;
    margin: 0 0 18px;
  }
  .kpi-grid td {
    width: 25%;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 12px;
    vertical-align: top;
  }
  .kpi-label {
    font-size: 8px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
    margin-bottom: 4px;
  }
  .kpi-val {
    font-size: 18px;
    font-weight: bold;
    color: #1a3a5c;
  }
  h2 {
    font-size: 13px;
    color: #1a3a5c;
    margin: 18px 0 8px;
    padding-bottom: 4px;
    border-bottom: 1px solid #e2e8f0;
  }
  .insight {
    padding: 8px 10px;
    margin-bottom: 6px;
    border-radius: 6px;
    font-size: 10px;
  }
  .insight-warn { background: #fffbeb; border-left: 3px solid #f59e0b; }
  .insight-info { background: #eff6ff; border-left: 3px solid #3b82f6; }
  .insight-ok { background: #ecfdf5; border-left: 3px solid #22c55e; }
  table.data {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
    font-size: 10px;
  }
  table.data th {
    text-align: left;
    background: #1a3a5c;
    color: #fff;
    padding: 6px 8px;
    font-size: 9px;
  }
  table.data td {
    padding: 6px 8px;
    border-bottom: 1px solid #f1f5f9;
  }
  table.data tr:nth-child(even) td { background: #f8fafc; }
  .chart-block {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 10px;
  }
  .chart-block h3 {
    margin: 0 0 6px;
    font-size: 10px;
    color: #475569;
  }
  .chart-row {
    font-size: 9px;
    padding: 2px 0;
    border-bottom: 1px dotted #e2e8f0;
  }
  .footer {
    margin-top: 24px;
    padding-top: 10px;
    border-top: 1px solid #e2e8f0;
    font-size: 8px;
    color: #94a3b8;
    text-align: center;
  }
</style>
</head>
<body>
  <div class="hdr">
    <p class="brand">kaBAGA Academy — LMS Analytics</p>
    <h1>Executive Analytics Summary</h1>
    <p class="sub">Platform learning performance and organizational training health</p>
    <p class="meta-row">
      Exported by <?= htmlspecialchars($meta['exported_by'] ?? '') ?>
      <?php if ( ! empty($meta['employee_id'])): ?> (<?= htmlspecialchars($meta['employee_id']) ?>)<?php endif; ?>
      · <?= htmlspecialchars($meta['export_date'] ?? '') ?>
      · Range: <?= htmlspecialchars($meta['range_label'] ?? '') ?>
    </p>
  </div>

  <table class="kpi-grid">
    <tr>
      <td><div class="kpi-label">Total learners</div><div class="kpi-val"><?= number_format((int) ($kpis['total_learners'] ?? 0)) ?></div></td>
      <td><div class="kpi-label">Active in range</div><div class="kpi-val"><?= number_format((int) ($kpis['active_in_range'] ?? 0)) ?></div></td>
      <td><div class="kpi-label">Completion rate</div><div class="kpi-val"><?= (int) ($kpis['completion_rate'] ?? 0) ?>%</div></td>
      <td><div class="kpi-label">Certificates</div><div class="kpi-val"><?= number_format((int) ($kpis['certificates_total'] ?? 0)) ?></div></td>
    </tr>
    <tr>
      <td><div class="kpi-label">Published courses</div><div class="kpi-val"><?= number_format((int) ($kpis['courses_published'] ?? 0)) ?></div></td>
      <td><div class="kpi-label">Pending approvals</div><div class="kpi-val"><?= number_format((int) ($kpis['pending_approvals'] ?? 0)) ?></div></td>
      <td><div class="kpi-label">At-risk learners</div><div class="kpi-val"><?= number_format((int) ($kpis['at_risk_count'] ?? 0)) ?></div></td>
      <td><div class="kpi-label">Avg progress</div><div class="kpi-val"><?= (int) ($kpis['avg_progress'] ?? 0) ?>%</div></td>
    </tr>
  </table>

  <?php if ( ! empty($insights)): ?>
  <h2>Key insights</h2>
  <?php foreach ($insights as $ins):
    $cls = 'insight-info';
    if (($ins['type'] ?? '') === 'warning') $cls = 'insight-warn';
    if (($ins['type'] ?? '') === 'success') $cls = 'insight-ok';
  ?>
  <div class="insight <?= $cls ?>"><?= htmlspecialchars($ins['text'] ?? '') ?></div>
  <?php endforeach; ?>
  <?php endif; ?>

  <h2>Top courses</h2>
  <?php if (empty($top)): ?>
  <p>No course enrollment data available.</p>
  <?php else: ?>
  <table class="data">
    <thead><tr><th>Course</th><th>Enrollments</th><th>Avg progress</th></tr></thead>
    <tbody>
      <?php foreach (array_slice($top, 0, 8) as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c->course_title ?? '') ?></td>
        <td><?= (int) ($c->enrollment_count ?? 0) ?></td>
        <td><?= (int) ($c->avg_progress ?? 0) ?>%</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <h2>Trend summary</h2>
  <?php
  $trend_keys = [
    'enrollment_trends' => 'Enrollments by month',
    'certificate_issuance_trend' => 'Certificates by month',
  ];
  foreach ($trend_keys as $key => $label):
    $chart = $charts[$key] ?? ['labels' => [], 'values' => []];
    $labels = $chart['labels'] ?? [];
    $values = $chart['values'] ?? [];
    if (empty($labels) || ($labels[0] ?? '') === 'No Data') continue;
  ?>
  <div class="chart-block">
    <h3><?= htmlspecialchars($label) ?></h3>
    <?php foreach ($labels as $i => $ym): ?>
    <div class="chart-row"><?= htmlspecialchars($ym) ?>: <strong><?= (int) ($values[$i] ?? 0) ?></strong></div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>

  <h2>HRMIS integration</h2>
  <table class="data">
    <tr><td><strong>Status</strong></td><td><?= ! empty($hr['connected']) ? 'Connected' : 'Disconnected' ?></td></tr>
    <tr><td><strong>Departments mapped</strong></td><td><?= (int) ($hr['department_count'] ?? 0) ?></td></tr>
    <tr><td><strong>Active employees</strong></td><td><?= number_format((int) ($hr['employee_count'] ?? 0)) ?></td></tr>
  </table>

  <div class="footer">Confidential — kaBAGA Academy LMS · Generated <?= date('Y-m-d H:i') ?></div>
</body>
</html>
