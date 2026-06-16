(function() {
  'use strict';

  function cpwToast(type, msg) {
    if (window.KA && typeof window.KA.toast === 'function') {
      window.KA.toast(type, msg);
    } else if (window.KA_SWAL) {
      if (type === 'error' && typeof KA_SWAL.SwalError === 'function') {
        KA_SWAL.SwalError({ title: msg });
      } else if (typeof KA_SWAL.SwalWarning === 'function') {
        KA_SWAL.SwalWarning({ title: msg });
      }
    }
  }

  function parseJsonResponse(r) {
    return r.text().then(function(text) {
      var raw = text == null ? '' : String(text);
      var trimmed = raw.trim();
      if (trimmed === '') {
        return {
          success: false,
          ok: false,
          message: 'Empty server response. Please refresh and try again.',
          _parseError: true,
        };
      }
      try {
        var res = JSON.parse(trimmed);
        if (typeof res.success === 'undefined' && typeof res.ok !== 'undefined') {
          res.success = !!res.ok;
        }
        if (typeof res.ok === 'undefined' && typeof res.success !== 'undefined') {
          res.ok = !!res.success;
        }
        return res;
      } catch (e) {
        if (typeof console !== 'undefined' && console.error) {
          console.error('Invalid JSON response:', trimmed.slice(0, 300), e);
        }
        return {
          success: false,
          ok: false,
          message: 'Could not read the server response. Please refresh and try again.',
          _parseError: true,
          _raw: trimmed.slice(0, 500),
        };
      }
    });
  }

  function responseOk(res) {
    return !!(res && (res.success || res.ok));
  }

  function cpwErrorMessage(res, fallback) {
    if (!res) {
      return fallback || 'Save failed.';
    }
    if (res.errors) {
      if (res.errors.whole_video_duration_seconds) {
        return res.errors.whole_video_duration_seconds;
      }
      if (res.errors.trigger_seconds) {
        return res.errors.trigger_seconds;
      }
    }
    return res.message || fallback || 'Save failed.';
  }

  function cpwShowError(res, fallback) {
    var msg = cpwErrorMessage(res, fallback);
    if (window.KA_SWAL && typeof KA_SWAL.SwalError === 'function') {
      KA_SWAL.SwalError({ title: msg });
    } else {
      cpwToast('error', msg);
    }
  }

  function cpwDurationStorageKey(moduleId) {
    return 'lms_cpw_whole_video_duration_' + (parseInt(moduleId, 10) || 0);
  }

  function getVideoDurationSeconds(C) {
    C = C || {};
    var el = document.getElementById('cpwSharedDuration')
      || document.getElementById('cpwAutoDuration');
    if (el && String(el.value).trim() !== '') {
      var fromInput = parseInt(String(el.value).trim(), 10);
      if (!isNaN(fromInput) && fromInput > 0) {
        return fromInput;
      }
    }
    var suggested = parseInt(C.suggestedVideoDurationSeconds, 10);
    if (!isNaN(suggested) && suggested > 0) {
      return suggested;
    }
    var mid = parseInt(C.moduleId, 10) || 0;
    if (mid > 0) {
      try {
        var stored = localStorage.getItem(cpwDurationStorageKey(mid));
        if (stored) {
          var fromStore = parseInt(stored, 10);
          if (!isNaN(fromStore) && fromStore > 0) {
            return fromStore;
          }
        }
      } catch (ignore) {}
    }
    var maxTs = parseInt(C.maxTriggerSeconds, 10) || 0;
    document.querySelectorAll('.cpw-meta-form input[name="trigger_seconds"]').forEach(function(inp) {
      var t = parseInt(String(inp.value).trim(), 10);
      if (!isNaN(t) && t > maxTs) {
        maxTs = t;
      }
    });
    return maxTs > 0 ? maxTs : 0;
  }

  function persistVideoDurationSeconds(moduleId, seconds) {
    var sec = parseInt(seconds, 10);
    if (isNaN(sec) || sec < 1) {
      return;
    }
    var el = document.getElementById('cpwSharedDuration');
    if (el && !el.value) {
      el.value = String(sec);
    }
    var mid = parseInt(moduleId, 10) || 0;
    if (mid > 0) {
      try {
        localStorage.setItem(cpwDurationStorageKey(mid), String(sec));
      } catch (ignore) {}
    }
  }

  function appendDurationFields(body, vd, moduleId) {
    if (!(vd > 0)) {
      return body;
    }
    body += '&whole_video_duration_seconds=' + encodeURIComponent(vd);
    body += '&video_duration_seconds=' + encodeURIComponent(vd);
    if (moduleId > 0) {
      body += '&module_id=' + encodeURIComponent(moduleId);
    }
    return body;
  }

  function escHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function resolveCheckpointContext() {
    var assessments = window.APP_CONTEXT && window.APP_CONTEXT.assessments;
    if (!assessments) {
      return null;
    }
    var ws = assessments.checkpointWorkspace;
    if (ws && typeof ws === 'object') {
      return ws;
    }
    var edit = assessments.edit;
    if (edit && edit.useWorkspace) {
      return {
        activeId: edit.assessmentId,
        moduleId: 0,
        courseId: 0,
        suggestedVideoDurationSeconds: 0,
        maxTriggerSeconds: 0,
        csrfFieldName: edit.csrfFieldName || '',
        csrfHash: edit.csrfHash || '',
        saveQuestionUrl: edit.saveQuestionUrl || '',
        deleteQuestionUrl: edit.deleteQuestionUrl || '',
        saveMetaUrl: '',
        autoGenerateUrl: '',
        generated: false,
      };
    }
    return null;
  }

  function initCheckpointWorkspace() {
    var root = document.getElementById('asxEditorRoot');
    var workspace = document.getElementById('cpwWorkspace') || document.getElementById('cpwEmptyState');
    if (!root || !workspace) {
      return;
    }
    if (root.getAttribute('data-cpw-init') === '1') {
      return;
    }

    var C = resolveCheckpointContext();
    if (!C || !C.saveQuestionUrl) {
      return;
    }

    var CSRF_NAME = C.csrfFieldName || '';
    var CSRF_HASH = C.csrfHash || '';
    var SAVE_Q_URL = C.saveQuestionUrl || '';
    var DEL_Q_URL = C.deleteQuestionUrl || '';
    var SAVE_META_URL = C.saveMetaUrl || '';
    var AUTO_URL = C.autoGenerateUrl || '';
    var MODULE_ID = parseInt(C.moduleId, 10) || 0;

    if (C.suggestedVideoDurationSeconds > 0) {
      persistVideoDurationSeconds(MODULE_ID, C.suggestedVideoDurationSeconds);
    }
    var sharedDurInput = document.getElementById('cpwSharedDuration');
    if (sharedDurInput) {
      sharedDurInput.addEventListener('change', function() {
        persistVideoDurationSeconds(MODULE_ID, sharedDurInput.value);
      });
      sharedDurInput.addEventListener('input', function() {
        persistVideoDurationSeconds(MODULE_ID, sharedDurInput.value);
      });
    }

    function csrfPrefix() {
      if (!String(CSRF_NAME).trim()) return '';
      return String(CSRF_NAME) + '=' + encodeURIComponent(String(CSRF_HASH || '')) + '&';
    }

    function setMsg(el, text, kind) {
      if (!el) return;
      el.textContent = text || '';
      el.classList.remove('is-ok', 'is-err');
      if (kind === 'ok') el.classList.add('is-ok');
      if (kind === 'err') el.classList.add('is-err');
    }

    function markUnsaved(show) {
      if (show && typeof window.asxEditorSetAutosave === 'function') {
        window.asxEditorSetAutosave('pending');
      } else if (!show && typeof window.asxEditorSetAutosave === 'function') {
        window.asxEditorSetAutosave('saved');
      }
    }

    function switchTab(aid) {
      aid = parseInt(aid, 10) || 0;
      document.querySelectorAll('[data-cpw-tab]').forEach(function(tab) {
        var id = parseInt(tab.getAttribute('data-cpw-tab'), 10);
        var on = id === aid;
        tab.classList.toggle('is-active', on);
        tab.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      document.querySelectorAll('[data-cpw-nav]').forEach(function(nav) {
        nav.classList.toggle('is-active', parseInt(nav.getAttribute('data-cpw-nav'), 10) === aid);
      });
      document.querySelectorAll('.cpw-block, .cpw-panel').forEach(function(panel) {
        var id = parseInt(panel.getAttribute('data-assessment-id'), 10);
        var on = id === aid;
        panel.classList.toggle('is-active', on);
        panel.setAttribute('aria-hidden', on ? 'false' : 'true');
      });
      var activePanel = document.getElementById('cpw-panel-' + aid);
      if (activePanel && activePanel.scrollIntoView) {
        activePanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
      if (window.history && window.history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', String(aid));
        window.history.replaceState({}, '', url.toString());
      }
    }

    window.cpwSwitchTab = switchTab;
    window.cpwFocusWorkspace = function() {
      if (typeof window.asxEditorShowSection === 'function') {
        window.asxEditorShowSection('content');
      }
      var first = document.querySelector('[data-cpw-tab]');
      if (first) switchTab(first.getAttribute('data-cpw-tab'));
    };

    document.querySelectorAll('[data-cpw-tab]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        switchTab(btn.getAttribute('data-cpw-tab'));
      });
    });

    document.querySelectorAll('[data-cpw-nav]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        switchTab(btn.getAttribute('data-cpw-nav'));
        if (typeof window.asxEditorShowSection === 'function') {
          window.asxEditorShowSection('content');
        }
      });
    });

    var tabParam = new URLSearchParams(window.location.search).get('tab');
    if (tabParam) {
      switchTab(tabParam);
    } else {
      var activeTab = document.querySelector('[data-cpw-tab].is-active');
      if (activeTab) switchTab(activeTab.getAttribute('data-cpw-tab'));
    }

    if (C.generated) {
      if (typeof window.asxEditorShowSection === 'function') {
        window.asxEditorShowSection('content');
      }
      document.querySelectorAll('.cpw-seg-tab, .cpw-block').forEach(function(el) {
        el.classList.add('cpw-glow-in');
      });
      var focusTabId = tabParam;
      if (!focusTabId) {
        var firstTab = document.querySelector('[data-cpw-tab]');
        focusTabId = firstTab ? firstTab.getAttribute('data-cpw-tab') : '';
      }
      if (focusTabId) {
        switchTab(focusTabId);
        setTimeout(function() {
          var panel = document.getElementById('cpw-panel-' + focusTabId);
          if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 200);
      }
      cpwToast('success', '3 checkpoints created. Configure each segment below.');
      if (window.history && window.history.replaceState) {
        var u = new URL(window.location.href);
        u.searchParams.delete('generated');
        window.history.replaceState({}, '', u.toString());
      }
    }

    function buildChoiceRow(aid, text, isCorrect) {
      var row = document.createElement('div');
      row.className = 'cpw-choice-row';
      var mark = document.createElement('button');
      mark.type = 'button';
      mark.className = 'cpw-choice-mark' + (isCorrect ? ' is-correct' : '');
      mark.title = 'Mark correct';
      mark.textContent = '✓';
      var input = document.createElement('input');
      input.type = 'text';
      input.className = 'cpw-input';
      input.placeholder = 'Choice text';
      input.value = text || '';
      var remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'cpw-choice-remove';
      remove.title = 'Remove';
      remove.textContent = '×';
      row.appendChild(mark);
      row.appendChild(input);
      row.appendChild(remove);
      mark.addEventListener('click', function() {
        row.parentElement.querySelectorAll('.cpw-choice-mark').forEach(function(m) {
          m.classList.remove('is-correct');
        });
        mark.classList.add('is-correct');
        markUnsaved(true);
      });
      input.addEventListener('input', function() { markUnsaved(true); });
      remove.addEventListener('click', function() {
        if (row.parentElement.querySelectorAll('.cpw-choice-row').length > 2) {
          row.remove();
          markUnsaved(true);
        }
      });
      return row;
    }

    function initChoicesForPanel(aid) {
      var list = document.querySelector('[data-cpw-choices="' + aid + '"]');
      var dataEl = document.getElementById('cpw-choices-data-' + aid);
      if (!list) return;
      if (list.querySelector('.cpw-choice-row')) {
        return;
      }
      list.innerHTML = '';
      var choices = [];
      if (dataEl && dataEl.textContent) {
        try {
          choices = JSON.parse(dataEl.textContent);
        } catch (e) {
          choices = [];
        }
      }
      if (!choices.length) {
        list.appendChild(buildChoiceRow(aid, '', true));
        list.appendChild(buildChoiceRow(aid, '', false));
        return;
      }
      choices.forEach(function(c) {
        list.appendChild(buildChoiceRow(aid, c.text || '', c.is_correct === 1 || c.is_correct === true || c.is_correct === '1'));
      });
    }

    document.querySelectorAll('[data-cpw-choices]').forEach(function(el) {
      var aid = el.getAttribute('data-cpw-choices');
      initChoicesForPanel(aid);
    });

    function addChoiceForAssessment(aid) {
      if (typeof window.cpwAddChoice === 'function') {
        window.cpwAddChoice(aid);
        markUnsaved(true);
        return;
      }
      aid = String(aid || '').trim();
      if (!aid) return;
      var list = document.querySelector('[data-cpw-choices="' + aid + '"]');
      if (!list) return;
      list.appendChild(buildChoiceRow(aid, '', false));
      markUnsaved(true);
      var input = list.lastElementChild && list.lastElementChild.querySelector('input');
      if (input) input.focus();
    }

    window._cpwAddChoiceHandler = addChoiceForAssessment;

    var choiceHost = document.getElementById('cpwBlocks') || workspace;
    if (choiceHost && choiceHost.getAttribute('data-cpw-choice-delegation') !== '1' && !window._cpwChoiceUiBound) {
      choiceHost.setAttribute('data-cpw-choice-delegation', '1');
      choiceHost.addEventListener('click', function(ev) {
        var btn = ev.target && ev.target.closest('.cpw-add-choice-btn');
        if (!btn) return;
        ev.preventDefault();
        addChoiceForAssessment(btn.getAttribute('data-aid'));
      });
    }

    function collectChoices(aid) {
      var list = document.querySelector('[data-cpw-choices="' + aid + '"]');
      if (!list) return { ok: false, message: 'Choices missing.' };
      var rows = list.querySelectorAll('.cpw-choice-row');
      if (rows.length < 2) {
        return { ok: false, message: 'Add at least two choices.' };
      }
      var choices = [];
      var hasCorrect = false;
      for (var i = 0; i < rows.length; i++) {
        var text = rows[i].querySelector('input').value.trim();
        if (!text) {
          return { ok: false, message: 'All choices need text.' };
        }
        var correct = rows[i].querySelector('.cpw-choice-mark').classList.contains('is-correct');
        if (correct) hasCorrect = true;
        choices.push({ text: text, is_correct: correct ? 1 : 0 });
      }
      if (!hasCorrect) {
        return { ok: false, message: 'Mark one choice as correct.' };
      }
      return { ok: true, choices: choices };
    }

    function updateTabComplete(aid, done) {
      var tab = document.querySelector('[data-cpw-tab="' + aid + '"]');
      if (tab) {
        tab.classList.toggle('is-complete', !!done);
        var existing = tab.querySelector('.cpw-seg-tab-done, .cpw-tab-check');
        if (done && !existing) {
          var chk = document.createElement('span');
          chk.className = 'cpw-seg-tab-done';
          chk.setAttribute('aria-hidden', 'true');
          chk.textContent = '✓';
          tab.appendChild(chk);
        } else if (!done && existing) {
          existing.remove();
        }
      }
      var nav = document.querySelector('[data-cpw-nav="' + aid + '"]');
      if (nav) nav.classList.toggle('is-done', !!done);
      var dot = document.querySelector('[data-cpw-complete="' + aid + '"]');
      if (dot) dot.classList.toggle('is-done', !!done);
    }

    document.querySelectorAll('.cpw-meta-form').forEach(function(form) {
      form.addEventListener('input', function() { markUnsaved(true); });
      form.addEventListener('submit', function(ev) {
        ev.preventDefault();
        var aid = parseInt(form.getAttribute('data-assessment-id'), 10);
        var msgEl = document.querySelector('[data-cpw-meta-msg="' + aid + '"]');
        var fd = new FormData(form);
        var tsRaw = String(fd.get('trigger_seconds') || '').trim();
        var ts = tsRaw === '' ? 0 : parseInt(tsRaw, 10);
        if (isNaN(ts) || ts < 0) {
          ts = 0;
        }
        var vd = getVideoDurationSeconds(C);
        if (ts > 0 && vd < 1) {
          var needMsg = 'Enter whole video duration (seconds) above the checkpoint list — this is the full video length, not the checkpoint timestamp.';
          setMsg(msgEl, needMsg, 'err');
          cpwShowError({ message: needMsg, errors: { whole_video_duration_seconds: needMsg } });
          var durCard = document.getElementById('cpwSharedDurationCard');
          if (durCard && durCard.scrollIntoView) {
            durCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          var durIn = document.getElementById('cpwSharedDuration');
          if (durIn) {
            durIn.focus();
          }
          return;
        }
        if (ts > 0 && vd > 0 && ts > vd) {
          var overMsg = 'Checkpoint timestamp (' + ts + 's) cannot exceed whole video duration (' + vd + 's).';
          setMsg(msgEl, overMsg, 'err');
          cpwShowError({ message: overMsg });
          return;
        }
        persistVideoDurationSeconds(MODULE_ID, vd);
        var body = csrfPrefix()
          + 'assessment_id=' + encodeURIComponent(aid)
          + '&title=' + encodeURIComponent(fd.get('title') || '')
          + '&trigger_seconds=' + encodeURIComponent(ts)
          + '&trigger_percent=0'
          + '&sort_order=' + encodeURIComponent(fd.get('sort_order') || '0')
          + '&checkpoint_required=' + (fd.get('checkpoint_required') ? '1' : '0');
        body = appendDurationFields(body, vd, MODULE_ID);
        setMsg(msgEl, 'Saving…', '');
        if (!SAVE_META_URL) {
          setMsg(msgEl, 'Save URL is not configured.', 'err');
          cpwToast('error', 'Save URL is not configured.');
          return;
        }
        fetch(SAVE_META_URL, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
          body: body,
        })
          .then(function(r) {
            return parseJsonResponse(r);
          })
          .then(function(res) {
            if (responseOk(res)) {
              setMsg(msgEl, res.message || 'Checkpoint saved.', 'ok');
              markUnsaved(false);
              if (vd > 0) {
                persistVideoDurationSeconds(MODULE_ID, vd);
              }
              var badge = document.querySelector('[data-cpw-ts-badge="' + aid + '"]');
              if (badge) {
                badge.innerHTML = ts > 0
                  ? '<span class="cpw-ts-pill">▶ ' + ts + 's</span>'
                  : '<span class="cpw-ts-pill cpw-ts-pill--muted">Start of video</span>';
              }
              var tab = document.querySelector('[data-cpw-tab="' + aid + '"] .cpw-tab-ts');
              if (tab) {
                tab.textContent = ts > 0 ? ts + 's' : '';
              } else if (ts > 0) {
                var tabBtn = document.querySelector('[data-cpw-tab="' + aid + '"] .cpw-tab-text');
                if (tabBtn) {
                  var span = document.createElement('span');
                  span.className = 'cpw-tab-ts';
                  span.textContent = ts + 's';
                  tabBtn.appendChild(span);
                }
              }
              cpwToast('success', res.message || 'Saved.');
            } else {
              var errMsg = cpwErrorMessage(res, 'Save failed.');
              setMsg(msgEl, errMsg, 'err');
              cpwShowError(res, errMsg);
              if (res && res.errors && res.errors.whole_video_duration_seconds) {
                var card = document.getElementById('cpwSharedDurationCard');
                if (card && card.scrollIntoView) {
                  card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
              }
            }
          })
          .catch(function() {
            setMsg(msgEl, 'Network error.', 'err');
            cpwShowError({ message: 'Network error. Check your connection and try again.' });
          });
      });
    });

    document.querySelectorAll('.cpw-question-form').forEach(function(form) {
      form.addEventListener('input', function() { markUnsaved(true); });
      form.addEventListener('submit', function(ev) {
        ev.preventDefault();
        var aid = parseInt(form.getAttribute('data-assessment-id'), 10);
        var msgEl = document.querySelector('[data-cpw-q-msg="' + aid + '"]');
        var qid = parseInt(form.querySelector('[name="question_id"]').value, 10) || 0;
        var qtext = form.querySelector('[name="question_text"]').value.trim();
        if (!qtext) {
          setMsg(msgEl, 'Enter question text.', 'err');
          return;
        }
        var ch = collectChoices(aid);
        if (!ch.ok) {
          setMsg(msgEl, ch.message, 'err');
          return;
        }
        var body = csrfPrefix()
          + 'assessment_id=' + encodeURIComponent(aid)
          + '&question_id=' + encodeURIComponent(qid)
          + '&question_text=' + encodeURIComponent(qtext)
          + '&question_type=multiple_choice'
          + '&is_required=1'
          + '&min_words=0';
        ch.choices.forEach(function(c, i) {
          body += '&choices[' + i + '][text]=' + encodeURIComponent(c.text);
          body += '&choices[' + i + '][is_correct]=' + encodeURIComponent(c.is_correct);
        });
        setMsg(msgEl, 'Saving question…', '');
        fetch(SAVE_Q_URL, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: body,
        })
          .then(parseJsonResponse)
          .then(function(res) {
            if (responseOk(res)) {
              setMsg(msgEl, res.message || 'Question saved.', 'ok');
              markUnsaved(false);
              updateTabComplete(aid, true);
              if (res.question && res.question.id) {
                form.querySelector('[name="question_id"]').value = res.question.id;
              }
              cpwToast('success', res.message || 'Question saved.');
            } else {
              setMsg(msgEl, res.message || 'Save failed.', 'err');
              cpwToast('error', res.message || 'Save failed.');
            }
          })
          .catch(function() {
            setMsg(msgEl, 'Network error.', 'err');
          });
      });
    });

    document.querySelectorAll('.cpw-delete-question-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var qid = parseInt(btn.getAttribute('data-qid'), 10);
        var aid = parseInt(btn.getAttribute('data-aid'), 10);
        if (!qid || !window.KA || !window.KA.confirm) {
          return;
        }
        window.KA.confirm({
          title: 'Delete this question?',
          text: 'The checkpoint will need a new question before learners can pass it.',
          confirmText: 'Delete',
          type: 'danger',
          onConfirm: function() {
            fetch(DEL_Q_URL, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
              },
              body: csrfPrefix() + 'question_id=' + encodeURIComponent(qid),
            })
              .then(parseJsonResponse)
              .then(function(res) {
                if (responseOk(res)) {
                  updateTabComplete(aid, false);
                  window.location.reload();
                } else {
                  cpwToast('error', res.message || 'Delete failed.');
                }
              });
          },
        });
      });
    });

    function runAutoGenerate(btn) {
      var vd = getVideoDurationSeconds(C);
      if (!(vd > 0)) {
        cpwShowError({ message: 'Enter whole video duration in seconds first (e.g. 94 or 1000).' });
        return;
      }
      persistVideoDurationSeconds(MODULE_ID, vd);
      var spin = btn.querySelector('.asx-btn-spinner');
      btn.disabled = true;
      btn.classList.add('is-loading');
      if (spin) spin.hidden = false;
      if (typeof window.asxEditorSetAutosave === 'function') {
        window.asxEditorSetAutosave('saving');
      }
      var body = csrfPrefix()
        + 'module_id=' + encodeURIComponent(MODULE_ID)
        + '&title=Video checkpoint'
        + '&checkpoint_required=1';
      body = appendDurationFields(body, vd, MODULE_ID);
      fetch(AUTO_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: body,
      })
        .then(parseJsonResponse)
        .then(function(res) {
          btn.disabled = false;
          btn.classList.remove('is-loading');
          if (spin) spin.hidden = true;
          if (responseOk(res) && res.redirect) {
            window.location.href = res.redirect;
          } else if (responseOk(res)) {
            window.location.reload();
          } else {
            cpwToast('error', res.message || 'Could not generate checkpoints.');
            if (typeof window.asxEditorSetAutosave === 'function') {
              window.asxEditorSetAutosave('saved');
            }
          }
        })
        .catch(function() {
          btn.disabled = false;
          btn.classList.remove('is-loading');
          if (spin) spin.hidden = true;
          cpwToast('error', 'Network error.');
        });
    }

    var autoBtn = document.getElementById('cpwAutoGenerateBtn');
    var autoMore = document.getElementById('cpwAutoGenerateMoreBtn');
    if (autoBtn) autoBtn.addEventListener('click', function() { runAutoGenerate(autoBtn); });
    if (autoMore) autoMore.addEventListener('click', function() { runAutoGenerate(autoMore); });

    document.querySelectorAll('#asxEditorRoot input, #asxEditorRoot textarea, #asxEditorRoot select').forEach(function(el) {
      el.addEventListener('input', function() { markUnsaved(true); });
      el.addEventListener('change', function() { markUnsaved(true); });
    });

    root.setAttribute('data-cpw-init', '1');
  }

  window.cpwConfirmDeleteCheckpoint = function(link) {
    var title = link.getAttribute('data-title') || 'this checkpoint';
    var href = link.getAttribute('href');
    if (window.KA && window.KA.deleteConfirm) {
      window.KA.deleteConfirm(href, title);
      return false;
    }
    if (window.KA_SWAL && typeof KA_SWAL.SwalConfirm === 'function') {
      KA_SWAL.SwalConfirm({
        title: 'Delete ' + title + '?',
        html: 'This action <strong>cannot be undone</strong>.',
        icon: 'warning',
        confirmButtonText: 'Yes, delete it',
        confirmButtonColor: '#dc2626',
      }).then(function (r) {
        if (r.isConfirmed && href) {
          window.location.href = href;
        }
      });
      return false;
    }
    return false;
  };

  window.cpwInitWorkspace = initCheckpointWorkspace;

  function bootCheckpointWorkspace() {
    initCheckpointWorkspace();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootCheckpointWorkspace);
  } else {
    bootCheckpointWorkspace();
  }
})();
