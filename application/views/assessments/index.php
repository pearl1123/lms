<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$assessments = $assessments ?? [];
$groups      = $assessment_groups ?? [];
if ($groups === [] && ! empty($assessments)) {
    $tmp = [];
    foreach ($assessments as $row) {
        $cid = (int) ($row->course_id ?? 0);
        $key = $cid > 0 ? (string) $cid : '0';
        if ( ! isset($tmp[$key])) {
            $tmp[$key] = [
                'course_id'    => $cid,
                'course_title' => (string) ($row->course_title ?? 'Unassigned course'),
                'assessments'  => [],
            ];
        }
        $tmp[$key]['assessments'][] = $row;
    }
    $groups = array_values($tmp);
}
$stats_defaults = ['total' => 0, 'pre_count' => 0, 'post_count' => 0, 'checkpoint_count' => 0];
$stats_raw      = $assessment_stats ?? [];
$stats          = array_merge($stats_defaults, is_array($stats_raw) ? $stats_raw : []);
$is_manager     = ! empty($is_manager);

$type_labels = [
  'pre'        => 'Pre-Assessment',
  'post'       => 'Post-Assessment',
  'checkpoint' => 'Video Checkpoint',
];
?>
<?php echo $alerts_partial_html ?? ''; ?>

<link rel="stylesheet" href="<?= base_url('assets/css/assessments.css') ?>">

