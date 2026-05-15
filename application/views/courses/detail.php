<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$course            = $course            ?? null;
$modules           = $modules           ?? [];
$is_enrolled       = $is_enrolled       ?? false;
$total_modules     = $total_modules     ?? 0;
$completed_modules = $completed_modules ?? 0;
$progress_pct      = $progress_pct      ?? 0;
$total_enrolled    = $total_enrolled    ?? 0;
$modules           = is_array($modules) || is_object($modules) ? $modules : [];
$user_role          = strtolower($user->role ?? 'employee');
$enrollment_status  = $enrollment_status ?? null;

if ( ! $course) return;

$content_icons = [
    'pdf'            => '📄',
    'slides'         => '📊',
    'video'          => '🎬',
    'audio'          => '🎧',
    'zoom_recording' => '🎥',
];

$content_colors = [
    'pdf'            => '#ef4444',
    'slides'         => '#3b82f6',
    'video'          => '#8b5cf6',
    'audio'          => '#f59f00',
    'zoom_recording' => '#06b6d4',
];
?>

<!-- ============================================================
     KABAGA ACADEMY — Course Detail
============================================================ -->
<link rel="stylesheet" href="<?= base_url('assets/css/course.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/module.css') ?>">

<!-- ══ Hero ═════════════════════════════════════════════════ -->
<div class="cd-hero animate__animated animate__fadeIn animate__fast">
  <div class="cd-hero-inner">

    <div class="cd-hero-left">
      <div class="cd-hero-cat"><?= htmlspecialchars($course->category_name ?? 'General') ?></div>
      <h2 class="cd-hero-title"><?= htmlspecialchars($course->title) ?></h2>
      <?php if ( ! empty($course->description)): ?>
        <p class="cd-hero-desc"><?= htmlspecialchars($course->description) ?></p>
      <?php endif; ?>
      <div class="cd-hero-meta">
        <div class="cd-hero-meta-item">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          <?= $total_modules ?> module<?= $total_modules !== 1 ? 's' : '' ?>
        </div>
        <div class="cd-hero-meta-item">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          <?= $total_enrolled ?> enrolled
        </div>
        <?php if ( ! empty($course->creator_name)): ?>
        <div class="cd-hero-meta-item">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
          <?= htmlspecialchars($course->creator_name) ?>
        </div>
        <?php endif; ?>
        <div class="cd-hero-meta-item">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <?= date('M j, Y', strtotime($course->created_at)) ?>
        </div>
      </div>
    </div>

    <!-- Enroll card -->
    <div class="cd-enroll-card">
      <?php if ($is_enrolled): ?>
        <div class="cd-enrolled-badge">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
          <?= $progress_pct >= 100 ? 'Course Completed!' : 'Currently Enrolled' ?>
        </div>
        <div class="cd-enroll-prog-wrap">
          <?php $this->load->view('components/progress_bar', [
              'progress_percent' => (int) $progress_pct,
              'size'               => 'md',
              'label'              => 'Your Progress',
              'course_id'          => (int) $course->id,
              'variant'            => 'embed',
              'sync_live'          => true,
          ]); ?>
          <div class="cd-enroll-prog-foot" id="cdEnrollProgFoot" data-course-id="<?= (int) $course->id ?>">
            <?= $completed_modules ?>/<?= $total_modules ?> modules completed
          </div>
        </div>

        <?php if ($progress_pct >= 100): ?>
          <a href="<?= base_url('certificates') ?>" class="cd-enroll-btn cd-enroll-btn-cert">
            🏆 View Certificate
          </a>
        <?php else: ?>
          <?php
          // Find first incomplete module
          $next_module = null;
          foreach ($modules as $m) {
            if (($m->status ?? 'not_started') !== 'completed') { $next_module = $m; break; }
          }
          ?>
          <a href="<?= $next_module ? base_url('courses/module/'.$next_module->id) : '#' ?>"
             class="cd-enroll-btn cd-enroll-btn-continue">
            <?= $completed_modules > 0 ? 'Continue Learning' : 'Start Course' ?>
          </a>
        <?php endif; ?>

        <a href="<?= base_url('my_courses') ?>" class="cd-enroll-btn cd-enroll-btn-outline">
          ← Back to My Courses
        </a>

      <?php elseif (in_array($user_role, ['employee', 'student'], true) && $enrollment_status === 'pending'): ?>
        <p class="cd-enroll-title">Waiting for approval</p>
        <p class="cd-enroll-muted">
          Your enrollment request is pending. You will be able to start modules after an instructor approves it.
        </p>
        <a href="<?= base_url('courses') ?>" class="cd-enroll-btn cd-enroll-btn-outline">
          ← Back to Catalog
        </a>

      <?php elseif (in_array($user_role, ['employee', 'student'], true) && $enrollment_status === 'rejected'): ?>
        <p class="cd-enroll-title">Request rejected</p>
        <p class="cd-enroll-muted">
          Your enrollment request was not approved. You may submit a new request if you still wish to join this course.
        </p>
        <a href="<?= base_url('index.php/courses/enroll/'.$course->id) ?>"
           class="cd-enroll-btn cd-enroll-btn-primary"
           onclick="return confirm('Submit a new enrollment request?')">
          Request enrollment again
        </a>
        <a href="<?= base_url('courses') ?>" class="cd-enroll-btn cd-enroll-btn-outline">
          ← Back to Catalog
        </a>

      <?php elseif (in_array($user_role, ['employee', 'student'], true)): ?>
        <p class="cd-enroll-title">Ready to get started?</p>
        <div class="cd-enroll-stats">
          <div class="cd-enroll-stat">
            <div class="cd-enroll-stat-val"><?= $total_modules ?></div>
            <div class="cd-enroll-stat-lbl">Modules</div>
          </div>
          <div class="cd-enroll-stat">
            <div class="cd-enroll-stat-val"><?= $total_enrolled ?></div>
            <div class="cd-enroll-stat-lbl">Enrolled</div>
          </div>
        </div>
        <a href="<?= base_url('index.php/courses/enroll/'.$course->id) ?>"
           class="cd-enroll-btn cd-enroll-btn-primary"
           onclick="return confirm('Request enrollment in this course?')">
          Request enrollment
        </a>
        <a href="<?= base_url('courses') ?>" class="cd-enroll-btn cd-enroll-btn-outline">
          ← Back to Catalog
        </a>

      <?php else: ?>
        <!-- Admin / Teacher view -->
        <p class="cd-enroll-title">Course Overview</p>
        <div class="cd-enroll-stats">
          <div class="cd-enroll-stat">
            <div class="cd-enroll-stat-val"><?= $total_modules ?></div>
            <div class="cd-enroll-stat-lbl">Modules</div>
          </div>
          <div class="cd-enroll-stat">
            <div class="cd-enroll-stat-val"><?= $total_enrolled ?></div>
            <div class="cd-enroll-stat-lbl">Students</div>
          </div>
        </div>
        <a href="<?= base_url('manage_courses/edit/'.$course->id) ?>"
           class="cd-enroll-btn cd-enroll-btn-primary">
          Edit Course
        </a>
        <a href="<?= base_url('courses') ?>" class="cd-enroll-btn cd-enroll-btn-outline">
          ← Back to Catalog
        </a>
      <?php endif; ?>
    </div>

  </div>
