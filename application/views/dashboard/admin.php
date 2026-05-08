<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$dashboard_data = $dashboard_data ?? ['stats' => [], 'courses' => [], 'notifications' => [], 'certificates' => [], 'requests' => [], 'charts' => []];
$stats           = $dashboard_data['stats'] ?? [];
$charts          = $dashboard_data['charts'] ?? [];
$courses_bucket  = $dashboard_data['courses'] ?? [];
$top_courses     = $courses_bucket['top_by_enrollments'] ?? [];
$pending_rows    = $dashboard_data['requests'] ?? [];
$activity_feed   = $dashboard_data['notifications'] ?? [];

$total_users          = (int) ($stats['total_users'] ?? 0);
$total_courses        = (int) ($stats['total_courses'] ?? 0);
$pending_count        = (int) ($stats['pending_requests_count'] ?? 0);
$cert_total           = (int) ($stats['certificate_total'] ?? 0);
$cert_today           = (int) ($stats['certificate_issued_today'] ?? 0);
$cert_week            = (int) ($stats['certificate_issued_week'] ?? 0);

$dash_icon = static function ($type_key) {
    switch ((string) $type_key) {
        case 'certificate': return 'A';
        case 'approval': return 'OK';
        case 'rejection': return '!';
        case 'request': return '+';
        default: return 'i';
    }
};
?>
<?php echo $alerts_partial_html ?? ''; ?>

