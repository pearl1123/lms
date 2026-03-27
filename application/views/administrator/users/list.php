<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$users = $users ?? [];
?>
<?php $this->load->view('layouts/alerts'); ?>
<style>
.uu-table { width:100%; border-collapse:collapse; margin-top:.75rem; }
.uu-table th,
.uu-table td { padding:.75rem 1rem; border:1px solid var(--ka-border,#e2e8f0); text-align:left; font-size:.85rem; }
.uu-table th { background:var(--ka-bg,#f8fafc); color:var(--ka-text-muted,#64748b); text-transform:uppercase; letter-spacing:.05em; }
.uu-badge { display:inline-flex; align-items:center; padding:.2rem .55rem; border-radius:500px; font-weight:700; font-size:.72rem; color:#fff; }
.uu-badge-admin { background:#0284c7; }
.uu-badge-teacher { background:#16a34a; }
.uu-badge-employee { background:#6b7280; }
.uu-badge-active { background:#16a34a; }
.uu-badge-inactive { background:#64748b; }
.uu-empty { padding:2rem; text-align:center; color:var(--ka-text-muted,#64748b); }
</style>

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;">
  <div>
    <h2 style="margin:0;font-size:1.35rem;font-weight:800;">Users & Enrollees</h2>
    <p style="margin:.25rem 0 0;font-size:.88rem;color:var(--ka-text-muted,#64748b);">All registered users in the LMS.</p>
  </div>
  <div style="font-size:.8rem;color:var(--ka-text-muted,#64748b);">Total: <?= count($users) ?></div>
</div>

<?php if (! empty($users)): ?>
<table class="uu-table">
  <thead>
    <tr>
      <th>#</th>
      <th>Full Name</th>
      <th>Employee ID</th>
      <th>Role</th>
      <th>Office</th>
      <th>Status</th>
      <th>Last login</th>
      <th>Registered</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($users as $index => $u): ?>
      <tr>
        <td><?= $index + 1 ?></td>
        <td><?= htmlspecialchars($u->fullname ?? '—') ?></td>
        <td><?= htmlspecialchars($u->employee_id ?? '—') ?></td>
        <td>
          <?php $role = strtolower($u->role ?? 'employee'); ?>
          <span class="uu-badge uu-badge-<?= $role ?>"><?= ucfirst($role) ?></span>
        </td>
        <td><?= htmlspecialchars($u->office ?? '—') ?></td>
        <td>
          <?php $status = strtolower($u->status ?? 'inactive'); ?>
          <span class="uu-badge uu-badge-<?= $status ?>"><?= ucfirst($status) ?></span>
        </td>
        <td><?= ! empty($u->last_login) ? date('Y-m-d H:i:s', strtotime($u->last_login)) : 'Never' ?></td>
        <td><?= ! empty($u->created_at) ? date('Y-m-d', strtotime($u->created_at)) : '—' ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
<div class="uu-empty">
  <p>No users found yet.</p>
</div>
<?php endif; ?>
