<?php defined('BASEPATH') OR exit('No direct script access allowed');
$kpis = $report['kpis'] ?? [];
$deltas = $report['kpi_deltas'] ?? [];
$insights = $report['insights'] ?? [];
?>
<section class="rpt-section is-active" id="rpt-section-overview" data-rpt-section="overview" data-rpt-keywords="overview executive kpi learners courses completion certificates">
  <div class="rpt-kpi-grid">
    <?php
    $cards = [
      ['label' => 'Total learners', 'value' => $kpis['total_learners'] ?? 0, 'delta' => null, 'sub' => 'Employee accounts'],
      ['label' => 'Active learners', 'value' => $kpis['active_in_range'] ?? 0, 'delta' => $deltas['active']['label'] ?? '', 'sub' => 'Module activity in range'],
      ['label' => 'Courses published', 'value' => $kpis['courses_published'] ?? 0, 'delta' => null, 'sub' => ($kpis['courses_draft'] ?? 0) . ' in draft'],
      ['label' => 'Completion rate', 'value' => ($kpis['completion_rate'] ?? 0) . '%', 'delta' => $deltas['completion']['label'] ?? '', 'sub' => 'Avg progress ' . (int) ($kpis['avg_progress'] ?? 0) . '%'],
      ['label' => 'Certificates issued', 'value' => $kpis['certificates_total'] ?? 0, 'delta' => $deltas['certificates']['label'] ?? '', 'sub' => ($kpis['certificates_week'] ?? 0) . ' this week'],
      ['label' => 'Pending approvals', 'value' => $kpis['pending_approvals'] ?? 0, 'delta' => null, 'sub' => 'Enrollment queue'],
      ['label' => 'At-risk learners', 'value' => $kpis['at_risk_count'] ?? 0, 'delta' => null, 'sub' => 'Low progress enrollees'],
    ];
    foreach ($cards as $card):
    ?>
    <article class="rpt-kpi-card">
      <span class="rpt-kpi-label"><?= htmlspecialchars($card['label']) ?></span>
      <span class="rpt-kpi-value"><?= is_numeric($card['value']) ? number_format($card['value']) : htmlspecialchars((string) $card['value']) ?></span>
      <?php if ( ! empty($card['delta'])): ?>
      <span class="rpt-kpi-delta"><?= htmlspecialchars($card['delta']) ?></span>
      <?php endif; ?>
      <span class="rpt-kpi-sub"><?= htmlspecialchars($card['sub']) ?></span>
    </article>
    <?php endforeach; ?>
  </div>

  <?php if ( ! empty($insights)): ?>
  <div class="rpt-insights">
    <?php foreach ($insights as $ins): ?>
    <div class="rpt-insight rpt-insight--<?= htmlspecialchars($ins['type'] ?? 'info') ?>">
      <?= htmlspecialchars($ins['text'] ?? '') ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
