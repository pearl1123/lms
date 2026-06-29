<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Aauth user / group / permission administration (read-write on link tables only).
 */
class User_access_model extends CI_Model {

    /** @var string */
    protected $users_table = 'aauth_users';

    public function count_users(array $filters = [])
    {
        $this->_apply_user_filters($filters);

        return (int) $this->db->count_all_results();
    }

    /**
     * @return array<int, object>
     */
    public function get_users_list(array $filters = [], $limit = 25, $offset = 0)
    {
        $this->_apply_user_filters($filters);

        $query = $this->db
            ->select('u.id, u.employee_id, u.fullname, u.email, u.office, u.status, u.role, u.last_login, u.created_at, u.failed_attempts, u.locked_until')
            ->order_by('u.fullname', 'ASC')
            ->limit((int) $limit, (int) $offset)
            ->get();

        if ($query === false) {
            log_message('error', 'User_access_model::get_users_list failed: ' . json_encode($this->db->error()));

            return [];
        }

        return $query->result();
    }

    /**
     * @param array<int, object> $users
     */
    public function attach_group_labels(array $users)
    {
        if ($users === []) {
            return $users;
        }

        $ids = array_map(static function ($u) {
            return (int) $u->id;
        }, $users);

        $rows = $this->db
            ->select('utg.user_id, g.id AS group_id, g.name AS group_name')
            ->from('aauth_user_to_group utg')
            ->join('aauth_groups g', 'g.id = utg.group_id', 'inner')
            ->where_in('utg.user_id', $ids)
            ->where('g.archived', 0)
            ->order_by('g.name', 'ASC')
            ->get()
            ->result();

        $map = [];
        foreach ($rows as $row) {
            $uid = (int) $row->user_id;
            if ( ! isset($map[$uid])) {
                $map[$uid] = [];
            }
            $map[$uid][] = $row->group_name;
        }

        foreach ($users as $u) {
            $uid = (int) $u->id;
            $u->group_labels = isset($map[$uid]) ? implode(', ', $map[$uid]) : '';
            $u->groups       = $map[$uid] ?? [];
        }

        return $users;
    }

    public function get_user_row($user_id)
    {
        $user = $this->db
            ->select('id, employee_id, fullname, email, office, status, role, last_login, created_at, failed_attempts, locked_until')
            ->where('id', (int) $user_id)
            ->where('DELETED', 0)
            ->get($this->users_table, 1)
            ->row();

        return $this->enrich_user_lock_fields($user);
    }

    /**
     * Attach login lockout metadata for admin UI.
     *
     * @param object|null $user
     * @return object|null
     */
    public function enrich_user_lock_fields($user)
    {
        if ( ! $user) {
            return null;
        }

        $this->load->model('User_model', 'user_model');

        $failed = (int) ($user->failed_attempts ?? 0);
        $locked_until = $user->locked_until ?? null;
        $is_locked = $this->user_model->is_login_locked($user);

        $user->failed_attempts = $failed;
        $user->is_login_locked = $is_locked;
        $user->locked_until_display = $is_locked && $locked_until
            ? date('Y-m-d H:i', strtotime((string) $locked_until))
            : '';

        return $user;
    }

    /**
     * @return array<int, object>
     */
    public function get_all_groups()
    {
        return $this->db
            ->where('archived', 0)
            ->order_by('name', 'ASC')
            ->get('aauth_groups')
            ->result();
    }

    /**
     * @return array<int, object>
     */
    public function get_user_groups($user_id)
    {
        return $this->db
            ->select('g.id, g.name, g.definition')
            ->from('aauth_user_to_group utg')
            ->join('aauth_groups g', 'g.id = utg.group_id', 'inner')
            ->where('utg.user_id', (int) $user_id)
            ->where('g.archived', 0)
            ->order_by('g.name', 'ASC')
            ->get()
            ->result();
    }

    public function user_in_group($user_id, $group_id)
    {
        return (bool) $this->db
            ->where('user_id', (int) $user_id)
            ->where('group_id', (int) $group_id)
            ->count_all_results('aauth_user_to_group');
    }

    public function add_user_to_group($user_id, $group_id)
    {
        if ($this->user_in_group($user_id, $group_id)) {
            return true;
        }

        return (bool) $this->db->insert('aauth_user_to_group', [
            'user_id'  => (int) $user_id,
            'group_id' => (int) $group_id,
        ]);
    }

