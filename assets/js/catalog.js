(function() {
  'use strict';

  function baseUrl() {
    var ctx = window.APP_CONTEXT && window.APP_CONTEXT.catalog;
    var b = (ctx && ctx.courseProgressUrl) || window.KA_COURSE_PROGRESS_URL || '';
    if (!b) return '';
    return b.replace(/\/?$/, '/');
  }

  function applyFillAndPct(root, pct, complete) {
    pct = Math.max(0, Math.min(100, parseInt(pct, 10) || 0));
    complete = !!complete || pct >= 100;

    root.querySelectorAll('.cmp-pb-fill, .mv-progress-fill').forEach(function(el) {
      if (el.classList.contains('inline')) {
        el.style.setProperty('--module-progress-pct', pct + '%');
      } else {
        el.style.width = pct + '%';
      }
      el.classList.toggle('cmp-pb-fill--complete', complete);
    });

    root.querySelectorAll('.cmp-pb-pct').forEach(function(el) {
      if (el.id === 'mvProgressPct') return;
      el.textContent = pct + '%';
      el.classList.toggle('cmp-pb-pct--complete', complete);
      if (complete) {
        el.setAttribute('data-complete', '1');
      } else {
        el.removeAttribute('data-complete');
      }
    });
  }

  function updateListRowMeta(courseId, pct) {
    pct = Math.max(0, Math.min(100, parseInt(pct, 10) || 0));
    document.querySelectorAll('.cat-list-item[data-course-id="' + courseId + '"] .cmp-course-progress-meta').forEach(function(el) {
      el.textContent = pct >= 100 ? '✓ Completed' : pct + '% done';
      el.style.color = pct >= 100 ? '#22c55e' : 'var(--ka-primary,#6dabcf)';
    });
  }

  function updateDetailFoot(courseId, completed, total) {
    var foot = document.getElementById('cdEnrollProgFoot');
    if (!foot) return;
    if (String(foot.getAttribute('data-course-id') || '') !== String(courseId)) return;
    foot.textContent = completed + '/' + total + ' modules completed';
  }

  function refreshCourseProgress(courseId) {
    var b = baseUrl();
    if (!b || !courseId) return;

    fetch(b + encodeURIComponent(courseId), { credentials: 'same-origin' })
      .then(function(r) {
        return r.text().then(function(text) {
          try {
            return JSON.parse(text);
          } catch (e) {
            return null;
          }
        });
      })
      .then(function(data) {
        if (!data || !data.ok) return;
        var pct = data.course_progress_percent;
        var done = data.completed_modules;
        var tot = data.total_modules;
        var complete = parseInt(pct, 10) >= 100;

        document.querySelectorAll('.cmp-progress-sync[data-course-id="' + courseId + '"]').forEach(function(root) {
          applyFillAndPct(root, pct, complete);
        });

        updateListRowMeta(courseId, pct);
        updateDetailFoot(courseId, done, tot);
      })
      .catch(function() { /* ignore transient network errors */ });
  }

  window.addEventListener('course_progress_updated', function(ev) {
    var id = ev && ev.detail && ev.detail.courseId;
    id = parseInt(id, 10) || 0;
    if (id < 1) return;
    refreshCourseProgress(id);
  });
})();
