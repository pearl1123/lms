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
$total_completed = count(array_filter($enrolled_courses, fn($c) => (int)($c->progress_pct ?? 0) >= 100));
$in_progress     = count(array_filter($enrolled_courses, fn($c) => (int)($c->progress_pct ?? 0) > 0 && (int)($c->progress_pct ?? 0) < 100));

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
     KABAGA ACADEMY — My Courses (Employee / Student)
============================================================ -->
<style>
/* ── Page header ── */
.ec-topbar { display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem; }
.ec-topbar-left h2 { font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 2px;letter-spacing:-.02em; }
.ec-topbar-left p  { font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0; }
.ec-btn {
  display:inline-flex;align-items:center;gap:6px;
  padding:.5rem 1rem;border-radius:8px;font-size:.8125rem;
  font-weight:600;text-decoration:none;border:none;cursor:pointer;
  transition:all .18s;white-space:nowrap;
}
.ec-btn-primary { background:var(--ka-navy,#1a3a5c);color:#fff; }
.ec-btn-primary:hover { background:#254d75;color:#fff;transform:translateY(-1px); }

/* ── KPI strip ── */
.ec-kpi-row { display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem; }
@media(max-width:1199.98px){.ec-kpi-row{grid-template-columns:repeat(2,1fr);}}
@media(max-width:575.98px) {.ec-kpi-row{grid-template-columns:1fr;}}

.ec-kpi {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:12px;padding:1rem 1.125rem;
  display:flex;align-items:center;gap:.875rem;
  transition:box-shadow .2s,transform .2s;
}
.ec-kpi:hover { box-shadow:0 6px 20px rgba(0,0,0,.06);transform:translateY(-2px); }
.ec-kpi-icon { width:40px;height:40px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center; }
.ec-kpi-icon svg { width:18px;height:18px; }
.ec-kpi-val { font-size:1.5rem;font-weight:800;color:var(--ka-text,#1e293b);line-height:1;letter-spacing:-.02em; }
.ec-kpi-lbl { font-size:.6875rem;color:var(--ka-text-muted,#64748b);font-weight:500;margin-top:2px; }

/* ── Tabs ── */
.ec-tabs { display:flex;gap:0;border-bottom:2px solid var(--ka-border,#e2e8f0);margin-bottom:1.25rem; }
.ec-tab {
  padding:.625rem 1.125rem;font-size:.875rem;font-weight:600;
  color:var(--ka-text-muted,#64748b);border:none;background:transparent;
  border-bottom:2px solid transparent;margin-bottom:-2px;
  cursor:pointer;transition:all .18s;white-space:nowrap;
  display:flex;align-items:center;gap:6px;
}
.ec-tab:hover { color:var(--ka-primary,#6dabcf); }
.ec-tab.active { color:var(--ka-navy,#1a3a5c);border-bottom-color:var(--ka-navy,#1a3a5c); }
.ec-tab-badge {
  font-size:.625rem;font-weight:700;padding:2px 6px;border-radius:10px;
  background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);
}
.ec-tab.active .ec-tab-badge { background:var(--ka-navy,#1a3a5c);color:#fff; }

/* ── Filters ── */
.ec-filters {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:12px;padding:.875rem 1rem;
  display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem;
}
.ec-search-wrap { position:relative;flex:1;min-width:200px; }
.ec-search-icon { position:absolute;left:.75rem;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--ka-text-muted,#64748b);pointer-events:none; }
.ec-search-input {
  width:100%;height:36px;padding:0 .75rem 0 2.25rem;
  border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;
  font-size:.8125rem;background:var(--ka-bg,#f8fafc);outline:none;transition:all .2s;
}
.ec-search-input:focus { border-color:var(--ka-primary,#6dabcf);background:#fff;box-shadow:0 0 0 3px rgba(109,171,207,.15); }
.ec-select {
  height:36px;padding:0 .75rem;border:1.5px solid var(--ka-border,#e2e8f0);
  border-radius:8px;font-size:.8125rem;background:var(--ka-bg,#f8fafc);
  outline:none;cursor:pointer;transition:all .2s;
}
.ec-select:focus { border-color:var(--ka-primary,#6dabcf); }

/* ── Course grid ── */
.ec-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.125rem;margin-bottom:1.5rem; }

.ec-course-card {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;overflow:hidden;
  transition:box-shadow .2s,transform .2s;display:flex;flex-direction:column;
  text-decoration:none;
}
.ec-course-card:hover { box-shadow:0 8px 28px rgba(0,0,0,.09);transform:translateY(-3px); }

.ec-card-thumb {
  height:130px;position:relative;overflow:hidden;
  display:flex;align-items:center;justify-content:center;
}
.ec-card-thumb-icon { opacity:.22; }
.ec-card-thumb-icon svg { width:56px;height:56px; }
.ec-card-status-badge {
  position:absolute;top:10px;right:10px;
  font-size:.625rem;font-weight:700;padding:3px 8px;border-radius:20px;
  backdrop-filter:blur(6px);
}
.ec-badge-completed  { background:rgba(34,197,94,.9);color:#fff; }
.ec-badge-inprogress { background:rgba(109,171,207,.9);color:#fff; }
.ec-badge-notstarted { background:rgba(0,0,0,.3);color:#fff; }

.ec-card-body { padding:1rem;flex:1;display:flex;flex-direction:column; }
.ec-card-category { font-size:.625rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--ka-primary,#6dabcf);margin-bottom:.375rem; }
.ec-card-title {
  font-size:.9rem;font-weight:700;color:var(--ka-text,#1e293b);
  line-height:1.35;margin-bottom:.625rem;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.ec-card-meta { display:flex;align-items:center;gap:.875rem;margin-bottom:.75rem;flex-wrap:wrap; }
.ec-card-meta-item { display:flex;align-items:center;gap:4px;font-size:.6875rem;color:var(--ka-text-muted,#64748b); }
.ec-card-meta-item svg { width:13px;height:13px; }

.ec-card-progress { margin-bottom:.875rem; }
.ec-card-progress-top { display:flex;justify-content:space-between;align-items:center;margin-bottom:4px; }
.ec-card-progress-lbl { font-size:.6875rem;color:var(--ka-text-muted,#64748b);font-weight:500; }
.ec-card-progress-pct { font-size:.6875rem;font-weight:700; }
.ec-prog-bar { height:6px;background:var(--ka-border,#e2e8f0);border-radius:3px;overflow:hidden; }
.ec-prog-fill { height:100%;border-radius:3px;transition:width .6s ease; }
.ec-prog-fill-active    { background:linear-gradient(90deg,var(--ka-primary,#6dabcf),#5a9ec1); }
.ec-prog-fill-completed { background:linear-gradient(90deg,#22c55e,#15803d); }
.ec-prog-fill-notstarted{ background:var(--ka-border,#e2e8f0); }

.ec-card-footer {
  margin-top:auto;padding-top:.75rem;
  border-top:1px solid var(--ka-border,#e2e8f0);
  display:flex;align-items:center;gap:.5rem;
}
.ec-card-cta {
  flex:1;padding:.5rem;border-radius:7px;text-align:center;
  font-size:.75rem;font-weight:700;text-decoration:none;
  transition:all .15s;
}
.ec-cta-continue  { background:var(--ka-navy,#1a3a5c);color:#fff; }
.ec-cta-continue:hover  { background:#254d75;color:#fff; }
.ec-cta-start     { background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf); }
.ec-cta-start:hover     { background:var(--ka-primary,#6dabcf);color:#fff; }
.ec-cta-review    { background:#ecfdf5;color:#15803d; }
.ec-cta-review:hover    { background:#22c55e;color:#fff; }

/* ── Available courses (catalog) ── */
.ec-available-card {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;overflow:hidden;
  transition:box-shadow .2s,transform .2s;display:flex;flex-direction:column;
}
.ec-available-card:hover { box-shadow:0 8px 28px rgba(0,0,0,.09);transform:translateY(-3px); }

/* ── Empty ── */
.ec-empty { text-align:center;padding:3rem 1rem;background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px; }
.ec-empty svg { width:48px;height:48px;margin:0 auto .875rem;opacity:.25;display:block; }
.ec-empty h4 { font-size:1rem;font-weight:700;color:var(--ka-text,#1e293b);margin-bottom:.375rem; }
.ec-empty p  { font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin-bottom:1.25rem; }

/* ── Tab panes ── */
.ec-pane { display:none; }
.ec-pane.active { display:block; }
</style>

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
    ? round(array_sum(array_map(fn($c) => (int)($c->progress_pct ?? 0), $enrolled_courses)) / $total_enrolled)
    : 0;
  ?>
  <div class="ec-kpi">
    <div class="ec-kpi-icon" style="background:#eff6ff;color:#3b82f6;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
    </div>
    <div><div class="ec-kpi-val"><?= $total_enrolled ?></div><div class="ec-kpi-lbl">Enrolled Courses</div></div>
  </div>
  <div class="ec-kpi">
    <div class="ec-kpi-icon" style="background:#f0fdf4;color:#22c55e;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
    </div>
    <div><div class="ec-kpi-val"><?= $total_completed ?></div><div class="ec-kpi-lbl">Completed</div></div>
  </div>
  <div class="ec-kpi">
    <div class="ec-kpi-icon" style="background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
    </div>
    <div><div class="ec-kpi-val"><?= $in_progress ?></div><div class="ec-kpi-lbl">In Progress</div></div>
  </div>
  <div class="ec-kpi">
    <div class="ec-kpi-icon" style="background:#fffbeb;color:#f59f00;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div><div class="ec-kpi-val"><?= $overall_pct ?>%</div><div class="ec-kpi-lbl">Overall Progress</div></div>
  </div>
</div>

<!-- ══ Tabs ════════════════════════════════════════════════ -->
<div class="ec-tabs animate__animated animate__fadeInUp animate__fast" style="animation-delay:.05s;" id="ecTabs">
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
<div class="ec-filters animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s;">
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
        $pct      = (int)($course->progress_pct ?? 0);
        $modules  = (int)($course->module_count  ?? 0);
        $done     = (int)($course->modules_done  ?? 0);
        $cat_name = $course->category_name ?? 'General';

        if ($pct >= 100)    { $status = 'completed';  $badge_class = 'ec-badge-completed';  $badge_text = 'Completed'; }
        elseif ($pct > 0)   { $status = 'inprogress'; $badge_class = 'ec-badge-inprogress'; $badge_text = 'In Progress'; }
        else                { $status = 'notstarted'; $badge_class = 'ec-badge-notstarted'; $badge_text = 'Not Started'; }

        $fill_class = $pct >= 100 ? 'ec-prog-fill-completed' : ($pct > 0 ? 'ec-prog-fill-active' : 'ec-prog-fill-notstarted');
        $pct_color  = $pct >= 100 ? '#22c55e' : 'var(--ka-primary,#6dabcf)';

        $cta_text  = $pct >= 100 ? 'Review' : ($pct > 0 ? 'Continue' : 'Start');
        $cta_class = $pct >= 100 ? 'ec-cta-review' : ($pct > 0 ? 'ec-cta-continue' : 'ec-cta-start');
      ?>
      <div class="ec-course-card animate__animated animate__fadeInUp" style="animation-delay:<?= ($i % 8) * 0.04 ?>s;"
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
            <div class="ec-card-progress-top">
              <span class="ec-card-progress-lbl">Progress</span>
              <span class="ec-card-progress-pct" style="color:<?= $pct_color ?>;"><?= $pct ?>%</span>
            </div>
            <div class="ec-prog-bar">
              <div class="ec-prog-fill <?= $fill_class ?>" style="width:<?= $pct ?>%"></div>
            </div>
          </div>
          <div class="ec-card-footer">
            <a href="<?= base_url('my_courses/view/'.$course->course_id) ?>" class="ec-card-cta <?= $cta_class ?>">
              <?= $cta_text ?>
            </a>
            <?php if ($pct >= 100): ?>
            <a href="<?= base_url('certificates') ?>" style="padding:.5rem .75rem;border-radius:7px;background:#ecfdf5;color:#15803d;font-size:.6875rem;font-weight:700;text-decoration:none;white-space:nowrap;" title="View Certificate">
              🏆 Cert
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="ec-empty" style="grid-column:1/-1;">
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
      <div class="ec-available-card animate__animated animate__fadeInUp" style="animation-delay:<?= ($i % 8) * 0.04 ?>s;"
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
          <p style="font-size:.75rem;color:var(--ka-text-muted,#64748b);margin-bottom:.875rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
            <?= htmlspecialchars($course->description) ?>
          </p>
          <?php endif; ?>
          <div class="ec-card-footer">
            <a href="<?= base_url('courses/enroll/'.$course->id) ?>" class="ec-card-cta ec-cta-start">Enroll Now</a>
            <a href="<?= base_url('courses/view/'.$course->id) ?>" style="padding:.5rem .75rem;border-radius:7px;background:var(--ka-bg,#f8fafc);border:1px solid var(--ka-border,#e2e8f0);color:var(--ka-text-muted,#64748b);font-size:.75rem;font-weight:600;text-decoration:none;white-space:nowrap;transition:all .15s;">
              Details
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="ec-empty" style="grid-column:1/-1;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        <h4>No new courses available</h4>
        <p>You're enrolled in all available courses. Check back later!</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  // ── Tabs ─────────────────────────────────────────────────
  document.querySelectorAll('.ec-tab').forEach(tab => {
    tab.addEventListener('click', function () {
      document.querySelectorAll('.ec-tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.ec-pane').forEach(p => p.classList.remove('active'));
      this.classList.add('active');
      document.getElementById('pane-' + this.dataset.pane).classList.add('active');
    });
  });

  // ── Filters ──────────────────────────────────────────────
  function applyFilters() {
    const keyword  = document.getElementById('ecSearch').value.toLowerCase().trim();
    const cat      = document.getElementById('ecFilterCat').value;
    const progress = document.getElementById('ecFilterProgress').value;

    document.querySelectorAll('#enrolledGrid [data-title], #availableGrid [data-title]').forEach(el => {
      const title   = el.dataset.title    || '';
      const elCat   = el.dataset.cat      || '';
      const elProg  = el.dataset.progress || '';

      const matchTitle    = !keyword   || title.includes(keyword);
      const matchCat      = !cat       || elCat === cat;
      const matchProgress = !progress  || elProg === progress;

      el.style.display = (matchTitle && matchCat && matchProgress) ? '' : 'none';
    });
  }

  document.getElementById('ecSearch').addEventListener('input', applyFilters);
  document.getElementById('ecFilterCat').addEventListener('change', applyFilters);
  document.getElementById('ecFilterProgress').addEventListener('change', applyFilters);
});
</script>