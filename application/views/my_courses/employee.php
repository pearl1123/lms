<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// Data from My_courses controller (employee)
$enrolled_courses = $enrolled_courses ?? [];
$available_courses= $available_courses?? [];
$categories       = $categories       ?? [];
$keyword          = $keyword          ?? '';
$filter_cat       = $filter_cat       ?? '';
$filter_status    = $filter_status    ?? '';

$full_name  = isset($user) && is_object($user) ? ($user->fullname ?? 'Learner') : 'Learner';
$first_name = explode(' ', trim($full_name))[0];

$total_enrolled  = count($enrolled_courses);
$total_completed = count(array_filter($enrolled_courses, fn($c) => (int)($c->course_progress_percent ?? $c->progress_pct ?? 0) >= 100));
$in_progress     = count(array_filter($enrolled_courses, fn($c) => (int)($c->course_progress_percent ?? $c->progress_pct ?? 0) > 0 && (int)($c->course_progress_percent ?? $c->progress_pct ?? 0) < 100));

$thumb_gradients = [
    'linear-gradient(135deg,#3b82f6,#1d4ed8)',
    'linear-gradient(135deg,#22c55e,#15803d)',
    'linear-gradient(135deg,#6dabcf,#1a3a5c)',
    'linear-gradient(135deg,#f59f00,#b45309)',
    'linear-gradient(135deg,#ef4444,#991b1b)',
    'linear-gradient(135deg,#8b5cf6,#6d28d9)',
    'linear-gradient(135deg,#06b6d4,#0e7490)',
    'linear-gradient(135deg,#ec4899,#be185d)',
];
?>

<?php $this->load->view('layouts/alerts'); ?>

<link rel="stylesheet" href="<?= base_url('assets/css/module.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">

<!-- ============================================================
     KABAGA ACADEMY — My Courses (Employee / Student)
============================================================ -->

<!-- ══ Top Bar ═══════════════════════════════════════════════ -->
<div class="ec-topbar animate__animated animate__fadeIn animate__fast">
  <div class="ec-topbar-left">
    <h2>My Learning</h2>
    <p>Track progress across your enrolled courses, <?= htmlspecialchars($first_name) ?></p>
  </div>
  <a href="<?= base_url('courses') ?>" class="ec-btn ec-btn-primary">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
    Browse Catalog
  </a>
</div>

<!-- ══ KPI Strip ════════════════════════════════════════════ -->
<div class="ec-kpi-row animate__animated animate__fadeInUp animate__fast">
  <?php
  $overall_pct = $total_enrolled > 0
    ? round(array_sum(array_map(fn($c) => (int)($c->course_progress_percent ?? $c->progress_pct ?? 0), $enrolled_courses)) / $total_enrolled)
    : 0;
  ?>
  <div class="ec-kpi">
    <div class="ec-kpi-icon ec-kpi-icon--blue">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
    </div>
    <div><div class="ec-kpi-val"><?= $total_enrolled ?></div><div class="ec-kpi-lbl">Enrolled Courses</div></div>
  </div>
  <div class="ec-kpi">
    <div class="ec-kpi-icon ec-kpi-icon--green">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
    </div>
    <div><div class="ec-kpi-val"><?= $total_completed ?></div><div class="ec-kpi-lbl">Completed</div></div>
  </div>
  <div class="ec-kpi">
    <div class="ec-kpi-icon ec-kpi-icon--accent">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
    </div>
    <div><div class="ec-kpi-val"><?= $in_progress ?></div><div class="ec-kpi-lbl">In Progress</div></div>
  </div>
  <div class="ec-kpi">
    <div class="ec-kpi-icon ec-kpi-icon--amber">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div><div class="ec-kpi-val"><?= $overall_pct ?>%</div><div class="ec-kpi-lbl">Overall Progress</div></div>
  </div>
</div>

<!-- ══ Tabs ════════════════════════════════════════════════ -->
<div class="ec-tabs animate__animated animate__fadeInUp animate__fast ec-animate-delay-025" id="ecTabs">
  <button class="ec-tab active" data-pane="enrolled">
    My Courses
    <span class="ec-tab-badge"><?= $total_enrolled ?></span>
  </button>
  <button class="ec-tab" data-pane="available">
    Available Courses
    <span class="ec-tab-badge"><?= count($available_courses) ?></span>
  </button>
</div>

<!-- ══ Filters ══════════════════════════════════════════════ -->
<div class="ec-filters animate__animated animate__fadeInUp animate__fast ec-animate-delay-1">
  <div class="ec-search-wrap">
    <svg class="ec-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="ec-search-input" id="ecSearch" placeholder="Search courses…">
  </div>
  <select class="ec-select" id="ecFilterCat">
    <option value="">All Categories</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->name) ?></option>
    <?php endforeach; ?>
  </select>
  <select class="ec-select" id="ecFilterProgress">
    <option value="">All Progress</option>
    <option value="notstarted">Not Started</option>
    <option value="inprogress">In Progress</option>
    <option value="completed">Completed</option>
  </select>
</div>

