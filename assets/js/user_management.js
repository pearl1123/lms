/**
 * LMS User Management — Aauth access UI
 */
(function () {
  'use strict';

  var cfg = window.UMGMT || {};
  var state = {
    userId: null,
    grantIds: [],
    denyIds: [],
    modal: null
  };

  function csrfPayload(extra) {
    var data = extra || {};
    if (cfg.csrfName && cfg.csrfHash) {
      data[cfg.csrfName] = cfg.csrfHash;
    }
    return data;
  }

  function toast(type, msg) {
    if (window.KA && typeof KA.toast === 'function') {
      KA.toast(type, msg);
      return;
    }
    if (window.Swal) {
      Swal.fire({ icon: type === 'success' ? 'success' : 'error', title: msg, toast: true, position: 'top-end', timer: 4000, showConfirmButton: false });
    }
  }

  function confirmAction(title, text) {
    if (window.KA_SWAL && typeof KA_SWAL.SwalConfirm === 'function') {
      return KA_SWAL.SwalConfirm({ title: title, text: text, icon: 'question' });
    }
    if (window.Swal) {
      return Swal.fire({ title: title, text: text, icon: 'question', showCancelButton: true, confirmButtonText: 'Yes' });
    }
    return Promise.resolve({ isConfirmed: window.confirm(text || title) });
  }

  function post(url, data) {
    var params = new URLSearchParams();
    var base = csrfPayload({});
    Object.keys(base).forEach(function (k) { params.append(k, base[k]); });
    Object.keys(data || {}).forEach(function (k) {
      if (k === 'grant_ids' || k === 'deny_ids') return;
      params.append(k, data[k]);
    });
    (data.grant_ids || []).forEach(function (id) { params.append('grant_ids[]', id); });
    (data.deny_ids || []).forEach(function (id) { params.append('deny_ids[]', id); });
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: params.toString(),
      credentials: 'same-origin'
    }).then(function (r) { return r.json(); });
  }

  function get(url) {
    return fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); });
  }

  function setLoading(on) {
    document.getElementById('umgmtModalLoading').classList.toggle('d-none', !on);
    document.getElementById('umgmtModalContent').classList.toggle('d-none', on);
  }

  function renderUserInfo(user) {
    var el = document.getElementById('umgmtUserInfo');
    var fields = [
      ['Employee ID', user.employee_id],
      ['Full Name', user.fullname],
      ['Email', user.email],
      ['Office', user.office || '—'],
      ['Status', user.status],
      ['LMS Role', user.role],
      ['Last Login', user.last_login || 'Never']
    ];
    el.innerHTML = fields.map(function (f) {
      return '<div class="umgmt-info-item"><label>' + escapeHtml(f[0]) + '</label><span>' + escapeHtml(String(f[1] || '—')) + '</span></div>';
    }).join('');
    document.getElementById('umgmtAccessModalLabel').textContent = 'Manage Access — ' + (user.fullname || '');
    document.getElementById('umgmtModalUserSubtitle').textContent = user.employee_id || '';
  }

  function renderGroups(groups, allGroups) {
    var list = document.getElementById('umgmtAssignedGroups');
    var assignedIds = {};
    if (!groups.length) {
      list.innerHTML = '<li class="umgmt-empty-chip">No groups assigned.</li>';
    } else {
      list.innerHTML = groups.map(function (g) {
        assignedIds[g.id] = true;
        return '<li data-group-id="' + g.id + '"><span><strong>' + escapeHtml(g.name) + '</strong></span>' +
          '<button type="button" class="libx-action libx-action-delete umgmt-btn-remove-group" data-group-id="' + g.id + '">Remove</button></li>';
      }).join('');
    }

    var sel = document.getElementById('umgmtAddGroupSelect');
    sel.innerHTML = '<option value="">Select group…</option>' + (allGroups || []).filter(function (g) {
      return !assignedIds[g.id];
    }).map(function (g) {
      return '<option value="' + g.id + '">' + escapeHtml(g.name) + '</option>';
    }).join('');
  }

  function cellHtml(cell, colKey) {
    if (!cell) return '<span class="text-muted">—</span>';
    var pid = cell.perm_id;
    var gOn = state.grantIds.indexOf(pid) !== -1;
    var dOn = state.denyIds.indexOf(pid) !== -1;
    return '<div class="umgmt-cell-checks" data-perm-id="' + pid + '">' +
      '<label><input type="checkbox" class="umgmt-grant" data-perm-id="' + pid + '" ' + (gOn ? 'checked' : '') + '> Grant</label>' +
      '<label><input type="checkbox" class="umgmt-deny" data-perm-id="' + pid + '" ' + (dOn ? 'checked' : '') + '> Deny</label>' +
      '</div>';
  }

  function renderTree(tree) {
    var root = document.getElementById('umgmtPermTree');
    if (!root) return;
    var html = '';
    (tree || []).forEach(function (mod) {
      html += '<div class="umgmt-tree-module"><div class="umgmt-tree-module-name">' + escapeHtml(mod.name || '') + '</div><ul class="umgmt-tree-subs">';
      (mod.submodules || []).forEach(function (sub) {
        html += '<li class="umgmt-tree-sub"><span class="umgmt-tree-sub-name">' + escapeHtml(sub.name || '') + '</span><ul class="umgmt-tree-actions">';
        (sub.actions || []).forEach(function (act) {
          html += '<li>' + cellHtml(act, act.label) + ' <code class="umgmt-tree-code">' + escapeHtml(act.name || '') + '</code></li>';
        });
        html += '</ul></li>';
      });
      html += '</ul></div>';
    });
    root.innerHTML = html || '<p class="libx-empty">No permission modules configured.</p>';
    root.querySelectorAll('.umgmt-grant').forEach(function (cb) { cb.addEventListener('change', onGrantChange); });
    root.querySelectorAll('.umgmt-deny').forEach(function (cb) { cb.addEventListener('change', onDenyChange); });
  }

  function renderMatrix(matrix) {
    var tbody = document.querySelector('#umgmtPermMatrix tbody');
    if (!tbody) return;
    var html = '';
    (matrix || []).forEach(function (row) {
      var cells = row.cells || {};
      var extras = cells.extra || [];
      var extraHtml = extras.length
        ? extras.map(function (ex) {
            return cellHtml(ex, 'extra');
          }).join('')
        : '<span class="text-muted">—</span>';

      html += '<tr>' +
        '<td class="umgmt-module">' + escapeHtml(row.module_main || '') + '</td>' +
        '<td>' + escapeHtml(row.module_sub || '') + '</td>' +
        '<td>' + cellHtml(cells.view, 'view') + '</td>' +
        '<td>' + cellHtml(cells.add, 'add') + '</td>' +
        '<td>' + cellHtml(cells.edit, 'edit') + '</td>' +
        '<td>' + cellHtml(cells.delete, 'delete') + '</td>' +
        '<td>' + extraHtml + '</td>' +
        '</tr>';
    });
    tbody.innerHTML = html || '<tr><td colspan="7" class="libx-empty">No permission modules configured.</td></tr>';

    tbody.querySelectorAll('.umgmt-grant').forEach(function (cb) {
      cb.addEventListener('change', onGrantChange);
    });
    tbody.querySelectorAll('.umgmt-deny').forEach(function (cb) {
      cb.addEventListener('change', onDenyChange);
    });
  }

  function onGrantChange(e) {
    var pid = parseInt(e.target.getAttribute('data-perm-id'), 10);
    if (e.target.checked) {
      if (state.grantIds.indexOf(pid) === -1) state.grantIds.push(pid);
      state.denyIds = state.denyIds.filter(function (id) { return id !== pid; });
    } else {
      state.grantIds = state.grantIds.filter(function (id) { return id !== pid; });
    }
    renderMatrix(window._umgmtLastMatrix);
    renderTree(window._umgmtLastTree);
  }

  function onDenyChange(e) {
    var pid = parseInt(e.target.getAttribute('data-perm-id'), 10);
    if (e.target.checked) {
      if (state.denyIds.indexOf(pid) === -1) state.denyIds.push(pid);
      state.grantIds = state.grantIds.filter(function (id) { return id !== pid; });
    } else {
      state.denyIds = state.denyIds.filter(function (id) { return id !== pid; });
    }
    renderMatrix(window._umgmtLastMatrix);
    renderTree(window._umgmtLastTree);
  }

  function renderEffective(effective) {
    fillChips('umgmtEffGroups', effective.from_groups || []);
    fillChips('umgmtEffGranted', effective.granted || []);
    fillChips('umgmtEffDenied', effective.denied || []);
    fillChips('umgmtEffFinal', effective.effective || []);
  }

  function fillChips(id, items) {
    var ul = document.getElementById(id);
    if (!items.length) {
      ul.innerHTML = '<li class="umgmt-empty-chip">None</li>';
      return;
    }
    ul.innerHTML = items.map(function (p) {
      return '<li title="' + escapeHtml(p.definition || '') + '">' + escapeHtml(p.name) + '</li>';
    }).join('');
  }

  function applyPayload(data) {
    state.grantIds = (data.grant_ids || []).map(Number);
    state.denyIds = (data.deny_ids || []).map(Number);
    if (data.matrix) window._umgmtLastMatrix = data.matrix;
    if (data.tree) window._umgmtLastTree = data.tree;
    renderGroups(data.groups || [], data.all_groups || []);
    if (window._umgmtLastTree) renderTree(window._umgmtLastTree);
    if (window._umgmtLastMatrix) renderMatrix(window._umgmtLastMatrix);
    if (data.effective) renderEffective(data.effective);
  }

  function openAccess(userId) {
    state.userId = userId;
    setLoading(true);
    if (!state.modal && window.bootstrap) {
      state.modal = new bootstrap.Modal(document.getElementById('umgmtAccessModal'));
    }
    if (state.modal) state.modal.show();

    get(cfg.baseUrl + '/access_data/' + userId).then(function (res) {
      setLoading(false);
      if (!res.success) {
        toast('error', res.message || 'Failed to load access data.');
        return;
      }
      renderUserInfo(res.user);
      applyPayload(res);
      if (state.viewOnly && window.bootstrap) {
        var tab = document.querySelector('[data-bs-target="#umgmtTabEffective"]');
        if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
      }
      document.getElementById('umgmtBtnSavePerms').style.display = state.viewOnly ? 'none' : '';
      document.getElementById('umgmtBtnAddGroup').style.display = state.viewOnly ? 'none' : '';
    }).catch(function () {
      setLoading(false);
      toast('error', 'Network error loading access data.');
    });
  }

  function updateRowGroupLabels(userId, labels) {
    var cell = document.querySelector('.umgmt-groups-cell[data-user-id="' + userId + '"]');
    if (cell) cell.textContent = labels || '—';
  }

  function escapeHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.umgmt-btn-access').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openAccess(parseInt(btn.getAttribute('data-user-id'), 10), false);
      });
    });
    document.querySelectorAll('.umgmt-btn-view-access').forEach(function (btn) {
      btn.addEventListener('click', function () {
        openAccess(parseInt(btn.getAttribute('data-user-id'), 10), true);
      });
    });

    document.getElementById('umgmtBtnAddGroup').addEventListener('click', function () {
      var gid = parseInt(document.getElementById('umgmtAddGroupSelect').value, 10);
      if (!gid || !state.userId) return;
      confirmAction('Add to group?', 'Assign this user to the selected group?').then(function (r) {
        if (!r.isConfirmed) return;
        post(cfg.baseUrl + '/group_add', { user_id: state.userId, group_id: gid }).then(function (res) {
          if (res.success) {
            toast('success', res.message);
            applyPayload(res);
            updateRowGroupLabels(state.userId, res.group_labels);
          } else toast('error', res.message || 'Failed.');
        });
      });
    });

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.umgmt-btn-remove-group');
      if (!btn || !state.userId) return;
      var gid = parseInt(btn.getAttribute('data-group-id'), 10);
      confirmAction('Remove from group?', 'Remove this user from the group?').then(function (r) {
        if (!r.isConfirmed) return;
        post(cfg.baseUrl + '/group_remove', { user_id: state.userId, group_id: gid }).then(function (res) {
          if (res.success) {
            toast('success', res.message);
            applyPayload(res);
            updateRowGroupLabels(state.userId, res.group_labels);
          } else toast('error', res.message || 'Failed.');
        });
      });
    });

    document.getElementById('umgmtBtnSavePerms').addEventListener('click', function () {
      if (!state.userId) return;
      confirmAction('Save permissions?', 'Update direct grant/deny overrides for this user?').then(function (r) {
        if (!r.isConfirmed) return;
        var btn = document.getElementById('umgmtBtnSavePerms');
        btn.disabled = true;
        post(cfg.baseUrl + '/permissions_save', {
          user_id: state.userId,
          grant_ids: state.grantIds,
          deny_ids: state.denyIds
        }).then(function (res) {
          btn.disabled = false;
          if (res.success) {
            toast('success', res.message);
            state.grantIds = (res.grant_ids || []).map(Number);
            state.denyIds = (res.deny_ids || []).map(Number);
            renderEffective(res.effective || {});
            renderMatrix(window._umgmtLastMatrix);
          } else toast('error', res.message || 'Save failed.');
        }).catch(function () {
          btn.disabled = false;
          toast('error', 'Network error.');
        });
      });
    });
  });
})();
