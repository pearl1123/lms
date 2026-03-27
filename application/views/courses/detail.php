<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$course            = $course            ?? null;
$modules           = $modules           ?? [];
$is_enrolled       = $is_enrolled       ?? false;
$total_modules     = $total_modules     ?? 0;
$completed_modules = $completed_modules ?? 0;
$progress_pct      = $progress_pct      ?? 0;
$total_enrolled    = $total_enrolled    ?? 0;
$user_role         = strtolower($user->role ?? 'employee');

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
<style>
/* ── Course hero ── */
.cd-hero {
  background: linear-gradient(135deg, var(--ka-navy,#1a3a5c) 0%, #1e4976 60%, #2d6a9f 100%);
  border-radius: 16px; padding: 2rem;
  margin-bottom: 1.75rem; position: relative; overflow: hidden;
  box-shadow: 0 8px 32px rgba(26,58,92,.18);
}
.cd-hero::before {
  content:''; position:absolute; top:-60px; right:-60px;
  width:260px; height:260px; border-radius:50%;
  background:rgba(109,171,207,.1); pointer-events:none;
}
.cd-hero-inner { position:relative; z-index:1; display:flex; gap:2rem; flex-wrap:wrap; align-items:flex-start; }
.cd-hero-left { flex:1; min-width:260px; }
.cd-hero-cat {
  font-size:.625rem; font-weight:700; text-transform:uppercase;
  letter-spacing:.1em; color:var(--ka-primary,#6dabcf); margin-bottom:.5rem;
}
.cd-hero-title {
  font-size:1.625rem; font-weight:800; color:#fff;
  margin:0 0 .625rem; letter-spacing:-.02em; line-height:1.25;
}
.cd-hero-desc { font-size:.875rem; color:rgba(255,255,255,.65); line-height:1.6; margin-bottom:1.25rem; }
.cd-hero-meta { display:flex; align-items:center; gap:1.125rem; flex-wrap:wrap; }
.cd-hero-meta-item {
  display:flex; align-items:center; gap:5px;
  font-size:.75rem; color:rgba(255,255,255,.6); font-weight:500;
}
.cd-hero-meta-item svg { width:14px; height:14px; flex-shrink:0; }

/* Enroll card */
.cd-enroll-card {
  background:#fff; border-radius:14px;
  padding:1.375rem; min-width:260px; flex-shrink:0;
  box-shadow:0 8px 32px rgba(0,0,0,.15);
}
.cd-enroll-title {
  font-size:.875rem; font-weight:700; color:var(--ka-text,#1e293b);
  margin-bottom:1rem;
}
.cd-enroll-stats { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:1rem; }
.cd-enroll-stat { text-align:center; }
.cd-enroll-stat-val { font-size:1.25rem; font-weight:800; color:var(--ka-text,#1e293b); line-height:1; }
.cd-enroll-stat-lbl { font-size:.625rem; color:var(--ka-text-muted,#64748b); margin-top:2px; font-weight:500; }
.cd-enroll-prog-wrap { margin-bottom:1rem; }
.cd-enroll-prog-top { display:flex; justify-content:space-between; margin-bottom:5px; }
.cd-enroll-prog-lbl { font-size:.6875rem; color:var(--ka-text-muted,#64748b); font-weight:500; }
.cd-enroll-prog-pct { font-size:.6875rem; font-weight:700; }
.cd-prog-bar { height:8px; background:var(--ka-border,#e2e8f0); border-radius:4px; overflow:hidden; }
.cd-prog-fill {
  height:100%; border-radius:4px;
  background:linear-gradient(90deg,var(--ka-primary,#6dabcf),#5a9ec1);
  transition:width .8s ease;
}
.cd-prog-fill.done { background:linear-gradient(90deg,#22c55e,#15803d); }
.cd-enroll-btn {
  display:block; width:100%; padding:.75rem;
  border-radius:9px; font-size:.875rem; font-weight:700;
  text-align:center; text-decoration:none;
  transition:all .2s; border:none; cursor:pointer;
  box-shadow:0 4px 12px rgba(0,0,0,.1);
}
.cd-enroll-btn-primary { background:var(--ka-navy,#1a3a5c); color:#fff; }
.cd-enroll-btn-primary:hover { background:#254d75; color:#fff; transform:translateY(-1px); }
.cd-enroll-btn-continue{ background:var(--ka-primary,#6dabcf); color:#fff; }
.cd-enroll-btn-continue:hover { background:#5a9ec1; color:#fff; transform:translateY(-1px); }
.cd-enroll-btn-cert { background:#ecfdf5; color:#15803d; }
.cd-enroll-btn-cert:hover { background:#22c55e; color:#fff; }
.cd-enroll-btn-outline {
  background:transparent; color:var(--ka-text-muted,#64748b);
  border:1.5px solid var(--ka-border,#e2e8f0); margin-top:.5rem;
}
.cd-enroll-btn-outline:hover { border-color:var(--ka-primary,#6dabcf); color:var(--ka-primary,#6dabcf); }
.cd-enrolled-badge {
  display:flex; align-items:center; justify-content:center; gap:6px;
  padding:.5rem; border-radius:8px; margin-bottom:.875rem;
  background:#ecfdf5; color:#065f46; font-size:.75rem; font-weight:700;
}
.cd-enrolled-badge svg { width:14px; height:14px; }

/* ── Layout ── */
.cd-layout { display:grid; grid-template-columns:1fr 320px; gap:1.25rem; }
@media(max-width:1099.98px){ .cd-layout { grid-template-columns:1fr; } }

/* ── Panel ── */
.cd-panel {
  background:#fff; border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px; overflow:hidden; margin-bottom:1.25rem;
}
.cd-panel-hdr {
  padding:1rem 1.25rem; border-bottom:1px solid var(--ka-border,#e2e8f0);
  display:flex; align-items:center; justify-content:space-between;
}
.cd-panel-title { font-size:.9rem; font-weight:700; color:var(--ka-text,#1e293b); margin:0; }
.cd-panel-body  { padding:1.25rem; }

/* ── Module list ── */
.cd-module-list { list-style:none; margin:0; padding:0; }
.cd-module-item {
  display:flex; align-items:center; gap:12px;
  padding:.875rem 1.25rem;
  border-bottom:1px solid var(--ka-border,#e2e8f0);
  transition:background .15s;
}
.cd-module-item:last-child { border-bottom:none; }
.cd-module-item:hover { background:var(--ka-accent,#e8f4fd); }

.cd-module-num {
  width:32px; height:32px; border-radius:50%; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  font-size:.75rem; font-weight:700;
}
.cd-module-num.status-completed { background:#ecfdf5; color:#22c55e; }
.cd-module-num.status-in_progress { background:var(--ka-accent,#e8f4fd); color:var(--ka-primary,#6dabcf); }
.cd-module-num.status-not_started { background:var(--ka-bg,#f8fafc); color:var(--ka-text-muted,#64748b); border:1.5px solid var(--ka-border,#e2e8f0); }
.cd-module-num.status-locked { background:var(--ka-bg,#f8fafc); color:#cbd5e1; border:1.5px solid var(--ka-border,#e2e8f0); }

.cd-module-body { flex:1; min-width:0; }
.cd-module-title { font-size:.875rem; font-weight:600; color:var(--ka-text,#1e293b); margin-bottom:2px; }
.cd-module-meta  { display:flex; align-items:center; gap:.625rem; }
.cd-module-type  {
  font-size:.625rem; font-weight:700; text-transform:uppercase;
  letter-spacing:.06em; padding:2px 7px; border-radius:20px;
}
.cd-module-status-tag {
  font-size:.625rem; font-weight:700; padding:2px 7px; border-radius:20px;
}
.cd-module-status-completed  { background:#ecfdf5; color:#065f46; }
.cd-module-status-in_progress{ background:var(--ka-accent,#e8f4fd); color:var(--ka-primary-deep,#4a8eb0); }
.cd-module-status-not_started{ background:var(--ka-bg,#f8fafc); color:var(--ka-text-muted,#64748b); }
.cd-module-status-locked     { background:var(--ka-bg,#f8fafc); color:#cbd5e1; }

.cd-module-action {
  font-size:.6875rem; font-weight:700; padding:5px 12px; border-radius:7px;
  text-decoration:none; white-space:nowrap; transition:all .15s;
  border:none; cursor:pointer;
}
.cd-module-action-start    { background:var(--ka-navy,#1a3a5c); color:#fff; }
.cd-module-action-start:hover { background:#254d75; color:#fff; }
.cd-module-action-continue { background:var(--ka-accent,#e8f4fd); color:var(--ka-primary-deep,#4a8eb0); }
.cd-module-action-continue:hover { background:var(--ka-primary,#6dabcf); color:#fff; }
.cd-module-action-review   { background:#f0fdf4; color:#15803d; }
.cd-module-action-review:hover { background:#22c55e; color:#fff; }
.cd-module-action-locked   { background:var(--ka-bg,#f8fafc); color:#cbd5e1; cursor:not-allowed; }

/* ── Info card (right sidebar) ── */
.cd-info-list { list-style:none; margin:0; padding:0; }
.cd-info-item {
  display:flex; align-items:center; gap:10px;
  padding:.75rem 0; border-bottom:1px solid var(--ka-border,#e2e8f0);
  font-size:.8125rem;
}
.cd-info-item:last-child { border-bottom:none; }
.cd-info-icon {
  width:32px; height:32px; border-radius:8px; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
}
.cd-info-icon svg { width:15px; height:15px; }
.cd-info-label { color:var(--ka-text-muted,#64748b); font-size:.75rem; font-weight:500; }
.cd-info-value { font-weight:600; color:var(--ka-text,#1e293b); margin-left:auto; }
</style>

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
          <div class="cd-enroll-prog-top">
            <span class="cd-enroll-prog-lbl">Your Progress</span>
            <span class="cd-enroll-prog-pct" style="color:<?= $progress_pct >= 100 ? '#22c55e' : 'var(--ka-primary,#6dabcf)' ?>">
              <?= $progress_pct ?>%
            </span>
          </div>
          <div class="cd-prog-bar">
            <div class="cd-prog-fill <?= $progress_pct >= 100 ? 'done' : '' ?>" style="width:<?= $progress_pct ?>%"></div>
          </div>
          <div style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin-top:5px;">
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
            if ($m->my_status !== 'completed') { $next_module = $m; break; }
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

      <?php elseif ($user_role === 'employee'): ?>
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
        <a href="<?= base_url('courses/enroll/'.$course->id) ?>"
           class="cd-enroll-btn cd-enroll-btn-primary"
           onclick="return confirm('Enroll in this course?')">
          Enroll Now — It\'s Free
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
    <div class="cd-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s;">
      <div class="cd-panel-hdr">
        <h3 class="cd-panel-title">Course Modules</h3>
        <span style="font-size:.75rem;font-weight:600;color:var(--ka-text-muted,#64748b);">
          <?= $total_modules ?> module<?= $total_modules !== 1 ? 's' : '' ?>
        </span>
      </div>
      <ul class="cd-module-list">
        <?php if ( ! empty($modules)): ?>
          <?php foreach ($modules as $idx => $module):
            $status = $module->my_status ?? 'not_started';
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
                <span class="cd-module-type" style="background:<?= $type_color ?>22;color:<?= $type_color ?>;">
                  <?= $type_icon ?> <?= ucfirst(str_replace('_', ' ', $module->content_type ?? 'content')) ?>
                </span>
                <?php if ($is_enrolled): ?>
                <span class="cd-module-status-tag cd-module-status-<?= $status ?>">
                  <?= ucfirst(str_replace('_', ' ', $status)) ?>
                </span>
                <?php endif; ?>
              </div>
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
          <li style="padding:2rem;text-align:center;color:var(--ka-text-muted,#64748b);font-size:.875rem;">
            No modules available yet.
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <!-- Right: Course info -->
  <div>
    <div class="cd-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.15s;">
      <div class="cd-panel-hdr">
        <h3 class="cd-panel-title">Course Info</h3>
      </div>
      <div class="cd-panel-body" style="padding-top:.25rem;padding-bottom:.25rem;">
        <ul class="cd-info-list">
          <li class="cd-info-item">
            <div class="cd-info-icon" style="background:#eff6ff;color:#3b82f6;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </div>
            <span class="cd-info-label">Total Modules</span>
            <span class="cd-info-value"><?= $total_modules ?></span>
          </li>
          <li class="cd-info-item">
            <div class="cd-info-icon" style="background:#f0fdf4;color:#22c55e;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <span class="cd-info-label">Enrolled</span>
            <span class="cd-info-value"><?= $total_enrolled ?></span>
          </li>
          <li class="cd-info-item">
            <div class="cd-info-icon" style="background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <span class="cd-info-label">Category</span>
            <span class="cd-info-value"><?= htmlspecialchars($course->category_name ?? '—') ?></span>
          </li>
          <?php if ( ! empty($course->creator_name)): ?>
          <li class="cd-info-item">
            <div class="cd-info-icon" style="background:#fffbeb;color:#f59f00;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
            </div>
            <span class="cd-info-label">Instructor</span>
            <span class="cd-info-value" style="max-width:120px;text-align:right;font-size:.75rem;"><?= htmlspecialchars($course->creator_name) ?></span>
          </li>
          <?php endif; ?>
          <li class="cd-info-item">
            <div class="cd-info-icon" style="background:#f1f5f9;color:#64748b;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <span class="cd-info-label">Published</span>
            <span class="cd-info-value"><?= date('M j, Y', strtotime($course->created_at)) ?></span>
          </li>
          <?php if ( ! empty($course->expiry_days)): ?>
          <li class="cd-info-item">
            <div class="cd-info-icon" style="background:#fef2f2;color:#ef4444;">
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
    <div class="cd-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.2s;">
      <div class="cd-panel-hdr">
        <h3 class="cd-panel-title">Content Types</h3>
      </div>
      <div class="cd-panel-body">
        <?php foreach ($type_counts as $type => $count):
          $color = $content_colors[$type] ?? '#64748b';
          $icon  = $content_icons[$type]  ?? '📁';
        ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:.625rem;">
          <span style="width:32px;height:32px;border-radius:8px;background:<?= $color ?>22;color:<?= $color ?>;display:flex;align-items:center;justify-content:center;font-size:.875rem;flex-shrink:0;">
            <?= $icon ?>
          </span>
          <span style="flex:1;font-size:.8125rem;font-weight:500;color:var(--ka-text,#1e293b);">
            <?= ucfirst(str_replace('_', ' ', $type)) ?>
          </span>
          <span style="font-size:.8125rem;font-weight:700;color:var(--ka-text-muted,#64748b);">
            <?= $count ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

</div>