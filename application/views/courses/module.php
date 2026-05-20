<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$module           = $module           ?? null;
$course           = $course           ?? null;
$all_modules      = $all_modules      ?? [];
$prev_module      = $prev_module      ?? null;
$next_module      = $next_module      ?? null;
$my_progress      = $my_progress      ?? null;
$pre_blocked      = $pre_blocked      ?? false;
$pre_assessment   = $pre_assessment   ?? null;
$post_assessments = $post_assessments ?? [];
$user_role        = strtolower($user->role ?? 'employee');
$is_completed     = ($my_progress && $my_progress->status === 'completed');
$lms_return_q     = isset($lms_return_q) ? (string) $lms_return_q : '';
$lms_course_qs    = ($lms_return_q !== '') ? ('?' . $lms_return_q) : '';

$youtube_video_id                = $youtube_video_id ?? null;
$video_checkpoint_payload        = $video_checkpoint_payload ?? [];
$video_checkpoint_passed_ids     = $video_checkpoint_passed_ids ?? [];
$video_checkpoint_required_cnt   = (int) ($video_checkpoint_required_cnt ?? 0);
$video_checkpoint_gate           = ! empty($video_checkpoint_gate);
$video_checkpoint_submit_url     = $video_checkpoint_submit_url ?? '';
$video_checkpoint_json_url       = $video_checkpoint_json_url ?? '';
$assessment_pass_threshold       = isset($assessment_pass_threshold) ? (float) $assessment_pass_threshold : (float) ka_assessment_pass_threshold();
$module_completion_state         = $module_completion_state ?? [
    'pre_assessment_passed'    => true,
    'video_completed'          => true,
    'post_assessment_passed'   => true,
    'can_mark_complete'        => true,
    'flow'                     => null,
];
$mcs_post_passed                 = ! empty($module_completion_state['post_assessment_passed']);
$can_start_post_assessment       = ! empty($can_start_post_assessment);

$is_video_module        = (($module->content_type ?? '') === 'video');
$pre_assessment_required = $pre_assessment && ( ! $is_video_module || ! empty($pre_assessment->is_required));
$video_optional_pre     = ($is_video_module && $pre_assessment && ! $pre_assessment_required);
$content_gated          = ($pre_blocked && $pre_assessment && ! $video_optional_pre);
$pre_assessment_passed = ! empty($module_completion_state['pre_assessment_passed']);
$video_completed_init   = ! empty($module_completion_state['video_completed']);
$first_post_take_url    = '';
if ( ! empty($post_assessments) && is_array($post_assessments)) {
    foreach ($post_assessments as $_pa) {
        if ( ! empty($_pa->id)) {
            $first_post_take_url = base_url('index.php/assessments/take/' . (int) $_pa->id);
            break;
        }
    }
}

$pre_assessment_id = (is_object($pre_assessment) && isset($pre_assessment->id))
    ? (int) $pre_assessment->id
    : 0;

if ( ! $module) return;
$MODULE_STATE_URL                = base_url('index.php/courses/module_state/' . (int) $module->id);

/** Pre-assessment modal payload (video modules only; set by Assessments::submit). */
$pre_assessment_modal = null;
if (function_exists('get_instance')) {
    /** @var CI_Controller&object{session: CI_Session} $CI */
    $CI = & get_instance();
    if (isset($CI->session)) {
        $_pm = $CI->session->flashdata('pre_assessment_modal');
        if (is_array($_pm)
            && (int) ($_pm['module_id'] ?? 0) === (int) $module->id
            && ($module->content_type ?? '') === 'video') {
            $pre_assessment_modal = $_pm;
        }
    }
}
$module_pre_modal = null;
if ($pre_assessment_modal !== null) {
    $_aid             = (int) ($pre_assessment_modal['assessment_id'] ?? 0);
    $module_pre_modal = [
        'assessment_id' => $_aid,
        'title'         => (string) ($pre_assessment_modal['title'] ?? 'Pre-assessment'),
        'score'         => (float) ($pre_assessment_modal['score'] ?? 0),
        'passed'        => ! empty($pre_assessment_modal['passed']),
        'pending_count' => (int) ($pre_assessment_modal['pending_count'] ?? 0),
        'threshold'     => (float) ($pre_assessment_modal['threshold'] ?? ka_assessment_pass_threshold()),
        'detail_url'    => ($_aid > 0) ? base_url('index.php/assessments/result/' . $_aid) : '',
    ];
}

