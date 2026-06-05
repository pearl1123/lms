<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Runtime Aauth permission resolution for the logged-in user.
 */
class Permission_model extends CI_Model {

    /** @var array<int, array<string, bool>> */
    private static $cache_by_user = [];

    public function engine_is_active()
    {
        return (int) $this->db->count_all('aauth_perm_module_main') > 0
            && (int) $this->db->count_all('aauth_perms') > 0;
    }

    /**
     * @return array<string, bool> permission name => true
     */
    public function get_effective_map($user_id)
    {
        $user_id = (int) $user_id;
        if (isset(self::$cache_by_user[$user_id])) {
            return self::$cache_by_user[$user_id];
        }

        if ( ! $this->engine_is_active()) {
            self::$cache_by_user[$user_id] = [];

            return [];
        }

        $group_ids = $this->_user_group_ids($user_id);
        $from_groups = $this->_perm_ids_for_groups($group_ids);
        $grants      = $this->_user_grant_perm_ids($user_id);
        $denies      = $this->_user_deny_perm_ids($user_id);

        $effective_ids = array_values(array_diff(
            array_unique(array_merge($from_groups, $grants)),
            $denies
        ));

        $map = [];
        if ($effective_ids !== []) {
            $rows = $this->db
                ->select('name')
                ->where_in('id', $effective_ids)
                ->group_start()
                ->where('archived', 0)
                ->or_where('archived IS NULL', null, false)
                ->group_end()
                ->get('aauth_perms')
                ->result();

            foreach ($rows as $row) {
                $map[(string) $row->name] = true;
            }
        }

        self::$cache_by_user[$user_id] = $map;

        return $map;
    }

    public function user_has($user_id, $permission_name)
    {
        $permission_name = trim((string) $permission_name);
        if ($permission_name === '') {
            return true;
        }

        if ( ! $this->engine_is_active()) {
            return false;
        }

        $map = $this->get_effective_map($user_id);

        return isset($map[$permission_name]);
    }

    /**
     * @param string|string[] $permission_names
     */
    public function user_has_any($user_id, $permission_names)
    {
        foreach ((array) $permission_names as $name) {
            if ($this->user_has($user_id, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int[]
     */
    private function _user_group_ids($user_id)
    {
        $rows = $this->db
            ->select('utg.group_id')
            ->from('aauth_user_to_group utg')
            ->join('aauth_groups g', 'g.id = utg.group_id', 'inner')
            ->where('utg.user_id', (int) $user_id)
            ->where('g.archived', 0)
            ->get()
            ->result();

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row->group_id;
        }

        return $ids;
    }

    /**
     * @param int[] $group_ids
     * @return int[]
     */
    private function _perm_ids_for_groups(array $group_ids)
    {
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
     * @return int[]
     */
    private function _user_grant_perm_ids($user_id)
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
     * @return int[]
     */
    private function _user_deny_perm_ids($user_id)
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

    public static function clear_cache($user_id = null)
    {
        if ($user_id === null) {
            self::$cache_by_user = [];

            return;
        }

        unset(self::$cache_by_user[(int) $user_id]);
    }
}
