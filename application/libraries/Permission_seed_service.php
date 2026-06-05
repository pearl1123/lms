<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Idempotent sync of LMS permission modules, submodules, perms, groups, and group links.
 */
class Permission_seed_service {

    /** @var CI_Controller */
    protected $CI;

    /** @var array<string, mixed> */
    protected $manifest;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->manifest = require APPPATH . 'config/lms_permissions.php';
    }

    /**
     * @param bool $assign_group_defaults When true, link default perms to groups (missing links only).
     * @return array<string, int>
     */
    public function sync($assign_group_defaults = true)
    {
        $stats = [
            'modules_created'    => 0,
            'submodules_created' => 0,
            'perms_created'      => 0,
            'groups_created'     => 0,
            'group_links_added'  => 0,
        ];

        $perm_name_to_id = $this->_sync_modules($stats);
        $this->_sync_groups($perm_name_to_id, $assign_group_defaults, $stats);
        $stats['admins_linked'] = $this->_ensure_admin_users_in_super_group();

        return $stats;
    }

    /**
     * Assign role=admin users with no group to Super Administrator (bootstrap only).
     */
    public function _ensure_admin_users_in_super_group()
    {
        $group = $this->CI->db
            ->where('name', 'Super Administrator')
            ->where('archived', 0)
            ->get('aauth_groups', 1)
            ->row();

        if ( ! $group) {
            return 0;
        }

        $group_id = (int) $group->id;
        $admins   = $this->CI->db
            ->select('id')
            ->where('role', 'admin')
            ->where('DELETED', 0)
            ->where('status', 'active')
            ->get('aauth_users')
            ->result();

        $linked = 0;
        foreach ($admins as $admin) {
            $uid = (int) $admin->id;
            $has = (int) $this->CI->db->where('user_id', $uid)->count_all_results('aauth_user_to_group');
            if ($has > 0) {
                continue;
            }
            if ($this->CI->db->insert('aauth_user_to_group', [
                'user_id'  => $uid,
                'group_id' => $group_id,
            ])) {
                $linked++;
            }
        }

        return $linked;
    }

    /**
     * @param array<string, int> $stats
     * @return array<string, int>
     */
    private function _sync_modules(array &$stats)
    {
        $perm_name_to_id = [];
        $modules         = $this->manifest['modules'] ?? [];

        foreach ($modules as $main_name => $subs) {
            $main_id = $this->_ensure_module_main($main_name, $stats);

            foreach ($subs as $sub_name => $def) {
                $sub_id = $this->_ensure_module_sub($main_id, $sub_name, $def, $stats);
                $cols   = ['view', 'add', 'edit', 'delete'];
                $update = [];

                foreach ($cols as $col) {
                    $pname = trim((string) ($def[$col] ?? ''));
                    if ($pname === '') {
                        continue;
                    }
                    $pid = $this->_ensure_perm($pname, $sub_id, $stats);
                    $perm_name_to_id[$pname] = $pid;
                    $update[$col]            = $pname;
                }

                $extras = $this->_normalize_extras($def['extra_permissions'] ?? []);
                $extra_names = [];
                foreach ($extras as $ex) {
                    $ename = $ex['name'];
                    $pid   = $this->_ensure_perm($ename, $sub_id, $stats);
                    $perm_name_to_id[$ename] = $pid;
                    $extra_names[] = $ename;
                }
                if ($extra_names !== []) {
                    $update['extra_permissions'] = json_encode($extra_names);
                }

                if ($update !== []) {
                    $this->CI->db->where('id', $sub_id)->update('aauth_perm_module_sub', $update);
                }
            }
        }

        return $perm_name_to_id;
    }

    /**
     * @param array<string, int> $perm_name_to_id
     * @param array<string, int> $stats
     */
    private function _sync_groups(array $perm_name_to_id, $assign_defaults, array &$stats)
    {
        $groups = $this->manifest['groups'] ?? [];
        $all_perm_ids = array_values($perm_name_to_id);

        foreach ($groups as $gname => $gdef) {
            $group_id = $this->_ensure_group($gname, (string) ($gdef['definition'] ?? ''), $stats);
            if ( ! $assign_defaults) {
                continue;
            }

            $want = $gdef['permissions'] ?? [];
            if (in_array('*', $want, true)) {
                $want_ids = $all_perm_ids;
            } else {
                $want_ids = [];
                foreach ($want as $pname) {
                    if (isset($perm_name_to_id[$pname])) {
                        $want_ids[] = $perm_name_to_id[$pname];
                    }
                }
            }

            foreach ($want_ids as $pid) {
                if ($this->_ensure_group_perm_link((int) $pid, $group_id)) {
                    $stats['group_links_added']++;
                }
            }
        }
    }

    private function _ensure_module_main($name, array &$stats)
    {
        $row = $this->CI->db->where('name', $name)->get('aauth_perm_module_main', 1)->row();
        if ($row) {
            return (int) $row->id;
        }

        $this->CI->db->insert('aauth_perm_module_main', ['name' => $name]);
        $stats['modules_created']++;

        return (int) $this->CI->db->insert_id();
    }

    /**
     * @param array<string, mixed> $def
     */
    private function _ensure_module_sub($main_id, $name, array $def, array &$stats)
    {
        $row = $this->CI->db
            ->where('module_main_id', (int) $main_id)
            ->where('name', $name)
            ->get('aauth_perm_module_sub', 1)
            ->row();

        if ($row) {
            return (int) $row->id;
        }

        $insert = [
            'module_main_id'    => (int) $main_id,
            'name'              => $name,
            'view'              => trim((string) ($def['view'] ?? '')),
            'add'               => trim((string) ($def['add'] ?? '')),
            'edit'              => trim((string) ($def['edit'] ?? '')),
            'delete'            => trim((string) ($def['delete'] ?? '')),
            'extra_permissions' => null,
        ];

        $extras = $this->_normalize_extras($def['extra_permissions'] ?? []);
        if ($extras !== []) {
            $insert['extra_permissions'] = json_encode(array_column($extras, 'name'));
        }

        $this->CI->db->insert('aauth_perm_module_sub', $insert);
        $stats['submodules_created']++;

        return (int) $this->CI->db->insert_id();
    }

    private function _ensure_perm($name, $module_sub_id, array &$stats)
    {
        $row = $this->CI->db
            ->where('name', $name)
            ->group_start()
            ->where('archived', 0)
            ->or_where('archived IS NULL', null, false)
            ->group_end()
            ->get('aauth_perms', 1)
            ->row();

        if ($row) {
            return (int) $row->id;
        }

        $this->CI->db->insert('aauth_perms', [
            'name'           => $name,
            'definition'     => 'LMS permission: ' . $name,
            'archived'       => 0,
            'module_sub_id'  => (int) $module_sub_id,
            'date_encoded'   => date('Y-m-d H:i:s'),
        ]);
        $stats['perms_created']++;

        return (int) $this->CI->db->insert_id();
    }

    private function _ensure_group($name, $definition, array &$stats)
    {
        $row = $this->CI->db
            ->where('name', $name)
            ->group_start()
            ->where('archived', 0)
            ->or_where('archived IS NULL', null, false)
            ->group_end()
            ->get('aauth_groups', 1)
            ->row();

        if ($row) {
            return (int) $row->id;
        }

        $this->CI->db->insert('aauth_groups', [
            'name'       => $name,
            'definition' => $definition,
            'archived'   => 0,
        ]);
        $stats['groups_created']++;

        return (int) $this->CI->db->insert_id();
    }

    private function _ensure_group_perm_link($perm_id, $group_id)
    {
        if ($perm_id < 1 || $group_id < 1) {
            return false;
        }

        $exists = $this->CI->db
            ->where('perm_id', $perm_id)
            ->where('group_id', $group_id)
            ->count_all_results('aauth_perm_to_group');

        if ($exists) {
            return false;
        }

        return (bool) $this->CI->db->insert('aauth_perm_to_group', [
            'perm_id'  => $perm_id,
            'group_id' => $group_id,
        ]);
    }

    /**
     * @param mixed $raw
     * @return array<int, array{name:string, label:string}>
     */
    private function _normalize_extras($raw)
    {
        if ( ! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = ['name' => trim($item), 'label' => trim($item)];
            } elseif (is_array($item) && ! empty($item['name'])) {
                $out[] = [
                    'name'  => trim((string) $item['name']),
                    'label' => trim((string) ($item['label'] ?? $item['name'])),
                ];
            }
        }

        return $out;
    }
}
