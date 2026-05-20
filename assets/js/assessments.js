(function() {
  'use strict';

  /** Registers APP_CONTEXT.assessments.actions for stable references (globals kept for onclick). */
  function kaEnsureAssessmentsActions() {
    var ctx = window.APP_CONTEXT || {};
    ctx.assessments = ctx.assessments || {};
    ctx.assessments.actions = ctx.assessments.actions || {};
    window.APP_CONTEXT = ctx;
    return ctx.assessments.actions;
  }

  /** Modal / form handlers for assessments UI (globals kept for onclick / onchange). */
  function kaEnsureAssessmentsUi() {
    var ctx = window.APP_CONTEXT || {};
    ctx.assessments = ctx.assessments || {};
    ctx.assessments.ui = ctx.assessments.ui || {};
    window.APP_CONTEXT = ctx;
    return ctx.assessments.ui;
  }

  var ASSESSMENT_UI_GLOBALS = [
    'openModal',
    'closeModal',
    'closeModalOutside',
    'onTypeChange',
    'addChoice',
    'saveQuestion',
    'addAnotherQuestion',
    'deleteQuestion',
    'onEditAssessmentTypeChange',
  ];

  /** Stubs available before DOMContentLoaded / initEdit (inline onclick-safe). */
  function kaBindAssessmentGlobals() {
    ASSESSMENT_UI_GLOBALS.forEach(function(name) {
      window[name] = function() {
        var ui = window.APP_CONTEXT && window.APP_CONTEXT.assessments && window.APP_CONTEXT.assessments.ui;
        var fn = ui && ui[name];
        if (typeof fn === 'function') {
          return fn.apply(null, arguments);
        }
        if (typeof console !== 'undefined' && console.warn) {
          console.warn('[assessments] ' + name + ' is not ready yet.');
        }
        if (window.KA && window.KA.toast) {
          window.KA.toast('error', 'Assessment editor is still loading. Please try again.');
        }
      };
    });

    window.editQuestion = function(qid) {
      var ax = window.APP_CONTEXT && window.APP_CONTEXT.assessments && window.APP_CONTEXT.assessments.actions;
      var fn = ax && ax.editQuestion;
      if (typeof fn === 'function') {
        return fn(qid);
      }
      if (window.KA && window.KA.toast) {
        window.KA.toast('error', 'Assessment editor is still loading. Please try again.');
      }
    };
  }

  kaBindAssessmentGlobals();

  function initIndex() {
    var search = document.getElementById('asxSearch');
    var typeEl = document.getElementById('asxFilterType');
    var countEl = document.getElementById('asxCount');
    if (!search || !typeEl || !countEl) return;
    var cards = document.querySelectorAll('.asx-card');
    if (!cards.length) return;

    var params = new URLSearchParams(window.location.search || '');
    var urlModuleId = parseInt(params.get('module_id') || '0', 10) || 0;
    var urlType = params.get('type') || '';
    if (urlType) {
      typeEl.value = urlType;
    }

    function filter() {
      var kw = search.value.toLowerCase().trim();
      var type = typeEl.value;
      var vis = 0;
      cards.forEach(function(c) {
        var title = c.dataset.title || '';
        var ctype = c.dataset.type || '';
        var mid = parseInt(c.dataset.moduleId || '0', 10) || 0;
        var show = (!kw || title.indexOf(kw) !== -1)
          && (!type || ctype === type)
          && (!urlModuleId || mid === urlModuleId);
        c.style.display = show ? '' : 'none';
        if (show) vis++;
      });
      countEl.textContent = vis + ' assessment' + (vis !== 1 ? 's' : '');
    }

    search.addEventListener('input', filter);
    typeEl.addEventListener('change', filter);
    filter();
  }

  function initTake() {
    var form = document.getElementById('takeForm');
    if (!form) return;

    var ctx = (window.APP_CONTEXT && window.APP_CONTEXT.assessments && window.APP_CONTEXT.assessments.take) || {};
    var total = parseInt(ctx.totalQuestions, 10);
    if (!total || total < 1) {
      total = document.querySelectorAll('.take-q-card[data-qid]').length;
    }

    var answeredSet = new Set();

    function markAnswered(qid) {
      answeredSet.add(qid);
      var n = document.getElementById('qnum-' + qid);
      var nav = document.getElementById('qnav-' + qid);
      var card = document.getElementById('qcard-' + qid);
      if (n) n.classList.add('answered');
      if (nav) nav.classList.add('answered');
      if (card) card.classList.add('answered');
      updateCounts();
    }

    function updateCounts() {
      var n = answeredSet.size;
      var pct = total > 0 ? Math.round((n / total) * 100) : 0;
      var fill = document.getElementById('takeProgressFill');
      if (fill) fill.style.width = pct + '%';
      var an = document.getElementById('answeredNum');
      if (an) an.textContent = n;
      var side = document.getElementById('sideAnsweredCount');
      if (side) {
        var strong = side.querySelector('strong');
        if (strong) strong.textContent = n;
      }
      var bottom = document.getElementById('bottomAnsweredCount');
      if (bottom) bottom.textContent = n + ' of ' + total + ' answered';
    }

    function updateWordCount(el, qid) {
      var words = el.value.trim() === '' ? 0 : el.value.trim().split(/\s+/).length;
      var minW = parseInt(el.dataset.minwords, 10) || 0;
      var el2 = document.getElementById('wc-' + qid);
      if (el2) {
        el2.textContent = words + ' word' + (words !== 1 ? 's' : '') + (minW ? ' (minimum ' + minW + ')' : '');
        el2.style.color = (minW && words < minW) ? '#dc2626' : '#64748b';
      }
    }

    function confirmSubmit() {
      var unanswered = total - answeredSet.size;
      var msg = unanswered > 0
        ? unanswered + ' question(s) are still unanswered. Submit anyway?'
        : 'Are you sure you want to submit? You cannot change your answers after submission.';

      window.KA.confirm({
        title: 'Submit Assessment?',
        text: msg,
        confirmText: 'Yes, submit now',
        cancelText: 'Review answers',
        type: unanswered > 0 ? 'warning' : 'info',
        onConfirm: function() { form.submit(); },
      });
    }

    var ax = kaEnsureAssessmentsActions();
    ax.markAnswered = markAnswered;
    window.markAnswered = ax.markAnswered;

    var ui = kaEnsureAssessmentsUi();
    ui.updateWordCount = updateWordCount;
    ui.confirmSubmit = confirmSubmit;
    window.updateWordCount = ui.updateWordCount;
    window.confirmSubmit = ui.confirmSubmit;
  }

  function initCreate() {
    if (!document.getElementById('createForm')) return;

    function toggleCheckpointAuto(on) {
      var m = document.getElementById('checkpointManualTriggerWrap');
      if (m) m.style.display = on ? 'none' : 'block';
    }

    function selectType(type) {
      var pre = document.getElementById('card-pre');
      var post = document.getElementById('card-post');
      if (pre) pre.classList.toggle('selected', type === 'pre');
      if (post) post.classList.toggle('selected', type === 'post');
      var cp = document.getElementById('card-checkpoint');
      if (cp) cp.classList.toggle('selected', type === 'checkpoint');
      var panel = document.getElementById('checkpointFields');
      var hint = document.getElementById('createSidebarHint');
      if (panel) panel.classList.toggle('visible', type === 'checkpoint');
      if (type === 'checkpoint') {
        var ag = document.getElementById('checkpoint_auto_generate');
        toggleCheckpointAuto(ag && ag.checked);
      }
      if (hint) {
        if (type === 'checkpoint') {
          var ag2 = document.getElementById('checkpoint_auto_generate');
          hint.textContent = (ag2 && ag2.checked)
            ? 'Three checkpoints will be created with randomized times. You will add one multiple-choice question to each on the next screens (start with the first).'
            : 'You will add one multiple-choice question. It appears in the course video player at the timestamp you set (or at the start if left empty).';
        } else {
          hint.textContent = 'After creating the assessment, you\'ll be taken to the question builder where you can add multiple choice, essay, fill-in-the-blank, or Likert scale questions.';
        }
      }
    }

    var ui = kaEnsureAssessmentsUi();
    ui.toggleCheckpointAuto = toggleCheckpointAuto;
    window.toggleCheckpointAuto = ui.toggleCheckpointAuto;

    var ax = kaEnsureAssessmentsActions();
    ax.selectType = selectType;
    window.selectType = ax.selectType;

    var checked = document.querySelector('input[name="type"]:checked');
    if (checked) selectType(checked.value);
    var ag = document.getElementById('checkpoint_auto_generate');
    if (ag) {
      ag.addEventListener('change', function() {
        var t = document.querySelector('input[name="type"]:checked');
        if (t && t.value === 'checkpoint') selectType('checkpoint');
      });
    }

    var createForm = document.getElementById('createForm');
    if (createForm && createForm.getAttribute('data-cp-duration-bound') !== '1') {
      createForm.setAttribute('data-cp-duration-bound', '1');
      createForm.addEventListener('submit', function(ev) {
        var type = document.querySelector('input[name="type"]:checked');
        if (!type || type.value !== 'checkpoint') return;
        var vdEl = document.getElementById('video_duration_seconds');
        var vd = vdEl ? parseInt(String(vdEl.value).trim(), 10) : NaN;
        if (!(vd > 0)) return;
        var auto = document.getElementById('checkpoint_auto_generate');
        if (auto && auto.checked) return;
        var tsEl = document.getElementById('trigger_seconds');
        var tsRaw = tsEl ? String(tsEl.value).trim() : '';
        var ts = tsRaw === '' ? 0 : parseInt(tsRaw, 10);
        if (isNaN(ts) || ts < 0) return;
        if (ts > vd) {
          ev.preventDefault();
          if (window.KA && typeof window.KA.toast === 'function') {
            window.KA.toast('error', 'Checkpoint exceeds video length (' + vd + 's)');
          } else {
            alert('Checkpoint exceeds video length (' + vd + 's)');
          }
        }
      });
    }
  }

  function initGrade() {
    var row = document.querySelector('.grd-score-row');
    if (!row) return;
    var C = (window.APP_CONTEXT && window.APP_CONTEXT.assessments && window.APP_CONTEXT.assessments.grade) || {};
    var saveUrl = C.saveGradeUrl;
    var csrfName = C.csrfFieldName;
    var csrfHash = C.csrfHash;
    if (!saveUrl) return;

    function parseJsonResponse(r) {
      return r.text().then(function(text) {
        try {
          return JSON.parse(text);
        } catch (e) {
          return {
            success: false,
            message: r.status === 401 ? 'Session expired. Please log in again.' : 'Unexpected server response.',
          };
        }
      });
    }

    function saveScore(answerId) {
      var input = document.getElementById('scoreinput-' + answerId);
      var chip = document.getElementById('savedchip-' + answerId);
      if (!input) return;

      var score = parseFloat(input.value);
      if (isNaN(score) || score < 0 || score > 100) {
        window.KA.toast('error', 'Score must be between 0 and 100.');
        return;
      }

      var scoreRow = input.closest('.grd-score-row');
      var btn = scoreRow ? scoreRow.querySelector('.grd-save-btn') : null;
      if (!btn) return;
      btn.disabled = true;
      btn.textContent = 'Saving…';

      fetch(saveUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: (csrfName ? csrfName + '=' + encodeURIComponent(csrfHash || '') + '&' : '')
          + 'answer_id=' + encodeURIComponent(answerId)
          + '&score=' + encodeURIComponent(score),
      })
        .then(parseJsonResponse)
        .then(function(data) {
          btn.disabled = false;
          btn.textContent = 'Update';
          if (data.success) {
            if (chip) {
              chip.style.display = 'flex';
              setTimeout(function() { chip.style.display = 'none'; }, 3000);
            }
            window.KA.toast('success', 'Score saved successfully.');
          } else {
            window.KA.toast('error', data.message || 'Failed to save score.');
          }
        })
        .catch(function() {
          btn.disabled = false;
          btn.textContent = 'Update';
          window.KA.toast('error', 'Network error. Please try again.');
        });
    }

    var ax = kaEnsureAssessmentsActions();
    ax.saveScore = saveScore;
    window.saveScore = ax.saveScore;
  }

  function initEdit() {
    var overlay = document.getElementById('qModalOverlay');
    if (!overlay) return;

    var C = (window.APP_CONTEXT && window.APP_CONTEXT.assessments && window.APP_CONTEXT.assessments.edit) || {};
    var ASSESSMENT_ID = C.assessmentId || 0;
    var CSRF_NAME = C.csrfFieldName || '';
    var CSRF_HASH = C.csrfHash || '';
    var SAVE_Q_URL = C.saveQuestionUrl || '';
    var DEL_Q_URL = C.deleteQuestionUrl || '';
    var TYPE_COLORS = C.typeColors || {};
    var TYPE_LABELS = C.typeLabels || {};

    // CI3 may have csrf_protection=false → token name/hash are empty strings; do not require them for editReady.
    var needsCsrf = String(CSRF_NAME || '').trim().length > 0;
    var csrfOk = !needsCsrf || String(CSRF_HASH || '').length > 0;
    var hasId = parseInt(ASSESSMENT_ID, 10) > 0;
    var editReady = !!(SAVE_Q_URL && DEL_Q_URL && hasId && csrfOk);

    function missingEditContextKeys() {
      var miss = [];
      if (!SAVE_Q_URL) miss.push('saveQuestionUrl');
      if (!DEL_Q_URL) miss.push('deleteQuestionUrl');
      if (!hasId) miss.push('assessmentId');
      if (!csrfOk) miss.push(needsCsrf ? 'csrfHash' : 'csrf');
      return miss;
    }

    if (!editReady && typeof console !== 'undefined' && console.warn) {
      console.warn('ASSESSMENT EDIT CONTEXT', {
        SAVE_Q_URL: SAVE_Q_URL,
        DEL_Q_URL: DEL_Q_URL,
        CSRF_NAME: CSRF_NAME,
        CSRF_HASH: CSRF_HASH,
        ASSESSMENT_ID: ASSESSMENT_ID,
        needsCsrf: needsCsrf,
        missingKeys: missingEditContextKeys(),
        APP_CONTEXT: window.APP_CONTEXT,
      });
    }

    function parseJsonResponse(r) {
      return r.text().then(function(text) {
        try {
          return JSON.parse(text);
        } catch (e) {
          return {
            success: false,
            message: r.status === 401 ? 'Session expired. Please log in again.' : 'Unexpected server response.',
          };
        }
      });
    }

    var pendingBatch = [];

    function isCheckpointMode() {
      var sel = document.getElementById('editAssessmentType');
      return sel && sel.value === 'checkpoint';
    }

    function isChoiceCorrect(val) {
      return val === true || val === 1 || val === '1';
    }

    function onEditAssessmentTypeChange() {
      var box = document.getElementById('editCheckpointFields');
      if (box) box.classList.toggle('visible', isCheckpointMode());
    }

    function syncCheckpointQuestionUi() {
      var btn = document.getElementById('addQuestionBtn');
      if (!btn) return;
      var n = document.querySelectorAll('.q-item').length;
      if (isCheckpointMode() && n >= 1) {
        btn.disabled = true;
        btn.title = 'Video checkpoints support one multiple-choice question. Edit or delete the existing question.';
      } else {
        btn.disabled = false;
        btn.title = '';
      }
    }

    function resetModalForm() {
      document.getElementById('mfQText').value = '';
      document.getElementById('mfQType').value = 'multiple_choice';
      document.getElementById('mfRequired').checked = true;
      document.getElementById('mfMinWords').value = '';
      document.getElementById('choicesList').innerHTML = '';
      addChoice();
      addChoice();
      onTypeChange();
    }

    function syncModalFooter() {
      var qid = parseInt(document.getElementById('modalQuestionId').value, 10) || 0;
      var addBtn = document.getElementById('addAnotherBtn');
      var saveText = document.getElementById('modalSaveText');
      var summary = document.getElementById('batchQueueSummary');
      var isEdit = qid > 0;
      var queued = pendingBatch.length;
      var canBatch = !isEdit && !isCheckpointMode();

      if (addBtn) {
        addBtn.style.display = canBatch ? '' : 'none';
      }

      if (summary) {
        if (canBatch && queued > 0) {
          summary.style.display = '';
          summary.textContent = queued + ' queued';
        } else {
          summary.style.display = 'none';
          summary.textContent = '';
        }
      }

      if (saveText) {
        if (isEdit) {
          saveText.textContent = 'Update Question';
        } else if (canBatch && queued > 0) {
          saveText.textContent = 'Save All Questions';
        } else {
          saveText.textContent = 'Add Question';
        }
      }
    }

    function updateActiveQuestionLabel() {
      var label = document.getElementById('activeQuestionLabel');
      if (!label) return;
      var n = pendingBatch.length + 1;
      if (pendingBatch.length > 0) {
        label.style.display = '';
        label.textContent = 'Question ' + n;
      } else {
        label.style.display = 'none';
        label.textContent = 'Question 1';
      }
    }

    function renderQueuedFormBlocks() {
      var stack = document.getElementById('questionFormStack');
      var activeBlock = document.getElementById('questionFormBlock0');
      if (!stack || !activeBlock) return;

      stack.querySelectorAll('.question-form-block--queued').forEach(function(el) {
        el.remove();
      });

      pendingBatch.forEach(function(q, i) {
        var block = document.createElement('div');
        block.className = 'question-form-block question-form-block--queued';
        block.setAttribute('data-q-block', String(i + 1));
        var typeLabel = TYPE_LABELS[q.question_type] || q.question_type;
        var preview = q.question_text.length > 90
          ? q.question_text.substring(0, 90) + '…'
          : q.question_text;
        block.innerHTML = '<div class="question-block-label">Question ' + (i + 1) + '</div>'
          + '<div class="batch-queued-preview">' + escHtml(preview)
          + ' <span class="batch-queued-type">(' + escHtml(typeLabel) + ')</span></div>'
          + '<button type="button" class="batch-remove-btn" data-index="' + i + '">Remove</button>';
        stack.insertBefore(block, activeBlock);

        block.querySelector('.batch-remove-btn').addEventListener('click', function() {
          var idx = parseInt(this.getAttribute('data-index'), 10);
          if (idx >= 0 && idx < pendingBatch.length) {
            pendingBatch.splice(idx, 1);
            renderQueuedFormBlocks();
            updateBatchQueueUi();
            updateActiveQuestionLabel();
            syncModalFooter();
          }
        });
      });

      updateActiveQuestionLabel();
    }

    function updateBatchQueueUi() {
      var panel = document.getElementById('batchQueuePanel');
      var list = document.getElementById('batchQueueList');
      if (!panel || !list) return;

      if (pendingBatch.length === 0) {
        panel.style.display = 'none';
        list.innerHTML = '';
        syncModalFooter();
        return;
      }

      panel.style.display = '';
      list.innerHTML = pendingBatch.map(function(q, i) {
        var label = TYPE_LABELS[q.question_type] || q.question_type;
        var preview = q.question_text.length > 72
          ? q.question_text.substring(0, 72) + '…'
          : q.question_text;
        return '<li><strong>' + (i + 1) + '.</strong> ' + escHtml(preview)
          + ' <span style="color:#64748b;">(' + escHtml(label) + ')</span></li>';
      }).join('');
      syncModalFooter();
    }

    function openModal(qid) {
      if (isCheckpointMode() && !qid && document.querySelectorAll('.q-item').length >= 1) {
        window.KA.toast('error', 'This video checkpoint already has a question.');
        return;
      }
      document.getElementById('qModalOverlay').classList.add('open');
      document.getElementById('modalQuestionId').value = qid || 0;
      document.getElementById('modalTitle').textContent = qid ? 'Edit Question' : 'Add Question';

      if (!qid) {
        pendingBatch = [];
        resetModalForm();
        renderQueuedFormBlocks();
        updateBatchQueueUi();
      }
      var typeRow = document.getElementById('mfQTypeRow');
      if (typeRow) typeRow.style.display = isCheckpointMode() ? 'none' : '';
      if (isCheckpointMode()) {
        document.getElementById('mfQType').value = 'multiple_choice';
        onTypeChange();
      }
      syncModalFooter();
    }

    function editQuestion(qid) {
      var el = document.getElementById('qitem-' + qid);
      if (!el) return;

      document.getElementById('mfQText').value = el.dataset.text;
      document.getElementById('mfQType').value = el.dataset.type;
      document.getElementById('mfRequired').checked = el.dataset.required === '1';
      document.getElementById('mfMinWords').value = el.dataset.minwords || '';

      var choices = [];
      try {
        choices = JSON.parse(el.dataset.choices || '[]');
      } catch (e) {
        if (window.KA && window.KA.toast) {
          window.KA.toast('error', 'Could not load saved choices for this question.');
        }
      }
      document.getElementById('choicesList').innerHTML = '';
      if (choices.length > 0) {
        choices.forEach(function(c) { addChoice(c.text, c.is_correct); });
      } else {
        addChoice();
        addChoice();
      }

      onTypeChange();
      openModal(qid);
    }

    function closeModal() {
      document.getElementById('qModalOverlay').classList.remove('open');
      pendingBatch = [];
      renderQueuedFormBlocks();
      updateBatchQueueUi();
      setSaveBusy(false);
    }

    function closeModalOutside(e) {
      if (e.target === document.getElementById('qModalOverlay')) closeModal();
    }

    function onTypeChange() {
      var type = document.getElementById('mfQType').value;
      var choicesEl = document.getElementById('mfChoicesSection');
      var minWEl = document.getElementById('mfMinWordsGroup');
      var likertEl = document.getElementById('mfLikertNote');
      var essayEl = document.getElementById('mfEssayNote');
      var choicesLabel = document.getElementById('mfChoicesLabel');
      var choiceHint = document.getElementById('choiceHint');

      choicesEl.style.display = 'none';
      minWEl.style.display = 'none';
      likertEl.style.display = 'none';
      essayEl.style.display = 'none';

      if (type === 'multiple_choice') {
        choicesEl.style.display = '';
        choicesLabel.textContent = 'Answer Choices (mark the correct one ✓)';
        choiceHint.textContent = 'Click ✓ on a choice to mark it as the correct answer. Only one correct answer allowed.';
      } else if (type === 'fill_blank') {
        choicesEl.style.display = '';
        choicesLabel.textContent = 'Accepted Answer';
        choiceHint.textContent = 'Add the correct answer(s). Auto-scored by exact match (case-insensitive).';
      } else if (type === 'likert') {
        likertEl.style.display = '';
      } else if (type === 'essay') {
        essayEl.style.display = '';
        minWEl.style.display = '';
      }
    }

    function addChoice(text, isCorrect) {
      var list = document.getElementById('choicesList');
      var row = document.createElement('div');
      row.className = 'choice-row';

      var inp = document.createElement('input');
      inp.type = 'text';
      inp.className = 'choice-text-input';
      inp.placeholder = 'Choice text…';
      inp.value = text || '';

      var correctBtn = document.createElement('button');
      correctBtn.type = 'button';
      correctBtn.className = 'choice-correct-btn' + (isChoiceCorrect(isCorrect) ? ' active' : '');
      correctBtn.title = 'Mark as correct';
      correctBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>';
      correctBtn.onclick = function() {
        if (document.getElementById('mfQType').value === 'multiple_choice') {
          list.querySelectorAll('.choice-correct-btn').forEach(function(b) { b.classList.remove('active'); });
          correctBtn.classList.add('active');
          return;
        }
        correctBtn.classList.toggle('active');
      };

      var delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.className = 'choice-del-btn';
      delBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
      delBtn.onclick = function() { row.remove(); };

      row.appendChild(inp);
      row.appendChild(correctBtn);
      row.appendChild(delBtn);
      list.appendChild(row);
      inp.focus();
    }

    function assessmentToast(type, message, durationMs) {
      if (typeof Swal === 'undefined') {
        if (window.KA && window.KA.toast) {
          window.KA.toast(type === 'loading' ? 'info' : type, message);
        }
        return;
      }
      if (typeof Swal.isVisible === 'function' && Swal.isVisible()) {
        Swal.close();
      }
      var iconColors = {
        success: '#22c55e',
        error: '#dc2626',
        warning: '#f59f00',
        info: '#6dabcf',
      };
      var icon = type === 'loading' ? 'info' : type;
      var timer = durationMs;
      if (timer === undefined) {
        timer = type === 'success' ? 1500 : (type === 'error' ? 3800 : 0);
      }
      var mixinOpts = {
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
      };
      if (timer > 0) {
        mixinOpts.timer = timer;
        mixinOpts.timerProgressBar = true;
        mixinOpts.didOpen = function(t) {
          t.addEventListener('mouseenter', Swal.stopTimer);
          t.addEventListener('mouseleave', Swal.resumeTimer);
        };
      }
      Swal.mixin(mixinOpts).fire({
        icon: icon,
        title: message,
        iconColor: iconColors[icon] || '#6dabcf',
        background: '#fff',
        color: '#1e293b',
      });
    }

    function getSaveToastCopy(qid, count) {
      if (qid > 0) {
        return { loading: 'Saving question…', success: 'Question updated.' };
      }
      if (count > 1) {
        return { loading: 'Saving questions…', success: 'Questions saved.' };
      }
      return { loading: 'Saving question…', success: 'Question added.' };
    }

    function cloneQuestionPayload(p) {
      return {
        question_text: p.question_text,
        question_type: p.question_type,
        is_required: p.is_required,
        min_words: p.min_words,
        choices: (p.choices || []).map(function(c) {
          return { text: c.text, is_correct: c.is_correct };
        }),
      };
    }

    function applyPayloadToForm(p) {
      document.getElementById('mfQText').value = p.question_text || '';
      document.getElementById('mfQType').value = p.question_type || 'multiple_choice';
      document.getElementById('mfRequired').checked = !!p.is_required;
      document.getElementById('mfMinWords').value = p.min_words || '';
      document.getElementById('choicesList').innerHTML = '';
      var choices = p.choices || [];
      if (choices.length > 0) {
        choices.forEach(function(c) { addChoice(c.text, c.is_correct); });
      } else {
        addChoice();
        addChoice();
      }
      onTypeChange();
    }

    function stashModalForRestore(qid) {
      var current = collectFormQuestion();
      return {
        qid: qid,
        pendingBatch: pendingBatch.map(cloneQuestionPayload),
        formPayload: current.ok ? cloneQuestionPayload(current.payload) : null,
      };
    }

    function restoreModalFromStash(stash) {
      if (!stash) return;
      openModal(stash.qid || 0);
      pendingBatch = (stash.pendingBatch || []).map(cloneQuestionPayload);
      if (stash.formPayload) {
        applyPayloadToForm(stash.formPayload);
      } else if (!stash.qid) {
        resetModalForm();
      }
      renderQueuedFormBlocks();
      updateBatchQueueUi();
    }

    function setSaveBusy(busy) {
      var saveBtn = document.getElementById('modalSaveBtn') || document.querySelector('.q-modal-save');
      var spinner = document.getElementById('modalSaveSpinner');
      var addBtn = document.getElementById('addAnotherBtn');
      var cancelBtn = document.querySelector('.q-modal-cancel');
      if (saveBtn) {
        saveBtn.disabled = !!busy;
        saveBtn.classList.toggle('is-busy', !!busy);
      }
      if (addBtn) addBtn.disabled = !!busy;
      if (cancelBtn) cancelBtn.disabled = !!busy;
      if (spinner) spinner.style.display = busy ? '' : 'none';
      if (busy) {
        var saveText = document.getElementById('modalSaveText');
        if (saveText) saveText.textContent = 'Saving…';
      } else {
        syncModalFooter();
      }
    }

    function collectFormQuestion() {
      var text = document.getElementById('mfQText').value.trim();
      var type = document.getElementById('mfQType').value;
      var choices = [];

      document.querySelectorAll('#choicesList .choice-row').forEach(function(row) {
        var t = row.querySelector('.choice-text-input').value.trim();
        var ok = row.querySelector('.choice-correct-btn').classList.contains('active');
        if (t) choices.push({ text: t, is_correct: ok ? 1 : 0 });
      });

      if (!text) {
        return { ok: false, message: 'Please enter the question text.' };
      }
      if (type === 'multiple_choice') {
        if (choices.length < 2) {
          return { ok: false, message: 'Add at least 2 choices.' };
        }
        if (!choices.some(function(c) { return c.is_correct; })) {
          return { ok: false, message: 'Mark one choice as the correct answer.' };
        }
      }
      if (isCheckpointMode() && type !== 'multiple_choice') {
        return { ok: false, message: 'Video checkpoints only support multiple choice.' };
      }
      if (type === 'fill_blank' && choices.length === 0) {
        return { ok: false, message: 'Add at least one accepted answer.' };
      }

      return {
        ok: true,
        payload: {
          question_text: text,
          question_type: type,
          is_required: document.getElementById('mfRequired').checked ? 1 : 0,
          min_words: parseInt(document.getElementById('mfMinWords').value, 10) || 0,
          choices: choices,
        },
      };
    }

    function buildOptimisticQuestion(payload, tempId) {
      return {
        id: tempId,
        question_text: payload.question_text,
        question_type: payload.question_type,
        is_required: payload.is_required,
        min_words: payload.min_words || null,
        choices: (payload.choices || []).map(function(c, i) {
          return {
            id: 'tmp-' + tempId + '-' + i,
            choice_text: c.text,
            is_correct: c.is_correct,
          };
        }),
      };
    }

    function appendCsrf(body) {
      if (String(CSRF_NAME || '').length > 0) {
        return String(CSRF_NAME) + '=' + encodeURIComponent(String(CSRF_HASH || '')) + '&' + body;
      }
      return body;
    }

    function encodeSingleQuestionBody(qid, payload) {
      var body = 'assessment_id=' + encodeURIComponent(ASSESSMENT_ID)
        + '&question_id=' + encodeURIComponent(qid)
        + '&question_text=' + encodeURIComponent(payload.question_text)
        + '&question_type=' + encodeURIComponent(payload.question_type)
        + '&is_required=' + encodeURIComponent(payload.is_required)
        + '&min_words=' + encodeURIComponent(payload.min_words);

      (payload.choices || []).forEach(function(c, i) {
        body += '&choices[' + i + '][text]=' + encodeURIComponent(c.text);
        body += '&choices[' + i + '][is_correct]=' + encodeURIComponent(c.is_correct);
      });
      return appendCsrf(body);
    }

    function encodeBatchBody(questions) {
      var body = 'assessment_id=' + encodeURIComponent(ASSESSMENT_ID);
      questions.forEach(function(q, qi) {
        body += '&questions[' + qi + '][question_text]=' + encodeURIComponent(q.question_text);
        body += '&questions[' + qi + '][question_type]=' + encodeURIComponent(q.question_type);
        body += '&questions[' + qi + '][is_required]=' + encodeURIComponent(q.is_required);
        body += '&questions[' + qi + '][min_words]=' + encodeURIComponent(q.min_words);
        (q.choices || []).forEach(function(c, ci) {
          body += '&questions[' + qi + '][choices][' + ci + '][text]=' + encodeURIComponent(c.text);
          body += '&questions[' + qi + '][choices][' + ci + '][is_correct]=' + encodeURIComponent(c.is_correct);
        });
      });
      return appendCsrf(body);
    }

    function rollbackOptimistic(tempIds, rollbackMap) {
      tempIds.forEach(function(id) {
        var el = document.getElementById('qitem-' + id);
        if (el) el.remove();
        if (rollbackMap && rollbackMap[id]) {
          document.getElementById('qList').insertAdjacentHTML('beforeend', rollbackMap[id]);
        }
      });
      updateNumbers();
      updateSummary();
      syncCheckpointQuestionUi();
      if (document.querySelectorAll('.q-item').length === 0) {
        var list = document.getElementById('qList');
        if (list && !document.getElementById('qEmpty')) {
          list.insertAdjacentHTML('beforeend',
            '<div id="qEmpty" class="q-empty-msg">No questions yet. Add your first question below.</div>'
          );
        }
      }
    }

    function finalizeSavedQuestion(qid, tempId, q) {
      if (qid > 0) {
        renderQuestion(q, false, true);
        return;
      }
      if (tempId) {
        var tempEl = document.getElementById('qitem-' + tempId);
        if (tempEl) tempEl.remove();
      }
      renderQuestion(q, !document.getElementById('qitem-' + q.id), true);
    }

    function finalizeBatchQuestions(tempIds, savedQuestions) {
      savedQuestions.forEach(function(q, i) {
        var tempId = tempIds[i];
        if (tempId) {
          var tempEl = document.getElementById('qitem-' + tempId);
          if (tempEl) tempEl.remove();
        }
        renderQuestion(q, !document.getElementById('qitem-' + q.id), true);
      });
      updateNumbers();
      updateSummary();
      syncCheckpointQuestionUi();
    }

    function handleSaveFailure(qid, tempIds, rollbackMap, modalStash, message) {
      if (qid > 0 && rollbackMap[qid]) {
        var el = document.getElementById('qitem-' + qid);
        if (el) el.outerHTML = rollbackMap[qid];
      } else {
        rollbackOptimistic(tempIds);
      }
      restoreModalFromStash(modalStash);
      assessmentToast('error', message || 'Failed to save question.', 3800);
    }

    function addAnotherQuestion() {
      var collected = collectFormQuestion();
      if (!collected.ok) {
        window.KA.toast('error', collected.message);
        return;
      }
      pendingBatch.push(collected.payload);
      renderQueuedFormBlocks();
      updateBatchQueueUi();
      resetModalForm();
      window.KA.toast('success', 'Question added. Fill in the next question below.');
      var textEl = document.getElementById('mfQText');
      if (textEl) textEl.focus();
    }

    function saveQuestion() {
      if (!editReady) {
        if (window.KA && window.KA.toast) {
          window.KA.toast('error', 'Cannot save: missing assessment context: ' + missingEditContextKeys().join(', ') + '.');
        }
        return;
      }

      var qid = parseInt(document.getElementById('modalQuestionId').value, 10) || 0;
      var toSave = pendingBatch.slice();
      var current = collectFormQuestion();

      if (qid <= 0 && current.ok) {
        toSave.push(current.payload);
      } else if (qid <= 0 && pendingBatch.length === 0) {
        window.KA.toast('error', current.message || 'Please enter the question text.');
        return;
      }

      if (qid > 0) {
        if (!current.ok) {
          window.KA.toast('error', current.message);
          return;
        }
        toSave = [current.payload];
      } else if (toSave.length === 0) {
        window.KA.toast('error', 'Add at least one question to save.');
        return;
      }

      var toastCopy = getSaveToastCopy(qid, toSave.length);
      var modalStash = stashModalForRestore(qid);
      var tempIds = [];
      var rollbackMap = {};
      var isBatch = qid <= 0 && toSave.length > 1;

      setSaveBusy(true);
      assessmentToast('loading', toastCopy.loading, 0);

      toSave.forEach(function(payload, idx) {
        var tempId = 'tmp-' + Date.now() + '-' + idx;
        tempIds.push(tempId);
        if (qid > 0) {
          var existing = document.getElementById('qitem-' + qid);
          if (existing) rollbackMap[qid] = existing.outerHTML;
          renderQuestion(buildOptimisticQuestion(payload, qid), false, true);
        } else {
          renderQuestion(buildOptimisticQuestion(payload, tempId), true, true);
        }
      });
      updateNumbers();
      updateSummary();
      closeModal();

      var body = isBatch
        ? encodeBatchBody(toSave)
        : encodeSingleQuestionBody(qid > 0 ? qid : 0, toSave[0]);

      fetch(SAVE_Q_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: body,
      })
        .then(parseJsonResponse)
        .then(function(data) {
          setSaveBusy(false);

          if (data.success) {
            if (isBatch && data.questions && data.questions.length) {
              finalizeBatchQuestions(tempIds, data.questions);
            } else if (data.question) {
              finalizeSavedQuestion(qid, tempIds[0], data.question);
              updateNumbers();
              updateSummary();
              syncCheckpointQuestionUi();
            }
            assessmentToast('success', data.message || toastCopy.success, 1500);
          } else {
            handleSaveFailure(qid, tempIds, rollbackMap, modalStash, data.message);
          }
        })
        .catch(function() {
          setSaveBusy(false);
          handleSaveFailure(qid, tempIds, rollbackMap, modalStash, 'Network error. Please try again.');
        });
    }

    function escHtml(s) {
      return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function escAttr(s) {
      return String(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function renderQuestion(q, isNew, skipCounters) {
      var list = document.getElementById('qList');
      var empty = document.getElementById('qEmpty');
      if (empty) empty.remove();

      var existing = document.getElementById('qitem-' + q.id);
      var tc = TYPE_COLORS[q.question_type] || '#6dabcf';
      var label = TYPE_LABELS[q.question_type] || q.question_type;

      var choicesHtml = '';
      if (q.choices && q.choices.length > 0) {
        choicesHtml = '<div class="q-choices-preview">';
        q.choices.forEach(function(c) {
          var correct = isChoiceCorrect(c.is_correct);
          var cls = correct ? 'correct' : 'wrong';
          var prefix = correct ? '✓ ' : '';
          choicesHtml += '<span class="q-choice-chip ' + cls + '">' + prefix + escHtml(c.choice_text) + '</span>';
        });
        choicesHtml += '</div>';
      }

      var choicesData = JSON.stringify((q.choices || []).map(function(c) {
        return { id: c.id, text: c.choice_text, is_correct: c.is_correct };
      }));

      var optimisticCls = String(q.id).indexOf('tmp-') === 0 ? ' optimistic' : '';
      var html = '<div class="q-item' + optimisticCls + '" id="qitem-' + q.id + '"'
        + ' data-id="' + q.id + '"'
        + ' data-text="' + escAttr(q.question_text) + '"'
        + ' data-type="' + q.question_type + '"'
        + ' data-required="' + (q.is_required ? '1' : '0') + '"'
        + ' data-minwords="' + (q.min_words || '') + '"'
        + ' data-choices="' + escAttr(choicesData) + '">'
        + '<div class="q-item-hdr">'
        + '<div class="q-item-num">?</div>'
        + '<span class="q-item-type" style="background:' + tc + '22;color:' + tc + '">' + label + '</span>'
        + (q.is_required ? '<span style="font-size:.625rem;font-weight:700;color:#dc2626;">* Required</span>' : '')
        + '<div class="q-item-actions">'
        + '<button class="q-action-btn" onclick="editQuestion(' + q.id + ')">'
        + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>'
        + '<button class="q-action-btn danger" onclick="deleteQuestion(' + q.id + ')">'
        + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>'
        + '</div></div>'
        + '<div class="q-item-body">'
        + '<div class="q-text">' + escHtml(q.question_text).replace(/\n/g, '<br>') + '</div>'
        + (q.min_words ? '<div class="q-meta"><span>Min ' + q.min_words + ' words</span></div>' : '')
        + choicesHtml
        + '</div></div>';

      if (existing) {
        existing.outerHTML = html;
      } else {
        list.insertAdjacentHTML('beforeend', html);
      }

      if (!skipCounters) {
        updateNumbers();
        updateSummary();
      }
    }

    function updateNumbers() {
      document.querySelectorAll('.q-item').forEach(function(el, i) {
        var num = el.querySelector('.q-item-num');
        if (num) num.textContent = i + 1;
      });
      var tot = document.querySelectorAll('.q-item').length;
      var lbl = document.getElementById('qCountLabel');
      if (lbl) lbl.textContent = tot + ' question' + (tot !== 1 ? 's' : '');
    }

    function updateSummary() {
      var counts = {};
      Object.keys(TYPE_LABELS).forEach(function(k) { counts[k] = 0; });
      document.querySelectorAll('.q-item').forEach(function(el) {
        var t = el.dataset.type;
        if (counts[t] !== undefined) counts[t]++;
      });
      Object.keys(counts).forEach(function(k) {
        var el = document.getElementById('typeCount-' + k);
        if (el) el.textContent = counts[k];
      });
      var tot = document.querySelectorAll('.q-item').length;
      var totEl = document.getElementById('typeCountTotal');
      if (totEl) totEl.textContent = tot;
    }

    function deleteQuestion(qid) {
      if (!editReady) {
        if (window.KA && window.KA.toast) {
          window.KA.toast('error', 'Cannot delete: missing assessment context: ' + missingEditContextKeys().join(', ') + '.');
        }
        return;
      }
      window.KA.confirm({
        title: 'Delete this question?',
        text: 'This will remove the question and all associated answers.',
        confirmText: 'Yes, delete',
        type: 'danger',
        onConfirm: function() {
          var delBody = '';
          if (String(CSRF_NAME || '').length > 0) {
            delBody = String(CSRF_NAME) + '=' + encodeURIComponent(String(CSRF_HASH || '')) + '&';
          }
          delBody += 'question_id=' + encodeURIComponent(qid);
          fetch(DEL_Q_URL, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: delBody,
          })
            .then(parseJsonResponse)
            .then(function(data) {
              if (data.success) {
                var el = document.getElementById('qitem-' + qid);
                if (el) el.remove();
                window.KA.toast('success', 'Question deleted.');
                updateNumbers();
                updateSummary();
                syncCheckpointQuestionUi();
                if (document.querySelectorAll('.q-item').length === 0) {
                  document.getElementById('qList').insertAdjacentHTML('beforeend',
                    '<div id="qEmpty" class="q-empty-msg">No questions yet. Add your first question below.</div>'
                  );
                }
              } else {
                window.KA.toast('error', data.message || 'Failed to delete.');
              }
            })
            .catch(function() {
              window.KA.toast('error', 'Network error. Please try again.');
            });
        },
      });
    }

    var ui = kaEnsureAssessmentsUi();
    ui.openModal = openModal;
    ui.closeModal = closeModal;
    ui.closeModalOutside = closeModalOutside;
    ui.onTypeChange = onTypeChange;
    ui.addChoice = addChoice;
    ui.saveQuestion = saveQuestion;
    ui.addAnotherQuestion = addAnotherQuestion;
    ui.deleteQuestion = deleteQuestion;
    ui.onEditAssessmentTypeChange = onEditAssessmentTypeChange;
    ASSESSMENT_UI_GLOBALS.forEach(function(name) {
      window[name] = ui[name];
    });

    var ax = kaEnsureAssessmentsActions();
    ax.editQuestion = editQuestion;
    window.editQuestion = ax.editQuestion;

    var addAnotherBtn = document.getElementById('addAnotherBtn');
    if (addAnotherBtn && addAnotherBtn.getAttribute('data-bound') !== '1') {
      addAnotherBtn.setAttribute('data-bound', '1');
      addAnotherBtn.addEventListener('click', function(e) {
        e.preventDefault();
        addAnotherQuestion();
      });
    }

    onTypeChange();

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeModal();
    });

    onEditAssessmentTypeChange();
    syncCheckpointQuestionUi();

    var metaForm = document.getElementById('assessmentMetaForm');
    if (metaForm && metaForm.getAttribute('data-cp-duration-bound') !== '1') {
      metaForm.setAttribute('data-cp-duration-bound', '1');
      metaForm.addEventListener('submit', function(ev) {
        if (!isCheckpointMode()) return;
        var tsEl = metaForm.querySelector('input[name="trigger_seconds"]');
        var durEl = document.getElementById('edit_video_duration_seconds');
        var tsRaw = tsEl ? String(tsEl.value).trim() : '';
        var ts = tsRaw === '' ? 0 : parseInt(tsRaw, 10);
        if (isNaN(ts) || ts < 0) return;
        if (ts < 1) return;
        var vd = durEl ? parseInt(String(durEl.value).trim(), 10) : NaN;
        if (!(vd > 0)) {
          ev.preventDefault();
          if (window.KA && typeof window.KA.toast === 'function') {
            window.KA.toast('error', 'Whole video duration is required when the timestamp is greater than zero.');
          } else {
            alert('Whole video duration is required when the timestamp is greater than zero.');
          }
          return;
        }
        if (ts > vd) {
          ev.preventDefault();
          if (window.KA && typeof window.KA.toast === 'function') {
            window.KA.toast('error', 'Checkpoint exceeds video length (' + vd + 's)');
          } else {
            alert('Checkpoint exceeds video length (' + vd + 's)');
          }
        }
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    initIndex();
    initTake();
    initCreate();
    initGrade();
    initEdit();
  });
})();