<!-- ══ Enrolled Pane ════════════════════════════════════════ -->
<div class="ec-pane active animate__animated animate__fadeIn animate__fast" id="pane-enrolled">
  <div class="ec-grid" id="enrolledGrid">
    <?php if ( ! empty($enrolled_courses)): ?>
      <?php foreach ($enrolled_courses as $i => $course):
        $grad     = $thumb_gradients[$i % count($thumb_gradients)];
        $pct      = (int)($course->course_progress_percent ?? $course->progress_pct ?? 0);
        $modules  = (int)($course->module_count  ?? 0);
        $done     = (int)($course->modules_done  ?? 0);
        $cat_name = $course->category_name ?? 'General';

        if ($pct >= 100)    { $status = 'completed';  $badge_class = 'ec-badge-completed';  $badge_text = 'Completed'; }
        elseif ($pct > 0)   { $status = 'inprogress'; $badge_class = 'ec-badge-inprogress'; $badge_text = 'In Progress'; }
        else                { $status = 'notstarted'; $badge_class = 'ec-badge-notstarted'; $badge_text = 'Not Started'; }

        $cta_text  = $pct >= 100 ? 'Review' : ($pct > 0 ? 'Continue' : 'Start');
        $cta_class = $pct >= 100 ? 'ec-cta-review' : ($pct > 0 ? 'ec-cta-continue' : 'ec-cta-start');
      ?>
      <div class="ec-course-card animate__animated animate__fadeInUp ec-stagger-<?= (int)($i % 8) ?>"
           data-title="<?= htmlspecialchars(strtolower($course->title)) ?>"
           data-cat="<?= $course->category_id ?? '' ?>"
           data-progress="<?= $status ?>">
        <div class="ec-card-thumb" style="background:<?= $grad ?>;">
          <div class="ec-card-thumb-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
          </div>
          <span class="ec-card-status-badge <?= $badge_class ?>"><?= $badge_text ?></span>
        </div>
        <div class="ec-card-body">
          <div class="ec-card-category"><?= htmlspecialchars($cat_name) ?></div>
          <div class="ec-card-title"><?= htmlspecialchars($course->title) ?></div>
          <div class="ec-card-meta">
            <div class="ec-card-meta-item">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
              <?= $done ?>/<?= $modules ?> modules
            </div>
            <div class="ec-card-meta-item">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Enrolled <?= date('M j', strtotime($course->enrolled_at ?? 'now')) ?>
            </div>
          </div>
          <div class="ec-card-progress">
            <?php $this->load->view('components/progress_bar', [
                'progress_percent' => $pct,
                'size'               => 'sm',
                'label'              => 'Progress',
                'course_id'          => (int) ($course->course_id ?? 0),
                'variant'            => 'embed',
                'sync_live'          => true,
            ]); ?>
          </div>
          <div class="ec-card-footer">
            <?php if ( ! empty($course->course_id)): ?>
            <a href="<?= base_url('courses/view/'.(int) $course->course_id) ?>" class="ec-card-cta <?= $cta_class ?>">
              <?= $cta_text ?>
            </a>
            <?php else: ?>
            <span class="ec-card-cta <?= $cta_class ?> ec-card-cta--disabled" title="Course unavailable"><?= $cta_text ?></span>
            <?php endif; ?>
            <?php if ($pct >= 100): ?>
            <a href="<?= base_url('certificates') ?>" class="ec-cert-link" title="View Certificate">
              🏆 Cert
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="ec-empty ec-empty--fullwidth">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        <h4>No enrolled courses</h4>
        <p>Browse the course catalog and start learning today.</p>
        <a href="<?= base_url('courses') ?>" class="ec-btn ec-btn-primary">Browse Courses</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ══ Available Pane ════════════════════════════════════════ -->
<div class="ec-pane" id="pane-available">
  <div class="ec-grid" id="availableGrid">
    <?php if ( ! empty($available_courses)): ?>
      <?php foreach ($available_courses as $i => $course):
        $grad    = $thumb_gradients[$i % count($thumb_gradients)];
        $cat_name= $course->category_name ?? 'General';
        $modules = (int)($course->module_count ?? 0);
      ?>
      <div class="ec-available-card animate__animated animate__fadeInUp ec-stagger-<?= (int)($i % 8) ?>"
           data-title="<?= htmlspecialchars(strtolower($course->title)) ?>"
           data-cat="<?= $course->category_id ?? '' ?>">
        <div class="ec-card-thumb" style="background:<?= $grad ?>;">
          <div class="ec-card-thumb-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
          </div>
        </div>
        <div class="ec-card-body">
          <div class="ec-card-category"><?= htmlspecialchars($cat_name) ?></div>
          <div class="ec-card-title"><?= htmlspecialchars($course->title) ?></div>
          <div class="ec-card-meta">
            <div class="ec-card-meta-item">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
              <?= $modules ?> modules
            </div>
          </div>
          <?php if ( ! empty($course->description)): ?>
          <p class="ec-available-desc">
            <?= htmlspecialchars($course->description) ?>
          </p>
          <?php endif; ?>
          <div class="ec-card-footer">
            <a href="<?= base_url('courses/enroll/'.$course->id) ?>" class="ec-card-cta ec-cta-start">Enroll Now</a>
            <a href="<?= base_url('courses/view/'.$course->id) ?>" class="ec-card-details-link">
              Details
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="ec-empty ec-empty--fullwidth">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        <h4>No new courses available</h4>
        <p>You're enrolled in all available courses. Check back later!</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php $_ka_json = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT; ?>
<script>
kaApplyAppContext(<?= json_encode([
  'catalog' => [
    'courseProgressUrl' => base_url('index.php/courses/progress_state/'),
  ],
  'dashboard' => [],
], $_ka_json) ?>);
</script>
<script src="<?= base_url('assets/js/dashboard.js') ?>"></script>
<script src="<?= base_url('assets/js/catalog.js') ?>"></script>