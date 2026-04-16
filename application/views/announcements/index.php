<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** @var object   $user */
/** @var string   $page_title */
/** @var object[] $notifications */
/** @var int      $unread_count */
/** @var int      $current_page */
/** @var int      $per_page */

$role       = strtolower($user->role ?? 'employee');
$full_name  = $user->fullname ?? 'User';
$first_name = explode(' ', trim($full_name))[0];

if ($role === 'admin') {
    $hero_eyebrow = 'System & LMS Center';
    $hero_title   = 'All Announcements & Alerts';
    $hero_sub     = 'Monitor system notices, course updates, and reminders sent across KABAGA Academy.';
} elseif ($role === 'teacher' || $role === 'instructor') {
    $hero_eyebrow = 'Instructor Updates';
    $hero_title   = 'Announcements for your classes';
    $hero_sub     = 'Stay on top of course changes, deadlines, and system messages affecting your learners.';
} else {
    $hero_eyebrow = 'My Announcements';
    $hero_title   = "Hi {$first_name}, here\'s what\'s new";
    $hero_sub     = 'We surface reminders, course updates, and system alerts relevant to your learning journey.';
}
?>

<style>
.ann-hero {
  background: linear-gradient(135deg, var(--ka-navy,#1a3a5c) 0%, #1e4976 55%, #2d6a9f 100%);
  border-radius: 16px;
  padding: 1.75rem 2rem;
  margin-bottom: 1.75rem;
  position: relative; overflow: hidden;
  box-shadow: 0 8px 32px rgba(26,58,92,.18);
}
.ann-hero::before {
  content:''; position:absolute; top:-80px; right:-60px;
  width:260px; height:260px; border-radius:50%;
  background:rgba(109,171,207,.1); pointer-events:none;
}
.ann-hero-body { position:relative; z-index:1; display:flex; justify-content:space-between; gap:1.5rem; flex-wrap:wrap; align-items:center; }
.ann-hero-eyebrow {
  font-size:.6875rem;font-weight:700;letter-spacing:.1em;
  text-transform:uppercase;color:rgba(255,255,255,.5);margin-bottom:.375rem;
}
.ann-hero-title {
  font-size:1.5rem;font-weight:800;color:#fff;
  margin:0 0 .375rem;letter-spacing:-.02em;line-height:1.2;
}
.ann-hero-sub { font-size:.875rem;color:rgba(255,255,255,.65);margin:0;max-width:420px; }
.ann-hero-stats { display:flex;gap:1.25rem;flex-wrap:wrap;align-items:center; }
.ann-stat-pill {
  padding:6px 11px;border-radius:999px;
  background:rgba(15,23,42,.55);color:#e5e7eb;
  font-size:.75rem;font-weight:500;display:inline-flex;align-items:center;gap:6px;
}
.ann-stat-pill strong { color:#fff;font-weight:700; }
.ann-hero-actions { display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.5rem; }
.ann-hero-btn {
  display:inline-flex;align-items:center;gap:6px;
  padding:.45rem .95rem;border-radius:8px;font-size:.75rem;
  font-weight:600;text-decoration:none;transition:all .18s;
}
.ann-hero-btn-primary { background:#f59e0b;color:#1f2933; }
.ann-hero-btn-primary:hover { background:#d97706;color:#111827;transform:translateY(-1px); }
.ann-hero-btn-ghost {
  border:1px solid rgba(255,255,255,.25);
  color:rgba(255,255,255,.85);background:rgba(15,23,42,.35);
}
.ann-hero-btn-ghost:hover { background:rgba(15,23,42,.6);color:#fff; }

.ann-panel {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;overflow:hidden;
}
.ann-panel-header {
  padding:.9rem 1.1rem;border-bottom:1px solid var(--ka-border,#e2e8f0);
  display:flex;align-items:center;justify-content:space-between;gap:.75rem;
}
.ann-panel-title { font-size:.9rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0; }
.ann-panel-filters { display:flex;align-items:center;gap:.5rem;flex-wrap:wrap; }
.ann-pill-filter {
  padding:4px 10px;border-radius:999px;font-size:.6875rem;font-weight:600;
  border:1px solid var(--ka-border,#e2e8f0);background:var(--ka-bg,#f8fafc);
  color:var(--ka-text-muted,#64748b);cursor:pointer;transition:all .15s;
}
.ann-pill-filter.active,
.ann-pill-filter:hover {
  border-color:var(--ka-primary,#6dabcf);
  background:var(--ka-accent,#e8f4fd);
  color:var(--ka-primary,#6dabcf);
}
.ann-panel-body { padding:.75rem 1.1rem 1rem; }

.ann-list { list-style:none;margin:0;padding:0; }
.ann-item {
  display:flex;gap:12px;padding:.7rem 0;
  border-bottom:1px solid var(--ka-border,#e2e8f0);
}
.ann-item:last-child { border-bottom:none; }
.ann-icon {
  width:34px;height:34px;border-radius:9px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
}
.ann-icon svg { width:16px;height:16px; }
.ann-icon-system   { background:#eff6ff;color:#3b82f6; }
.ann-icon-course   { background:#f0fdf4;color:#22c55e; }
.ann-icon-reminder { background:#fffbeb;color:#f59f00; }
.ann-icon-other    { background:#e8f4fd;color:#0f172a; }
.ann-icon-unread   { box-shadow:0 0 0 2px #22c55e44; }

.ann-body { flex:1;min-width:0; }
.ann-title-row { display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:2px; }
.ann-title {
  font-size:.83rem;font-weight:600;color:var(--ka-text,#1e293b);
  margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.ann-type-chip {
  font-size:.65rem;font-weight:700;border-radius:999px;
  padding:2px 8px;white-space:nowrap;
}
.ann-type-system   { background:#eff6ff;color:#1d4ed8; }
.ann-type-course   { background:#f0fdf4;color:#15803d; }
.ann-type-reminder { background:#fffbeb;color:#92400e; }
.ann-type-other    { background:#e5e7eb;color:#111827; }

.ann-message {
  font-size:.75rem;color:var(--ka-text-muted,#64748b);
  line-height:1.5;max-height:3.2em;overflow:hidden;
}
.ann-meta-row {
  display:flex;align-items:center;gap:.75rem;
  margin-top:4px;font-size:.6875rem;color:var(--ka-text-muted,#64748b);flex-wrap:wrap;
}
.ann-badge-status {
  padding:2px 7px;border-radius:999px;font-size:.65rem;font-weight:700;
}
.ann-badge-unread { background:#fef3c7;color:#b45309; }
.ann-badge-read   { background:#ecfdf5;color:#15803d; }
.ann-dot {
  width:7px;height:7px;border-radius:50%;background:#22c55e;
  box-shadow:0 0 0 4px rgba(34,197,94,.18);
}
.ann-dot.read { background:#9ca3af;box-shadow:none; }

.ann-actions {
  flex-shrink:0;display:flex;flex-direction:column;gap:6px;
  align-items:flex-end;justify-content:center;
}
.ann-btn-small {
  padding:4px 8px;border-radius:7px;font-size:.6875rem;font-weight:600;
  border:1px solid var(--ka-border,#e2e8f0);background:#fff;
  color:var(--ka-primary,#6dabcf);text-decoration:none;transition:all .15s;
}
.ann-btn-small:hover {
  background:var(--ka-primary,#6dabcf);color:#fff;
}
.ann-empty {
  text-align:center;padding:2.5rem 1rem;color:var(--ka-text-muted,#64748b);
}
.ann-empty svg { width:40px;height:40px;margin-bottom:.75rem;opacity:.25; }
.ann-empty h4 { font-size:.95rem;font-weight:700;margin-bottom:.25rem;color:var(--ka-text,#1e293b); }
.ann-empty p { font-size:.8rem;margin:0; }

@media (max-width: 575.98px) {
  .ann-hero { padding:1.5rem 1.25rem; }
  .ann-hero-body { flex-direction:column;align-items:flex-start; }
  .ann-hero-sub { max-width:100%; }
}
</style>

<div class="page-body">
  <div class="container-xl">

    <div class="ann-hero animate__animated animate__fadeIn animate__fast">
      <div class="ann-hero-body">
        <div>
          <p class="ann-hero-eyebrow"><?= htmlspecialchars($hero_eyebrow) ?></p>
          <h2 class="ann-hero-title"><?= htmlspecialchars($hero_title) ?></h2>
          <p class="ann-hero-sub"><?= htmlspecialchars($hero_sub) ?></p>
        </div>
        <div class="ann-hero-stats">
          <span class="ann-stat-pill">
            <strong><?= (int)$unread_count ?></strong> unread
            announcement<?= (int)$unread_count === 1 ? '' : 's' ?>
          </span>
          <span class="ann-stat-pill">
            <strong><?= count($notifications) ?></strong> total messages
          </span>
          <div class="ann-hero-actions">
            <?php if (!empty($unread_count)): ?>
              <a href="<?= base_url('index.php/announcements/mark_all_read') ?>" class="ann-hero-btn ann-hero-btn-primary">
                Mark all as read
              </a>
            <?php endif; ?>
            <a href="<?= base_url('index.php/dashboard') ?>" class="ann-hero-btn ann-hero-btn-ghost">
              Back to dashboard
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="ann-panel animate__animated animate__fadeInUp animate__fast">
      <div class="ann-panel-header">
        <h3 class="ann-panel-title">
          <?= $role === 'admin' ? 'All system announcements' : 'Your recent announcements' ?>
        </h3>
        <div class="ann-panel-filters">
          <button type="button" class="ann-pill-filter active" data-ann-filter="all">All</button>
          <button type="button" class="ann-pill-filter" data-ann-filter="unread">Unread</button>
          <button type="button" class="ann-pill-filter" data-ann-filter="read">Read</button>
        </div>
      </div>
      <div class="ann-panel-body">
        <?php if (empty($notifications)): ?>
          <div class="ann-empty">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <h4>No announcements yet</h4>
            <p>You’ll see course updates, reminders, and system messages here.</p>
          </div>
        <?php else: ?>
          <ul class="ann-list" id="annList">
            <?php foreach ($notifications as $n):
              $is_read  = (int)($n->is_read ?? 0) === 1;
              $type_id  = (int)($n->type_id ?? 0);
              $type_name= strtolower($n->type_name ?? '');

              $icon_class = 'ann-icon-other';
              $type_class = 'ann-type-other';
              if ($type_id === 1 || strpos($type_name, 'system') !== false) {
                  $icon_class = 'ann-icon-system';
                  $type_class = 'ann-type-system';
              } elseif ($type_id === 2 || strpos($type_name, 'course') !== false) {
                  $icon_class = 'ann-icon-course';
                  $type_class = 'ann-type-course';
              } elseif ($type_id === 3 || strpos($type_name, 'reminder') !== false) {
                  $icon_class = 'ann-icon-reminder';
                  $type_class = 'ann-type-reminder';
              }

              $created_at = !empty($n->date_encoded)
                  ? date('M d, Y · h:i A', strtotime($n->date_encoded))
                  : '';
            ?>
              <li class="ann-item" data-status="<?= $is_read ? 'read' : 'unread' ?>">
                <div class="ann-icon <?= $icon_class ?> <?= $is_read ? '' : 'ann-icon-unread' ?>">
                  <?php if ($type_class === 'ann-type-system'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                  <?php elseif ($type_class === 'ann-type-course'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                  <?php elseif ($type_class === 'ann-type-reminder'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                  <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                  <?php endif; ?>
                </div>
                <div class="ann-body">
                  <div class="ann-title-row">
                    <h4 class="ann-title"><?= htmlspecialchars($n->title ?? '(No title)') ?></h4>
                    <span class="ann-type-chip <?= $type_class ?>">
                      <?= htmlspecialchars($n->type_name ?? 'Notification') ?>
                    </span>
                  </div>
                  <div class="ann-message"><?= $n->message ?></div>
                  <div class="ann-meta-row">
                    <span><?= $created_at ?></span>
                    <?php if (!$is_read): ?>
                      <span class="ann-badge-status ann-badge-unread">Unread</span>
                    <?php else: ?>
                      <span class="ann-badge-status ann-badge-read">Read</span>
                    <?php endif; ?>
                    <span style="display:inline-flex;align-items:center;gap:4px;">
                      <span class="ann-dot <?= $is_read ? 'read' : '' ?>"></span>
                      <span><?= $is_read ? 'Seen' : 'New' ?></span>
                    </span>
                  </div>
                </div>
                <div class="ann-actions">
                  <?php if (!$is_read): ?>
                    <a href="<?= base_url('index.php/announcements/mark_read/' . (int)$n->user_notification_id) ?>"
                       class="ann-btn-small">
                      Mark as read
                    </a>
                  <?php endif; ?>
                  <?php if (!empty($n->course_id) && $type_class === 'ann-type-course'): ?>
                    <a href="<?= base_url('index.php/courses/view/' . (int)$n->course_id) ?>"
                       class="ann-btn-small"
                       style="border-color:var(--ka-primary,#6dabcf);color:var(--ka-primary,#6dabcf);">
                      Open course
                    </a>
                  <?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const pills = document.querySelectorAll('.ann-pill-filter');
  const items = document.querySelectorAll('#annList .ann-item');

  pills.forEach(pill => {
    pill.addEventListener('click', function () {
      pills.forEach(p => p.classList.remove('active'));
      this.classList.add('active');
      const filter = this.dataset.annFilter;

      items.forEach(item => {
        const status = item.dataset.status || 'unread';
        const show = (filter === 'all')
          || (filter === 'unread' && status === 'unread')
          || (filter === 'read'   && status === 'read');
        item.style.display = show ? '' : 'none';
      });
    });
  });
});
</script>

