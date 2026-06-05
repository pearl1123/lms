/**
 * Group permission matrix — save on toggle.
 */
(function () {
  'use strict';

  var cfg = window.PERM_GROUPS || {};
  if (!cfg.canEdit) return;

  function csrfParams(extra) {
    var data = extra || {};
    if (cfg.csrfName && cfg.csrfHash) {
      data[cfg.csrfName] = cfg.csrfHash;
    }
    return data;
  }

  function post(url, data) {
    var params = new URLSearchParams();
    var base = csrfParams({});
    Object.keys(base).forEach(function (k) { params.append(k, base[k]); });
    Object.keys(data || {}).forEach(function (k) {
      if (k === 'perm_ids') return;
      params.append(k, data[k]);
    });
    (data.perm_ids || []).forEach(function (id) { params.append('perm_ids[]', id); });
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: params.toString(),
      credentials: 'same-origin'
    }).then(function (r) { return r.json(); });
  }

  function collectGroupPermIds(groupId) {
    var ids = [];
    document.querySelectorAll('.umgmt-gmatrix-cb[data-group-id="' + groupId + '"]:checked').forEach(function (cb) {
      ids.push(parseInt(cb.getAttribute('data-perm-id'), 10));
    });
    return ids;
  }

  function toast(type, msg) {
    if (window.KA && typeof KA.toast === 'function') {
      KA.toast(type, msg);
      return;
    }
    if (window.Swal) {
      Swal.fire({ icon: type === 'success' ? 'success' : 'error', title: msg, toast: true, position: 'top-end', timer: 3500, showConfirmButton: false });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var syncBtn = document.getElementById('permBtnSync');
    if (syncBtn) {
      syncBtn.addEventListener('click', function () {
        syncBtn.disabled = true;
        post(cfg.baseUrl + '/sync', {}).then(function (res) {
          syncBtn.disabled = false;
          if (res.success) {
            toast('success', res.message || 'Synced.');
            window.location.reload();
          } else {
            toast('error', res.message || 'Sync failed.');
          }
        }).catch(function () {
          syncBtn.disabled = false;
          toast('error', 'Network error.');
        });
      });
    }

    document.querySelectorAll('.umgmt-gmatrix-cb').forEach(function (cb) {
      cb.addEventListener('change', function () {
        var gid = cb.getAttribute('data-group-id');
        var ids = collectGroupPermIds(gid);
        post(cfg.baseUrl + '/group_save', { group_id: gid, perm_ids: ids }).then(function (res) {
          if (res.success) {
            toast('success', res.message || 'Saved.');
          } else {
            toast('error', res.message || 'Save failed.');
            cb.checked = !cb.checked;
          }
        }).catch(function () {
          toast('error', 'Network error.');
          cb.checked = !cb.checked;
        });
      });
    });
  });
})();