<div class="db-page db-page--admin">
  <header class="db-dash-hero db-dash-hero--admin">
    <div class="db-dash-hero-top">
      <div>
        <p class="db-dash-hero-eyebrow">Control center</p>
        <h1 class="db-dash-hero-title">System overview</h1>
        <p class="db-dash-hero-lead">Live signals across users, courses, enrollments, and certificates.</p>
      </div>
      <a href="<?= site_url('enrollments/requests'); ?>" class="db-dash-pill-btn">Enrollment queue</a>
    </div>
    <ul class="db-dash-hero-metrics" aria-label="Key system metrics">
      <li><span class="db-dash-metric-val"><?= number_format($total_users); ?></span><span class="db-dash-metric-lbl">Users</span></li>
      <li><span class="db-dash-metric-val"><?= number_format($total_courses); ?></span><span class="db-dash-metric-lbl">Active courses</span></li>
      <li><span class="db-dash-metric-val"><?= number_format($pending_count); ?></span><span class="db-dash-metric-lbl">Pending requests</span></li>
      <li><span class="db-dash-metric-val"><?= number_format($cert_today); ?></span><span class="db-dash-metric-lbl">Certs today</span></li>
      <li><span class="db-dash-metric-val"><?= number_format($cert_week); ?></span><span class="db-dash-metric-lbl">Certs (7d)</span></li>
      <li><span class="db-dash-metric-val"><?= number_format($cert_total); ?></span><span class="db-dash-metric-lbl">Certs all-time</span></li>
    </ul>
  </header>

  <section class="db-kpi-grid" aria-label="Admin KPI snapshot">
    <article class="db-kpi-card db-kpi-card--dash"><div class="db-kpi-head"><span class="db-kpi-label">Total users</span><span class="db-kpi-icon">U</span></div><div class="db-kpi-value"><?= number_format($total_users); ?></div><div class="db-kpi-sub">Platform accounts</div></article>
    <article class="db-kpi-card db-kpi-card--dash"><div class="db-kpi-head"><span class="db-kpi-label">Total courses</span><span class="db-kpi-icon">C</span></div><div class="db-kpi-value"><?= number_format($total_courses); ?></div><div class="db-kpi-sub">Published &amp; available</div></article>
    <article class="db-kpi-card db-kpi-card--dash"><div class="db-kpi-head"><span class="db-kpi-label">Pending approvals</span><span class="db-kpi-icon">R</span></div><div class="db-kpi-value"><?= number_format($pending_count); ?></div><div class="db-kpi-sub">Enrollment requests</div></article>
    <article class="db-kpi-card db-kpi-card--dash"><div class="db-kpi-head"><span class="db-kpi-label">Certificates issued</span><span class="db-kpi-icon">A</span></div><div class="db-kpi-value"><?= number_format($cert_total); ?></div><div class="db-kpi-sub">Historical total</div></article>
  </section>

  <section class="db-dash-chart-grid db-dash-chart-grid--2">
    <article class="db-card db-card--elevated">
      <header class="db-card-header"><h2 class="db-card-title">System growth trend</h2></header>
      <div class="db-card-body"><div class="db-chart-wrap db-dash-chart-md"><canvas id="adminUsersGrowthChart"></canvas></div></div>
    </article>
    <article class="db-card db-card--elevated">
      <header class="db-card-header"><h2 class="db-card-title">Enrollment activity</h2></header>
      <div class="db-card-body"><div class="db-chart-wrap db-dash-chart-md"><canvas id="adminEnrollmentTrendChart"></canvas></div></div>
    </article>
    <article class="db-card db-card--elevated">
      <header class="db-card-header"><h2 class="db-card-title">Course completion overview</h2></header>
      <div class="db-card-body"><div class="db-chart-wrap db-dash-chart-sm"><canvas id="adminCompletionOverviewChart"></canvas></div></div>
    </article>
    <article class="db-card db-card--elevated">
      <header class="db-card-header"><h2 class="db-card-title">Certificate issuance trend</h2></header>
      <div class="db-card-body"><div class="db-chart-wrap db-dash-chart-sm"><canvas id="adminCertificateTrendChart"></canvas></div></div>
    </article>
  </section>

  <section class="db-dash-ops-grid">
    <article class="db-card db-card--elevated db-dash-panel">
      <header class="db-card-header db-dash-panel-head">
        <h2 class="db-card-title">Pending enrollment requests</h2>
        <span class="db-dash-muted">Preview</span>
      </header>
      <div class="db-card-body">
        <?php if (! empty($pending_rows)): ?>
          <div class="db-table-wrap">
            <table class="db-dash-table">
              <thead><tr><th>Learner</th><th>Course</th><th>Requested</th><th></th></tr></thead>
              <tbody>
                <?php foreach (array_slice($pending_rows, 0, 8) as $row): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) ($row->fullname ?? 'Learner')); ?></td>
                    <td><?= htmlspecialchars((string) ($row->course_title ?? '—')); ?></td>
                    <td><?= ! empty($row->enrolled_at) ? htmlspecialchars(date('M j, Y', strtotime($row->enrolled_at))) : '—'; ?></td>
                    <td><a href="<?= site_url('enrollments/requests'); ?>" class="db-dash-table-link">Queue</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p class="db-dash-footnote">Final approve / reject happens in the secure enrollment queue.</p>
        <?php else: ?>
          <div class="db-dash-empty-state">No pending enrollment requests.</div>
        <?php endif; ?>
      </div>
    </article>

    <article class="db-card db-card--elevated db-dash-panel">
      <header class="db-card-header db-dash-panel-head">
        <h2 class="db-card-title">Top active courses</h2>
        <span class="db-dash-muted">By enrollments</span>
      </header>
      <div class="db-card-body">
        <?php if (! empty($top_courses)): ?>
          <ol class="db-dash-ranked">
            <?php foreach ($top_courses as $i => $tc): ?>
              <li class="db-dash-ranked-item">
                <span class="db-dash-ranked-idx"><?= (int) $i + 1; ?></span>
                <div class="db-dash-ranked-body">
                  <p class="db-dash-ranked-title"><?= htmlspecialchars((string) ($tc->course_title ?? 'Course')); ?></p>
                  <p class="db-dash-ranked-meta"><?= (int) ($tc->enrollment_count ?? 0); ?> enrollments</p>
                </div>
                <?php $cid = (int) ($tc->course_id ?? 0); ?>
                <?php if ($cid > 0): ?><a class="db-dash-mini-link" href="<?= site_url('course/' . $cid); ?>">View</a><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php else: ?>
          <div class="db-dash-empty-state">No course enrollment data yet.</div>
        <?php endif; ?>
      </div>
    </article>
  </section>

  <section class="db-card db-card--elevated">
    <header class="db-card-header db-dash-panel-head">
      <h2 class="db-card-title">Recent system activity</h2>
      <span class="db-dash-muted">Notifications</span>
    </header>
    <div class="db-card-body">
      <?php if (! empty($activity_feed)): ?>
        <ul class="db-dash-timeline">
          <?php foreach ($activity_feed as $act):
            $tk = $act->type_key ?? 'system';
            $slug = preg_replace('/[^a-z]/', '', (string) $tk) ?: 'system';
            $when = ! empty($act->date_encoded) ? date('M j, g:i A', strtotime($act->date_encoded)) : '';
            ?>
            <li class="db-dash-timeline-row">
              <span class="db-dash-timeline-glyph db-dash-timeline-glyph--<?= htmlspecialchars($slug); ?>"><?= htmlspecialchars($dash_icon($tk)); ?></span>
              <div>
                <p class="db-dash-timeline-title"><?= htmlspecialchars((string) ($act->title ?? 'Update')); ?></p>
                <p class="db-dash-timeline-meta"><?= htmlspecialchars($when); ?></p>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="db-dash-empty-state">No system notifications captured yet.</div>
      <?php endif; ?>
    </div>
  </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  window.DASHBOARD_ROLE = 'admin';
  window.DASHBOARD_CHARTS = <?= json_encode($charts); ?>;
});
</script>
