<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$report = $report ?? [];
$range  = $report['range'] ?? '30d';
$charts = $report['charts'] ?? [];
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/reports.css'); ?>">

<div class="rpt-workspace animate__animated animate__fadeIn animate__fast">
  <header class="rpt-page-head">
    <div class="rpt-page-head-main">
      <p class="rpt-eyebrow">Analytics workspace</p>
      <h1 class="rpt-page-title">Reports &amp; Analytics</h1>
      <p class="rpt-page-subtitle">Insight-first view of learning performance, enrollments, and organizational training health.</p>
      <p class="rpt-updated">Last updated <?= htmlspecialchars($report['last_updated'] ?? '') ?></p>
    </div>
    <div class="rpt-page-head-actions">
      <form method="get" action="<?= site_url('reports') ?>" class="rpt-filter-form" id="rptRangeForm">
        <label class="visually-hidden" for="rptRange">Date range</label>
        <select name="range" id="rptRange" class="rpt-select">
          <?php
          $ranges = ['7d' => 'Last 7 days', '30d' => 'Last 30 days', '90d' => 'Last 90 days', '12m' => 'Last 12 months', 'all' => 'All time'];
          foreach ($ranges as $val => $label):
          ?>
          <option value="<?= htmlspecialchars($val) ?>" <?= $range === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <div class="rpt-search-wrap">
        <label class="visually-hidden" for="rptSearch">Search reports</label>
        <input type="search" id="rptSearch" class="rpt-search" placeholder="Search reports…" autocomplete="off">
      </div>
      <div class="rpt-export-dropdown">
        <button type="button" class="rpt-btn rpt-btn--outline" id="rptExportToggle" aria-expanded="false" aria-haspopup="true">Export</button>
        <div class="rpt-export-menu" id="rptExportMenu" hidden>
          <a href="<?= site_url('reports/export/pdf?range=' . rawurlencode($range)) ?>" class="rpt-export-menu-item rpt-export-link" data-rpt-export="pdf">Executive PDF</a>
          <a href="<?= site_url('reports/export/excel?range=' . rawurlencode($range) . '&section=all') ?>" class="rpt-export-menu-item rpt-export-link" data-rpt-export="excel">Excel workbook</a>
          <a href="<?= site_url('reports/export/csv?range=' . rawurlencode($range) . '&section=all') ?>" class="rpt-export-menu-item rpt-export-link" data-rpt-export="csv">CSV pack</a>
        </div>
      </div>
    </div>
  </header>

  <div class="rpt-layout">
    <aside class="rpt-aside">
      <?php $this->load->view('reports/report_sidebar'); ?>
      <div class="rpt-insights-panel">
        <h3 class="rpt-insights-panel-title">Quick insights</h3>
        <?php foreach (($report['insights'] ?? []) as $ins): ?>
        <p class="rpt-insight-line rpt-insight-line--<?= htmlspecialchars($ins['type'] ?? 'info') ?>"><?= htmlspecialchars($ins['text'] ?? '') ?></p>
        <?php endforeach; ?>
        <?php if (empty($report['insights'])): ?>
        <p class="rpt-help">Insights appear as your platform accumulates learning data.</p>
        <?php endif; ?>
      </div>
    </aside>

    <div class="rpt-main">
      <?php
      $this->load->view('reports/executive_overview', get_defined_vars());
      $this->load->view('reports/learning_analytics', get_defined_vars());
      $this->load->view('reports/learning_notes_analytics', get_defined_vars());
      $this->load->view('reports/course_performance', get_defined_vars());
      $this->load->view('reports/learner_insights', get_defined_vars());
      $this->load->view('reports/certificate_analytics', get_defined_vars());
      $this->load->view('reports/hrmis_analytics', get_defined_vars());
      $this->load->view('reports/export_center', get_defined_vars());
      ?>
    </div>
  </div>
</div>

<script>
  window.REPORTS_CHARTS = <?= json_encode($charts, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script defer src="<?= base_url('assets/js/reports.js'); ?>"></script>