    public function remove_user_from_group($user_id, $group_id)
    {
        $this->db
            ->where('user_id', (int) $user_id)
            ->where('group_id', (int) $group_id)
            ->delete('aauth_user_to_group');

        return $this->db->affected_rows() > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get_permission_matrix_rows()
    {
        $mains = $this->db->order_by('name', 'ASC')->get('aauth_perm_module_main')->result();
        $rows  = [];

        foreach ($mains as $main) {
            $subs = $this->db
                ->where('module_main_id', (int) $main->id)
                ->order_by('name', 'ASC')
                ->get('aauth_perm_module_sub')
                ->result();

            foreach ($subs as $sub) {
                $cells = $this->_cells_for_submodule($sub);
                if ($cells === []) {
                    continue;
                }

                $rows[] = [
                    'module_main_id' => (int) $main->id,
                    'module_main'    => (string) $main->name,
                    'module_sub_id'  => (int) $sub->id,
                    'module_sub'     => (string) $sub->name,
                    'cells'          => $cells,
                ];
            }
        }

        return $rows;
    }

    /**
     * Nested tree for permission UI (module → submodule → actions).
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_permission_tree()
    {
        $tree = [];
        foreach ($this->get_permission_matrix_rows() as $row) {
            $main = (string) $row['module_main'];
            if ( ! isset($tree[$main])) {
                $tree[$main] = [
                    'name'       => $main,
                    'submodules' => [],
                ];
            }

            $actions = [];
            foreach ($row['cells'] as $key => $cell) {
                if ($key === 'extra' && is_array($cell)) {
                    foreach ($cell as $ex) {
                        $actions[] = $ex;
                    }
                } elseif (is_array($cell)) {
                    $actions[] = $cell;
                }
            }

            $tree[$main]['submodules'][] = [
                'name'    => (string) $row['module_sub'],
                'actions' => $actions,
            ];
        }

        return array_values($tree);
    }

    /**
     * Flat permission list for group matrix rows.
     *
     * @return array<int, array{perm_id:int, name:string, module_main:string, module_sub:string, label:string}>
     */
    public function get_flat_permissions()
    {
        $flat = [];
        foreach ($this->get_permission_matrix_rows() as $row) {
            foreach ($row['cells'] as $key => $cell) {
                if ($key === 'extra' && is_array($cell)) {
                    foreach ($cell as $ex) {
                        $flat[] = [
                            'perm_id'     => (int) $ex['perm_id'],
                            'name'        => (string) $ex['name'],
                            'module_main' => (string) $row['module_main'],
                            'module_sub'  => (string) $row['module_sub'],
                            'label'       => (string) $ex['label'],
                        ];
                    }
                } elseif (is_array($cell)) {
                    $flat[] = [
                        'perm_id'     => (int) $cell['perm_id'],
                        'name'        => (string) $cell['name'],
                        'module_main' => (string) $row['module_main'],
                        'module_sub'  => (string) $row['module_sub'],
                        'label'       => (string) $cell['label'],
                    ];
                }
            }
        }

        usort($flat, static function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $flat;
    }

    /**
     * @param int[] $perm_ids
     */
    public function save_group_permissions($group_id, array $perm_ids)
    {
        $group_id = (int) $group_id;
        if ($group_id < 1) {
            return false;
        }

        $perm_ids = array_values(array_unique(array_filter(array_map('intval', $perm_ids))));

        $this->db->where('group_id', $group_id)->delete('aauth_perm_to_group');

        foreach ($perm_ids as $pid) {
            if ($pid > 0) {
                $this->db->insert('aauth_perm_to_group', [
                    'perm_id'  => $pid,
                    'group_id' => $group_id,
                ]);
            }
        }

        return true;
    }

    /**
     * @return array<int, array{group: object, perm_ids: int[]}>
     */
    public function get_group_matrix_data()
    {
        $groups = $this->get_all_groups();
        $out    = [];

        foreach ($groups as $group) {
            $gid = (int) $group->id;
            $out[] = [
                'group'    => $group,
                'perm_ids' => $this->get_group_permission_ids([$gid]),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, int>
     */
    public function get_group_permission_ids($group_ids)
    {
        $group_ids = array_values(array_filter(array_map('intval', (array) $group_ids)));
        if ($group_ids === []) {
            return [];
        }

        $rows = $this->db
            ->select('perm_id')
            ->where_in('group_id', $group_ids)
            ->get('aauth_perm_to_group')
            ->result();

        $ids = [];
        foreach ($rows as $row) {
            $ids[(int) $row->perm_id] = (int) $row->perm_id;
        }

        return array_values($ids);
    }

    /**
     * @return array<int, int>
     */
    public function get_user_grant_ids($user_id)
    {
        $rows = $this->db
            ->select('perm_id')
            ->where('user_id', (int) $user_id)
            ->get('aauth_perm_to_user')
            ->result();

        return array_map(static function ($r) {
            return (int) $r->perm_id;
        }, $rows);
    }

    /**
     * @return array<int, int>
     */
    public function get_user_deny_ids($user_id)
    {
        if ( ! $this->db->table_exists('aauth_perm_deny_to_user')) {
            return [];
        }

        $rows = $this->db
            ->select('perm_id')
            ->where('user_id', (int) $user_id)
            ->get('aauth_perm_deny_to_user')
            ->result();

        return array_map(static function ($r) {
            return (int) $r->perm_id;
        }, $rows);
    }

    /**
     * @param int[] $grant_ids
     * @param int[] $deny_ids
     */
    public function save_user_permission_overrides($user_id, array $grant_ids, array $deny_ids)
    {
        $user_id   = (int) $user_id;
        $grant_ids = array_values(array_unique(array_map('intval', $grant_ids)));
        $deny_ids  = array_values(array_unique(array_map('intval', $deny_ids)));

        $this->db->where('user_id', $user_id)->delete('aauth_perm_to_user');

        if ($this->db->table_exists('aauth_perm_deny_to_user')) {
            $this->db->where('user_id', $user_id)->delete('aauth_perm_deny_to_user');
        }

        foreach ($grant_ids as $pid) {
            if ($pid > 0) {
                $this->db->insert('aauth_perm_to_user', [
                    'perm_id' => $pid,
                    'user_id' => $user_id,
                ]);
            }
        }

        if ($this->db->table_exists('aauth_perm_deny_to_user')) {
            foreach ($deny_ids as $pid) {
                if ($pid > 0) {
                    $this->db->insert('aauth_perm_deny_to_user', [
                        'perm_id' => $pid,
                        'user_id' => $user_id,
                    ]);
                }
            }
        }

        return true;
    }

    /**
     * @return array<int, object>
     */
    public function get_perms_by_ids(array $perm_ids)
    {
        $perm_ids = array_values(array_filter(array_map('intval', $perm_ids)));
        if ($perm_ids === []) {
            return [];
        }

        return $this->db
            ->where_in('id', $perm_ids)
            ->where('archived', 0)
            ->order_by('name', 'ASC')
            ->get('aauth_perms')
            ->result();
    }

    public function log_audit($affected_user_id, $admin_user_id, $action, $entity_type = null, $entity_id = null, $details = null)
    {
        if ( ! $this->db->table_exists('lms_user_access_audit')) {
            return false;
        }

        return (bool) $this->db->insert('lms_user_access_audit', [
            'affected_user_id' => (int) $affected_user_id,
            'admin_user_id'    => (int) $admin_user_id,
            'action'           => (string) $action,
            'entity_type'      => $entity_type,
            'entity_id'        => $entity_id !== null ? (int) $entity_id : null,
            'details'          => $details,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param object $sub
     * @return array<string, array{perm_id:int, name:string, label:string}>
     */
    private function _cells_for_submodule($sub)
    {
        $cells = [];

        foreach (['view' => 'View', 'add' => 'Add', 'edit' => 'Edit', 'delete' => 'Delete'] as $col => $label) {
            $name = trim((string) ($sub->{$col} ?? ''));
            if ($name === '') {
                continue;
            }
            $perm = $this->_perm_by_name($name);
            if ($perm) {
                $cells[$col] = [
                    'perm_id' => (int) $perm->id,
                    'name'    => (string) $perm->name,
                    'label'   => $label,
                ];
            }
        }

        $extras = $this->_parse_extra_permissions($sub->extra_permissions ?? '');
        if ($extras !== []) {
            $cells['extra'] = [];
            foreach ($extras as $ename) {
                $perm = $this->_perm_by_name($ename);
                if ($perm) {
                    $cells['extra'][] = [
                        'perm_id' => (int) $perm->id,
                        'name'    => (string) $perm->name,
                        'label'   => $ename,
                    ];
                }
            }
        }

        return $cells;
    }

    /**
     * @param string $raw
     * @return string[]
     */
    private function _parse_extra_permissions($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $out = [];
            foreach ($decoded as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $out[] = trim($item);
                } elseif (is_array($item) && ! empty($item['name'])) {
                    $out[] = trim((string) $item['name']);
                }
            }

            return $out;
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', $raw))));
    }

    /**
     * @param string $name
     * @return object|null
     */
    private function _perm_by_name($name)
    {
        return $this->db
            ->where('name', $name)
            ->group_start()
            ->where('archived', 0)
            ->or_where('archived IS NULL', null, false)
            ->group_end()
            ->get('aauth_perms', 1)
            ->row();
    }

    private function _apply_user_filters(array $filters)
    {
        $this->db->reset_query();
        $this->db->from($this->users_table . ' u');
        $this->db->where('u.DELETED', 0);

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $this->db->group_start();
            $this->db->like('u.fullname', $q);
            $this->db->or_like('u.employee_id', $q);
            $this->db->or_like('u.email', $q);
            $this->db->or_like('u.office', $q);
            $this->db->group_end();
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $this->db->where('u.status', $status);
        }

        $role = trim((string) ($filters['role'] ?? ''));
        if ($role !== '') {
            $this->db->where('u.role', $role);
        }

        $group_id = (int) ($filters['group_id'] ?? 0);
        if ($group_id > 0) {
            $this->db->where(
                'u.id IN (SELECT user_id FROM aauth_user_to_group WHERE group_id = ' . $group_id . ')',
                null,
                false
            );
        }
    }
}
