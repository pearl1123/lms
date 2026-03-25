<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$courses   = $courses   ?? [];
$user_role = strtolower($user->role ?? 'teacher');
$is_admin  = $user_role === 'admin';

$active   = array_filter($courses, fn($c) => ! (int)$c->archived);
$archived = array_filter($courses, fn($c) =>  (int)$c->archived);
?>
<?php $this->load->view('layouts/alerts'); ?>
<style>
.mc-topbar { display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem; }
.mc-topbar h2 { font-size:1.25rem;font-weight:800;color:var(--ka-text,#1e293b);margin:0 0 2px;letter-spacing:-.02em; }
.mc-topbar p  { font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:0; }
.mc-btn { display:inline-flex;align-items:center;gap:6px;padding:.5rem 1.125rem;border-radius:8px;font-size:.8125rem;font-weight:700;text-decoration:none;border:none;cursor:pointer;transition:all .18s; }
.mc-btn-primary { background:var(--ka-navy,#1a3a5c);color:#fff; }
.mc-btn-primary:hover { background:#254d75;color:#fff;transform:translateY(-1px); }
.mc-btn-outline { background:#fff;color:var(--ka-text,#1e293b);border:1.5px solid var(--ka-border,#e2e8f0); }
.mc-btn-outline:hover { border-color:var(--ka-primary,#6dabcf);color:var(--ka-primary,#6dabcf); }

/* Stats */
.mc-stats { display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem; }
@media(max-width:767.98px){ .mc-stats { grid-template-columns:repeat(2,1fr); } }
.mc-stat { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:12px;padding:.875rem 1rem;display:flex;align-items:center;gap:.75rem; }
.mc-stat-icon { width:38px;height:38px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center; }
.mc-stat-icon svg { width:17px;height:17px; }
.mc-stat-val { font-size:1.375rem;font-weight:800;color:var(--ka-text,#1e293b);line-height:1; }
.mc-stat-lbl { font-size:.6875rem;color:var(--ka-text-muted,#64748b);font-weight:500;margin-top:2px; }

/* Filters */
.mc-filters { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:12px;padding:.875rem 1rem;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem; }
.mc-search-wrap { position:relative;flex:1;min-width:200px; }
.mc-search-icon { position:absolute;left:.75rem;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--ka-text-muted,#64748b);pointer-events:none; }
.mc-search { width:100%;height:36px;padding:0 .75rem 0 2.25rem;border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;font-size:.8125rem;background:var(--ka-bg,#f8fafc);outline:none;transition:all .2s; }
.mc-search:focus { border-color:var(--ka-primary,#6dabcf);background:#fff;box-shadow:0 0 0 3px rgba(109,171,207,.15); }
.mc-select { height:36px;padding:0 .75rem;border:1.5px solid var(--ka-border,#e2e8f0);border-radius:8px;font-size:.8125rem;background:var(--ka-bg,#f8fafc);outline:none;cursor:pointer; }
.mc-select:focus { border-color:var(--ka-primary,#6dabcf); }

/* Table */
.mc-panel { background:#fff;border:1px solid var(--ka-border,#e2e8f0);border-radius:14px;overflow:hidden;margin-bottom:1.25rem; }
.mc-panel-hdr { padding:1rem 1.25rem;border-bottom:1px solid var(--ka-border,#e2e8f0);display:flex;align-items:center;justify-content:space-between; }
.mc-panel-title { font-size:.9rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0; }
.mc-table { width:100%;border-collapse:collapse; }
.mc-table th { padding:.75rem 1rem;text-align:left;font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--ka-text-muted,#64748b);border-bottom:1px solid var(--ka-border,#e2e8f0);background:var(--ka-bg,#f8fafc);white-space:nowrap; }
.mc-table td { padding:.875rem 1rem;border-bottom:1px solid var(--ka-border,#e2e8f0);font-size:.8125rem;color:var(--ka-text,#1e293b);vertical-align:middle; }
.mc-table tr:last-child td { border-bottom:none; }
.mc-table tbody tr:hover td { background:var(--ka-accent,#e8f4fd); }
.mc-table tr.archived-row td { opacity:.55; }

.mc-course-title { font-weight:700;color:var(--ka-text,#1e293b);margin-bottom:2px; }
.mc-course-meta  { font-size:.6875rem;color:var(--ka-text-muted,#64748b); }
.mc-badge { display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:.625rem;font-weight:700; }
.mc-badge-active   { background:#ecfdf5;color:#065f46; }
.mc-badge-archived { background:var(--ka-bg,#f8fafc);color:var(--ka-text-muted,#64748b);border:1px solid var(--ka-border,#e2e8f0); }

/* Progress mini */
.mc-prog-wrap { display:flex;align-items:center;gap:.5rem; }
.mc-prog-bar  { width:80px;height:5px;background:var(--ka-border,#e2e8f0);border-radius:3px;overflow:hidden; }
.mc-prog-fill { height:100%;border-radius:3px;background:var(--ka-primary,#6dabcf); }

/* Action buttons */
.mc-actions { display:flex;align-items:center;gap:.375rem; }
.mc-action-btn { width:30px;height:30px;border-radius:7px;border:1.5px solid var(--ka-border,#e2e8f0);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--ka-text-muted,#64748b);transition:all .15s;text-decoration:none; }
.mc-action-btn:hover { background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);border-color:var(--ka-primary,#6dabcf); }
.mc-action-btn.danger:hover { background:#fef2f2;color:#dc2626;border-color:#dc2626; }
.mc-action-btn svg { width:13px;height:13px; }

/* Empty */
.mc-empty { text-align:center;padding:3rem 1rem; }
.mc-empty svg { width:44px;height:44px;margin:0 auto .875rem;opacity:.2;display:block; }
.mc-empty p { font-size:.875rem;color:var(--ka-text-muted,#64748b);margin-bottom:1.25rem; }
</style>

<!-- ══ Topbar ════════════════════════════════════════════════ -->
<div class="mc-topbar animate__animated animate__fadeIn animate__fast">
  <div>
    <h2>Manage Courses</h2>
    <p><?= $is_admin ? 'All courses in the system' : 'Courses you have created' ?></p>
  </div>
  <a href="<?= base_url('index.php/manage_courses/create') ?>" class="mc-btn mc-btn-primary">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Course
  </a>
</div>

<!-- ══ Stats ════════════════════════════════════════════════ -->
<?php
$total_enrolled = 0;
$total_modules  = 0;
foreach ($courses as $c) {
    $total_enrolled += (int)($c->enrolled_count ?? 0);
    $total_modules  += (int)($c->module_count   ?? 0);
}
?>
<div class="mc-stats animate__animated animate__fadeInUp animate__fast">
  <div class="mc-stat">
    <div class="mc-stat-icon" style="background:#eff6ff;color:#3b82f6;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/></svg></div>
    <div><div class="mc-stat-val"><?= count($active) ?></div><div class="mc-stat-lbl">Active Courses</div></div>
  </div>
  <div class="mc-stat">
    <div class="mc-stat-icon" style="background:#ecfdf5;color:#22c55e;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
    <div><div class="mc-stat-val"><?= $total_enrolled ?></div><div class="mc-stat-lbl">Total Enrollments</div></div>
  </div>
  <div class="mc-stat">
    <div class="mc-stat-icon" style="background:var(--ka-accent,#e8f4fd);color:var(--ka-primary,#6dabcf);"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
    <div><div class="mc-stat-val"><?= $total_modules ?></div><div class="mc-stat-lbl">Total Modules</div></div>
  </div>
  <div class="mc-stat">
    <div class="mc-stat-icon" style="background:#fef2f2;color:#ef4444;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v18H3z" opacity=".3"/><path d="M9 9h6v6H9z"/></svg></div>
    <div><div class="mc-stat-val"><?= count($archived) ?></div><div class="mc-stat-lbl">Archived</div></div>
  </div>
</div>

<!-- ══ Filters ══════════════════════════════════════════════ -->
<div class="mc-filters animate__animated animate__fadeInUp animate__fast" style="animation-delay:.05s;">
  <div class="mc-search-wrap">
    <svg class="mc-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="mc-search" id="mcSearch" placeholder="Search courses…">
  </div>
  <select class="mc-select" id="mcFilterStatus">
    <option value="">All Courses</option>
    <option value="active">Active</option>
    <option value="archived">Archived</option>
  </select>
  <span style="font-size:.8125rem;font-weight:600;color:var(--ka-text-muted,#64748b);" id="mcCount"><?= count($courses) ?> courses</span>
</div>

<!-- ══ Courses Table ═════════════════════════════════════════ -->
<div class="mc-panel animate__animated animate__fadeInUp animate__fast" style="animation-delay:.1s;">
  <div class="mc-panel-hdr">
    <h3 class="mc-panel-title">All Courses</h3>
    <a href="<?= base_url('index.php/manage_courses/create') ?>" class="mc-btn mc-btn-outline" style="padding:.375rem .875rem;font-size:.75rem;">
      + New Course
    </a>
  </div>

  <?php if ( ! empty($courses)): ?>
  <table class="mc-table" id="mcTable">
    <thead>
      <tr>
        <th>Course</th>
        <th>Category</th>
        <?php if ($is_admin): ?><th>Instructor</th><?php endif; ?>
        <th>Modules</th>
        <th>Enrolled</th>
        <th>Avg Progress</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($courses as $course): ?>
      <tr class="<?= $course->archived ? 'archived-row' : '' ?>"
          data-title="<?= htmlspecialchars(strtolower($course->title)) ?>"
          data-status="<?= $course->archived ? 'archived' : 'active' ?>">
        <td>
          <div class="mc-course-title"><?= htmlspecialchars($course->title) ?></div>
          <div class="mc-course-meta">
            <?= htmlspecialchars($course->modality_name ?? '') ?>
            <?php if ( ! empty($course->expiry_days)): ?> · <?= $course->expiry_days ?> day expiry<?php endif; ?>
          </div>
        </td>
        <td><?= htmlspecialchars($course->category_name ?? '—') ?></td>
        <?php if ($is_admin): ?>
        <td><?= htmlspecialchars($course->creator_name ?? '—') ?></td>
        <?php endif; ?>
        <td><?= (int)($course->module_count ?? 0) ?></td>
        <td><?= (int)($course->enrolled_count ?? 0) ?></td>
        <td>
          <?php $avg = (int)($course->avg_progress ?? 0); ?>
          <div class="mc-prog-wrap">
            <div class="mc-prog-bar"><div class="mc-prog-fill" style="width:<?= $avg ?>%"></div></div>
            <span style="font-size:.6875rem;font-weight:700;color:var(--ka-text-muted,#64748b);"><?= $avg ?>%</span>
          </div>
        </td>
        <td>
          <span class="mc-badge <?= $course->archived ? 'mc-badge-archived' : 'mc-badge-active' ?>">
            <?= $course->archived ? 'Archived' : 'Active' ?>
          </span>
        </td>
        <td>
          <div class="mc-actions">
            <a href="<?= base_url('index.php/manage_courses/edit/'.$course->id) ?>"
               class="mc-action-btn" title="Edit">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </a>
            <a href="<?= base_url('index.php/courses/view/'.$course->id) ?>"
               class="mc-action-btn" title="Preview" target="_blank">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </a>
            <?php if ( ! $course->archived): ?>
            <button onclick="KA.deleteConfirm('<?= base_url('index.php/manage_courses/delete/'.$course->id) ?>', '<?= htmlspecialchars(addslashes($course->title), ENT_QUOTES) ?>')"
                    class="mc-action-btn danger" title="Archive">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="mc-empty">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
    <p>No courses yet. Create your first course to get started.</p>
    <a href="<?= base_url('index.php/manage_courses/create') ?>" class="mc-btn mc-btn-primary">Create Course</a>
  </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var search = document.getElementById('mcSearch');
  var filter = document.getElementById('mcFilterStatus');
  var countEl= document.getElementById('mcCount');
  var rows   = document.querySelectorAll('#mcTable tbody tr');

  function applyFilter() {
    var kw  = search.value.toLowerCase().trim();
    var st  = filter.value;
    var vis = 0;
    rows.forEach(function(r) {
      var title  = r.dataset.title  || '';
      var status = r.dataset.status || '';
      var show   = (!kw || title.includes(kw)) && (!st || status === st);
      r.style.display = show ? '' : 'none';
      if (show) vis++;
    });
    countEl.textContent = vis + ' course' + (vis !== 1 ? 's' : '');
  }
  search.addEventListener('input', applyFilter);
  filter.addEventListener('change', applyFilter);
});
</script>