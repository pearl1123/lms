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

$youtube_video_id                = $youtube_video_id ?? null;
$video_checkpoint_payload        = $video_checkpoint_payload ?? [];
$video_checkpoint_passed_ids     = $video_checkpoint_passed_ids ?? [];
$video_checkpoint_required_cnt   = (int) ($video_checkpoint_required_cnt ?? 0);
$video_checkpoint_gate           = ! empty($video_checkpoint_gate);
$video_checkpoint_submit_url     = $video_checkpoint_submit_url ?? '';
$video_checkpoint_json_url       = $video_checkpoint_json_url ?? '';
$assessment_pass_threshold       = isset($assessment_pass_threshold) ? (float) $assessment_pass_threshold : (float) ka_assessment_pass_threshold();

if ( ! $module) return;

$MARK_COMPLETE_URL = base_url('index.php/courses/complete_module/' . $module->id);
$CSRF_NAME  = $csrf_field_name ?? '';
$CSRF_HASH  = $csrf_hash ?? '';

// Build content URL
$content_path = $module->content_path ?? '';
$is_external  = (strpos($content_path, 'http://') === 0 || strpos($content_path, 'https://') === 0);
$content_url  = $is_external ? $content_path : base_url($content_path);
?>
<?php echo $alerts_partial_html ?? ''; ?>
<style>
/* ── Layout ── */
.mv-layout { display:grid;grid-template-columns:1fr 280px;gap:1.25rem;align-items:start; }
@media(max-width:991.98px){ .mv-layout{grid-template-columns:1fr;} }

