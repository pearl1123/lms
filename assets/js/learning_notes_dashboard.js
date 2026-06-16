/**
 * My Notes dashboard — open note detail + export (TXT/PDF).
 */
(function () {
  'use strict';

  var notes = window.LN_DASH_NOTES || [];
  var exportBase = window.LN_DASH_EXPORT_BASE || '';
  var exportParams = window.LN_DASH_EXPORT_PARAMS || {};
  var notesById = {};
  var activeNoteId = 0;

  notes.forEach(function (n) {
    notesById[n.id] = n;
  });

  var detail = document.getElementById('lnDashDetail');
  if (!detail) return;

  var backdrop = detail.querySelector('.ln-dash-detail-backdrop');
  var closeBtn = document.getElementById('lnDashDetailClose');
  var exportOne = document.getElementById('lnDashExportOne');
  var exportOneFormat = document.getElementById('lnExportOneFormat');
  var exportAllBtn = document.getElementById('lnExportAllBtn');
  var exportAllFormat = document.getElementById('lnExportAllFormat');
  var titleEl = document.getElementById('lnDashDetailTitle');
  var metaEl = document.getElementById('lnDashDetailMeta');
  var bodyEl = document.getElementById('lnDashDetailBody');
  var returnEl = document.getElementById('lnDashDetailReturn');

  function buildExportUrl(noteId, format) {
    var url = exportBase + (noteId ? '/' + noteId : '');
    var qs = new URLSearchParams();

    Object.keys(exportParams).forEach(function (key) {
      if (exportParams[key] !== '' && exportParams[key] != null) {
        qs.set(key, exportParams[key]);
      }
    });
    qs.set('format', format || 'txt');

    var query = qs.toString();
    return query ? url + '?' + query : url;
  }

  function updateSingleExportLink() {
    if (!exportOne || !activeNoteId) return;
    var format = exportOneFormat ? exportOneFormat.value : 'txt';
    exportOne.href = buildExportUrl(activeNoteId, format);
  }

  function formatDate(iso) {
    if (!iso) return '';
    var d = new Date(iso.replace(' ', 'T'));
    if (isNaN(d.getTime())) return iso;
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function openNote(id) {
    var note = notesById[id];
    if (!note) return;

    activeNoteId = id;

    if (titleEl) titleEl.textContent = note.note_title || 'Untitled note';

    var meta = [];
    if (note.course_title) meta.push(note.course_title);
    if (note.module_title) meta.push(note.module_title);
    if (note.context_label) meta.push(note.context_label);
    if (note.tags && note.tags.length) meta.push('Tags: ' + note.tags.join(', '));
    if (note.updated_at) meta.push('Updated ' + formatDate(note.updated_at));
    if (metaEl) metaEl.textContent = meta.join(' · ');

    if (bodyEl) bodyEl.textContent = note.note_content || '';

    updateSingleExportLink();

    if (returnEl) {
      if (note.return_url) {
        returnEl.href = note.return_url;
        returnEl.hidden = false;
      } else {
        returnEl.hidden = true;
      }
    }

    detail.hidden = false;
    detail.setAttribute('aria-hidden', 'false');
    document.body.classList.add('ln-dash-detail-open');
    if (closeBtn) closeBtn.focus();
  }

  function closeDetail() {
    detail.hidden = true;
    detail.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('ln-dash-detail-open');
    activeNoteId = 0;
  }

  document.querySelectorAll('.ln-note-card[data-note-id]').forEach(function (card) {
    card.addEventListener('click', function (e) {
      if (e.target.closest('a')) return;
      openNote(parseInt(card.getAttribute('data-note-id'), 10));
    });
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openNote(parseInt(card.getAttribute('data-note-id'), 10));
      }
    });
  });

  if (exportOneFormat) {
    exportOneFormat.addEventListener('change', updateSingleExportLink);
  }

  if (exportAllBtn) {
    exportAllBtn.addEventListener('click', function () {
      var format = exportAllFormat ? exportAllFormat.value : 'txt';
      window.location.href = buildExportUrl(0, format);
    });
  }

  if (backdrop) backdrop.addEventListener('click', closeDetail);
  if (closeBtn) closeBtn.addEventListener('click', closeDetail);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !detail.hidden) closeDetail();
  });
})();
