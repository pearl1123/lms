<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$courses        = $courses        ?? [];
$categories     = $categories     ?? [];
$total_courses  = $total_courses  ?? count($courses);
$total_enrolled = $total_enrolled ?? 0;
$total_available= $total_available?? 0;
$keyword        = $keyword        ?? '';
$filter_cat     = $filter_cat     ?? '';
$user_role      = strtolower($user->role ?? 'employee');

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

<!-- ============================================================
     KABAGA ACADEMY — Course Catalog
============================================================ -->
<style>
/* ── Hero ── */
.cat-hero {
  background: linear-gradient(135deg, var(--ka-navy,#1a3a5c) 0%, #1e4976 55%, #2d6a9f 100%);
  border-radius: 16px;
  padding: 2rem 2rem 0;
  margin-bottom: 1.75rem;
  position: relative; overflow: hidden;
  box-shadow: 0 8px 32px rgba(26,58,92,.18);
}
.cat-hero::before {
  content:''; position:absolute; top:-80px; right:-60px;
  width:280px; height:280px; border-radius:50%;
  background:rgba(109,171,207,.1); pointer-events:none;
}
.cat-hero-body { position:relative; z-index:1; }
.cat-hero-eyebrow {
  font-size:.6875rem; font-weight:700; letter-spacing:.1em;
  text-transform:uppercase; color:rgba(255,255,255,.5); margin-bottom:.375rem;
}
.cat-hero-title {
  font-size:1.625rem; font-weight:800; color:#fff;
  margin:0 0 .375rem; letter-spacing:-.02em; line-height:1.2;
}
.cat-hero-sub { font-size:.875rem; color:rgba(255,255,255,.6); margin:0 0 1.5rem; }

/* Hero stat strip */
.cat-hero-stats {
  display:flex; border-top:1px solid rgba(255,255,255,.1);
  margin-top:1rem; padding-top:1rem; padding-bottom:1.25rem;
  gap:0;
}
.cat-hero-stat { flex:1; text-align:center; padding:0 .5rem; }
.cat-hero-stat + .cat-hero-stat { border-left:1px solid rgba(255,255,255,.1); }
.cat-hero-stat-val { font-size:1.375rem; font-weight:800; color:#fff; line-height:1; }
.cat-hero-stat-lbl { font-size:.6875rem; color:rgba(255,255,255,.5); margin-top:4px; font-weight:500; }

/* ── Filters bar ── */
.cat-filters {
  background:#fff; border:1px solid var(--ka-border,#e2e8f0);
  border-radius:12px; padding:.875rem 1rem;
  display:flex; align-items:center; gap:.75rem;
  flex-wrap:wrap; margin-bottom:1.25rem;
}
.cat-search-wrap { position:relative; flex:1; min-width:220px; }
.cat-search-icon {
  position:absolute; left:.75rem; top:50%; transform:translateY(-50%);
  width:15px; height:15px; color:var(--ka-text-muted,#64748b); pointer-events:none;
}
.cat-search-input {
  width:100%; height:38px; padding:0 .75rem 0 2.25rem;
  border:1.5px solid var(--ka-border,#e2e8f0); border-radius:8px;
  font-size:.8125rem; color:var(--ka-text,#1e293b);
  background:var(--ka-bg,#f8fafc); outline:none; transition:all .2s;
}
.cat-search-input:focus {
  border-color:var(--ka-primary,#6dabcf); background:#fff;
  box-shadow:0 0 0 3px rgba(109,171,207,.15);
}
.cat-select {
  height:38px; padding:0 .875rem;
  border:1.5px solid var(--ka-border,#e2e8f0); border-radius:8px;
  font-size:.8125rem; color:var(--ka-text,#1e293b);
  background:var(--ka-bg,#f8fafc); outline:none; cursor:pointer; transition:all .2s;
}
.cat-select:focus { border-color:var(--ka-primary,#6dabcf); }

/* Filter pills */
.cat-pills { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
.cat-pill {
  padding:5px 12px; border-radius:20px; font-size:.75rem; font-weight:600;
  cursor:pointer; transition:all .18s; border:1.5px solid var(--ka-border,#e2e8f0);
  background:#fff; color:var(--ka-text-muted,#64748b); white-space:nowrap;
}
.cat-pill:hover { border-color:var(--ka-primary,#6dabcf); color:var(--ka-primary,#6dabcf); }
.cat-pill.active {
  background:var(--ka-navy,#1a3a5c); border-color:var(--ka-navy,#1a3a5c); color:#fff;
}

/* View toggle */
.cat-view-toggle { display:flex; gap:4px; }
.cat-toggle-btn {
  width:34px; height:34px; border-radius:7px;
  border:1.5px solid var(--ka-border,#e2e8f0);
  background:transparent; cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  color:var(--ka-text-muted,#64748b); transition:all .15s;
}
.cat-toggle-btn.active,
.cat-toggle-btn:hover {
  background:var(--ka-navy,#1a3a5c);
  border-color:var(--ka-navy,#1a3a5c); color:#fff;
}
.cat-toggle-btn svg { width:15px; height:15px; }

.cat-result-count {
  font-size:.8125rem; font-weight:600;
  color:var(--ka-text-muted,#64748b); white-space:nowrap; margin-left:auto;
}

/* ── Grid layout ── */
.cat-grid {
  display:grid;
  grid-template-columns:repeat(auto-fill, minmax(290px, 1fr));
  gap:1.125rem; margin-bottom:1.5rem;
}

/* ── Course card ── */
.cat-card {
  background:#fff; border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px; overflow:hidden;
  display:flex; flex-direction:column;
  transition:box-shadow .2s, transform .2s;
}
.cat-card:hover { box-shadow:0 10px 32px rgba(0,0,0,.1); transform:translateY(-4px); }

.cat-card-thumb {
  height:140px; position:relative; overflow:hidden;
  display:flex; align-items:center; justify-content:center;
  flex-shrink:0;
}
.cat-card-thumb-icon { opacity:.2; }
.cat-card-thumb-icon svg { width:64px; height:64px; }

/* Enrolled ribbon */
.cat-enrolled-ribbon {
  position:absolute; top:10px; left:10px;
  display:flex; align-items:center; gap:5px;
  padding:4px 10px; border-radius:20px;
  font-size:.625rem; font-weight:700; backdrop-filter:blur(6px);
}
.cat-enrolled-ribbon.enrolled  { background:rgba(34,197,94,.9);  color:#fff; }
.cat-enrolled-ribbon.available { background:rgba(0,0,0,.3);       color:#fff; }
.cat-enrolled-ribbon svg { width:11px; height:11px; }

/* Progress overlay bar (for enrolled courses) */
.cat-card-progress-overlay {
  position:absolute; bottom:0; left:0; right:0; height:4px;
  background:rgba(255,255,255,.2);
}
.cat-card-progress-fill {
  height:100%; background:#fff; border-radius:0;
  transition:width .6s ease;
}

.cat-card-body { padding:1rem; flex:1; display:flex; flex-direction:column; }
.cat-card-cat {
  font-size:.625rem; font-weight:700; text-transform:uppercase;
  letter-spacing:.08em; color:var(--ka-primary,#6dabcf); margin-bottom:.375rem;
}
.cat-card-title {
  font-size:.9375rem; font-weight:700; color:var(--ka-text,#1e293b);
  line-height:1.35; margin-bottom:.625rem;
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}
.cat-card-desc {
  font-size:.75rem; color:var(--ka-text-muted,#64748b);
  line-height:1.5; margin-bottom:.75rem;
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
  flex:1;
}
.cat-card-meta {
  display:flex; align-items:center; gap:.875rem;
  margin-bottom:.875rem; flex-wrap:wrap;
}
.cat-card-meta-item {
  display:flex; align-items:center; gap:4px;
  font-size:.6875rem; color:var(--ka-text-muted,#64748b); font-weight:500;
}
.cat-card-meta-item svg { width:13px; height:13px; flex-shrink:0; }

/* Progress bar (inline, for enrolled) */
.cat-inline-progress { margin-bottom:.875rem; }
.cat-inline-progress-top {
  display:flex; justify-content:space-between;
  align-items:center; margin-bottom:4px;
}
.cat-inline-progress-lbl { font-size:.6875rem; color:var(--ka-text-muted,#64748b); font-weight:500; }
.cat-inline-progress-pct { font-size:.6875rem; font-weight:700; }
.cat-prog-bar { height:5px; background:var(--ka-border,#e2e8f0); border-radius:3px; overflow:hidden; }
.cat-prog-fill {
  height:100%; border-radius:3px;
  background:linear-gradient(90deg,var(--ka-primary,#6dabcf),#5a9ec1);
  transition:width .6s ease;
}
.cat-prog-fill.complete { background:linear-gradient(90deg,#22c55e,#15803d); }

.cat-card-footer {
  padding-top:.75rem; border-top:1px solid var(--ka-border,#e2e8f0);
  display:flex; align-items:center; gap:.5rem; margin-top:auto;
}
.cat-cta {
  flex:1; padding:.5rem; border-radius:7px; text-align:center;
  font-size:.8125rem; font-weight:700; text-decoration:none;
  transition:all .15s; border:none; cursor:pointer;
}
.cat-cta-enroll   { background:var(--ka-navy,#1a3a5c); color:#fff; }
.cat-cta-enroll:hover   { background:#254d75; color:#fff; transform:translateY(-1px); }
.cat-cta-continue { background:var(--ka-accent,#e8f4fd); color:var(--ka-primary-deep,#4a8eb0); }
.cat-cta-continue:hover { background:var(--ka-primary,#6dabcf); color:#fff; }
.cat-cta-review   { background:#ecfdf5; color:#15803d; }
.cat-cta-review:hover   { background:#22c55e; color:#fff; }
.cat-cta-view     { background:var(--ka-bg,#f8fafc); color:var(--ka-text-muted,#64748b); border:1px solid var(--ka-border,#e2e8f0); }
.cat-cta-view:hover     { border-color:var(--ka-primary,#6dabcf); color:var(--ka-primary,#6dabcf); }

/* Admin/Teacher: manage button */
.cat-cta-manage   { background:#fffbeb; color:#b45309; }
.cat-cta-manage:hover { background:#f59f00; color:#fff; }

/* ── List view ── */
.cat-list { display:flex; flex-direction:column; gap:.75rem; margin-bottom:1.5rem; }
.cat-list-item {
  background:#fff; border:1px solid var(--ka-border,#e2e8f0);
  border-radius:12px; padding:.875rem 1.125rem;
  display:flex; align-items:center; gap:1rem;
  transition:box-shadow .15s, transform .15s;
}
.cat-list-item:hover { box-shadow:0 4px 16px rgba(0,0,0,.07); transform:translateY(-1px); }
.cat-list-thumb {
  width:52px; height:52px; border-radius:10px; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  font-size:.75rem; font-weight:700; color:#fff;
}
.cat-list-body { flex:1; min-width:0; }
.cat-list-title {
  font-size:.9rem; font-weight:700; color:var(--ka-text,#1e293b);
  margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.cat-list-meta {
  display:flex; align-items:center; gap:.875rem; flex-wrap:wrap;
}
.cat-list-meta-item {
  display:flex; align-items:center; gap:4px;
  font-size:.6875rem; color:var(--ka-text-muted,#64748b);
}
.cat-list-meta-item svg { width:12px; height:12px; }
.cat-list-progress { width:120px; flex-shrink:0; }
.cat-list-actions { display:flex; align-items:center; gap:.5rem; flex-shrink:0; }
.cat-list-btn {
  padding:6px 14px; border-radius:7px; font-size:.75rem; font-weight:700;
  text-decoration:none; transition:all .15s; white-space:nowrap; border:none; cursor:pointer;
}

/* ── Empty state ── */
.cat-empty {
  text-align:center; padding:3.5rem 1rem;
  background:#fff; border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;
}
.cat-empty svg { width:52px; height:52px; margin:0 auto .875rem; opacity:.2; display:block; }
.cat-empty h4 { font-size:1rem; font-weight:700; color:var(--ka-text,#1e293b); margin-bottom:.375rem; }
.cat-empty p  { font-size:.8125rem; color:var(--ka-text-muted,#64748b); margin-bottom:0; }


</style>

<?php $this->load->view('layouts/alerts'); ?>


<!-- ══ Hero ═════════════════════════════════════════════════ -->
<div class="cat-hero animate__animated animate__fadeIn animate__fast">
  <div class="cat-hero-body">
    <p class="cat-hero-eyebrow">KABAGA Academy</p>
    <h2 class="cat-hero-title">Course Catalog</h2>
    <p class="cat-hero-sub">
      Discover and enroll in training programs designed for Lung Center employees
    </p>
  </div>
  <div class="cat-hero-stats">
    <div class="cat-hero-stat">
      <div class="cat-hero-stat-val"><?= $total_courses ?></div>
      <div class="cat-hero-stat-lbl">Total Courses</div>
    </div>
    <div class="cat-hero-stat">
      <div class="cat-hero-stat-val"><?= $total_enrolled ?></div>
      <div class="cat-hero-stat-lbl">Enrolled</div>
    </div>
    <div class="cat-hero-stat">
      <div class="cat-hero-stat-val"><?= $total_available ?></div>
      <div class="cat-hero-stat-lbl">Available</div>
    </div>
    <div class="cat-hero-stat">
      <div class="cat-hero-stat-val"><?= count($categories) ?></div>
      <div class="cat-hero-stat-lbl">Categories</div>
    </div>
  </div>
</div>

<!-- ══ Filters ══════════════════════════════════════════════ -->
<div class="cat-filters animate__animated animate__fadeInUp animate__fast">

  <!-- Search -->
  <div class="cat-search-wrap">
    <svg class="cat-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="cat-search-input" id="catSearch"
           placeholder="Search courses, topics, categories…"
           value="<?= htmlspecialchars($keyword) ?>">
  </div>

  <!-- Category filter -->
  <select class="cat-select" id="catFilterCat">
    <option value="">All Categories</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= $cat->id ?>" <?= $filter_cat == $cat->id ? 'selected' : '' ?>>
        <?= htmlspecialchars($cat->name) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <!-- Status filter -->
  <select class="cat-select" id="catFilterStatus">
    <option value="">All Courses</option>
    <option value="enrolled">Enrolled</option>
    <option value="available">Not Enrolled</option>
    <?php if ($user_role === 'employee'): ?>
    <option value="completed">Completed</option>
    <option value="inprogress">In Progress</option>
    <?php endif; ?>
  </select>

  <!-- Result count -->
  <span class="cat-result-count" id="catResultCount">
    <?= count($courses) ?> course<?= count($courses) !== 1 ? 's' : '' ?>
  </span>

  <!-- View toggle -->
  <div class="cat-view-toggle">
    <button class="cat-toggle-btn active" id="btnGrid" title="Grid view">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
    </button>
    <button class="cat-toggle-btn" id="btnList" title="List view">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
  </div>

</div>

<!-- Category pills -->
<div class="cat-pills animate__animated animate__fadeInUp animate__fast" style="margin-bottom:1.25rem;animation-delay:.05s;">
  <span class="cat-pill active" data-cat="">All</span>
  <?php foreach ($categories as $cat): ?>
    <span class="cat-pill" data-cat="<?= $cat->id ?>"><?= htmlspecialchars($cat->name) ?></span>
  <?php endforeach; ?>
</div>

<!-- ══ Grid View ════════════════════════════════════════════ -->
<div id="catGridView" class="cat-grid animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s;">
  <?php if ( ! empty($courses)): ?>
    <?php foreach ($courses as $i => $course):
      $grad      = $thumb_gradients[$i % count($thumb_gradients)];
      $initials  = strtoupper(substr($course->title, 0, 2));
      $cat_name  = $course->category_name ?? 'General';
      $modules   = (int)($course->total_modules  ?? 0);
      $enrolled  = (int)($course->total_enrolled ?? 0);
      $is_enr    = (bool)$course->is_enrolled;
      $my_pct    = (int)($course->my_progress ?? 0);

      // Status tag for filtering
      if ($is_enr && $my_pct >= 100) $status_tag = 'completed';
      elseif ($is_enr && $my_pct > 0) $status_tag = 'inprogress';
      elseif ($is_enr) $status_tag = 'enrolled';
      else $status_tag = 'available';

      // CTA
      if ($user_role === 'employee') {
        if ($my_pct >= 100)  { $cta_text = 'Review';   $cta_class = 'cat-cta-review'; }
        elseif ($is_enr)     { $cta_text = 'Continue'; $cta_class = 'cat-cta-continue'; }
        else                 { $cta_text = 'Enroll Now'; $cta_class = 'cat-cta-enroll'; }
      } else {
        $cta_text  = 'View Details';
        $cta_class = 'cat-cta-view';
      }
    ?>
    <div class="cat-card animate__animated animate__fadeInUp"
         style="animation-delay:<?= ($i % 8) * 0.04 ?>s;"
         data-title="<?= htmlspecialchars(strtolower($course->title)) ?>"
         data-cat="<?= (int)($course->category_id ?? 0) ?>"
         data-status="<?= $status_tag ?>">

      <div class="cat-card-thumb" style="background:<?= $grad ?>;">
        <div class="cat-card-thumb-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
        </div>

        <!-- Ribbon -->
        <?php if ($is_enr): ?>
        <span class="cat-enrolled-ribbon enrolled">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4"/></svg>
          <?= $my_pct >= 100 ? 'Completed' : 'Enrolled' ?>
        </span>
        <?php else: ?>
        <span class="cat-enrolled-ribbon available">Available</span>
        <?php endif; ?>

        <!-- Progress overlay for enrolled -->
        <?php if ($is_enr && $modules > 0): ?>
        <div class="cat-card-progress-overlay">
          <div class="cat-card-progress-fill" style="width:<?= $my_pct ?>%"></div>
        </div>
        <?php endif; ?>
      </div>

      <div class="cat-card-body">
        <div class="cat-card-cat"><?= htmlspecialchars($cat_name) ?></div>
        <div class="cat-card-title"><?= htmlspecialchars($course->title) ?></div>
        <?php if ( ! empty($course->description)): ?>
        <div class="cat-card-desc"><?= htmlspecialchars($course->description) ?></div>
        <?php endif; ?>

        <div class="cat-card-meta">
          <div class="cat-card-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <?= $modules ?> module<?= $modules !== 1 ? 's' : '' ?>
          </div>
          <div class="cat-card-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <?= $enrolled ?> enrolled
          </div>
          <?php if ( ! empty($course->creator_name)): ?>
          <div class="cat-card-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
            <?= htmlspecialchars($course->creator_name) ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Progress bar for enrolled employee -->
        <?php if ($is_enr && $user_role === 'employee' && $modules > 0): ?>
        <div class="cat-inline-progress">
          <div class="cat-inline-progress-top">
            <span class="cat-inline-progress-lbl">Your Progress</span>
            <span class="cat-inline-progress-pct" style="color:<?= $my_pct >= 100 ? '#22c55e' : 'var(--ka-primary,#6dabcf)' ?>">
              <?= $my_pct ?>%
            </span>
          </div>
          <div class="cat-prog-bar">
            <div class="cat-prog-fill <?= $my_pct >= 100 ? 'complete' : '' ?>" style="width:<?= $my_pct ?>%"></div>
          </div>
        </div>
        <?php endif; ?>

        <div class="cat-card-footer">
          <?php if ($user_role === 'employee' && ! $is_enr): ?>
            <a href="<?= base_url('courses/enroll/'.$course->id) ?>"
               class="cat-cta <?= $cta_class ?>"
               onclick="event.preventDefault(); KA.enrollConfirm(this.href, '<?= htmlspecialchars(addslashes($course->title), ENT_QUOTES) ?>')">
              <?= $cta_text ?>
            </a>
          <?php else: ?>
            <a href="<?= base_url('courses/view/'.$course->id) ?>"
               class="cat-cta <?= $cta_class ?>">
              <?= $cta_text ?>
            </a>
          <?php endif; ?>

          <a href="<?= base_url('courses/view/'.$course->id) ?>"
             class="cat-cta cat-cta-view" style="flex:none;padding:6px 12px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </a>

          <?php if (in_array($user_role, ['admin', 'teacher'])): ?>
          <a href="<?= base_url('manage_courses/edit/'.$course->id) ?>"
             class="cat-cta cat-cta-manage" style="flex:none;padding:6px 10px;font-size:.6875rem;">
            Edit
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="cat-empty" style="grid-column:1/-1;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
      <h4>No courses found</h4>
      <p>Try adjusting your search or filters.</p>
    </div>
  <?php endif; ?>
</div>

<!-- ══ List View (hidden by default) ════════════════════════ -->
<div id="catListView" style="display:none;" class="cat-list animate__animated animate__fadeIn">
  <?php if ( ! empty($courses)): ?>
    <?php foreach ($courses as $i => $course):
      $grad     = $thumb_gradients[$i % count($thumb_gradients)];
      $initials = strtoupper(substr($course->title, 0, 2));
      $cat_name = $course->category_name ?? 'General';
      $modules  = (int)($course->total_modules  ?? 0);
      $enrolled = (int)($course->total_enrolled ?? 0);
      $is_enr   = (bool)$course->is_enrolled;
      $my_pct   = (int)($course->my_progress ?? 0);

      if ($is_enr && $my_pct >= 100) $status_tag = 'completed';
      elseif ($is_enr && $my_pct > 0) $status_tag = 'inprogress';
      elseif ($is_enr) $status_tag = 'enrolled';
      else $status_tag = 'available';
    ?>
    <div class="cat-list-item"
         data-title="<?= htmlspecialchars(strtolower($course->title)) ?>"
         data-cat="<?= (int)($course->category_id ?? 0) ?>"
         data-status="<?= $status_tag ?>">

      <div class="cat-list-thumb" style="background:<?= $grad ?>"><?= $initials ?></div>

      <div class="cat-list-body">
        <div class="cat-list-title"><?= htmlspecialchars($course->title) ?></div>
        <div class="cat-list-meta">
          <div class="cat-list-meta-item" style="color:var(--ka-primary,#6dabcf);font-weight:600;">
            <?= htmlspecialchars($cat_name) ?>
          </div>
          <div class="cat-list-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <?= $modules ?> modules
          </div>
          <div class="cat-list-meta-item">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <?= $enrolled ?> enrolled
          </div>
          <?php if ($is_enr): ?>
          <div class="cat-list-meta-item" style="color:<?= $my_pct >= 100 ? '#22c55e' : 'var(--ka-primary,#6dabcf)' ?>;font-weight:600;">
            <?= $my_pct >= 100 ? '✓ Completed' : $my_pct.'% done' ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($is_enr && $user_role === 'employee' && $modules > 0): ?>
      <div class="cat-list-progress">
        <div class="cat-prog-bar">
          <div class="cat-prog-fill <?= $my_pct >= 100 ? 'complete' : '' ?>" style="width:<?= $my_pct ?>%"></div>
        </div>
        <div style="font-size:.625rem;color:var(--ka-text-muted,#64748b);margin-top:3px;text-align:right;"><?= $my_pct ?>%</div>
      </div>
      <?php endif; ?>

      <div class="cat-list-actions">
        <?php if ($user_role === 'employee' && ! $is_enr): ?>
          <a href="<?= base_url('courses/enroll/'.$course->id) ?>"
             class="cat-list-btn"
             style="background:var(--ka-navy,#1a3a5c);color:#fff;"
             onclick="event.preventDefault(); KA.enrollConfirm(this.href, '<?= htmlspecialchars(addslashes($course->title ?? 'this course'), ENT_QUOTES) ?>')">
            Enroll
          </a>
        <?php elseif ($is_enr): ?>
          <a href="<?= base_url('courses/view/'.$course->id) ?>"
             class="cat-list-btn"
             style="background:var(--ka-accent,#e8f4fd);color:var(--ka-primary-deep,#4a8eb0);">
            <?= $my_pct >= 100 ? 'Review' : 'Continue' ?>
          </a>
        <?php else: ?>
          <a href="<?= base_url('courses/view/'.$course->id) ?>"
             class="cat-list-btn"
             style="background:var(--ka-bg,#f8fafc);color:var(--ka-text-muted,#64748b);border:1px solid var(--ka-border,#e2e8f0);">
            View
          </a>
        <?php endif; ?>
        <?php if (in_array($user_role, ['admin', 'teacher'])): ?>
        <a href="<?= base_url('manage_courses/edit/'.$course->id) ?>"
           class="cat-list-btn"
           style="background:#fffbeb;color:#b45309;">
          Edit
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="cat-empty">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/></svg>
      <h4>No courses found</h4>
      <p>Try adjusting your search or filters.</p>
    </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  const gridView = document.getElementById('catGridView');
  const listView = document.getElementById('catListView');
  const btnGrid  = document.getElementById('btnGrid');
  const btnList  = document.getElementById('btnList');
  const countEl  = document.getElementById('catResultCount');

  // ── View toggle ──────────────────────────────────────────
  btnGrid.addEventListener('click', function () {
    gridView.style.display = '';
    listView.style.display = 'none';
    btnGrid.classList.add('active');
    btnList.classList.remove('active');
  });

  btnList.addEventListener('click', function () {
    gridView.style.display = 'none';
    listView.style.display = '';
    btnList.classList.add('active');
    btnGrid.classList.remove('active');
  });

  // ── Category pills ───────────────────────────────────────
  document.querySelectorAll('.cat-pill').forEach(pill => {
    pill.addEventListener('click', function () {
      document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
      this.classList.add('active');
      const cat = this.dataset.cat;
      document.getElementById('catFilterCat').value = cat;
      applyFilters();
    });
  });

  // Sync pill with select
  document.getElementById('catFilterCat').addEventListener('change', function () {
    document.querySelectorAll('.cat-pill').forEach(p => {
      p.classList.toggle('active', p.dataset.cat === this.value);
    });
    applyFilters();
  });

  // ── Filters ──────────────────────────────────────────────
  document.getElementById('catSearch').addEventListener('input', applyFilters);
  document.getElementById('catFilterStatus').addEventListener('change', applyFilters);

  function applyFilters() {
    const keyword = document.getElementById('catSearch').value.toLowerCase().trim();
    const cat     = document.getElementById('catFilterCat').value;
    const status  = document.getElementById('catFilterStatus').value;

    // Apply to both grid cards and list items
    const allItems = document.querySelectorAll(
      '#catGridView .cat-card, #catListView .cat-list-item'
    );

    let visible = 0;
    allItems.forEach(el => {
      const title    = el.dataset.title  || '';
      const elCat    = el.dataset.cat    || '';
      const elStatus = el.dataset.status || '';

      const matchTitle  = !keyword || title.includes(keyword);
      const matchCat    = !cat     || elCat === cat;
      const matchStatus = !status  || elStatus === status;

      const show = matchTitle && matchCat && matchStatus;
      el.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    // Divide by 2 because each course has a card AND a list item
    const unique = visible / 2;
    countEl.textContent = unique + ' course' + (unique !== 1 ? 's' : '');
  }

  // Pre-apply if URL had filters
  if (document.getElementById('catSearch').value ||
      document.getElementById('catFilterCat').value ||
      document.getElementById('catFilterStatus').value) {
    applyFilters();
  }
});
</script>