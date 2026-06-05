<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$panels    = $checkpoint_workspace['panels'] ?? [];
$active_id = (int) ($checkpoint_workspace['active_id'] ?? 0);
if (empty($panels)) {
    return;
}
?>
<div class="asx-rail-inner">
  <h3 class="asx-rail-title">Checkpoint navigator</h3>
  <ul class="asx-rail-list" id="cpwNavigatorList">
    <?php foreach ($panels as $i => $panel):
      $aid = (int) $panel['assessment']->id;
      $done = ! empty($panel['has_question']);
      $ts = (int) ($panel['trigger_seconds'] ?? 0);
    ?>
    <li>
      <button type="button"
              class="asx-rail-item <?= $aid === $active_id ? 'is-active' : '' ?> <?= $done ? 'is-done' : '' ?>"
              data-cpw-nav="<?= $aid ?>">
        <span class="asx-rail-item-num"><?= $i + 1 ?></span>
        <span class="asx-rail-item-body">
          <span class="asx-rail-item-label"><?= htmlspecialchars($panel['segment_label'] ?? ('Checkpoint ' . ($i + 1)), ENT_QUOTES) ?></span>
          <span class="asx-rail-item-meta"><?= $ts > 0 ? (int) $ts . 's' : 'Start' ?> · <?= $done ? 'Ready' : 'Needs question' ?></span>
        </span>
      </button>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
