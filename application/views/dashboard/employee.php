<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$dashboard_data = $dashboard_data ?? ['stats'=>[],'courses'=>[],'notifications'=>[],'certificates'=>[],'requests'=>[],'charts'=>[]];
$stats = $dashboard_data['stats'] ?? [];
$charts = $dashboard_data['charts'] ?? [];
$courses_data = $dashboard_data['courses'] ?? [];
$enrolled_courses = $courses_data['enrolled'] ?? [];
$continue = $courses_data['continue_learning'] ?? null;
$certificates = $dashboard_data['certificates'] ?? [];
$activity_items = $dashboard_data['notifications'] ?? [];
$courses_enrolled = (int) ($stats['courses_enrolled'] ?? 0);
$courses_completed = (int) ($stats['courses_completed'] ?? 0);
$full_name = is_object($user ?? null) ? ($user->fullname ?? 'Learner') : 'Learner';
$first_name = explode(' ', trim($full_name))[0];
$progress_pct = $courses_enrolled > 0 ? (int) round(($courses_completed / $courses_enrolled) * 100) : 0;

$act_icon = static function ($type_key) {
    switch ((string) $type_key) {
        case 'certificate':
            return 'A';
        case 'approval':
            return 'OK';
        case 'rejection':
            return '!';
        case 'request':
            return '+';
        default:
            return '•';
    }
};
?>
<?php echo $alerts_partial_html ?? ''; ?>
<?php
$stu_initial = static function ($title) {
    $t = trim((string) $title);

    return $t !== '' ? strtoupper(function_exists('mb_substr') ? mb_substr($t, 0, 1) : substr($t, 0, 1)) : '?';
};
?>

