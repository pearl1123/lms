<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin user management & Aauth access control.
 *
 * @property User_model          $user_model
 * @property User_access_model   $user_access_model
 * @property User_access_service $user_access_service
 */
class Users extends KA_Controller {

    private const PER_PAGE = 20;

    public function __construct()
    {
        parent::__construct();
        $this->require_permission(['users.view', 'users.manage'], 'dashboard');
        $this->load->model('User_access_model', 'user_access_model');
        $this->load->model('User_model', 'user_model');
        $this->load->library('User_access_service', null, 'user_access_service');
        $this->load->library('Permission_seed_service', null, 'permission_seed_service');
        $this->_auto_sync_permissions_if_empty();
    }

    private function _auto_sync_permissions_if_empty()
    {
        if ((int) $this->db->count_all('aauth_perm_module_main') > 0) {
            return;
        }

        $this->permission_seed_service->sync(true);
        $this->load->model('Permission_model', 'permission_model');
        Permission_model::clear_cache();
        $this->_load_user_permissions();
    }

    public function index()
    {
        $filters = [
            'q'        => $this->get_param('q'),
            'status'   => $this->get_param('status'),
            'role'     => $this->get_param('role'),
            'group_id' => $this->get_int('group_id'),
        ];

        $page   = max(1, $this->get_int('page', 1));
        $total  = $this->user_access_model->count_users($filters);
        $offset = ($page - 1) * self::PER_PAGE;
        $users  = $this->user_access_model->get_users_list($filters, self::PER_PAGE, $offset);
        $users  = $this->user_access_model->attach_group_labels($users);
        foreach ($users as $u) {
            $this->user_access_model->enrich_user_lock_fields($u);
        }

        $this->render('administrator/users/management', [
            'page_title'   => 'User Management',
            'users'        => $users,
            'filters'      => $filters,
            'groups'       => $this->user_access_model->get_all_groups(),
            'pagination'   => [
                'page'       => $page,
                'per_page'   => self::PER_PAGE,
                'total'      => $total,
                'total_pages'=> (int) max(1, ceil($total / self::PER_PAGE)),
            ],
            'access_base'  => site_url('users'),
        ], [
            ['label' => 'Dashboard', 'url' => site_url('dashboard')],
            ['label' => 'User Management'],
        ]);
    }

    /**
     * JSON — user + groups + matrix + effective permissions.
     */
    public function access_data($user_id = 0)
    {
        $user = $this->user_access_model->get_user_row((int) $user_id);
        if ( ! $user) {
            $this->json_error('User not found.', [], 404);

            return;
        }

        $payload = $this->user_access_service->build_access_payload((int) $user->id);

        $this->json_ok([
            'user'           => $user,
            'groups'         => $payload['groups'],
            'all_groups'     => $this->user_access_model->get_all_groups(),
            'matrix'         => $payload['matrix'],
            'tree'           => $payload['tree'],
            'grant_ids'      => $payload['grant_ids'],
            'deny_ids'       => $payload['deny_ids'],
            'effective'      => $payload['effective'],
        ]);
    }

    /**
     * POST JSON — add user to group.
     */
    public function group_add()
    {
        $user_id  = (int) $this->post_param('user_id');
        $group_id = (int) $this->post_param('group_id');

        if ($user_id < 1 || $group_id < 1) {
            $this->json_error('Invalid user or group.');

            return;
        }

        if ( ! $this->user_access_model->get_user_row($user_id)) {
            $this->json_error('User not found.');

            return;
        }

        if ( ! $this->user_access_model->add_user_to_group($user_id, $group_id)) {
            $this->json_error('Unable to assign group.');

            return;
        }

        $group = $this->db->where('id', $group_id)->get('aauth_groups', 1)->row();
        $this->user_access_model->log_audit(
            $user_id,
            (int) $this->auth_user->id,
            'group_assigned',
            'group',
            $group_id,
            $group ? $group->name : null
        );

        $this->json_ok($this->_access_json_payload($user_id, 'Group assigned.'));
    }

    /**
     * POST JSON — remove user from group.
     */
    public function group_remove()
    {
        $user_id  = (int) $this->post_param('user_id');
        $group_id = (int) $this->post_param('group_id');

        if ($user_id < 1 || $group_id < 1) {
            $this->json_error('Invalid user or group.');

            return;
        }

        $this->user_access_model->remove_user_from_group($user_id, $group_id);

        $group = $this->db->where('id', $group_id)->get('aauth_groups', 1)->row();
        $this->user_access_model->log_audit(
            $user_id,
            (int) $this->auth_user->id,
            'group_removed',
            'group',
            $group_id,
            $group ? $group->name : null
        );

        $this->json_ok($this->_access_json_payload($user_id, 'Group removed.'));
    }

