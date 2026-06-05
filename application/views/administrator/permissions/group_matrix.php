<?php defined('BASEPATH') OR exit('No direct script access allowed');
$flat_perms   = $flat_perms ?? [];
$group_matrix = $group_matrix ?? [];
$can_edit     = ! empty($can_edit);
$base         = rtrim($permissions_base ?? site_url('permissions'), '/');
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/libraries_crud.css'); ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/user_management.css'); ?>">

<div class="libx-workspace umgmt-workspace">
  <header class="libx-hero-card">
    <div class="libx-hero-main">
      <p class="libx-eyebrow">Administration</p>
      <h1 class="libx-title">Group Permission Matrix</h1>
      <p class="libx-subtitle">Assign permissions to Aauth groups. Users inherit group permissions unless overridden.</p>
    </div>
    <div class="libx-hero-actions">
      <a href="<?= base_url('index.php/users'); ?>" class="libx-btn libx-btn-ghost">User Management</a>
      <?php if ($can_edit): ?>
      <button type="button" class="libx-btn libx-btn-primary" id="permBtnSync">Sync Permission Catalog</button>
      <?php endif; ?>
    </div>
  </header>

  <?php if ($flat_perms === []): ?>
  <div class="libx-table-card">
    <p class="libx-empty p-4">No permission modules configured.
      <?php if ($can_edit): ?> Click <strong>Sync Permission Catalog</strong> to generate LMS permissions from the manifest.<?php endif; ?>
    </p>
  </div>
  <?php else: ?>
  <div class="libx-table-card umgmt-gmatrix-wrap">
    <div class="libx-table-wrap">
      <table class="umgmt-gmatrix" id="permGroupMatrix">
        <thead>
          <tr>
            <th class="umgmt-gmatrix-sticky">Permission</th>
            <th class="umgmt-gmatrix-sticky2">Module</th>
            <?php foreach ($group_matrix as $gm): ?>
            <th class="umgmt-gmatrix-group" title="<?= htmlspecialchars($gm['group']->definition ?? '', ENT_QUOTES); ?>">
              <?= htmlspecialchars($gm['group']->name ?? '', ENT_QUOTES); ?>
            </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($flat_perms as $perm): ?>
          <tr data-perm-id="<?= (int) $perm['perm_id']; ?>">
            <td class="umgmt-gmatrix-sticky"><code><?= htmlspecialchars($perm['name'], ENT_QUOTES); ?></code></td>
            <td class="umgmt-gmatrix-sticky2 text-muted small">
              <?= htmlspecialchars($perm['module_main'] . ' → ' . $perm['module_sub'], ENT_QUOTES); ?>
            </td>
            <?php foreach ($group_matrix as $gm):
              $gid = (int) $gm['group']->id;
              $on  = in_array((int) $perm['perm_id'], $gm['perm_ids'], true);
            ?>
            <td class="text-center">
              <?php if ($can_edit): ?>
              <input type="checkbox"
                     class="umgmt-gmatrix-cb"
                     data-group-id="<?= $gid; ?>"
                     data-perm-id="<?= (int) $perm['perm_id']; ?>"
                     <?= $on ? 'checked' : ''; ?>
                     aria-label="<?= htmlspecialchars(($gm['group']->name ?? '') . ' — ' . $perm['name'], ENT_QUOTES); ?>">
              <?php else: ?>
              <span class="umgmt-gmatrix-ro"><?= $on ? '✓' : '—'; ?></span>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if ($can_edit): ?>
  <p class="umgmt-hint mt-2">Changes save per group when you toggle a checkbox.</p>
  <?php endif; ?>
  <?php endif; ?>
</div>

<script>
window.PERM_GROUPS = {
  baseUrl: <?= json_encode($base); ?>,
  canEdit: <?= $can_edit ? 'true' : 'false'; ?>,
  csrfName: <?= json_encode($csrf_field_name ?? ''); ?>,
  csrfHash: <?= json_encode($csrf_hash ?? ''); ?>,
  groups: <?= json_encode(array_map(static function ($gm) {
      return ['id' => (int) $gm['group']->id, 'name' => (string) ($gm['group']->name ?? '')];
  }, $group_matrix)); ?>
};
</script>
<script src="<?= base_url('assets/js/group_permissions.js'); ?>"></script>
