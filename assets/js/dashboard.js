(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    var tabsRoot = document.getElementById('ecTabs');
    if (!tabsRoot) return;

    document.querySelectorAll('.ec-tab').forEach(function(tab) {
      tab.addEventListener('click', function() {
        document.querySelectorAll('.ec-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.ec-pane').forEach(function(p) { p.classList.remove('active'); });
        tab.classList.add('active');
        var pane = document.getElementById('pane-' + tab.dataset.pane);
        if (pane) pane.classList.add('active');
      });
    });

    var search = document.getElementById('ecSearch');
    var filterCat = document.getElementById('ecFilterCat');
    var filterProgress = document.getElementById('ecFilterProgress');
    if (!search || !filterCat || !filterProgress) return;

    function applyFilters() {
      var keyword = search.value.toLowerCase().trim();
      var cat = filterCat.value;
      var progress = filterProgress.value;

      document.querySelectorAll('#enrolledGrid [data-title], #availableGrid [data-title]').forEach(function(el) {
        var title = el.dataset.title || '';
        var elCat = el.dataset.cat || '';
        var elProg = el.dataset.progress || '';

        var matchTitle = !keyword || title.indexOf(keyword) !== -1;
        var matchCat = !cat || elCat === cat;
        var matchProgress = !progress || elProg === progress;

        el.style.display = (matchTitle && matchCat && matchProgress) ? '' : 'none';
      });
    }

    search.addEventListener('input', applyFilters);
    filterCat.addEventListener('change', applyFilters);
    filterProgress.addEventListener('change', applyFilters);
  });
})();
