<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Shared fallback card (404 / under construction).
 *
 * @var string $ef_icon     compass|rocket|tools
 * @var string $ef_title
 * @var string $ef_subtitle
 * @var string $ef_hint
 * @var string $ef_dashboard_url
 */
$ef_icon = $ef_icon ?? 'rocket';
$icon_map = [
    'compass' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>',
    'rocket'  => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>',
    'tools'   => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
];
$icon_svg = $icon_map[$ef_icon] ?? $icon_map['rocket'];
?>
<div class="ef-wrap">
  <div class="ef-card animate__animated animate__fadeInUp animate__fast">
    <div class="ef-icon" aria-hidden="true"><?= $icon_svg ?></div>
    <p class="ef-eyebrow">kaBAGA Academy</p>
    <h1 class="ef-title"><?= htmlspecialchars($ef_title ?? 'Feature in Development', ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="ef-subtitle"><?= htmlspecialchars($ef_subtitle ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <?php if ( ! empty($ef_hint)): ?>
    <p class="ef-hint"><?= htmlspecialchars($ef_hint, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <div class="ef-actions">
      <a href="<?= html_escape($ef_dashboard_url ?? site_url('dashboard')) ?>" class="ef-btn ef-btn--primary"><?= htmlspecialchars($ef_primary_label ?? 'Back to Dashboard', ENT_QUOTES, 'UTF-8') ?></a>
      <button type="button" class="ef-btn ef-btn--ghost" onclick="if (window.history.length > 1) { history.back(); } else { window.location.href='<?= html_escape($ef_dashboard_url ?? site_url('dashboard')) ?>'; }">Go back</button>
      <button type="button" class="ef-btn ef-btn--muted" disabled title="Coming soon">Notify admin</button>
    </div>
  </div>
</div>
