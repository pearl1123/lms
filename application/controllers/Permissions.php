<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Group permission matrix and permission catalog sync.
 *
 * @property User_access_model      $user_access_model
 * @property Permission_seed_service $permission_seed_service
 */
class Permissions extends KA_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->require_permission(['groups.view', 'users.view'], 'dashboard');
        $this->load->model('User_access_model', 'user_access_model');
        $this->load->library('Permission_seed_service', null, 'permission_seed_service');
        $this->_auto_sync_if_empty();
    }

    public function index()
    {
        redirect('permissions/groups');
    }

    /**
     * Group × permission matrix.
     */
    public function groups()
    {
        $this->require_permission('groups.view', 'dashboard');

        $flat   = $this->user_access_model->get_flat_permissions();
        $matrix = $this->user_access_model->get_group_matrix_data();

        $this->render('administrator/permissions/group_matrix', [
            'page_title'    => 'Group Permissions',
            'flat_perms'    => $flat,
            'group_matrix'  => $matrix,
            'can_edit'      => $this->user_can('groups.manage'),
            'permissions_base' => site_url('permissions'),
        ], [
            ['label' => 'Dashboard', 'url' => site_url('dashboard')],
            ['label' => 'User Management', 'url' => site_url('users')],
            ['label' => 'Group Permissions'],
        ]);
    }

    /**
     * POST JSON — save permissions for one group.
     */
    public function group_save()
    {
        $this->require_permission('groups.manage', 'dashboard');

        $group_id  = (int) $this->post_param('group_id');
        $perm_ids  = $this->post_param('perm_ids');

        if ($group_id < 1) {
            $this->json_error('Invalid group.');

            return;
        }

        $group = $this->db->where('id', $group_id)->where('archived', 0)->get('aauth_groups', 1)->row();
        if ( ! $group) {
            $this->json_error('Group not found.');

            return;
        }

        if ( ! is_array($perm_ids)) {
            $perm_ids = [];
        }

        $this->user_access_model->save_group_permissions($group_id, $perm_ids);
        $this->load->model('Permission_model', 'permission_model');
        Permission_model::clear_cache();

        $this->json_ok([
            'message'   => 'Group permissions saved.',
            'perm_ids'  => array_map('intval', $perm_ids),
            'group_id'  => $group_id,
        ]);
    }

    /**
     * POST JSON — sync missing modules/perms/groups from manifest.
     */
    public function sync()
    {
        $this->require_permission(['groups.manage', 'users.manage'], 'dashboard');

        $stats = $this->permission_seed_service->sync(true);
        $this->load->model('Permission_model', 'permission_model');
        Permission_model::clear_cache();

        $this->json_ok([
            'message' => 'Permission catalog synchronized.',
            'stats'   => $stats,
            'matrix'  => $this->user_access_model->get_permission_matrix_rows(),
            'tree'    => $this->user_access_model->get_permission_tree(),
        ]);
    }

    private function _auto_sync_if_empty()
    {
        if ((int) $this->db->count_all('aauth_perm_module_main') > 0) {
            return;
        }

        $this->permission_seed_service->sync(true);
        $this->load->model('Permission_model', 'permission_model');
        Permission_model::clear_cache();
    }
}
