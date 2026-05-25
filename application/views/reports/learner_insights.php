<?php defined('BASEPATH') OR exit('No direct script access allowed');
$struggling = $report['struggling'] ?? [];
$active = $report['active_learners'] ?? [];
$ready = $report['certificate_ready'] ?? [];
?>
<section class="rpt-section" id="rpt-section-learners" data-rpt-section="learners" data-rpt-keywords="learner insights active risk overdue inactive" hidden>
  <div class="rpt-split-grid">
    <article class="rpt-card">
      <header class="rpt-card-hdr">
        <h2 class="rpt-card-title">Most active learners</h2>
        <p class="rpt-card-desc">Modules completed in the last 30 days</p>
      </header>
      <div class="rpt-card-body">
        <?php if (empty($active)): ?>
          <?php $this->load->view('components/empty_state', [
            'emoji' => '👤',
            'title' => 'No learner activity yet',
            'description' => 'Learner engagement will appear once modules are completed.',
          ]); ?>
        <?php else: ?>
        <ul class="rpt-learner-list">
          <?php foreach ($active as $row): ?>
          <li class="rpt-learner-card">
            <div class="rpt-learner-avatar"><?= htmlspecialchars(substr((string) ($row->fullname ?? 'L'), 0, 1)) ?></div>
            <div>
              <strong><?= htmlspecialchars($row->fullname ?? '') ?></strong>
              <span class="rpt-list-meta"><?= htmlspecialchars($row->employee_id ?? '') ?></span>
            </div>
            <span class="rpt-pill rpt-pill--ok"><?= (int) ($row->completed_cnt ?? 0) ?> modules</span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </article>

    <article class="rpt-card">
      <header class="rpt-card-hdr">
        <h2 class="rpt-card-title">Learners at risk</h2>
        <p class="rpt-card-desc">Low progress on approved enrollments</p>
      </header>
      <div class="rpt-card-body">
        <?php if (empty($struggling)): ?>
          <?php $this->load->view('components/empty_state', [
            'emoji' => '✓',
            'title' => 'No at-risk learners',
            'description' => 'All tracked enrollments are progressing normally.',
          ]); ?>
        <?php else: ?>
        <ul class="rpt-learner-list">
          <?php foreach ($struggling as $row): ?>
          <li class="rpt-learner-card">
            <div class="rpt-learner-avatar rpt-learner-avatar--warn"><?= htmlspecialchars(substr((string) ($row->fullname ?? 'L'), 0, 1)) ?></div>
            <div>
              <strong><?= htmlspecialchars($row->fullname ?? '') ?></strong>
              <span class="rpt-list-meta"><?= htmlspecialchars($row->course_title ?? '') ?></span>
            </div>
            <span class="rpt-pill rpt-pill--warn"><?= (int) ($row->progress_pct ?? 0) ?>%</span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </article>
  </div>

  <?php if ( ! empty($ready)): ?>
  <article class="rpt-card" style="margin-top:1rem;">
    <header class="rpt-card-hdr">
      <h2 class="rpt-card-title">Certificate-ready learners</h2>
      <p class="rpt-card-desc">Completed all modules — certificate not yet issued</p>
    </header>
    <div class="rpt-card-body">
      <ul class="rpt-list">
        <?php foreach ($ready as $row): ?>
        <li class="rpt-list-item">
          <div>
            <strong><?= htmlspecialchars($row->fullname ?? '') ?></strong>
            <span class="rpt-list-meta"><?= htmlspecialchars($row->course_title ?? '') ?></span>
          </div>
          <span class="rpt-pill">Ready</span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </article>
  <?php endif; ?>
</section>
