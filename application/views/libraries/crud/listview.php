<?php defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->helper('library_crud');
$lib         = $lib ?? [];
$rows        = $rows ?? [];
$filters     = $filters ?? [];
$stats       = $stats ?? ['total' => 0, 'active' => 0, 'archived' => 0];
$base_url    = $base_url ?? '';
$library_key = $library_key ?? '';
$pk          = $lib['primary_key'] ?? 'id';
$can_archive = ! empty($lib['soft_delete']);
$csrf_name   = $csrf_field_name ?? '';
$csrf_hash   = $csrf_hash ?? '';
$select_opts = $select_options ?? [];
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/libraries_crud.css') ?>">

<div class="libx-workspace animate__animated animate__fadeIn animate__fast">
  <header class="libx-hero-card">
    <div class="libx-hero-main">
      <p class="libx-eyebrow">Admin Library</p>
      <h1 class="libx-title"><?= htmlspecialchars($lib['title'] ?? 'Library', ENT_QUOTES) ?></h1>
      <?php if ( ! empty($lib['subtitle'])): ?>
      <p class="libx-subtitle"><?= htmlspecialchars($lib['subtitle'], ENT_QUOTES) ?></p>
      <?php endif; ?>
    </div>
    <div class="libx-hero-stats">
      <div class="libx-stat"><span class="libx-stat-val"><?= (int) $stats['total'] ?></span><span class="libx-stat-lbl">Listed</span></div>
      <div class="libx-stat"><span class="libx-stat-val"><?= (int) ($stats['active'] ?? 0) ?></span><span class="libx-stat-lbl">Active</span></div>
      <?php if ($can_archive): ?>
      <div class="libx-stat"><span class="libx-stat-val"><?= (int) ($stats['archived'] ?? 0) ?></span><span class="libx-stat-lbl">Archived</span></div>
      <?php endif; ?>
    </div>
  </header>

  <div class="libx-toolbar-card">
    <form method="get" action="<?= htmlspecialchars($base_url, ENT_QUOTES) ?>" class="libx-toolbar" id="libxFilterForm">
      <div class="libx-search-wrap">
        <svg class="libx-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="search" name="q" class="libx-input libx-search" placeholder="Search…"
               value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES) ?>">
      </div>
      <?php if ($can_archive): ?>
      <label class="libx-check">
        <input type="checkbox" name="show_archived" value="1" <?= ! empty($filters['include_archived']) ? 'checked' : '' ?>>
        Show archived
      </label>
      <?php endif; ?>
      <button type="submit" class="libx-btn libx-btn-ghost">Apply</button>
      <button type="button" class="libx-btn libx-btn-primary" id="libxBtnAdd">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Record
      </button>
    </form>
  </div>

  <div class="libx-table-card">
    <div class="libx-table-wrap">
      <table class="libx-table" id="libxTable">
        <thead>
          <tr>
            <?php foreach ($lib['list_columns'] ?? [] as $col): ?>
            <th<?= ! empty($col['width']) ? ' style="width:' . htmlspecialchars($col['width'], ENT_QUOTES) . '"' : '' ?>><?= htmlspecialchars($col['label'], ENT_QUOTES) ?></th>
            <?php endforeach; ?>
            <th class="libx-th-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr>
            <td colspan="<?= count($lib['list_columns'] ?? []) + 1 ?>" class="libx-empty">No records found.</td>
          </tr>
          <?php else: ?>
          <?php
          $CI = &get_instance();
          $CI->load->model('Library_crud_model', 'libx_row_model');
          $CI->libx_row_model->set_config($lib);
          foreach ($rows as $row):
            $is_archived = $can_archive && $CI->libx_row_model->is_archived_row($row);
            $row_id = $row->{$pk};
          ?>
          <tr class="<?= $is_archived ? 'libx-row-archived' : '' ?>" data-id="<?= htmlspecialchars((string) $row_id, ENT_QUOTES) ?>">
            <?php foreach ($lib['list_columns'] ?? [] as $col): ?>
            <td><?php libx_render_cell($row, $col, $lib); ?></td>
            <?php endforeach; ?>
            <td class="libx-actions">
              <?php if ( ! $is_archived): ?>
              <button type="button" class="libx-action libx-action-edit" data-id="<?= htmlspecialchars((string) $row_id, ENT_QUOTES) ?>">Edit</button>
              <?php if ($can_archive): ?>
              <button type="button" class="libx-action libx-action-delete" data-id="<?= htmlspecialchars((string) $row_id, ENT_QUOTES) ?>">Archive</button>
              <?php endif; ?>
              <?php elseif ($can_archive): ?>
              <button type="button" class="libx-action libx-action-restore" data-id="<?= htmlspecialchars((string) $row_id, ENT_QUOTES) ?>">Restore</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
$this->load->view('libraries/crud/modal_add', get_defined_vars());
$this->load->view('libraries/crud/modal_update', get_defined_vars());
?>

<script>
window.LIBX_CRUD = <?= json_encode([
    'baseUrl'      => $base_url,
    'primaryKey'   => $pk,
    'csrfName'     => $csrf_name,
    'csrfHash'     => $csrf_hash,
    'canArchive'   => $can_archive,
    'rows'         => array_map(static function ($row) use ($pk) {
        return (array) $row;
    }, $rows),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= base_url('assets/js/libraries_notify.js') ?>"></script>
<script src="<?= base_url('assets/js/libraries_crud.js') ?>"></script>
