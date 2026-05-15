<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$dashboard_data = $dashboard_data ?? ['stats' => [], 'courses' => [], 'notifications' => [], 'certificates' => [], 'requests' => [], 'charts' => []];
$stats            = $dashboard_data['stats'] ?? [];
$charts           = $dashboard_data['charts'] ?? [];
$courses_data     = $dashboard_data['courses'] ?? [];
$assigned         = $courses_data['assigned'] ?? [];
$struggling       = $courses_data['struggling_learners'] ?? [];
$cert_ready       = $courses_data['certificate_ready'] ?? [];
$pending_requests = $dashboard_data['requests'] ?? [];
$issued_certs     = $dashboard_data['certificates'] ?? [];
$activity_feed    = $dashboard_data['notifications'] ?? [];

$my_courses         = (int) ($stats['my_courses'] ?? 0);
$enrolled_students  = (int) ($stats['enrolled_students'] ?? 0);
$avg_completion     = (int) ($stats['avg_completion'] ?? 0);
$pending_reviews    = (int) ($stats['pending_reviews'] ?? 0);
$full_name          = isset($user) && is_object($user) ? ($user->fullname ?? 'Instructor') : 'Instructor';
$first_name         = explode(' ', trim((string) $full_name))[0];

$csrf_name = $csrf_field_name ?? '';
$csrf_val  = $csrf_hash ?? '';

$ins_icon = static function ($type_key) {
    switch ((string) $type_key) {
        case 'certificate': return 'A';
        case 'approval': return 'OK';
        case 'rejection': return '!';
        case 'request': return '+';
        default: return '•';
    }
};
?>
<?php echo $alerts_partial_html ?? ''; ?>

