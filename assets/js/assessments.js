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

  function initIndex() {
    var search = document.getElementById('asxSearch');
    var typeEl = document.getElementById('asxFilterType');
    var countEl = document.getElementById('asxCount');
    if (!search || !typeEl || !countEl) return;
    var cards = document.querySelectorAll('.asx-card');
    if (!cards.length) return;

    function filter() {
      var kw = search.value.toLowerCase().trim();
      var type = typeEl.value;
      var vis = 0;
      cards.forEach(function(c) {
        var title = c.dataset.title || '';
        var ctype = c.dataset.type || '';
        var show = (!kw || title.indexOf(kw) !== -1) && (!type || ctype === type);
        c.style.display = show ? '' : 'none';
        if (show) vis++;
      });
      countEl.textContent = vis + ' assessment' + (vis !== 1 ? 's' : '');
    }

    search.addEventListener('input', filter);
    typeEl.addEventListener('change', filter);
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
      var d = document.getElementById('checkpointDurationWrap');
      var m = document.getElementById('checkpointManualTriggerWrap');
      if (d) d.style.display = on ? 'block' : 'none';
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
  }

  function initGrade() {
    var row = document.querySelector('.grd-score-row');
    if (!row) return;
    var C = (window.APP_CONTEXT && window.APP_CONTEXT.assessments && window.APP_CONTEXT.assessments.grade) || {};
    var saveUrl = C.saveGradeUrl;
    var csrfName = C.csrfFieldName;
    var csrfHash = C.csrfHash;
    if (!saveUrl || !csrfName || !csrfHash) return;

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
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: csrfName + '=' + encodeURIComponent(csrfHash)
          + '&answer_id=' + encodeURIComponent(answerId)
          + '&score=' + encodeURIComponent(score),
      })
        .then(function(r) { return r.json(); })
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
    var ASSESSMENT_ID = C.assessmentId;
    var CSRF_NAME = C.csrfFieldName;
    var CSRF_HASH = C.csrfHash;
    var SAVE_Q_URL = C.saveQuestionUrl;
    var DEL_Q_URL = C.deleteQuestionUrl;
    var TYPE_COLORS = C.typeColors || {};
    var TYPE_LABELS = C.typeLabels || {};
    if (!SAVE_Q_URL || !DEL_Q_URL || !CSRF_NAME || !CSRF_HASH || !ASSESSMENT_ID) return;

    function isCheckpointMode() {
      var sel = document.getElementById('editAssessmentType');
      return sel && sel.value === 'checkpoint';
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

    function openModal(qid) {
      if (isCheckpointMode() && !qid && document.querySelectorAll('.q-item').length >= 1) {
        window.KA.toast('error', 'This video checkpoint already has a question.');
        return;
      }
      document.getElementById('qModalOverlay').classList.add('open');
      document.getElementById('modalQuestionId').value = qid || 0;
      document.getElementById('modalTitle').textContent = qid ? 'Edit Question' : 'Add Question';
      document.getElementById('modalSaveText').textContent = qid ? 'Update Question' : 'Add Question';

      if (!qid) {
        document.getElementById('mfQText').value = '';
        document.getElementById('mfQType').value = 'multiple_choice';
        document.getElementById('mfRequired').checked = true;
        document.getElementById('mfMinWords').value = '';
        document.getElementById('choicesList').innerHTML = '';
        addChoice();
        addChoice();
        onTypeChange();
      }
      var typeRow = document.getElementById('mfQTypeRow');
      if (typeRow) typeRow.style.display = isCheckpointMode() ? 'none' : '';
      if (isCheckpointMode()) {
        document.getElementById('mfQType').value = 'multiple_choice';
        onTypeChange();
      }
    }

    function editQuestion(qid) {
      var el = document.getElementById('qitem-' + qid);
      if (!el) return;

      document.getElementById('mfQText').value = el.dataset.text;
      document.getElementById('mfQType').value = el.dataset.type;
      document.getElementById('mfRequired').checked = el.dataset.required === '1';
      document.getElementById('mfMinWords').value = el.dataset.minwords || '';

      var choices = JSON.parse(el.dataset.choices || '[]');
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
      correctBtn.className = 'choice-correct-btn' + (isCorrect ? ' active' : '');
      correctBtn.title = 'Mark as correct';
      correctBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>';
      correctBtn.onclick = function() {
        if (document.getElementById('mfQType').value === 'multiple_choice') {
          list.querySelectorAll('.choice-correct-btn').forEach(function(b) { b.classList.remove('active'); });
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

    function saveQuestion() {
      var qid = parseInt(document.getElementById('modalQuestionId').value, 10) || 0;
      var text = document.getElementById('mfQText').value.trim();
      var type = document.getElementById('mfQType').value;

      if (!text) { window.KA.toast('error', 'Please enter the question text.'); return; }

      var choices = [];
      document.querySelectorAll('#choicesList .choice-row').forEach(function(row) {
        var t = row.querySelector('.choice-text-input').value.trim();
        var ok = row.querySelector('.choice-correct-btn').classList.contains('active');
        if (t) choices.push({ text: t, is_correct: ok ? 1 : 0 });
      });

      if (type === 'multiple_choice') {
        if (choices.length < 2) { window.KA.toast('error', 'Add at least 2 choices.'); return; }
        if (!choices.some(function(c) { return c.is_correct; })) {
          window.KA.toast('error', 'Mark one choice as the correct answer.'); return;
        }
      }
      if (isCheckpointMode() && type !== 'multiple_choice') {
        window.KA.toast('error', 'Video checkpoints only support multiple choice.');
        return;
      }
      if (type === 'fill_blank' && choices.length === 0) {
        window.KA.toast('error', 'Add at least one accepted answer.'); return;
      }

      var saveBtn = document.querySelector('.q-modal-save');
      saveBtn.disabled = true;
      document.getElementById('modalSaveText').textContent = 'Saving…';

      var body = CSRF_NAME + '=' + encodeURIComponent(CSRF_HASH)
        + '&assessment_id=' + encodeURIComponent(ASSESSMENT_ID)
        + '&question_id=' + encodeURIComponent(qid)
        + '&question_text=' + encodeURIComponent(text)
        + '&question_type=' + encodeURIComponent(type)
        + '&is_required=' + (document.getElementById('mfRequired').checked ? 1 : 0)
        + '&min_words=' + (parseInt(document.getElementById('mfMinWords').value, 10) || 0);

      choices.forEach(function(c, i) {
        body += '&choices[' + i + '][text]=' + encodeURIComponent(c.text);
        body += '&choices[' + i + '][is_correct]=' + c.is_correct;
      });

      fetch(SAVE_Q_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body,
      })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          saveBtn.disabled = false;
          document.getElementById('modalSaveText').textContent = qid ? 'Update Question' : 'Add Question';

          if (data.success) {
            closeModal();
            window.KA.toast('success', data.message);
            renderQuestion(data.question, qid === 0);
            syncCheckpointQuestionUi();
          } else {
            window.KA.toast('error', data.message || 'Failed to save question.');
          }
        })
        .catch(function() {
          saveBtn.disabled = false;
          document.getElementById('modalSaveText').textContent = qid ? 'Update Question' : 'Add Question';
          window.KA.toast('error', 'Network error. Please try again.');
        });
    }

    function escHtml(s) {
      return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function escAttr(s) {
      return String(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function renderQuestion(q, isNew) {
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
          var cls = c.is_correct ? 'correct' : 'wrong';
          var prefix = c.is_correct ? '✓ ' : '';
          choicesHtml += '<span class="q-choice-chip ' + cls + '">' + prefix + escHtml(c.choice_text) + '</span>';
        });
        choicesHtml += '</div>';
      }

      var choicesData = JSON.stringify((q.choices || []).map(function(c) {
        return { id: c.id, text: c.choice_text, is_correct: c.is_correct };
      }));

      var html = '<div class="q-item" id="qitem-' + q.id + '"'
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

      updateNumbers();
      updateSummary();
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
      window.KA.confirm({
        title: 'Delete this question?',
        text: 'This will remove the question and all associated answers.',
        confirmText: 'Yes, delete',
        type: 'danger',
        onConfirm: function() {
          fetch(DEL_Q_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: CSRF_NAME + '=' + encodeURIComponent(CSRF_HASH) + '&question_id=' + encodeURIComponent(qid),
          })
            .then(function(r) { return r.json(); })
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
    ui.deleteQuestion = deleteQuestion;
    ui.onEditAssessmentTypeChange = onEditAssessmentTypeChange;
    window.openModal = ui.openModal;
    window.closeModal = ui.closeModal;
    window.closeModalOutside = ui.closeModalOutside;
    window.onTypeChange = ui.onTypeChange;
    window.addChoice = ui.addChoice;
    window.saveQuestion = ui.saveQuestion;
    window.deleteQuestion = ui.deleteQuestion;
    window.onEditAssessmentTypeChange = ui.onEditAssessmentTypeChange;

    var ax = kaEnsureAssessmentsActions();
    ax.editQuestion = editQuestion;
    window.editQuestion = ax.editQuestion;

    onTypeChange();

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeModal();
    });

    onEditAssessmentTypeChange();
    syncCheckpointQuestionUi();
  }

  document.addEventListener('DOMContentLoaded', function() {
    initIndex();
    initTake();
    initCreate();
    initGrade();
    initEdit();
  });
})();
