/**
 * Paginated PDF viewer (PDF.js) — one page at a time, zoom + rotate.
 */
(function(global) {
  'use strict';

  var ZOOM_MIN = 0.5;
  var ZOOM_MAX = 3;
  var ZOOM_STEP = 0.25;

  var state = {
    pdf: null,
    page: 1,
    total: 0,
    rendering: false,
    pendingPage: null,
    root: null,
    canvas: null,
    wrap: null,
    prevBtn: null,
    nextBtn: null,
    pageInfo: null,
    zoomLabel: null,
    zoom: 1,
    rotation: 0,
    viewedLast: false,
    onPageChange: null,
    onLastPageReached: null,
  };

  function findInRoot(selector) {
    return state.root ? state.root.querySelector(selector) : null;
  }

  function cacheControls() {
    state.prevBtn = findInRoot('#mvPdfPrev, #mvSlidesPdfPrev');
    state.nextBtn = findInRoot('#mvPdfNext, #mvSlidesPdfNext');
    state.pageInfo = findInRoot('#mvPdfPageInfo, #mvSlidesPdfPageInfo');
    state.zoomLabel = findInRoot('[data-pdf-zoom-label]');
  }

  function normalizeRotation(deg) {
    var r = parseInt(deg, 10) || 0;
    r = ((r % 360) + 360) % 360;
    return r;
  }

  function setBusy(busy) {
    if (state.root) {
      state.root.classList.toggle('is-loading', !!busy);
    }
  }

  function updateZoomLabel() {
    if (state.zoomLabel) {
      state.zoomLabel.textContent = Math.round(state.zoom * 100) + '%';
    }
  }

  function updateToolbar() {
    if (!state.prevBtn || !state.nextBtn) cacheControls();
    if (state.prevBtn) state.prevBtn.disabled = state.page <= 1 || state.rendering;
    if (state.nextBtn) state.nextBtn.disabled = state.page >= state.total || state.rendering;
    if (state.pageInfo) {
      state.pageInfo.textContent = state.total > 0
        ? ('Page ' + state.page + ' of ' + state.total)
        : 'Loading…';
    }
    updateZoomLabel();
  }

  function notifyPageChange() {
    if (typeof state.onPageChange === 'function') {
      state.onPageChange(state.page, state.total);
    }
    if (state.page >= state.total && state.total > 0 && !state.viewedLast) {
      state.viewedLast = true;
      if (typeof state.onLastPageReached === 'function') {
        state.onLastPageReached(state.page, state.total);
      }
      try {
        if (global.parent && global.parent !== global) {
          global.parent.postMessage({ type: 'pdf-scrolled-end', page: state.page, total: state.total }, '*');
        }
      } catch (e) { /* ignore */ }
    }
    updateToolbar();
  }

  function getWrapSize() {
    var wrapW = state.wrap ? state.wrap.clientWidth : 800;
    var wrapH = state.wrap ? state.wrap.clientHeight : 600;
    if (wrapW < 32 || wrapH < 32) {
      wrapW = 800;
      wrapH = 600;
    }
    return { width: wrapW, height: wrapH };
  }

  function buildViewport(page) {
    var rotation = normalizeRotation(state.rotation);
    var unit = page.getViewport({ scale: 1, rotation: rotation });
    var wrap = getWrapSize();
    var fitScale = Math.min(
      (wrap.width - 16) / unit.width,
      (wrap.height - 16) / unit.height
    );
    if (!isFinite(fitScale) || fitScale <= 0) fitScale = 1;
    var scale = Math.min(fitScale * state.zoom, 6);
    return page.getViewport({ scale: scale, rotation: rotation });
  }

  function renderPage(num) {
    if (!state.pdf || !state.canvas) return;
    state.rendering = true;
    updateToolbar();

    state.pdf.getPage(num).then(function(page) {
      var viewport = buildViewport(page);
      var ctx = state.canvas.getContext('2d');
      state.canvas.width = Math.floor(viewport.width);
      state.canvas.height = Math.floor(viewport.height);
      state.canvas.style.width = viewport.width + 'px';
      state.canvas.style.height = viewport.height + 'px';

      return page.render({ canvasContext: ctx, viewport: viewport }).promise;
    }).then(function() {
      state.rendering = false;
      state.page = num;
      notifyPageChange();
      if (state.pendingPage && state.pendingPage !== state.page) {
        var p = state.pendingPage;
        state.pendingPage = null;
        renderPage(p);
      }
    }).catch(function() {
      state.rendering = false;
      updateToolbar();
    });
  }

  function refreshView() {
    if (!state.pdf || state.rendering) return;
    renderPage(state.page);
  }

  function goToPage(num) {
    var target = Math.max(1, parseInt(num, 10) || 1);
    if (!state.pdf) {
      state.pendingPage = target;
      return;
    }
    if (state.total > 0) {
      target = Math.min(target, state.total);
    }
    if (state.rendering) {
      state.pendingPage = target;
      return;
    }
    if (target === state.page) {
      refreshView();
      return;
    }
    renderPage(target);
  }

  function step(delta) {
    goToPage(state.page + delta);
  }

  function changeZoom(delta) {
    var next = Math.round((state.zoom + delta) * 100) / 100;
    state.zoom = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX, next));
    refreshView();
  }

  function rotate(delta) {
    state.rotation = normalizeRotation(state.rotation + delta);
    refreshView();
  }

  function resetView() {
    state.zoom = 1;
    state.rotation = 0;
    refreshView();
  }

  function handleToolAction(action) {
    if (state.rendering) return;
    if (action === 'zoom-in') changeZoom(ZOOM_STEP);
    else if (action === 'zoom-out') changeZoom(-ZOOM_STEP);
    else if (action === 'rotate-left') rotate(-90);
    else if (action === 'rotate-right') rotate(90);
    else if (action === 'reset-view') resetView();
  }

  function bindControls() {
    cacheControls();
    if (state.prevBtn) {
      state.prevBtn.addEventListener('click', function() { step(-1); });
    }
    if (state.nextBtn) {
      state.nextBtn.addEventListener('click', function() { step(1); });
    }
    if (state.root) {
      state.root.querySelectorAll('[data-pdf-action]').forEach(function(btn) {
        btn.addEventListener('click', function() {
          handleToolAction(btn.getAttribute('data-pdf-action') || '');
        });
      });
    }
    if (state.wrap) {
      state.wrap.addEventListener('wheel', function(e) {
        if (e.ctrlKey || e.metaKey) {
          e.preventDefault();
          changeZoom(e.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP);
          return;
        }
        if (state.zoom <= 1) {
          e.preventDefault();
        }
      }, { passive: false });
    }
    document.addEventListener('keydown', function(e) {
      if (!state.root || !document.body.contains(state.root)) return;
      if (e.key === 'ArrowLeft') { e.preventDefault(); step(-1); }
      if (e.key === 'ArrowRight') { e.preventDefault(); step(1); }
      if ((e.ctrlKey || e.metaKey) && (e.key === '=' || e.key === '+')) {
        e.preventDefault();
        changeZoom(ZOOM_STEP);
      }
      if ((e.ctrlKey || e.metaKey) && e.key === '-') {
        e.preventDefault();
        changeZoom(-ZOOM_STEP);
      }
    });
    var resizeTimer;
    global.addEventListener('resize', function() {
      if (!state.pdf) return;
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function() {
        refreshView();
      }, 150);
    });
  }

  function init(options) {
    options = options || {};
    var rootId = options.rootId || 'mvPdfViewer';
    state.root = document.getElementById(rootId);
    if (!state.root) return null;

    var pdfUrl = options.pdfUrl || state.root.getAttribute('data-pdf-url') || '';
    if (!pdfUrl) return null;

    state.canvas = state.root.querySelector('canvas');
    state.wrap = state.root.querySelector('.mv-pdf-viewer-canvas-wrap');
    state.prevBtn = null;
    state.nextBtn = null;
    state.pageInfo = null;
    state.zoomLabel = null;
    state.zoom = 1;
    state.rotation = 0;
    cacheControls();
    state.onPageChange = options.onPageChange || null;
    state.onLastPageReached = options.onLastPageReached || null;
    state.viewedLast = false;
    state.page = Math.max(1, parseInt(options.startPage, 10) || 1);

    var lib = global.pdfjsLib || global['pdfjs-dist/build/pdf'];
    if (!lib || typeof lib.getDocument !== 'function') {
      console.error('[ModulePdfViewer] PDF.js not loaded');
      return null;
    }
    if (options.workerSrc) {
      lib.GlobalWorkerOptions.workerSrc = options.workerSrc;
    } else if (!lib.GlobalWorkerOptions.workerSrc) {
      lib.GlobalWorkerOptions.workerSrc = (function() {
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
          var src = scripts[i].src || '';
          if (src.indexOf('pdf.min.js') !== -1) {
            return src.replace(/pdf\.min\.js(\?.*)?$/, 'pdf.worker.min.js');
          }
        }
        return '';
      })();
    }

    bindControls();
    setBusy(true);

    lib.getDocument({ url: pdfUrl, withCredentials: true }).promise.then(function(pdf) {
      state.pdf = pdf;
      state.total = pdf.numPages || 0;
      setBusy(false);
      if (state.page > state.total) state.page = state.total;
      var initialPage = state.pendingPage || state.page;
      state.pendingPage = null;
      if (state.total === 1) state.viewedLast = false;
      goToPage(initialPage);
      if (state.total === 1) {
        state.viewedLast = true;
        if (typeof state.onLastPageReached === 'function') {
          state.onLastPageReached(1, 1);
        }
      }
    }).catch(function(err) {
      setBusy(false);
      console.error('[ModulePdfViewer] load failed', err);
      if (!state.pageInfo) cacheControls();
      if (state.pageInfo) state.pageInfo.textContent = 'Could not load document';
      if (typeof options.onError === 'function') {
        options.onError(err);
      }
    });

    return {
      goToPage: goToPage,
      getCurrentPage: function() { return state.page; },
      getTotalPages: function() { return state.total; },
      hasReachedLastPage: function() { return state.viewedLast; },
    };
  }

  global.ModulePdfViewer = {
    init: init,
    goToPage: function(num) {
      if (state.pdf) goToPage(num);
      else state.pendingPage = Math.max(1, parseInt(num, 10) || 1);
    },
    getCurrentPage: function() { return state.page; },
    getTotalPages: function() { return state.total; },
    hasReachedLastPage: function() { return state.viewedLast; },
  };
})(window);
