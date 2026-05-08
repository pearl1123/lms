(function (global) {
  'use strict';

  function ensureDataset(input) {
    if (!input || !Array.isArray(input.labels) || !Array.isArray(input.values) || input.labels.length === 0 || input.values.length === 0) {
      return { labels: ['No Data'], values: [0] };
    }
    return input;
  }

  function isPlaceholder(input) {
    return input && Array.isArray(input.labels) && Array.isArray(input.values) &&
      input.labels.length === 1 && input.labels[0] === 'No Data' && Number(input.values[0]) === 0;
  }

  function renderEmpty(canvas, message) {
    if (!canvas || !canvas.parentElement) return;
    canvas.parentElement.innerHTML = '<div class="db-chart-empty">' + (message || 'No data available yet') + '</div>';
  }

  function initInstructorCharts(data) {
    if (typeof Chart === 'undefined') return;
    data = data || {};
    var progress = ensureDataset(data.course_progress_distribution);
    var performance = ensureDataset(data.student_performance);
    var enroll = ensureDataset(data.enrollment_per_course);
    var completed = ensureDataset(data.completion_distribution);

    var canvas1 = document.getElementById('instructorCourseProgressChart');
    var canvas2 = document.getElementById('instructorStudentPerformanceChart');
    var canvas3 = document.getElementById('instructorEnrollmentChart');
    var canvas4 = document.getElementById('instructorCompletionDistChart');
    var ctx1 = canvas1 ? canvas1.getContext('2d') : null;
    var ctx2 = canvas2 ? canvas2.getContext('2d') : null;
    var ctx3 = canvas3 ? canvas3.getContext('2d') : null;
    var ctx4 = canvas4 ? canvas4.getContext('2d') : null;

    var chartOpts = { responsive: true, maintainAspectRatio: false };

    if (ctx1 && !isPlaceholder(progress)) {
      new Chart(ctx1, {
        type: 'bar',
        data: {
          labels: progress.labels,
          datasets: [{ label: 'Avg course progress %', data: progress.values, backgroundColor: '#6dabcf', borderRadius: 6 }]
        },
        options: Object.assign({}, chartOpts, {
          scales: { y: { beginAtZero: true, max: 100 }, x: { grid: { display: false } } },
          plugins: { legend: { display: false } }
        })
      });
    } else if (canvas1) {
      renderEmpty(canvas1, 'No course analytics yet');
    }

    if (ctx2 && !isPlaceholder(performance)) {
      new Chart(ctx2, {
        type: 'bar',
        data: {
          labels: performance.labels,
          datasets: [{ label: 'Modules completed', data: performance.values, backgroundColor: '#1a3a5c', borderRadius: 6 }]
        },
        options: Object.assign({}, chartOpts, {
          indexAxis: 'y',
          scales: { x: { beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } },
          plugins: { legend: { display: false } }
        })
      });
    } else if (canvas2) {
      renderEmpty(canvas2, 'No learner performance samples yet');
    }

    if (ctx3 && !isPlaceholder(enroll)) {
      new Chart(ctx3, {
        type: 'bar',
        data: {
          labels: enroll.labels,
          datasets: [{ label: 'Enrollments', data: enroll.values, backgroundColor: '#3b82f6', borderRadius: 6 }]
        },
        options: Object.assign({}, chartOpts, {
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } },
          plugins: { legend: { display: false } }
        })
      });
    } else if (canvas3) {
      renderEmpty(canvas3, 'No enrollment volume yet');
    }

    var compSum = datasetSum(completed.values);
    if (ctx4 && !isPlaceholder(completed) && compSum > 0) {
      new Chart(ctx4, {
        type: 'doughnut',
        data: {
          labels: completed.labels,
          datasets: [{
            data: completed.values,
            backgroundColor: ['#ef4444', '#f59f00', '#6dabcf', '#22c55e'],
            borderWidth: 2,
            borderColor: '#fff'
          }]
        },
        options: Object.assign({}, chartOpts, {
          plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
        })
      });
    } else if (canvas4) {
      renderEmpty(canvas4, compSum <= 0 && !isPlaceholder(completed)
        ? 'No enrollees in your courses yet'
        : 'No completion distribution yet');
    }
  }

  function initAdminCharts(data) {
    if (typeof Chart === 'undefined') return;
    data = data || {};
    var users = ensureDataset(data.users_over_time);
    var enrollment = ensureDataset(data.enrollment_trends);
    var completion = ensureDataset(data.course_completion_overview);
    var certs = ensureDataset(data.certificate_issuance_trend);

    var cUsers = document.getElementById('adminUsersGrowthChart');
    var cEnr = document.getElementById('adminEnrollmentTrendChart');
    var cComp = document.getElementById('adminCompletionOverviewChart');
    var cCert = document.getElementById('adminCertificateTrendChart');

    function lineTrend(canvas, ds, label, clr, bg) {
      var ctx = canvas.getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: ds.labels,
          datasets: [{
            label: label,
            data: ds.values,
            borderColor: clr,
            backgroundColor: bg,
            fill: true,
            tension: 0.35,
            pointRadius: 3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } }
          },
          plugins: { legend: { display: false } }
        }
      });
    }

    if (cUsers && !isPlaceholder(users)) {
      lineTrend(cUsers, users, 'New users', '#1a3a5c', 'rgba(26,58,92,0.12)');
    } else if (cUsers) renderEmpty(cUsers, 'No user growth history yet');

    if (cEnr && !isPlaceholder(enrollment)) {
      var ctxE = cEnr.getContext('2d');
      new Chart(ctxE, {
        type: 'bar',
        data: {
          labels: enrollment.labels,
          datasets: [{ label: 'Enrollments', data: enrollment.values, backgroundColor: '#6dabcf', borderRadius: 6 }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } }
          },
          plugins: { legend: { display: false } }
        }
      });
    } else if (cEnr) renderEmpty(cEnr, 'No enrollment history yet');

    var compSum = datasetSum(completion.values);
    if (cComp && !isPlaceholder(completion) && compSum > 0) {
      var ctxC = cComp.getContext('2d');
      new Chart(ctxC, {
        type: 'doughnut',
        data: {
          labels: completion.labels,
          datasets: [{
            data: completion.values,
            backgroundColor: ['#3b82f6', '#22c55e', '#f59f00'],
            borderWidth: 2,
            borderColor: '#fff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
        }
      });
    } else if (cComp) {
      renderEmpty(cComp, compSum <= 0 && !isPlaceholder(completion)
        ? 'Not enough completion samples'
        : 'No completion overview yet');
    }

    if (cCert && !isPlaceholder(certs)) {
      lineTrend(cCert, certs, 'Certificates', '#22c55e', 'rgba(34,197,94,0.15)');
    } else if (cCert) renderEmpty(cCert, 'No certificate issuance history yet');
  }

  function datasetSum(vals) {
    var s = 0;
    var i;
    for (i = 0; i < (vals ? vals.length : 0); i++) {
      s += Number(vals[i]) || 0;
    }

    return s;
  }

  function initStudentCharts(data) {
    if (typeof Chart === 'undefined') return;
    data = data || {};
    var trend = ensureDataset(data.learning_progress_trend);
    var dist = ensureDataset(data.course_completion_distribution);
    var weekly = ensureDataset(data.weekly_learning_activity);

    var canvasTrend = document.getElementById('studentLearningTrendChart');
    var canvasDist = document.getElementById('studentCourseDistChart');
    var canvasWeek = document.getElementById('studentWeeklyChart');
    var ctxT = canvasTrend ? canvasTrend.getContext('2d') : null;
    var ctxD = canvasDist ? canvasDist.getContext('2d') : null;
    var ctxW = canvasWeek ? canvasWeek.getContext('2d') : null;

    if (ctxT && !isPlaceholder(trend)) {
      new Chart(ctxT, {
        type: 'line',
        data: {
          labels: trend.labels,
          datasets: [{
            label: 'Modules completed (cumulative)',
            data: trend.values,
            borderColor: '#1a3a5c',
            backgroundColor: 'rgba(26,58,92,0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointBackgroundColor: '#1a3a5c'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } }
          },
          plugins: { legend: { display: false } }
        }
      });
    } else if (canvasTrend) {
      renderEmpty(canvasTrend, 'No completed modules tracked yet');
    }

    var distSum = datasetSum(dist.values);
    if (ctxD && !isPlaceholder(dist) && distSum > 0) {
      new Chart(ctxD, {
        type: 'doughnut',
        data: {
          labels: dist.labels,
          datasets: [{
            data: dist.values,
            backgroundColor: ['#22c55e', '#6dabcf', '#cbd5e1'],
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
        }
      });
    } else if (canvasDist) {
      renderEmpty(canvasDist, datasetSum(dist.values) === 0 && !isPlaceholder(dist)
        ? 'Enroll in a course to see your mix of progress'
        : 'No enrollment data yet');
    }

    var weekSum = datasetSum(weekly.values);
    if (ctxW && !isPlaceholder(weekly) && weekSum > 0) {
      new Chart(ctxW, {
        type: 'bar',
        data: {
          labels: weekly.labels,
          datasets: [{
            label: 'Modules finished',
            data: weekly.values,
            backgroundColor: '#3b82f6',
            borderRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } }
          },
          plugins: { legend: { display: false } }
        }
      });
    } else if (canvasWeek) {
      renderEmpty(canvasWeek, 'No completions in the last 7 days yet');
    }
  }

  global.initInstructorCharts = initInstructorCharts;
  global.initAdminCharts = initAdminCharts;
  global.initStudentCharts = initStudentCharts;
})(window);
