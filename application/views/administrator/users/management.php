<?php defined('BASEPATH') OR exit('No direct script access allowed');
$users      = $users ?? [];
$filters    = $filters ?? [];
$groups     = $groups ?? [];
$pagination = $pagination ?? ['page' => 1, 'total_pages' => 1, 'total' => 0];
$access_base = rtrim($access_base ?? site_url('users'), '/');
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/libraries_crud.css'); ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/user_management.css'); ?>">

<div class="libx-workspace umgmt-workspace">
  <header class="libx-hero-card">
    <div class="libx-hero-main">
      <p class="libx-eyebrow">Administration</p>
      <h1 class="libx-title">User Management</h1>
      <p class="libx-subtitle">Manage LMS accounts, Aauth groups, and permission overrides.</p>
    </div>
    <div class="libx-hero-actions">
      <a href="<?= base_url('index.php/permissions/groups'); ?>" class="libx-btn libx-btn-ghost">Group Permissions</a>
    </div>
    <div class="libx-hero-stats">
      <div class="libx-stat"><span class="libx-stat-val"><?= (int) $pagination['total'] ?></span><span class="libx-stat-lbl">Users</span></div>
    </div>
  </header>

  <div class="libx-toolbar-card">
    <form method="get" action="<?= htmlspecialchars($access_base, ENT_QUOTES); ?>" class="libx-toolbar umgmt-filters" id="umgmtFilterForm">
      <div class="libx-search-wrap">
        <svg class="libx-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="search" name="q" class="libx-input libx-search" placeholder="Search name, ID, email, office…"
               value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES); ?>">
      </div>
      <select name="status" class="libx-input umgmt-select">
        <option value="">All statuses</option>
        <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
      </select>
      <select name="role" class="libx-input umgmt-select">
        <option value="">All roles</option>
        <option value="admin" <?= ($filters['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
        <option value="teacher" <?= ($filters['role'] ?? '') === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
        <option value="employee" <?= ($filters['role'] ?? '') === 'employee' ? 'selected' : ''; ?>>Employee</option>
      </select>
      <select name="group_id" class="libx-input umgmt-select">
        <option value="0">All groups</option>
        <?php foreach ($groups as $g): ?>
        <option value="<?= (int) $g->id; ?>" <?= (int) ($filters['group_id'] ?? 0) === (int) $g->id ? 'selected' : ''; ?>>
          <?= htmlspecialchars($g->name ?? '', ENT_QUOTES); ?>
        </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="libx-btn libx-btn-ghost">Apply</button>
    </form>
  </div>

  <div class="libx-table-card">
    <div class="libx-table-wrap">
      <table class="libx-table" id="umgmtUsersTable">
        <thead>
          <tr>
            <th>Employee ID</th>
            <th>Full Name</th>
            <th>Office</th>
            <th>Email</th>
            <th>Status</th>
            <th>Assigned Roles</th>
            <th>Last Login</th>
            <th class="libx-th-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
          <tr><td colspan="8" class="libx-empty">No users found.</td></tr>
          <?php else: ?>
          <?php foreach ($users as $u): ?>
          <tr data-user-id="<?= (int) $u->id; ?>">
            <td><?= htmlspecialchars($u->employee_id ?? '—', ENT_QUOTES); ?></td>
            <td><?= htmlspecialchars($u->fullname ?? '—', ENT_QUOTES); ?></td>
            <td><?= htmlspecialchars($u->office ?? '—', ENT_QUOTES); ?></td>
            <td><?= htmlspecialchars($u->email ?? '—', ENT_QUOTES); ?></td>
            <td><span class="umgmt-badge umgmt-badge-<?= htmlspecialchars(strtolower($u->status ?? 'inactive'), ENT_QUOTES); ?>"><?= htmlspecialchars(ucfirst($u->status ?? '—'), ENT_QUOTES); ?></span></td>
            <td class="umgmt-groups-cell" data-user-id="<?= (int) $u->id; ?>"><?= htmlspecialchars($u->group_labels ?? '—', ENT_QUOTES); ?></td>
            <td><?= ! empty($u->last_login) ? htmlspecialchars(date('Y-m-d H:i', strtotime($u->last_login)), ENT_QUOTES) : 'Never'; ?></td>
            <td class="libx-actions">
              <button type="button" class="libx-action libx-action-view umgmt-btn-view-access" data-user-id="<?= (int) $u->id; ?>">View Permissions</button>
              <button type="button" class="libx-action libx-action-edit umgmt-btn-access" data-user-id="<?= (int) $u->id; ?>">Manage Permissions</button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ((int) ($pagination['total_pages'] ?? 1) > 1): ?>
  <nav class="umgmt-pagination" aria-label="Users pagination">
    <?php
    $page = (int) ($pagination['page'] ?? 1);
    $tp   = (int) ($pagination['total_pages'] ?? 1);
    $qs   = $_GET;
    for ($p = 1; $p <= $tp; $p++):
        $qs['page'] = $p;
        $href = $access_base . '?' . http_build_query($qs);
    ?>
    <a href="<?= htmlspecialchars($href, ENT_QUOTES); ?>" class="umgmt-page-link <?= $p === $page ? 'is-active' : ''; ?>"><?= $p; ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</div>

<?php $this->load->view('administrator/users/access_modal'); ?>

<script>
window.UMGMT = <?= json_encode([
    'baseUrl'    => $access_base,
    'csrfName'   => $csrf_field_name ?? '',
    'csrfHash'   => $csrf_hash ?? '',
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>
<script src="<?= base_url('assets/js/user_management.js'); ?>"></script>
