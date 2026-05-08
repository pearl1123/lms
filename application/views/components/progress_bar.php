<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Shared course / module progress bar (mv-progress styles).
 *
 * @var int         $progress_percent 0–100
 * @var string|null $size            sm|md|lg
 * @var string|null $label
 * @var int|null    $course_id       for live sync (data-course-id)
 * @var string|null $variant         panel|embed|module_sidebar|catalog_overlay|module_list_row|list_micro
 * @var bool|null   $sync_live       add cmp-progress-sync when true
 */
$pct = max(0, min(100, (int) ($progress_percent ?? 0)));
$size = $size ?? 'md';
if ( ! in_array($size, ['sm', 'md', 'lg'], true)) {
    $size = 'md';
}
$label = isset($label) ? (string) $label : 'Progress';
$course_id = (int) ($course_id ?? 0);
$variant = $variant ?? 'panel';
$sync_live = ! empty($sync_live) && $course_id > 0;
$complete = $pct >= 100;

$sync_cls = $sync_live ? ' cmp-progress-sync' : '';
$data_cid = $course_id > 0 ? ' data-course-id="' . $course_id . '"' : '';

$size_cls = ' cmp-pb--' . htmlspecialchars($size, ENT_QUOTES, 'UTF-8');
$fill_cls = 'mv-progress-fill cmp-pb-fill' . ($complete ? ' cmp-pb-fill--complete' : '');

if ($variant === 'catalog_overlay') : ?>
<div class="cat-card-progress-overlay<?= $sync_cls ?>"<?= $data_cid ?>>
  <div class="<?= htmlspecialchars($fill_cls, ENT_QUOTES, 'UTF-8') ?> cmp-pb-overlay-fill cat-card-progress-fill" style="width:<?= $pct ?>%;"></div>
</div>
<?php elseif ($variant === 'module_list_row') : ?>
<div class="cd-module-progress-wrap mv-progress mv-progress--embed"<?= $data_cid ?>>
  <div class="cd-module-progress-top">
    <span><?= htmlspecialchars($label) ?></span>
    <span class="cmp-pb-pct"><?= $pct ?>%</span>
  </div>
  <div class="mv-progress-track inline">
    <div class="mv-progress-fill inline cmp-pb-fill<?= $complete ? ' cmp-pb-fill--complete' : '' ?>" style="--module-progress-pct: <?= $pct ?>%;"></div>
  </div>
</div>
<?php elseif ($variant === 'list_micro') : ?>
<div class="cat-list-progress mv-progress mv-progress--embed<?= $sync_cls ?>"<?= $data_cid ?>>
  <div class="mv-progress-track<?= $size_cls ?>" style="height:5px;border-radius:3px;">
    <div class="<?= htmlspecialchars($fill_cls, ENT_QUOTES, 'UTF-8') ?>" style="width:<?= $pct ?>%;"></div>
  </div>
  <div class="cmp-pb-pct cmp-pb-pct--micro" style="font-size:.625rem;color:var(--ka-text-muted,#64748b);margin-top:3px;text-align:right;"><?= $pct ?>%</div>
</div>
<?php elseif ($variant === 'module_sidebar') : ?>
<div class="mv-progress animate__animated animate__fadeInDown animate__fast<?= $sync_cls ?>"<?= $data_cid ?>>
  <div class="mv-progress-hdr">
    <span><?= htmlspecialchars($label) ?></span>
    <span id="mvProgressPct" class="cmp-pb-pct">0%</span>
  </div>
  <div class="mv-progress-track">
    <div class="<?= htmlspecialchars($fill_cls, ENT_QUOTES, 'UTF-8') ?>" id="mvProgressFill" style="width:0%;"></div>
  </div>
  <div class="mv-progress-meta" id="mvProgressMeta">Tracking video checkpoints and assessments…</div>
</div>
<?php else :
    $embed = ($variant === 'embed');
    $box_cls = 'mv-progress' . $size_cls . ($embed ? ' mv-progress--embed' : '') . ($sync_cls ? $sync_cls : '');
    ?>
<div class="<?= htmlspecialchars($box_cls, ENT_QUOTES, 'UTF-8') ?>"<?= $data_cid ?>>
  <div class="mv-progress-hdr">
    <span><?= htmlspecialchars($label) ?></span>
    <span class="cmp-pb-pct<?= $complete ? ' cmp-pb-pct--complete' : '' ?>"<?= $complete ? ' data-complete="1"' : '' ?>><?= $pct ?>%</span>
  </div>
  <div class="mv-progress-track">
    <div class="<?= htmlspecialchars($fill_cls, ENT_QUOTES, 'UTF-8') ?>" style="width:<?= $pct ?>%;"></div>
  </div>
</div>
<?php endif;
