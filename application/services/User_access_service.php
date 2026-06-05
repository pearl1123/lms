<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Resolves effective Aauth permissions for a user.
 */
class User_access_service {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('User_access_model', 'user_access_model');
    }

    /**
     * @return array{
     *   matrix: array,
     *   groups: array,
     *   grant_ids: int[],
     *   deny_ids: int[],
     *   effective: array
     * }
     */
    public function build_access_payload($user_id)
    {
        $user_id = (int) $user_id;
        $matrix  = $this->CI->user_access_model->get_permission_matrix_rows();
        $tree    = $this->CI->user_access_model->get_permission_tree();
        $groups  = $this->CI->user_access_model->get_user_groups($user_id);
        $group_ids = array_map(static function ($g) {
            return (int) $g->id;
        }, $groups);

        $grant_ids = $this->CI->user_access_model->get_user_grant_ids($user_id);
        $deny_ids  = $this->CI->user_access_model->get_user_deny_ids($user_id);
        $from_groups = $this->CI->user_access_model->get_group_permission_ids($group_ids);

        $effective_ids = array_values(array_diff(
            array_unique(array_merge($from_groups, $grant_ids)),
            $deny_ids
        ));

        $perm_map = [];
        foreach ($this->CI->user_access_model->get_perms_by_ids(
            array_unique(array_merge($from_groups, $grant_ids, $deny_ids, $effective_ids))
        ) as $perm) {
            $perm_map[(int) $perm->id] = $perm;
        }

        return [
            'matrix'    => $matrix,
            'tree'      => $tree,
            'groups'    => $groups,
            'grant_ids' => $grant_ids,
            'deny_ids'  => $deny_ids,
            'effective' => [
                'from_groups' => $this->_format_perm_list($from_groups, $perm_map),
                'granted'     => $this->_format_perm_list($grant_ids, $perm_map),
                'denied'      => $this->_format_perm_list($deny_ids, $perm_map),
                'effective'   => $this->_format_perm_list($effective_ids, $perm_map),
            ],
        ];
    }

    /**
     * @param int[] $ids
     * @param array<int, object> $perm_map
     * @return array<int, array{id:int, name:string, definition:string}>
     */
    private function _format_perm_list(array $ids, array $perm_map)
    {
        $out = [];
        foreach ($ids as $id) {
            if (isset($perm_map[$id])) {
                $p = $perm_map[$id];
                $out[] = [
                    'id'         => (int) $p->id,
                    'name'       => (string) $p->name,
                    'definition' => (string) ($p->definition ?? ''),
                ];
            }
        }

        usort($out, static function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $out;
    }
}
