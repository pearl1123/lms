<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
$analytics        = $analytics ?? [];
$courses_enrolled = $analytics['courses_enrolled']  ?? 0;
$courses_completed= $analytics['courses_completed'] ?? 0;
$learning_hours   = $analytics['learning_hours']    ?? '0h';
$badges           = $analytics['badges']            ?? 0;
$full_name  = $user->fullname ?? 'Learner';
$first_name = explode(' ', trim($full_name))[0];
$top_learners = $top_learners ?? [];
?>

<?php $this->load->view('layouts/alerts'); ?>

<!-- ============================================================
     KABAGA ACADEMY — Employee / Student Dashboard
     Lung Center of the Philippines
============================================================ -->

<style>
/* ── Welcome Hero ─────────────────────────────────── */
.emp-hero {
  background: linear-gradient(135deg, var(--ka-navy,#1a3a5c) 0%, #1e4976 55%, #2d6a9f 100%);
  border-radius: 16px;
  padding: 1.75rem 2rem;
  margin-bottom: 1.75rem;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(26,58,92,.18);
}
.emp-hero::before {
  content:'';position:absolute;top:-80px;right:-80px;
  width:280px;height:280px;border-radius:50%;
  background:rgba(109,171,207,.1);pointer-events:none;
}
.emp-hero::after {
  content:'';position:absolute;bottom:-50px;right:220px;
  width:160px;height:160px;border-radius:50%;
  background:rgba(255,255,255,.04);pointer-events:none;
}
.emp-hero-inner { position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:1.5rem;flex-wrap:wrap; }
.emp-hero-left {}
.emp-hero-eyebrow { font-size:.6875rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.5);margin-bottom:.375rem; }
.emp-hero-title { font-size:1.5rem;font-weight:800;color:#fff;margin:0 0 .375rem;letter-spacing:-.02em;line-height:1.2; }
.emp-hero-sub { font-size:.875rem;color:rgba(255,255,255,.6);margin:0 0 1.25rem; }
.emp-cta-row { display:flex;gap:.625rem;flex-wrap:wrap; }
.emp-cta {
  display:inline-flex;align-items:center;gap:6px;
  padding:.5rem 1.125rem;border-radius:8px;font-size:.8125rem;
  font-weight:600;text-decoration:none;transition:all .2s;white-space:nowrap;
}
.emp-cta-primary { background:var(--ka-primary,#6dabcf);color:#fff;box-shadow:0 4px 14px rgba(109,171,207,.4); }
.emp-cta-primary:hover { background:#5a9ec1;color:#fff;transform:translateY(-1px); }
.emp-cta-ghost { background:rgba(255,255,255,.1);color:rgba(255,255,255,.85);border:1px solid rgba(255,255,255,.2); }
.emp-cta-ghost:hover { background:rgba(255,255,255,.18);color:#fff; }

/* Progress ring */
.emp-hero-ring { flex-shrink:0;display:flex;flex-direction:column;align-items:center;gap:.5rem; }
.emp-ring-wrap { position:relative;width:110px;height:110px; }
.emp-ring-wrap svg { transform:rotate(-90deg); }
.emp-ring-text { position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center; }
.emp-ring-pct  { font-size:1.375rem;font-weight:800;color:#fff;line-height:1; }
.emp-ring-lbl  { font-size:.625rem;color:rgba(255,255,255,.5);font-weight:600;text-transform:uppercase;letter-spacing:.06em; }
.emp-ring-caption { font-size:.6875rem;color:rgba(255,255,255,.5);font-weight:500; }

/* ── KPI Strip ── */
.emp-kpi-strip { display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.75rem; }
@media(max-width:1199.98px){.emp-kpi-strip{grid-template-columns:repeat(2,1fr);}}
@media(max-width:575.98px){.emp-kpi-strip{grid-template-columns:1fr;}}

.emp-kpi {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;padding:1.125rem 1.25rem;
  display:flex;align-items:center;gap:.875rem;
  transition:box-shadow .2s,transform .2s;
}
.emp-kpi:hover { box-shadow:0 8px 24px rgba(0,0,0,.07);transform:translateY(-2px); }
.emp-kpi-icon {
  width:44px;height:44px;border-radius:12px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
}
.emp-kpi-icon svg { width:20px;height:20px; }
.emp-kpi-val  { font-size:1.625rem;font-weight:800;color:var(--ka-text,#1e293b);line-height:1;letter-spacing:-.02em; }
.emp-kpi-lbl  { font-size:.75rem;color:var(--ka-text-muted,#64748b);font-weight:500;margin-top:3px; }

/* ── 2-col grid ── */
.emp-grid { display:grid;grid-template-columns:1fr 320px;gap:1.25rem;margin-bottom:1.25rem; }
@media(max-width:1099.98px){.emp-grid{grid-template-columns:1fr;}}

.emp-grid-equal { display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem; }
@media(max-width:991.98px){.emp-grid-equal{grid-template-columns:1fr;}}

/* ── Panel ── */
.emp-panel {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;overflow:hidden;
}
.emp-panel-hdr {
  display:flex;align-items:center;justify-content:space-between;
  padding:1rem 1.25rem;border-bottom:1px solid var(--ka-border,#e2e8f0);
}
.emp-panel-title { font-size:.9rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0; }
.emp-panel-link {
  font-size:.75rem;font-weight:600;color:var(--ka-primary,#6dabcf);
  text-decoration:none;display:flex;align-items:center;gap:4px;
}
.emp-panel-link:hover { color:var(--ka-primary-dark,#5a9ec1); }
.emp-panel-body { padding:1.25rem; }

/* ── Course cards ── */
.emp-course-grid { display:grid;grid-template-columns:1fr 1fr;gap:.875rem; }
@media(max-width:767.98px){.emp-course-grid{grid-template-columns:1fr;}}

.emp-course-card {
  border:1px solid var(--ka-border,#e2e8f0);border-radius:12px;
  overflow:hidden;transition:all .25s;cursor:pointer;text-decoration:none;display:block;
}
.emp-course-card:hover { box-shadow:0 8px 24px rgba(0,0,0,.1);transform:translateY(-3px); }
.emp-course-thumb {
  height:100px;display:flex;align-items:center;justify-content:center;
  position:relative;overflow:hidden;
}
.emp-course-thumb-badge {
  position:absolute;top:8px;right:8px;
  font-size:.625rem;font-weight:700;padding:3px 8px;border-radius:20px;
  background:rgba(0,0,0,.35);color:#fff;backdrop-filter:blur(4px);
}
.emp-course-body { padding:.875rem; }
.emp-course-name { font-size:.8125rem;font-weight:700;color:var(--ka-text,#1e293b);line-height:1.3;margin-bottom:.5rem; }
.emp-course-meta { display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem; }
.emp-course-instructor { font-size:.6875rem;color:var(--ka-text-muted,#64748b); }
.emp-course-pct-label { font-size:.6875rem;font-weight:700;color:var(--ka-primary,#6dabcf); }
.emp-mini-bar { height:5px;background:var(--ka-border,#e2e8f0);border-radius:3px;overflow:hidden; }
.emp-mini-fill { height:100%;border-radius:3px;background:linear-gradient(90deg,var(--ka-primary,#6dabcf),#5a9ec1); }

/* ── Lesson list ── */
.emp-lesson-list { list-style:none;margin:0;padding:0; }
.emp-lesson-item {
  display:flex;align-items:center;gap:12px;
  padding:.75rem 0;border-bottom:1px solid var(--ka-border,#e2e8f0);
  text-decoration:none;transition:background .15s;
}
.emp-lesson-item:last-child { border-bottom:none; }
.emp-lesson-item:hover .emp-lesson-name { color:var(--ka-primary,#6dabcf); }
.emp-lesson-num {
  width:28px;height:28px;border-radius:8px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:.6875rem;font-weight:700;
}
.emp-lesson-num.done  { background:#ecfdf5;color:#22c55e; }
.emp-lesson-num.next  { background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf); }
.emp-lesson-num.todo  { background:var(--ka-bg,#f8fafc);color:var(--ka-text-muted,#64748b); }
.emp-lesson-name { font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.emp-lesson-duration { font-size:.6875rem;color:var(--ka-text-muted,#64748b);white-space:nowrap; }

/* ── Leaderboard ── */
.emp-leader-list { list-style:none;margin:0;padding:0; }
.emp-leader-item {
  display:flex;align-items:center;gap:10px;
  padding:.625rem 0;border-bottom:1px solid var(--ka-border,#e2e8f0);
}
.emp-leader-item:last-child { border-bottom:none; }
.emp-leader-rank {
  width:22px;text-align:center;font-size:.75rem;
  font-weight:800;color:var(--ka-text-muted,#64748b);flex-shrink:0;
}
.emp-leader-rank.gold   { color:#f59f00; }
.emp-leader-rank.silver { color:#94a3b8; }
.emp-leader-rank.bronze { color:#c2855a; }
.emp-leader-avatar {
  width:30px;height:30px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,var(--ka-primary,#6dabcf),var(--ka-navy,#1a3a5c));
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-weight:700;font-size:.625rem;
}
.emp-leader-name  { flex:1;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b); }
.emp-leader-pts   { font-size:.75rem;font-weight:700;color:var(--ka-primary,#6dabcf); }

/* ── Badge shelf ── */
.emp-badge-shelf { display:flex;flex-wrap:wrap;gap:.75rem; }
.emp-badge-item  { display:flex;flex-direction:column;align-items:center;gap:5px;text-align:center; }
.emp-badge-icon  {
  width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:1.375rem;box-shadow:0 2px 12px rgba(0,0,0,.1);
  border:3px solid #fff;
}
.emp-badge-name  { font-size:.625rem;font-weight:700;color:var(--ka-text-muted,#64748b);max-width:60px;line-height:1.2; }
.emp-badge-locked { opacity:.35;filter:grayscale(1); }

/* ── Announcement card ── */
.emp-announce-list { list-style:none;margin:0;padding:0; }
.emp-announce-item {
  display:flex;gap:12px;padding:.875rem 0;
  border-bottom:1px solid var(--ka-border,#e2e8f0);
}
.emp-announce-item:last-child { border-bottom:none; }
.emp-announce-icon {
  width:36px;height:36px;border-radius:9px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
}
.emp-announce-icon svg { width:15px;height:15px; }
.emp-announce-body { flex:1;min-width:0; }
.emp-announce-title { font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);margin-bottom:2px; }
.emp-announce-desc  { font-size:.75rem;color:var(--ka-text-muted,#64748b);line-height:1.4; }
.emp-announce-time  { font-size:.6875rem;color:var(--ka-text-muted,#64748b);margin-top:4px;font-weight:500; }
</style>

<!-- ══ Hero ═══════════════════════════════════════════════ -->
<div class="emp-hero animate__animated animate__fadeIn animate__fast">
  <div class="emp-hero-inner">
    <div class="emp-hero-left">
      <p class="emp-hero-eyebrow">My Learning Dashboard</p>
      <h2 class="emp-hero-title">Welcome back, <?= htmlspecialchars($first_name) ?> 👋</h2>
      <p class="emp-hero-sub">Keep the momentum going — you're doing great! <?= date('l, F j, Y') ?></p>
      <div class="emp-cta-row">
        <a href="<?= base_url('index.php/my_courses') ?>" class="emp-cta emp-cta-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Continue Learning
        </a>
        <a href="<?= base_url('index.php/courses') ?>" class="emp-cta emp-cta-ghost">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
          Browse Courses
        </a>
      </div>
    </div>

    <!-- Overall progress ring -->
    <div class="emp-hero-ring">
      <?php
        $pct = $courses_enrolled > 0 ? round(($courses_completed / $courses_enrolled) * 100) : 0;
        $r   = 46; $circ = 2 * M_PI * $r;
        $dash = $circ * ($pct / 100);
      ?>
      <div class="emp-ring-wrap">
        <svg width="110" height="110" viewBox="0 0 110 110">
          <circle cx="55" cy="55" r="<?= $r ?>" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="8"/>
          <circle cx="55" cy="55" r="<?= $r ?>" fill="none" stroke="var(--ka-primary,#6dabcf)" stroke-width="8"
            stroke-dasharray="<?= round($dash,2) ?> <?= round($circ,2) ?>"
            stroke-linecap="round" style="transition:stroke-dasharray .6s ease;"/>
        </svg>
        <div class="emp-ring-text">
          <span class="emp-ring-pct"><?= $pct ?>%</span>
          <span class="emp-ring-lbl">Done</span>
        </div>
      </div>
      <div class="emp-ring-caption"><?= $courses_completed ?> / <?= $courses_enrolled ?> courses</div>
    </div>
  </div>
</div>

<!-- ══ KPI Strip ════════════════════════════════════════════ -->
<div class="emp-kpi-strip">

  <div class="emp-kpi animate__animated animate__fadeInUp animate__fast">
    <div class="emp-kpi-icon" style="background:#eff6ff;color:#3b82f6;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
    </div>
    <div>
      <div class="emp-kpi-val"><?= $courses_enrolled ?></div>
      <div class="emp-kpi-lbl">Enrolled Courses</div>
    </div>
  </div>

  <div class="emp-kpi animate__animated animate__fadeInUp animate__fast" style="animation-delay:.05s">
    <div class="emp-kpi-icon" style="background:#f0fdf4;color:#22c55e;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
    </div>
    <div>
      <div class="emp-kpi-val"><?= $courses_completed ?></div>
      <div class="emp-kpi-lbl">Completed</div>
    </div>
  </div>

  <div class="emp-kpi animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s">
    <div class="emp-kpi-icon" style="background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div>
      <div class="emp-kpi-val"><?= htmlspecialchars($learning_hours) ?></div>
      <div class="emp-kpi-lbl">Learning Hours</div>
    </div>
  </div>

  <div class="emp-kpi animate__animated animate__fadeInUp animate__fast" style="animation-delay:.15s">
    <div class="emp-kpi-icon" style="background:#fffbeb;color:#f59f00;">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14l-5-4.87 6.91-1.01z"/></svg>
    </div>
    <div>
      <div class="emp-kpi-val"><?= $badges ?></div>
      <div class="emp-kpi-lbl">Badges Earned</div>
    </div>
  </div>

</div>

<!-- ══ Main 2-col ════════════════════════════════════════════ -->
<div class="emp-grid">

  <!-- My Courses -->
  <div class="emp-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.2s;">
    <div class="emp-panel-hdr">
      <h3 class="emp-panel-title">My Active Courses</h3>
      <a href="<?= base_url('index.php/my_courses') ?>" class="emp-panel-link">
        View all
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </a>
    </div>
    <div class="emp-panel-body">
      <div class="emp-course-grid">

        <a href="<?= base_url('index.php/my_courses') ?>" class="emp-course-card">
          <div class="emp-course-thumb" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="1.5"><path d="M9 12l2 2 4-4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            <span class="emp-course-thumb-badge">In Progress</span>
          </div>
          <div class="emp-course-body">
            <div class="emp-course-name">Basic Infection Control</div>
            <div class="emp-course-meta">
              <span class="emp-course-instructor">Dr. Santos</span>
              <span class="emp-course-pct-label">72%</span>
            </div>
            <div class="emp-mini-bar"><div class="emp-mini-fill" style="width:72%"></div></div>
          </div>
        </a>

        <a href="<?= base_url('index.php/my_courses') ?>" class="emp-course-card">
          <div class="emp-course-thumb" style="background:linear-gradient(135deg,#22c55e,#15803d);">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <span class="emp-course-thumb-badge">In Progress</span>
          </div>
          <div class="emp-course-body">
            <div class="emp-course-name">Patient Safety Module</div>
            <div class="emp-course-meta">
              <span class="emp-course-instructor">Prof. Reyes</span>
              <span class="emp-course-pct-label">45%</span>
            </div>
            <div class="emp-mini-bar"><div class="emp-mini-fill" style="width:45%"></div></div>
          </div>
        </a>

        <a href="<?= base_url('index.php/my_courses') ?>" class="emp-course-card">
          <div class="emp-course-thumb" style="background:linear-gradient(135deg,var(--ka-primary,#6dabcf),var(--ka-navy,#1a3a5c));">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            <span class="emp-course-thumb-badge">Not Started</span>
          </div>
          <div class="emp-course-body">
            <div class="emp-course-name">Respiratory Care Basics</div>
            <div class="emp-course-meta">
              <span class="emp-course-instructor">Dr. Cruz</span>
              <span class="emp-course-pct-label">0%</span>
            </div>
            <div class="emp-mini-bar"><div class="emp-mini-fill" style="width:0%"></div></div>
          </div>
        </a>

        <a href="<?= base_url('index.php/my_courses') ?>" class="emp-course-card">
          <div class="emp-course-thumb" style="background:linear-gradient(135deg,#f59f00,#b45309);">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="1.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            <span class="emp-course-thumb-badge">Completed ✓</span>
          </div>
          <div class="emp-course-body">
            <div class="emp-course-name">Fire Safety &amp; Evacuation</div>
            <div class="emp-course-meta">
              <span class="emp-course-instructor">Eng. Lim</span>
              <span class="emp-course-pct-label" style="color:#22c55e;">100%</span>
            </div>
            <div class="emp-mini-bar"><div class="emp-mini-fill" style="width:100%;background:linear-gradient(90deg,#22c55e,#15803d);"></div></div>
          </div>
        </a>

      </div>
    </div>
  </div>

  <!-- Right: Leaderboard + Badges -->
  <div style="display:flex;flex-direction:column;gap:1.25rem;">

    <!-- Leaderboard -->
    <div class="emp-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.25s;">
      <div class="emp-panel-hdr">
        <h3 class="emp-panel-title">🏆 Leaderboard</h3>
        <a href="<?= base_url('index.php/leaderboard') ?>" class="emp-panel-link">Full board</a>
      </div>
      <div class="emp-panel-body" style="padding-top:.5rem;padding-bottom:.5rem;">
        <ul class="emp-leader-list">
          <?php
          $leaders = !empty($top_learners) ? $top_learners : [
            ['name'=>'Alice Mendoza','points'=>980],
            ['name'=>'John Bautista','points'=>870],
            ['name'=>'Maria Garcia', 'points'=>850],
            ['name'=>'Carlos Reyes', 'points'=>820],
            ['name'=>'Ana Santos',   'points'=>790],
          ];
          $rank_class = ['gold','silver','bronze','',''];
          $rank_icon  = ['🥇','🥈','🥉','4','5'];
          foreach($leaders as $i => $l):
            $initials = implode('', array_map(function($w) { return $w !== '' ? strtoupper($w[0]) : ''; }, explode(' ', trim($l['name']))));
            $initials = substr($initials,0,2);
          ?>
          <li class="emp-leader-item">
            <span class="emp-leader-rank <?= $rank_class[$i] ?? '' ?>"><?= $rank_icon[$i] ?? ($i+1) ?></span>
            <div class="emp-leader-avatar"><?= $initials ?></div>
            <span class="emp-leader-name"><?= htmlspecialchars($l['name']) ?></span>
            <span class="emp-leader-pts"><?= number_format($l['points']) ?> pts</span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <!-- Badges -->
    <div class="emp-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.3s;">
      <div class="emp-panel-hdr">
        <h3 class="emp-panel-title">My Badges</h3>
        <a href="<?= base_url('index.php/certificates') ?>" class="emp-panel-link">See all</a>
      </div>
      <div class="emp-panel-body">
        <div class="emp-badge-shelf">
          <div class="emp-badge-item">
            <div class="emp-badge-icon" style="background:linear-gradient(135deg,#f59f00,#b45309);">🔥</div>
            <div class="emp-badge-name">7-Day Streak</div>
          </div>
          <div class="emp-badge-item">
            <div class="emp-badge-icon" style="background:linear-gradient(135deg,#22c55e,#15803d);">✅</div>
            <div class="emp-badge-name">First Complete</div>
          </div>
          <div class="emp-badge-item">
            <div class="emp-badge-icon" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">📚</div>
            <div class="emp-badge-name">Bookworm</div>
          </div>
          <div class="emp-badge-item emp-badge-locked">
            <div class="emp-badge-icon" style="background:linear-gradient(135deg,#94a3b8,#64748b);">🏅</div>
            <div class="emp-badge-name">Top Learner</div>
          </div>
          <div class="emp-badge-item emp-badge-locked">
            <div class="emp-badge-icon" style="background:linear-gradient(135deg,#94a3b8,#64748b);">🎓</div>
            <div class="emp-badge-name">Graduate</div>
          </div>
        </div>
      </div>
    </div>

  </div>

</div><!-- /emp-grid -->

<!-- ══ Lower Row ════════════════════════════════════════════ -->
<div class="emp-grid-equal">

  <!-- Next Up: Lessons -->
  <div class="emp-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.35s;">
    <div class="emp-panel-hdr">
      <h3 class="emp-panel-title">Up Next — Infection Control</h3>
      <a href="<?= base_url('index.php/my_courses') ?>" class="emp-panel-link">Open course</a>
    </div>
    <div class="emp-panel-body" style="padding-top:.25rem;padding-bottom:.25rem;">
      <?php
      $lessons = !empty($lessons) ? $lessons : [
        'Introduction to Infection Control',
        'Hand Hygiene Protocols',
        'PPE Usage & Standards',
        'Isolation Precautions',
        'Final Assessment',
      ];
      $states = ['done','done','next','todo','todo'];
      $durations = ['8 min','12 min','15 min','10 min','20 min'];
      ?>
      <ul class="emp-lesson-list">
        <?php foreach($lessons as $i => $lesson):
          $state = $states[$i] ?? 'todo';
          $icon  = $state==='done' ? '✓' : ($state==='next' ? '▶' : ($i+1));
        ?>
        <li class="emp-lesson-item">
          <div class="emp-lesson-num <?= $state ?>"><?= $icon ?></div>
          <span class="emp-lesson-name"><?= htmlspecialchars($lesson) ?></span>
          <span class="emp-lesson-duration"><?= $durations[$i] ?? '' ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <!-- Announcements -->
  <div class="emp-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.4s;">
    <div class="emp-panel-hdr">
      <h3 class="emp-panel-title">Announcements</h3>
      <a href="<?= base_url('index.php/announcements') ?>" class="emp-panel-link">View all</a>
    </div>
    <div class="emp-panel-body" style="padding-top:.25rem;padding-bottom:.25rem;">
      <ul class="emp-announce-list">
        <li class="emp-announce-item">
          <div class="emp-announce-icon" style="background:#fef2f2;color:#ef4444;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          </div>
          <div class="emp-announce-body">
            <div class="emp-announce-title">Mandatory Training Deadline</div>
            <div class="emp-announce-desc">Complete Infection Control by March 31.</div>
            <div class="emp-announce-time">2 hours ago</div>
          </div>
        </li>
        <li class="emp-announce-item">
          <div class="emp-announce-icon" style="background:#eff6ff;color:#3b82f6;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
          </div>
          <div class="emp-announce-body">
            <div class="emp-announce-title">New Course Available</div>
            <div class="emp-announce-desc">Respiratory Care Basics is now live.</div>
            <div class="emp-announce-time">Yesterday</div>
          </div>
        </li>
        <li class="emp-announce-item">
          <div class="emp-announce-icon" style="background:#f0fdf4;color:#22c55e;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
          </div>
          <div class="emp-announce-body">
            <div class="emp-announce-title">Certificates Ready</div>
            <div class="emp-announce-desc">Download your Fire Safety certificate now.</div>
            <div class="emp-announce-time">2 days ago</div>
          </div>
        </li>
        <li class="emp-announce-item">
          <div class="emp-announce-icon" style="background:#fffbeb;color:#f59f00;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <div class="emp-announce-body">
            <div class="emp-announce-title">Live Webinar — Apr 5</div>
            <div class="emp-announce-desc">Join the Q&amp;A session on Patient Safety.</div>
            <div class="emp-announce-time">3 days ago</div>
          </div>
        </li>
      </ul>
    </div>
  </div>

</div><!-- /emp-grid-equal -->

<!-- learning activity chart -->
<div class="emp-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.45s;margin-bottom:1.25rem;">
  <div class="emp-panel-hdr">
    <h3 class="emp-panel-title">My Learning Activity</h3>
    <span style="font-size:.75rem;color:var(--ka-text-muted,#64748b);font-weight:500;">Last 7 days</span>
  </div>
  <div class="emp-panel-body">
    <div id="emp-activity-chart" style="height:160px;"></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const ctx = document.getElementById('emp-activity-chart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
      datasets: [{
        label: 'Minutes',
        data: [25, 40, 15, 55, 30, 10, 45],
        backgroundColor: '#6dabcf99',
        borderColor: '#6dabcf',
        borderWidth: 2,
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false },
        tooltip: { backgroundColor: '#1a3a5c', cornerRadius: 8, padding: 10,
          callbacks: { label: ctx => ` ${ctx.raw} minutes studied` } }
      },
      scales: {
        x: { grid: { display: false }, border: { display: false },
          ticks: { font: { size: 11 }, color: '#94a3b8' } },
        y: { grid: { color: '#e2e8f0' }, border: { display: false, dash: [4,4] },
          ticks: { font: { size: 11 }, color: '#94a3b8' } }
      }
    }
  });
});
</script>