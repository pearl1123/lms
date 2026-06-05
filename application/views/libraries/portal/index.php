<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $nav_groups = $nav_groups ?? []; ?>
<?php echo $alerts_partial_html ?? ''; ?>
<link rel="stylesheet" href="<?= base_url('assets/css/libraries_crud.css') ?>">

<div class="libx-workspace animate__animated animate__fadeIn animate__fast">
  <header class="libx-hero-card">
    <div class="libx-hero-main">
      <p class="libx-eyebrow">Admin</p>
      <h1 class="libx-title">Libraries</h1>
      <p class="libx-subtitle">Manage lookup, reference, and configuration tables from the LMS schema. All modules use soft archive where supported.</p>
    </div>
  </header>

  <?php foreach ($nav_groups as $group): ?>
  <section class="libx-portal-group">
    <h2 class="libx-portal-heading"><?= htmlspecialchars($group['label'], ENT_QUOTES) ?></h2>
    <div class="libx-portal-grid">
      <?php foreach ($group['items'] as $item): ?>
      <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES) ?>" class="libx-portal-card">
        <span class="libx-portal-card-title"><?= htmlspecialchars($item['title'], ENT_QUOTES) ?></span>
        <?php if ( ! empty($item['custom'])): ?>
        <span class="libx-badge libx-badge--neutral">Custom UI</span>
        <?php endif; ?>
        <span class="libx-portal-card-arrow">→</span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endforeach; ?>
</div>

<style>
.libx-portal-group { margin-bottom: 1.5rem; }
.libx-portal-heading { font-size: 0.8125rem; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; margin: 0 0 0.75rem; }
.libx-portal-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.75rem; }
.libx-portal-card {
  display: flex; flex-direction: column; gap: 0.35rem;
  padding: 1rem 1.125rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
  text-decoration: none; color: inherit; transition: box-shadow 0.15s, border-color 0.15s;
}
.libx-portal-card:hover { border-color: #94a3b8; box-shadow: 0 4px 12px rgba(15,23,42,0.08); }
.libx-portal-card-title { font-weight: 700; font-size: 0.9375rem; color: #0f172a; }
.libx-portal-card-arrow { font-size: 0.875rem; color: #2563eb; margin-top: auto; }
.libx-color-swatch { display: inline-block; width: 14px; height: 14px; border-radius: 4px; vertical-align: middle; border: 1px solid rgba(0,0,0,0.1); }
</style>
<script src="<?= base_url('assets/js/libraries_notify.js') ?>"></script>
