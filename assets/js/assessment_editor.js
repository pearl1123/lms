(function() {
  'use strict';

  window.asxEditorSetAutosave = function(state) {
    var el = document.getElementById('asxAutosaveIndicator');
    if (!el) return;
    el.classList.remove('is-pending');
    var text = el.querySelector('.asx-editor-autosave-text');
    if (state === 'pending') {
      el.classList.add('is-pending');
      if (text) text.textContent = 'Unsaved changes';
    } else if (state === 'saving') {
      if (text) text.textContent = 'Saving…';
    } else {
      if (text) text.textContent = 'All changes saved';
    }
  };

  function initEditorNav() {
    var root = document.getElementById('asxEditorRoot');
    if (!root) return;

    var navItems = root.querySelectorAll('[data-asx-nav]');
    var sections = root.querySelectorAll('[data-asx-section]');

    function showSection(id) {
      navItems.forEach(function(btn) {
        btn.classList.toggle('is-active', btn.getAttribute('data-asx-nav') === id);
      });
      sections.forEach(function(sec) {
        var on = sec.getAttribute('data-asx-section') === id;
        sec.classList.toggle('is-active', on);
        if (on) {
          sec.setAttribute('aria-hidden', 'false');
        } else {
          sec.setAttribute('aria-hidden', 'true');
        }
      });
      if (id === 'content' && typeof window.cpwFocusWorkspace === 'function') {
        window.cpwFocusWorkspace();
      }
    }

    navItems.forEach(function(btn) {
      btn.addEventListener('click', function() {
        showSection(btn.getAttribute('data-asx-nav') || 'overview');
      });
    });

    var titleInput = document.getElementById('asxMetaTitle');
    var titleDisplay = document.getElementById('asxEditorTitleDisplay');
    if (titleInput && titleDisplay) {
      titleInput.addEventListener('input', function() {
        titleDisplay.textContent = titleInput.value || 'Assessment';
        window.asxEditorSetAutosave('pending');
      });
    }

    var metaForm = document.getElementById('assessmentMetaForm');
    if (metaForm) {
      metaForm.addEventListener('input', function() {
        window.asxEditorSetAutosave('pending');
      });
      metaForm.addEventListener('submit', function() {
        window.asxEditorSetAutosave('saving');
      });
    }

    window.asxEditorShowSection = showSection;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEditorNav);
  } else {
    initEditorNav();
  }
})();
