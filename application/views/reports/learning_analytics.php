<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="rpt-section" id="rpt-section-learning" data-rpt-section="learning" data-rpt-keywords="learning analytics enrollment completion trend engagement" hidden>
  <div class="rpt-chart-grid rpt-chart-grid--2">
    <article class="rpt-card">
      <header class="rpt-card-hdr">
        <h2 class="rpt-card-title">Enrollment trends</h2>
        <p class="rpt-card-desc">Approved enrollments by month</p>
      </header>
      <div class="rpt-card-body">
        <div class="rpt-chart-wrap"><canvas id="rptEnrollmentTrend" aria-label="Enrollment trends"></canvas></div>
      </div>
    </article>
    <article class="rpt-card">
      <header class="rpt-card-hdr">
        <h2 class="rpt-card-title">Completion distribution</h2>
        <p class="rpt-card-desc">Learner progress buckets across all courses</p>
      </header>
      <div class="rpt-card-body">
        <div class="rpt-chart-wrap rpt-chart-wrap--sm"><canvas id="rptCompletionDist" aria-label="Completion distribution"></canvas></div>
      </div>
    </article>
    <article class="rpt-card">
      <header class="rpt-card-hdr">
        <h2 class="rpt-card-title">Platform growth</h2>
        <p class="rpt-card-desc">New user accounts over time</p>
      </header>
      <div class="rpt-card-body">
        <div class="rpt-chart-wrap"><canvas id="rptUsersGrowth" aria-label="User growth"></canvas></div>
      </div>
    </article>
    <article class="rpt-card">
      <header class="rpt-card-hdr">
        <h2 class="rpt-card-title">Recent enrollments</h2>
        <p class="rpt-card-desc">Latest approved enrollments</p>
      </header>
      <div class="rpt-card-body">
        <?php $recent = $report['recent_enrollments'] ?? []; ?>
        <?php if (empty($recent)): ?>
          <?php $this->load->view('components/empty_state', [
            'emoji' => '📊',
            'title' => 'No enrollment activity yet',
            'description' => 'Publish courses and approve enrollments to see learning analytics.',
            'cta_href' => site_url('manage_courses'),
            'cta_label' => 'Manage courses',
          ]); ?>
        <?php else: ?>
        <ul class="rpt-list">
          <?php foreach ($recent as $row): ?>
          <li class="rpt-list-item">
            <div>
              <strong><?= htmlspecialchars($row->fullname ?? 'Learner') ?></strong>
              <span class="rpt-list-meta"><?= htmlspecialchars($row->course_title ?? '') ?></span>
            </div>
            <time class="rpt-list-time"><?= ! empty($row->enrolled_at) ? date('M j', strtotime($row->enrolled_at)) : '—' ?></time>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </article>
  </div>
</section>
