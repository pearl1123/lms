<?php defined('BASEPATH') OR exit('No direct script access allowed');
$top = $report['top_courses'] ?? [];
?>
<section class="rpt-section" id="rpt-section-courses" data-rpt-section="courses" data-rpt-keywords="course performance top enrollment progress" hidden>
  <article class="rpt-card">
    <header class="rpt-card-hdr">
      <h2 class="rpt-card-title">Course performance</h2>
      <p class="rpt-card-desc">Top courses by enrollment with average learner progress</p>
    </header>
    <div class="rpt-card-body">
      <?php if (empty($top)): ?>
        <?php $this->load->view('components/empty_state', [
          'emoji' => '📚',
          'title' => 'No course data yet',
          'description' => 'Create and publish courses to track performance analytics.',
          'cta_href' => site_url('manage_courses/create'),
          'cta_label' => 'Create course',
        ]); ?>
      <?php else: ?>
      <div class="rpt-course-grid">
        <?php foreach ($top as $i => $c): ?>
        <div class="rpt-course-card">
          <div class="rpt-course-rank"><?= (int) $i + 1 ?></div>
          <div class="rpt-course-body">
            <h3 class="rpt-course-title"><?= htmlspecialchars($c->course_title ?? 'Course') ?></h3>
            <p class="rpt-course-meta"><?= (int) ($c->enrollment_count ?? 0) ?> enrollments</p>
            <div class="rpt-progress">
              <div class="rpt-progress-bar" style="width:<?= min(100, (int) ($c->avg_progress ?? 0)) ?>%"></div>
            </div>
            <span class="rpt-pill"><?= (int) ($c->avg_progress ?? 0) ?>% avg progress</span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </article>
</section>