<div class="db-page db-page--student">
  <header class="db-stu-welcome">
    <p class="db-stu-welcome-eyebrow">My learning</p>
    <h1 class="db-stu-welcome-title">Welcome back, <?= htmlspecialchars($first_name) ?></h1>
    <p class="db-stu-welcome-sub">Pick up where you left off — your progress syncs with every course page.</p>
  </header>

  <section class="db-stu-continue<?= empty($continue) ? ' db-stu-continue--empty' : '' ?>" aria-labelledby="stu-continue-heading">
    <?php if (! empty($continue)): ?>
      <div class="db-stu-continue-inner">
        <div class="db-stu-continue-thumb db-stu-thumb--<?= (int) ($continue->thumb_tone ?? 0) ?>" aria-hidden="true">
          <span class="db-stu-thumb-letter"><?= htmlspecialchars($stu_initial($continue->title ?? '')) ?></span>
        </div>
        <div class="db-stu-continue-copy">
          <p id="stu-continue-heading" class="db-stu-label">Continue learning</p>
          <h2 class="db-stu-continue-course"><?= htmlspecialchars((string) ($continue->title ?? 'Course')) ?></h2>
          <p class="db-stu-continue-meta">
            <span><?= (int) ($continue->progress_pct ?? 0) ?>% complete</span>
            <span class="db-dot" aria-hidden="true"></span>
            <?php $next_title = trim((string) ($continue->next_module_title ?? '')); ?>
            <?php if ($next_title !== ''): ?>
              <span>Next: <?= htmlspecialchars($next_title) ?></span>
            <?php else: ?>
              <span>Open course to continue</span>
            <?php endif; ?>
          </p>
          <p class="db-stu-continue-est"><?= htmlspecialchars((string) ($continue->estimate_label ?? '')) ?> · Active <?= htmlspecialchars((string) ($continue->last_activity_label ?? '')) ?></p>
          <div class="db-stu-continue-actions">
            <a href="<?= htmlspecialchars((string) ($continue->resume_url ?? '#')) ?>" class="db-stu-btn db-stu-btn--primary">Resume course</a>
            <a href="<?= htmlspecialchars((string) ($continue->outline_url ?? '#')) ?>" class="db-stu-btn db-stu-btn--ghost">Course outline</a>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="db-stu-continue-empty">
        <h2 id="stu-continue-heading" class="db-stu-continue-course">No active course</h2>
        <p class="db-stu-continue-meta">Enroll in a course or finish a completed path — your next module will appear here automatically.</p>
        <div class="db-stu-continue-actions">
          <a href="<?= site_url('my_courses') ?>" class="db-stu-btn db-stu-btn--primary">My courses</a>
          <a href="<?= site_url('courses') ?>" class="db-stu-btn db-stu-btn--ghost">Browse catalog</a>
        </div>
      </div>
    <?php endif; ?>
  </section>

  <section class="db-kpi-grid" aria-label="Learning summary">
    <article class="db-kpi-card"><div class="db-kpi-head"><div class="db-kpi-label">Courses enrolled</div><span class="db-kpi-icon">C</span></div><div class="db-kpi-value"><?= number_format($courses_enrolled) ?></div><div class="db-kpi-sub">Approved enrollments</div></article>
    <article class="db-kpi-card"><div class="db-kpi-head"><div class="db-kpi-label">Courses completed</div><span class="db-kpi-icon">D</span></div><div class="db-kpi-value"><?= number_format($courses_completed) ?></div><div class="db-kpi-sub">All modules done</div></article>
    <article class="db-kpi-card"><div class="db-kpi-head"><div class="db-kpi-label">Certificates</div><span class="db-kpi-icon">A</span></div><div class="db-kpi-value"><?= number_format((int)($stats['certificate_count'] ?? count($certificates))) ?></div><div class="db-kpi-sub">Issued &amp; ready</div></article>
    <article class="db-kpi-card"><div class="db-kpi-head"><div class="db-kpi-label">Catalog progress</div><span class="db-kpi-icon">%</span></div><div class="db-kpi-value"><?= number_format($progress_pct) ?>%</div><div class="db-kpi-sub">Share of courses finished</div></article>
  </section>

  <section class="db-card db-stu-section" aria-labelledby="stu-mycourses-heading">
    <header class="db-card-header">
      <h3 id="stu-mycourses-heading" class="db-card-title">My courses</h3>
      <a href="<?= site_url('my_courses') ?>" class="db-stu-link">View all</a>
    </header>
    <div class="db-card-body">
      <?php if (!empty($enrolled_courses)): ?>
        <div class="db-stu-course-grid">
          <?php foreach ($enrolled_courses as $course):
            $pct = (int) ($course->progress_pct ?? 0);
            $is_done = $pct >= 100;
            $instr = trim((string) ($course->instructor_name ?? ''));
            ?>
            <article class="db-stu-course-card">
              <div class="db-stu-course-card-top">
                <div class="db-stu-mini-thumb db-stu-thumb--<?= (int) ($course->thumb_tone ?? 0) ?>"><?= htmlspecialchars($stu_initial($course->title ?? '')) ?></div>
                <div class="db-stu-course-card-head">
                  <?php if ($is_done): ?><span class="db-stu-badge db-stu-badge--done">Completed</span><?php endif; ?>
                  <h4 class="db-stu-course-title"><?= htmlspecialchars((string) ($course->title ?? 'Course')) ?></h4>
                  <?php if ($instr !== ''): ?>
                    <p class="db-stu-course-inst"><?= htmlspecialchars($instr) ?></p>
                  <?php endif; ?>
                </div>
              </div>
              <div class="db-stu-progress-row">
                <span><?= (int) ($course->modules_done ?? 0) ?> / <?= (int) ($course->module_count ?? 0) ?> modules</span>
                <span class="db-stu-pct"><?= $pct ?>%</span>
              </div>
              <progress class="db-stu-progress" max="100" value="<?= max(0, min(100, $pct)) ?>"><?= max(0, min(100, $pct)) ?>%</progress>
              <p class="db-stu-last">Last activity <?= !empty($course->last_activity_label) ? htmlspecialchars((string) $course->last_activity_label) : '—' ?></p>
              <div class="db-stu-course-actions">
                <a href="<?= htmlspecialchars((string) ($course->resume_url ?? site_url('course/' . (int) ($course->course_id ?? 0)))) ?>" class="db-stu-btn db-stu-btn--primary db-stu-btn--sm"><?= $is_done ? 'Review' : 'Continue' ?></a>
                <a href="<?= site_url('course/' . (int) ($course->course_id ?? 0)) ?>" class="db-stu-btn db-stu-btn--ghost db-stu-btn--sm">Details</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="db-empty">You are not enrolled in any courses yet.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="db-grid-2 db-stu-charts-main">
    <article class="db-card">
      <header class="db-card-header"><h3 class="db-card-title">Learning progress trend</h3></header>
      <div class="db-card-body"><div class="db-chart-wrap db-chart-compact"><canvas id="studentLearningTrendChart"></canvas></div></div>
    </article>
    <article class="db-card">
      <header class="db-card-header"><h3 class="db-card-title">Course status</h3></header>
      <div class="db-card-body"><div class="db-chart-wrap db-chart-compact"><canvas id="studentCourseDistChart"></canvas></div></div>
    </article>
  </section>

  <section class="db-card db-stu-section">
    <header class="db-card-header"><h3 class="db-card-title">This week’s activity</h3></header>
    <div class="db-card-body"><div class="db-chart-wrap db-chart-compact"><canvas id="studentWeeklyChart"></canvas></div></div>
  </section>

  <section class="db-grid-2 db-stu-bottom">
    <article class="db-card">
      <header class="db-card-header"><h3 class="db-card-title">Certificates</h3></header>
      <div class="db-card-body">
        <?php if (!empty($certificates)): ?>
          <ul class="db-stu-cert-list">
            <?php foreach ($certificates as $cert):
              $issued = !empty($cert->issued_at) ? date('M j, Y', strtotime($cert->issued_at)) : '—';
              $cid = (int) ($cert->id ?? 0);
              ?>
              <li class="db-stu-cert-item">
                <div>
                  <p class="db-stu-cert-title"><?= htmlspecialchars((string) ($cert->course_title ?? 'Course')) ?></p>
                  <p class="db-stu-cert-date">Completed <?= htmlspecialchars($issued) ?></p>
                </div>
                <div class="db-stu-cert-actions">
                  <?php if (!empty($cert->file_path)): ?>
                    <a href="<?= base_url($cert->file_path) ?>" class="db-stu-btn db-stu-btn--ghost db-stu-btn--sm" target="_blank" rel="noopener">Download</a>
                  <?php endif; ?>
                  <?php if ($cid > 0): ?>
                    <a href="<?= site_url('certificates/view/' . $cid) ?>" class="db-stu-btn db-stu-btn--primary db-stu-btn--sm">View</a>
                  <?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="db-stu-empty-lg">No certificates yet — keep learning!</div>
        <?php endif; ?>
      </div>
    </article>

    <article class="db-card">
      <header class="db-card-header"><h3 class="db-card-title">Recent activity</h3></header>
      <div class="db-card-body">
        <?php if (!empty($activity_items)): ?>
          <ul class="db-stu-timeline">
            <?php foreach ($activity_items as $act):
              $tk = $act->type_key ?? 'system';
              $when = !empty($act->date_encoded) ? date('M j, g:i A', strtotime($act->date_encoded)) : '';
              ?>
              <li class="db-stu-timeline-item">
                <?php $tk_slug = preg_replace('/[^a-z]/', '', (string) $tk) ?: 'system'; ?>
                <span class="db-stu-timeline-dot db-stu-timeline-dot--<?= htmlspecialchars($tk_slug) ?>" aria-hidden="true"><?= htmlspecialchars($act_icon($tk)) ?></span>
                <div>
                  <p class="db-stu-timeline-title"><?= htmlspecialchars((string) ($act->title ?? 'Update')) ?></p>
                  <p class="db-stu-timeline-meta"><?= htmlspecialchars($when) ?></p>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="db-empty">No recent notifications — activity from certificates, enrollments, and modules appears here.</div>
        <?php endif; ?>
      </div>
    </article>
  </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  window.DASHBOARD_ROLE = 'employee';
  window.DASHBOARD_CHARTS = <?= json_encode($charts) ?>;
});
</script>
