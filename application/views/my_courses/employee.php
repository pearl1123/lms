<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$enrolled_courses  = $enrolled_courses ?? [];
$invited_courses   = $invited_courses ?? [];
$available_courses = $available_courses ?? [];
$categories        = $categories ?? [];

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

<div class="ec-tabs animate__animated animate__fadeInUp animate__fast ec-animate-delay-025" id="ecTabs">
  <button type="button" class="ec-tab active" data-pane="enrolled">
    My Courses <span class="ec-tab-badge"><?= $total_enrolled ?></span>
  </button>
  <button type="button" class="ec-tab" data-pane="invited">
    Invited Courses <span class="ec-tab-badge"><?= count($invited_courses) ?></span>
  </button>
  <button type="button" class="ec-tab" data-pane="available">
    Available Courses <span class="ec-tab-badge"><?= count($available_courses) ?></span>
  </button>
</div>

<div class="ec-filters animate__animated animate__fadeInUp animate__fast ec-animate-delay-1">
  <div class="ec-search-wrap">
    <svg class="ec-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="ec-search-input" id="ecSearch" placeholder="Search courses…">
  </div>
  <select class="ec-select" id="ecFilterCat">
    <option value="">All Categories</option>
    <?php foreach ($categories as $cat): ?>
    <option value="<?= (int) $cat->id ?>"><?= htmlspecialchars($cat->name) ?></option>
    <?php endforeach; ?>
  </select>
  <select class="ec-select" id="ecFilterProgress">
    <option value="">All Progress</option>
    <option value="notstarted">Not Started</option>
    <option value="inprogress">In Progress</option>
    <option value="completed">Completed</option>
  </select>
</div>

<section class="ec-pane active animate__animated animate__fadeIn animate__fast" id="pane-enrolled" aria-label="Enrolled courses">
  <div class="ec-grid" id="enrolledGrid">
    <?php if ( ! empty($enrolled_courses)): ?>
      <?php foreach ($enrolled_courses as $i => $course): ?>
        <?php $this->load->view('my_courses/_learning_card', ['mode' => 'enrolled', 'course' => $course, 'i' => $i, 'thumb_gradients' => $thumb_gradients]); ?>
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
</section>

<section class="ec-pane" id="pane-invited" aria-label="Invited courses">
  <div class="ec-grid" id="invitedGrid">
    <?php if ( ! empty($invited_courses)): ?>
      <?php foreach ($invited_courses as $i => $course): ?>
        <?php $this->load->view('my_courses/_learning_card', ['mode' => 'invited', 'course' => $course, 'i' => $i, 'thumb_gradients' => $thumb_gradients]); ?>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="ec-empty ec-empty--fullwidth">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        <h4>No pending invitations</h4>
        <p>When an instructor invites you to a course, it will appear here.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="ec-pane" id="pane-available" aria-label="Available courses">
  <div class="ec-grid" id="availableGrid">
    <?php if ( ! empty($available_courses)): ?>
      <?php foreach ($available_courses as $i => $course): ?>
        <?php $this->load->view('my_courses/_learning_card', ['mode' => 'available', 'course' => $course, 'i' => $i, 'thumb_gradients' => $thumb_gradients]); ?>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="ec-empty ec-empty--fullwidth">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        <h4>No new courses available</h4>
        <p>You're enrolled in all available courses. Check back later!</p>
      </div>
    <?php endif; ?>
  </div>
</section>

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
