<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
$analytics        = $analytics ?? [];
$my_courses       = $analytics['my_courses']          ?? 0;
$enrolled_students= $analytics['enrolled_students']    ?? 0;
$avg_completion   = $analytics['avg_completion']       ?? 78;
$pending_reviews  = $analytics['pending_reviews']      ?? 4;
// FIX: $user is always a stdClass object from the controller, never an array.
// Column name in aauth_users is "fullname" (no underscore).
$full_name  = isset($user) && is_object($user) ? ($user->fullname ?? 'Instructor') : 'Instructor';
$first_name = explode(' ', trim($full_name))[0];
?>

<?php $this->load->view('layouts/alerts'); ?>

<!-- ============================================================
     KABAGA ACADEMY — ETD / Instructor Dashboard
     Lung Center of the Philippines
============================================================ -->

<style>
/* ── Hero ── */
.ins-hero {
  background: linear-gradient(135deg, #1a3a5c 0%, #254d75 50%, #1e5c8a 100%);
  border-radius: 16px;
  padding: 1.75rem 2rem;
  margin-bottom: 1.75rem;
  position: relative; overflow: hidden;
  box-shadow: 0 8px 32px rgba(26,58,92,.18);
}
.ins-hero::before {
  content:''; position:absolute; top:-60px; right:-60px;
  width:240px; height:240px; border-radius:50%;
  background: rgba(109,171,207,.1); pointer-events:none;
}
.ins-hero::after {
  content:''; position:absolute; bottom:-40px; right:260px;
  width:140px; height:140px; border-radius:50%;
  background: rgba(255,255,255,.04); pointer-events:none;
}
.ins-hero-inner { position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:1.5rem;flex-wrap:wrap; }
.ins-hero-eyebrow { font-size:.6875rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.5);margin-bottom:.375rem; }
.ins-hero-title { font-size:1.5rem;font-weight:800;color:#fff;margin:0 0 .375rem;letter-spacing:-.02em;line-height:1.2; }
.ins-hero-sub { font-size:.875rem;color:rgba(255,255,255,.6);margin:0 0 1.25rem; }
.ins-cta-row { display:flex;gap:.625rem;flex-wrap:wrap; }
.ins-cta {
  display:inline-flex;align-items:center;gap:6px;
  padding:.5rem 1.125rem;border-radius:8px;font-size:.8125rem;
  font-weight:600;text-decoration:none;transition:all .2s;white-space:nowrap;
}
.ins-cta-primary { background:var(--ka-primary,#6dabcf);color:#fff;box-shadow:0 4px 14px rgba(109,171,207,.4); }
.ins-cta-primary:hover { background:#5a9ec1;color:#fff;transform:translateY(-1px); }
.ins-cta-ghost { background:rgba(255,255,255,.1);color:rgba(255,255,255,.85);border:1px solid rgba(255,255,255,.2); }
.ins-cta-ghost:hover { background:rgba(255,255,255,.18);color:#fff; }

/* Hero stat strip */
.ins-hero-stats { display:flex;gap:0;border-top:1px solid rgba(255,255,255,.1);margin-top:1.25rem;padding-top:1.25rem; }
.ins-hero-stat { flex:1;text-align:center;padding:0 .5rem; }
.ins-hero-stat + .ins-hero-stat { border-left:1px solid rgba(255,255,255,.1); }
.ins-hero-stat-val { font-size:1.375rem;font-weight:800;color:#fff;line-height:1; }
.ins-hero-stat-lbl { font-size:.6875rem;color:rgba(255,255,255,.5);margin-top:4px;font-weight:500; }

/* ── KPI strip ── */
.ins-kpi-strip { display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.75rem; }
@media(max-width:1199.98px){.ins-kpi-strip{grid-template-columns:repeat(2,1fr);}}
@media(max-width:575.98px){.ins-kpi-strip{grid-template-columns:1fr;}}

.ins-kpi {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;padding:1.125rem 1.25rem;
  position:relative;overflow:hidden;
  transition:box-shadow .2s,transform .2s;
}
.ins-kpi:hover { box-shadow:0 8px 24px rgba(0,0,0,.07);transform:translateY(-2px); }
.ins-kpi-accent { position:absolute;top:0;left:0;width:4px;height:100%;border-radius:14px 0 0 14px; }
.ins-kpi-top { display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:.875rem; }
.ins-kpi-icon {
  width:42px;height:42px;border-radius:11px;
  display:flex;align-items:center;justify-content:center;
}
.ins-kpi-icon svg { width:20px;height:20px; }
.ins-kpi-tag { font-size:.625rem;font-weight:700;padding:3px 8px;border-radius:20px; }
.ins-kpi-val  { font-size:1.875rem;font-weight:800;color:var(--ka-text,#1e293b);line-height:1;letter-spacing:-.03em; }
.ins-kpi-lbl  { font-size:.75rem;font-weight:500;color:var(--ka-text-muted,#64748b);margin-top:4px; }
.ins-kpi-foot { display:flex;align-items:center;gap:6px;margin-top:.625rem;font-size:.6875rem;font-weight:600; }
.trend-up   { color:#22c55e; }
.trend-down { color:#ef4444; }
.trend-icon { width:14px;height:14px; }

/* ── Panel ── */
.ins-panel {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;overflow:hidden;
}
.ins-panel-hdr {
  display:flex;align-items:center;justify-content:space-between;
  padding:1rem 1.25rem;border-bottom:1px solid var(--ka-border,#e2e8f0);
}
.ins-panel-title { font-size:.9rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0; }
.ins-panel-link {
  font-size:.75rem;font-weight:600;color:var(--ka-primary,#6dabcf);
  text-decoration:none;display:flex;align-items:center;gap:4px;
}
.ins-panel-link:hover { color:#5a9ec1; }
.ins-panel-body { padding:1.25rem; }

/* ── Layout grids ── */
.ins-grid-main  { display:grid;grid-template-columns:1fr 320px;gap:1.25rem;margin-bottom:1.25rem; }
.ins-grid-lower { display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem; }
@media(max-width:1099.98px){.ins-grid-main{grid-template-columns:1fr;}}
@media(max-width:991.98px){.ins-grid-lower{grid-template-columns:1fr;}}

/* ── My courses table ── */
.ins-course-table { width:100%;border-collapse:collapse; }
.ins-course-table thead th {
  font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;
  color:var(--ka-text-muted,#64748b);padding:.625rem 1rem;
  background:var(--ka-bg,#f8fafc);border-bottom:1px solid var(--ka-border,#e2e8f0);
  white-space:nowrap;
}
.ins-course-table tbody tr { border-bottom:1px solid var(--ka-border,#e2e8f0);transition:background .15s; }
.ins-course-table tbody tr:hover { background:var(--ka-accent,#e8f4fd); }
.ins-course-table tbody tr:last-child { border-bottom:none; }
.ins-course-table tbody td { padding:.8125rem 1rem;font-size:.8125rem;color:var(--ka-text,#1e293b);vertical-align:middle; }

.ins-course-badge {
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 9px;border-radius:20px;font-size:.6875rem;font-weight:600;
}
.ins-course-badge-pub    { background:#ecfdf5;color:#065f46; }
.ins-course-badge-draft  { background:#fffbeb;color:#92400e; }
.ins-course-badge-review { background:#eff6ff;color:#1d4ed8; }

.ins-mini-bar  { height:5px;background:var(--ka-border,#e2e8f0);border-radius:3px;overflow:hidden;min-width:80px; }
.ins-mini-fill { height:100%;border-radius:3px;background:linear-gradient(90deg,var(--ka-primary,#6dabcf),#5a9ec1); }

/* ── Student list ── */
.ins-student-list { list-style:none;margin:0;padding:0; }
.ins-student-item {
  display:flex;align-items:center;gap:10px;
  padding:.75rem 0;border-bottom:1px solid var(--ka-border,#e2e8f0);
}
.ins-student-item:last-child { border-bottom:none; }
.ins-student-avatar {
  width:32px;height:32px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,var(--ka-primary,#6dabcf),var(--ka-navy,#1a3a5c));
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-weight:700;font-size:.625rem;
}
.ins-student-info { flex:1;min-width:0; }
.ins-student-name { font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);line-height:1.2; }
.ins-student-course { font-size:.6875rem;color:var(--ka-text-muted,#64748b); }
.ins-student-pct { font-size:.75rem;font-weight:700;color:var(--ka-primary,#6dabcf);white-space:nowrap; }

/* ── Pending reviews ── */
.ins-review-list { list-style:none;margin:0;padding:0; }
.ins-review-item {
  display:flex;align-items:center;gap:12px;
  padding:.8125rem 0;border-bottom:1px solid var(--ka-border,#e2e8f0);
}
.ins-review-item:last-child { border-bottom:none; }
.ins-review-icon {
  width:36px;height:36px;border-radius:9px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
}
.ins-review-icon svg { width:16px;height:16px; }
.ins-review-body { flex:1;min-width:0; }
.ins-review-title { font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:2px; }
.ins-review-sub   { font-size:.6875rem;color:var(--ka-text-muted,#64748b); }
.ins-review-action {
  font-size:.6875rem;font-weight:700;padding:4px 10px;border-radius:7px;
  background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);
  text-decoration:none;white-space:nowrap;transition:all .15s;
}
.ins-review-action:hover { background:var(--ka-primary,#6dabcf);color:#fff; }

/* ── Schedule/calendar strip ── */
.ins-cal-strip { display:flex;gap:.625rem;overflow-x:auto;padding-bottom:4px; }
.ins-cal-strip::-webkit-scrollbar { height:3px; }
.ins-cal-strip::-webkit-scrollbar-thumb { background:var(--ka-border,#e2e8f0);border-radius:2px; }
.ins-cal-day {
  flex-shrink:0;width:58px;border-radius:12px;
  border:1.5px solid var(--ka-border,#e2e8f0);
  padding:.625rem .5rem;text-align:center;
  transition:all .2s;cursor:pointer;
}
.ins-cal-day:hover { border-color:var(--ka-primary,#6dabcf);background:var(--ka-accent,#e8f4fd); }
.ins-cal-day.today { background:var(--ka-navy,#1a3a5c);border-color:var(--ka-navy,#1a3a5c); }
.ins-cal-day.has-event { border-color:var(--ka-primary,#6dabcf); }
.ins-cal-dow { font-size:.625rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--ka-text-muted,#64748b);margin-bottom:4px; }
.ins-cal-day.today .ins-cal-dow { color:rgba(255,255,255,.6); }
.ins-cal-num { font-size:1rem;font-weight:800;color:var(--ka-text,#1e293b);line-height:1; }
.ins-cal-day.today .ins-cal-num { color:#fff; }
.ins-cal-dot { width:5px;height:5px;border-radius:50%;background:var(--ka-primary,#6dabcf);margin:5px auto 0; }
.ins-cal-day.today .ins-cal-dot { background:rgba(255,255,255,.6); }

.ins-event-list { list-style:none;margin:.875rem 0 0;padding:0; }
.ins-event-item {
  display:flex;align-items:center;gap:10px;
  padding:.625rem 0;border-bottom:1px solid var(--ka-border,#e2e8f0);
}
.ins-event-item:last-child { border-bottom:none; }
.ins-event-dot { width:10px;height:10px;border-radius:3px;flex-shrink:0; }
.ins-event-body { flex:1;min-width:0; }
.ins-event-title { font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b); }
.ins-event-time  { font-size:.6875rem;color:var(--ka-text-muted,#64748b); }

/* ── Quick actions ── */
.ins-quick-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem; }
.ins-quick-btn {
  display:flex;align-items:center;gap:10px;padding:.875rem 1rem;
  border-radius:10px;background:var(--ka-bg,#f8fafc);
  border:1px solid var(--ka-border,#e2e8f0);
  text-decoration:none;transition:all .18s;cursor:pointer;
}
.ins-quick-btn:hover { background:var(--ka-accent,#e8f4fd);border-color:var(--ka-primary,#6dabcf);transform:translateY(-1px); }
.ins-quick-icon { width:36px;height:36px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center; }
.ins-quick-icon svg { width:16px;height:16px; }
.ins-quick-title { font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);line-height:1.2; }
.ins-quick-sub   { font-size:.6875rem;color:var(--ka-text-muted,#64748b); }
</style>

<!-- ══ Hero ═══════════════════════════════════════════════ -->
<div class="ins-hero animate__animated animate__fadeIn animate__fast">
  <div class="ins-hero-inner">
    <div>
      <p class="ins-hero-eyebrow">ETD / Instructor Panel</p>
      <h2 class="ins-hero-title">Hello, <?= htmlspecialchars($first_name) ?>! 🎓</h2>
      <p class="ins-hero-sub">Manage your classes, review learners, and track progress — <?= date('l, F j, Y') ?></p>
      <div class="ins-cta-row">
        <a href="<?= base_url('index.php/manage_courses/create') ?>" class="ins-cta ins-cta-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Create Course
        </a>
        <a href="<?= base_url('index.php/my_classes') ?>" class="ins-cta ins-cta-ghost">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          My Classes
        </a>
        <a href="<?= base_url('index.php/assessments') ?>" class="ins-cta ins-cta-ghost">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          Assessments
        </a>
      </div>
    </div>
  </div>
  <div class="ins-hero-stats">
    <div class="ins-hero-stat">
      <div class="ins-hero-stat-val"><?= $my_courses ?></div>
      <div class="ins-hero-stat-lbl">My Courses</div>
    </div>
    <div class="ins-hero-stat">
      <div class="ins-hero-stat-val"><?= $enrolled_students ?></div>
      <div class="ins-hero-stat-lbl">Enrolled Learners</div>
    </div>
    <div class="ins-hero-stat">
      <div class="ins-hero-stat-val"><?= $avg_completion ?>%</div>
      <div class="ins-hero-stat-lbl">Avg Completion</div>
    </div>
    <div class="ins-hero-stat">
      <div class="ins-hero-stat-val"><?= $pending_reviews ?></div>
      <div class="ins-hero-stat-lbl">Pending Reviews</div>
    </div>
  </div>
</div>

<!-- ══ KPI Cards ════════════════════════════════════════════ -->
<div class="ins-kpi-strip">

  <div class="ins-kpi animate__animated animate__fadeInUp animate__fast">
    <div class="ins-kpi-accent" style="background:#3b82f6;"></div>
    <div class="ins-kpi-top">
      <div class="ins-kpi-icon" style="background:#eff6ff;color:#3b82f6;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      </div>
      <span class="ins-kpi-tag" style="background:#eff6ff;color:#3b82f6;">Courses</span>
    </div>
    <div class="ins-kpi-val"><?= $my_courses ?></div>
    <div class="ins-kpi-lbl">Published Courses</div>
    <div class="ins-kpi-foot">
      <svg class="trend-icon trend-up" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
      <span class="trend-up">1 added this month</span>
    </div>
  </div>

  <div class="ins-kpi animate__animated animate__fadeInUp animate__fast" style="animation-delay:.05s">
    <div class="ins-kpi-accent" style="background:#22c55e;"></div>
    <div class="ins-kpi-top">
      <div class="ins-kpi-icon" style="background:#f0fdf4;color:#22c55e;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <span class="ins-kpi-tag" style="background:#f0fdf4;color:#22c55e;">Students</span>
    </div>
    <div class="ins-kpi-val"><?= $enrolled_students ?></div>
    <div class="ins-kpi-lbl">Active Learners</div>
    <div class="ins-kpi-foot">
      <svg class="trend-icon trend-up" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
      <span class="trend-up">+5 this week</span>
    </div>
  </div>

  <div class="ins-kpi animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s">
    <div class="ins-kpi-accent" style="background:var(--ka-primary,#6dabcf);"></div>
    <div class="ins-kpi-top">
      <div class="ins-kpi-icon" style="background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      </div>
      <span class="ins-kpi-tag" style="background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);">Completion</span>
    </div>
    <div class="ins-kpi-val"><?= $avg_completion ?>%</div>
    <div class="ins-kpi-lbl">Avg Course Completion</div>
    <div class="ins-kpi-foot">
      <svg class="trend-icon trend-up" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
      <span class="trend-up">+3% vs last month</span>
    </div>
  </div>

  <div class="ins-kpi animate__animated animate__fadeInUp animate__fast" style="animation-delay:.15s">
    <div class="ins-kpi-accent" style="background:#f59f00;"></div>
    <div class="ins-kpi-top">
      <div class="ins-kpi-icon" style="background:#fffbeb;color:#f59f00;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      </div>
      <span class="ins-kpi-tag" style="background:#fffbeb;color:#f59f00;">Reviews</span>
    </div>
    <div class="ins-kpi-val"><?= $pending_reviews ?></div>
    <div class="ins-kpi-lbl">Pending Submissions</div>
    <div class="ins-kpi-foot" style="color:var(--ka-text-muted,#64748b);">
      Awaiting your review
    </div>
  </div>

</div>

<!-- ══ Main Grid ════════════════════════════════════════════ -->
<div class="ins-grid-main">

  <!-- My Courses Table -->
  <div class="ins-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.2s;">
    <div class="ins-panel-hdr">
      <h3 class="ins-panel-title">My Courses</h3>
      <a href="<?= base_url('index.php/manage_courses') ?>" class="ins-panel-link">
        Manage all
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </a>
    </div>
    <div style="overflow-x:auto;">
      <table class="ins-course-table">
        <thead>
          <tr>
            <th>Course</th>
            <th>Status</th>
            <th>Students</th>
            <th>Avg Progress</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if ( ! empty($my_courses_list)): ?>
            <?php
            $thumb_colors = [
              'linear-gradient(135deg,#3b82f6,#1d4ed8)',
              'linear-gradient(135deg,#22c55e,#15803d)',
              'linear-gradient(135deg,#6dabcf,#1a3a5c)',
              'linear-gradient(135deg,#f59f00,#b45309)',
              'linear-gradient(135deg,#ef4444,#991b1b)',
            ];
            foreach ($my_courses_list as $i => $course):
              $color   = $thumb_colors[$i % count($thumb_colors)];
              $initials= strtoupper(substr($course->title, 0, 2));
            ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:10px;">
                  <div style="width:36px;height:36px;border-radius:9px;background:<?= $color ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;font-weight:700;font-size:.6875rem;">
                    <?= $initials ?>
                  </div>
                  <div>
                    <div style="font-weight:600;font-size:.8125rem;"><?= htmlspecialchars($course->title) ?></div>
                    <div style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);"><?= $course->module_count ?? 0 ?> modules</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="ins-course-badge ins-course-badge-pub">● <?= htmlspecialchars($course->status_label ?? 'Published') ?></span>
              </td>
              <td style="font-weight:600;"><?= $course->enrolled_count ?? 0 ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div class="ins-mini-bar"><div class="ins-mini-fill" style="width:<?= $course->avg_progress ?? 0 ?>%"></div></div>
                  <span style="font-size:.75rem;font-weight:700;color:var(--ka-primary,#6dabcf);white-space:nowrap;"><?= $course->avg_progress ?? 0 ?>%</span>
                </div>
              </td>
              <td><a href="<?= base_url('index.php/manage_courses/edit/' . $course->id) ?>" style="font-size:.6875rem;font-weight:600;color:var(--ka-primary,#6dabcf);text-decoration:none;">Edit</a></td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Fallback sample rows when no DB data yet -->
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:10px;">
                  <div style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.8)" stroke-width="2"><path d="M9 12l2 2 4-4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                  </div>
                  <div>
                    <div style="font-weight:600;font-size:.8125rem;">No courses yet</div>
                    <div style="font-size:.6875rem;color:var(--ka-text-muted,#64748b);">Create your first course</div>
                  </div>
                </div>
              </td>
              <td><span class="ins-course-badge ins-course-badge-draft">● Draft</span></td>
              <td style="font-weight:600;color:var(--ka-text-muted);">—</td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div class="ins-mini-bar"><div class="ins-mini-fill" style="width:0%"></div></div>
                  <span style="font-size:.75rem;font-weight:600;color:var(--ka-text-muted,#64748b);">0%</span>
                </div>
              </td>
              <td><a href="<?= base_url('index.php/manage_courses/create') ?>" style="font-size:.6875rem;font-weight:600;color:var(--ka-primary,#6dabcf);text-decoration:none;">Create</a></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Right column -->
  <div style="display:flex;flex-direction:column;gap:1.25rem;">

    <!-- Quick Actions -->
    <div class="ins-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.25s;">
      <div class="ins-panel-hdr">
        <h3 class="ins-panel-title">Quick Actions</h3>
      </div>
      <div class="ins-panel-body">
        <div class="ins-quick-grid">
          <a href="<?= base_url('index.php/manage_courses/create') ?>" class="ins-quick-btn">
            <div class="ins-quick-icon" style="background:#eff6ff;color:#3b82f6;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </div>
            <div>
              <div class="ins-quick-title">New Course</div>
              <div class="ins-quick-sub">Start from scratch</div>
            </div>
          </a>
          <a href="<?= base_url('index.php/assessments') ?>" class="ins-quick-btn">
            <div class="ins-quick-icon" style="background:#f0fdf4;color:#22c55e;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </div>
            <div>
              <div class="ins-quick-title">Assessments</div>
              <div class="ins-quick-sub">Create quizzes</div>
            </div>
          </a>
          <a href="<?= base_url('index.php/my_classes') ?>" class="ins-quick-btn">
            <div class="ins-quick-icon" style="background:#fffbeb;color:#f59f00;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div>
              <div class="ins-quick-title">My Classes</div>
              <div class="ins-quick-sub">Schedule sessions</div>
            </div>
          </a>
          <a href="<?= base_url('index.php/reports') ?>" class="ins-quick-btn">
            <div class="ins-quick-icon" style="background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </div>
            <div>
              <div class="ins-quick-title">Analytics</div>
              <div class="ins-quick-sub">View reports</div>
            </div>
          </a>
        </div>
      </div>
    </div>

    <!-- Pending Reviews -->
    <div class="ins-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.3s;">
      <div class="ins-panel-hdr">
        <h3 class="ins-panel-title">Pending Reviews</h3>
        <span style="font-size:.6875rem;font-weight:700;padding:3px 8px;background:#fffbeb;color:#92400e;border-radius:20px;"><?= $pending_reviews ?> items</span>
      </div>
      <div class="ins-panel-body" style="padding-top:.25rem;padding-bottom:.25rem;">
        <ul class="ins-review-list">
          <li class="ins-review-item">
            <div class="ins-review-icon" style="background:#eff6ff;color:#3b82f6;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="ins-review-body">
              <div class="ins-review-title">Assessment — Juan Dela Cruz</div>
              <div class="ins-review-sub">Infection Control · Module 4</div>
            </div>
            <a href="<?= base_url('index.php/assessments') ?>" class="ins-review-action">Review</a>
          </li>
          <li class="ins-review-item">
            <div class="ins-review-icon" style="background:#f0fdf4;color:#22c55e;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="ins-review-body">
              <div class="ins-review-title">Assignment — Maria Santos</div>
              <div class="ins-review-sub">Patient Safety · Module 3</div>
            </div>
            <a href="<?= base_url('index.php/assessments') ?>" class="ins-review-action">Review</a>
          </li>
          <li class="ins-review-item">
            <div class="ins-review-icon" style="background:#fffbeb;color:#f59f00;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="ins-review-body">
              <div class="ins-review-title">Quiz Retake — Ana Lim</div>
              <div class="ins-review-sub">Respiratory Care · Quiz 2</div>
            </div>
            <a href="<?= base_url('index.php/assessments') ?>" class="ins-review-action">Review</a>
          </li>
        </ul>
      </div>
    </div>

  </div>

</div><!-- /ins-grid-main -->

<!-- ══ Lower Row ════════════════════════════════════════════ -->
<div class="ins-grid-lower">

  <!-- Student Progress — from controller $student_list -->
  <div class="ins-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.35s;">
    <div class="ins-panel-hdr">
      <h3 class="ins-panel-title">Student Progress</h3>
      <a href="<?= base_url('index.php/my_classes') ?>" class="ins-panel-link">View all</a>
    </div>
    <div class="ins-panel-body" style="padding-top:.25rem;padding-bottom:.25rem;">
      <ul class="ins-student-list">
        <?php
        $display_students = ! empty($student_list) ? $student_list : [];
        $fallback_colors  = ['#3b82f6','#22c55e','#6dabcf','#f59f00','#ef4444','#8b5cf6'];

        if ( ! empty($display_students)):
          foreach ($display_students as $idx => $s):
            $sname    = is_object($s) ? $s->fullname     : ($s['name']       ?? 'Unknown');
            $scourse  = is_object($s) ? $s->course_title : ($s['course']     ?? '—');
            $spct     = is_object($s) ? ($s->progress_pct ?? 0) : ($s['pct'] ?? 0);
            $color    = $fallback_colors[$idx % count($fallback_colors)];
            $parts    = explode(' ', trim($sname));
            $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
        ?>
        <li class="ins-student-item">
          <div class="ins-student-avatar" style="background:linear-gradient(135deg,<?= $color ?>,var(--ka-navy,#1a3a5c));"><?= $initials ?></div>
          <div class="ins-student-info">
            <div class="ins-student-name"><?= htmlspecialchars($sname) ?></div>
            <div class="ins-student-course"><?= htmlspecialchars($scourse) ?></div>
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <div class="ins-mini-bar" style="width:70px;"><div class="ins-mini-fill" style="width:<?= $spct ?>%;background:linear-gradient(90deg,<?= $color ?>,<?= $color ?>99);"></div></div>
            <span class="ins-student-pct" style="color:<?= $color ?>;"><?= $spct ?>%</span>
          </div>
        </li>
        <?php endforeach; else: ?>
        <li style="padding:1rem 0;text-align:center;color:var(--ka-text-muted,#64748b);font-size:.8125rem;">No enrolled students yet.</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <!-- Schedule + Completion Chart -->
  <div style="display:flex;flex-direction:column;gap:1.25rem;">

    <!-- Completion chart -->
    <div class="ins-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.4s;">
      <div class="ins-panel-hdr">
        <h3 class="ins-panel-title">Completion Trend</h3>
        <span style="font-size:.75rem;color:var(--ka-text-muted,#64748b);">Last 6 months</span>
      </div>
      <div class="ins-panel-body">
        <div id="ins-completion-chart" style="height:160px;"></div>
      </div>
    </div>

    <!-- Upcoming schedule -->
    <div class="ins-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.45s;">
      <div class="ins-panel-hdr">
        <h3 class="ins-panel-title">Upcoming Schedule</h3>
        <a href="<?= base_url('index.php/my_classes') ?>" class="ins-panel-link">Full calendar</a>
      </div>
      <div class="ins-panel-body">
        <div class="ins-cal-strip">
          <?php
          $days_short = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
          for ($d = 0; $d < 7; $d++):
            $ts      = strtotime("+$d days");
            $dow     = $days_short[date('w', $ts)];
            $num     = date('j', $ts);
            $is_today= ($d === 0);
            $has_ev  = in_array($d, [0, 2, 4]);
          ?>
          <div class="ins-cal-day <?= $is_today ? 'today' : '' ?> <?= ($has_ev && ! $is_today) ? 'has-event' : '' ?>">
            <div class="ins-cal-dow"><?= $dow ?></div>
            <div class="ins-cal-num"><?= $num ?></div>
            <?php if ($has_ev): ?><div class="ins-cal-dot"></div><?php endif; ?>
          </div>
          <?php endfor; ?>
        </div>
        <ul class="ins-event-list">
          <li class="ins-event-item">
            <div class="ins-event-dot" style="background:#3b82f6;"></div>
            <div class="ins-event-body">
              <div class="ins-event-title">Live Q&amp;A — Infection Control</div>
              <div class="ins-event-time">Today · 10:00 AM – 11:00 AM</div>
            </div>
          </li>
          <li class="ins-event-item">
            <div class="ins-event-dot" style="background:#22c55e;"></div>
            <div class="ins-event-body">
              <div class="ins-event-title">Module Review — Patient Safety</div>
              <div class="ins-event-time">Today · 2:00 PM – 3:30 PM</div>
            </div>
          </li>
          <li class="ins-event-item">
            <div class="ins-event-dot" style="background:#f59f00;"></div>
            <div class="ins-event-body">
              <div class="ins-event-title">Assessment Deadline — Respiratory</div>
              <div class="ins-event-time"><?= date('D, M j', strtotime('+2 days')) ?> · All day</div>
            </div>
          </li>
        </ul>
      </div>
    </div>

  </div>

</div><!-- /ins-grid-lower -->

<script src="<?= base_url('assets/js/chart.umd.min.js'); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const ctx = document.getElementById('ins-completion-chart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['Oct','Nov','Dec','Jan','Feb','Mar'],
      datasets: [
        { label: 'Infection Control', data: [55,60,65,70,78,88],
          borderColor: '#3b82f6', borderWidth: 2.5, pointRadius: 3,
          pointBackgroundColor: '#3b82f6', tension: .4, fill: false },
        { label: 'Patient Safety', data: [40,48,55,60,68,75],
          borderColor: '#22c55e', borderWidth: 2.5, pointRadius: 3,
          pointBackgroundColor: '#22c55e', tension: .4, fill: false },
        { label: 'Respiratory', data: [0,0,20,35,50,62],
          borderColor: '#6dabcf', borderWidth: 2.5, pointRadius: 3,
          pointBackgroundColor: '#6dabcf', tension: .4, fill: false },
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', align: 'start',
          labels: { boxWidth: 10, boxHeight: 3, usePointStyle: false,
            font: { size: 10, weight: '600' }, padding: 12 } },
        tooltip: { backgroundColor: '#1a3a5c', cornerRadius: 8, padding: 10 }
      },
      scales: {
        x: { grid: { display: false }, border: { display: false },
          ticks: { font: { size: 10 }, color: '#94a3b8' } },
        y: { min: 0, max: 100,
          grid: { color: '#e2e8f0' }, border: { display: false, dash: [4,4] },
          ticks: { font: { size: 10 }, color: '#94a3b8',
            callback: v => v + '%' } }
      }
    }
  });
});
</script>