<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Premium empty state (Phase 4 SaaS UI)
 *
 * @var string      $emoji        Single emoji (default 📦)
 * @var string      $title
 * @var string      $description
 * @var string|null $cta_href     Optional link URL
 * @var string|null $cta_label    Optional CTA label
 * @var string      $modifier     Extra CSS classes e.g. "ka-empty--wide"
 * @var string|null $id          Optional HTML id
 */
$emoji       = isset($emoji) ? (string) $emoji : '📦';
$title       = isset($title) ? (string) $title : 'Nothing here yet';
$description = isset($description) ? (string) $description : '';
$cta_href    = isset($cta_href) ? (string) $cta_href : '';
$cta_label   = isset($cta_label) ? (string) $cta_label : '';
$modifier    = isset($modifier) ? (string) $modifier : '';
$id_attr     = ! empty($id) ? ' id="' . html_escape((string) $id) . '"' : '';
?>
<div class="ka-empty <?= html_escape(trim($modifier)) ?>"<?= $id_attr ?>>
  <div class="ka-empty-icon" aria-hidden="true"><?= htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8') ?></div>
  <h3 class="ka-empty-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
  <?php if ($description !== ''): ?>
    <p class="ka-empty-text"><?= $description ?></p>
  <?php endif; ?>
  <?php if ($cta_href !== '' && $cta_label !== ''): ?>
    <a class="ka-empty-cta" href="<?= html_escape($cta_href) ?>"><?= htmlspecialchars($cta_label, ENT_QUOTES, 'UTF-8') ?></a>
  <?php endif; ?>
</div>
