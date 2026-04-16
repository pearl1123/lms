<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
// Pull analytics from controller
$analytics   = $analytics ?? [];
$total_users = $analytics['total_users']   ?? 0;
$active_users= $analytics['active_users']  ?? 0;
$inactive    = $analytics['inactive_users']?? 0;
$total_courses=$analytics['total_courses'] ?? 0;
?>

<?php echo $alerts_partial_html ?? ''; ?>

<!-- ============================================================
     KABAGA ACADEMY — Admin Dashboard View
     Premium LMS — Lung Center of the Philippines
============================================================ -->

<style>
/* ── Page-level overrides ── */
.ka-page-content { background: var(--ka-bg, #f8fafc); }

/* ── Welcome banner ── */
.ka-welcome {
  background: linear-gradient(135deg, var(--ka-navy, #1a3a5c) 0%, #254d75 60%, #2d6a9f 100%);
  border-radius: 16px;
  padding: 2rem 2rem 0;
  overflow: hidden;
  position: relative;
  margin-bottom: 1.75rem;
  box-shadow: 0 8px 32px rgba(26,58,92,.18);
}
.ka-welcome::before {
  content: '';
  position: absolute;
  top: -60px; right: -60px;
  width: 260px; height: 260px;
  border-radius: 50%;
  background: rgba(109,171,207,.12);
  pointer-events: none;
}
.ka-welcome::after {
  content: '';
  position: absolute;
  bottom: -40px; right: 160px;
  width: 160px; height: 160px;
  border-radius: 50%;
  background: rgba(109,171,207,.07);
  pointer-events: none;
}
.ka-welcome-body { position: relative; z-index: 1; }
.ka-welcome-eyebrow {
  font-size: .6875rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: rgba(255,255,255,.55);
  margin-bottom: .375rem;
}
.ka-welcome-title {
  font-size: 1.5rem; font-weight: 800; color: #fff;
  margin: 0 0 .375rem; letter-spacing: -.02em; line-height: 1.2;
}
.ka-welcome-sub { font-size: .875rem; color: rgba(255,255,255,.65); margin: 0 0 1.25rem; }
.ka-welcome-actions { display: flex; gap: .75rem; flex-wrap: wrap; }
.ka-welcome-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: .5rem 1.125rem; border-radius: 8px; font-size: .8125rem;
  font-weight: 600; text-decoration: none; transition: all .2s;
  white-space: nowrap;
}
.ka-welcome-btn-primary {
  background: var(--ka-primary, #6dabcf); color: #fff;
  box-shadow: 0 4px 14px rgba(109,171,207,.4);
}
.ka-welcome-btn-primary:hover { background: #5a9ec1; color: #fff; transform: translateY(-1px); }
.ka-welcome-btn-ghost {
  background: rgba(255,255,255,.1); color: rgba(255,255,255,.85);
  border: 1px solid rgba(255,255,255,.2);
}
.ka-welcome-btn-ghost:hover { background: rgba(255,255,255,.18); color: #fff; }
.ka-welcome-graphic {
  display: flex; align-items: flex-end; gap: 6px;
  padding-top: 1.5rem; margin-top: 1.25rem;
  border-top: 1px solid rgba(255,255,255,.1);
}
.ka-welcome-stat { text-align: center; flex: 1; padding-bottom: 1.25rem; }
.ka-welcome-stat-val { font-size: 1.375rem; font-weight: 800; color: #fff; line-height: 1; }
.ka-welcome-stat-lbl { font-size: .6875rem; color: rgba(255,255,255,.5); margin-top: 4px; font-weight: 500; }
.ka-welcome-stat + .ka-welcome-stat { border-left: 1px solid rgba(255,255,255,.1); }

/* ── KPI Cards ── */
.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.75rem; }
@media (max-width: 1199.98px) { .kpi-grid { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 575.98px)  { .kpi-grid { grid-template-columns: 1fr; } }

.kpi-card {
  background: #fff; border: 1px solid var(--ka-border, #e2e8f0);
  border-radius: 14px; padding: 1.25rem 1.375rem;
  position: relative; overflow: hidden;
  transition: box-shadow .2s, transform .2s;
}
.kpi-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.07); transform: translateY(-2px); }

.kpi-accent {
  position: absolute; top: 0; left: 0;
  width: 4px; height: 100%; border-radius: 14px 0 0 14px;
}
.kpi-icon-wrap {
  width: 42px; height: 42px; border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: .875rem; flex-shrink: 0;
}
.kpi-icon-wrap svg { width: 20px; height: 20px; }
.kpi-val { font-size: 1.875rem; font-weight: 800; color: var(--ka-text, #1e293b); line-height: 1; margin-bottom: 4px; letter-spacing: -.03em; }
.kpi-label { font-size: .75rem; font-weight: 500; color: var(--ka-text-muted, #64748b); margin-bottom: .625rem; }
.kpi-footer { display: flex; align-items: center; gap: 6px; font-size: .6875rem; font-weight: 600; }
.kpi-trend-up   { color: #22c55e; }
.kpi-trend-down { color: #ef4444; }
.kpi-trend-icon { width: 14px; height: 14px; }
.kpi-sparkline { height: 40px; width: 100%; margin-top: .625rem; }

/* Card colours per type */
.kpi-blue   .kpi-accent { background: #3b82f6; }
.kpi-blue   .kpi-icon-wrap { background: #eff6ff; color: #3b82f6; }
.kpi-green  .kpi-accent { background: #22c55e; }
.kpi-green  .kpi-icon-wrap { background: #f0fdf4; color: #22c55e; }
.kpi-amber  .kpi-accent { background: #f59f00; }
.kpi-amber  .kpi-icon-wrap { background: #fffbeb; color: #f59f00; }
.kpi-sky    .kpi-accent { background: var(--ka-primary, #6dabcf); }
.kpi-sky    .kpi-icon-wrap { background: var(--ka-accent, #e8f4fd); color: var(--ka-primary, #6dabcf); }

/* ── Section grid ── */
.ka-section-grid { display: grid; grid-template-columns: 1fr 340px; gap: 1.25rem; margin-bottom: 1.25rem; }
@media (max-width: 1099.98px) { .ka-section-grid { grid-template-columns: 1fr; } }

.ka-section-grid-wide { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }
@media (max-width: 991.98px) { .ka-section-grid-wide { grid-template-columns: 1fr; } }

/* ── Panel card ── */
.ka-panel {
  background: #fff; border: 1px solid var(--ka-border, #e2e8f0);
  border-radius: 14px; overflow: hidden;
}
.ka-panel-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--ka-border, #e2e8f0);
}
.ka-panel-title { font-size: .9rem; font-weight: 700; color: var(--ka-text, #1e293b); margin: 0; }
.ka-panel-action {
  font-size: .75rem; font-weight: 600; color: var(--ka-primary, #6dabcf);
  text-decoration: none; display: flex; align-items: center; gap: 4px;
}
.ka-panel-action:hover { color: var(--ka-primary-dark, #5a9ec1); }
.ka-panel-body { padding: 1.25rem; }

/* ── Chart container ── */
.ka-chart-wrap { position: relative; width: 100%; }

/* ── Enrollment table ── */
.ka-enroll-table { width: 100%; border-collapse: collapse; }
.ka-enroll-table thead th {
  font-size: .6875rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .07em; color: var(--ka-text-muted, #64748b);
  padding: .625rem 1rem; background: var(--ka-bg, #f8fafc);
  border-bottom: 1px solid var(--ka-border, #e2e8f0);
  white-space: nowrap;
}
.ka-enroll-table tbody tr { border-bottom: 1px solid var(--ka-border, #e2e8f0); transition: background .15s; }
.ka-enroll-table tbody tr:hover { background: var(--ka-accent, #e8f4fd); }
.ka-enroll-table tbody tr:last-child { border-bottom: none; }
.ka-enroll-table tbody td { padding: .75rem 1rem; font-size: .8125rem; color: var(--ka-text, #1e293b); vertical-align: middle; }
.ka-enroll-table .user-cell { display: flex; align-items: center; gap: 10px; }
.ka-enroll-table .user-avatar {
  width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
  background: linear-gradient(135deg, var(--ka-primary, #6dabcf), var(--ka-navy, #1a3a5c));
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-weight: 700; font-size: .625rem;
}
.ka-enroll-table .user-name { font-weight: 600; font-size: .8125rem; line-height: 1.3; }
.ka-enroll-table .user-id { font-size: .6875rem; color: var(--ka-text-muted, #64748b); }

.badge-status {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 9px; border-radius: 20px; font-size: .6875rem; font-weight: 600;
}
.badge-status-active   { background: #ecfdf5; color: #065f46; }
.badge-status-pending  { background: #fffbeb; color: #92400e; }
.badge-status-inactive { background: #fef2f2; color: #991b1b; }
.badge-status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

/* ── Activity feed ── */
.ka-activity-list { list-style: none; margin: 0; padding: 0; }
.ka-activity-item { display: flex; gap: 12px; padding: .875rem 0; border-bottom: 1px solid var(--ka-border, #e2e8f0); }
.ka-activity-item:last-child { border-bottom: none; padding-bottom: 0; }
.ka-activity-icon {
  width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
}
.ka-activity-icon svg { width: 15px; height: 15px; }
.ai-blue   { background: #eff6ff; color: #3b82f6; }
.ai-green  { background: #f0fdf4; color: #22c55e; }
.ai-amber  { background: #fffbeb; color: #f59f00; }
.ai-red    { background: #fef2f2; color: #ef4444; }
.ai-sky    { background: var(--ka-accent, #e8f4fd); color: var(--ka-primary, #6dabcf); }
.ka-activity-body { flex: 1; min-width: 0; }
.ka-activity-text { font-size: .8125rem; color: var(--ka-text, #1e293b); line-height: 1.4; margin: 0; }
.ka-activity-text strong { font-weight: 600; }
.ka-activity-meta { display: flex; align-items: center; gap: 8px; margin-top: 3px; }
.ka-activity-time { font-size: .6875rem; color: var(--ka-text-muted, #64748b); }

/* ── Quick-actions ── */
.ka-quick-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: .75rem; }
.ka-quick-btn {
  display: flex; align-items: center; gap: 10px;
  padding: .875rem 1rem; border-radius: 10px;
  background: var(--ka-bg, #f8fafc); border: 1px solid var(--ka-border, #e2e8f0);
  text-decoration: none; transition: all .18s; cursor: pointer;
}
.ka-quick-btn:hover { background: var(--ka-accent, #e8f4fd); border-color: var(--ka-primary, #6dabcf); transform: translateY(-1px); }
.ka-quick-icon {
  width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
}
.ka-quick-icon svg { width: 16px; height: 16px; }
.ka-quick-text-title { font-size: .8125rem; font-weight: 600; color: var(--ka-text, #1e293b); line-height: 1.2; }
.ka-quick-text-sub   { font-size: .6875rem; color: var(--ka-text-muted, #64748b); }

/* ── Course progress list ── */
.ka-course-list { list-style: none; margin: 0; padding: 0; }
.ka-course-item { display: flex; align-items: center; gap: 12px; padding: .75rem 0; border-bottom: 1px solid var(--ka-border, #e2e8f0); }
.ka-course-item:last-child { border-bottom: none; }
.ka-course-thumb {
  width: 40px; height: 40px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: .75rem; font-weight: 700; color: #fff;
}
.ka-course-meta { flex: 1; min-width: 0; }
.ka-course-name { font-size: .8125rem; font-weight: 600; color: var(--ka-text, #1e293b); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; }
.ka-course-pct  { font-size: .6875rem; color: var(--ka-text-muted, #64748b); }
.ka-mini-bar { height: 4px; background: var(--ka-border, #e2e8f0); border-radius: 2px; overflow: hidden; margin-top: 4px; }
.ka-mini-fill { height: 100%; border-radius: 2px; background: linear-gradient(90deg, var(--ka-primary, #6dabcf), #5a9ec1); }
.ka-course-enrolled { font-size: .75rem; font-weight: 600; color: var(--ka-text-muted, #64748b); white-space: nowrap; }

/* ── Donut legend ── */
.ka-donut-wrap { display: flex; align-items: center; gap: 1.5rem; }
.ka-donut-legend { flex: 1; display: flex; flex-direction: column; gap: .625rem; }
.ka-legend-item { display: flex; align-items: center; gap: 8px; font-size: .8125rem; }
.ka-legend-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
.ka-legend-label { flex: 1; color: var(--ka-text-muted, #64748b); font-weight: 500; }
.ka-legend-val   { font-weight: 700; color: var(--ka-text, #1e293b); }

/* ── Empty state ── */
.ka-empty { text-align: center; padding: 2rem 1rem; color: var(--ka-text-muted, #64748b); }
.ka-empty svg { width: 40px; height: 40px; margin-bottom: .75rem; opacity: .35; }
.ka-empty p { font-size: .8125rem; margin: 0; }

/* ── Responsive ── */
@media (max-width: 767.98px) {
  .ka-welcome-title { font-size: 1.25rem; }
  .ka-welcome-graphic { display: none; }
}
</style>

<!-- ══ Welcome Banner ══════════════════════════════════════ -->
<div class="ka-welcome animate__animated animate__fadeIn animate__fast">
  <div class="ka-welcome-body">
    <p class="ka-welcome-eyebrow">Admin Control Panel</p>
    <h2 class="ka-welcome-title">Good <?= (date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening')) ?>, <?= htmlspecialchars(explode(' ', trim(is_object($user ?? null) ? ($user->fullname ?? 'Admin') : 'Admin'))[0]) ?> 👋</h2>
    <p class="ka-welcome-sub">Here's what's happening at KABAGA Academy today — <?= date('l, F j, Y') ?></p>
    <div class="ka-welcome-actions">
      <a href="<?= base_url('manage_courses/create') ?>" class="ka-welcome-btn ka-welcome-btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Course
      </a>
      <a href="<?= base_url('users') ?>" class="ka-welcome-btn ka-welcome-btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Manage Users
      </a>
      <a href="<?= base_url('reports') ?>" class="ka-welcome-btn ka-welcome-btn-ghost">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Reports
      </a>
    </div>
  </div>
  <div class="ka-welcome-graphic">
    <div class="ka-welcome-stat">
      <div class="ka-welcome-stat-val"><?= number_format($total_users) ?></div>
      <div class="ka-welcome-stat-lbl">Total Users</div>
    </div>
    <div class="ka-welcome-stat">
      <div class="ka-welcome-stat-val"><?= number_format($active_users) ?></div>
      <div class="ka-welcome-stat-lbl">Active Users</div>
    </div>
    <div class="ka-welcome-stat">
      <div class="ka-welcome-stat-val"><?= number_format($total_courses) ?></div>
      <div class="ka-welcome-stat-lbl">Courses</div>
    </div>
    <div class="ka-welcome-stat">
      <div class="ka-welcome-stat-val">94%</div>
      <div class="ka-welcome-stat-lbl">Completion Rate</div>
    </div>
  </div>
</div>

<!-- ══ KPI Cards ════════════════════════════════════════════ -->
<div class="kpi-grid">

  <!-- Total Users -->
  <div class="kpi-card kpi-blue animate__animated animate__fadeInUp animate__fast">
    <div class="kpi-accent"></div>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;">
      <div class="kpi-icon-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <span style="font-size:.6875rem;font-weight:600;padding:3px 8px;background:#eff6ff;color:#3b82f6;border-radius:20px;">Users</span>
    </div>
    <div class="kpi-val"><?= number_format($total_users) ?></div>
    <div class="kpi-label">Total Enrolled Users</div>
    <div class="kpi-footer">
      <svg class="kpi-trend-icon kpi-trend-up" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
      <span class="kpi-trend-up">+12% this month</span>
    </div>
    <div class="kpi-sparkline" id="spark-users"></div>
  </div>

  <!-- Active Users -->
  <div class="kpi-card kpi-green animate__animated animate__fadeInUp animate__fast" style="animation-delay:.05s">
    <div class="kpi-accent"></div>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;">
      <div class="kpi-icon-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
      </div>
      <span style="font-size:.6875rem;font-weight:600;padding:3px 8px;background:#f0fdf4;color:#22c55e;border-radius:20px;">Active</span>
    </div>
    <div class="kpi-val"><?= number_format($active_users) ?></div>
    <div class="kpi-label">Active Learners</div>
    <div class="kpi-footer">
      <svg class="kpi-trend-icon kpi-trend-up" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
      <span class="kpi-trend-up">+8% vs last week</span>
    </div>
    <div class="kpi-sparkline" id="spark-active"></div>
  </div>

  <!-- Courses -->
  <div class="kpi-card kpi-sky animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s">
    <div class="kpi-accent"></div>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;">
      <div class="kpi-icon-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
      </div>
      <span style="font-size:.6875rem;font-weight:600;padding:3px 8px;background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);border-radius:20px;">Courses</span>
    </div>
    <div class="kpi-val"><?= number_format($total_courses) ?></div>
    <div class="kpi-label">Published Courses</div>
    <div class="kpi-footer">
      <svg class="kpi-trend-icon kpi-trend-up" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
      <span class="kpi-trend-up">3 added this month</span>
    </div>
    <div class="kpi-sparkline" id="spark-courses"></div>
  </div>

  <!-- Certificates -->
  <div class="kpi-card kpi-amber animate__animated animate__fadeInUp animate__fast" style="animation-delay:.15s">
    <div class="kpi-accent"></div>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;">
      <div class="kpi-icon-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
      </div>
      <span style="font-size:.6875rem;font-weight:600;padding:3px 8px;background:#fffbeb;color:#f59f00;border-radius:20px;">Certs</span>
    </div>
    <div class="kpi-val">320</div>
    <div class="kpi-label">Certificates Issued</div>
    <div class="kpi-footer">
      <svg class="kpi-trend-icon kpi-trend-up" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
      <span class="kpi-trend-up">+24 this week</span>
    </div>
    <div class="kpi-sparkline" id="spark-certs"></div>
  </div>

</div><!-- /kpi-grid -->

<!-- ══ Main Section ══════════════════════════════════════════ -->
<div class="ka-section-grid">

  <!-- Enrollment Chart -->
  <div class="ka-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.2s">
    <div class="ka-panel-header">
      <h3 class="ka-panel-title">Enrollment Overview</h3>
      <div style="display:flex;align-items:center;gap:.75rem;">
        <div style="display:flex;gap:.5rem;">
          <button class="chart-period active" data-period="7d" style="font-size:.6875rem;font-weight:600;padding:4px 10px;border-radius:6px;border:1px solid var(--ka-border);background:var(--ka-navy);color:#fff;cursor:pointer;transition:all .15s;">7D</button>
          <button class="chart-period" data-period="30d" style="font-size:.6875rem;font-weight:600;padding:4px 10px;border-radius:6px;border:1px solid var(--ka-border);background:transparent;color:var(--ka-text-muted);cursor:pointer;transition:all .15s;">30D</button>
          <button class="chart-period" data-period="90d" style="font-size:.6875rem;font-weight:600;padding:4px 10px;border-radius:6px;border:1px solid var(--ka-border);background:transparent;color:var(--ka-text-muted);cursor:pointer;transition:all .15s;">90D</button>
        </div>
        <a href="<?= base_url('reports') ?>" class="ka-panel-action">
          Full Report
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      </div>
    </div>
    <div class="ka-panel-body">
      <div id="chart-enrollment" style="height:240px;"></div>
    </div>
  </div>

  <!-- Right column: Quick actions + Activity -->
  <div style="display:flex;flex-direction:column;gap:1.25rem;">

    <!-- Quick Actions -->
    <div class="ka-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.25s;">
      <div class="ka-panel-header">
        <h3 class="ka-panel-title">Quick Actions</h3>
      </div>
      <div class="ka-panel-body">
        <div class="ka-quick-grid">
          <a href="<?= base_url('manage_courses/create') ?>" class="ka-quick-btn">
            <div class="ka-quick-icon" style="background:#eff6ff;color:#3b82f6;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </div>
            <div>
              <div class="ka-quick-text-title">Add Course</div>
              <div class="ka-quick-text-sub">Create new content</div>
            </div>
          </a>
          <a href="<?= base_url('users') ?>" class="ka-quick-btn">
            <div class="ka-quick-icon" style="background:#f0fdf4;color:#22c55e;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            </div>
            <div>
              <div class="ka-quick-text-title">Add User</div>
              <div class="ka-quick-text-sub">Enroll learner</div>
            </div>
          </a>
          <a href="<?= base_url('announcements') ?>" class="ka-quick-btn">
            <div class="ka-quick-icon" style="background:#fffbeb;color:#f59f00;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <div>
              <div class="ka-quick-text-title">Announce</div>
              <div class="ka-quick-text-sub">Send notification</div>
            </div>
          </a>
          <a href="<?= base_url('reports') ?>" class="ka-quick-btn">
            <div class="ka-quick-icon" style="background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div>
              <div class="ka-quick-text-title">Reports</div>
              <div class="ka-quick-text-sub">View analytics</div>
            </div>
          </a>
        </div>
      </div>
    </div>

    <!-- User Status Donut -->
    <div class="ka-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.3s;">
      <div class="ka-panel-header">
        <h3 class="ka-panel-title">User Status</h3>
      </div>
      <div class="ka-panel-body">
        <div class="ka-donut-wrap">
          <div style="width:110px;height:110px;flex-shrink:0;">
            <canvas id="donut-users" width="110" height="110"></canvas>
          </div>
          <div class="ka-donut-legend">
            <div class="ka-legend-item">
              <div class="ka-legend-dot" style="background:#22c55e;"></div>
              <span class="ka-legend-label">Active</span>
              <span class="ka-legend-val"><?= $active_users ?></span>
            </div>
            <div class="ka-legend-item">
              <div class="ka-legend-dot" style="background:#f59f00;"></div>
              <span class="ka-legend-label">Inactive</span>
              <span class="ka-legend-val"><?= $inactive ?></span>
            </div>
            <div class="ka-legend-item">
              <div class="ka-legend-dot" style="background:#e2e8f0;"></div>
              <span class="ka-legend-label">Pending</span>
              <span class="ka-legend-val"><?= max(0, $total_users - $active_users - $inactive) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /right col -->

</div><!-- /ka-section-grid -->

<!-- ══ Lower Section ════════════════════════════════════════ -->
<div class="ka-section-grid-wide">

  <!-- Latest Enrollments -->
  <div class="ka-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.35s;">
    <div class="ka-panel-header">
      <h3 class="ka-panel-title">Latest Enrollments</h3>
      <a href="<?= base_url('users') ?>" class="ka-panel-action">
        View all
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </a>
    </div>
    <div style="overflow-x:auto;">
      <table class="ka-enroll-table">
        <thead>
          <tr>
            <th>Learner</th>
            <th>Course</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div class="user-cell">
                <div class="user-avatar">JD</div>
                <div>
                  <div class="user-name">Juan Dela Cruz</div>
                  <div class="user-id">EMP-00121</div>
                </div>
              </div>
            </td>
            <td>Basic Infection Control</td>
            <td><span class="badge-status badge-status-active"><span class="badge-status-dot"></span>Active</span></td>
            <td style="color:var(--ka-text-muted);font-size:.75rem;"><?= date('M j, Y') ?></td>
          </tr>
          <tr>
            <td>
              <div class="user-cell">
                <div class="user-avatar" style="background:linear-gradient(135deg,#f59f00,#1a3a5c);">MS</div>
                <div>
                  <div class="user-name">Maria Santos</div>
                  <div class="user-id">EMP-00098</div>
                </div>
              </div>
            </td>
            <td>Patient Safety Module</td>
            <td><span class="badge-status badge-status-pending"><span class="badge-status-dot"></span>Pending</span></td>
            <td style="color:var(--ka-text-muted);font-size:.75rem;"><?= date('M j, Y', strtotime('-1 day')) ?></td>
          </tr>
          <tr>
            <td>
              <div class="user-cell">
                <div class="user-avatar" style="background:linear-gradient(135deg,#22c55e,#1a3a5c);">AL</div>
                <div>
                  <div class="user-name">Ana Lim</div>
                  <div class="user-id">EMP-00205</div>
                </div>
              </div>
            </td>
            <td>Respiratory Care Basics</td>
            <td><span class="badge-status badge-status-active"><span class="badge-status-dot"></span>Active</span></td>
            <td style="color:var(--ka-text-muted);font-size:.75rem;"><?= date('M j, Y', strtotime('-2 days')) ?></td>
          </tr>
          <tr>
            <td>
              <div class="user-cell">
                <div class="user-avatar" style="background:linear-gradient(135deg,#3b82f6,#1a3a5c);">RR</div>
                <div>
                  <div class="user-name">Roberto Reyes</div>
                  <div class="user-id">EMP-00177</div>
                </div>
              </div>
            </td>
            <td>Fire Safety &amp; Evacuation</td>
            <td><span class="badge-status badge-status-inactive"><span class="badge-status-dot"></span>Inactive</span></td>
            <td style="color:var(--ka-text-muted);font-size:.75rem;"><?= date('M j, Y', strtotime('-3 days')) ?></td>
          </tr>
          <tr>
            <td>
              <div class="user-cell">
                <div class="user-avatar" style="background:linear-gradient(135deg,#6dabcf,#254d75);">CE</div>
                <div>
                  <div class="user-name">Carla Espiritu</div>
                  <div class="user-id">EMP-00310</div>
                </div>
              </div>
            </td>
            <td>Data Privacy Compliance</td>
            <td><span class="badge-status badge-status-active"><span class="badge-status-dot"></span>Active</span></td>
            <td style="color:var(--ka-text-muted);font-size:.75rem;"><?= date('M j, Y', strtotime('-4 days')) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Right: Activity Feed -->
  <div style="display:flex;flex-direction:column;gap:1.25rem;">

    <!-- Activity -->
    <div class="ka-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.4s;">
      <div class="ka-panel-header">
        <h3 class="ka-panel-title">Recent Activity</h3>
        <a href="<?= base_url('reports') ?>" class="ka-panel-action">See all</a>
      </div>
      <div class="ka-panel-body" style="padding-top:.25rem;padding-bottom:.25rem;">
        <ul class="ka-activity-list">
          <li class="ka-activity-item">
            <div class="ka-activity-icon ai-green">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
            </div>
            <div class="ka-activity-body">
              <p class="ka-activity-text"><strong>Ana Lim</strong> earned a certificate in Patient Safety</p>
              <div class="ka-activity-meta"><span class="ka-activity-time">Just now</span></div>
            </div>
          </li>
          <li class="ka-activity-item">
            <div class="ka-activity-icon ai-blue">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <div class="ka-activity-body">
              <p class="ka-activity-text">New course <strong>Respiratory Care</strong> was published</p>
              <div class="ka-activity-meta"><span class="ka-activity-time">2 hrs ago</span></div>
            </div>
          </li>
          <li class="ka-activity-item">
            <div class="ka-activity-icon ai-amber">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <div class="ka-activity-body">
              <p class="ka-activity-text">Mandatory training reminder sent to <strong>48 users</strong></p>
              <div class="ka-activity-meta"><span class="ka-activity-time">5 hrs ago</span></div>
            </div>
          </li>
          <li class="ka-activity-item">
            <div class="ka-activity-icon ai-sky">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            </div>
            <div class="ka-activity-body">
              <p class="ka-activity-text"><strong>12 new employees</strong> enrolled this week</p>
              <div class="ka-activity-meta"><span class="ka-activity-time">Yesterday</span></div>
            </div>
          </li>
          <li class="ka-activity-item">
            <div class="ka-activity-icon ai-red">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="ka-activity-body">
              <p class="ka-activity-text"><strong>3 assessments</strong> flagged for review</p>
              <div class="ka-activity-meta"><span class="ka-activity-time">Yesterday</span></div>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Top Courses -->
    <div class="ka-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.45s;">
      <div class="ka-panel-header">
        <h3 class="ka-panel-title">Top Courses</h3>
        <a href="<?= base_url('manage_courses') ?>" class="ka-panel-action">Manage</a>
      </div>
      <div class="ka-panel-body" style="padding-top:.25rem;padding-bottom:.25rem;">
        <ul class="ka-course-list">
          <li class="ka-course-item">
            <div class="ka-course-thumb" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">IC</div>
            <div class="ka-course-meta">
              <div class="ka-course-name">Basic Infection Control</div>
              <div class="ka-mini-bar"><div class="ka-mini-fill" style="width:88%"></div></div>
              <div class="ka-course-pct">88% avg completion</div>
            </div>
            <div class="ka-course-enrolled">45 learners</div>
          </li>
          <li class="ka-course-item">
            <div class="ka-course-thumb" style="background:linear-gradient(135deg,#22c55e,#15803d);">PS</div>
            <div class="ka-course-meta">
              <div class="ka-course-name">Patient Safety Module</div>
              <div class="ka-mini-bar"><div class="ka-mini-fill" style="width:75%"></div></div>
              <div class="ka-course-pct">75% avg completion</div>
            </div>
            <div class="ka-course-enrolled">38 learners</div>
          </li>
          <li class="ka-course-item">
            <div class="ka-course-thumb" style="background:linear-gradient(135deg,var(--ka-primary),var(--ka-navy));">RC</div>
            <div class="ka-course-meta">
              <div class="ka-course-name">Respiratory Care Basics</div>
              <div class="ka-mini-bar"><div class="ka-mini-fill" style="width:62%"></div></div>
              <div class="ka-course-pct">62% avg completion</div>
            </div>
            <div class="ka-course-enrolled">29 learners</div>
          </li>
          <li class="ka-course-item">
            <div class="ka-course-thumb" style="background:linear-gradient(135deg,#f59f00,#b45309);">FS</div>
            <div class="ka-course-meta">
              <div class="ka-course-name">Fire Safety &amp; Evacuation</div>
              <div class="ka-mini-bar"><div class="ka-mini-fill" style="width:55%"></div></div>
              <div class="ka-course-pct">55% avg completion</div>
            </div>
            <div class="ka-course-enrolled">22 learners</div>
          </li>
        </ul>
      </div>
    </div>

  </div>

</div><!-- /ka-section-grid-wide -->

<!-- ══ Chart.js + Donut ═════════════════════════════════════ -->
<script src="<?= base_url('assets/js/chart.umd.min.js'); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

  const primary  = '#6dabcf';
  const navy     = '#1a3a5c';
  const green    = '#22c55e';
  const amber    = '#f59f00';
  const blue     = '#3b82f6';
  const border   = '#e2e8f0';

  // ── Sparkline helper ──────────────────────────────────────
  function sparkline(id, data, color) {
    const ctx = document.getElementById(id);
    if (!ctx) return;
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: data.map((_,i) => i),
        datasets: [{ data, borderColor: color, borderWidth: 2,
          pointRadius: 0, tension: .4, fill: true,
          backgroundColor: color + '22' }]
      },
      options: { responsive: true, animation: false,
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false } }
      }
    });
  }

  sparkline('spark-users',  [30,42,38,55,48,62,71,68,80,90,85,<?= $total_users ?>], blue);
  sparkline('spark-active', [22,28,25,34,31,40,45,42,52,58,61,<?= $active_users ?>], green);
  sparkline('spark-courses',[1,2,2,3,3,4,4,5,6,6,7,<?= $total_courses ?>], primary);
  sparkline('spark-certs',  [18,24,30,28,36,40,48,55,62,70,80,320], amber);

  // ── Enrollment Line Chart ─────────────────────────────────
  const enrollData = {
    '7d':  { labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
             enrolled: [8,12,6,15,10,4,9], completed: [3,5,4,8,6,2,5] },
    '30d': { labels: Array.from({length:30}, (_,i) => `D${i+1}`),
             enrolled: [5,7,9,6,11,8,14,10,7,12,9,15,11,8,13,10,7,16,12,9,14,11,8,17,13,10,15,12,9,18],
             completed:[2,3,4,3,5,4,6,5,3,6,4,7,5,4,6,5,3,7,6,4,6,5,4,8,6,5,7,6,4,8] },
    '90d': { labels: Array.from({length:12}, (_,i) => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][i]),
             enrolled: [40,55,48,70,65,80,75,90,85,100,95,110],
             completed:[15,22,20,30,28,36,32,40,38,48,44,52] }
  };

  const enrollCtx = document.getElementById('chart-enrollment').getContext('2d');
  const enrollChart = new Chart(enrollCtx, {
    type: 'bar',
    data: {
      labels: enrollData['7d'].labels,
      datasets: [
        { label: 'Enrolled', data: enrollData['7d'].enrolled,
          backgroundColor: primary + 'cc', borderRadius: 6, borderSkipped: false },
        { label: 'Completed', type: 'line', data: enrollData['7d'].completed,
          borderColor: navy, borderWidth: 2.5, pointRadius: 4,
          pointBackgroundColor: navy, tension: .4,
          fill: false, yAxisID: 'y' }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { position: 'top', align: 'end',
        labels: { boxWidth: 10, boxHeight: 10, borderRadius: 3, usePointStyle: true,
          font: { size: 11, weight: '600' } } },
        tooltip: { mode: 'index', intersect: false,
          backgroundColor: navy, titleColor: '#fff', bodyColor: 'rgba(255,255,255,.8)',
          cornerRadius: 8, padding: 10 }
      },
      scales: {
        x: { grid: { display: false }, border: { display: false },
          ticks: { font: { size: 11 }, color: '#94a3b8' } },
        y: { grid: { color: border }, border: { display: false, dash: [4,4] },
          ticks: { font: { size: 11 }, color: '#94a3b8' } }
      }
    }
  });

  // Period switch
  document.querySelectorAll('.chart-period').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.chart-period').forEach(b => {
        b.style.background = 'transparent'; b.style.color = '#64748b'; b.style.borderColor = border;
      });
      this.style.background = navy; this.style.color = '#fff'; this.style.borderColor = navy;
      const period = this.dataset.period;
      const d = enrollData[period];
      enrollChart.data.labels = d.labels;
      enrollChart.data.datasets[0].data = d.enrolled;
      enrollChart.data.datasets[1].data = d.completed;
      enrollChart.update();
    });
  });

  // ── Donut ─────────────────────────────────────────────────
  const donutCtx = document.getElementById('donut-users');
  if (donutCtx) {
    const active   = <?= intval($active_users) ?>;
    const inactive = <?= intval($inactive) ?>;
    const pending  = Math.max(0, <?= intval($total_users) ?> - active - inactive);
    new Chart(donutCtx, {
      type: 'doughnut',
      data: {
        datasets: [{
          data: [active, inactive, pending || 1],
          backgroundColor: [green, amber, border],
          borderWidth: 0,
          hoverOffset: 4
        }]
      },
      options: {
        cutout: '72%',
        plugins: { legend: { display: false }, tooltip: { enabled: true } },
        animation: { duration: 800 }
      }
    });
  }

});
</script>