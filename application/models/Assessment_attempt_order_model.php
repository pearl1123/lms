<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Persistent assessment attempt order (pre/post take flows).
 *
 * @property CI_DB_mysqli_driver $db
 */
class Assessment_attempt_order_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function table_ready()
    {
        static $ready = null;
        if ($ready === null) {
            $ready = $this->db->table_exists('assessment_attempt_orders');
        }

        return $ready;
    }

    /**
     * @return object|null
     */
    public function get_active($user_id, $assessment_id)
    {
        if ( ! $this->table_ready()) {
            return null;
        }

        $r = $this->db
            ->where('assessment_id', (int) $assessment_id)
            ->where('user_id', (int) $user_id)
            ->where('status', 'active')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('assessment_attempt_orders');

        return ($r && $r->num_rows() > 0) ? $r->row() : null;
    }

    /**
     * @param array{assessment_id:int,user_id:int,enrollment_id?:int,assessment_version:int,is_legacy_version?:int,question_order_json:string,choice_order_json?:string} $data
     * @return int insert id or 0
     */
    public function create(array $data)
    {
        if ( ! $this->table_ready()) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $ok  = $this->db->insert('assessment_attempt_orders', [
            'assessment_id'       => (int) $data['assessment_id'],
            'user_id'             => (int) $data['user_id'],
            'enrollment_id'       => ! empty($data['enrollment_id']) ? (int) $data['enrollment_id'] : null,
            'assessment_version'  => max(1, (int) ($data['assessment_version'] ?? 1)),
            'is_legacy_version'   => ! empty($data['is_legacy_version']) ? 1 : 0,
            'question_order_json' => (string) $data['question_order_json'],
            'choice_order_json'   => isset($data['choice_order_json']) ? (string) $data['choice_order_json'] : null,
            'status'              => 'active',
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        return $ok ? (int) $this->db->insert_id() : 0;
    }

    public function mark_submitted($user_id, $assessment_id)
    {
        if ( ! $this->table_ready()) {
            return false;
        }

        return (bool) $this->db
            ->where('assessment_id', (int) $assessment_id)
            ->where('user_id', (int) $user_id)
            ->where('status', 'active')
            ->update('assessment_attempt_orders', [
                'status'     => 'submitted',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Close all non-submitted orders for retake (active → retaken).
     */
    public function mark_retaken($user_id, $assessment_id)
    {
        if ( ! $this->table_ready()) {
            return false;
        }

        return (bool) $this->db
            ->where('assessment_id', (int) $assessment_id)
            ->where('user_id', (int) $user_id)
            ->where_in('status', ['active', 'submitted'])
            ->update('assessment_attempt_orders', [
                'status'     => 'retaken',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function mark_legacy_version($order_id)
    {
        if ( ! $this->table_ready()) {
            return false;
        }

        return (bool) $this->db
            ->where('id', (int) $order_id)
            ->where('is_legacy_version', 0)
            ->update('assessment_attempt_orders', [
                'is_legacy_version' => 1,
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * @return array{question_ids:int[],choice_orders:array<int,int[]>}
     */
    public function decode_order_payload($row)
    {
        $q_ids = json_decode((string) ($row->question_order_json ?? '[]'), true);
        $c_raw = json_decode((string) ($row->choice_order_json ?? '{}'), true);

        if ( ! is_array($q_ids)) {
            $q_ids = [];
        }
        $q_ids = array_values(array_map('intval', $q_ids));

        $choice_orders = [];
        if (is_array($c_raw)) {
            foreach ($c_raw as $qid => $cids) {
                $choice_orders[(int) $qid] = array_values(array_map('intval', (array) $cids));
            }
        }

        return [
            'question_ids'  => $q_ids,
            'choice_orders' => $choice_orders,
        ];
    }

    /**
     * @param array{question_ids:int[],choice_orders:array<int,int[]>} $stored
     */
    public function encode_order_payload(array $stored)
    {
        return [
            'question_order_json' => json_encode(
                array_values(array_map('intval', (array) ($stored['question_ids'] ?? []))),
                JSON_UNESCAPED_UNICODE
            ),
            'choice_order_json'   => json_encode(
                (object) ($stored['choice_orders'] ?? []),
                JSON_UNESCAPED_UNICODE
            ),
        ];
    }

    public function count_retakes_for_assessment($assessment_id)
    {
        if ( ! $this->table_ready()) {
            return 0;
        }

        return (int) $this->db
            ->where('assessment_id', (int) $assessment_id)
            ->where('status', 'retaken')
            ->count_all_results('assessment_attempt_orders');
    }

    public function count_started_for_assessment($assessment_id)
    {
        if ( ! $this->table_ready()) {
            return 0;
        }

        return (int) $this->db
            ->where('assessment_id', (int) $assessment_id)
            ->count_all_results('assessment_attempt_orders');
    }
}
