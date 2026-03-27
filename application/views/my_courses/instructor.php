<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// Data from My_courses controller (instructor/teacher)
$my_courses_list  = $my_courses_list  ?? [];
$categories       = $categories       ?? [];
$keyword          = $keyword          ?? '';
$filter_cat       = $filter_cat       ?? '';
$filter_status    = $filter_status    ?? '';

$full_name  = isset($user) && is_object($user) ? ($user->fullname ?? 'Instructor') : 'Instructor';
$first_name = explode(' ', trim($full_name))[0];

$total_courses    = count($my_courses_list);
$total_published  = count(array_filter($my_courses_list, fn($c) => !(bool)($c->archived ?? 0)));
$total_archived   = count(array_filter($my_courses_list, fn($c) => (bool)($c->archived ?? 0)));
$total_students   = array_sum(array_map(fn($c) => (int)($c->enrolled_count ?? 0), $my_courses_list));

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
     KABAGA ACADEMY — My Courses (Instructor / Teacher)
============================================================ -->
<style>
/* ── Topbar ── */
.ic-topbar { display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem; }
.ic-topbar-left h2 { font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 2px;letter-spacing:-.02em; }
.ic-topbar-left p  { font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0; }
.ic-topbar-right { display:flex;align-items:center;gap:.625rem;flex-wrap:wrap; }