$MARK_COMPLETE_URL = base_url('index.php/courses/complete_module/' . $module->id);
$CSRF_NAME  = $csrf_field_name ?? '';
$CSRF_HASH  = $csrf_hash ?? '';

// Build content URL
$content_path = $module->content_path ?? '';
$is_external  = (strpos($content_path, 'http://') === 0 || strpos($content_path, 'https://') === 0);
$content_url  = $is_external ? $content_path : base_url($content_path);

$module_app_context = [
    'modulePage'                 => true,
    'isCompleted'                => (bool) $is_completed,
    'contentType'                => (string) ($module->content_type ?? ''),
    'markUrl'                    => $MARK_COMPLETE_URL,
    'csrfName'                   => $CSRF_NAME,
    'csrfHash'                   => $CSRF_HASH,
    'hasNext'                    => (bool) $next_module,
    'nextUrl'                    => $next_module ? base_url('index.php/courses/module/'.$next_module->id) : '',
    'moduleStateUrl'             => (string) $MODULE_STATE_URL,
    'preBlocked'                 => (bool) ($pre_blocked && $pre_assessment),
    'preResultModal'             => $module_pre_modal,
    'postAssessmentPassed'       => (bool) $mcs_post_passed,
    'canStartPostAssessment'     => (bool) $can_start_post_assessment,
    'moduleProgressPercent'      => 0,
    'isYoutubeIframe'            => (bool) $youtube_video_id,
    'moduleId'                   => (int) ($module->id ?? 0),
    'courseId'                   => (int) ($module->course_id ?? 0),
    'videoCheckpoints'           => $video_checkpoint_payload,
    'videoCheckpointGate'        => (bool) $video_checkpoint_gate,
    'videoCheckpointSubmitUrl'   => (string) $video_checkpoint_submit_url,
    'youtubeVideoId'             => (string) ($youtube_video_id ?? ''),
    'slidesFileExt'              => strtolower((string) pathinfo($content_path, PATHINFO_EXTENSION)),
    'vcPassedIds'                => array_values(array_map('intval', (array) $video_checkpoint_passed_ids)),
    'videoOptionalPre'           => (bool) $video_optional_pre,
    'videoCompletedInitial'      => (bool) $video_completed_init,
    'hasPostAssessments'         => (bool) ! empty($post_assessments),
    'firstPostAssessmentHref'    => (string) $first_post_take_url,
];
$_ctx_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES;
?>
<?php echo $alerts_partial_html ?? ''; ?>
<link rel="stylesheet" href="<?= base_url('assets/css/module.css') ?>">

<!-- Video checkpoint modal (non-dismissible until correct) -->
<div class="mv-yt-modal-overlay" id="mvVcCheckpointOverlay" aria-hidden="true">
  <div class="mv-yt-modal" role="dialog" aria-modal="true" aria-labelledby="mvVcCheckpointTitle" onclick="event.stopPropagation();">
    <div class="mv-yt-modal-hdr" id="mvVcCheckpointTitle">Video Checkpoint</div>
    <div class="mv-yt-modal-body">
      <p class="mv-yt-modal-q" id="mvVcCheckpointQuestion"></p>
      <div id="mvVcCheckpointChoices"></div>
      <button type="button" class="mv-yt-submit" id="mvVcCheckpointSubmit" disabled>Submit answer</button>
      <div class="mv-yt-err" id="mvVcCheckpointErr"></div>
    </div>
  </div>
</div>

