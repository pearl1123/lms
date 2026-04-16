<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Prepared by ka_merge_layout_vars() (see KA_Controller::render / controllers loading layouts/main)
$nc        = $nav_context ?? [];
// First URI segment only — strict equality for active states (my_courses ≠ courses).
$segment   = isset($nc['segment_1']) ? trim((string) $nc['segment_1']) : '';
$seg2      = isset($nc['segment_2']) ? trim((string) $nc['segment_2']) : '';
$user_role        = $nc['user_role'] ?? 'employee';
$user_role_label  = $nc['user_role_label'] ?? 'Employee';
$full_name        = $nc['full_name'] ?? 'User';
$emp_id           = $nc['employee_id'] ?? '';
$initials         = $nc['initials'] ?? '';
$my_courses_label = $nc['my_courses_label'] ?? 'My Courses';
?>

<!-- ============================================================
     KABAGA ACADEMY — Sidebar Navigation
============================================================ -->

<style>
  .ka-sidebar {
    position: fixed; top: 0; left: 0;
    width: var(--ka-sidebar-width, 260px);
    height: 100vh;
    background: var(--ka-sidebar-bg, #1a3a5c);
    display: flex; flex-direction: column;
    z-index: 1040; overflow: hidden;
    transition: width 0.3s cubic-bezier(0.4,0,0.2,1), transform 0.3s ease;
    box-shadow: 4px 0 24px rgba(0,0,0,0.12);
  }
  .ka-sidebar-brand {
    display: flex; align-items: center; gap: 10px;
    padding: 1.125rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    text-decoration: none;
    min-height: var(--ka-header-height, 64px);
    flex-shrink: 0;
  }
  .ka-sidebar-brand-logo {
    width: 38px; height: 38px; border-radius: 10px;
    background: rgba(255,255,255,0.12);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden;
    border: 1px solid rgba(255,255,255,0.15);
  }
  .ka-sidebar-brand-logo img { width:100%; height:100%; object-fit:contain; padding:4px; }
  .ka-sidebar-brand-text { display:flex; flex-direction:column; overflow:hidden; }
  .ka-sidebar-brand-name {
    font-size:0.9375rem; font-weight:700; color:#fff;
    white-space:nowrap; letter-spacing:-0.01em; line-height:1.2;
  }
  .ka-sidebar-brand-sub {
    font-size:0.6875rem; color:rgba(255,255,255,0.5);
    white-space:nowrap; letter-spacing:0.02em; line-height:1.3;
  }
  .ka-sidebar-scroll {
    flex:1; overflow-y:auto; overflow-x:hidden; padding:1rem 0;
  }
  .ka-sidebar-scroll::-webkit-scrollbar { width:3px; }
  .ka-sidebar-scroll::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.15); border-radius:2px; }
  .ka-nav-section {
    padding:0.625rem 1.25rem 0.25rem;
    font-size:0.6875rem; font-weight:700;
    text-transform:uppercase; letter-spacing:0.1em;
    color:rgba(255,255,255,0.35);
  }
  .ka-nav-list { list-style:none; margin:0; padding:0 0.625rem; }
  .ka-nav-item { margin-bottom:2px; }
  .ka-nav-link {
    display:flex; align-items:center; gap:10px;
    padding:0.5625rem 0.875rem; border-radius:9px;
    color:rgba(255,255,255,0.7); font-size:0.875rem;
    font-weight:500; text-decoration:none;
    transition:all 0.18s ease; position:relative; white-space:nowrap;
  }
  .ka-nav-link:hover { background:rgba(255,255,255,0.08); color:#fff; }
  .ka-nav-link.active {
    background:rgba(109,171,207,0.22);
    color:var(--ka-primary,#6dabcf); font-weight:600;
  }
  .ka-nav-link.active::before {
    content:''; position:absolute; left:0; top:50%;
    transform:translateY(-50%);
    width:3px; height:20px;
    background:var(--ka-primary,#6dabcf); border-radius:0 2px 2px 0;
  }
  .ka-nav-icon { width:18px; height:18px; flex-shrink:0; opacity:0.75; transition:opacity 0.18s; }
  .ka-nav-link:hover .ka-nav-icon,
  .ka-nav-link.active .ka-nav-icon { opacity:1; }
  .ka-nav-badge {
    margin-left:auto;
    background:var(--ka-primary,#6dabcf); color:#fff;
    font-size:0.6875rem; font-weight:700;
    padding:1px 7px; border-radius:10px; line-height:1.5;
  }
  .ka-nav-badge.new { background:#e8f4fd; color:#1a3a5c; }
  .ka-nav-submenu { list-style:none; margin:2px 0 4px; padding:0 0 0 1.75rem; }
  .ka-nav-submenu .ka-nav-link {
    padding:0.4375rem 0.75rem; font-size:0.8125rem; color:rgba(255,255,255,0.55);
  }
  .ka-nav-submenu .ka-nav-link:hover { color:#fff; background:rgba(255,255,255,0.06); }
  .ka-nav-submenu .ka-nav-link.active { color:var(--ka-primary,#6dabcf); background:rgba(109,171,207,0.15); }
  .ka-nav-submenu .ka-nav-link.active::before { display:none; }
  .ka-nav-caret {
    margin-left:auto; width:14px; height:14px;
    transition:transform 0.25s ease; opacity:0.5;
  }
  .ka-nav-link[aria-expanded="true"] .ka-nav-caret { transform:rotate(90deg); opacity:0.8; }
  .ka-sidebar-user {
    border-top:1px solid rgba(255,255,255,0.08);
    padding:0.875rem 1rem;
    display:flex; align-items:center; gap:10px;
    flex-shrink:0; cursor:pointer; text-decoration:none;
    transition:background 0.15s;
  }
  .ka-sidebar-user:hover { background:rgba(255,255,255,0.06); }
  .ka-sidebar-user-avatar {
    width:34px; height:34px; border-radius:50%;
    background:linear-gradient(135deg,var(--ka-primary,#6dabcf),#4a8eb0);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:700; font-size:0.75rem;
    flex-shrink:0; border:2px solid rgba(255,255,255,0.2);
  }
  .ka-sidebar-user-info { flex:1; overflow:hidden; }
  .ka-sidebar-user-name {
    font-size:0.8125rem; font-weight:600; color:#fff;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.3;
  }
  .ka-sidebar-user-role {
    font-size:0.6875rem; color:rgba(255,255,255,0.45);
    text-transform:capitalize; line-height:1.3;
  }
  .ka-sidebar-user-dots { width:16px; height:16px; color:rgba(255,255,255,0.35); flex-shrink:0; }
  .ka-sidebar-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,0.4); z-index:1039; backdrop-filter:blur(2px);
  }
  .ka-sidebar-overlay.show { display:block; }
  .ka-main-wrap {
    margin-left:var(--ka-sidebar-width,260px);
    min-height:100vh; display:flex; flex-direction:column;
    transition:margin-left 0.3s ease;
  }
  @media (max-width:991.98px) {
    .ka-sidebar { transform:translateX(-100%); }
    .ka-sidebar.open { transform:translateX(0); }
    .ka-main-wrap { margin-left:0; }
  }
</style>

<div class="ka-sidebar-overlay" id="kaSidebarOverlay" onclick="kaToggleSidebar()"></div>

<aside class="ka-sidebar" id="kaSidebar">

  <!-- Brand -->
  <a href="<?= base_url('index.php/dashboard') ?>" class="ka-sidebar-brand">
    <div class="ka-sidebar-brand-logo">
      <img src="<?= base_url('assets/tabler/img/logo.png') ?>" alt="KABAGA Academy">
    </div>
    <div class="ka-sidebar-brand-text">
      <span class="ka-sidebar-brand-name">KABAGA Academy</span>
      <span class="ka-sidebar-brand-sub">Lung Center of the Philippines</span>
    </div>
  </a>

  <nav class="ka-sidebar-scroll">

    <!-- ── MAIN ── -->
    <p class="ka-nav-section">Main</p>
    <ul class="ka-nav-list">

      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/dashboard') ?>"
           class="ka-nav-link <?= $segment === 'dashboard' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
          </svg>
          Dashboard
        </a>
      </li>

      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/my_courses') ?>"
           class="ka-nav-link <?= $segment === 'my_courses' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
          </svg>
          <?= htmlspecialchars($my_courses_label) ?>
        </a>
      </li>

      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/courses') ?>"
           class="ka-nav-link <?= $segment === 'courses' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
          Course Catalog
        </a>
      </li>

      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/assessments') ?>"
           class="ka-nav-link <?= $segment === 'assessments' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 11l3 3L22 4"/>
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
          </svg>
          Assessments
        </a>
      </li>

      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/certificates') ?>"
           class="ka-nav-link <?= $segment === 'certificates' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="8" r="6"/>
            <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
          </svg>
          Certificates
        </a>
      </li>

      <?php if (in_array($user_role, ['admin', 'teacher'])): ?>
      <!-- Libraries menu -->
      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/libraries') ?>"
           class="ka-nav-link <?= $segment === 'libraries' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 19h16M4 14h16M4 9h16M4 4h16"/>
          </svg>
          Libraries
        </a>
      </li>
      <?php endif; ?>

    </ul>

    <!-- ── PROGRESS ── -->
    <p class="ka-nav-section">Progress</p>
    <ul class="ka-nav-list">

      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/progress') ?>"
           class="ka-nav-link <?= $segment === 'progress' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="20" x2="18" y2="10"/>
            <line x1="12" y1="20" x2="12" y2="4"/>
            <line x1="6"  y1="20" x2="6"  y2="14"/>
          </svg>
          My Progress
        </a>
      </li>

      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/leaderboard') ?>"
           class="ka-nav-link <?= $segment === 'leaderboard' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M8 21h8M12 21v-4M17 8h.01"/>
            <path d="M5 8H3a2 2 0 0 0-2 2v1a2 2 0 0 0 2 2h2M19 8h2a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2h-2M5 8V6a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a4 4 0 0 1-4 4H9a4 4 0 0 1-4-4V8z"/>
          </svg>
          Leaderboard
        </a>
      </li>

      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/announcements') ?>"
           class="ka-nav-link <?= $segment === 'announcements' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          Announcements
          <?php if ( ! empty($unread_notification_count) && (int)$unread_notification_count > 0): ?>
          <span class="ka-nav-badge" style="background:#dc2626;color:#fff;">
            <?= (int)$unread_notification_count > 99 ? '99+' : (int)$unread_notification_count ?>
          </span>
          <?php endif; ?>
        </a>
      </li>

    </ul>

    <!-- ── MANAGEMENT — admin & teacher only ── -->
    <?php if (in_array($user_role, ['admin', 'teacher'])): ?>
    <p class="ka-nav-section">Management</p>
    <ul class="ka-nav-list">

      <?php if ($user_role === 'admin'): ?>
      <li class="ka-nav-item">
        <a href="#manageCoursesMenu"
           class="ka-nav-link <?= in_array($segment, ['manage_courses', 'categories']) ? 'active' : '' ?>"
           data-bs-toggle="collapse"
           aria-expanded="<?= in_array($segment, ['manage_courses', 'categories']) ? 'true' : 'false' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
          </svg>
          Manage Courses
          <svg class="ka-nav-caret" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="9 18 15 12 9 6"/>
          </svg>
        </a>
        <ul class="ka-nav-submenu collapse <?= in_array($segment, ['manage_courses', 'categories']) ? 'show' : '' ?>"
            id="manageCoursesMenu">
          <li>
            <a href="<?= base_url('index.php/manage_courses') ?>"
               class="ka-nav-link <?= ($segment === 'manage_courses' && ! $seg2) ? 'active' : '' ?>">
              All Courses
            </a>
          </li>
          <li>
            <a href="<?= base_url('index.php/manage_courses/create') ?>"
               class="ka-nav-link <?= ($segment === 'manage_courses' && $seg2 === 'create') ? 'active' : '' ?>">
              Create Course
            </a>
          </li>
          <li>
            <a href="<?= base_url('index.php/categories') ?>"
               class="ka-nav-link <?= $segment === 'categories' ? 'active' : '' ?>">
              Categories
            </a>
          </li>
        </ul>
      </li>

      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/users') ?>"
           class="ka-nav-link <?= $segment === 'users' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
          Users &amp; Enrollees
        </a>
      </li>

      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/reports') ?>"
           class="ka-nav-link <?= $segment === 'reports' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
          </svg>
          Reports &amp; Analytics
        </a>
      </li>
      <?php endif; ?>

      <?php if ($user_role === 'teacher'): ?>
      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/my_classes') ?>"
           class="ka-nav-link <?= $segment === 'my_classes' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8"  y1="2" x2="8"  y2="6"/>
            <line x1="3"  y1="10" x2="21" y2="10"/>
          </svg>
          My Classes
        </a>
      </li>
      <?php endif; ?>

      <?php if (in_array($user_role, ['admin', 'teacher'])): ?>
      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/enrollments/requests') ?>"
           class="ka-nav-link <?= ($segment === 'enrollments' && $seg2 === 'requests') ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="8.5" cy="7" r="4"/>
            <path d="M20 8v6M23 11h-6"/>
          </svg>
          Enrollment Requests
        </a>
      </li>
      <?php endif; ?>

    </ul>
    <?php endif; ?>

    <!-- ── ACCOUNT ── -->
    <p class="ka-nav-section">Account</p>
    <ul class="ka-nav-list">

      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/profile') ?>"
           class="ka-nav-link <?= $segment === 'profile' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
          My Profile
        </a>
      </li>

      <li class="ka-nav-item">
        <a href="<?= base_url('index.php/settings') ?>"
           class="ka-nav-link <?= $segment === 'settings' ? 'active' : '' ?>">
          <svg class="ka-nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
          </svg>
          Settings
        </a>
      </li>

    </ul>

  </nav>

  <!-- User Footer -->
  <div class="dropdown" style="flex-shrink:0;">
    <a href="#" class="ka-sidebar-user" data-bs-toggle="dropdown" aria-expanded="false">
      <div class="ka-sidebar-user-avatar"><?= $initials ?></div>
      <div class="ka-sidebar-user-info">
        <div class="ka-sidebar-user-name"><?= htmlspecialchars($full_name) ?></div>
        <div class="ka-sidebar-user-role"><?= htmlspecialchars($user_role_label) ?></div>
      </div>
      <svg class="ka-sidebar-user-dots" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>
      </svg>
    </a>
    <ul class="dropdown-menu dropdown-menu-end mb-1" style="min-width:200px;">
      <li>
        <div class="px-3 py-2">
          <div style="font-weight:600;font-size:.8125rem;color:var(--ka-text)"><?= htmlspecialchars($full_name) ?></div>
          <div style="font-size:.75rem;color:var(--ka-text-muted)"><?= htmlspecialchars($emp_id) ?></div>
        </div>
      </li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item" href="<?= base_url('index.php/profile') ?>">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2 text-muted" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        My Profile
      </a></li>
      <li><a class="dropdown-item" href="<?= base_url('index.php/settings') ?>">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2 text-muted" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Account Settings
      </a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item text-danger" href="<?= base_url('index.php/auth/logout') ?>">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Sign Out
      </a></li>
    </ul>
  </div>

</aside>

<!-- Main wrapper — closed in footer.php -->
<div class="ka-main-wrap" id="kaMainWrap">