<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $b = $settings['branding'] ?? []; ?>
<section class="stg-panel" role="tabpanel" id="stg-panel-branding" data-stg-tab="branding" data-stg-keywords="branding logo favicon color accent login certificate" hidden>
  <div class="stg-card">
    <div class="stg-card-hdr">
      <h2 class="stg-card-title">Branding</h2>
      <p class="stg-card-kicker">Logos, colors, and certificate presentation for a polished organization identity.</p>
    </div>
    <div class="stg-card-body">
      <div class="stg-row-2">
        <div class="stg-field">
          <label class="stg-label">Logo</label>
          <div class="stg-upload-card">
            <?php if ( ! empty($b['logo_path']) && is_file(FCPATH . $b['logo_path'])): ?>
            <img src="<?= base_url($b['logo_path']) ?>" alt="Logo">
            <?php else: ?>
            <div style="font-size:2rem;margin-bottom:.35rem;">🖼</div>
            <?php endif; ?>
            <input type="file" name="logo_upload" class="stg-input" accept="image/*">
          </div>
        </div>
        <div class="stg-field">
          <label class="stg-label">Favicon</label>
          <div class="stg-upload-card">
            <?php if ( ! empty($b['favicon_path']) && is_file(FCPATH . $b['favicon_path'])): ?>
            <img src="<?= base_url($b['favicon_path']) ?>" alt="Favicon" style="max-height:32px;">
            <?php endif; ?>
            <input type="file" name="favicon_upload" class="stg-input" accept=".ico,.png,image/*">
          </div>
        </div>
      </div>
      <div class="stg-field">
        <label class="stg-label">Login background</label>
        <div class="stg-upload-card">
          <?php if ( ! empty($b['login_bg_path']) && is_file(FCPATH . $b['login_bg_path'])): ?>
          <img src="<?= base_url($b['login_bg_path']) ?>" alt="Login background" style="max-height:80px;border-radius:8px;">
          <?php endif; ?>
          <input type="file" name="login_bg_upload" class="stg-input" accept="image/*">
        </div>
      </div>
      <div class="stg-row-2">
        <div class="stg-field">
          <label class="stg-label" for="stgAccentColor">Accent color</label>
          <input type="color" class="stg-input" id="stgAccentColor" name="settings[branding][accent_color]"
                 value="<?= htmlspecialchars($b['accent_color'] ?? '#6dabcf', ENT_QUOTES, 'UTF-8') ?>" style="height:42px;padding:.25rem;">
          <span class="stg-preview-chip" id="stgAccentChip" aria-hidden="true"></span>
        </div>
        <div class="stg-field">
          <label class="stg-label" for="stg_sidebar_style">Sidebar style</label>
          <select class="stg-select" id="stg_sidebar_style" name="settings[branding][sidebar_style]">
            <?php $ss = $b['sidebar_style'] ?? 'navy'; ?>
            <option value="navy" <?= $ss === 'navy' ? 'selected' : '' ?>>Navy (default)</option>
            <option value="slate" <?= $ss === 'slate' ? 'selected' : '' ?>>Slate</option>
            <option value="light" <?= $ss === 'light' ? 'selected' : '' ?>>Light</option>
          </select>
        </div>
      </div>
      <div class="stg-field">
        <label class="stg-label" for="stg_cert_banner">Certificate banner text</label>
        <input type="text" class="stg-input" id="stg_cert_banner" name="settings[branding][certificate_banner]"
               placeholder="Optional header line on PDF certificates"
               value="<?= htmlspecialchars($b['certificate_banner'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
    </div>
  </div>
</section>
