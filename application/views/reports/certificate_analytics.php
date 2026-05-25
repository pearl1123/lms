<?php defined('BASEPATH') OR exit('No direct script access allowed');
$kpis = $report['kpis'] ?? [];
$recent = $report['recent_certificates'] ?? [];
?>
<section class="rpt-section" id="rpt-section-certificates" data-rpt-section="certificates" data-rpt-keywords="certificate issued verification serial expiry" hidden>
  <div class="rpt-kpi-grid rpt-kpi-grid--compact">
    <article class="rpt-kpi-card">
      <span class="rpt-kpi-label">Total issued</span>
      <span class="rpt-kpi-value"><?= number_format((int) ($kpis['certificates_total'] ?? 0)) ?></span>
      <span class="rpt-kpi-sub">All time</span>
    </article>
    <article class="rpt-kpi-card">
      <span class="rpt-kpi-label">This week</span>
      <span class="rpt-kpi-value"><?= number_format((int) ($kpis['certificates_week'] ?? 0)) ?></span>
      <span class="rpt-kpi-sub">Last 7 days</span>
    </article>
    <article class="rpt-kpi-card">
      <span class="rpt-kpi-label">Ready to issue</span>
      <span class="rpt-kpi-value"><?= number_format(count($report['certificate_ready'] ?? [])) ?></span>
      <span class="rpt-kpi-sub">Awaiting certificate</span>
    </article>
  </div>

  <div class="rpt-chart-grid rpt-chart-grid--2" style="margin-top:1rem;">
    <article class="rpt-card">
      <header class="rpt-card-hdr">
        <h2 class="rpt-card-title">Issuance trend</h2>
        <p class="rpt-card-desc">Certificates issued per month</p>
      </header>
      <div class="rpt-card-body">
        <div class="rpt-chart-wrap"><canvas id="rptCertTrend" aria-label="Certificate issuance trend"></canvas></div>
      </div>
    </article>
    <article class="rpt-card">
      <header class="rpt-card-hdr">
        <h2 class="rpt-card-title">Recent certificates</h2>
        <p class="rpt-card-desc">Latest issued credentials</p>
      </header>
      <div class="rpt-card-body">
        <?php if (empty($recent)): ?>
          <?php $this->load->view('components/empty_state', [
            'emoji' => '🎓',
            'title' => 'No certificates issued yet',
            'description' => 'Certificates appear when learners complete courses and credentials are generated.',
          ]); ?>
        <?php else: ?>
        <ul class="rpt-list">
          <?php foreach ($recent as $row): ?>
          <li class="rpt-list-item">
            <div>
              <strong><?= htmlspecialchars($row->fullname ?? '') ?></strong>
              <span class="rpt-list-meta"><?= htmlspecialchars($row->course_title ?? '') ?></span>
            </div>
            <time class="rpt-list-time"><?= ! empty($row->issued_at) ? date('M j, Y', strtotime($row->issued_at)) : '—' ?></time>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </article>
  </div>
</section>