.ic-btn {
  display:inline-flex;align-items:center;gap:6px;
  padding:.5rem 1rem;border-radius:8px;font-size:.8125rem;
  font-weight:600;text-decoration:none;border:none;cursor:pointer;
  transition:all .18s;white-space:nowrap;
}
.ic-btn-primary { background:var(--ka-navy,#1a3a5c);color:#fff; }
.ic-btn-primary:hover { background:#254d75;color:#fff;transform:translateY(-1px); }
.ic-btn-outline { background:#fff;color:var(--ka-text,#1e293b);border:1.5px solid var(--ka-border,#e2e8f0); }
.ic-btn-outline:hover { border-color:var(--ka-primary,#6dabcf);color:var(--ka-primary,#6dabcf); }

/* ── KPI ── */
.ic-kpi-row { display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem; }
@media(max-width:1199.98px){.ic-kpi-row{grid-template-columns:repeat(2,1fr);}}
@media(max-width:575.98px) {.ic-kpi-row{grid-template-columns:1fr;}}

.ic-kpi {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:12px;padding:1rem 1.125rem;
  display:flex;align-items:center;gap:.875rem;
  transition:box-shadow .2s,transform .2s;
}
.ic-kpi:hover { box-shadow:0 6px 20px rgba(0,0,0,.06);transform:translateY(-2px); }
.ic-kpi-icon { width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center; }
.ic-kpi-icon svg { width:18px;height:18px; }
.ic-kpi-val { font-size:1.5rem;font-weight:800;color:var(--ka-text,#1e293b);line-height:1;letter-spacing:-.02em; }
.ic-kpi-lbl { font-size:.6875rem;color:var(--ka-text-muted,#64748b);font-weight:500;margin-top:2px; }

/* ── Filters ── */
.ic-filters {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:12px;padding:.875rem 1rem;
  display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem;
}
.ic-search-wrap { position:relative;flex:1;min-width:200px; }
.ic-search-icon { position:absolute;left:.75rem;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--ka-text-muted,#64748b);pointer-events:none; }
.ic-search-input {
  width:100%;height:36px;padding:0 .75rem 0 2.25rem;
  border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;
  font-size:.8125rem;background:var(--ka-bg,#f8fafc);outline:none;transition:all .2s;
}
.ic-search-input:focus { border-color:var(--ka-primary,#6dabcf);background:#fff;box-shadow:0 0 0 3px rgba(109,171,207,.15); }
.ic-select {
  height:36px;padding:0 .75rem;border:1.5px solid var(--ka-border,#e2e8f0);
  border-radius:8px;font-size:.8125rem;background:var(--ka-bg,#f8fafc);
  outline:none;cursor:pointer;transition:all .2s;
}
.ic-select:focus { border-color:var(--ka-primary,#6dabcf); }
.ic-filter-count { font-size:.75rem;font-weight:600;color:var(--ka-text-muted,#64748b);white-space:nowrap; }

/* ── View toggle ── */
.ic-view-toggle { display:flex;gap:4px; }
.ic-toggle-btn {
  width:32px;height:32px;border-radius:7px;border:1.5px solid var(--ka-border,#e2e8f0);
  background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;
  color:var(--ka-text-muted,#64748b);transition:all .15s;
}
.ic-toggle-btn.active,
.ic-toggle-btn:hover { background:var(--ka-navy,#1a3a5c);border-color:var(--ka-navy,#1a3a5c);color:#fff; }
.ic-toggle-btn svg { width:15px;height:15px; }

/* ── Course card ── */
.ic-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:1.125rem;margin-bottom:1.5rem; }

.ic-card {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;overflow:hidden;
  transition:box-shadow .2s,transform .2s;display:flex;flex-direction:column;
}
.ic-card:hover { box-shadow:0 8px 28px rgba(0,0,0,.09);transform:translateY(-3px); }

.ic-card-thumb {
  height:130px;position:relative;overflow:hidden;
  display:flex;align-items:center;justify-content:center;
}
.ic-card-thumb-icon { opacity:.22; }
.ic-card-thumb-icon svg { width:56px;height:56px; }

.ic-card-thumb-badge {
  position:absolute;top:10px;left:10px;
  font-size:.625rem;font-weight:700;padding:3px 8px;
  border-radius:20px;backdrop-filter:blur(6px);
}
.ic-badge-published { background:rgba(34,197,94,.9);color:#fff; }
.ic-badge-archived  { background:rgba(100,116,139,.8);color:#fff; }

.ic-card-thumb-menu { position:absolute;top:10px;right:10px; }
.ic-card-menu-btn {
  width:28px;height:28px;border-radius:6px;
  background:rgba(0,0,0,.35);border:none;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  color:#fff;backdrop-filter:blur(4px);transition:background .15s;
}
.ic-card-menu-btn:hover { background:rgba(0,0,0,.55); }
.ic-card-menu-btn svg { width:14px;height:14px; }

.ic-card-body { padding:1rem;flex:1;display:flex;flex-direction:column; }
.ic-card-category { font-size:.625rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ka-primary,#6dabcf);margin-bottom:.375rem; }
.ic-card-title {
  font-size:.9rem;font-weight:700;color:var(--ka-text,#1e293b);
  line-height:1.35;margin-bottom:.625rem;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.ic-card-meta { display:flex;align-items:center;gap:.875rem;margin-bottom:.75rem;flex-wrap:wrap; }
.ic-card-meta-item { display:flex;align-items:center;gap:4px;font-size:.6875rem;color:var(--ka-text-muted,#64748b); }
.ic-card-meta-item svg { width:13px;height:13px; }

.ic-card-progress { margin-bottom:.875rem; }
.ic-card-progress-top { display:flex;justify-content:space-between;align-items:center;margin-bottom:4px; }
.ic-card-progress-lbl { font-size:.6875rem;color:var(--ka-text-muted,#64748b);font-weight:500; }
.ic-card-progress-pct { font-size:.6875rem;font-weight:700;color:var(--ka-primary,#6dabcf); }
.ic-prog-bar { height:5px;background:var(--ka-border,#e2e8f0);border-radius:3px;overflow:hidden; }
.ic-prog-fill { height:100%;border-radius:3px;background:linear-gradient(90deg,var(--ka-primary,#6dabcf),#5a9ec1); }

.ic-card-footer {
  margin-top:auto;padding-top:.75rem;
  border-top:1px solid var(--ka-border,#e2e8f0);
  display:flex;align-items:center;gap:.5rem;
}
.ic-card-btn {
  flex:1;padding:.4375rem .5rem;border-radius:7px;
  font-size:.75rem;font-weight:600;text-decoration:none;
  text-align:center;transition:all .15s;
}
.ic-btn-edit   { background:var(--ka-accent,#e8f4fd);color:var(--ka-primary-deep,#4a8eb0); }
.ic-btn-edit:hover   { background:var(--ka-primary,#6dabcf);color:#fff; }
.ic-btn-manage { background:#f0fdf4;color:#15803d; }
.ic-btn-manage:hover { background:#22c55e;color:#fff; }
.ic-btn-delete { background:#fef2f2;color:#dc2626; }
.ic-btn-delete:hover { background:#dc2626;color:#fff; }

/* ── Table view ── */
.ic-table-wrap { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;overflow:hidden;margin-bottom:1.5rem; }
.ic-table { width:100%;border-collapse:collapse; }
.ic-table thead th {
  font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;
  color:var(--ka-text-muted,#64748b);padding:.75rem 1rem;
  background:var(--ka-bg,#f8fafc);border-bottom:1px solid var(--ka-border,#e2e8f0);
  white-space:nowrap;
}
.ic-table tbody tr { border-bottom:1px solid var(--ka-border,#e2e8f0);transition:background .15s; }
.ic-table tbody tr:hover { background:var(--ka-accent,#e8f4fd); }
.ic-table tbody tr:last-child { border-bottom:none; }
.ic-table tbody td { padding:.8125rem 1rem;font-size:.8125rem;color:var(--ka-text,#1e293b);vertical-align:middle; }

.ic-tbl-course { display:flex;align-items:center;gap:10px; }
.ic-tbl-thumb { width:38px;height:38px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.6875rem; }
.ic-tbl-title { font-weight:600;font-size:.8125rem;line-height:1.3; }
.ic-tbl-sub   { font-size:.6875rem;color:var(--ka-text-muted,#64748b); }

.ic-status-badge { display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:.6875rem;font-weight:600; }
.ic-status-pub  { background:#ecfdf5;color:#065f46; }
.ic-status-arch { background:#f1f5f9;color:#64748b; }

.ic-mini-bar { height:5px;background:var(--ka-border,#e2e8f0);border-radius:3px;overflow:hidden;min-width:80px; }
.ic-mini-fill{ height:100%;border-radius:3px;background:linear-gradient(90deg,var(--ka-primary,#6dabcf),#5a9ec1); }

.ic-action-row { display:flex;align-items:center;gap:.5rem; }
.ic-action-link {
  font-size:.6875rem;font-weight:600;padding:4px 10px;border-radius:6px;
  text-decoration:none;transition:all .15s;white-space:nowrap;
}
.ic-action-edit   { background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf); }
.ic-action-edit:hover   { background:var(--ka-primary,#6dabcf);color:#fff; }
.ic-action-manage { background:#f0fdf4;color:#15803d; }
.ic-action-manage:hover { background:#22c55e;color:#fff; }
.ic-action-delete { background:#fef2f2;color:#dc2626; }
.ic-action-delete:hover { background:#dc2626;color:#fff; }

/* ── Empty ── */
.ic-empty { text-align:center;padding:3rem 1rem;background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px; }
.ic-empty svg { width:48px;height:48px;margin:0 auto .875rem;opacity:.25;display:block; }
.ic-empty h4 { font-size:1rem;font-weight:700;color:var(--ka-text,#1e293b);margin-bottom:.375rem; }
.ic-empty p  { font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin-bottom:1.25rem; }
</style>

<!-- ══ Top Bar ═══════════════════════════════════════════════ -->
<div class="ic-topbar animate__animated animate__fadeIn animate__fast">
  <div class="ic-topbar-left">
    <h2>My Courses</h2>
    <p>Create, manage and track all your courses, <?= htmlspecialchars($first_name) ?></p>
  </div>
  <div class="ic-topbar-right">
    <div class="ic-view-toggle" id="icViewToggle">
      <button class="ic-toggle-btn active" data-view="grid" title="Grid view">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
      </button>
      <button class="ic-toggle-btn" data-view="table" title="Table view">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
    <a href="<?= base_url('manage_courses/create') ?>" class="ic-btn ic-btn-primary">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Course
    </a>
  </div>
</div>

<!-- ══ KPI Strip ════════════════════════════════════════════ -->
<div class="ic-kpi-row animate__animated animate__fadeInUp animate__fast">
  <?php
  $avg_comp_all = $total_courses > 0
    ? round(array_sum(array_map(fn($c) => (int)($c->avg_progress ?? 0), $my_courses_list)) / $total_courses)
    : 0;
  ?>
  <div class="ic-kpi">
    <div class="ic-kpi-icon" style="background:#eff6ff;color:#3b82f6;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
    </div>
    <div><div class="ic-kpi-val"><?= $total_courses ?></div><div class="ic-kpi-lbl">Total Courses</div></div>
  </div>
  <div class="ic-kpi">
    <div class="ic-kpi-icon" style="background:#ecfdf5;color:#22c55e;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
    </div>
    <div><div class="ic-kpi-val"><?= $total_published ?></div><div class="ic-kpi-lbl">Published</div></div>
  </div>
  <div class="ic-kpi">
    <div class="ic-kpi-icon" style="background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
    </div>
    <div><div class="ic-kpi-val"><?= number_format($total_students) ?></div><div class="ic-kpi-lbl">Total Students</div></div>
  </div>
  <div class="ic-kpi">
    <div class="ic-kpi-icon" style="background:#fffbeb;color:#f59f00;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    </div>
    <div><div class="ic-kpi-val"><?= $avg_comp_all ?>%</div><div class="ic-kpi-lbl">Avg Completion</div></div>
  </div>
</div>

<!-- ══ Filters ══════════════════════════════════════════════ -->
<div class="ic-filters animate__animated animate__fadeInUp animate__fast" style="animation-delay:.05s;">
  <div class="ic-search-wrap">
    <svg class="ic-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="ic-search-input" id="icSearch" placeholder="Search your courses…">
  </div>
  <select class="ic-select" id="icFilterCat">
    <option value="">All Categories</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->name) ?></option>
    <?php endforeach; ?>
  </select>
  <select class="ic-select" id="icFilterStatus">
    <option value="">All Status</option>
    <option value="0">Published</option>
    <option value="1">Archived</option>
  </select>
  <span class="ic-filter-count" id="icFilterCount"><?= $total_courses ?> courses</span>
</div>

<!-- ══ Grid View ════════════════════════════════════════════ -->
<div id="icGridView" class="ic-grid animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s;">
  <?php if ( ! empty($my_courses_list)): ?>
    <?php foreach ($my_courses_list as $i => $course):
      $grad       = $thumb_gradients[$i % count($thumb_gradients)];
      $initials   = strtoupper(substr($course->title, 0, 2));
      $is_archived= (bool)($course->archived ?? 0);
      $cat_name   = $course->category_name ?? 'General';
      $modules    = (int)($course->module_count   ?? 0);
      $enrolled   = (int)($course->enrolled_count ?? 0);
      $avg_prog   = (int)($course->avg_progress   ?? 0);
    ?>
    <div class="ic-card animate__animated animate__fadeInUp" style="animation-delay:<?= ($i % 8) * 0.04 ?>s;"
         data-title="<?= htmlspecialchars(strtolower($course->title)) ?>"
         data-cat="<?= $course->category_id ?? '' ?>"
         data-archived="<?= (int)$is_archived ?>">
      <div class="ic-card-thumb" style="background:<?= $grad ?>;">
        <div class="ic-card-thumb-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        </div>
        <span class="ic-card-thumb-badge <?= $is_archived ? 'ic-badge-archived' : 'ic-badge-published' ?>">
          <?= $is_archived ? 'Archived' : 'Published' ?>
        </span>
        <div class="ic-card-thumb-menu dropdown">
          <button class="ic-card-menu-btn" data-bs-toggle="dropdown">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= base_url('manage_courses/edit/'.$course->id) ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit Course
            </a></li>
            <li><a class="dropdown-item" href="<?= base_url('manage_courses/modules/'.$course->id) ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
              Manage Modules
            </a></li>
            <li><a class="dropdown-item" href="<?= base_url('manage_courses/students/'.$course->id) ?>">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
              View Students
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= base_url('manage_courses/delete/'.$course->id) ?>" onclick="event.preventDefault(); KA.deleteConfirm(this.href, 'Course')">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
              Delete
            </a></li>
          </ul>
        </div>
      </div>
      <div class="ic-card-body">
        <div class="ic-card-category"><?= htmlspecialchars($cat_name) ?></div>
        <div class="ic-card-title"><?= htmlspecialchars($course->title) ?></div>
        <div class="ic-card-meta">
          <div class="ic-card-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <?= $modules ?> modules
          </div>
          <div class="ic-card-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <?= $enrolled ?> students
          </div>
        </div>
        <div class="ic-card-progress">
          <div class="ic-card-progress-top">
            <span class="ic-card-progress-lbl">Avg Completion</span>
            <span class="ic-card-progress-pct"><?= $avg_prog ?>%</span>
          </div>
          <div class="ic-prog-bar"><div class="ic-prog-fill" style="width:<?= $avg_prog ?>%"></div></div>
        </div>
        <div class="ic-card-footer">
          <a href="<?= base_url('manage_courses/edit/'.$course->id) ?>" class="ic-card-btn ic-btn-edit">Edit</a>
          <a href="<?= base_url('manage_courses/modules/'.$course->id) ?>" class="ic-card-btn ic-btn-manage">Modules</a>
          <a href="<?= base_url('manage_courses/delete/'.$course->id) ?>" class="ic-card-btn ic-btn-delete" onclick="event.preventDefault(); KA.deleteConfirm(this.href, 'Course')">Del</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="ic-empty" style="grid-column:1/-1;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      <h4>No courses yet</h4>
      <p>Start building your first course for your learners.</p>
      <a href="<?= base_url('manage_courses/create') ?>" class="ic-btn ic-btn-primary">Create First Course</a>
    </div>
  <?php endif; ?>
</div>

<!-- ══ Table View ═══════════════════════════════════════════ -->
<div id="icTableView" style="display:none;" class="animate__animated animate__fadeIn">
  <div class="ic-table-wrap">
    <table class="ic-table">
      <thead>
        <tr>
          <th>Course</th>
          <th>Category</th>
          <th>Status</th>
          <th>Modules</th>
          <th>Students</th>
          <th>Avg Progress</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ( ! empty($my_courses_list)): ?>
          <?php foreach ($my_courses_list as $i => $course):
            $grad       = $thumb_gradients[$i % count($thumb_gradients)];
            $initials   = strtoupper(substr($course->title, 0, 2));
            $is_archived= (bool)($course->archived ?? 0);
            $cat_name   = $course->category_name ?? 'General';
            $modules    = (int)($course->module_count   ?? 0);
            $enrolled   = (int)($course->enrolled_count ?? 0);
            $avg_prog   = (int)($course->avg_progress   ?? 0);
          ?>
          <tr data-title="<?= htmlspecialchars(strtolower($course->title)) ?>"
              data-cat="<?= $course->category_id ?? '' ?>"
              data-archived="<?= (int)$is_archived ?>">
            <td>
              <div class="ic-tbl-course">
                <div class="ic-tbl-thumb" style="background:<?= $grad ?>"><?= $initials ?></div>
                <div>
                  <div class="ic-tbl-title"><?= htmlspecialchars($course->title) ?></div>
                  <div class="ic-tbl-sub">ID #<?= $course->id ?></div>
                </div>
              </div>
            </td>
            <td style="color:var(--ka-text-muted,#64748b);font-size:.8125rem;"><?= htmlspecialchars($cat_name) ?></td>
            <td><span class="ic-status-badge <?= $is_archived ? 'ic-status-arch' : 'ic-status-pub' ?>">● <?= $is_archived ? 'Archived' : 'Published' ?></span></td>
            <td style="font-weight:600;"><?= $modules ?></td>
            <td style="font-weight:600;"><?= $enrolled ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="ic-mini-bar"><div class="ic-mini-fill" style="width:<?= $avg_prog ?>%"></div></div>
                <span style="font-size:.75rem;font-weight:700;color:var(--ka-primary,#6dabcf);white-space:nowrap;"><?= $avg_prog ?>%</span>
              </div>
            </td>
            <td style="color:var(--ka-text-muted,#64748b);font-size:.75rem;white-space:nowrap;"><?= date('M j, Y', strtotime($course->created_at)) ?></td>
            <td>
              <div class="ic-action-row">
                <a href="<?= base_url('manage_courses/edit/'.$course->id) ?>" class="ic-action-link ic-action-edit">Edit</a>
                <a href="<?= base_url('manage_courses/modules/'.$course->id) ?>" class="ic-action-link ic-action-manage">Modules</a>
                <a href="<?= base_url('manage_courses/delete/'.$course->id) ?>" class="ic-action-link ic-action-delete" onclick="event.preventDefault(); KA.deleteConfirm(this.href, 'Course')">Delete</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8" style="text-align:center;padding:2.5rem;color:var(--ka-text-muted,#64748b);">No courses yet. <a href="<?= base_url('manage_courses/create') ?>">Create one now</a>.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  // ── View toggle ──────────────────────────────────────────
  const gridView  = document.getElementById('icGridView');
  const tableView = document.getElementById('icTableView');

  document.querySelectorAll('.ic-toggle-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.ic-toggle-btn').forEach(b => b.classList.remove('active'));
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
    const keyword = document.getElementById('icSearch').value.toLowerCase().trim();
    const cat     = document.getElementById('icFilterCat').value;
    const status  = document.getElementById('icFilterStatus').value;

    const allItems = document.querySelectorAll('.ic-card, #icTableView tbody tr');
    let visible = 0;

    allItems.forEach(el => {
      const title    = el.dataset.title    || '';
      const elCat    = el.dataset.cat      || '';
      const archived = el.dataset.archived || '0';

      const show = (!keyword || title.includes(keyword))
                && (!cat     || elCat === cat)
                && (status === '' || archived === status);

      el.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    document.getElementById('icFilterCount').textContent = visible + ' course' + (visible !== 1 ? 's' : '');
  }

  document.getElementById('icSearch').addEventListener('input', applyFilters);
  document.getElementById('icFilterCat').addEventListener('change', applyFilters);
  document.getElementById('icFilterStatus').addEventListener('change', applyFilters);
});
</script>