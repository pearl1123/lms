/**
 * Reports & Analytics workspace
 */
(function () {
  'use strict';

  function ensureDataset(input) {
    if (!input || !Array.isArray(input.labels) || !Array.isArray(input.values) ||
      input.labels.length === 0 || input.values.length === 0) {
      return { labels: ['No Data'], values: [0] };
    }
    return input;
  }

  function isPlaceholder(input) {
    return input && input.labels.length === 1 && input.labels[0] === 'No Data' && Number(input.values[0]) === 0;
  }

  function renderEmpty(canvas, message) {
    if (!canvas || !canvas.parentElement) return;
    canvas.parentElement.innerHTML = '<div class="rpt-chart-empty">' + (message || 'No data available yet') + '</div>';
  }

  function initSectionNav() {
    var links = document.querySelectorAll('.rpt-nav-link[data-rpt-section]');
    var sections = document.querySelectorAll('.rpt-section[data-rpt-section]');
    if (!links.length) return;

    function activate(name) {
      links.forEach(function (btn) {
        btn.classList.toggle('is-active', btn.getAttribute('data-rpt-section') === name);
      });
      sections.forEach(function (sec) {
        var on = sec.getAttribute('data-rpt-section') === name;
        sec.classList.toggle('is-active', on);
        if (on) {
          sec.removeAttribute('hidden');
        } else {
          sec.setAttribute('hidden', 'hidden');
        }
      });
      try {
        if (history.replaceState) {
          history.replaceState(null, '', '#report-' + name);
        }
      } catch (e) {}
    }

    links.forEach(function (btn) {
      btn.addEventListener('click', function () {
        activate(btn.getAttribute('data-rpt-section'));
      });
    });

    var initial = 'overview';
    var hash = (location.hash || '').replace(/^#report-/, '');
    if (hash && document.querySelector('.rpt-section[data-rpt-section="' + hash + '"]')) {
      initial = hash;
    }
    activate(initial);
  }

  function initSearch() {
    var input = document.getElementById('rptSearch');
    var sections = document.querySelectorAll('.rpt-section[data-rpt-keywords]');
    if (!input || !sections.length) return;

    input.addEventListener('input', function () {
      var q = (input.value || '').toLowerCase().trim();
      sections.forEach(function (sec) {
        if (!q) {
          sec.classList.remove('rpt-hidden-by-search');
          return;
        }
        var keys = (sec.getAttribute('data-rpt-keywords') || '').toLowerCase();
        var text = (sec.textContent || '').toLowerCase();
        sec.classList.toggle('rpt-hidden-by-search', keys.indexOf(q) === -1 && text.indexOf(q) === -1);
      });
    });
  }

  function initRangeForm() {
    var sel = document.getElementById('rptRange');
    var form = document.getElementById('rptRangeForm');
    if (!sel || !form) return;
    sel.addEventListener('change', function () {
      form.submit();
    });
  }

  function initCharts() {
    if (typeof Chart === 'undefined') return;
    var data = window.REPORTS_CHARTS || {};
    var opts = { responsive: true, maintainAspectRatio: false };

    var enroll = ensureDataset(data.enrollment_trends);
    var completion = ensureDataset(data.course_completion_overview);
    var users = ensureDataset(data.users_over_time);
    var certs = ensureDataset(data.certificate_issuance_trend);

    var c1 = document.getElementById('rptEnrollmentTrend');
    if (c1 && !isPlaceholder(enroll)) {
      new Chart(c1.getContext('2d'), {
        type: 'line',
        data: {
          labels: enroll.labels,
          datasets: [{
            label: 'Enrollments',
            data: enroll.values,
            borderColor: '#6dabcf',
            backgroundColor: 'rgba(109, 171, 207, 0.12)',
            fill: true,
            tension: 0.35
          }]
        },
        options: Object.assign({}, opts, {
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        })
      });
    } else if (c1) {
      renderEmpty(c1, 'No enrollment trends yet');
    }

    var c2 = document.getElementById('rptCompletionDist');
    if (c2 && !isPlaceholder(completion)) {
      var sum = completion.values.reduce(function (a, b) { return a + b; }, 0);
      if (sum > 0) {
        new Chart(c2.getContext('2d'), {
          type: 'doughnut',
          data: {
            labels: completion.labels,
            datasets: [{
              data: completion.values,
              backgroundColor: ['#ef4444', '#f59f00', '#22c55e'],
              borderWidth: 2,
              borderColor: '#fff'
            }]
          },
          options: Object.assign({}, opts, { plugins: { legend: { position: 'bottom' } } })
        });
      } else {
        renderEmpty(c2, 'No completion data yet');
      }
    } else if (c2) {
      renderEmpty(c2, 'No completion data yet');
    }

    var c3 = document.getElementById('rptUsersGrowth');
    if (c3 && !isPlaceholder(users)) {
      new Chart(c3.getContext('2d'), {
        type: 'bar',
        data: {
          labels: users.labels,
          datasets: [{
            label: 'New users',
            data: users.values,
            backgroundColor: '#1a3a5c',
            borderRadius: 6
          }]
        },
        options: Object.assign({}, opts, {
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        })
      });
    } else if (c3) {
      renderEmpty(c3, 'No user growth data yet');
    }

    var c4 = document.getElementById('rptCertTrend');
    if (c4 && !isPlaceholder(certs)) {
      new Chart(c4.getContext('2d'), {
        type: 'line',
        data: {
          labels: certs.labels,
          datasets: [{
            label: 'Certificates',
            data: certs.values,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.35
          }]
        },
        options: Object.assign({}, opts, {
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        })
      });
    } else if (c4) {
      renderEmpty(c4, 'No certificate issuance data yet');
    }
  }

  function initExports() {
    var rangeSel = document.getElementById('rptRange');
    var links = document.querySelectorAll('.rpt-export-link');
    if (!links.length) return;

    function syncRangeInHref(link) {
      if (!rangeSel || !link.href) return;
      try {
        var url = new URL(link.href, window.location.origin);
        url.searchParams.set('range', rangeSel.value || '30d');
        link.href = url.pathname + url.search;
      } catch (e) {}
    }

    links.forEach(function (link) {
      syncRangeInHref(link);
      link.addEventListener('click', function () {
        syncRangeInHref(link);
        link.classList.add('is-loading');
        window.setTimeout(function () {
          link.classList.remove('is-loading');
        }, 4000);
      });
    });

    if (rangeSel) {
      rangeSel.addEventListener('change', function () {
        links.forEach(syncRangeInHref);
      });
    }

    var toggle = document.getElementById('rptExportToggle');
    var menu = document.getElementById('rptExportMenu');
    if (toggle && menu) {
      toggle.addEventListener('click', function () {
        var open = menu.hasAttribute('hidden');
        if (open) {
          menu.removeAttribute('hidden');
          toggle.setAttribute('aria-expanded', 'true');
        } else {
          menu.setAttribute('hidden', 'hidden');
          toggle.setAttribute('aria-expanded', 'false');
        }
      });
      document.addEventListener('click', function (e) {
        if (!toggle.contains(e.target) && !menu.contains(e.target)) {
          menu.setAttribute('hidden', 'hidden');
          toggle.setAttribute('aria-expanded', 'false');
        }
      });
    }
  }

  function boot() {
    initSectionNav();
    initSearch();
    initRangeForm();
    initCharts();
    initExports();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
