<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// Data from My_courses controller (admin)
$courses      = $courses      ?? [];
$categories   = $categories   ?? [];
$total        = $total_courses ?? count($courses);
$keyword      = $keyword       ?? '';
$filter_cat   = $filter_cat    ?? '';
$filter_status= $filter_status ?? '';

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

<!-- ============================================================
     KABAGA ACADEMY — My Courses (Admin)
============================================================ -->
<style>
/* ── Page header bar ── */
.mc-topbar {
  display:flex;align-items:center;justify-content:space-between;
  gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;
}
.mc-topbar-left h2 {
  font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);
  margin:0 0 2px;letter-spacing:-.02em;
}
.mc-topbar-left p { font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0; }
.mc-topbar-right { display:flex;align-items:center;gap:.625rem;flex-wrap:wrap; }

.mc-btn {
  display:inline-flex;align-items:center;gap:6px;
  padding:.5rem 1rem;border-radius:8px;font-size:.8125rem;
  font-weight:600;text-decoration:none;border:none;cursor:pointer;
  transition:all .18s;white-space:nowrap;
}
.mc-btn-primary {
  background:var(--ka-navy,#1a3a5c);color:#fff;
}
.mc-btn-primary:hover { background:#254d75;color:#fff;transform:translateY(-1px); }
.mc-btn-outline {
  background:#fff;color:var(--ka-text,#1e293b);
  border:1.5px solid var(--ka-border,#e2e8f0);
}
.mc-btn-outline:hover { border-color:var(--ka-primary,#6dabcf);color:var(--ka-primary,#6dabcf); }

/* ── KPI strip ── */
.mc-kpi-row { display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem; }
@media(max-width:1199.98px){.mc-kpi-row{grid-template-columns:repeat(2,1fr);}}
@media(max-width:575.98px){.mc-kpi-row{grid-template-columns:1fr;}}

.mc-kpi {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:12px;padding:1rem 1.125rem;
  display:flex;align-items:center;gap:.875rem;
}
.mc-kpi-icon {
  width:40px;height:40px;border-radius:10px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
}
.mc-kpi-icon svg { width:18px;height:18px; }
.mc-kpi-val { font-size:1.5rem;font-weight:800;color:var(--ka-text,#1e293b);line-height:1;letter-spacing:-.02em; }
.mc-kpi-lbl { font-size:.6875rem;color:var(--ka-text-muted,#64748b);font-weight:500;margin-top:2px; }

/* ── Filters bar ── */
.mc-filters {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:12px;padding:.875rem 1rem;
  display:flex;align-items:center;gap:.75rem;
  flex-wrap:wrap;margin-bottom:1.25rem;
}
.mc-search-wrap { position:relative;flex:1;min-width:200px; }
.mc-search-icon {
  position:absolute;left:.75rem;top:50%;transform:translateY(-50%);
  width:15px;height:15px;color:var(--ka-text-muted,#64748b);pointer-events:none;
}
.mc-search-input {
  width:100%;height:36px;padding:0 .75rem 0 2.25rem;
  border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;
  font-size:.8125rem;color:var(--ka-text,#1e293b);
  background:var(--ka-bg,#f8fafc);outline:none;transition:all .2s;
}
.mc-search-input:focus { border-color:var(--ka-primary,#6dabcf);background:#fff;box-shadow:0 0 0 3px rgba(109,171,207,.15); }
.mc-select {
  height:36px;padding:0 .75rem;border:1.5px solid var(--ka-border,#e2e8f0);
  border-radius:8px;font-size:.8125rem;color:var(--ka-text,#1e293b);
  background:var(--ka-bg,#f8fafc);outline:none;cursor:pointer;transition:all .2s;
}
.mc-select:focus { border-color:var(--ka-primary,#6dabcf); }
.mc-filter-count {
  font-size:.75rem;font-weight:600;color:var(--ka-text-muted,#64748b);
  white-space:nowrap;
}

/* ── Course grid ── */
.mc-grid {
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
  gap:1.125rem;
  margin-bottom:1.5rem;
}

.mc-card {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;overflow:hidden;
  transition:box-shadow .2s,transform .2s;
  display:flex;flex-direction:column;
}
.mc-card:hover { box-shadow:0 8px 28px rgba(0,0,0,.09);transform:translateY(-3px); }

.mc-card-thumb {
  height:130px;position:relative;overflow:hidden;
  display:flex;align-items:center;justify-content:center;
}
.mc-card-thumb-icon { opacity:.25; }
.mc-card-thumb-icon svg { width:56px;height:56px; }
.mc-card-thumb-badge {
  position:absolute;top:10px;left:10px;
  font-size:.625rem;font-weight:700;padding:3px 8px;
  border-radius:20px;backdrop-filter:blur(6px);
}
.mc-card-thumb-badge-published { background:rgba(34,197,94,.9);color:#fff; }
.mc-card-thumb-badge-archived  { background:rgba(100,116,139,.8);color:#fff; }
.mc-card-thumb-menu {
  position:absolute;top:10px;right:10px;
}
.mc-card-thumb-menu-btn {
  width:28px;height:28px;border-radius:6px;
  background:rgba(0,0,0,.35);border:none;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  color:#fff;backdrop-filter:blur(4px);transition:background .15s;
}
.mc-card-thumb-menu-btn:hover { background:rgba(0,0,0,.55); }
.mc-card-thumb-menu-btn svg { width:14px;height:14px; }

.mc-card-body { padding:1rem;flex:1;display:flex;flex-direction:column; }
.mc-card-category {
  font-size:.625rem;font-weight:700;text-transform:uppercase;
  letter-spacing:.08em;color:var(--ka-primary,#6dabcf);margin-bottom:.375rem;
}
.mc-card-title {
  font-size:.9rem;font-weight:700;color:var(--ka-text,#1e293b);
  line-height:1.35;margin-bottom:.625rem;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.mc-card-meta {
  display:flex;align-items:center;gap:.875rem;
  margin-bottom:.75rem;flex-wrap:wrap;
}
.mc-card-meta-item {
  display:flex;align-items:center;gap:4px;
  font-size:.6875rem;color:var(--ka-text-muted,#64748b);font-weight:500;
}
.mc-card-meta-item svg { width:13px;height:13px;flex-shrink:0; }

.mc-card-progress { margin-bottom:.875rem; }
.mc-card-progress-top {
  display:flex;justify-content:space-between;align-items:center;
  margin-bottom:4px;
}
.mc-card-progress-lbl { font-size:.6875rem;color:var(--ka-text-muted,#64748b);font-weight:500; }
.mc-card-progress-pct { font-size:.6875rem;font-weight:700;color:var(--ka-primary,#6dabcf); }
.mc-prog-bar { height:5px;background:var(--ka-border,#e2e8f0);border-radius:3px;overflow:hidden; }
.mc-prog-fill { height:100%;border-radius:3px;background:linear-gradient(90deg,var(--ka-primary,#6dabcf),#5a9ec1); }

.mc-card-footer {
  margin-top:auto;padding-top:.75rem;
  border-top:1px solid var(--ka-border,#e2e8f0);
  display:flex;align-items:center;gap:.5rem;
}
.mc-card-btn {
  flex:1;padding:.4375rem .5rem;border-radius:7px;
  font-size:.75rem;font-weight:600;text-decoration:none;
  text-align:center;transition:all .15s;border:none;cursor:pointer;
}
.mc-card-btn-primary { background:var(--ka-accent,#e8f4fd);color:var(--ka-primary-deep,#4a8eb0); }
.mc-card-btn-primary:hover { background:var(--ka-primary,#6dabcf);color:#fff; }
.mc-card-btn-danger  { background:#fef2f2;color:#dc2626; }
.mc-card-btn-danger:hover  { background:#dc2626;color:#fff; }

/* ── Table view ── */
.mc-table-wrap { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;overflow:hidden;margin-bottom:1.5rem; }
.mc-table { width:100%;border-collapse:collapse; }
.mc-table thead th {
  font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;
  color:var(--ka-text-muted,#64748b);padding:.75rem 1rem;
  background:var(--ka-bg,#f8fafc);border-bottom:1px solid var(--ka-border,#e2e8f0);
  white-space:nowrap;
}
.mc-table thead th:first-child { padding-left:1.25rem; }
.mc-table tbody tr { border-bottom:1px solid var(--ka-border,#e2e8f0);transition:background .15s; }
.mc-table tbody tr:hover { background:var(--ka-accent,#e8f4fd); }
.mc-table tbody tr:last-child { border-bottom:none; }
.mc-table tbody td { padding:.8125rem 1rem;font-size:.8125rem;color:var(--ka-text,#1e293b);vertical-align:middle; }
.mc-table tbody td:first-child { padding-left:1.25rem; }

.mc-tbl-course { display:flex;align-items:center;gap:10px; }
.mc-tbl-thumb {
  width:38px;height:38px;border-radius:9px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-weight:700;font-size:.6875rem;
}
.mc-tbl-title { font-weight:600;font-size:.8125rem;color:var(--ka-text,#1e293b);line-height:1.3; }
.mc-tbl-sub   { font-size:.6875rem;color:var(--ka-text-muted,#64748b); }

.mc-status-badge {
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 9px;border-radius:20px;font-size:.6875rem;font-weight:600;
}
.mc-status-pub  { background:#ecfdf5;color:#065f46; }
.mc-status-arch { background:#f1f5f9;color:#64748b; }

.mc-mini-bar { height:5px;background:var(--ka-border,#e2e8f0);border-radius:3px;overflow:hidden;min-width:80px; }
.mc-mini-fill{ height:100%;border-radius:3px;background:linear-gradient(90deg,var(--ka-primary,#6dabcf),#5a9ec1); }

.mc-action-row { display:flex;align-items:center;gap:.5rem; }
.mc-action-link {
  font-size:.6875rem;font-weight:600;padding:4px 10px;border-radius:6px;
  text-decoration:none;transition:all .15s;white-space:nowrap;
}
.mc-action-edit   { background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf); }
.mc-action-edit:hover   { background:var(--ka-primary,#6dabcf);color:#fff; }
.mc-action-delete { background:#fef2f2;color:#dc2626; }
.mc-action-delete:hover { background:#dc2626;color:#fff; }

/* ── View toggle ── */
.mc-view-toggle { display:flex;gap:4px; }
.mc-toggle-btn {
  width:32px;height:32px;border-radius:7px;border:1.5px solid var(--ka-border,#e2e8f0);
  background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;
  color:var(--ka-text-muted,#64748b);transition:all .15s;
}
.mc-toggle-btn.active,
.mc-toggle-btn:hover { background:var(--ka-navy,#1a3a5c);border-color:var(--ka-navy,#1a3a5c);color:#fff; }
.mc-toggle-btn svg { width:15px;height:15px; }

/* ── Empty state ── */
.mc-empty {
  text-align:center;padding:3rem 1rem;
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;
}
.mc-empty-icon { width:56px;height:56px;margin:0 auto .875rem;opacity:.25; }
.mc-empty h4 { font-size:1rem;font-weight:700;color:var(--ka-text,#1e293b);margin-bottom:.375rem; }
.mc-empty p  { font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin-bottom:1.25rem; }

/* ── Pagination ── */
.mc-pagination {
  display:flex;align-items:center;justify-content:space-between;
  flex-wrap:wrap;gap:.75rem;
}
.mc-pagination-info { font-size:.8125rem;color:var(--ka-text-muted,#64748b); }
.mc-pagination-btns { display:flex;gap:4px; }
.mc-page-btn {
  min-width:32px;height:32px;padding:0 8px;border-radius:7px;
  border:1.5px solid var(--ka-border,#e2e8f0);background:#fff;
  font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);
  cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;
}
.mc-page-btn:hover   { border-color:var(--ka-primary,#6dabcf);color:var(--ka-primary,#6dabcf); }
.mc-page-btn.active  { background:var(--ka-navy,#1a3a5c);border-color:var(--ka-navy,#1a3a5c);color:#fff; }
.mc-page-btn:disabled{ opacity:.4;cursor:not-allowed; }
</style>

<!-- ══ Top Bar ═══════════════════════════════════════════════ -->
<div class="mc-topbar animate__animated animate__fadeIn animate__fast">
  <div class="mc-topbar-left">
    <h2>Course Management</h2>
    <p>Manage, publish and archive all courses across KABAGA Academy</p>
  </div>
  <div class="mc-topbar-right">
    <div class="mc-view-toggle" id="mcViewToggle">
      <button class="mc-toggle-btn active" data-view="grid" title="Grid view">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
      </button>
      <button class="mc-toggle-btn" data-view="table" title="Table view">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
    <a href="<?= base_url('manage_courses/create') ?>" class="mc-btn mc-btn-primary">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Course
    </a>
  </div>
</div>

<!-- ══ KPI Strip ════════════════════════════════════════════ -->
<div class="mc-kpi-row animate__animated animate__fadeInUp animate__fast">
  <?php
  $pub_count  = count(array_filter($courses, fn($c) => !(bool)($c->archived ?? 0)));
  $arch_count = count(array_filter($courses, fn($c) => (bool)($c->archived ?? 0)));
  $total_enroll = 0;
  foreach ($courses as $c) { $total_enroll += (int)($c->enrolled_count ?? 0); }
  ?>
  <div class="mc-kpi">
    <div class="mc-kpi-icon" style="background:#eff6ff;color:#3b82f6;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
    </div>
    <div><div class="mc-kpi-val"><?= count($courses) ?></div><div class="mc-kpi-lbl">Total Courses</div></div>
  </div>
  <div class="mc-kpi">
    <div class="mc-kpi-icon" style="background:#ecfdf5;color:#22c55e;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
    </div>
    <div><div class="mc-kpi-val"><?= $pub_count ?></div><div class="mc-kpi-lbl">Published</div></div>
  </div>
  <div class="mc-kpi">
    <div class="mc-kpi-icon" style="background:#f1f5f9;color:#64748b;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
    </div>
    <div><div class="mc-kpi-val"><?= $arch_count ?></div><div class="mc-kpi-lbl">Archived</div></div>
  </div>
  <div class="mc-kpi">
    <div class="mc-kpi-icon" style="background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div><div class="mc-kpi-val"><?= number_format($total_enroll) ?></div><div class="mc-kpi-lbl">Total Enrollments</div></div>
  </div>
</div>

<!-- ══ Filters ══════════════════════════════════════════════ -->
<div class="mc-filters animate__animated animate__fadeInUp animate__fast" style="animation-delay:.05s;">
  <div class="mc-search-wrap">
    <svg class="mc-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="mc-search-input" id="mcSearch" placeholder="Search courses…" value="<?= htmlspecialchars($keyword) ?>">
  </div>
  <select class="mc-select" id="mcFilterCat">
    <option value="">All Categories</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= $cat->id ?>" <?= $filter_cat == $cat->id ? 'selected' : '' ?>><?= htmlspecialchars($cat->name) ?></option>
    <?php endforeach; ?>
  </select>
  <select class="mc-select" id="mcFilterStatus">
    <option value="">All Status</option>
    <option value="0" <?= $filter_status === '0' ? 'selected' : '' ?>>Published</option>
    <option value="1" <?= $filter_status === '1' ? 'selected' : '' ?>>Archived</option>
  </select>
  <span class="mc-filter-count" id="mcFilterCount"><?= count($courses) ?> courses</span>
</div>

<!-- ══ Grid View ════════════════════════════════════════════ -->
<div id="mcGridView" class="mc-grid animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s;">
  <?php if ( ! empty($courses)): ?>
    <?php foreach ($courses as $i => $course):
      $grad       = $thumb_gradients[$i % count($thumb_gradients)];
      $initials   = strtoupper(substr($course->title, 0, 2));
      $is_archived= (bool)($course->archived ?? 0);
      $cat_name   = $course->category_name ?? 'General';
      $modules    = (int)($course->module_count   ?? 0);
      $enrolled   = (int)($course->enrolled_count ?? 0);
      $avg_prog   = (int)($course->avg_progress   ?? 0);
    ?>
    <div class="mc-card animate__animated animate__fadeInUp" style="animation-delay:<?= ($i % 8) * 0.04 ?>s;" data-title="<?= htmlspecialchars(strtolower($course->title)) ?>" data-cat="<?= $course->category_id ?? '' ?>" data-archived="<?= (int)$is_archived ?>">
      <div class="mc-card-thumb" style="background:<?= $grad ?>;">
        <div class="mc-card-thumb-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        </div>
        <span class="mc-card-thumb-badge <?= $is_archived ? 'mc-card-thumb-badge-archived' : 'mc-card-thumb-badge-published' ?>">
          <?= $is_archived ? 'Archived' : 'Published' ?>
        </span>
        <div class="mc-card-thumb-menu dropdown">
          <button class="mc-card-thumb-menu-btn" data-bs-toggle="dropdown">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= base_url('manage_courses/edit/'.$course->id) ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit Course
            </a></li>
            <li><a class="dropdown-item" href="<?= base_url('manage_courses/view/'.$course->id) ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              View Details
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= base_url('manage_courses/delete/'.$course->id) ?>" onclick="event.preventDefault(); KA.deleteConfirm(this.href, 'Course')">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
              Delete
            </a></li>
          </ul>
        </div>
      </div>
      <div class="mc-card-body">
        <div class="mc-card-category"><?= htmlspecialchars($cat_name) ?></div>
        <div class="mc-card-title"><?= htmlspecialchars($course->title) ?></div>
        <div class="mc-card-meta">
          <div class="mc-card-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <?= $modules ?> modules
          </div>
          <div class="mc-card-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <?= $enrolled ?> enrolled
          </div>
        </div>
        <div class="mc-card-progress">
          <div class="mc-card-progress-top">
            <span class="mc-card-progress-lbl">Avg Completion</span>
            <span class="mc-card-progress-pct"><?= $avg_prog ?>%</span>
          </div>
          <div class="mc-prog-bar"><div class="mc-prog-fill" style="width:<?= $avg_prog ?>%"></div></div>
        </div>
        <div class="mc-card-footer">
          <a href="<?= base_url('manage_courses/edit/'.$course->id) ?>" class="mc-card-btn mc-card-btn-primary">Edit</a>
          <a href="<?= base_url('manage_courses/view/'.$course->id) ?>" class="mc-card-btn mc-card-btn-primary">View</a>
          <a href="<?= base_url('manage_courses/delete/'.$course->id) ?>" class="mc-card-btn mc-card-btn-danger" onclick="event.preventDefault(); KA.deleteConfirm(this.href, 'Course')">Delete</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="mc-empty" style="grid-column:1/-1;">
      <div class="mc-empty-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
      </div>
      <h4>No courses found</h4>
      <p>Get started by creating your first course.</p>
      <a href="<?= base_url('manage_courses/create') ?>" class="mc-btn mc-btn-primary">Create First Course</a>
    </div>
  <?php endif; ?>
</div>

<!-- ══ Table View (hidden by default) ══════════════════════ -->
<div id="mcTableView" style="display:none;" class="animate__animated animate__fadeIn">
  <div class="mc-table-wrap">
    <table class="mc-table">
      <thead>
        <tr>
          <th>Course</th>
          <th>Category</th>
          <th>Status</th>
          <th>Modules</th>
          <th>Enrolled</th>
          <th>Avg Progress</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ( ! empty($courses)): ?>
          <?php foreach ($courses as $i => $course):
            $grad       = $thumb_gradients[$i % count($thumb_gradients)];
            $initials   = strtoupper(substr($course->title, 0, 2));
            $is_archived= (bool)($course->archived ?? 0);
            $cat_name   = $course->category_name ?? 'General';
            $modules    = (int)($course->module_count   ?? 0);
            $enrolled   = (int)($course->enrolled_count ?? 0);
            $avg_prog   = (int)($course->avg_progress   ?? 0);
          ?>
          <tr data-title="<?= htmlspecialchars(strtolower($course->title)) ?>" data-cat="<?= $course->category_id ?? '' ?>" data-archived="<?= (int)$is_archived ?>">
            <td>
              <div class="mc-tbl-course">
                <div class="mc-tbl-thumb" style="background:<?= $grad ?>"><?= $initials ?></div>
                <div>
                  <div class="mc-tbl-title"><?= htmlspecialchars($course->title) ?></div>
                  <div class="mc-tbl-sub">ID #<?= $course->id ?></div>
                </div>
              </div>
            </td>
            <td style="color:var(--ka-text-muted,#64748b);font-size:.8125rem;"><?= htmlspecialchars($cat_name) ?></td>
            <td><span class="mc-status-badge <?= $is_archived ? 'mc-status-arch' : 'mc-status-pub' ?>">● <?= $is_archived ? 'Archived' : 'Published' ?></span></td>
            <td style="font-weight:600;"><?= $modules ?></td>
            <td style="font-weight:600;"><?= $enrolled ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="mc-mini-bar"><div class="mc-mini-fill" style="width:<?= $avg_prog ?>%"></div></div>
                <span style="font-size:.75rem;font-weight:700;color:var(--ka-primary,#6dabcf);white-space:nowrap;"><?= $avg_prog ?>%</span>
              </div>
            </td>
            <td style="color:var(--ka-text-muted,#64748b);font-size:.75rem;white-space:nowrap;"><?= date('M j, Y', strtotime($course->created_at)) ?></td>
            <td>
              <div class="mc-action-row">
                <a href="<?= base_url('manage_courses/edit/'.$course->id) ?>" class="mc-action-link mc-action-edit">Edit</a>
                <a href="<?= base_url('manage_courses/delete/'.$course->id) ?>" class="mc-action-link mc-action-delete" onclick="event.preventDefault(); KA.deleteConfirm(this.href, 'Course')">Delete</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8" style="text-align:center;padding:2.5rem;color:var(--ka-text-muted,#64748b);">No courses found. <a href="<?= base_url('manage_courses/create') ?>">Create one</a>.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  // ── View toggle ──────────────────────────────────────────
  const gridView  = document.getElementById('mcGridView');
  const tableView = document.getElementById('mcTableView');

  document.querySelectorAll('.mc-toggle-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.mc-toggle-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      if (this.dataset.view === 'grid') {
        gridView.style.display  = '';
        tableView.style.display = 'none';
      } else {
        gridView.style.display  = 'none';
        tableView.style.display = '';
      }
    });
  });

  // ── Live filter ──────────────────────────────────────────
  function applyFilters() {
    const keyword  = document.getElementById('mcSearch').value.toLowerCase().trim();
    const cat      = document.getElementById('mcFilterCat').value;
    const status   = document.getElementById('mcFilterStatus').value;

    const allCards = document.querySelectorAll('.mc-card, #mcTableView tbody tr');
    let visible = 0;

    allCards.forEach(el => {
      const title    = el.dataset.title    || '';
      const elCat    = el.dataset.cat      || '';
      const archived = el.dataset.archived || '0';

      const matchTitle  = !keyword || title.includes(keyword);
      const matchCat    = !cat     || elCat === cat;
      const matchStatus = status === '' || archived === status;

      const show = matchTitle && matchCat && matchStatus;
      el.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    document.getElementById('mcFilterCount').textContent = visible + ' course' + (visible !== 1 ? 's' : '');
  }

  document.getElementById('mcSearch').addEventListener('input', applyFilters);
  document.getElementById('mcFilterCat').addEventListener('change', applyFilters);
  document.getElementById('mcFilterStatus').addEventListener('change', applyFilters);
});
</script>