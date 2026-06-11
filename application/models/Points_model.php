<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Points ledger — user_points, points_transactions, points_rules.
 */
class Points_model extends CI_Model {

    public function schema_ready()
    {
        return $this->db->table_exists('user_points')
            && $this->db->table_exists('points_transactions')
            && $this->db->table_exists('points_rules');
    }

    /**
     * @param string $rule_key
     * @return object|null
     */
    public function get_rule($rule_key)
    {
        if ( ! $this->schema_ready()) {
            return null;
        }

        return $this->db
            ->where('rule_key', (string) $rule_key)
            ->where('is_active', 1)
            ->get('points_rules', 1)
            ->row();
    }

    /**
     * @param int $user_id
     * @return int
     */
    public function get_user_total($user_id)
    {
        if ( ! $this->schema_ready()) {
            return 0;
        }

        $row = $this->db
            ->select('total_points')
            ->where('user_id', (int) $user_id)
            ->get('user_points', 1)
            ->row();

        return $row ? (int) $row->total_points : 0;
    }

    /**
     * Idempotent award — returns transaction id or 0 if duplicate/skipped.
     *
     * @param int    $user_id
     * @param string $rule_key
     * @param string $reference_type
     * @param int    $reference_id
     * @param string $description
     * @return int
     */
    public function award($user_id, $rule_key, $reference_type, $reference_id, $description = '')
    {
        if ( ! $this->schema_ready()) {
            return 0;
        }

        $uid = (int) $user_id;
        if ($uid < 1) {
            return 0;
        }

        $rule = $this->get_rule($rule_key);
        if ( ! $rule || (int) $rule->points < 1) {
            return 0;
        }

        $points = (int) $rule->points;
        $ref_type = (string) $reference_type;
        $ref_id   = (int) $reference_id;
        $now      = date('Y-m-d H:i:s');

        $exists = $this->db
            ->where('user_id', $uid)
            ->where('rule_key', (string) $rule_key)
            ->where('reference_type', $ref_type)
            ->where('reference_id', $ref_id)
            ->count_all_results('points_transactions');
        if ($exists > 0) {
            return 0;
        }

        $this->db->trans_start();

        $this->db->insert('points_transactions', [
            'user_id'         => $uid,
            'rule_key'        => (string) $rule_key,
            'points'          => $points,
            'reference_type'  => $ref_type,
            'reference_id'    => $ref_id,
            'description'     => $description !== '' ? $description : (string) ($rule->label ?? ''),
            'created_at'      => $now,
        ]);
        $tx_id = (int) $this->db->insert_id();

        $row = $this->db->where('user_id', $uid)->get('user_points', 1)->row();
        if ($row) {
            $this->db->where('user_id', $uid)->update('user_points', [
                'total_points' => (int) $row->total_points + $points,
                'updated_at'   => $now,
            ]);
        } else {
            $this->db->insert('user_points', [
                'user_id'      => $uid,
                'total_points' => $points,
                'updated_at'   => $now,
            ]);
        }

        $this->db->trans_complete();

        return $this->db->trans_status() ? $tx_id : 0;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return array<int,object>
     */
    public function get_leaderboard_rows($limit = 20, $offset = 0)
    {
        if ( ! $this->schema_ready()) {
            return [];
        }

        $limit  = max(1, min(100, (int) $limit));
        $offset = max(0, (int) $offset);

        $r = $this->db
            ->select('up.user_id, up.total_points, u.fullname, u.employee_id, u.office AS department')
            ->from('user_points up')
            ->join('aauth_users u', 'u.id = up.user_id', 'left')
            ->where('u.DELETED', 0)
            ->where('u.status', 'active')
            ->order_by('up.total_points', 'DESC')
            ->order_by('u.fullname', 'ASC')
            ->limit($limit, $offset)
            ->get();

        return $r ? $r->result() : [];
    }

    /**
     * Monthly points from transactions (current calendar month).
     *
     * @param int $limit
     * @return array<int,object>
     */
    public function get_monthly_leaderboard_rows($limit = 20)
    {
        if ( ! $this->schema_ready()) {
            return [];
        }

        $limit = max(1, min(100, (int) $limit));
        $start = date('Y-m-01 00:00:00');

        $sql = 'SELECT pt.user_id, SUM(pt.points) AS monthly_points, u.fullname, u.employee_id, u.office AS department
            FROM points_transactions pt
            LEFT JOIN aauth_users u ON u.id = pt.user_id
            WHERE pt.created_at >= ?
            AND u.DELETED = 0 AND u.status = \'active\'
            GROUP BY pt.user_id, u.fullname, u.employee_id, u.office
            ORDER BY monthly_points DESC, u.fullname ASC
            LIMIT ' . (int) $limit;

        $r = $this->db->query($sql, [$start]);

        return $r ? $r->result() : [];
    }

    /**
     * @param string $department
     * @param int    $limit
     * @return array<int,object>
     */
    public function get_department_leaderboard_rows($department, $limit = 20)
    {
        if ( ! $this->schema_ready()) {
            return [];
        }

        $dept = trim((string) $department);
        if ($dept === '') {
            return [];
        }

        $limit = max(1, min(100, (int) $limit));

        $r = $this->db
            ->select('up.user_id, up.total_points, u.fullname, u.employee_id, u.office AS department')
            ->from('user_points up')
            ->join('aauth_users u', 'u.id = up.user_id', 'left')
            ->where('u.DELETED', 0)
            ->where('u.status', 'active')
            ->where('u.office', $dept)
            ->order_by('up.total_points', 'DESC')
            ->order_by('u.fullname', 'ASC')
            ->limit($limit)
            ->get();

        return $r ? $r->result() : [];
    }
}
