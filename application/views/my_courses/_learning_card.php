<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/**
 * Single course card for My Learning panes.
 *
 * @var string   $mode             enrolled|invited|available
 * @var object   $course
 * @var int      $i                loop index
 * @var string[] $thumb_gradients
 */
$mode             = $mode ?? 'available';
$course           = $course ?? null;
$i                = (int) ($i ?? 0);
$thumb_gradients  = is_array($thumb_gradients ?? null) ? $thumb_gradients : [];
if ( ! $course) {
    return;
}

$grad     = $thumb_gradients[$i % max(1, count($thumb_gradients))] ?? 'linear-gradient(135deg,#6dabcf,#1a3a5c)';
$cat_name = $course->category_name ?? 'General';
$modules  = (int) ($course->module_count ?? $course->total_modules ?? 0);
$cid      = (int) ($course->course_id ?? $course->id ?? 0);

$card_class = $mode === 'enrolled' ? 'ec-course-card' : 'ec-available-card';
$data_attrs = 'data-title="' . htmlspecialchars(strtolower((string) $course->title), ENT_QUOTES, 'UTF-8') . '"'
    . ' data-cat="' . htmlspecialchars((string) ($course->category_id ?? ''), ENT_QUOTES, 'UTF-8') . '"';

if ($mode === 'enrolled') {
    $pct = (int) ($course->course_progress_percent ?? $course->progress_pct ?? 0);
    $done = (int) ($course->modules_done ?? 0);
    if ($pct >= 100) {
        $status = 'completed';
        $badge_class = 'ec-badge-completed';
        $badge_text = 'Completed';
    } elseif ($pct > 0) {
        $status = 'inprogress';
        $badge_class = 'ec-badge-inprogress';
        $badge_text = 'In Progress';
    } else {
        $status = 'notstarted';
        $badge_class = 'ec-badge-notstarted';
        $badge_text = 'Not Started';
    }
    $cta_text  = $pct >= 100 ? 'Review' : ($pct > 0 ? 'Continue' : 'Start');
    $cta_class = $pct >= 100 ? 'ec-cta-review' : ($pct > 0 ? 'ec-cta-continue' : 'ec-cta-start');
    $data_attrs .= ' data-progress="' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '"';
}
?>
<article class="<?= $card_class ?> animate__animated animate__fadeInUp ec-stagger-<?= $i % 8 ?>"
         <?= $data_attrs ?>>
  <div class="ec-card-thumb" style="background:<?= $grad ?>;">
    <div class="ec-card-thumb-icon">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
    </div>
    <?php if ($mode === 'enrolled'): ?>
    <span class="ec-card-status-badge <?= $badge_class ?>"><?= htmlspecialchars($badge_text) ?></span>
    <?php elseif ($mode === 'invited'): ?>
    <span class="ec-card-status-badge ec-badge-inprogress">Invited</span>
    <?php endif; ?>
  </div>
  <div class="ec-card-body">
    <p class="ec-card-category"><?= htmlspecialchars($cat_name) ?></p>
    <div class="ec-card-title"><?= htmlspecialchars($course->title) ?></div>
    <div class="ec-card-meta">
      <div class="ec-card-meta-item">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        <?php if ($mode === 'enrolled'): ?>
        <?= $done ?>/<?= $modules ?> modules
        <?php else: ?>
        <?= $modules ?> modules
        <?php endif; ?>
      </div>
      <?php if ($mode === 'enrolled' && ! empty($course->enrolled_at)): ?>
      <div class="ec-card-meta-item">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Enrolled <?= date('M j', strtotime($course->enrolled_at)) ?>
      </div>
      <?php endif; ?>
    </div>
    <?php if ($mode === 'enrolled'): ?>
    <div class="ec-card-progress">
      <?php $this->load->view('components/progress_bar', [
          'progress_percent' => $pct,
          'size'             => 'sm',
          'label'            => 'Progress',
          'course_id'        => $cid,
          'variant'          => 'embed',
          'sync_live'        => true,
      ]); ?>
    </div>
    <?php elseif ($mode === 'available' && ! empty($course->description)): ?>
    <p class="ec-available-desc"><?= htmlspecialchars($course->description) ?></p>
    <?php endif; ?>
    <div class="ec-card-footer">
      <?php if ($mode === 'enrolled'): ?>
        <?php if ($cid > 0): ?>
        <a href="<?= base_url('courses/view/' . $cid . '?' . ka_lms_return_q('my_courses')) ?>" class="ec-card-cta <?= $cta_class ?>"><?= htmlspecialchars($cta_text) ?></a>
        <?php else: ?>
        <span class="ec-card-cta <?= $cta_class ?> ec-card-cta--disabled" title="Course unavailable"><?= htmlspecialchars($cta_text) ?></span>
        <?php endif; ?>
        <?php if ($pct >= 100): ?>
        <a href="<?= base_url('certificates') ?>" class="ec-cert-link" title="View Certificate">🏆 Cert</a>
        <?php endif; ?>
      <?php elseif ($mode === 'invited'): ?>
        <?php $iid = (int) ($course->invitation_id ?? 0); ?>
        <?php if ($iid > 0): ?>
        <a href="<?= base_url('index.php/courses/accept_invitation/' . $iid) ?>"
           class="ec-card-cta ec-cta-start"
           onclick="return confirm('Accept this course invitation?')">Accept invite</a>
        <?php endif; ?>
        <?php if ($cid > 0): ?>
        <a href="<?= base_url('courses/view/' . $cid . '?' . ka_lms_return_q('my_courses')) ?>" class="ec-card-details-link">Details</a>
        <?php endif; ?>
      <?php else: ?>
        <?php if ($cid > 0): ?>
        <a href="<?= base_url('index.php/courses/enroll/' . $cid) ?>" class="ec-card-cta ec-cta-start">Enroll Now</a>
        <a href="<?= base_url('courses/view/' . $cid . '?' . ka_lms_return_q('my_courses')) ?>" class="ec-card-details-link">Details</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</article>