    /**
     * POST JSON — save user grant/deny overrides.
     */
    public function permissions_save()
    {
        $user_id = (int) $this->post_param('user_id');
        if ($user_id < 1 || ! $this->user_access_model->get_user_row($user_id)) {
            $this->json_error('User not found.');

            return;
        }

        $grant_ids = $this->post_param('grant_ids');
        $deny_ids  = $this->post_param('deny_ids');

        if ( ! is_array($grant_ids)) {
            $grant_ids = [];
        }
        if ( ! is_array($deny_ids)) {
            $deny_ids = [];
        }

        $old_grants = $this->user_access_model->get_user_grant_ids($user_id);
        $old_denies = $this->user_access_model->get_user_deny_ids($user_id);

        $this->user_access_model->save_user_permission_overrides($user_id, $grant_ids, $deny_ids);

        $new_grants = array_map('intval', $grant_ids);
        $new_denies = array_map('intval', $deny_ids);

        $this->_audit_perm_diff($user_id, $old_grants, $new_grants, 'perm_granted', 'perm_revoked');
        $this->_audit_perm_diff($user_id, $old_denies, $new_denies, 'perm_denied', 'perm_deny_removed');

        $this->json_ok($this->_access_json_payload($user_id, 'Permissions saved.'));
    }

    /**
     * POST JSON — reset failed login attempts / lockout for a user.
     */
    public function reset_login_lock()
    {
        $this->require_permission('users.manage', 'users');

        $user_id = (int) $this->post_param('user_id');
        if ($user_id < 1) {
            $this->json_error('Invalid user.');

            return;
        }

        $user = $this->user_access_model->get_user_row($user_id);
        if ( ! $user) {
            $this->json_error('User not found.', [], 404);

            return;
        }

        if ( ! $this->user_model->reset_login_lockout($user_id)) {
            $this->json_error('Unable to reset login lockout.');

            return;
        }

        $this->user_access_model->log_audit(
            $user_id,
            (int) $this->auth_user->id,
            'login_unlocked',
            'user',
            $user_id,
            json_encode([
                'failed_attempts_before' => (int) ($user->failed_attempts ?? 0),
                'locked_until_before'    => (string) ($user->locked_until ?? ''),
            ])
        );

        $updated = $this->user_access_model->get_user_row($user_id);

        $this->json_ok([
            'message' => 'Login attempts reset. The user can sign in again.',
            'user'    => $updated,
        ]);
    }

    /**
     * @param int[] $old_ids
     * @param int[] $new_ids
     */
    private function _audit_perm_diff($user_id, array $old_ids, array $new_ids, $add_action, $remove_action)
    {
        $old_ids = array_map('intval', $old_ids);
        $new_ids = array_map('intval', $new_ids);

        $added   = array_diff($new_ids, $old_ids);
        $removed = array_diff($old_ids, $new_ids);

        foreach ($added as $perm_id) {
            $perm = $this->db->where('id', (int) $perm_id)->get('aauth_perms', 1)->row();
            $this->user_access_model->log_audit(
                $user_id,
                (int) $this->auth_user->id,
                $add_action,
                'permission',
                (int) $perm_id,
                $perm ? $perm->name : null
            );
        }

        foreach ($removed as $perm_id) {
            $perm = $this->db->where('id', (int) $perm_id)->get('aauth_perms', 1)->row();
            $this->user_access_model->log_audit(
                $user_id,
                (int) $this->auth_user->id,
                $remove_action,
                'permission',
                (int) $perm_id,
                $perm ? $perm->name : null
            );
        }
    }

    /**
     * @param int    $user_id
     * @param string $message
     * @return array<string, mixed>
     */
    private function _access_json_payload($user_id, $message)
    {
        $payload = $this->user_access_service->build_access_payload($user_id);
        $user    = $this->user_access_model->get_user_row($user_id);
        $users   = $this->user_access_model->attach_group_labels([$user]);

        return array_merge([
            'message'      => $message,
            'groups'       => $payload['groups'],
            'all_groups'   => $this->user_access_model->get_all_groups(),
            'matrix'       => $payload['matrix'],
            'tree'         => $payload['tree'],
            'grant_ids'    => $payload['grant_ids'],
            'deny_ids'     => $payload['deny_ids'],
            'effective'    => $payload['effective'],
            'group_labels' => $users[0]->group_labels ?? '',
        ]);
    }

    /**
     * @param string $message
     * @param array  $extra
     * @param int    $http_code
     */
    protected function json_error($message, array $extra = [], $http_code = 400)
    {
        if ($http_code !== 400) {
            http_response_code((int) $http_code);
        }
        parent::json_error($message, $extra);
    }
}