/* ── Content panel ── */
.mv-content-panel {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;overflow:hidden;
}
.mv-content-header {
  padding:1rem 1.25rem;border-bottom:1px solid var(--ka-border,#e2e8f0);
  display:flex;align-items:center;gap:.875rem;
}
.mv-content-type-badge {
  padding:3px 10px;border-radius:20px;font-size:.625rem;font-weight:700;
  text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;
}
.mv-title { font-size:1rem;font-weight:700;color:var(--ka-text,#1e293b);margin:0; }
.mv-desc  { font-size:.8125rem;color:var(--ka-text-muted,#64748b);margin:.375rem 0 0;line-height:1.5; }

/* ── Viewers ── */
.mv-pdf-frame,
.mv-slides-frame {
  width:100%;height:70vh;min-height:480px;border:none;display:block;
  background:var(--ka-bg,#f8fafc);
}
.mv-video-wrap { background:#000;position:relative; }
.mv-video { width:100%;max-height:72vh;display:block; }
.mv-audio-wrap {
  padding:2.5rem 1.5rem;text-align:center;
  background:linear-gradient(135deg,var(--ka-navy,#1a3a5c) 0%,#254d75 100%);
}
.mv-audio-icon {
  width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.1);
  display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;
}
.mv-audio-icon svg { width:32px;height:32px;color:#fff; }
.mv-audio-title { font-size:1.125rem;font-weight:700;color:#fff;margin-bottom:1rem; }
.mv-audio-player { width:100%;border-radius:9px;outline:none;margin-bottom:1rem; }
.mv-zoom-wrap { padding:2.5rem;text-align:center; }
.mv-zoom-icon {
  width:80px;height:80px;border-radius:50%;background:var(--ka-accent,#e8f4fd);
  display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;
}
.mv-zoom-icon svg { width:36px;height:36px;color:var(--ka-primary,#6dabcf); }
.mv-zoom-title { font-size:1.125rem;font-weight:700;color:var(--ka-text,#1e293b);margin-bottom:.5rem; }
.mv-zoom-sub   { font-size:.875rem;color:var(--ka-text-muted,#64748b);margin-bottom:1.5rem; }
.mv-zoom-btn {
  display:inline-flex;align-items:center;gap:.5rem;
  padding:.875rem 1.75rem;border-radius:10px;
  background:var(--ka-navy,#1a3a5c);color:#fff;
  font-size:.9375rem;font-weight:700;text-decoration:none;transition:all .18s;
}
.mv-zoom-btn:hover { background:#254d75;color:#fff;transform:translateY(-2px); }
.mv-slides-download {
  padding:.875rem 1.25rem;border-top:1px solid var(--ka-border,#e2e8f0);
  display:flex;align-items:center;gap:.75rem;font-size:.8125rem;
}

/* ── Mark complete bar ── */
.mv-complete-bar {
  padding:1rem 1.25rem;border-top:1px solid var(--ka-border,#e2e8f0);
  display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;
}
.mv-complete-status {
  display:flex;align-items:center;gap:8px;
  font-size:.875rem;font-weight:600;
}
.mv-complete-status.done { color:#22c55e; }
.mv-complete-status.pending { color:var(--ka-text-muted,#64748b); }
.mv-complete-status svg { width:18px;height:18px; }
.mv-mark-btn {
  padding:.625rem 1.375rem;border-radius:8px;font-size:.875rem;font-weight:700;
  border:none;cursor:pointer;transition:all .18s;
  background:var(--ka-navy,#1a3a5c);color:#fff;
}
.mv-mark-btn:hover { background:#254d75;transform:translateY(-1px); }
.mv-mark-btn:disabled { background:var(--ka-border,#e2e8f0);color:var(--ka-text-muted,#64748b);cursor:not-allowed;transform:none; }

/* ── Pre-assessment gate ── */
.mv-gate {
  padding:3rem 2rem;text-align:center;
}
.mv-gate-icon {
  width:80px;height:80px;border-radius:50%;background:#fef2f2;
  display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;
}
.mv-gate-icon svg { width:36px;height:36px;color:#dc2626; }
.mv-gate-title { font-size:1.125rem;font-weight:700;color:var(--ka-text,#1e293b);margin-bottom:.5rem; }
.mv-gate-sub   { font-size:.875rem;color:var(--ka-text-muted,#64748b);margin-bottom:1.5rem;line-height:1.6; }
.mv-gate-btn {
  display:inline-flex;align-items:center;gap:.5rem;
  padding:.875rem 1.75rem;border-radius:10px;
  background:#dc2626;color:#fff;
  font-size:.9375rem;font-weight:700;text-decoration:none;transition:all .18s;
}
.mv-gate-btn:hover { background:#b91c1c;color:#fff; }

/* ── Sidebar ── */
.mv-sidebar-panel {
  background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;overflow:hidden;margin-bottom:1rem;
}
.mv-sidebar-hdr {
  padding:.875rem 1rem;border-bottom:1px solid var(--ka-border,#e2e8f0);
  font-size:.8125rem;font-weight:700;color:var(--ka-text,#1e293b);
}
.mv-mod-item {
  display:flex;align-items:center;gap:.625rem;padding:.75rem 1rem;
  border-bottom:1px solid var(--ka-border,#e2e8f0);text-decoration:none;
  transition:background .15s;
}
.mv-mod-item:last-child { border-bottom:none; }
.mv-mod-item:hover { background:var(--ka-accent,#e8f4fd); }
.mv-mod-item.current { background:var(--ka-accent,#e8f4fd); }
.mv-mod-num {
  width:24px;height:24px;border-radius:50%;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:.625rem;font-weight:700;
}
.mv-mod-num.completed  { background:#ecfdf5;color:#22c55e; }
.mv-mod-num.current    { background:var(--ka-navy,#1a3a5c);color:#fff; }
.mv-mod-num.pending    { background:var(--ka-bg,#f8fafc);color:var(--ka-text-muted,#64748b);border:1.5px solid var(--ka-border,#e2e8f0); }
.mv-mod-title { font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);line-height:1.3; }
.mv-mod-status { font-size:.625rem;color:var(--ka-text-muted,#64748b);margin-top:1px; }

/* ── Post-assessment CTA ── */
.mv-post-asx {
  background:linear-gradient(135deg,#fffbeb,#fef3c7);
  border:1px solid #fde68a;border-radius:14px;
  padding:1.25rem;margin-bottom:1rem;
}
.mv-post-asx h4 { font-size:.875rem;font-weight:700;color:#92400e;margin:0 0 .375rem; }
.mv-post-asx p  { font-size:.8125rem;color:#78350f;margin:0 0 .875rem;line-height:1.5; }
.mv-post-asx-btn {
  display:block;padding:.625rem;border-radius:8px;
  background:#d97706;color:#fff;text-align:center;
  font-size:.8125rem;font-weight:700;text-decoration:none;transition:all .15s;
}
.mv-post-asx-btn:hover { background:#b45309;color:#fff; }

/* ── Nav buttons ── */
.mv-nav { display:flex;gap:.625rem;margin-top:1rem; }
.mv-nav-btn {
  flex:1;padding:.625rem;border-radius:8px;text-align:center;
  font-size:.8125rem;font-weight:700;text-decoration:none;transition:all .15s;
  border:1.5px solid var(--ka-border,#e2e8f0);color:var(--ka-text,#1e293b);background:#fff;
}
.mv-nav-btn:hover { border-color:var(--ka-primary,#6dabcf);color:var(--ka-primary,#6dabcf); }
.mv-nav-btn.primary { background:var(--ka-navy,#1a3a5c);border-color:var(--ka-navy,#1a3a5c);color:#fff; }
.mv-nav-btn.primary:hover { background:#254d75;border-color:#254d75;color:#fff; }

/* ── Progress toast ── */
.mv-toast {
  position:fixed;bottom:24px;right:24px;z-index:9999;
  background:var(--ka-navy,#1a3a5c);color:#fff;
  padding:.875rem 1.25rem;border-radius:12px;
  font-size:.875rem;font-weight:600;
  display:flex;align-items:center;gap:.625rem;
  box-shadow:0 8px 32px rgba(0,0,0,.2);
  transform:translateY(80px);opacity:0;
  transition:all .4s cubic-bezier(.34,1.56,.64,1);
  pointer-events:none;
}
.mv-toast.show { transform:translateY(0);opacity:1; }
.mv-toast svg { width:18px;height:18px;flex-shrink:0; }

/* ── YouTube checkpoint modal ── */
.mv-yt-modal-overlay {
  position:fixed;inset:0;background:rgba(15,23,42,.72);z-index:10050;
  display:none;align-items:center;justify-content:center;padding:1.25rem;
}
.mv-yt-modal-overlay.is-open { display:flex; }
.mv-yt-modal {
  width:100%;max-width:440px;background:#fff;border:1px solid var(--ka-border,#e2e8f0);
  border-radius:14px;box-shadow:0 24px 48px rgba(0,0,0,.25);overflow:hidden;
}
.mv-yt-modal-hdr {
  padding:1rem 1.25rem;border-bottom:1px solid var(--ka-border,#e2e8f0);
  background:linear-gradient(135deg,var(--ka-navy,#1a3a5c),#254d75);
  color:#fff;font-size:.9375rem;font-weight:800;
}
.mv-yt-modal-body { padding:1.25rem; }
.mv-yt-modal-q { font-size:.875rem;font-weight:600;color:var(--ka-text,#1e293b);margin:0 0 1rem;line-height:1.45; }
.mv-yt-choice {
  display:block;width:100%;text-align:left;padding:.65rem .875rem;margin-bottom:.5rem;
  border-radius:8px;border:1.5px solid var(--ka-border,#e2e8f0);
  background:#fff;font-size:.8125rem;font-weight:600;color:var(--ka-text,#1e293b);
  cursor:pointer;transition:all .15s;
}
.mv-yt-choice:hover { border-color:var(--ka-primary,#6dabcf);background:var(--ka-accent,#e8f4fd); }
.mv-yt-choice.selected { border-color:var(--ka-navy,#1a3a5c);background:var(--ka-accent,#e8f4fd); }
.mv-yt-submit {
  width:100%;margin-top:.75rem;padding:.7rem 1rem;border-radius:8px;border:none;
  background:var(--ka-navy,#1a3a5c);color:#fff;font-size:.875rem;font-weight:700;cursor:pointer;
}
.mv-yt-submit:disabled { opacity:.55;cursor:not-allowed; }
.mv-yt-err {
  display:none;margin-top:.75rem;padding:.625rem .75rem;border-radius:8px;
  background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:.8125rem;font-weight:600;
}
.mv-yt-err.is-visible { display:block; }
.mv-yt-ytframe { width:100%;aspect-ratio:16/9;background:#000;min-height:240px; }
</style>

<!-- YouTube checkpoint modal (non-dismissible until correct) -->
<div class="mv-yt-modal-overlay" id="mvVcCheckpointOverlay" aria-hidden="true">
  <div class="mv-yt-modal" role="dialog" aria-modal="true" aria-labelledby="mvVcCheckpointTitle" onclick="event.stopPropagation();">
    <div class="mv-yt-modal-hdr" id="mvVcCheckpointTitle">Learning checkpoint</div>
    <div class="mv-yt-modal-body">
      <p class="mv-yt-modal-q" id="mvVcCheckpointQuestion"></p>
      <div id="mvVcCheckpointChoices"></div>
      <button type="button" class="mv-yt-submit" id="mvVcCheckpointSubmit" disabled>Submit answer</button>
      <div class="mv-yt-err" id="mvVcCheckpointErr"></div>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="mv-toast" id="mvToast">
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
  <span id="mvToastMsg">Module completed!</span>
</div>

<!-- Breadcrumb back link -->
<div style="margin-bottom:1rem;" class="animate__animated animate__fadeIn animate__fast">
  <a href="<?= base_url('index.php/courses/view/'.$module->course_id) ?>"
     style="display:inline-flex;align-items:center;gap:5px;font-size:.8125rem;font-weight:600;text-decoration:none;color:var(--ka-text-muted,#64748b);">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    <?= htmlspecialchars($module->course_title) ?>
  </a>
</div>

<div class="mv-layout animate__animated animate__fadeInUp animate__fast">

  <!-- ══ Main Content ════════════════════════════════════════ -->
  <div>
    <div class="mv-content-panel">

      <!-- Header -->
      <div class="mv-content-header">
        <?php
        $type_cfg = [
          'pdf'            => ['label'=>'PDF',           'bg'=>'#fef2f2','color'=>'#ef4444'],
          'video'          => ['label'=>'Video',         'bg'=>'#f5f3ff','color'=>'#8b5cf6'],
          'slides'         => ['label'=>'Slides',        'bg'=>'#eff6ff','color'=>'#3b82f6'],
          'audio'          => ['label'=>'Audio',         'bg'=>'#fffbeb','color'=>'#f59f00'],
          'zoom_recording' => ['label'=>'Zoom Recording','bg'=>'#ecfdf5','color'=>'#22c55e'],
        ];
        $tc = $type_cfg[$module->content_type] ?? ['label'=>'Content','bg'=>'#f8fafc','color'=>'#64748b'];
        ?>
        <span class="mv-content-type-badge"
              style="background:<?= $tc['bg'] ?>;color:<?= $tc['color'] ?>;">
          <?= $tc['label'] ?>
        </span>
        <div>
          <div class="mv-title"><?= htmlspecialchars($module->title) ?></div>
          <?php if ( ! empty($module->description)): ?>
          <div class="mv-desc"><?= htmlspecialchars($module->description) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($pre_blocked && $pre_assessment): ?>
      <!-- ── PRE-ASSESSMENT GATE ── -->
      <div class="mv-gate">
        <div class="mv-gate-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <div class="mv-gate-title">Pre-Assessment Required</div>
        <div class="mv-gate-sub">
          You must complete the pre-assessment for this module before viewing its content.<br>
          The pre-assessment helps us understand your baseline knowledge.
        </div>
        <a href="<?= base_url('index.php/assessments/take/'.$pre_assessment->id) ?>"
           class="mv-gate-btn">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          Start Pre-Assessment
        </a>
      </div>

      <?php elseif ($module->content_type === 'pdf'): ?>
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
        <div style="padding:3rem;text-align:center;">
          <div style="width:80px;height:80px;border-radius:50%;background:#eff6ff;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
          </div>
          <div style="font-size:1rem;font-weight:700;color:var(--ka-text,#1e293b);margin-bottom:.5rem;">
            <?= htmlspecialchars($module->title) ?>
          </div>
          <div style="font-size:.875rem;color:var(--ka-text-muted,#64748b);margin-bottom:1.5rem;">
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

      <!-- ── Complete Bar ─────────────────────────────── -->
      <?php if ( ! $pre_blocked): ?>
      <div class="mv-complete-bar">
        <div class="mv-complete-status <?= $is_completed ? 'done' : 'pending' ?>" id="mvCompleteStatus">
          <?php if ($is_completed): ?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
            Module completed
          <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span id="mvCompleteHint">
              <?php if ($module->content_type === 'pdf' || $module->content_type === 'slides'): ?>
                Scroll to the end to complete
              <?php elseif ($module->content_type === 'video' && $youtube_video_id && $video_checkpoint_gate): ?>
                Answer all video checkpoints, then watch to the end to mark complete
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
        <button type="button" class="mv-mark-btn" disabled style="background:#ecfdf5;color:#15803d;">
          ✓ Completed
        </button>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div><!-- /mv-content-panel -->

    <!-- ── Navigation ── -->
    <div class="mv-nav" style="margin-top:.875rem;">
      <?php if ($prev_module): ?>
      <a href="<?= base_url('index.php/courses/module/'.$prev_module->id) ?>" class="mv-nav-btn">
        ← <?= htmlspecialchars(mb_strimwidth($prev_module->title, 0, 28, '…')) ?>
      </a>
      <?php else: ?>
      <a href="<?= base_url('index.php/courses/view/'.$module->course_id) ?>" class="mv-nav-btn">
        ← Back to Course
      </a>
      <?php endif; ?>

      <?php if ($next_module): ?>
      <a href="<?= base_url('index.php/courses/module/'.$next_module->id) ?>" class="mv-nav-btn primary" id="mvNextBtn"
         <?= ! $is_completed ? 'style="opacity:.45;pointer-events:none;"' : '' ?>>
        <?= htmlspecialchars(mb_strimwidth($next_module->title, 0, 28, '…')) ?> →
      </a>
      <?php else: ?>
      <a href="<?= base_url('index.php/courses/view/'.$module->course_id) ?>" class="mv-nav-btn primary">
        Back to Course →
      </a>
      <?php endif; ?>
    </div>

  </div>

  <!-- ══ Sidebar ═══════════════════════════════════════════ -->
  <div>

    <!-- Post-assessment CTA (shown when module is completed and has post-asx) -->
    <?php if ($is_completed && ! empty($post_assessments)): ?>
    <div class="mv-post-asx animate__animated animate__fadeInDown animate__fast">
      <h4>Post-Assessment</h4>
      <p>You've completed this module. Take the post-assessment to test your understanding.</p>
      <?php foreach ($post_assessments as $pa): ?>
        <?php
        $has_done = $pa->has_done ?? false;
        $result   = $pa->result   ?? ['score' => 0, 'pending' => 0, 'scored' => 0, 'total' => 0];
        $passed   = $pa->passed   ?? false;
        ?>
        <?php if ($passed): ?>
          <div style="background:#ecfdf5;border-radius:7px;padding:.625rem .875rem;font-size:.8125rem;font-weight:700;color:#065f46;">
            ✓ Passed (<?= number_format($result['score'], 1) ?>%)
          </div>
        <?php elseif ($has_done && $result && $result['pending'] > 0): ?>
          <div style="background:#fffbeb;border-radius:7px;padding:.625rem .875rem;font-size:.8125rem;font-weight:700;color:#92400e;">
            ⏳ Awaiting grading
          </div>
        <?php elseif ($has_done): ?>
          <div style="background:#fef2f2;border-radius:7px;padding:.5rem .875rem;font-size:.8125rem;color:#7f1d1d;margin-bottom:.5rem;">
            Score: <?= number_format($result['score'], 1) ?>% — need <?= number_format($assessment_pass_threshold, 0) ?>% to pass
          </div>
          <a href="<?= base_url('index.php/assessments/take/'.$pa->id) ?>"
             class="mv-post-asx-btn">Retake Assessment</a>
        <?php else: ?>
          <a href="<?= base_url('index.php/assessments/take/'.$pa->id) ?>"
             class="mv-post-asx-btn">Take Post-Assessment</a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Module list -->
    <div class="mv-sidebar-panel animate__animated animate__fadeInRight animate__fast">
      <div class="mv-sidebar-hdr">
        Course Modules
        <span style="font-weight:400;color:var(--ka-text-muted,#64748b);font-size:.75rem;">
          — <?= htmlspecialchars(mb_strimwidth($module->course_title ?? '', 0, 22, '…')) ?>
        </span>
      </div>
      <?php foreach ($all_modules as $idx => $m):
        $is_cur = ((int)$m->id === (int)$module->id);
        $m_done = ($m->my_status === 'completed');
        $num_class = $m_done ? 'completed' : ($is_cur ? 'current' : 'pending');
      ?>
      <a href="<?= base_url('index.php/courses/module/'.$m->id) ?>"
         class="mv-mod-item <?= $is_cur ? 'current' : '' ?>"
         <?= $is_cur ? 'aria-current="page"' : '' ?>>
        <div class="mv-mod-num <?= $num_class ?>">
          <?= $m_done ? '✓' : ($idx + 1) ?>
        </div>
        <div>
          <div class="mv-mod-title"><?= htmlspecialchars($m->title) ?></div>
          <div class="mv-mod-status">
            <?= ucfirst(str_replace('_', ' ', $m->my_status ?? 'not started')) ?>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Back to course -->
    <a href="<?= base_url('index.php/courses/view/'.$module->course_id) ?>"
       style="display:block;padding:.75rem;border-radius:8px;text-align:center;font-size:.8125rem;font-weight:600;text-decoration:none;border:1.5px solid var(--ka-border,#e2e8f0);color:var(--ka-text-muted,#64748b);transition:all .15s;"
       onmouseover="this.style.borderColor='var(--ka-primary,#6dabcf)'"
       onmouseout="this.style.borderColor='var(--ka-border,#e2e8f0)'">
      ← Back to Course Overview
    </a>

  </div>
</div>

<script>
var IS_COMPLETED  = <?= $is_completed ? 'true' : 'false' ?>;
var CONTENT_TYPE  = '<?= $module->content_type ?? '' ?>';
var MARK_URL      = '<?= $MARK_COMPLETE_URL ?>';
var CSRF_NAME     = '<?= $CSRF_NAME ?>';
var CSRF_HASH     = '<?= $CSRF_HASH ?>';
var HAS_NEXT      = <?= $next_module ? 'true' : 'false' ?>;
var NEXT_URL      = '<?= $next_module ? base_url('index.php/courses/module/'.$next_module->id) : '' ?>';
var PRE_BLOCKED   = <?= $pre_blocked ? 'true' : 'false' ?>;

var IS_YOUTUBE_IFRAME = <?= $youtube_video_id ? 'true' : 'false' ?>;
var MODULE_ID = <?= (int) ($module->id ?? 0) ?>;
var VIDEO_CHECKPOINTS = <?= json_encode($video_checkpoint_payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
var VIDEO_CHECKPOINT_GATE = <?= $video_checkpoint_gate ? 'true' : 'false' ?>;
var VIDEO_CHECKPOINT_SUBMIT_URL = <?= json_encode((string) $video_checkpoint_submit_url) ?>;
var ytPlayer = null;
var vcPassed = {};
var ytCheckTimer = null;
var vcActiveCheckpoint = null;
var ytPlaybackActive = false;
var ytPlayerReady = false;
<?php foreach ($video_checkpoint_passed_ids as $_pid): ?>
vcPassed[<?= (int) $_pid ?>] = true;
<?php endforeach; ?>

function showToast(msg) {
  var t = document.getElementById('mvToast');
  document.getElementById('mvToastMsg').textContent = msg;
  t.classList.add('show');
  setTimeout(function() { t.classList.remove('show'); }, 3500);
}

function markComplete() {
  if (IS_COMPLETED) return;
  var fd = new FormData();
  fd.append(CSRF_NAME, CSRF_HASH);
  fetch(MARK_URL, { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success || data.message === 'Already completed.') {
        IS_COMPLETED = true;

        // Update status label
        var statusEl = document.getElementById('mvCompleteStatus');
        if (statusEl) {
          statusEl.className = 'mv-complete-status done';
          statusEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:18px;height:18px"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg> Module completed';
        }

        // Update mark button
        var btn = document.getElementById('mvMarkBtn');
        if (btn) {
          btn.textContent = '✓ Completed';
          btn.style.background = '#ecfdf5';
          btn.style.color = '#15803d';
          btn.disabled = true;
        }

        // Enable next button
        var nextBtn = document.getElementById('mvNextBtn');
        if (nextBtn) {
          nextBtn.style.opacity = '1';
          nextBtn.style.pointerEvents = 'auto';
        }

        showToast('Module completed! ' + (data.course_done ? '🎉 Course finished!' : data.progress_pct + '% overall'));

        // Reload post-assessment section
        if (data.success) {
          setTimeout(function() { location.reload(); }, 1800);
        }
      } else if (data.message) {
        showToast(data.message);
      }
    })
    .catch(function(e) { console.error('complete_module error:', e); });
}

/** Single trigger model: 0..1 playback progress when checkpoint fires (seconds override percent). */
function ytTriggerProgress01(q, durationSec) {
  var ts = parseInt(q.trigger_seconds, 10) || 0;
  if (ts > 0 && durationSec > 0) {
    return Math.min(1, Math.max(0, ts / durationSec));
  }
  var p = parseFloat(q.trigger_percent);
  if (!isNaN(p)) return Math.min(1, Math.max(0, p / 100));
  return 0.25;
}

function vcSortedCheckpointsByTrigger(durationSec) {
  return (VIDEO_CHECKPOINTS || []).slice().sort(function(a, b) {
    return ytTriggerProgress01(a, durationSec) - ytTriggerProgress01(b, durationSec);
  });
}

function vcAllRequiredPassed() {
  if (!VIDEO_CHECKPOINT_GATE) return true;
  for (var i = 0; i < VIDEO_CHECKPOINTS.length; i++) {
    var q = VIDEO_CHECKPOINTS[i];
    if (q.is_required && !vcPassed[q.id]) return false;
  }
  return true;
}

function ytStopProgressCheck() {
  if (ytCheckTimer) {
    clearInterval(ytCheckTimer);
    ytCheckTimer = null;
  }
}

function ytStartProgressCheck() {
  ytStopProgressCheck();
  if (!ytPlaybackActive) return;
  ytCheckTimer = setInterval(ytTick, 1000);
}

function vcModalIsOpen() {
  var o = document.getElementById('mvVcCheckpointOverlay');
  return o && o.classList.contains('is-open');
}

function vcOpenCheckpointModal(cp) {
  vcActiveCheckpoint = cp;
  ytStopProgressCheck();
  if (ytPlayer && ytPlayer.pauseVideo) ytPlayer.pauseVideo();

  var overlay = document.getElementById('mvVcCheckpointOverlay');
  var qEl = document.getElementById('mvVcCheckpointQuestion');
  var cEl = document.getElementById('mvVcCheckpointChoices');
  var err = document.getElementById('mvVcCheckpointErr');
  var sub = document.getElementById('mvVcCheckpointSubmit');
  if (err) { err.textContent = ''; err.classList.remove('is-visible'); }
  if (qEl) qEl.textContent = cp.question || '';
  if (cEl) {
    cEl.innerHTML = '';
    (cp.choices || []).forEach(function(label, idx) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'mv-yt-choice';
      b.setAttribute('data-idx', String(idx));
      b.textContent = label;
      b.addEventListener('click', function() {
        cEl.querySelectorAll('.mv-yt-choice').forEach(function(x) { x.classList.remove('selected'); });
        b.classList.add('selected');
        if (sub) sub.disabled = false;
      });
      cEl.appendChild(b);
    });
  }
  if (sub) sub.disabled = true;
  if (overlay) {
    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden', 'false');
  }
}

function vcCloseCheckpointModal() {
  vcActiveCheckpoint = null;
  var overlay = document.getElementById('mvVcCheckpointOverlay');
  if (overlay) {
    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden', 'true');
  }
  if (ytPlayer && ytPlayer.playVideo) ytPlayer.playVideo();
}

function ytTick() {
  if (!ytPlaybackActive || !ytPlayer || !ytPlayer.getCurrentTime || vcModalIsOpen()) return;
  var d = ytPlayer.getDuration();
  var t = ytPlayer.getCurrentTime();
  if (!(d > 0)) return;
  var progress = t / d;
  if (!VIDEO_CHECKPOINT_GATE && progress >= 0.8) {
    var mbEarly = document.getElementById('mvMarkBtn');
    if (mbEarly) mbEarly.disabled = false;
  }
  var order = vcSortedCheckpointsByTrigger(d);
  for (var i = 0; i < order.length; i++) {
    var q = order[i];
    if (vcPassed[q.id]) continue;
    if (progress + 1e-6 >= ytTriggerProgress01(q, d) - 0.002) {
      vcOpenCheckpointModal(q);
      return;
    }
  }
}

/** If IFrame API never becomes ready, avoid leaving the user stuck on a dead control. */
function mvYoutubeApiWatchdog() {
  if (!IS_YOUTUBE_IFRAME || IS_COMPLETED) return;
  if (ytPlayerReady) return;
  showToast('Video player could not load. You can use Mark as Complete after a short wait, or reload the page.');
  setTimeout(function() {
    var b = document.getElementById('mvMarkBtn');
    var h = document.getElementById('mvCompleteHint');
    if (!b || IS_COMPLETED) return;
    if (!VIDEO_CHECKPOINT_GATE) {
      b.disabled = false;
      if (h) h.textContent = 'Mark as complete (video unavailable)';
    }
  }, 45000);
  setTimeout(function() {
    var b = document.getElementById('mvMarkBtn');
    var h = document.getElementById('mvCompleteHint');
    if (!b || IS_COMPLETED) return;
    if (VIDEO_CHECKPOINT_GATE) {
      b.disabled = false;
      if (h) {
        h.textContent = 'If the player failed, reload. Mark as Complete still requires all checkpoints on the server.';
      }
    }
  }, 90000);
}

function onYouTubeIframeAPIReady() {
  if (!IS_YOUTUBE_IFRAME) return;
  function boot() {
    if (IS_COMPLETED) return;
    var host = document.getElementById('mvYouTubePlayer');
    if (!host || ytPlayer || !window.YT || !YT.Player) return;
    ytPlayer = new YT.Player('mvYouTubePlayer', {
      videoId: <?= json_encode((string) $youtube_video_id) ?>,
      width: '100%',
      height: '100%',
      playerVars: { rel: 0, modestbranding: 1, enablejsapi: 1 },
      events: {
        onReady: function() {
          ytPlayerReady = true;
        },
        onStateChange: function(ev) {
          if (typeof YT === 'undefined' || !YT.PlayerState) return;
          var markBtn = document.getElementById('mvMarkBtn');
          var YS = YT.PlayerState;
          if (ev.data === YS.PLAYING) {
            ytPlaybackActive = true;
            ytStartProgressCheck();
          } else if (ev.data === YS.PAUSED || ev.data === YS.BUFFERING || ev.data === YS.CUED) {
            ytPlaybackActive = false;
            ytStopProgressCheck();
          } else if (ev.data === YS.ENDED) {
            ytPlaybackActive = false;
            ytStopProgressCheck();
            if (markBtn) markBtn.disabled = false;
            if (VIDEO_CHECKPOINT_GATE) {
              if (vcAllRequiredPassed()) {
                showToast('Video finished. Click Mark as Complete when ready.');
              } else {
                showToast('Answer all checkpoints to complete this module.');
                if (markBtn) markBtn.disabled = true;
              }
            } else {
              markComplete();
            }
          }
        }
      }
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
}

document.addEventListener('DOMContentLoaded', function() {
  var ovBg = document.getElementById('mvVcCheckpointOverlay');
  if (ovBg) {
    ovBg.addEventListener('mousedown', function(ev) {
      if (ev.target === ovBg) {
        ev.preventDefault();
        ev.stopPropagation();
      }
    });
  }

  var qSub = document.getElementById('mvVcCheckpointSubmit');
  if (qSub) {
    qSub.addEventListener('click', function() {
      if (!vcActiveCheckpoint) return;
      var cEl = document.getElementById('mvVcCheckpointChoices');
      var sel = cEl ? cEl.querySelector('.mv-yt-choice.selected') : null;
      var err = document.getElementById('mvVcCheckpointErr');
      if (!sel) {
        if (err) { err.textContent = 'Select an answer.'; err.classList.add('is-visible'); }
        return;
      }
      var idx = parseInt(sel.getAttribute('data-idx'), 10);
      if (isNaN(idx) || idx < 0) {
        if (err) { err.textContent = 'Select a valid answer.'; err.classList.add('is-visible'); }
        return;
      }
      var fd = new FormData();
      fd.append('assessment_id', String(vcActiveCheckpoint.id));
      fd.append('module_id', String(MODULE_ID));
      fd.append('choice_index', String(idx));
      if (CSRF_NAME) {
        fd.append(CSRF_NAME, CSRF_HASH);
      }
      qSub.disabled = true;
      qSub.setAttribute('aria-busy', 'true');
      fetch(VIDEO_CHECKPOINT_SUBMIT_URL, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      })
        .then(function(r) {
          var status = r.status;
          return r.text().then(function(text) {
            var data = null;
            try {
              data = text ? JSON.parse(text) : null;
            } catch (e) {
              console.error('video_checkpoint_submit: JSON parse failed', {
                status: status,
                bodySnippet: text ? text.slice(0, 800) : ''
              });
              throw e;
            }
            if (!data || typeof data !== 'object') {
              console.error('video_checkpoint_submit: invalid JSON body', status, text ? text.slice(0, 800) : '');
              throw new Error('invalid_json');
            }
            if (!r.ok) {
              console.warn('video_checkpoint_submit: HTTP error', status, data);
            }
            return data;
          });
        })
        .then(function(data) {
          if (data.ok) {
            vcPassed[vcActiveCheckpoint.id] = true;
            if (err) err.classList.remove('is-visible');
            vcCloseCheckpointModal();
          } else {
            if (err) {
              err.textContent = data.message || 'Incorrect answer. Please try again.';
              err.classList.add('is-visible');
            }
            if (ytPlayer && ytPlayer.pauseVideo) ytPlayer.pauseVideo();
          }
        })
        .catch(function() {
          if (err) {
            err.textContent = 'Could not submit. Check your connection and try again.';
            err.classList.add('is-visible');
          }
        })
        .then(function() {
          qSub.removeAttribute('aria-busy');
          if (vcModalIsOpen()) qSub.disabled = false;
        });
    });
  }

  document.addEventListener('keydown', function(ev) {
    if (ev.key === 'Escape' && vcModalIsOpen()) {
      ev.preventDefault();
      ev.stopPropagation();
    }
  }, true);
});

document.addEventListener('DOMContentLoaded', function() {
  if (IS_COMPLETED || PRE_BLOCKED) return;

  var markBtn = document.getElementById('mvMarkBtn');

  // ── VIDEO: YouTube (IFrame API + checkpoints) ───────────────
  if (CONTENT_TYPE === 'video' && IS_YOUTUBE_IFRAME) {
    if (markBtn) {
      markBtn.disabled = true;
      if (VIDEO_CHECKPOINT_GATE && vcAllRequiredPassed()) {
        markBtn.disabled = false;
      }
    }
    if (markBtn) {
      markBtn.addEventListener('click', function() {
        if (!this.disabled && VIDEO_CHECKPOINT_GATE && !vcAllRequiredPassed()) {
          showToast('Answer all video checkpoints first.');
          return;
        }
        if (!this.disabled) markComplete();
      });
    }
    setTimeout(mvYoutubeApiWatchdog, 5000);
    return;
  }

  // ── VIDEO: HTML5 (non-YouTube) ───────────────────────────────
  if (CONTENT_TYPE === 'video') {
    var video = document.getElementById('mvVideo');
    if (video) {
      video.addEventListener('ended', function() {
        if (markBtn) markBtn.disabled = false;
        markComplete();
      });
      // Allow manual mark after 80% watched
      video.addEventListener('timeupdate', function() {
        if (video.duration > 0 && (video.currentTime / video.duration) >= 0.8) {
          if (markBtn) markBtn.disabled = false;
        }
      });
    }
  }

  // ── AUDIO: auto-complete on ended ──────────────────────────
  else if (CONTENT_TYPE === 'audio') {
    var audio = document.getElementById('mvAudio');
    if (audio) {
      audio.addEventListener('ended', function() {
        if (markBtn) markBtn.disabled = false;
        markComplete();
      });
      audio.addEventListener('timeupdate', function() {
        if (audio.duration > 0 && (audio.currentTime / audio.duration) >= 0.9) {
          if (markBtn) markBtn.disabled = false;
        }
      });
    }
  }

  // ── PDF: detect scroll completion via postMessage from iframe ──
  else if (CONTENT_TYPE === 'pdf') {
    // Enable after 30 seconds as fallback for PDF
    setTimeout(function() {
      if (markBtn && !IS_COMPLETED) {
        markBtn.disabled = false;
        var hint = document.getElementById('mvCompleteHint');
        if (hint) hint.textContent = 'Ready to mark as complete';
      }
    }, 30000);

    // PDF iframe scroll detection via message (works with same-origin PDFs)
    window.addEventListener('message', function(e) {
      if (e.data && e.data.type === 'pdf-scrolled-end') {
        if (markBtn) markBtn.disabled = false;
        markComplete();
      }
    });
  }

  // ── SLIDES (PDF): same as PDF ───────────────────────────────
  else if (CONTENT_TYPE === 'slides') {
    var ext = '<?= strtolower(pathinfo($content_path, PATHINFO_EXTENSION)) ?>';
    if (ext === 'pdf') {
      setTimeout(function() {
        if (markBtn && !IS_COMPLETED) markBtn.disabled = false;
      }, 20000);
    } else {
      // PPTX: enable after download click
      var dlBtn = document.getElementById('mvSlidesDownload');
      if (dlBtn) {
        dlBtn.addEventListener('click', function() {
          setTimeout(function() {
            if (markBtn && !IS_COMPLETED) markBtn.disabled = false;
          }, 2000);
        });
      }
    }
  }

  // ── ZOOM: enable after clicking the link ────────────────────
  else if (CONTENT_TYPE === 'zoom_recording') {
    var zoomBtn = document.getElementById('mvZoomLink');
    if (zoomBtn) {
      zoomBtn.addEventListener('click', function() {
        setTimeout(function() {
          if (markBtn && !IS_COMPLETED) {
            markBtn.disabled = false;
            var hint = document.getElementById('mvCompleteHint');
            if (hint) hint.textContent = 'Click to mark as complete after watching';
          }
        }, 1000);
      });
    }
  }

  // ── Manual mark button ──────────────────────────────────────
  if (markBtn) {
    markBtn.addEventListener('click', function() {
      if (!this.disabled) markComplete();
    });
  }
});
</script>
<?php if ( ! empty($youtube_video_id)): ?>
<script src="https://www.youtube.com/iframe_api"></script>
<?php endif; ?>