<div class="db-page db-page--instructor">
  <header class="db-dash-hero db-dash-hero--teach">
    <div class="db-dash-hero-top">
      <div>
        <p class="db-dash-hero-eyebrow">Teaching operations</p>
        <h1 class="db-dash-hero-title">Welcome back, <?= htmlspecialchars($first_name); ?></h1>
        <p class="db-dash-hero-lead">Monitor classes, unblock learners, and act on enrollment flow.</p>
      </div>
      <div class="db-dash-hero-cta-stack">
        <a href="<?= site_url('my_courses'); ?>" class="db-dash-pill-btn db-dash-pill-btn--outline">My courses</a>
        <a href="<?= site_url('enrollments/requests'); ?>" class="db-dash-pill-btn">Enrollment queue</a>
      </div>
    </div>
    <ul class="db-dash-hero-metrics" aria-label="Instructor KPI strip">
      <li><span class="db-dash-metric-val"><?= number_format($my_courses); ?></span><span class="db-dash-metric-lbl">Assigned courses</span></li>
      <li><span class="db-dash-metric-val"><?= number_format($enrolled_students); ?></span><span class="db-dash-metric-lbl">Active students</span></li>
      <li><span class="db-dash-metric-val"><?= number_format($pending_reviews); ?></span><span class="db-dash-metric-lbl">Pending approvals</span></li>
      <li><span class="db-dash-metric-val"><?= number_format($avg_completion); ?>%</span><span class="db-dash-metric-lbl">Avg completion</span></li>
    </ul>
  </header>

  <section class="db-kpi-grid" aria-label="Instructor KPI cards">
    <article class="db-kpi-card db-kpi-card--dash"><div class="db-kpi-head"><span class="db-kpi-label">My courses</span><span class="db-kpi-icon">C</span></div><div class="db-kpi-value"><?= number_format($my_courses); ?></div><div class="db-kpi-sub">Courses you publish</div></article>
    <article class="db-kpi-card db-kpi-card--dash"><div class="db-kpi-head"><span class="db-kpi-label">Pending requests</span><span class="db-kpi-icon">R</span></div><div class="db-kpi-value"><?= number_format($pending_reviews); ?></div><div class="db-kpi-sub">Enrollment queue</div></article>
    <article class="db-kpi-card db-kpi-card--dash"><div class="db-kpi-head"><span class="db-kpi-label">Active students</span><span class="db-kpi-icon">S</span></div><div class="db-kpi-value"><?= number_format($enrolled_students); ?></div><div class="db-kpi-sub">Across your catalog</div></article>
    <article class="db-kpi-card db-kpi-card--dash"><div class="db-kpi-head"><span class="db-kpi-label">Completion rate</span><span class="db-kpi-icon">%</span></div><div class="db-kpi-value"><?= number_format($avg_completion); ?>%</div><div class="db-kpi-sub">Average course progress</div></article>
  </section>

  <section class="db-dash-chart-grid db-dash-chart-grid--2">
    <article class="db-card db-card--elevated">
      <header class="db-card-header"><h2 class="db-card-title">Course progress overview</h2></header>
      <div class="db-card-body"><div class="db-chart-wrap db-dash-chart-md"><canvas id="instructorCourseProgressChart"></canvas></div></div>
    </article>
    <article class="db-card db-card--elevated">
      <header class="db-card-header"><h2 class="db-card-title">Completed modules by learner</h2></header>
      <div class="db-card-body"><div class="db-chart-wrap db-dash-chart-md"><canvas id="instructorStudentPerformanceChart"></canvas></div></div>
    </article>
    <article class="db-card db-card--elevated">
      <header class="db-card-header"><h2 class="db-card-title">Enrollment per course</h2></header>
      <div class="db-card-body"><div class="db-chart-wrap db-dash-chart-sm"><canvas id="instructorEnrollmentChart"></canvas></div></div>
    </article>
    <article class="db-card db-card--elevated">
      <header class="db-card-header"><h2 class="db-card-title">Completion distribution</h2></header>
      <div class="db-card-body"><div class="db-chart-wrap db-dash-chart-sm"><canvas id="instructorCompletionDistChart"></canvas></div></div>
    </article>
  </section>

  <section class="db-dash-teach-grid">
    <article class="db-card db-card--elevated db-dash-panel">
      <header class="db-card-header db-dash-panel-head">
        <h2 class="db-card-title">Pending enrollment requests</h2>
        <span class="db-dash-muted">Actions</span>
      </header>
      <div class="db-card-body">
        <?php if (! empty($pending_requests)): ?>
          <ul class="db-dash-action-list">
            <?php foreach (array_slice($pending_requests, 0, 6) as $req):
              $eid = (int) ($req->enrollment_id ?? $req->id ?? 0);
              ?>
              <li class="db-dash-action-row">
                <div>
                  <p class="db-dash-action-title"><?= htmlspecialchars((string) ($req->fullname ?? 'Learner')); ?></p>
                  <p class="db-dash-action-meta"><?= htmlspecialchars((string) ($req->course_title ?? 'Course')); ?> · <?= ! empty($req->enrolled_at) ? htmlspecialchars(date('M j, Y', strtotime($req->enrolled_at))) : ''; ?></p>
                </div>
                <?php if ($eid > 0 && $csrf_name !== ''): ?>
                  <div class="db-dash-action-forms">
                    <form method="post" action="<?= site_url('enrollments/approve/' . $eid); ?>" class="db-dash-inline-form">
                      <input type="hidden" name="<?= html_escape($csrf_name); ?>" value="<?= html_escape($csrf_val); ?>">
                      <button type="submit" class="btn btn-sm btn-success">Approve</button>
                    </form>
                    <form method="post" action="<?= site_url('enrollments/reject/' . $eid); ?>" class="db-dash-inline-form">
                      <input type="hidden" name="<?= html_escape($csrf_name); ?>" value="<?= html_escape($csrf_val); ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                    </form>
                  </div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
          <a href="<?= site_url('enrollments/requests'); ?>" class="db-dash-foot-link">Full queue →</a>
        <?php else: ?>
          <div class="db-dash-empty-state">No enrollment requests awaiting you.</div>
        <?php endif; ?>
      </div>
    </article>

    <article class="db-card db-card--elevated db-dash-panel">
      <header class="db-card-header db-dash-panel-head">
        <h2 class="db-card-title">Students who need attention</h2>
        <span class="db-dash-muted">Lowest progress first</span>
      </header>
      <div class="db-card-body">
        <?php if (! empty($struggling)): ?>
          <ul class="db-dash-student-risk">
            <?php foreach ($struggling as $s): $pct = (int) ($s->progress_pct ?? 0); $cid = (int) ($s->course_id ?? 0); ?>
              <li class="db-dash-student-risk-row">
                <div>
                  <p class="db-dash-risk-name"><?= htmlspecialchars((string) ($s->fullname ?? 'Learner')); ?></p>
                  <p class="db-dash-risk-course"><?= htmlspecialchars((string) ($s->course_title ?? 'Course')); ?></p>
                </div>
                <div class="db-dash-risk-pct"><?= max(0, min(100, $pct)); ?>%</div>
                <?php if ($cid > 0): ?><a class="db-dash-mini-link" href="<?= site_url('course/' . $cid); ?>">Open</a><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="db-dash-empty-state">No struggling enrollments surfaced yet.</div>
        <?php endif; ?>
      </div>
    </article>
  </section>

  <section class="db-dash-teach-grid">
    <article class="db-card db-card--elevated db-dash-panel">
      <header class="db-card-header db-dash-panel-head">
        <h2 class="db-card-title">Certificate-ready learners</h2>
        <span class="db-dash-muted">All modules completed, no PDF yet</span>
      </header>
      <div class="db-card-body">
        <?php if (! empty($cert_ready)): ?>
          <ul class="db-dash-plain-list">
            <?php foreach ($cert_ready as $row): $cid = (int) ($row->course_id ?? 0); ?>
              <li class="db-dash-plain-row">
                <div>
                  <p class="db-dash-plain-title"><?= htmlspecialchars((string) ($row->fullname ?? 'Learner')); ?></p>
                  <p class="db-dash-plain-meta"><?= htmlspecialchars((string) ($row->course_title ?? 'Course')); ?></p>
                </div>
                <?php if ($cid > 0): ?><a class="db-dash-mini-link" href="<?= site_url('course/' . $cid); ?>">Review course</a><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="db-dash-empty-state">No learners waiting on certificate issuance right now.</div>
        <?php endif; ?>
      </div>
    </article>

    <article class="db-card db-card--elevated db-dash-panel">
      <header class="db-card-header db-dash-panel-head">
        <h2 class="db-card-title">Recent certificates</h2>
        <span class="db-dash-muted">Issued across your courses</span>
      </header>
      <div class="db-card-body">
        <?php if (! empty($issued_certs)): ?>
          <ul class="db-dash-plain-list">
            <?php foreach (array_slice($issued_certs, 0, 8) as $cert):
              $certificate_id = (int) ($cert->id ?? 0);
              ?>
              <li class="db-dash-plain-row">
                <div>
                  <p class="db-dash-plain-title"><?= htmlspecialchars((string) ($cert->user_name ?? 'Learner')); ?></p>
                  <p class="db-dash-plain-meta"><?= htmlspecialchars((string) ($cert->course_title ?? 'Course')); ?> · <?= ! empty($cert->issued_at) ? htmlspecialchars(date('M j, Y', strtotime($cert->issued_at))) : ''; ?></p>
                </div>
                <?php if ($certificate_id > 0): ?>
                  <a class="db-dash-mini-link" href="<?= site_url('certificates/view/' . $certificate_id); ?>" target="_blank" rel="noopener">View</a>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="db-dash-empty-state">Certificates issued from your catalog will surface here.</div>
        <?php endif; ?>
      </div>
    </article>
  </section>

  <article class="db-card db-card--elevated">
    <header class="db-card-header db-dash-panel-head">
      <h2 class="db-card-title">Quick access · active courses</h2>
      <span class="db-dash-muted">Jump into teaching</span>
    </header>
    <div class="db-card-body">
      <?php if (! empty($assigned)): ?>
        <div class="db-dash-chip-row">
          <?php foreach (array_slice($assigned, 0, 8) as $co):
            $id = (int) ($co->id ?? 0);
            ?>
            <a href="<?= site_url('course/' . $id); ?>" class="db-dash-chip"><?= htmlspecialchars((string) ($co->title ?? 'Course')); ?></a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="db-dash-empty-state">Publish or claim a course to populate quick links.</div>
      <?php endif; ?>
    </div>
  </article>

  <section class="db-card db-card--elevated">
    <header class="db-card-header db-dash-panel-head">
      <h2 class="db-card-title">Activity timeline</h2>
      <span class="db-dash-muted">Filtered to your teaching scope</span>
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
              <span class="db-dash-timeline-glyph db-dash-timeline-glyph--<?= htmlspecialchars($slug); ?>"><?= htmlspecialchars($ins_icon($tk)); ?></span>
              <div>
                <p class="db-dash-timeline-title"><?= htmlspecialchars((string) ($act->title ?? 'Update')); ?></p>
                <p class="db-dash-timeline-meta"><?= htmlspecialchars($when); ?></p>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="db-dash-empty-state">No instructor notifications yet.</div>
      <?php endif; ?>
    </div>
  </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  window.DASHBOARD_ROLE = 'instructor';
  window.DASHBOARD_CHARTS = <?= json_encode($charts); ?>;
});
</script>
