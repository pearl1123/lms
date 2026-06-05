<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<script>
(function () {
  function cloneRow(containerId, selector) {
    var c = document.getElementById(containerId);
    var row = c && c.querySelector(selector);
    if (!c || !row) return;
    var clone = row.cloneNode(true);
    clone.querySelectorAll('input').forEach(function (inp) {
      if (inp.type === 'hidden' && inp.name === 'signatory_id[]') inp.value = '0';
      if (inp.type === 'hidden' && inp.name === 'batch_id[]') inp.value = '0';
      else if (inp.type !== 'hidden') inp.value = '';
    });
    clone.querySelectorAll('select').forEach(function (sel) { sel.selectedIndex = 0; });
    c.appendChild(clone);
  }
  var addSig = document.getElementById('p3AddSignatory');
  if (addSig) addSig.addEventListener('click', function () { cloneRow('p3SignatoryRows', '[data-p3-signatory-row]'); });
  var addBatch = document.getElementById('p3AddBatch');
  if (addBatch) addBatch.addEventListener('click', function () { cloneRow('p3BatchRows', '[data-p3-batch-row]'); });
})();
</script>