<!-- Pre-assessment immediate feedback (video modules); full report still at Assessments → result -->
<div class="mv-pre-result-overlay" id="mvPreAssessmentOverlay" aria-hidden="true" aria-modal="true" role="dialog" aria-labelledby="mvPreAssessmentTitle">
  <div class="mv-pre-result-card">
    <div class="mv-pre-result-hdr" id="mvPreAssessmentTitle">Pre-assessment results</div>
    <div class="mv-pre-result-body">
      <div id="mvPreAssessmentBadge" class="mv-pre-result-badge"></div>
      <div class="mv-pre-result-score" id="mvPreAssessmentScore"></div>
      <p class="mv-pre-result-note" id="mvPreAssessmentNote"></p>
      <div class="mv-pre-result-actions">
        <button type="button" class="mv-pre-btn-primary" id="mvPreAssessmentContinue">Continue to video</button>
        <a class="mv-pre-link-muted" id="mvPreAssessmentDetail" href="#">View detailed results</a>
      </div>
    </div>
  </div>
</div>

<!-- Video finished — prompt for post-assessment (UX only; does not navigate until user chooses) -->
<div class="mv-pre-result-overlay" id="mvVideoCompleteOverlay" aria-hidden="true" aria-modal="true" role="dialog" aria-labelledby="mvVideoCompleteTitle">
  <div class="mv-pre-result-card mv-video-complete-card" onclick="event.stopPropagation();">
    <div class="mv-pre-result-hdr" id="mvVideoCompleteTitle">🎉 Video Completed!</div>
    <div class="mv-pre-result-body">
      <p class="mv-video-complete-msg">You can now take the post-assessment.</p>
      <div class="mv-video-complete-actions">
        <a class="mv-pre-btn-primary" id="mvVideoCompleteTakePost" href="#">Take Post-Assessment</a>
        <button type="button" class="mv-video-complete-secondary" id="mvVideoCompleteLater">Maybe Later</button>
      </div>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="mv-toast" id="mvToast">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
  <span id="mvToastMsg">Module completed!</span>
</div>

<!-- Breadcrumb back link -->
<div class="mv-breadcrumb-row animate__animated animate__fadeIn animate__fast">
  <a href="<?= base_url('index.php/courses/view/'.$module->course_id . $lms_course_qs) ?>" class="mv-breadcrumb-back">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    <?= htmlspecialchars($module->course_title) ?>
  </a>
</div>

