<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $c = $settings['certificates'] ?? []; ?>
<section class="stg-panel" role="tabpanel" id="stg-panel-certificates" data-stg-tab="certificates" data-stg-keywords="certificate serial verification signatory" hidden>
  <div class="stg-card">
    <div class="stg-card-hdr">
      <h2 class="stg-card-title">Certificates</h2>
      <p class="stg-card-kicker">Serial format and default certificate metadata for new courses.</p>
    </div>
    <div class="stg-card-body">
      <div class="stg-row-2">
        <div class="stg-field">
          <label class="stg-label" for="stg_serial_prefix">Default serial prefix</label>
          <input type="text" class="stg-input" id="stg_serial_prefix" name="settings[certificates][serial_prefix]"
                 maxlength="12" value="<?= htmlspecialchars($c['serial_prefix'] ?? 'CERT', ENT_QUOTES, 'UTF-8') ?>">
          <div class="stg-serial-preview" id="stgSerialPreview">CERT-<?= date('Y') ?>-0001</div>
        </div>
        <div class="stg-field">
          <label class="stg-label">Verification</label>
          <label class="stg-toggle" style="border:0;padding:0;">
            <input type="hidden" name="settings[certificates][verification_enabled]" value="0">
            <input type="checkbox" name="settings[certificates][verification_enabled]" value="1"
                   <?= ! isset($c['verification_enabled']) || $c['verification_enabled'] !== '0' ? 'checked' : '' ?>>
            <span class="stg-toggle-text">
              <strong>Public verification page</strong>
              <span>Learners can verify certificates via code at <?= base_url('certificates/verify') ?>.</span>
            </span>
          </label>
        </div>
      </div>
      <div class="stg-row-2">
        <div class="stg-field">
          <label class="stg-label" for="stg_def_sig">Default signatory name</label>
          <input type="text" class="stg-input" id="stg_def_sig" name="settings[certificates][default_signatory]"
                 value="<?= htmlspecialchars($c['default_signatory'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="stg-field">
          <label class="stg-label" for="stg_def_sig_title">Default signatory title</label>
          <input type="text" class="stg-input" id="stg_def_sig_title" name="settings[certificates][default_signatory_title]"
                 value="<?= htmlspecialchars($c['default_signatory_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
      <div class="stg-field">
        <label class="stg-label" for="stg_cert_expiry">Certificate validity (days)</label>
        <input type="number" class="stg-input" id="stg_cert_expiry" name="settings[certificates][cert_expiry_days]"
               min="1" placeholder="No expiry"
               value="<?= htmlspecialchars($c['cert_expiry_days'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <p class="stg-help">Optional platform default; individual courses can override in the course workspace.</p>
      </div>
      <div class="stg-field">
        <label class="stg-label" for="stg_cert_pdf_template">PDF template</label>
        <select class="stg-input" id="stg_cert_pdf_template" name="settings[certificates][pdf_template]">
          <?php
          $this->load->helper('certificate_pdf');
          $active_tpl = $c['pdf_template'] ?? 'official_lcp_certificate';
          foreach (ka_cert_allowed_templates() as $slug => $label):
          ?>
          <option value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"<?= $active_tpl === $slug ? ' selected' : '' ?>>
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
          </option>
          <?php endforeach; ?>
        </select>
        <p class="stg-help">Default layout for newly generated certificate PDFs. Use Regenerate on an existing certificate to apply a template change.</p>
      </div>
    </div>
  </div>
</section>
