<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$shell = $shell_editor ?? [];
$shell_partial = $shell_partial ?? '';
$shell_mode    = (string) ($shell['mode'] ?? 'exam');
$shell_title   = (string) ($shell['title'] ?? 'Assessment');
$shell_sub     = (string) ($shell['subtitle'] ?? '');
$shell_type    = (string) ($shell['type_label'] ?? '');
$shell_type_color = (string) ($shell['type_color'] ?? '#6dabcf');
$shell_status  = (string) ($shell['status'] ?? 'Draft');
$shell_back_url = (string) ($shell['back_url'] ?? base_url('index.php/assessments'));
$shell_back_label = (string) ($shell['back_label'] ?? 'Back');
$shell_id      = (int) ($shell['assessment_id'] ?? 0);
$is_video_ws   = ($shell_mode === 'checkpoint');
$nav_items     = is_array($shell['nav'] ?? null) ? $shell['nav'] : [];
if (empty($nav_items)) {
    $nav_items = [
        ['id' => 'overview', 'label' => 'Overview', 'icon' => '◆'],
        ['id' => 'content', 'label' => $is_video_ws ? 'Checkpoints' : 'Questions', 'icon' => '✎'],
        ['id' => 'settings', 'label' => 'Settings', 'icon' => '⚙'],
        ['id' => 'publish', 'label' => 'Publish', 'icon' => '↗'],
    ];
}
?>
<link rel="stylesheet" href="<?= base_url('assets/css/assessments.css') ?>">

<div class="asx-editor asx-editor--<?= htmlspecialchars($shell_mode, ENT_QUOTES) ?>" id="asxEditorRoot" data-editor-mode="<?= htmlspecialchars($shell_mode, ENT_QUOTES) ?>">
  <header class="asx-editor-topbar">
    <div class="asx-editor-topbar-left">
      <a href="<?= htmlspecialchars($shell_back_url, ENT_QUOTES) ?>" class="asx-editor-back"><?= htmlspecialchars($shell_back_label) ?></a>
      <div class="asx-editor-title-wrap">
        <h1 class="asx-editor-title" id="asxEditorTitleDisplay"><?= htmlspecialchars($shell_title, ENT_QUOTES) ?></h1>
        <?php if ($shell_sub !== ''): ?>
        <p class="asx-editor-subtitle"><?= htmlspecialchars($shell_sub) ?></p>
        <?php endif; ?>
      </div>
      <?php if ($shell_type !== ''): ?>
      <span class="asx-editor-type-pill" style="--asx-type:<?= htmlspecialchars($shell_type_color, ENT_QUOTES) ?>"><?= htmlspecialchars($shell_type) ?></span>
      <?php endif; ?>
      <span class="asx-editor-status asx-editor-status--draft"><?= htmlspecialchars($shell_status) ?></span>
    </div>
    <div class="asx-editor-topbar-right">
      <span class="asx-editor-autosave" id="asxAutosaveIndicator" aria-live="polite">
        <span class="asx-editor-autosave-dot"></span>
        <span class="asx-editor-autosave-text">All changes saved</span>
      </span>
      <?php if ( ! empty($shell['save_form_id'])): ?>
      <button type="submit" form="<?= htmlspecialchars($shell['save_form_id'], ENT_QUOTES) ?>" class="asx-editor-save-btn" id="asxEditorSaveBtn">
        Save assessment
      </button>
      <?php elseif ( ! empty($shell['save_btn_id'])): ?>
      <button type="button" class="asx-editor-save-btn" id="<?= htmlspecialchars($shell['save_btn_id'], ENT_QUOTES) ?>">
        <?= htmlspecialchars($shell['save_label'] ?? 'Save', ENT_QUOTES) ?>
      </button>
      <?php endif; ?>
    </div>
  </header>

  <div class="asx-editor-frame">
    <aside class="asx-editor-sidebar" aria-label="Assessment sections">
      <nav class="asx-editor-nav">
        <?php foreach ($nav_items as $item):
          $nid = (string) ($item['id'] ?? '');
          $active = ! empty($item['active']);
        ?>
        <button type="button"
                class="asx-editor-nav-item <?= $active ? 'is-active' : '' ?>"
                data-asx-nav="<?= htmlspecialchars($nid, ENT_QUOTES) ?>"
                <?= ! empty($item['hidden']) ? 'style="display:none"' : '' ?>>
          <span class="asx-editor-nav-icon" aria-hidden="true"><?= $item['icon'] ?? '•' ?></span>
          <span class="asx-editor-nav-label"><?= htmlspecialchars($item['label'] ?? $nid, ENT_QUOTES) ?></span>
        </button>
        <?php endforeach; ?>
      </nav>
      <?php if ( ! empty($shell['sidebar_footer'])): ?>
      <div class="asx-editor-sidebar-foot"><?= $shell['sidebar_footer'] ?></div>
      <?php endif; ?>
    </aside>

    <div class="asx-editor-main">
      <?php if ($shell_partial !== ''): ?>
        <?php $this->load->view($shell_partial, get_defined_vars()); ?>
      <?php endif; ?>
    </div>

    <?php if ( ! empty($shell_rail_partial)): ?>
    <aside class="asx-editor-rail" aria-label="Checkpoint navigator">
      <?php $this->load->view($shell_rail_partial, get_defined_vars()); ?>
    </aside>
    <?php endif; ?>
  </div>
</div>

<script src="<?= base_url('assets/js/assessment_editor.js') ?>"></script>
