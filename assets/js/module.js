(function() {
'use strict';
var root = window.APP_CONTEXT || {};
var C = (root.module && typeof root.module === 'object') ? root.module : root;
if (!C.modulePage) {
  return;
}

var IS_COMPLETED = !!C.isCompleted;
var CONTENT_TYPE = C.contentType || '';
var MARK_URL = C.markUrl || '';
var CSRF_NAME = C.csrfName || '';
var CSRF_HASH = C.csrfHash || '';
var HAS_NEXT = !!C.hasNext;
var NEXT_URL = C.nextUrl || '';
var MODULE_STATE_URL = C.moduleStateUrl || '';
var PRE_BLOCKED = !!C.preBlocked;
var VIDEO_PRE_OPTIONAL = !!C.videoOptionalPre;
var VIDEO_COMPLETED_INITIAL = !!C.videoCompletedInitial;
var HAS_POST_ASSESSMENTS = !!C.hasPostAssessments;
var FIRST_POST_ASSESSMENT_HREF = C.firstPostAssessmentHref || '';
var PRE_RESULT_MODAL = C.preResultModal || null;
var POST_ASSESSMENT_PASSED = !!C.postAssessmentPassed;
var CAN_START_POST_ASSESSMENT = !!C.canStartPostAssessment;
var MODULE_PROGRESS_PERCENT = typeof C.moduleProgressPercent === 'number' ? C.moduleProgressPercent : 0;

var IS_YOUTUBE_IFRAME = !!C.isYoutubeIframe;
var MODULE_ID = parseInt(C.moduleId, 10) || 0;
var COURSE_ID = parseInt(C.courseId, 10) || 0;
var VIDEO_CHECKPOINTS = Array.isArray(C.videoCheckpoints) ? C.videoCheckpoints : [];
var VIDEO_CHECKPOINT_GATE = !!C.videoCheckpointGate;
var VIDEO_CHECKPOINT_SUBMIT_URL = C.videoCheckpointSubmitUrl || '';
var ytPlayer = null;
var vcPassed = {};
var ytCheckTimer = null;

function parseJsonResponse(r) {
  return r.text().then(function(text) {
    try {
      return JSON.parse(text);
    } catch (e) {
      return {
        success: false,
        ok: false,
        message: r.status === 401 ? 'Session expired. Please log in again.' : 'Unexpected server response.',
      };
    }
  });
}
var vcActiveCheckpoint = null;
var ytPlaybackActive = false;
var ytPlayerReady = false;

(C.vcPassedIds || []).forEach(function(id) {
  vcPassed[parseInt(id, 10)] = true;
});

var lastVideoCompleted = VIDEO_COMPLETED_INITIAL;

function showToast(msg) {
  var t = document.getElementById('mvToast');
  document.getElementById('mvToastMsg').textContent = msg;
  t.classList.add('show');
  setTimeout(function() { t.classList.remove('show'); }, 3500);
}

/** Course fully complete: certificate UX (no full page reload). */
function showCompletionModal(res) {
  var prev = document.getElementById('mvCourseCompleteOverlay');
  if (prev) prev.remove();

  var overlay = document.createElement('div');
  overlay.id = 'mvCourseCompleteOverlay';
  overlay.className = 'mv-completion-overlay';
  overlay.setAttribute('role', 'dialog');
  overlay.setAttribute('aria-modal', 'true');
  overlay.setAttribute('aria-labelledby', 'mvCourseCompleteTitle');

  var certUrl = res && res.certificate_url ? String(res.certificate_url) : '';
  var certReady = !!(res && res.certificate_ready);
  var extra = (res && res.certificate_message) ? String(res.certificate_message) : '';

  overlay.innerHTML =
    '<div class="mv-completion-dialog">' +
      '<h2 id="mvCourseCompleteTitle" class="mv-completion-title">Course completed</h2>' +
      '<p class="mv-completion-msg">Congratulations — you finished every module for this course.' +
      (certReady ? ' Your certificate is ready.' : '') + '</p>' +
      (extra ? '<p class="mv-completion-note">' + extra.replace(/</g, '&lt;') + '</p>' : '') +
      '<div class="mv-completion-actions">' +
      (certUrl
        ? '<a class="mv-completion-primary" href="' + certUrl.replace(/"/g, '&quot;') + '" target="_blank" rel="noopener">View certificate</a>'
        : '') +
      '<button type="button" class="mv-completion-close">Close</button>' +
      '</div></div>';

  function closeIt() {
    overlay.remove();
    document.removeEventListener('keydown', onKey);
  }
  function onKey(ev) {
    if (ev.key === 'Escape') closeIt();
  }

  overlay.addEventListener('click', function(ev) {
    if (ev.target === overlay) closeIt();
  });
  var closeBtn = overlay.querySelector('.mv-completion-close');
  if (closeBtn) closeBtn.addEventListener('click', closeIt);

  document.addEventListener('keydown', onKey);
  document.body.appendChild(overlay);
}

function updatePostAssessmentSummaryText() {
  var s = document.getElementById('mvPostAssessmentSummary');
  if (!s) return;
  if (IS_COMPLETED) {
    s.textContent = 'Review your post-assessment results for this module.';
  } else if (POST_ASSESSMENT_PASSED) {
    s.innerHTML = 'All post-assessments passed. Finish any remaining content steps, then use <strong>Mark as Complete</strong>.';
  } else if (!CAN_START_POST_ASSESSMENT) {
    s.innerHTML = '<strong>Locked:</strong> Locked until you complete the video checkpoints.';
  } else {
    s.innerHTML = '<strong>Required:</strong> pass all post-assessments below before you can mark this module complete (you can retake until you pass).';
  }
}

function updatePostAssessmentButtonsState() {
  var actions = document.querySelectorAll('.js-post-asx-action');
  actions.forEach(function(a) {
    if (!CAN_START_POST_ASSESSMENT) {
      a.classList.add('is-disabled');
      a.setAttribute('aria-disabled', 'true');
      a.setAttribute('title', 'Locked until you complete the video checkpoints');
      a.setAttribute('href', 'javascript:void(0)');
    } else {
      var to = a.getAttribute('data-href') || '';
      a.classList.remove('is-disabled');
      a.removeAttribute('aria-disabled');
      a.removeAttribute('title');
      if (to) a.setAttribute('href', to);
    }
  });
}

function triggerPostUnlockAnimation() {
  var actions = document.querySelectorAll('.js-post-asx-action');
  actions.forEach(function(a) {
    a.classList.remove('is-unlocking');
    void a.offsetWidth;
    a.classList.add('is-unlocking');
    setTimeout(function() { a.classList.remove('is-unlocking'); }, 750);
  });
}

function animateProgressValue(targetPercent) {
  var fill = document.getElementById('mvProgressFill');
  var pct = document.getElementById('mvProgressPct');
  if (!fill || !pct) return;

  var target = Math.max(0, Math.min(100, parseInt(targetPercent, 10) || 0));
  var start = Math.max(0, Math.min(100, parseInt(MODULE_PROGRESS_PERCENT, 10) || 0));
  var diff = target - start;
  if (diff === 0) {
    fill.style.width = target + '%';
    pct.textContent = target + '%';
    return;
  }

  var duration = Math.min(900, Math.max(600, 600 + Math.abs(diff) * 6));
  var startedAt = null;

  function step(ts) {
    if (startedAt === null) startedAt = ts;
    var elapsed = ts - startedAt;
    var t = Math.min(1, elapsed / duration);
    // easeOutCubic
    var eased = 1 - Math.pow(1 - t, 3);
    var v = start + diff * eased;
    var shown = Math.round(v);
    fill.style.width = shown + '%';
    pct.textContent = shown + '%';
    if (t < 1) {
      requestAnimationFrame(step);
    } else {
      fill.style.width = target + '%';
      pct.textContent = target + '%';
      MODULE_PROGRESS_PERCENT = target;
    }
  }

  requestAnimationFrame(step);
}

function updateProgressBarUI(state) {
  var fill = document.getElementById('mvProgressFill');
  var pct = document.getElementById('mvProgressPct');
  var meta = document.getElementById('mvProgressMeta');
  if (!fill || !pct || !meta || !state) return;

  var p = parseInt(state.progress_percent, 10);
  if (isNaN(p)) p = 0;
  p = Math.max(0, Math.min(100, p));
  animateProgressValue(p);

  var t = parseInt(state.checkpoints_total, 10) || 0;
  var c = parseInt(state.checkpoints_completed, 10) || 0;
  if (state.post_assessment_passed) {
    meta.textContent = 'Post-assessment passed. Module progress complete.';
  } else if (state.video_completed) {
    meta.textContent = 'Video checkpoints completed. Post-assessment unlocked.';
  } else if (t > 0) {
    meta.textContent = 'Checkpoint progress: ' + c + '/' + t + ' required completed.';
  } else {
    meta.textContent = 'Progress updates as you complete module steps.';
  }
}

function applyModuleFlowState(state) {
  if (!state || typeof state !== 'object') return;
  var wasVideoCompleted = lastVideoCompleted;
  var prevPostPassed = POST_ASSESSMENT_PASSED;
  var prevCanStart = CAN_START_POST_ASSESSMENT;
  POST_ASSESSMENT_PASSED = !!state.post_assessment_passed;
  CAN_START_POST_ASSESSMENT = !!state.can_start_post_assessment;
  if (!prevPostPassed && POST_ASSESSMENT_PASSED) {
    dispatchCourseProgressUpdated();
  }
  updateProgressBarUI(state);
  updatePostAssessmentSummaryText();
  updatePostAssessmentButtonsState();
  if (!prevCanStart && CAN_START_POST_ASSESSMENT) {
    triggerPostUnlockAnimation();
  }

  lastVideoCompleted = !!state.video_completed;
  if (CONTENT_TYPE === 'video' && HAS_POST_ASSESSMENTS && !IS_COMPLETED
      && state.video_completed && !wasVideoCompleted && !state.post_assessment_passed) {
    runVideoCompletionUx(state);
  }
}

function mvCloseVideoCompleteOverlay() {
  var el = document.getElementById('mvVideoCompleteOverlay');
  if (!el || !el.classList.contains('is-open')) return;
  el.classList.remove('is-open');
  el.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

function mvOpenVideoCompleteOverlay() {
  var el = document.getElementById('mvVideoCompleteOverlay');
  var a = document.getElementById('mvVideoCompleteTakePost');
  if (!el || !a) return;

  var href = FIRST_POST_ASSESSMENT_HREF;
  if (!href) {
    var pick = document.querySelector('.js-post-asx-action[data-href]');
    if (pick) href = pick.getAttribute('data-href') || '';
  }
  a.setAttribute('href', href || '#');
  if (!href || href === '#') {
    a.setAttribute('aria-disabled', 'true');
    a.classList.add('is-disabled');
  } else {
    a.removeAttribute('aria-disabled');
    a.classList.remove('is-disabled');
  }

  el.classList.add('is-open');
  el.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
  a.focus();
}

function runVideoCompletionUx(state) {
  var panel = document.getElementById('mvContentPanel');
  var wrap = document.querySelector('.mv-video-wrap');
  var side = document.querySelector('.mv-layout > div:last-child .mv-progress');

  if (panel) {
    panel.classList.add('mv-flow-celebrate');
    setTimeout(function() { panel.classList.remove('mv-flow-celebrate'); }, 1400);
  }
  if (wrap) {
    wrap.classList.add('mv-flow-celebrate');
    setTimeout(function() { wrap.classList.remove('mv-flow-celebrate'); }, 1400);
  }
  if (side) {
    side.classList.add('mv-flow-celebrate');
    setTimeout(function() { side.classList.remove('mv-flow-celebrate'); }, 1400);
  }

  mvOpenVideoCompleteOverlay();
}

function fetchModuleFlowStateAndApply() {
  if (!MODULE_STATE_URL) return Promise.resolve();
  return fetch(MODULE_STATE_URL, { credentials: 'same-origin' })
    .then(parseJsonResponse)
    .then(function(data) {
      if (data && data.ok) {
        applyModuleFlowState(data);
      }
    })
    .catch(function(e) {
      console.error('module_state fetch error:', e);
    });
}

function emitModuleFlowUpdated() {
  if (window.LMS_STATE && typeof window.LMS_STATE.syncModuleState === 'function') {
    window.LMS_STATE.syncModuleState();
  } else {
    document.dispatchEvent(new CustomEvent('module_flow_updated'));
  }
}

function dispatchCourseProgressUpdated() {
  if (COURSE_ID < 1) return;
  if (window.LMS_STATE && typeof window.LMS_STATE.syncCourseProgress === 'function') {
    window.LMS_STATE.syncCourseProgress(COURSE_ID);
  } else {
    window.dispatchEvent(new CustomEvent('course_progress_updated', {
      detail: { courseId: COURSE_ID },
    }));
  }
}

function markComplete() {
  if (IS_COMPLETED) return;
  var fd = new FormData();
  if (CSRF_NAME) {
    fd.append(CSRF_NAME, CSRF_HASH || '');
  }
  var withIndex = function(url) {
    if (!url || url.indexOf('/index.php/') !== -1) return url;
    try {
      var u = new URL(url, window.location.origin);
      if (u.pathname.indexOf('/index.php/') === -1) {
        u.pathname = u.pathname.replace('/lms/', '/lms/index.php/');
      }
      return u.toString();
    } catch (e) {
      return url;
    }
  };

  var postComplete = function(url, retried) {
    return fetch(url, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function(r) {
        if (r.status === 404 && !retried) {
          return postComplete(withIndex(url), true);
        }
        return parseJsonResponse(r);
      });
  };

  postComplete(MARK_URL, false)
    .then(function(data) {
      if ( ! (data.success || data.ok || data.message === 'Already completed.')) {
        showToast(data.message || 'Could not mark complete.');
        return;
      }
      if (data.success || data.ok || data.message === 'Already completed.') {
        IS_COMPLETED = true;

        // Update status label
        var statusEl = document.getElementById('mvCompleteStatus');
        if (statusEl) {
          statusEl.className = 'mv-complete-status done';
          statusEl.innerHTML = '<svg class="mv-complete-status-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg> Module completed';
        }

        // Update mark button
        var btn = document.getElementById('mvMarkBtn');
        if (btn) {
          btn.textContent = '✓ Completed';
          btn.classList.add('mv-mark-btn-done');
          btn.disabled = true;
        }

        // Enable next button
        var nextBtn = document.getElementById('mvNextBtn');
        if (nextBtn) {
          nextBtn.classList.remove('mv-next-locked');
          nextBtn.classList.add('mv-next-unlocked');
        }

        showToast('Module completed! ' + (data.course_completed || data.course_done ? '🎉 Course finished!' : (data.progress_pct != null ? data.progress_pct + '% overall' : '')));

        dispatchCourseProgressUpdated();

        if (data.course_completed && data.certificate_url) {
          showCompletionModal(data);
        }

        // No page reload; this page keeps state reactively.
      }
    })
    .catch(function(e) {
      console.error('complete_module error:', e);
      showToast('Could not mark complete. Please try again.');
    });
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

var preAssessmentModalTimer = null;

function mvResumePlaybackAfterPreModal() {
  if (typeof ytPlayer !== 'undefined' && ytPlayer && ytPlayer.playVideo) {
    try { ytPlayer.playVideo(); } catch (e) {}
  }
  var v = document.getElementById('mvVideo');
  if (v && v.play) {
    v.play().catch(function() {});
  }
}

function mvDismissPreAssessmentModal() {
  var el = document.getElementById('mvPreAssessmentOverlay');
  if ( ! el || ! el.classList.contains('is-open')) return;
  el.classList.remove('is-open');
  el.setAttribute('aria-hidden', 'true');
  if (preAssessmentModalTimer) {
    clearTimeout(preAssessmentModalTimer);
    preAssessmentModalTimer = null;
  }
  document.body.style.overflow = '';
  mvResumePlaybackAfterPreModal();
}

function mvShowPreAssessmentModal(data) {
  if (!data || typeof data !== 'object') return;
  var el = document.getElementById('mvPreAssessmentOverlay');
  var badge = document.getElementById('mvPreAssessmentBadge');
  var scoreEl = document.getElementById('mvPreAssessmentScore');
  var note = document.getElementById('mvPreAssessmentNote');
  var btn = document.getElementById('mvPreAssessmentContinue');
  var link = document.getElementById('mvPreAssessmentDetail');
  if (!el || !badge || !scoreEl || !note || !btn || !link) return;

  var pending = parseInt(data.pending_count, 10) || 0;
  badge.classList.remove('pass', 'fail', 'pending');

  if (pending > 0) {
    badge.classList.add('pending');
    badge.textContent = 'Awaiting review';
    scoreEl.textContent = typeof data.score === 'number'
      ? (data.score + '% (provisional)')
      : (String(data.score) + '% (provisional)');
  } else if (data.passed) {
    badge.classList.add('pass');
    badge.textContent = 'Passed';
    scoreEl.textContent = (typeof data.score === 'number' ? data.score : parseFloat(data.score)) + '%';
  } else {
    badge.classList.add('fail');
    badge.textContent = 'Below threshold';
    scoreEl.textContent = (typeof data.score === 'number' ? data.score : parseFloat(data.score)) + '%';
  }

  var th = typeof data.threshold === 'number'
    ? data.threshold
    : parseFloat(data.threshold || 0);
  note.textContent = 'This optional pre-assessment measures readiness only—it does not affect your '
    + 'ability to watch the lesson. Pass mark is typically ' + th + '% for scored items.';
  link.href = data.detail_url || '#';
  link.style.display = data.detail_url ? 'inline-block' : 'none';

  el.classList.add('is-open');
  el.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';

  btn.focus();

  preAssessmentModalTimer = setTimeout(mvDismissPreAssessmentModal, 4200);
}

function ytTick() {
  if (!ytPlaybackActive || !ytPlayer || !ytPlayer.getCurrentTime || vcModalIsOpen()) return;
  var d = ytPlayer.getDuration();
  var t = ytPlayer.getCurrentTime();
  if (!(d > 0)) return;
  var progress = t / d;
  if (!VIDEO_CHECKPOINT_GATE && progress >= 0.8 && POST_ASSESSMENT_PASSED) {
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
    if (!VIDEO_CHECKPOINT_GATE && POST_ASSESSMENT_PASSED) {
      b.disabled = false;
      if (h) h.textContent = 'Mark as complete (video unavailable)';
    }
  }, 45000);
  setTimeout(function() {
    var b = document.getElementById('mvMarkBtn');
    var h = document.getElementById('mvCompleteHint');
    if (!b || IS_COMPLETED) return;
    if (VIDEO_CHECKPOINT_GATE && POST_ASSESSMENT_PASSED) {
      b.disabled = false;
      if (h) {
        h.textContent = 'If the player failed, reload. Mark as Complete still requires all required video checkpoints on the server.';
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
      videoId: (C.youtubeVideoId || ''),
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
            emitModuleFlowUpdated();
            if (VIDEO_CHECKPOINT_GATE) {
              if (vcAllRequiredPassed() && POST_ASSESSMENT_PASSED) {
                if (markBtn) markBtn.disabled = false;
                showToast('Video finished. Click Mark as Complete when ready.');
              } else {
                if ( ! POST_ASSESSMENT_PASSED) {
                  if (!HAS_POST_ASSESSMENTS) {
                    showToast('Pass the post-assessment in the sidebar before marking this module complete.');
                  }
                } else {
                  showToast('Answer all required video checkpoints to complete this module.');
                }
                if (markBtn) markBtn.disabled = true;
              }
            } else if (POST_ASSESSMENT_PASSED) {
              if (markBtn) markBtn.disabled = false;
              markComplete();
            } else if (markBtn) {
              markBtn.disabled = true;
              if (!HAS_POST_ASSESSMENTS) {
                showToast('Pass the post-assessment in the sidebar before marking this module complete.');
              }
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
  document.addEventListener('module_flow_updated', function() {
    fetchModuleFlowStateAndApply();
  });
  updatePostAssessmentSummaryText();
  updatePostAssessmentButtonsState();
  emitModuleFlowUpdated();

  if (PRE_RESULT_MODAL) {
    mvShowPreAssessmentModal(PRE_RESULT_MODAL);
    var preBtn = document.getElementById('mvPreAssessmentContinue');
    if (preBtn) {
      preBtn.addEventListener('click', function() { mvDismissPreAssessmentModal(); });
    }
    document.addEventListener('keydown', function(ev) {
      var o = document.getElementById('mvPreAssessmentOverlay');
      if (o && o.classList.contains('is-open') && ev.key === 'Escape') {
        ev.preventDefault();
        mvDismissPreAssessmentModal();
      }
    }, true);
  }

  var vco = document.getElementById('mvVideoCompleteOverlay');
  if (vco) {
    vco.addEventListener('mousedown', function(ev) {
      if (ev.target === vco) {
        ev.preventDefault();
        mvCloseVideoCompleteOverlay();
      }
    });
  }
  var vLater = document.getElementById('mvVideoCompleteLater');
  if (vLater) {
    vLater.addEventListener('click', function() { mvCloseVideoCompleteOverlay(); });
  }
  var vTake = document.getElementById('mvVideoCompleteTakePost');
  if (vTake) {
    vTake.addEventListener('click', function(ev) {
      var h = vTake.getAttribute('href') || '';
      if (!h || h === '#') {
        ev.preventDefault();
        mvCloseVideoCompleteOverlay();
        var card = document.getElementById('mvPostAssessmentCard');
        if (card) card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });
  }
  document.addEventListener('keydown', function(ev) {
    var vo = document.getElementById('mvVideoCompleteOverlay');
    if (vo && vo.classList.contains('is-open') && ev.key === 'Escape') {
      ev.preventDefault();
      mvCloseVideoCompleteOverlay();
    }
  }, true);

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
      fd.append('checkpoint_id', String(vcActiveCheckpoint.id));
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
            showToast('Checkpoint completed ✔');
            if (err) err.classList.remove('is-visible');
            vcCloseCheckpointModal();
            emitModuleFlowUpdated();
            dispatchCourseProgressUpdated();
            var mbSync = document.getElementById('mvMarkBtn');
            if (mbSync && POST_ASSESSMENT_PASSED && ( ! VIDEO_CHECKPOINT_GATE || vcAllRequiredPassed())) {
              mbSync.disabled = false;
            }
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
  if (IS_COMPLETED || (PRE_BLOCKED && !VIDEO_PRE_OPTIONAL)) return;

  var markBtn = document.getElementById('mvMarkBtn');

  // ── VIDEO: YouTube (IFrame API + checkpoints) ───────────────
  if (CONTENT_TYPE === 'video' && IS_YOUTUBE_IFRAME) {
    if (markBtn) {
      markBtn.disabled = true;
      if (POST_ASSESSMENT_PASSED && ( ! VIDEO_CHECKPOINT_GATE || vcAllRequiredPassed())) {
        markBtn.disabled = false;
      }
    }
    if (markBtn) {
      markBtn.addEventListener('click', function() {
        if (!this.disabled && VIDEO_CHECKPOINT_GATE && !vcAllRequiredPassed()) {
          showToast('Answer all video checkpoints first.');
          return;
        }
        if (!this.disabled && !POST_ASSESSMENT_PASSED) {
          showToast('Pass the post-assessment in the sidebar first.');
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
        emitModuleFlowUpdated();
        if (markBtn && POST_ASSESSMENT_PASSED) markBtn.disabled = false;
        if (POST_ASSESSMENT_PASSED) markComplete();
      });
      // Allow manual mark after 80% watched
      video.addEventListener('timeupdate', function() {
        if (POST_ASSESSMENT_PASSED && video.duration > 0 && (video.currentTime / video.duration) >= 0.8) {
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
        if (markBtn && POST_ASSESSMENT_PASSED) markBtn.disabled = false;
        if (POST_ASSESSMENT_PASSED) markComplete();
      });
      audio.addEventListener('timeupdate', function() {
        if (POST_ASSESSMENT_PASSED && audio.duration > 0 && (audio.currentTime / audio.duration) >= 0.9) {
          if (markBtn) markBtn.disabled = false;
        }
      });
    }
  }

  // ── PDF: detect scroll completion via postMessage from iframe ──
  else if (CONTENT_TYPE === 'pdf') {
    // Enable after 30 seconds as fallback for PDF
    setTimeout(function() {
      if (markBtn && !IS_COMPLETED && POST_ASSESSMENT_PASSED) {
        markBtn.disabled = false;
        var hint = document.getElementById('mvCompleteHint');
        if (hint) hint.textContent = 'Ready to mark as complete';
      }
    }, 30000);

    // PDF iframe scroll detection via message (works with same-origin PDFs)
    window.addEventListener('message', function(e) {
      if (e.data && e.data.type === 'pdf-scrolled-end') {
        if (markBtn && POST_ASSESSMENT_PASSED) markBtn.disabled = false;
        if (POST_ASSESSMENT_PASSED) markComplete();
      }
    });
  }

  // ── SLIDES (PDF): same as PDF ───────────────────────────────
  else if (CONTENT_TYPE === 'slides') {
    var ext = String(C.slidesFileExt || '').toLowerCase();
    if (ext === 'pdf') {
      setTimeout(function() {
        if (markBtn && !IS_COMPLETED && POST_ASSESSMENT_PASSED) markBtn.disabled = false;
      }, 20000);
    } else {
      // PPTX: enable after download click
      var dlBtn = document.getElementById('mvSlidesDownload');
      if (dlBtn) {
        dlBtn.addEventListener('click', function() {
          setTimeout(function() {
            if (markBtn && !IS_COMPLETED && POST_ASSESSMENT_PASSED) markBtn.disabled = false;
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
          if (markBtn && !IS_COMPLETED && POST_ASSESSMENT_PASSED) {
            markBtn.disabled = false;
            var hint = document.getElementById('mvCompleteHint');
            if (hint) hint.textContent = 'Click to mark as complete after watching';
          }
        }, 1000);
      });
    }
  }

  // ── Manual mark button (non–YouTube-iframe paths; YouTube registers its own handler above) ──
  if (markBtn) {
    markBtn.addEventListener('click', function() {
      if (!this.disabled && !POST_ASSESSMENT_PASSED) {
        showToast('Pass the post-assessment in the sidebar first.');
        return;
      }
      if (!this.disabled) markComplete();
    });
  }
});

window.onYouTubeIframeAPIReady = onYouTubeIframeAPIReady;

})();
