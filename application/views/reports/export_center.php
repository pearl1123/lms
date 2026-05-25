<?php defined('BASEPATH') OR exit('No direct script access allowed');
$presets = $report['export_presets'] ?? [];
$range   = $report['range'] ?? '30d';

$export_url = function ($format, $section) use ($range) {
    return site_url('reports/export/' . $format . '?range=' . rawurlencode($range) . '&section=' . rawurlencode($section));
};
?>
<section class="rpt-section" id="rpt-section-export" data-rpt-section="export" data-rpt-keywords="export csv pdf excel download scheduled" hidden>
  <article class="rpt-card">
    <header class="rpt-card-hdr">
      <h2 class="rpt-card-title">Export center</h2>
      <p class="rpt-card-desc">Downloads respect the date range selected at the top of this page (<?= htmlspecialchars($report['range'] ?? '30d') ?>).</p>
    </header>
    <div class="rpt-card-body">
      <div class="rpt-export-actions rpt-export-actions--primary">
        <a href="<?= html_escape($export_url('pdf', 'all')) ?>" class="rpt-btn rpt-btn--primary rpt-export-link" data-rpt-export="pdf">
          <span class="rpt-export-icon" aria-hidden="true">PDF</span>
          Executive summary (PDF)
        </a>
        <a href="<?= html_escape($export_url('excel', 'all')) ?>" class="rpt-btn rpt-btn--primary rpt-export-link" data-rpt-export="excel">
          <span class="rpt-export-icon" aria-hidden="true">XLS</span>
          Full workbook (Excel)
        </a>
        <a href="<?= html_escape($export_url('csv', 'all')) ?>" class="rpt-btn rpt-btn--primary rpt-export-link" data-rpt-export="csv">
          <span class="rpt-export-icon" aria-hidden="true">CSV</span>
          Full pack (CSV)
        </a>
      </div>

      <div class="rpt-export-grid">
        <?php foreach ($presets as $preset):
          $format  = $preset['format'] ?? 'csv';
          $section = $preset['section'] ?? 'all';
          if (in_array($preset['id'] ?? '', ['executive_pdf', 'full_excel', 'full_csv'], true)) {
              continue;
          }
          $href = $export_url($format, $section);
        ?>
        <div class="rpt-export-card">
          <div class="rpt-export-card-top">
            <span class="rpt-export-badge"><?= strtoupper(htmlspecialchars($format)) ?></span>
            <h3 class="rpt-export-title"><?= htmlspecialchars($preset['label'] ?? '') ?></h3>
          </div>
          <p class="rpt-export-desc"><?= htmlspecialchars($preset['desc'] ?? '') ?></p>
          <a href="<?= html_escape($href) ?>" class="rpt-btn rpt-btn--outline rpt-export-link" data-rpt-export="<?= htmlspecialchars($format) ?>">
            Download <?= strtoupper(htmlspecialchars($format)) ?>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </article>

  <article class="rpt-card" style="margin-top:1rem;">
    <header class="rpt-card-hdr">
      <h2 class="rpt-card-title">Export notes</h2>
    </header>
    <div class="rpt-card-body">
      <ul class="rpt-export-notes">
        <li>Files are named <code>LMS_Report_[Section]_YYYY-MM-DD</code> with the correct extension.</li>
        <li>Excel exports use multi-sheet workbooks (Overview, Courses, Learners, Certificates).</li>
        <li>Change the date range filter before exporting to refresh active-learner and certificate windows.</li>
      </ul>
    </div>
  </article>
</section>