<div class="mv-layout animate__animated animate__fadeInUp animate__fast">

  <!-- ══ Main Content ════════════════════════════════════════ -->
  <div>
    <div class="mv-content-panel" id="mvContentPanel">

      <!-- Header -->
      <div class="mv-content-header">
        <?php
        $type_labels = [
            'pdf'            => 'PDF',
            'video'          => 'Video',
            'slides'         => 'Slides',
            'audio'          => 'Audio',
            'zoom_recording' => 'Zoom Recording',
        ];
        $type_label       = $type_labels[$module->content_type ?? ''] ?? 'Content';
        $ctype_slug_raw   = strtolower((string) ($module->content_type ?? ''));
        $ctype_slug_safe  = preg_replace('/[^a-z0-9_]/', '_', $ctype_slug_raw);
        if ($ctype_slug_safe === '') {
            $ctype_slug_safe = 'default';
        }
        ?>
        <span class="mv-content-type-badge mv-content-type-badge--<?= htmlspecialchars($ctype_slug_safe, ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars($type_label) ?>
        </span>
        <div>
          <div class="mv-title"><?= htmlspecialchars($module->title) ?></div>
          <?php if ( ! empty($module->description)): ?>
          <div class="mv-desc"><?= htmlspecialchars($module->description) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($content_gated): ?>
      <!-- ── PRE-ASSESSMENT GATE (non-video modules: blocks content until pre attempted) ── -->
      <div class="mv-gate">
        <div class="mv-gate-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <div class="mv-gate-title">Pre-Assessment Required</div>
        <div class="mv-gate-sub">
          You must complete the pre-assessment for this module before viewing its content.<br>
          The pre-assessment helps us understand your baseline knowledge.
        </div>
        <a href="<?= $pre_assessment_id > 0 ? base_url('index.php/assessments/take/' . $pre_assessment_id) : '#' ?>"
           class="mv-gate-btn">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          Start Pre-Assessment
        </a>
      </div>

      <?php else: ?>

      <?php if ($video_optional_pre): ?>
      <!-- ── Optional pre-assessment (video only — never blocks the player) ── -->
      <div class="mv-optional-pre-card animate__animated animate__fadeIn animate__fast">
        <div class="mv-optional-pre-kicker">Optional</div>
        <div class="mv-optional-pre-title">Optional Pre-Assessment</div>
        <p class="mv-optional-pre-sub">Test your knowledge before starting — your choice; it does not block the lesson.</p>
        <?php if ($pre_assessment_passed): ?>
          <div class="mv-optional-pre-done">
            <span class="mv-optional-pre-done-badge">✓ Pre-assessment completed</span>
            <p class="mv-optional-pre-done-note">You can continue the video anytime. Open your detailed results if you need them.</p>
            <a href="<?= $pre_assessment_id > 0 ? base_url('index.php/assessments/result/' . $pre_assessment_id) : '#' ?>"
               class="mv-optional-pre-link">View pre-assessment results</a>
          </div>
        <?php else: ?>
          <a href="<?= $pre_assessment_id > 0 ? base_url('index.php/assessments/take/' . $pre_assessment_id) : '#' ?>"
             class="mv-optional-pre-cta">
            Take Pre-Assessment
          </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>


      <?php if ($module->content_type === 'pdf'): ?>
      <!-- ── PDF VIEWER ── -->
      <iframe class="mv-pdf-frame"
              id="mvPdfFrame"
              src="<?= htmlspecialchars($content_url) ?>"
              title="<?= htmlspecialchars($module->title) ?>">
      </iframe>

      <?php elseif ($module->content_type === 'video'): ?>
      <!-- ── VIDEO: YouTube (IFrame API + checkpoints) OR HTML5 ── -->
      <?php if ($youtube_video_id): ?>
      <div class="mv-video-wrap">
        <div class="mv-yt-ytframe" id="mvYouTubePlayer"></div>
      </div>
      <?php else: ?>
      <div class="mv-video-wrap">
        <video class="mv-video" id="mvVideo" controls controlsList="nodownload" preload="metadata"
               oncontextmenu="return false;">
          <source src="<?= htmlspecialchars($content_url) ?>" type="video/mp4">
          Your browser does not support the video tag.
        </video>
      </div>
      <?php endif; ?>

      <?php elseif ($module->content_type === 'slides'): ?>
      <!-- ── SLIDES VIEWER ── -->
      <?php
        $ext = strtolower(pathinfo($content_path, PATHINFO_EXTENSION));
      ?>
      <?php if ($ext === 'pdf'): ?>
        <iframe class="mv-slides-frame"
                id="mvSlidesFrame"
                src="<?= htmlspecialchars($content_url) ?>"
                title="<?= htmlspecialchars($module->title) ?>">
        </iframe>
      <?php else: ?>
        <!-- PPTX: can't embed directly, show download + manual complete -->
        <div class="mv-slides-fallback">
          <div class="mv-slides-fallback-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
          </div>
          <div class="mv-slides-fallback-title">
            <?= htmlspecialchars($module->title) ?>
          </div>
          <div class="mv-slides-fallback-sub">
            PowerPoint Presentation — download to view in Microsoft Office or Google Slides.
          </div>
          <a href="<?= htmlspecialchars($content_url) ?>" download
             class="mv-zoom-btn" id="mvSlidesDownload">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download Presentation
          </a>
        </div>
      <?php endif; ?>

      <?php elseif ($module->content_type === 'audio'): ?>
      <!-- ── AUDIO PLAYER ── -->
      <div class="mv-audio-wrap">
        <div class="mv-audio-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
        </div>
        <div class="mv-audio-title"><?= htmlspecialchars($module->title) ?></div>
        <audio class="mv-audio-player" id="mvAudio" controls
               preload="metadata" controlsList="nodownload"
               oncontextmenu="return false;">
          <source src="<?= htmlspecialchars($content_url) ?>" type="audio/mpeg">
          <source src="<?= htmlspecialchars($content_url) ?>" type="audio/wav">
          Your browser does not support the audio tag.
        </audio>
      </div>

      <?php elseif ($module->content_type === 'zoom_recording'): ?>
      <!-- ── ZOOM RECORDING (external link) ── -->
      <div class="mv-zoom-wrap">
        <div class="mv-zoom-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
        </div>
        <div class="mv-zoom-title">Zoom Recording</div>
        <div class="mv-zoom-sub">
          This module contains a Zoom recording. Click the button below to open it.<br>
          After watching, come back and mark the module as complete.
        </div>
        <a href="<?= htmlspecialchars($content_url) ?>" target="_blank"
           class="mv-zoom-btn" id="mvZoomLink">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
          Open Zoom Recording
        </a>
      </div>
      <?php endif; ?>

      <!-- ── Complete Bar (shown whenever module content is visible) ── -->
      <div class="mv-complete-bar">
        <div class="mv-complete-status <?= $is_completed ? 'done' : 'pending' ?>" id="mvCompleteStatus">
          <?php if ($is_completed): ?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
            Module completed
          <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span id="mvCompleteHint">
              <?php if ( ! $mcs_post_passed && ! empty($post_assessments)): ?>
                Pass all post-assessments (sidebar), then finish content requirements to mark complete
              <?php elseif ($module->content_type === 'pdf' || $module->content_type === 'slides'): ?>
                Scroll to the end to complete
              <?php elseif ($module->content_type === 'video' && $youtube_video_id && $video_checkpoint_gate): ?>
                Complete required video checkpoints; pass post-assessment (sidebar) to mark complete
              <?php elseif ($module->content_type === 'video' || $module->content_type === 'audio'): ?>
                Watch to the end to complete
              <?php else: ?>
                Open the recording then mark complete
              <?php endif; ?>
            </span>
          <?php endif; ?>
        </div>
        <?php if ( ! $is_completed): ?>
        <button type="button" class="mv-mark-btn" id="mvMarkBtn" disabled>
          Mark as Complete
        </button>
        <?php else: ?>
        <button type="button" class="mv-mark-btn mv-mark-btn-done" disabled>
          ✓ Completed
        </button>
        <?php endif; ?>
      </div>

      <?php endif; ?>

    </div><!-- /mv-content-panel -->

    <!-- ── Navigation ── -->
    <div class="mv-nav mv-nav-spaced">
      <?php if ($prev_module): ?>
      <a href="<?= base_url('index.php/courses/module/'.$prev_module->id . $lms_course_qs) ?>" class="mv-nav-btn">
        ← <?= htmlspecialchars(mb_strimwidth($prev_module->title, 0, 28, '…')) ?>
      </a>
      <?php else: ?>
      <a href="<?= base_url('index.php/courses/view/'.$module->course_id . $lms_course_qs) ?>" class="mv-nav-btn">
        ← Back to Course
      </a>
      <?php endif; ?>

      <?php if ($next_module): ?>
      <a href="<?= base_url('index.php/courses/module/'.$next_module->id . $lms_course_qs) ?>"
         class="mv-nav-btn primary <?= ! $is_completed ? 'mv-next-locked' : 'mv-next-unlocked' ?>" id="mvNextBtn">
        <?= htmlspecialchars(mb_strimwidth($next_module->title, 0, 28, '…')) ?> →
      </a>
      <?php else: ?>
      <a href="<?= base_url('index.php/courses/view/'.$module->course_id . $lms_course_qs) ?>" class="mv-nav-btn primary">
        Back to Course →
      </a>
      <?php endif; ?>
    </div>

  </div>

  <!-- ══ Sidebar ═══════════════════════════════════════════ -->
  <div>

    <?php $this->load->view('components/progress_bar', [
        'progress_percent' => 0,
        'size'               => 'md',
        'label'              => 'Module Progress',
        'variant'            => 'module_sidebar',
    ]); ?>

    <!-- Post-assessment (required to pass before marking module complete when configured) -->
    <?php if ( ! empty($post_assessments)): ?>
    <div class="mv-post-asx animate__animated animate__fadeInDown animate__fast" id="mvPostAssessmentCard">
      <h4>Post-Assessment</h4>
      <p id="mvPostAssessmentSummary">
        <?php if ($is_completed): ?>
        Review your post-assessment results for this module.
        <?php elseif ( ! $can_start_post_assessment && ! $mcs_post_passed): ?>
        <strong>Locked:</strong> Locked until you complete the video checkpoints.
        <?php elseif ( ! $mcs_post_passed): ?>
        <strong>Required:</strong> pass all post-assessments below before you can mark this module complete (you can retake until you pass).
        <?php else: ?>
        All post-assessments passed. Finish any remaining content steps, then use <strong>Mark as Complete</strong>.
        <?php endif; ?>
      </p>
      <?php foreach ($post_assessments as $pa): ?>
        <?php
        $has_done = $pa->has_done ?? false;
        $result   = $pa->result   ?? ['score' => 0, 'pending' => 0, 'scored' => 0, 'total' => 0];
        $passed   = $pa->passed   ?? false;
        ?>
        <?php if ( ! $can_start_post_assessment && ! $passed): ?>
          <a href="javascript:void(0)"
             class="mv-post-asx-btn is-disabled"
             aria-disabled="true"
             title="Locked until you complete the video checkpoints">Locked until you complete the video checkpoints</a>
        <?php elseif ($passed): ?>
          <div class="mv-post-result-pass">
            ✓ Passed (<?= number_format($result['score'], 1) ?>%)
          </div>
        <?php elseif ($has_done && $result && $result['pending'] > 0): ?>
          <div class="mv-post-result-pending">
            ⏳ Awaiting grading
          </div>
        <?php elseif ($has_done): ?>
          <div class="mv-post-result-fail">
            Score: <?= number_format($result['score'], 1) ?>% — need <?= number_format($assessment_pass_threshold, 0) ?>% to pass
          </div>
          <a href="<?= base_url('index.php/assessments/take/'.$pa->id) ?>"
             class="mv-post-asx-btn js-post-asx-action"
             data-href="<?= base_url('index.php/assessments/take/'.$pa->id) ?>">Retake Assessment</a>
        <?php else: ?>
          <a href="<?= base_url('index.php/assessments/take/'.$pa->id) ?>"
             class="mv-post-asx-btn js-post-asx-action"
             data-href="<?= base_url('index.php/assessments/take/'.$pa->id) ?>">Take Post-Assessment</a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Module list -->
    <div class="mv-sidebar-panel animate__animated animate__fadeInRight animate__fast">
      <div class="mv-sidebar-hdr">
        Course Modules
        <span class="mv-sidebar-course-title">
          — <?= htmlspecialchars(mb_strimwidth($module->course_title ?? '', 0, 22, '…')) ?>
        </span>
      </div>
      <?php foreach ($all_modules as $idx => $m):
        $is_cur = ((int)$m->id === (int)$module->id);
        $m_status = $m->status ?? 'not_started';
        $m_done = ($m_status === 'completed');
        $num_class = $m_done ? 'completed' : ($is_cur ? 'current' : 'pending');
      ?>
      <a href="<?= base_url('index.php/courses/module/'.$m->id . $lms_course_qs) ?>"
         class="mv-mod-item <?= $is_cur ? 'current' : '' ?>"
         <?= $is_cur ? 'aria-current="page"' : '' ?>>
        <div class="mv-mod-num <?= $num_class ?>">
          <?= $m_done ? '✓' : ($idx + 1) ?>
        </div>
        <div>
          <div class="mv-mod-title"><?= htmlspecialchars($m->title) ?></div>
          <div class="mv-mod-status">
            <?= ucfirst(str_replace('_', ' ', $m_status ?? 'not started')) ?>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Back to course -->
    <a href="<?= base_url('index.php/courses/view/'.$module->course_id . $lms_course_qs) ?>" class="mv-back-course-outline">
      ← Back to Course Overview
    </a>

  </div>
</div>

<script>
kaApplyAppContext(<?= json_encode([
  'catalog' => [],
  'dashboard' => [],
  'module' => $module_app_context,
  'assessments' => [],
], $_ctx_flags) ?>);
</script>
<script src="<?= base_url('assets/js/module.js') ?>"></script>
<?php if ( ! empty($youtube_video_id)): ?>
<script src="https://www.youtube.com/iframe_api"></script>
<?php endif; ?>
