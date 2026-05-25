<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$st = $settings['storage'] ?? [];
$stats = $storage_stats ?? [];
$bytes = (int) ($stats['uploads_bytes'] ?? 0);
$quotaMb = (int) ($st['quota_mb'] ?? ($stats['quota_mb'] ?? 1024));
$quotaBytes = max(1, $quotaMb * 1048576);
$percent = min(100, (int) round(($bytes / $quotaBytes) * 100));
$usage = [
  'human'   => $stats['uploads_label'] ?? '0 B',
  'percent' => $percent,
];
?>
<section class="stg-panel" role="tabpanel" id="stg-panel-storage" data-stg-tab="storage" data-stg-keywords="storage upload limit pdf quota files" hidden>
  <div class="stg-card">
    <div class="stg-card-hdr">
      <h2 class="stg-card-title">Storage</h2>
      <p class="stg-card-kicker">Upload limits and platform file usage.</p>
    </div>
    <div class="stg-card-body">
      <div class="stg-usage-card">
        <div class="stg-usage-head">
          <span>Uploads directory</span>
          <strong><?= htmlspecialchars($usage['human'] ?? '0 B') ?> / <?= (int) $quotaMb ?> MB</strong>
        </div>
        <div class="stg-progress" role="progressbar" aria-valuenow="<?= (int) ($usage['percent'] ?? 0) ?>" aria-valuemin="0" aria-valuemax="100">
          <div class="stg-progress-fill" style="width:<?= min(100, (int) ($usage['percent'] ?? 0)) ?>%;"></div>
        </div>
      </div>
      <div class="stg-row-2">
        <div class="stg-field">
          <label class="stg-label" for="stg_max_upload">Max upload size (MB)</label>
          <input type="number" class="stg-input" id="stg_max_upload" name="settings[storage][max_upload_mb]"
                 min="1" value="<?= htmlspecialchars($st['max_upload_mb'] ?? '50', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="stg-field">
          <label class="stg-label" for="stg_max_pdf">Max PDF size (MB)</label>
          <input type="number" class="stg-input" id="stg_max_pdf" name="settings[storage][max_pdf_mb]"
                 min="1" value="<?= htmlspecialchars($st['max_pdf_mb'] ?? '25', ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
      <div class="stg-field">
        <label class="stg-label" for="stg_allowed_types">Allowed file types</label>
        <input type="text" class="stg-input" id="stg_allowed_types" name="settings[storage][allowed_extensions]"
               placeholder="pdf,doc,docx,ppt,pptx,jpg,png,mp4"
               value="<?= htmlspecialchars($st['allowed_extensions'] ?? 'pdf,doc,docx,ppt,pptx,jpg,png,mp4', ENT_QUOTES, 'UTF-8') ?>">
        <p class="stg-help">Comma-separated extensions without dots.</p>
      </div>
      <div class="stg-field">
        <label class="stg-label" for="stg_quota">Storage quota (MB)</label>
        <input type="number" class="stg-input" id="stg_quota" name="settings[storage][quota_mb]"
               min="100" value="<?= htmlspecialchars($st['quota_mb'] ?? '2048', ENT_QUOTES, 'UTF-8') ?>">
      </div>
    </div>
  </div>
</section>