</div>

<!-- ══ Body Layout ═══════════════════════════════════════════ -->
<div class="cd-layout">

  <!-- Left: Modules list -->
  <div>
    <div class="cd-panel cd-panel-delay-1 animate__animated animate__fadeInUp animate__fast">
      <div class="cd-panel-hdr">
        <h3 class="cd-panel-title">Course Modules</h3>
        <span class="cd-panel-meta">
          <?= $total_modules ?> module<?= $total_modules !== 1 ? 's' : '' ?>
        </span>
      </div>
      <ul class="cd-module-list">
        <?php if ( ! empty($modules)): ?>
          <?php foreach ($modules as $idx => $module):
            $status = $module->status ?? 'not_started';
            if ( ! $is_enrolled) $status = 'locked';

            // Icon & action
            if ($status === 'completed') {
              $num_content = '✓';
              $action_text = 'Review';
              $action_class= 'cd-module-action-review';
            } elseif ($status === 'in_progress') {
              $num_content = $idx + 1;
              $action_text = 'Continue';
              $action_class= 'cd-module-action-continue';
            } elseif ($status === 'locked') {
              $num_content = '🔒';
              $action_text = 'Locked';
              $action_class= 'cd-module-action-locked';
            } else {
              $num_content = $idx + 1;
              $action_text = 'Start';
              $action_class= 'cd-module-action-start';
            }

            $type_color = $content_colors[$module->content_type ?? ''] ?? '#64748b';
            $type_icon  = $content_icons[$module->content_type ?? ''] ?? '📁';
          ?>
          <li class="cd-module-item">
            <div class="cd-module-num status-<?= $status !== 'locked' ? $status : 'locked' ?>">
              <?= $num_content ?>
            </div>
            <div class="cd-module-body">
              <div class="cd-module-title"><?= htmlspecialchars($module->title) ?></div>
              <div class="cd-module-meta">
                <span class="cd-module-type" style="--cd-type-base: <?= htmlspecialchars($type_color, ENT_QUOTES, 'UTF-8') ?>">
                  <?= $type_icon ?> <?= ucfirst(str_replace('_', ' ', $module->content_type ?? 'content')) ?>
                </span>
                <?php if ($is_enrolled): ?>
                <span class="cd-module-status-tag cd-module-status-<?= $status ?>">
                  <?= ucfirst(str_replace('_', ' ', $status)) ?>
                </span>
                <?php endif; ?>
              </div>
              <?php if ($is_enrolled): ?>
              <?php $mpp = (int) ($module->progress_percent ?? 0); ?>
              <?php $this->load->view('components/progress_bar', [
                  'progress_percent' => $mpp,
                  'size'               => 'sm',
                  'label'              => 'Progress',
                  'variant'            => 'module_list_row',
              ]); ?>
              <?php endif; ?>
            </div>
            <?php if ($status !== 'locked'): ?>
            <a href="<?= base_url('courses/module/'.$module->id) ?>"
               class="cd-module-action <?= $action_class ?>">
              <?= $action_text ?>
            </a>
            <?php else: ?>
            <span class="cd-module-action cd-module-action-locked">Locked</span>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li class="cd-module-empty">
            No modules available yet.
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <!-- Right: Course info -->
  <div>
    <div class="cd-panel cd-panel-delay-2 animate__animated animate__fadeInUp animate__fast">
      <div class="cd-panel-hdr">
        <h3 class="cd-panel-title">Course Info</h3>
      </div>
      <div class="cd-panel-body cd-panel-body--tight">
        <ul class="cd-info-list">
          <li class="cd-info-item">
            <div class="cd-info-icon cd-info-icon--blue">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </div>
            <span class="cd-info-label">Total Modules</span>
            <span class="cd-info-value"><?= $total_modules ?></span>
          </li>
          <li class="cd-info-item">
            <div class="cd-info-icon cd-info-icon--green">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <span class="cd-info-label">Enrolled</span>
            <span class="cd-info-value"><?= $total_enrolled ?></span>
          </li>
          <li class="cd-info-item">
            <div class="cd-info-icon cd-info-icon--accent">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <span class="cd-info-label">Category</span>
            <span class="cd-info-value"><?= htmlspecialchars($course->category_name ?? '—') ?></span>
          </li>
          <?php if ( ! empty($course->creator_name)): ?>
          <li class="cd-info-item">
            <div class="cd-info-icon cd-info-icon--amber">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
            </div>
            <span class="cd-info-label">Instructor</span>
            <span class="cd-info-value cd-info-value--wrap"><?= htmlspecialchars($course->creator_name) ?></span>
          </li>
          <?php endif; ?>
          <li class="cd-info-item">
            <div class="cd-info-icon cd-info-icon--slate">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <span class="cd-info-label">Published</span>
            <span class="cd-info-value"><?= date('M j, Y', strtotime($course->created_at)) ?></span>
          </li>
          <?php if ( ! empty($course->expiry_days)): ?>
          <li class="cd-info-item">
            <div class="cd-info-icon cd-info-icon--red">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <span class="cd-info-label">Expires in</span>
            <span class="cd-info-value"><?= $course->expiry_days ?> days</span>
          </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <!-- Content types summary -->
    <?php
    $type_counts = [];
    foreach ($modules as $m) {
        $t = $m->content_type ?? 'content';
        $type_counts[$t] = ($type_counts[$t] ?? 0) + 1;
    }
    if ( ! empty($type_counts)):
    ?>
    <div class="cd-panel cd-panel-delay-3 animate__animated animate__fadeInUp animate__fast">
      <div class="cd-panel-hdr">
        <h3 class="cd-panel-title">Content Types</h3>
      </div>
      <div class="cd-panel-body">
        <?php foreach ($type_counts as $type => $count):
          $color = $content_colors[$type] ?? '#64748b';
          $icon  = $content_icons[$type]  ?? '📁';
        ?>
        <div class="cd-type-summary-row">
          <span class="cd-type-summary-icon" style="--cd-type-base: <?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>">
            <?= $icon ?>
          </span>
          <span class="cd-type-summary-label">
            <?= ucfirst(str_replace('_', ' ', $type)) ?>
          </span>
          <span class="cd-type-summary-count">
            <?= $count ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

</div>
<?php $_ka_json = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT; ?>
<script>
kaApplyAppContext(<?= json_encode([
  'catalog' => ['courseProgressUrl' => base_url('index.php/courses/progress_state/')],
], $_ka_json) ?>);
</script>
<script src="<?= base_url('assets/js/catalog.js') ?>"></script>