<div class="asx-workspace">
  <header class="asx-hero animate__animated animate__fadeIn animate__fast">
    <div class="asx-hero-copy">
      <p class="asx-eyebrow">Learning</p>
      <h1 class="asx-hero-title">Assessments</h1>
      <p class="asx-hero-sub">
        <?= $is_manager
          ? 'Organize and review course assessments by program.'
          : 'Select a course to view and take its assessments.' ?>
      </p>
    </div>
    <?php if ($is_manager): ?>
    <div class="asx-hero-actions">
      <a href="<?= base_url('index.php/assessments/integrity_analytics') ?>" class="asx-btn asx-btn-ghost">Integrity Analytics</a>
      <a href="<?= base_url('index.php/assessments/create') ?>" class="asx-btn asx-btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Assessment
      </a>
    </div>
    <?php endif; ?>
  </header>

  <div class="asx-kpi-strip animate__animated animate__fadeInUp animate__fast">
    <div class="asx-kpi">
      <span class="asx-kpi-val"><?= (int) $stats['total'] ?></span>
      <span class="asx-kpi-lbl">Assessments</span>
    </div>
    <div class="asx-kpi">
      <span class="asx-kpi-val"><?= count($groups) ?></span>
      <span class="asx-kpi-lbl">Courses</span>
    </div>
    <div class="asx-kpi">
      <span class="asx-kpi-val"><?= (int) $stats['pre_count'] ?></span>
      <span class="asx-kpi-lbl">Pre</span>
    </div>
    <div class="asx-kpi">
      <span class="asx-kpi-val"><?= (int) $stats['post_count'] ?></span>
      <span class="asx-kpi-lbl">Post</span>
    </div>
    <?php if ($is_manager): ?>
    <div class="asx-kpi">
      <span class="asx-kpi-val"><?= (int) ($stats['checkpoint_count'] ?? 0) ?></span>
      <span class="asx-kpi-lbl">Checkpoints</span>
    </div>
    <?php endif; ?>
  </div>

  <div class="asx-toolbar animate__animated animate__fadeInUp animate__fast asx-animate-delay-05">
    <div class="asx-search-wrap">
      <svg class="asx-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="search" class="asx-search" id="asxSearch" placeholder="Search courses or assessments…" autocomplete="off">
    </div>
    <select class="asx-select" id="asxFilterType" aria-label="Filter by assessment type">
      <option value="">All types</option>
      <option value="pre">Pre-Assessment</option>
      <option value="post">Post-Assessment</option>
      <?php if ($is_manager): ?>
      <option value="checkpoint">Video Checkpoint</option>
      <?php endif; ?>
    </select>
    <span class="asx-filter-count" id="asxCount"><?= (int) $stats['total'] ?> assessment<?= (int) $stats['total'] !== 1 ? 's' : '' ?></span>
  </div>

  <div class="asx-course-board animate__animated animate__fadeInUp animate__fast asx-animate-delay-1" id="asxGrid">
    <?php if ( ! empty($groups)): ?>
      <?php foreach ($groups as $gi => $group):
        $course_title = (string) ($group['course_title'] ?? 'Unassigned course');
        $course_id    = (int) ($group['course_id'] ?? 0);
        $group_items  = $group['assessments'] ?? [];
        $group_count  = count($group_items);
        $pre_n = $post_n = $chk_n = $done_n = 0;
        foreach ($group_items as $_a) {
            $t = $_a->type ?? '';
            if ($t === 'pre') $pre_n++;
            elseif ($t === 'post') $post_n++;
            elseif ($t === 'checkpoint') $chk_n++;
            if ( ! empty($_a->already_answered)) $done_n++;
        }
        $panel_id = 'asxCoursePanel' . $gi;
        $search_course = strtolower($course_title);
      ?>
      <article class="asx-course-card"
               data-course-id="<?= $course_id ?>"
               data-course-title="<?= htmlspecialchars($search_course, ENT_QUOTES) ?>">
        <button type="button"
                class="asx-course-card-btn"
                data-asx-toggle="<?= $panel_id ?>"
                aria-expanded="false"
                aria-controls="<?= $panel_id ?>">
          <div class="asx-course-card-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
          </div>
          <div class="asx-course-card-body">
            <h2 class="asx-course-card-title"><?= htmlspecialchars($course_title) ?></h2>
            <p class="asx-course-card-meta">
              <span><?= $group_count ?> assessment<?= $group_count !== 1 ? 's' : '' ?></span>
              <?php if ($pre_n): ?><span>· <?= $pre_n ?> pre</span><?php endif; ?>
              <?php if ($post_n): ?><span>· <?= $post_n ?> post</span><?php endif; ?>
              <?php if ($is_manager && $chk_n): ?><span>· <?= $chk_n ?> checkpoint<?= $chk_n !== 1 ? 's' : '' ?></span><?php endif; ?>
              <?php if ( ! $is_manager && $group_count > 0): ?>
                <span class="asx-course-progress">· <?= $done_n ?>/<?= $group_count ?> completed</span>
              <?php endif; ?>
            </p>
          </div>
          <span class="asx-course-card-chevron" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25"><polyline points="6 9 12 15 18 9"/></svg>
          </span>
        </button>

        <div class="asx-course-panel" id="<?= $panel_id ?>" hidden>
          <div class="asx-assessment-table" role="list">
            <?php foreach ($group_items as $i => $a):
              $type_key   = (string) ($a->type ?? '');
              $type_label = $type_labels[$type_key] ?? ucfirst($type_key);
              $q_count    = (int) ($a->question_count ?? 0);
              $search_blob = strtolower(trim(
                  ($a->title ?? '') . ' ' . $course_title . ' ' . ($a->module_title ?? '') . ' ' . $type_label
              ));
              $answered = ! empty($a->already_answered);
            ?>
            <div class="asx-assessment-row asx-card"
                 role="listitem"
                 data-title="<?= htmlspecialchars($search_blob, ENT_QUOTES) ?>"
                 data-type="<?= htmlspecialchars($type_key, ENT_QUOTES) ?>"
                 data-module-id="<?= (int) ($a->module_id ?? 0) ?>"
                 data-course-id="<?= $course_id ?>">
              <div class="asx-assessment-main">
                <span class="asx-type-pill asx-type-pill--<?= htmlspecialchars(preg_replace('/[^a-z]/', '', $type_key) ?: 'other', ENT_QUOTES) ?>">
                  <?= htmlspecialchars($type_label) ?>
                </span>
                <div class="asx-assessment-copy">
                  <div class="asx-assessment-title"><?= htmlspecialchars($a->title ?? 'Untitled Assessment') ?></div>
                  <div class="asx-assessment-sub">
                    <span><?= htmlspecialchars($a->module_title ?? '—') ?></span>
                    <span aria-hidden="true">·</span>
                    <span><?= $q_count ?> question<?= $q_count !== 1 ? 's' : '' ?></span>
                    <?php if ( ! empty($a->created_at_display)): ?>
                      <span aria-hidden="true">·</span>
                      <span><?= htmlspecialchars($a->created_at_display) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="asx-assessment-side">
                <?php if ( ! $is_manager && ! empty($a->score_chip_class) && ! empty($a->score_chip_text)): ?>
                  <span class="asx-score-chip <?= htmlspecialchars($a->score_chip_class) ?>">
                    <?= htmlspecialchars($a->score_chip_text) ?>
                  </span>
                <?php elseif ( ! $is_manager && ! $answered): ?>
                  <span class="asx-status-muted">Not started</span>
                <?php endif; ?>

                <?php if ($is_manager): ?>
                  <div class="asx-row-actions">
                    <a href="<?= base_url('index.php/assessments/edit/'.$a->id) ?>" class="asx-link-btn">Edit</a>
                    <a href="<?= base_url('index.php/assessments/review/'.$a->id) ?>" class="asx-link-btn asx-link-btn--primary">Review</a>
                  </div>
                <?php else: ?>
                  <?php if ($answered): ?>
                    <a href="<?= base_url('index.php/assessments/result/'.$a->id) ?>" class="asx-link-btn asx-link-btn--primary">View result</a>
                  <?php else: ?>
                    <a href="<?= base_url('index.php/assessments/take/'.$a->id) ?>" class="asx-link-btn asx-link-btn--primary">Take assessment</a>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    <?php else: ?>
      <?php $this->load->view('components/empty_state', [
          'emoji'       => '📋',
          'title'       => 'No assessments yet',
          'description' => $is_manager
              ? 'Create your first assessment to attach pre-, post-, or video checkpoints to course modules.'
              : 'No assessments have been assigned to you yet.',
          'cta_href'    => $is_manager ? base_url('index.php/assessments/create') : '',
          'cta_label'   => $is_manager ? 'Create assessment' : '',
          'modifier'    => '',
      ]); ?>
    <?php endif; ?>
  </div>
</div>

<script src="<?= base_url('assets/js/assessments.js') ?>"></script>
