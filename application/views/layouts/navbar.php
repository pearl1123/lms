<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Prepared by ka_merge_layout_vars()
$nc             = $nav_context ?? [];
$full_name      = $nc['full_name'] ?? 'User';
$user_role_label = $nc['user_role_label'] ?? 'Employee';
$initials       = $nc['initials'] ?? '';
$streak    = $nc['streak'] ?? null;
$notifications = $navbar_notifications ?? [];
if ( ! is_array($notifications)) {
    $notifications = [];
}

// Breadcrumb — set $breadcrumbs in controller (passed through layouts/main)
// e.g. $data['breadcrumbs'] = [['label'=>'Courses','url'=>'courses'],['label'=>'Module 1']];
?>

<!-- ============================================================
     KABAGA ACADEMY — Top Navbar
     Premium LMS Layout — Lung Center of the Philippines
============================================================ -->

<style>
  /* ── Top Navbar ── */
  .ka-navbar {
    position: sticky;
    top: 0;
    z-index: 1030;
    height: var(--ka-header-height, 64px);
    background: #ffffff;
    border-bottom: 1px solid var(--ka-border, #e2e8f0);
    display: flex;
    align-items: center;
    padding: 0 1.5rem;
    gap: 1rem;
    box-shadow: 0 1px 0 rgba(0,0,0,0.05);
  }

  /* Mobile toggle */
  .ka-nav-toggle {
    display: none;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid var(--ka-border);
    background: transparent;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--ka-text-muted);
    transition: all 0.15s;
    flex-shrink: 0;
  }
  .ka-nav-toggle:hover { background: var(--ka-accent); color: var(--ka-primary); }
  @media (max-width: 991.98px) { .ka-nav-toggle { display: flex; } }

  /* Page title / breadcrumb */
  .ka-navbar-title {
    flex: 1;
    min-width: 0;
  }
  .ka-navbar-page-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--ka-text);
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0;
  }
  .ka-breadcrumb {
    display: flex;
    align-items: center;
    gap: 4px;
    margin: 0;
    padding: 0;
    list-style: none;
  }
  .ka-breadcrumb li {
    display: flex;
    align-items: center;
    font-size: 0.75rem;
    color: var(--ka-text-muted);
    gap: 4px;
  }
  .ka-breadcrumb li a {
    color: var(--ka-text-muted);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.15s;
  }
  .ka-breadcrumb li a:hover { color: var(--ka-primary); }
  .ka-breadcrumb li.active { color: var(--ka-primary-deep); font-weight: 600; }
  .ka-breadcrumb-sep {
    width: 12px; height: 12px;
    opacity: 0.4;
  }

  /* Search bar */
  .ka-nav-search {
    position: relative;
    flex-shrink: 0;
  }
  .ka-nav-search-input {
    width: 220px;
    height: 36px;
    border: 1.5px solid var(--ka-border);
    border-radius: 20px;
    padding: 0 0.875rem 0 2.375rem;
    font-size: 0.8125rem;
    background: var(--ka-bg);
    color: var(--ka-text);
    transition: all 0.2s ease;
    outline: none;
  }
  .ka-nav-search-input:focus {
    border-color: var(--ka-primary);
    box-shadow: 0 0 0 3px rgba(109,171,207,0.15);
    width: 280px;
    background: #fff;
  }
  .ka-nav-search-input::placeholder { color: var(--ka-text-muted); }
  .ka-nav-search-icon {
    position: absolute;
    left: 0.75rem; top: 50%;
    transform: translateY(-50%);
    width: 15px; height: 15px;
    color: var(--ka-text-muted);
    pointer-events: none;
  }
  @media (max-width: 767.98px) { .ka-nav-search { display: none; } }

  /* Nav actions */
  .ka-nav-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
  }
  .ka-nav-btn {
    width: 36px; height: 36px;
    border-radius: 8px;
    border: 1px solid var(--ka-border);
    background: transparent;
    display: flex; align-items: center; justify-content: center;
    color: var(--ka-text-muted);
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s;
    position: relative;
    flex-shrink: 0;
  }
  .ka-nav-btn:hover {
    background: var(--ka-accent);
    color: var(--ka-primary);
    border-color: var(--ka-primary);
  }
  .ka-nav-btn svg { width: 18px; height: 18px; }
  .ka-nav-btn .ka-badge {
    position: absolute;
    top: -3px; right: -3px;
    min-width: 16px; height: 16px;
    padding: 0 4px;
    background: #d63939;
    color: #fff;
    font-size: 0.625rem;
    font-weight: 700;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid #fff;
    line-height: 1;
  }

  /* User avatar in navbar */
  .ka-nav-user {
    display: flex; align-items: center; gap: 8px;
    padding: 4px 8px 4px 4px;
    border-radius: 24px;
    border: 1px solid var(--ka-border);
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s;
    background: transparent;
    flex-shrink: 0;
  }
  .ka-nav-user:hover { border-color: var(--ka-primary); background: var(--ka-accent); }
  .ka-nav-user-avatar {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--ka-primary), var(--ka-navy));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 0.6875rem;
    flex-shrink: 0;
  }
  .ka-nav-user-name {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--ka-text);
    white-space: nowrap;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .ka-nav-user-chevron { width: 14px; height: 14px; color: var(--ka-text-muted); }
  @media (max-width: 575.98px) {
    .ka-nav-user-name, .ka-nav-user-chevron { display: none; }
    .ka-nav-user { padding: 4px; }
  }

  /* Progress bar — learning streak indicator */
  .ka-streak-chip {
    display: flex; align-items: center; gap: 6px;
    padding: 4px 10px;
    background: linear-gradient(135deg, #fff7ed, #fef3c7);
    border: 1px solid #fde68a;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #92400e;
    flex-shrink: 0;
    white-space: nowrap;
  }
  .ka-streak-chip svg { width: 14px; height: 14px; color: #f59f00; }
  @media (max-width: 767.98px) { .ka-streak-chip { display: none; } }

  /* Notification dropdown */
  .ka-notif-dropdown {
    width: 340px;
    padding: 0;
    border-radius: 12px !important;
    overflow: hidden;
  }
  .ka-notif-header {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--ka-border);
    display: flex; align-items: center; justify-content: space-between;
  }
  .ka-notif-header-title { font-weight: 700; font-size: 0.875rem; color: var(--ka-text); margin: 0; }
  .ka-notif-list { max-height: 320px; overflow-y: auto; }
  .ka-notif-item {
    display: flex; gap: 12px; padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--ka-border);
    text-decoration: none;
    transition: background 0.15s;
  }
  .ka-notif-item:hover { background: var(--ka-accent); }
  .ka-notif-item:last-child { border-bottom: none; }
  .ka-notif-icon {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 0.875rem;
  }
  .ka-notif-icon.course { background: #eff6ff; color: #3b82f6; }
  .ka-notif-icon.cert   { background: #f0fdf4; color: #22c55e; }
  .ka-notif-icon.alert  { background: #fef2f2; color: #ef4444; }
  .ka-notif-icon.info   { background: var(--ka-accent); color: var(--ka-primary); }
  .ka-notif-body { flex: 1; min-width: 0; }
  .ka-notif-text { font-size: 0.8125rem; color: var(--ka-text); line-height: 1.4; margin: 0; }
  .ka-notif-text strong { font-weight: 600; }
  .ka-notif-time { font-size: 0.6875rem; color: var(--ka-text-muted); margin-top: 3px; }
  .ka-notif-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--ka-primary);
    flex-shrink: 0;
    margin-top: 6px;
  }
  .ka-notif-footer {
    padding: 0.625rem 1rem;
    text-align: center;
    border-top: 1px solid var(--ka-border);
    background: var(--ka-bg);
  }
  .ka-notif-footer a { font-size: 0.8125rem; font-weight: 600; color: var(--ka-primary); text-decoration: none; }
  .ka-notif-footer a:hover { color: var(--ka-primary-dark); }
</style>

<!-- ══ TOP NAVBAR ════════════════════════════════════════════ -->
<header class="ka-navbar">

  <!-- Mobile sidebar toggle -->
  <button class="ka-nav-toggle" onclick="kaToggleSidebar()" aria-label="Toggle menu">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>

  <!-- Page title + breadcrumb -->
  <div class="ka-navbar-title">
    <?php if (!empty($page_title)): ?>
      <h1 class="ka-navbar-page-title"><?= htmlspecialchars($page_title) ?></h1>
    <?php endif; ?>
    <?php if (!empty($breadcrumbs)): ?>
      <ul class="ka-breadcrumb">
        <li>
          <a href="<?= base_url('dashboard'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          </a>
        </li>
        <?php foreach ($breadcrumbs as $i => $crumb): ?>
          <li>
            <svg class="ka-breadcrumb-sep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
          </li>
          <?php if ($i === count($breadcrumbs) - 1): ?>
            <li class="active"><?= htmlspecialchars($crumb['label']) ?></li>
          <?php else: ?>
            <li><a href="<?= base_url('' . $crumb['url']); ?>"><?= htmlspecialchars($crumb['label']) ?></a></li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <!-- Search -->
  <div class="ka-nav-search">
    <svg class="ka-nav-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="ka-nav-search-input" placeholder="Search courses, topics…" id="kaGlobalSearch" autocomplete="off">
  </div>

  <!-- Actions -->
  <div class="ka-nav-actions">

    <!-- Streak chip -->
    <?php if ($streak !== null && $streak !== ''): ?>
    <div class="ka-streak-chip">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.657 6.343a8 8 0 1 1-11.314 11.314 8 8 0 0 1 11.314-11.314zm-3.071 2.122a6 6 0 0 1-6.97 9.293 6 6 0 0 1 6.97-9.293z"/></svg>
      <?= htmlspecialchars((string) $streak) ?> day streak
    </div>
    <?php endif; ?>

    <!-- Notifications -->
    <div class="dropdown">
      <a href="#" class="ka-nav-btn" data-bs-toggle="dropdown" aria-label="Notifications">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <?php if (!empty($notif_count) && $notif_count > 0): ?>
          <span class="ka-badge"><?= $notif_count > 9 ? '9+' : $notif_count ?></span>
        <?php endif; ?>
      </a>
      <div class="dropdown-menu dropdown-menu-end ka-notif-dropdown">
        <div class="ka-notif-header">
          <p class="ka-notif-header-title">Notifications</p>
          <?php if ( ! empty($notif_count) && $notif_count > 0): ?>
          <a href="<?= base_url('index.php/announcements/mark_all_read'); ?>" style="font-size:.75rem;font-weight:600;color:var(--ka-primary);text-decoration:none;">Mark all read</a>
          <?php endif; ?>
        </div>
        <div class="ka-notif-list">
          <?php if (empty($notifications)): ?>
          <div class="ka-notif-item" style="cursor:default;pointer-events:none;">
            <div class="ka-notif-icon info">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="ka-notif-body">
              <p class="ka-notif-text">No announcements yet.</p>
            </div>
          </div>
          <?php else: ?>
          <?php foreach ($notifications as $n):
            $is_read   = (int) ($n->is_read ?? 0) === 1;
            $type_id   = (int) ($n->type_id ?? 0);
            $type_name = strtolower((string) ($n->type_name ?? ''));
            $notif_icon = 'info';
            if ($type_id === 1 || strpos($type_name, 'system') !== false) {
                $notif_icon = 'info';
            } elseif ($type_id === 2 || strpos($type_name, 'course') !== false) {
                $notif_icon = 'course';
            } elseif ($type_id === 3 || strpos($type_name, 'reminder') !== false) {
                $notif_icon = 'alert';
            }
            $preview = strip_tags((string) ($n->message ?? ''));
            if (function_exists('mb_substr')) {
                $preview = mb_strlen($preview) > 140 ? mb_substr($preview, 0, 140) . '…' : $preview;
            } else {
                $preview = strlen($preview) > 140 ? substr($preview, 0, 140) . '…' : $preview;
            }
            $created = ! empty($n->date_encoded) ? date('M j, g:i A', strtotime($n->date_encoded)) : '';
            $link    = base_url('index.php/announcements/mark_read/' . (int) ($n->user_notification_id ?? 0));
          ?>
          <a href="<?= $link ?>" class="ka-notif-item">
            <div class="ka-notif-icon <?= $notif_icon === 'course' ? 'course' : ($notif_icon === 'alert' ? 'alert' : 'info') ?>">
              <?php if ($notif_icon === 'course'): ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
              <?php elseif ($notif_icon === 'alert'): ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
              <?php else: ?>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <?php endif; ?>
            </div>
            <div class="ka-notif-body">
              <p class="ka-notif-text"><strong><?= htmlspecialchars($n->title ?? '(No title)') ?></strong><?= $preview !== '' ? ' — ' . htmlspecialchars($preview) : '' ?></p>
              <?php if ($created !== ''): ?><div class="ka-notif-time"><?= htmlspecialchars($created) ?></div><?php endif; ?>
            </div>
            <?php if ( ! $is_read): ?><div class="ka-notif-dot"></div><?php endif; ?>
          </a>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="ka-notif-footer">
          <a href="<?= base_url('index.php/announcements'); ?>">View all notifications</a>
        </div>
      </div>
    </div>

    <!-- Help -->
    <a href="<?= base_url('help'); ?>" class="ka-nav-btn" title="Help & Support">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    </a>

    <!-- Divider -->
    <div style="width:1px;height:24px;background:var(--ka-border);flex-shrink:0;"></div>

    <!-- User dropdown -->
    <div class="dropdown">
      <a href="#" class="ka-nav-user" data-bs-toggle="dropdown" aria-label="User menu">
        <div class="ka-nav-user-avatar"><?= $initials ?></div>
        <span class="ka-nav-user-name"><?= htmlspecialchars($full_name) ?></span>
        <svg class="ka-nav-user-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
      </a>
      <ul class="dropdown-menu dropdown-menu-end" style="min-width:200px;">
        <li>
          <div class="px-3 py-2">
            <div style="font-weight:600;font-size:.8125rem;color:var(--ka-text)"><?= htmlspecialchars($full_name) ?></div>
            <div style="font-size:.75rem;color:var(--ka-text-muted)"><?= htmlspecialchars($user_role_label) ?></div>
          </div>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?= base_url('profile'); ?>">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2 text-muted" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          My Profile
        </a></li>
        <li><a class="dropdown-item" href="<?= base_url('my_courses'); ?>">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2 text-muted" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
          My Learning
        </a></li>
        <li><a class="dropdown-item" href="<?= base_url('certificates'); ?>">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2 text-muted" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>
          My Certificates
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?= base_url('settings'); ?>">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2 text-muted" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          Settings
        </a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="<?= base_url('auth/logout'); ?>">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Sign Out
        </a></li>
      </ul>
    </div>

  </div><!-- /ka-nav-actions -->

</header>

<!-- ══ Page Content Wrapper ─ put your view content after this ── -->
<div class="ka-page-content" style="flex:1;padding:1.5rem;">