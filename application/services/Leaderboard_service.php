<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Leaderboard queries — global, department, monthly.
 */
class Leaderboard_service {

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Points_model', 'points_model');
        $this->CI->load->model('User_model', 'user_model');
    }

    public function is_available()
    {
        return $this->CI->points_model->schema_ready();
    }

    /**
     * @param int $limit
     * @return array{rows:array<int,object>,viewer_rank:?int,viewer_points:int}
     */
    public function get_global($limit = 25, $viewer_id = 0)
    {
        $rows = $this->CI->points_model->get_leaderboard_rows($limit);
        $viewer_id = (int) $viewer_id;

        return [
            'rows'          => $this->_decorate_rows($rows),
            'viewer_rank'   => $viewer_id > 0 ? $this->_rank_in_list($rows, $viewer_id) : null,
            'viewer_points' => $viewer_id > 0 ? $this->CI->points_model->get_user_total($viewer_id) : 0,
        ];
    }

    /**
     * @param string $department
     * @param int    $limit
     * @param int    $viewer_id
     */
    public function get_department($department, $limit = 25, $viewer_id = 0)
    {
        $rows = $this->CI->points_model->get_department_leaderboard_rows($department, $limit);

        return [
            'rows'          => $this->_decorate_rows($rows, 'total_points'),
            'viewer_rank'   => (int) $viewer_id > 0 ? $this->_rank_in_list($rows, (int) $viewer_id) : null,
            'viewer_points' => (int) $viewer_id > 0 ? $this->CI->points_model->get_user_total((int) $viewer_id) : 0,
            'department'    => trim((string) $department),
        ];
    }

    /**
     * @param int $limit
     * @param int $viewer_id
     */
    public function get_monthly($limit = 25, $viewer_id = 0)
    {
        $rows = $this->CI->points_model->get_monthly_leaderboard_rows($limit);

        return [
            'rows'          => $this->_decorate_rows($rows, 'monthly_points'),
            'viewer_rank'   => (int) $viewer_id > 0 ? $this->_rank_in_list($rows, (int) $viewer_id, 'monthly_points') : null,
            'viewer_points' => (int) $viewer_id > 0 ? $this->_monthly_points_for_user((int) $viewer_id) : 0,
            'period_label'  => date('F Y'),
        ];
    }

    /**
     * @param array<int,object> $rows
     * @param string            $points_field
     * @return array<int,object>
     */
    private function _decorate_rows(array $rows, $points_field = 'total_points')
    {
        $out = [];
        $rank = 1;
        foreach ($rows as $row) {
            $item = clone $row;
            $item->rank = $rank++;
            $item->points_display = (int) ($row->{$points_field} ?? $row->total_points ?? 0);
            $out[] = $item;
        }

        return $out;
    }

    /**
     * @param array<int,object> $rows
     * @param int               $user_id
     * @param string            $points_field
     * @return int|null
     */
    private function _rank_in_list(array $rows, $user_id, $points_field = 'total_points')
    {
        $rank = 1;
        foreach ($rows as $row) {
            if ((int) ($row->user_id ?? 0) === (int) $user_id) {
                return $rank;
            }
            $rank++;
        }

        return null;
    }

    /**
     * @param int $user_id
     * @return int
     */
    private function _monthly_points_for_user($user_id)
    {
        if ( ! $this->CI->points_model->schema_ready()) {
            return 0;
        }

        $start = date('Y-m-01 00:00:00');
        $r = $this->CI->db
            ->select_sum('points', 'pts')
            ->where('user_id', (int) $user_id)
            ->where('created_at >=', $start)
            ->get('points_transactions', 1)
            ->row();

        return $r ? (int) ($r->pts ?? 0) : 0;
    }
}
