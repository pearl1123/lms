/**
 * Learning Notes — module sidebar workspace
 */
(function(global) {
  'use strict';

  var cfg = global.LN_NOTES_CONFIG || {};
  if (!cfg.enabled) return;

  var state = {
    notes: [],
    listFilter: 'all',
    editingId: 0,
    draftTimer: null,
  };

  function $(id) { return document.getElementById(id); }

  function draftKey() {
    return 'ln_draft_u' + (cfg.userId || 0) + '_c' + (cfg.courseId || 0) + '_m' + (cfg.moduleId || 0);
  }

  function saveDraft() {
    try {
      var payload = {
        title: ($('lnNoteTitle') && $('lnNoteTitle').value) || '',
        content: ($('lnNoteContent') && $('lnNoteContent').value) || '',
        tags: ($('lnNoteTags') && $('lnNoteTags').value) || '',
        color: ($('lnColorLabel') && $('lnColorLabel').value) || '',
        updated: new Date().toISOString(),
      };
      if (!payload.content && !payload.title) {
        global.localStorage.removeItem(draftKey());
        return;
      }
      global.localStorage.setItem(draftKey(), JSON.stringify(payload));
    } catch (e) { /* quota */ }
  }

  function restoreDraft() {
    try {
      var raw = global.localStorage.getItem(draftKey());
      if (!raw) return;
      var d = JSON.parse(raw);
      if ($('lnNoteTitle') && d.title) $('lnNoteTitle').value = d.title;
      if ($('lnNoteContent') && d.content) $('lnNoteContent').value = d.content;
      if ($('lnNoteTags') && d.tags) $('lnNoteTags').value = d.tags;
      if (d.color) setColor(d.color);
    } catch (e) { /* ignore */ }
  }

  function clearDraft() {
    try { global.localStorage.removeItem(draftKey()); } catch (e) {}
  }

  function formatTimestamp(sec) {
    sec = Math.max(0, parseInt(sec, 10) || 0);
    var h = Math.floor(sec / 3600);
    var m = Math.floor((sec % 3600) / 60);
    var s = sec % 60;
    if (h > 0) return h + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
  }

  function getPdfPage() {
    var frame = document.getElementById('mvPdfFrame') || document.getElementById('mvSlidesFrame');
    if (frame && frame.src) {
      var m = String(frame.src).match(/[#&]page=(\d+)/i);
      if (m) return parseInt(m[1], 10);
    }
    if (global.LMS_RESUME && typeof global.LMS_RESUME.getMerged === 'function') {
      var merged = global.LMS_RESUME.getMerged();
      if (merged && merged.type === 'pdf' && merged.position > 0) {
        return Math.max(1, parseInt(merged.position, 10));
      }
    }
    return 0;
  }

  function getSlideNumber() {
    var wrap = document.getElementById('mvSlidesViewer');
    if (wrap) {
      var cards = wrap.querySelectorAll('.mv-slide-card');
      if (cards.length) {
        var top = wrap.scrollTop || 0;
        var idx = 0;
        for (var i = 0; i < cards.length; i++) {
          if (cards[i].offsetTop <= top + 40) idx = i;
        }
        return idx + 1;
      }
    }
    if (global.LMS_RESUME && typeof global.LMS_RESUME.getMerged === 'function') {
      var merged = global.LMS_RESUME.getMerged();
      if (merged && merged.type === 'slides' && merged.position > 0) {
        return Math.max(1, parseInt(merged.position, 10));
      }
    }
    return 0;
  }

  function captureContext() {
    var type = (cfg.contentType || 'general').toLowerCase();
    var hint = '';
    var ts = 0;
    var page = 0;
    var slide = 0;

    if (type === 'video') {
      if (typeof global.mvGetYoutubeSeconds === 'function') {
        ts = global.mvGetYoutubeSeconds();
      }
      if (ts < 1) {
        var v = document.getElementById('mvVideo');
        if (v && v.currentTime >= 0) ts = Math.floor(v.currentTime);
      }
      if (ts > 0) hint = 'Video at ' + formatTimestamp(ts);
    } else if (type === 'audio') {
      var a = document.getElementById('mvAudio');
      if (a && a.currentTime >= 0) ts = Math.floor(a.currentTime);
      if (ts > 0) hint = 'Audio at ' + formatTimestamp(ts);
    } else if (type === 'pdf') {
      page = getPdfPage();
      if (page > 0) hint = 'Page ' + page;
    } else if (type === 'slides') {
      slide = getSlideNumber();
      if (slide > 0) hint = 'Slide ' + slide;
    }

    if ($('lnTimestampSeconds')) $('lnTimestampSeconds').value = ts > 0 ? String(ts) : '';
    if ($('lnPdfPage')) $('lnPdfPage').value = page > 0 ? String(page) : '';
    if ($('lnSlideNumber')) $('lnSlideNumber').value = slide > 0 ? String(slide) : '';
    if ($('lnContextHint')) {
      $('lnContextHint').textContent = hint || 'General note (no position captured)';
    }

    return { type: type, timestamp_seconds: ts, pdf_page: page, slide_number: slide, hint: hint };
  }

  function apiUrl(path) {
    var base = (cfg.apiBase || '').replace(/\/$/, '');
    return base + '/' + path.replace(/^\//, '');
  }

  function apiFetch(path, options) {
    options = options || {};
    var headers = options.headers || {};
    headers['X-Requested-With'] = 'XMLHttpRequest';
    if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(options.body);
    }
    if (cfg.csrfName && cfg.csrfHash) {
      headers[cfg.csrfName] = cfg.csrfHash;
    }
    options.headers = headers;
    options.credentials = 'same-origin';
    return fetch(apiUrl(path), options).then(function(r) {
      return r.json().catch(function() {
        return { success: false, message: 'Invalid response' };
      });
    });
  }

  function loadNotes() {
    var q = ($('lnSearch') && $('lnSearch').value) || '';
    var url = 'api_list?course_id=' + encodeURIComponent(cfg.courseId) +
      '&module_id=' + encodeURIComponent(cfg.moduleId) +
      '&q=' + encodeURIComponent(q);
    apiFetch(url).then(function(res) {
      if (!res.success) {
        renderListError(res.message || 'Could not load notes');
        return;
      }
      state.notes = res.notes || [];
      renderNotesList();
    });
  }

  function renderListError(msg) {
    var list = $('lnNotesList');
    if (list) list.innerHTML = '<p class="ln-empty">' + escapeHtml(msg) + '</p>';
  }

  function escapeHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function filteredNotes() {
    return state.notes.filter(function(n) {
      if (state.listFilter === 'pinned' && !n.is_pinned) return false;
      if (state.listFilter === 'favorites' && !n.is_favorite) return false;
      return true;
    });
  }

  function renderNotesList() {
    var list = $('lnNotesList');
    if (!list) return;
    var items = filteredNotes();
    if (!items.length) {
      list.innerHTML = '<p class="ln-empty">No notes yet. Capture your first insight above.</p>';
      return;
    }
    list.innerHTML = items.map(function(n) {
      var colorCls = n.color_label ? ' ln-note-item--' + n.color_label : '';
      var meta = [];
      if (n.context_label) meta.push(escapeHtml(n.context_label));
      meta.push(escapeHtml(n.created_ago || ''));
      return (
        '<article class="ln-note-item' + colorCls + '" data-id="' + n.id + '">' +
          '<h4 class="ln-note-item-title">' + escapeHtml(n.note_title || 'Untitled') + '</h4>' +
          '<p class="ln-note-item-preview">' + escapeHtml(n.preview || '') + '</p>' +
          '<p class="ln-note-item-meta">' + meta.join(' · ') + '</p>' +
          '<div class="ln-note-item-actions">' +
            (n.return_url ? '<a href="' + escapeHtml(n.return_url) + '" class="ln-note-icon-btn">Return to content</a>' : '') +
            '<button type="button" class="ln-note-icon-btn js-ln-pin' + (n.is_pinned ? ' is-on' : '') + '" data-id="' + n.id + '">Pin</button>' +
            '<button type="button" class="ln-note-icon-btn js-ln-fav' + (n.is_favorite ? ' is-on' : '') + '" data-id="' + n.id + '">★</button>' +
            '<button type="button" class="ln-note-icon-btn js-ln-edit" data-id="' + n.id + '">Edit</button>' +
            '<button type="button" class="ln-note-icon-btn js-ln-del" data-id="' + n.id + '">Delete</button>' +
          '</div>' +
        '</article>'
      );
    }).join('');

    list.querySelectorAll('.js-ln-pin').forEach(function(btn) {
      btn.addEventListener('click', function() {
        togglePin(parseInt(btn.getAttribute('data-id'), 10));
      });
    });
    list.querySelectorAll('.js-ln-fav').forEach(function(btn) {
      btn.addEventListener('click', function() {
        toggleFavorite(parseInt(btn.getAttribute('data-id'), 10));
      });
    });
    list.querySelectorAll('.js-ln-edit').forEach(function(btn) {
      btn.addEventListener('click', function() {
        editNote(parseInt(btn.getAttribute('data-id'), 10));
      });
    });
    list.querySelectorAll('.js-ln-del').forEach(function(btn) {
      btn.addEventListener('click', function() {
        deleteNote(parseInt(btn.getAttribute('data-id'), 10));
      });
    });
  }

  function resetForm() {
    state.editingId = 0;
    if ($('lnNoteForm')) $('lnNoteForm').reset();
    if ($('lnContentType')) $('lnContentType').value = cfg.contentType || 'general';
    setColor('');
    captureContext();
    clearDraft();
  }

  function setColor(color) {
    if ($('lnColorLabel')) $('lnColorLabel').value = color || '';
    document.querySelectorAll('.ln-color-btn').forEach(function(btn) {
      btn.classList.toggle('is-active', btn.getAttribute('data-color') === (color || ''));
    });
  }

  function editNote(id) {
    var n = state.notes.find(function(x) { return x.id === id; });
    if (!n) return;
    state.editingId = id;
    if ($('lnNoteTitle')) $('lnNoteTitle').value = n.note_title || '';
    if ($('lnNoteContent')) $('lnNoteContent').value = n.note_content || '';
    if ($('lnNoteTags')) $('lnNoteTags').value = (n.tags || []).join(', ');
    setColor(n.color_label || '');
    if ($('lnSaveBtn')) $('lnSaveBtn').textContent = 'Update Note';
    captureContext();
  }

  function saveNote(ev) {
    if (ev) ev.preventDefault();
    captureContext();
    var title = ($('lnNoteTitle') && $('lnNoteTitle').value) || '';
    var content = ($('lnNoteContent') && $('lnNoteContent').value) || '';
    if (!content.trim()) return;

    var ctx = captureContext();
    var body = {
      course_id: cfg.courseId,
      module_id: cfg.moduleId,
      note_title: title,
      note_content: content,
      content_type: ctx.type,
      timestamp_seconds: ctx.timestamp_seconds || null,
      pdf_page: ctx.pdf_page || null,
      slide_number: ctx.slide_number || null,
      tags: ($('lnNoteTags') && $('lnNoteTags').value) || '',
      color_label: ($('lnColorLabel') && $('lnColorLabel').value) || null,
    };

    var path = state.editingId > 0 ? 'api_update/' + state.editingId : 'api_save';
    var method = 'POST';

    apiFetch(path, { method: method, body: body }).then(function(res) {
      if (!res.success) {
        alert(res.message || 'Save failed');
        return;
      }
      resetForm();
      if ($('lnSaveBtn')) $('lnSaveBtn').textContent = 'Save Note';
      loadNotes();
    });
  }

  function deleteNote(id) {
    if (!global.confirm('Delete this note?')) return;
    apiFetch('api_delete/' + id, { method: 'POST', body: {} }).then(function(res) {
      if (res.success) loadNotes();
    });
  }

  function togglePin(id) {
    apiFetch('api_toggle_pin/' + id, { method: 'POST', body: {} }).then(function(res) {
      if (res.success) loadNotes();
    });
  }

  function toggleFavorite(id) {
    apiFetch('api_toggle_favorite/' + id, { method: 'POST', body: {} }).then(function(res) {
      if (res.success) loadNotes();
    });
  }

  function openPanel() {
    var root = $('lnNotesRoot');
    var tab = $('lnNotesTab');
    if (root) root.classList.add('is-open');
    if (tab) {
      tab.classList.add('is-open');
      tab.setAttribute('aria-expanded', 'true');
    }
    document.body.style.overflow = 'hidden';
    captureContext();
    loadNotes();
  }

  function closePanel() {
    var root = $('lnNotesRoot');
    var tab = $('lnNotesTab');
    if (root) root.classList.remove('is-open');
    if (tab) {
      tab.classList.remove('is-open');
      tab.setAttribute('aria-expanded', 'false');
    }
    document.body.style.overflow = '';
    saveDraft();
  }

  function bindEvents() {
    var tab = $('lnNotesTab');
    var close = $('lnNotesClose');
    var backdrop = $('lnNotesBackdrop');
    var form = $('lnNoteForm');
    var cancel = $('lnCancelBtn');
    var search = $('lnSearch');

    if (tab) tab.addEventListener('click', function() {
      var root = $('lnNotesRoot');
      if (root && root.classList.contains('is-open')) closePanel();
      else openPanel();
    });
    if (close) close.addEventListener('click', closePanel);
    if (backdrop) backdrop.addEventListener('click', closePanel);
    if (form) form.addEventListener('submit', saveNote);
    if (cancel) cancel.addEventListener('click', resetForm);

    if (search) {
      var st;
      search.addEventListener('input', function() {
        clearTimeout(st);
        st = setTimeout(loadNotes, 280);
      });
    }

    document.querySelectorAll('.ln-filter-chip').forEach(function(chip) {
      chip.addEventListener('click', function() {
        document.querySelectorAll('.ln-filter-chip').forEach(function(c) {
          c.classList.remove('is-active');
        });
        chip.classList.add('is-active');
        state.listFilter = chip.getAttribute('data-filter') || 'all';
        renderNotesList();
      });
    });

    document.querySelectorAll('.ln-color-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        setColor(btn.getAttribute('data-color') || '');
        saveDraft();
      });
    });

    ['lnNoteTitle', 'lnNoteContent', 'lnNoteTags'].forEach(function(id) {
      var el = $(id);
      if (el) {
        el.addEventListener('input', function() {
          clearTimeout(state.draftTimer);
          state.draftTimer = setTimeout(saveDraft, 400);
        });
      }
    });

    global.addEventListener('beforeunload', saveDraft);
    document.addEventListener('keydown', function(ev) {
      if (ev.key === 'Escape') closePanel();
    });
  }

  function init() {
    restoreDraft();
    captureContext();
    bindEvents();
    setInterval(captureContext, 5000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  global.LN_NOTES = { open: openPanel, close: closePanel, captureContext: captureContext };
})(window);
