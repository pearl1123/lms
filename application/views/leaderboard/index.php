<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$tab          = $tab ?? 'global';
$board        = $board ?? ['rows' => [], 'viewer_points' => 0, 'viewer_rank' => null];
$schema_ready = ! empty($schema_ready);
$department   = trim((string) ($department ?? ''));
$rows         = $board['rows'] ?? [];
?>
<div class="page-header d-print-none mb-4">
  <div class="row align-items-center">
    <div class="col">
      <h2 class="page-title">Leaderboard</h2>
      <div class="text-secondary">Earn points by completing courses, passing assessments, and earning certificates.</div>
    </div>
  </div>
</div>

<?php if ( ! $schema_ready): ?>
<div class="alert alert-warning">
  The points system is not available yet. Ask your administrator to run <code>application/sql/migration_enhancement_phase6.sql</code>.
</div>
<?php else: ?>

<div class="btn-list mb-4">
  <a href="<?= base_url('index.php/leaderboard?tab=global') ?>" class="btn <?= $tab === 'global' ? 'btn-primary' : 'btn-outline-primary' ?>">Global</a>
  <a href="<?= base_url('index.php/leaderboard?tab=department') ?>" class="btn <?= $tab === 'department' ? 'btn-primary' : 'btn-outline-primary' ?>">My department</a>
  <a href="<?= base_url('index.php/leaderboard?tab=monthly') ?>" class="btn <?= $tab === 'monthly' ? 'btn-primary' : 'btn-outline-primary' ?>">This month</a>
</div>

<?php if ($tab === 'department' && $department === ''): ?>
<div class="alert alert-info">Your profile has no department set. Showing global rankings instead.</div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-body d-flex flex-wrap gap-3 align-items-center">
    <div><strong>Your points:</strong> <?= (int) ($board['viewer_points'] ?? 0) ?></div>
    <?php if ( ! empty($board['viewer_rank'])): ?>
    <div><strong>Your rank:</strong> #<?= (int) $board['viewer_rank'] ?></div>
    <?php endif; ?>
    <?php if ($tab === 'monthly' && ! empty($board['period_label'])): ?>
    <div class="text-secondary"><?= htmlspecialchars((string) $board['period_label'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-vcenter card-table">
      <thead>
        <tr>
          <th style="width:4rem;">#</th>
          <th>Learner</th>
          <th>Department</th>
          <th class="text-end">Points</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="4" class="text-secondary">No points recorded yet.</td></tr>
        <?php else: foreach ($rows as $row): ?>
        <tr>
          <td><?= (int) ($row->rank ?? 0) ?></td>
          <td>
            <div class="fw-semibold"><?= htmlspecialchars((string) ($row->fullname ?? 'Learner'), ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ( ! empty($row->employee_id)): ?>
            <div class="text-secondary small"><?= htmlspecialchars((string) $row->employee_id, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars((string) ($row->department ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-end fw-bold"><?= (int) ($row->points_display ?? 0) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
