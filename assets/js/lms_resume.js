/**
 * LMS resume persistence (localStorage + optional server sync).
 */
(function(global) {
  'use strict';

  var cfg = {};
  var saveTimer = null;
  var lastPayload = '';

  function storageKey() {
    return 'ka_resume_u' + (cfg.userId || 0) + '_m' + (cfg.moduleId || 0);
  }

  function readLocal() {
    try {
      var raw = global.localStorage.getItem(storageKey());
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function writeLocal(payload) {
    try {
      global.localStorage.setItem(storageKey(), JSON.stringify(payload));
    } catch (e) {
      /* ignore quota */
    }
  }

  function mergeState(serverState, localState, queryHints) {
    var out = { type: cfg.contentType || '', position: 0, meta: {} };
    var sources = [serverState, localState, queryHints];
    sources.forEach(function(src) {
      if (!src || typeof src !== 'object') return;
      if (src.type) out.type = String(src.type);
      var p = parseFloat(src.position);
      if (!isNaN(p) && p > out.position) {
        out.position = p;
      }
      if (src.meta && typeof src.meta === 'object') {
        out.meta = Object.assign({}, out.meta, src.meta);
      }
    });
    if (!out.type) out.type = cfg.contentType || 'video';
    return out;
  }

  function payloadFromPosition(type, position, meta) {
    return {
      type: type || cfg.contentType || 'video',
      position: Math.max(0, parseFloat(position) || 0),
      meta: meta || {},
      updated_at: new Date().toISOString(),
    };
  }

  function saveToServer(payload) {
    if (!cfg.saveUrl) return;
    var bodyKey = JSON.stringify(payload);
    if (bodyKey === lastPayload) return;
    lastPayload = bodyKey;

    var fd = new FormData();
    fd.append('type', payload.type || '');
    fd.append('position', String(payload.position != null ? payload.position : 0));
    if (payload.meta && typeof payload.meta === 'object') {
      fd.append('meta', JSON.stringify(payload.meta));
    }
    if (cfg.csrfName && cfg.csrfHash) {
      fd.append(cfg.csrfName, cfg.csrfHash);
    }

    fetch(cfg.saveUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: fd,
    }).catch(function() {
      /* non-blocking */
    });
  }

  function scheduleSave(payload) {
    writeLocal(payload);
    if (saveTimer) clearTimeout(saveTimer);
    saveTimer = setTimeout(function() {
      saveToServer(payload);
    }, 1200);
  }

  function applyVideoResume(state) {
    var sec = Math.max(0, parseFloat(state.position) || 0);
    if (sec < 1) return;

    var html5 = document.getElementById('mvVideo');
    if (html5) {
      var seekHtml5 = function() {
        if (html5.duration && sec < html5.duration - 1) {
          html5.currentTime = sec;
        }
      };
      if (html5.readyState >= 1) seekHtml5();
      else html5.addEventListener('loadedmetadata', seekHtml5, { once: true });
    }

    if (cfg.onYoutubeReady && typeof cfg.onYoutubeReady === 'function') {
      cfg.onYoutubeReady(sec);
    }
  }

  function applyPdfResume(state) {
    var page = Math.max(1, parseInt(state.position, 10) || 1);
    if (global.ModulePdfViewer && typeof global.ModulePdfViewer.goToPage === 'function') {
      global.ModulePdfViewer.goToPage(page);
      return;
    }
    var frame = document.getElementById('mvPdfFrame') || document.getElementById('mvSlidesFrame');
    if (!frame || !frame.src) return;
    var base = String(frame.src).split('#')[0];
    frame.src = base + '#page=' + page;
  }

  function applySlidesResume(state) {
    var slide = Math.max(1, parseInt(state.position, 10) || 1);
    if (global.ModulePdfViewer && typeof global.ModulePdfViewer.goToPage === 'function') {
      global.ModulePdfViewer.goToPage(slide);
      return;
    }
    var wrap = document.getElementById('mvSlidesViewer');
    if (!wrap) return;
    var cards = wrap.querySelectorAll('.mv-slide-card');
    if (!cards.length) return;
    var idx = Math.min(cards.length - 1, slide - 1);
    var target = cards[idx];
    if (target && target.scrollIntoView) {
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function bindVideo(htmlVideo, youtubeHook) {
    cfg.onYoutubeReady = youtubeHook || null;

    if (htmlVideo) {
      htmlVideo.addEventListener('timeupdate', function() {
        if (!htmlVideo.duration || htmlVideo.paused) return;
        scheduleSave(payloadFromPosition('video', htmlVideo.currentTime));
      });
    }

    setInterval(function() {
      if (cfg.getYoutubeSeconds && typeof cfg.getYoutubeSeconds === 'function') {
        var t = cfg.getYoutubeSeconds();
        if (t > 0) scheduleSave(payloadFromPosition('video', t));
      }
    }, 5000);
  }

  function bindAudio(audioEl) {
    if (!audioEl) return;
    audioEl.addEventListener('timeupdate', function() {
      if (!audioEl.duration || audioEl.paused) return;
      scheduleSave(payloadFromPosition('audio', audioEl.currentTime));
    });
  }

  function init(options) {
    cfg = options || {};

    var merged = mergeState(cfg.serverState, readLocal(), cfg.queryHints || null);
    if (merged.position > 0) {
      if (merged.type === 'video' || merged.type === 'audio') applyVideoResume(merged);
      else if (merged.type === 'pdf') applyPdfResume(merged);
      else if (merged.type === 'slides') applySlidesResume(merged);
    }

    var ctype = (cfg.contentType || merged.type || '').toLowerCase();
    if (ctype === 'video') {
      bindVideo(
        document.getElementById('mvVideo'),
        typeof cfg.onYoutubeReady === 'function' ? cfg.onYoutubeReady : null
      );
    } else if (ctype === 'audio') {
      bindAudio(document.getElementById('mvAudio'));
    }

    global.addEventListener('beforeunload', function() {
      var local = readLocal();
      if (local) {
        saveToServer(local);
        return;
      }
      if (global.ModulePdfViewer && typeof global.ModulePdfViewer.getCurrentPage === 'function') {
        var page = global.ModulePdfViewer.getCurrentPage();
        if (page > 0) {
          var type = (cfg.contentType || 'pdf').toLowerCase();
          if (type === 'slides' || type === 'pdf') {
            saveToServer(payloadFromPosition(type === 'slides' ? 'slides' : 'pdf', page));
          }
        }
      }
    });
  }

  function getMergedStartPage(fallback) {
    var merged = mergeState(cfg.serverState, readLocal(), cfg.queryHints || null);
    var page = parseInt(merged.position, 10) || 0;
    if (page > 0) return page;
    return Math.max(1, parseInt(fallback, 10) || 1);
  }

  global.LMS_RESUME = {
    init: init,
    bindVideo: bindVideo,
    bindAudio: bindAudio,
    saveNow: function(type, position) {
      scheduleSave(payloadFromPosition(type, position));
      saveToServer(payloadFromPosition(type, position));
    },
    getMerged: function() {
      return mergeState(cfg.serverState, readLocal(), cfg.queryHints || null);
    },
    getMergedStartPage: function(fallback) {
      return getMergedStartPage(fallback);
    },
  };
})(window);
