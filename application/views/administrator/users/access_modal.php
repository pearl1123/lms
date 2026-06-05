<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="modal fade" id="umgmtAccessModal" tabindex="-1" aria-labelledby="umgmtAccessModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content umgmt-modal">
      <div class="modal-header">
        <div>
          <h5 class="modal-title" id="umgmtAccessModalLabel">Manage Access</h5>
          <p class="umgmt-modal-sub mb-0" id="umgmtModalUserSubtitle">—</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="umgmtModalLoading" class="umgmt-loading d-none">
          <div class="umgmt-spinner"></div>
          <span>Loading access data…</span>
        </div>

        <div id="umgmtModalContent" class="d-none">
          <ul class="nav nav-tabs umgmt-tabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#umgmtTabOverview" type="button">Overview</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#umgmtTabGroups" type="button">Groups</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#umgmtTabPerms" type="button">Permissions</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#umgmtTabEffective" type="button">Effective Access</button></li>
          </ul>

          <div class="tab-content umgmt-tab-panels">
            <div class="tab-pane fade show active" id="umgmtTabOverview" role="tabpanel">
              <div class="umgmt-info-grid" id="umgmtUserInfo"></div>
            </div>

            <div class="tab-pane fade" id="umgmtTabGroups" role="tabpanel">
              <div class="umgmt-group-toolbar">
                <select id="umgmtAddGroupSelect" class="libx-input umgmt-select"></select>
                <button type="button" class="libx-btn libx-btn-primary" id="umgmtBtnAddGroup">Add to Group</button>
              </div>
              <ul class="umgmt-group-list" id="umgmtAssignedGroups"></ul>
            </div>

            <div class="tab-pane fade" id="umgmtTabPerms" role="tabpanel">
              <p class="umgmt-hint">Grant adds a user override (<code>aauth_perm_to_user</code>). Deny blocks permission even if inherited from a group.</p>
              <div class="umgmt-perm-tree-wrap" id="umgmtPermTree"></div>
              <details class="umgmt-matrix-details mt-3">
                <summary>Table view</summary>
                <div class="umgmt-matrix-wrap">
                  <table class="umgmt-matrix" id="umgmtPermMatrix">
                    <thead>
                      <tr>
                        <th>Module</th>
                        <th>Submodule</th>
                        <th>View</th>
                        <th>Add</th>
                        <th>Edit</th>
                        <th>Delete</th>
                        <th>Extra</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </details>
              <div class="umgmt-matrix-actions">
                <button type="button" class="libx-btn libx-btn-primary" id="umgmtBtnSavePerms">Save Permission Overrides</button>
              </div>
            </div>

            <div class="tab-pane fade" id="umgmtTabEffective" role="tabpanel">
              <div class="umgmt-effective-grid">
                <div class="umgmt-effective-col">
                  <h6>From groups</h6>
                  <ul id="umgmtEffGroups" class="umgmt-perm-chips"></ul>
                </div>
                <div class="umgmt-effective-col">
                  <h6>Granted directly</h6>
                  <ul id="umgmtEffGranted" class="umgmt-perm-chips"></ul>
                </div>
                <div class="umgmt-effective-col">
                  <h6>Denied directly</h6>
                  <ul id="umgmtEffDenied" class="umgmt-perm-chips"></ul>
                </div>
                <div class="umgmt-effective-col umgmt-effective-final">
                  <h6>Effective permissions</h6>
                  <ul id="umgmtEffFinal" class="umgmt-perm-chips"></ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="libx-btn libx-btn-ghost" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
