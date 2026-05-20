(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    var tabsRoot = document.getElementById('ecTabs');
    if (!tabsRoot) return;

    var search = document.getElementById('ecSearch');
    var filterCat = document.getElementById('ecFilterCat');
    var filterProgress = document.getElementById('ecFilterProgress');
    if (!search || !filterCat) return;

    function applyFilters() {
      var keyword = search.value.toLowerCase().trim();
      var cat = filterCat.value;
      var progress = filterProgress ? filterProgress.value : '';
      var activePane = document.querySelector('.ec-pane.active');
      var paneId = activePane ? activePane.id : '';

      document.querySelectorAll(
        '#enrolledGrid [data-title], #invitedGrid [data-title], #availableGrid [data-title]'
      ).forEach(function(el) {
        var title = el.dataset.title || '';
        var elCat = el.dataset.cat || '';
        var elProg = el.dataset.progress || '';
        var isEnrolledPane = paneId === 'pane-enrolled';

        var matchTitle = !keyword || title.indexOf(keyword) !== -1;
        var matchCat = !cat || elCat === cat;
        var matchProgress = !isEnrolledPane || !progress || elProg === progress;

        el.style.display = (matchTitle && matchCat && matchProgress) ? '' : 'none';
      });
    }

    document.querySelectorAll('.ec-tab').forEach(function(tab) {
      tab.addEventListener('click', function() {
        document.querySelectorAll('.ec-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.ec-pane').forEach(function(p) { p.classList.remove('active'); });
        tab.classList.add('active');
        var pane = document.getElementById('pane-' + tab.dataset.pane);
        if (pane) pane.classList.add('active');
        applyFilters();
      });
    });

    search.addEventListener('input', applyFilters);
    filterCat.addEventListener('change', applyFilters);
    if (filterProgress) {
      filterProgress.addEventListener('change', applyFilters);
    }
  });
})();
