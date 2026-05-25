<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$stats = is_array($stats ?? null) ? $stats : [];
$recent_activity = is_array($recent_activity ?? null) ? $recent_activity : [];
?>
<section class="prf-panel" role="tabpanel" id="prf-panel-activity" data-prf-tab="activity" aria-labelledby="prf-nav-activity" hidden>
  <div class="prf-stat-grid">
    <div class="prf-stat">
      <div class="prf-stat-value"><?= (int) ($stats['enrolled'] ?? 0) ?></div>
      <div class="prf-stat-label">Enrolled courses</div>
    </div>
    <div class="prf-stat">
      <div class="prf-stat-value"><?= (int) ($stats['completed'] ?? 0) ?></div>
      <div class="prf-stat-label">Completed</div>
    </div>
    <div class="prf-stat">
      <div class="prf-stat-value"><?= (int) ($stats['certificates'] ?? 0) ?></div>
      <div class="prf-stat-label">Certificates</div>
    </div>
    <div class="prf-stat">
      <div class="prf-stat-value"><?= (int) ($stats['invitations'] ?? 0) ?></div>
      <div class="prf-stat-label">Pending invitations</div>
    </div>
  </div>

  <div class="prf-card">
    <div class="prf-card-hdr">
      <h2 class="prf-card-title">Recent learning activity</h2>
      <p class="prf-card-kicker">Latest enrollments and certificates on your account.</p>
    </div>
    <div class="prf-card-body">
      <?php if ( ! empty($recent_activity)): ?>
      <ul class="prf-activity-list">
        <?php foreach ($recent_activity as $item): ?>
        <?php
          $icon = ($item->type ?? '') === 'certificate' ? '🏆' : '📚';
          $at = ! empty($item->at) ? date('M j, Y', strtotime((string) $item->at)) : '';
        ?>
        <li class="prf-activity-item">
          <span class="prf-activity-icon" aria-hidden="true"><?= $icon ?></span>
          <div>
            <p class="prf-activity-label"><?= htmlspecialchars($item->label ?? 'Activity', ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($at !== ''): ?>
            <p class="prf-activity-date"><?= htmlspecialchars($at, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?>
      <?php $this->load->view('components/empty_state', [
          'emoji'       => '📭',
          'title'       => 'No recent activity',
          'description' => 'Enroll in a course or complete learning to see activity here.',
          'cta_href'    => base_url('my_courses'),
          'cta_label'   => 'Browse my courses',
          'modifier'    => 'ka-empty--wide',
      ]); ?>
      <?php endif; ?>
    </div>
  </div>
</section>
